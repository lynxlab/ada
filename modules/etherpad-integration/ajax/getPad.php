<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\EtherpadIntegration\AMAEtherpadDataHandler;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadActions;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadClient;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadException;
use Lynxlab\ADA\Module\EtherpadIntegration\Pads;
use Lynxlab\ADA\Module\EtherpadIntegration\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(EtherpadActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMAEtherpadDataHandler $etDH
 */
$etDH = AMAEtherpadDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];
session_write_close();

// sanitizie data
$passedData = [];
$needed = [
    [
        'key' => 'padId',
        'sanitize' => fn ($v) => intval($v),
    ],
    [
        'key' => 'nodeId',
        'sanitize' => fn ($v) => trim($v),
    ],
    [
        'key' => 'groupId',
        'sanitize' => fn ($v) => trim($v),
    ],
];

foreach ($needed as $n) {
    if (array_key_exists($n['key'], $_REQUEST)) {
        $passedData[$n['key']] = $n['sanitize']($_REQUEST[$n['key']]);
    } else {
        $passedData[$n['key']] = null;
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        if (!EtherpadActions::canDo(EtherpadActions::ACCESS_PAD)) {
            throw new EtherpadException(translateFN('Utente non autorizzato'));
        }

        if (is_null($passedData['groupId'])) {
            throw new EtherpadException(translateFN('Passare un id gruppo'));
        } else {
            // check that passed group exists
            $groupObj = $etDH->findOneBy('Groups', [
                'groupId' => $passedData['groupId'],
                'isActive' => true,
            ]);
            if (is_null($groupObj)) {
                throw new EtherpadException(sprintf(translateFN('Il gruppo %s non esiste'), $passedData['groupId']));
            }
        }

        $nodeData = [];
        if (is_null($passedData['nodeId']) || $passedData['nodeId'] === Pads::INSTANCEPADID) {
            $passedData['nodeId'] = Pads::INSTANCEPADID;
            $padName = Pads::INSTANCEPADNAME;
        } else {
            if (false === DataValidator::validateNodeId($passedData['nodeId'])) {
                throw new EtherpadException(translateFN('ID nodo non valido'));
            } else {
                // check that passed node exists
                $nodeData = $etDH->getNodeInfo($passedData['nodeId']);
                if (AMADB::isError($nodeData)) {
                    throw new EtherpadException(sprintf(translateFN('Il nodo %s non esiste'), $passedData['nodeId']));
                }
                if (is_array($nodeData) && count($nodeData) > 0) {
                    $nodeData['node_id'] = $passedData['nodeId'];
                }
                $padName = sprintf(Pads::NODEPADNAME, $passedData['nodeId']);
            }
        }
        $whereArr = [
            'groupId' => $passedData['groupId'],
            'nodeId' => $passedData['nodeId'],
            'isActive' => true,
        ];
        if (!is_null($passedData['padId'])) {
            $whereArr['padId'] = $passedData['padId'];
        }
        $res = $etDH->findOneBy('Pads', $whereArr, [ 'creationDate' => 'DESC' ]);
        $realPadName = null;
        if ($res instanceof Pads) {
            $realPadName = $res->getRealPadName();
        } else {
            if (EtherpadActions::canDo(EtherpadActions::CREATE_PAD)) {
                // create an etherpad group and save its id locally
                $ethClient = new EtherpadClient(MODULES_ETHERPAD_APIKEY, Utils::getEtherpadURL());
                $rawPad = $ethClient->createGroupPad($groupObj->getGroupId(), $padName, Pads::getEmptyPadText($nodeData));
                if (property_exists($rawPad, 'padID') && strlen($rawPad->padID) > 0) {
                    if (
                        $etDH->savePad([
                        'groupId' => $groupObj->getGroupId(),
                        'nodeId' => $passedData['nodeId'],
                        'padName' => $padName,
                        'realPadName' => $rawPad->padID,
                        'isActive' => true,
                        ])
                    ) {
                        $realPadName = $rawPad->padID;
                    } else {
                        // delete the remote pad if something went wrong while saving locally
                        $ethClient->deletePad($rawPad->padID);
                        throw new EtherpadException(translateFN('Errore nel salvataggio dei dati, pad non creato'));
                    }
                } else {
                    throw new EtherpadException(translateFN('Impossibile creare il pad'));
                }
            } else {
                throw new EtherpadException(translateFN('Utente non abilitato a creare documenti condivisi'));
            }
        }
    } catch (Exception $e) {
        $res = $e;
    }

    if (AMADB::isError($res) || $res instanceof Exception) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
        $retArray['padName'] = null;
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = null;
        $retArray['padName'] = $realPadName;
    }

    header('Content-Type: application/json');
    echo json_encode($retArray);
}
die();

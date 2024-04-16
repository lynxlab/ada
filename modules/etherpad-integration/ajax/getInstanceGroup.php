<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\EtherpadIntegration\AMAEtherpadDataHandler;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadActions;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadClient;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadException;
use Lynxlab\ADA\Module\EtherpadIntegration\Groups;
use Lynxlab\ADA\Module\EtherpadIntegration\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

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
require_once(ROOT_DIR . '/browsing/include/browsing_functions.inc.php');
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
        'key' => 'instanceId',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
];

foreach ($needed as $n) {
    if (array_key_exists($n['key'], $_REQUEST)) {
        $passedData[$n['key']] = $n['sanitize']($_REQUEST[$n['key']]);
    } else {
        $passedData[$n['key']] = null;
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' && !is_null($passedData['instanceId'])) {
    try {
        $res = $etDH->findOneBy('Groups', [
            'instanceId' => $passedData['instanceId'],
            'isActive' => true,
        ]);
        $groupId = null;
        if ($res instanceof Groups) {
            $groupId = $res->getGroupId();
        } else {
            if (EtherpadActions::canDo(EtherpadActions::INSTANCE_GROUP_MAP)) {
                // check instance existence
                $instanceAr = $etDH->courseInstanceGet($passedData['instanceId']);
                if (!AMADB::isError($instanceAr)) {
                    // create an etherpad group and save its id locally
                    $ethClient = new EtherpadClient(MODULES_ETHERPAD_APIKEY, Utils::getEtherpadURL());
                    $rawGroup = $ethClient->createGroupIfNotExistsFor($passedData['instanceId']);
                    if (property_exists($rawGroup, 'groupID')) {
                        if (
                            $etDH->saveGroupMapping([
                            'groupId' => $rawGroup->groupID,
                            'instanceId' => $passedData['instanceId'],
                            'isActive' => true,
                            ])
                        ) {
                            $groupId = $rawGroup->groupID;
                        }
                    } else {
                        throw new EtherpadException(translateFN('Impossibile ottenere un id di gruppo'));
                    }
                } else {
                    throw new EtherpadException(translateFN('Istanza non trovata'));
                }
            } else {
                throw new EtherpadException(translateFN('Utente non abilitato a creare gruppi di lavoro per documenti condivisi'));
            }
        }
    } catch (Exception $e) {
        $res = $e;
    }

    if (AMADB::isError($res) || $res instanceof Exception) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
        $retArray['groupId'] = null;
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = null;
        $retArray['groupId'] = $groupId;
    }

    header('Content-Type: application/json');
    echo json_encode($retArray);
}
die();

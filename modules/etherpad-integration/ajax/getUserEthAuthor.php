<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\EtherpadIntegration\AMAEtherpadDataHandler;
use Lynxlab\ADA\Module\EtherpadIntegration\Authors;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadActions;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadClient;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadException;
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
        'key' => 'userId',
        'sanitize' => fn ($v) => intval($v),
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
        if (is_null($passedData['userId'])) {
            $tmpUser = $userObj;
        } else {
            // check user, findUser will redirect if not found :(
            if (AMADB::isError($GLOBALS['common_dh']->getUserType($passedData['userId']))) {
                throw new EtherpadException(translateFN('Utente sconosciuto'));
            }
            $tmpUser = MultiPort::findUser($passedData['userId']);
        }

        $userId = $tmpUser->getId();
        $userFullName = $tmpUser->getFullName();
        $res = $etDH->findOneBy('Authors', [
            'userId' => $userId,
            'isActive' => true,
        ]);
        $authorId = null;
        if ($res instanceof Authors) {
            $authorId = $res->getAuthorId();
        } else {
            if (EtherpadActions::canDo(EtherpadActions::USER_MAP)) {
                // create an etherpad author and save its id locally
                $ethClient = new EtherpadClient(MODULES_ETHERPAD_APIKEY, Utils::getEtherpadURL());
                $rawAuthor = $ethClient->createAuthorIfNotExistsFor($userId, $userFullName);
                if (property_exists($rawAuthor, 'authorID')) {
                    if (
                        $etDH->saveAuthorMapping([
                        'authorId' => $rawAuthor->authorID,
                        'userId' => $userId,
                        'isActive' => true,
                        ])
                    ) {
                        $authorId = $rawAuthor->authorID;
                    }
                } else {
                    throw new EtherpadException(translateFN('Impossibile ottenere un id autore'));
                }
            } else {
                throw new EtherpadException(translateFN('Utente non abilitato a creare autori'));
            }
        }
    } catch (Exception $e) {
        $res = $e;
    }

    if (AMADB::isError($res) || $res instanceof Exception) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
        $retArray['authorId'] = null;
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = null;
        $retArray['authorId'] = $authorId;
    }

    header('Content-Type: application/json');
    echo json_encode($retArray);
}
die();

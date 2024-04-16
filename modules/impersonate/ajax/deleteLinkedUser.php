<?php

/**
 * @package     impersonate module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\CollaboraACL\AMACollaboraACLDataHandler;
use Lynxlab\ADA\Module\Impersonate\AMAImpersonateDataHandler;
use Lynxlab\ADA\Module\Impersonate\ImpersonateActions;
use Lynxlab\ADA\Module\Impersonate\ImpersonateException;
use Lynxlab\ADA\Module\Impersonate\LinkedUsers;

use function Lynxlab\ADA\Main\AMA\DBRead\readUser;
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
[$allowedUsersAr, $neededObjAr] = array_values(ImpersonateActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMACollaboraACLDataHandler $impDH
 */
$impDH = AMAImpersonateDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];
session_write_close();

// sanitizie data
$passedData = [];
$needed = [
    [
        'key' => 'linkedType',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
    [
        'key' => 'sourceId',
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

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed data
     */
    if (array_key_exists('sess_userObj', $_SESSION)) {
        $sourceUser = readUser($passedData['sourceId']);
        if ($passedData['linkedType'] > 0) {
            // if the session user has an inactive link, activate it
            $linkedObj = $impDH->findBy('LinkedUsers', [
                'source_id' => $sourceUser->getId(),
                'source_type' => $sourceUser->getType(),
                'linked_type' => $passedData['linkedType'],
                'is_active' => true,
            ]);
            if (is_array($linkedObj) && count($linkedObj) > 0) {
                $linkedObj = reset($linkedObj);
                $linkedObj->setIsActive(false);
                $linkUpdate = true;
            }

            if (isset($linkedObj) && $linkedObj instanceof LinkedUsers) {
                $res = $impDH->saveLinkedUsers($linkedObj->toArray(), $linkUpdate);
            }
        } else {
            $res = new ImpersonateException(translateFN('Passare il tipo di utente da scollegare'));
        }
    } else {
        $res = new ImpersonateException(translateFN('Nessun utente in sessione'));
    }
}

if (AMADB::isError($res) || $res instanceof ImpersonateException) {
    // if it's an error display the error message
    $retArray['status'] = "ERROR";
    $retArray['msg'] = $res->getMessage();
} else {
    $retArray['status'] = "OK";
    $retArray['msg'] = translateFN("Utente scollegato");
    $retArray['reload'] = true;
}

header('Content-Type: application/json');
echo json_encode($retArray);

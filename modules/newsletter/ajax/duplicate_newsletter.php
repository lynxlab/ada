<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Newsletter\AMANewsletterDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$GLOBALS['dh'] = AMANewsletterDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['id'])) {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Non so cosa duplicare")];
    } else {
        $result = $dh->duplicateNewsletter(intval($_POST['id']));

        if (!AMADB::isError($result)) {
            $retArray =  ["status" => "OK", "msg" => translateFN("Newsletter duplicata")];
        } else {
            $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore di duplicazione") ];
        }
    }
} else {
    $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

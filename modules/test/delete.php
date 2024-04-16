<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
//require_once(ROOT_DIR.'/include/HtmlLibrary/ServicesModuleHtmlLib.inc.php');

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$res = $dh->testDeleteNodeTest($_GET['id_nodo']);
if (!$dh->isError($res) && $res) {
    echo 1;
} else {
    echo 0;
}

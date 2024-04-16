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

$retval = [];

if (isset($_GET['selected'])  && intval($_GET['selected']) > 0) {
    $instancesArr = $dh->courseInstanceGetList(['title'], intval($_GET['selected']));

    if (!AMADB::isError($instancesArr)) {
        array_push($retval, [ "label" => translateFN('Tutte le istanze'), "value" => 0]);
        for ($i = 0; $i < count($instancesArr); $i++) {
            array_push($retval, [ "label" => $instancesArr[$i][1], "value" => intval($instancesArr[$i][0])  ]);
        }
    }
}
echo json_encode($retval);

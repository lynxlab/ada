<?php

use Lynxlab\ADA\Main\AMA\AMADB;

use function \translateFN;

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version        0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = [];
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
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$GLOBALS['dh'] = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['id'])) {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Non so cosa cancellare")];
    } else {
        $result = $dh->deleteCompleteRule(intval($_POST['id']));

        if (!AMADB::isError($result)) {
            $retArray =  ["status" => "OK", "msg" => translateFN("Regola cancellata")];
        } else {
            $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore di cancellazione") ];
        }
    }
} else {
    $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

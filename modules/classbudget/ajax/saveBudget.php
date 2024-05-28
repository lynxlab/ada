<?php

/**
 * CLASSBUDGET MODULE.
 *
 * @package        classbudget module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2015, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classbudget
 * @version        0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classbudget\AMAClassbudgetDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

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

$GLOBALS['dh'] = AMAClassbudgetDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['type']) && strlen($_POST['type']) > 0
) {
    // $data is an array coming from post, $type is a string coming from post
    if (isset($data) && is_array($data) && count($data) > 0) {
        $res = $GLOBALS['dh']->saveCosts($data, trim($type));
        if (AMADB::isError($res)) {
            $retArray = ["status" => "ERROR", "msg" => translateFN("Errore nel salvataggio")];
        } else {
            $retArray = ["status" => "OK", "msg" => translateFN("Costi salvati") . '<br/><br/>' . translateFN('Attendere il ricaricamento della pagina') . '...', "callback" => "self.document.location.reload();"];
        }
    } else {
        $retArray = ["status" => "OK", "msg" => translateFN("Niente da salvare")];
    }
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

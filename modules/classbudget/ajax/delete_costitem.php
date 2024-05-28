<?php

/**
 * CLASSBUDGET MODULE.
 *
 * @package         classbudget module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2015, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classbudget
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classbudget\AMAClassbudgetDataHandler;
use Lynxlab\ADA\Module\Classbudget\CostitemBudgetManagement;
use Lynxlab\ADA\Module\Classbudget\CostItemManagement;

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
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

$GLOBALS['dh'] = AMAClassbudgetDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['cost_item_id'])) {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Non so cosa cancellare")];
    } else {
        $costItemManager = new CostItemManagement($GLOBALS['dh']->getCostItem($cost_item_id));
        $result = $GLOBALS['dh']->deleteCostItem(intval($_POST['cost_item_id']));

        if (!AMADB::isError($result)) {
            $retArray = ["status" => "OK", "msg" => translateFN("Voce cancellata")];
            // get the new item cost table to be displayed
            $costItemBudget = new CostitemBudgetManagement($costItemManager->id_istanza_corso);
            $htmlObj = $costItemBudget->run(MODULES_CLASSBUDGET_EDIT);
            $retArray['html'] = $htmlObj->getHtml();
        } else {
            $retArray = ["status" => "ERROR", "msg" => translateFN("Errore di cancellazione")];
        }
    }
} else {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

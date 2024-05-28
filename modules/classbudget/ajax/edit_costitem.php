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

$retArray = ['status' => 'ERROR'];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed costitem data
     */
    // build a costitem with passed POST data
    $costItemManager = new CostItemManagement($_POST);
    // try to save it
    $res = $GLOBALS['dh']->saveCosts([$costItemManager->toArray()], 'item');

    if (AMADB::isError($res)) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = translateFN('Voce di costo salvata');
        // get the new item cost table to be displayed
        $costItemBudget = new CostitemBudgetManagement($costItemManager->id_istanza_corso);
        $htmlObj = $costItemBudget->run(MODULES_CLASSBUDGET_EDIT);
        $retArray['html'] = $htmlObj->getHtml();
    }
} elseif (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' &&
    isset($_GET['cost_item_id']) && intval(trim($_GET['cost_item_id'])) > 0
) {
    /**
     * it's a GET with an cost_item_id, load it and display
     */
    $cost_item_id = intval(trim($_GET['cost_item_id']));
    // try to load it
    $res = $GLOBALS['dh']->getCostItem($cost_item_id);

    if (AMADB::isError($res)) {
        // if it's an error display the error message without the form
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
    } else {
        // display the form with loaded data
        $costItemManager = new CostItemManagement($res);
        $data = $costItemManager->run(MODULES_CLASSBUDGET_EDIT_COST_ITEM);

        $retArray['status'] = "OK";
        $retArray['html'] = $data['htmlObj']->getHtml();
        $retArray['dialogTitle'] = translateFN('Modifica Voce di Costo');
    }
} else {
    /**
     * it's a get without a cost_item_id, display the empty form
     */
    if (isset($_GET['course_instance_id']) && intval($_GET['course_instance_id']) > 0) {
        $course_instance_id = intval($_GET['course_instance_id']);
    } else {
        $course_instance_id = null;
    }
    $costItemManager = new CostItemManagement(['id_istanza_corso' => $course_instance_id]);
    $data = $costItemManager->run(MODULES_CLASSBUDGET_EDIT_COST_ITEM);

    $retArray['status'] = "OK";
    $retArray['html'] = $data['htmlObj']->getHtml();
    $retArray['dialogTitle'] = translateFN('Nuova Voce di Costo');
}

echo json_encode($retArray);

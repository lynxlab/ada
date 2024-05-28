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

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classbudget\AMAClassbudgetDataHandler;

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

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($tableName) && strlen($tableName) > 0 && isset($fieldName) && strlen($fieldName) > 0) {
        if (!isset($primaryKey) || (isset($primaryKey) && strlen($primaryKey) <= 0)) {
            $primaryKey = null;
        }
        echo json_encode($GLOBALS['dh']->doSearchForAutocomplete($tableName, $fieldName, trim($term), $primaryKey));
    }
}

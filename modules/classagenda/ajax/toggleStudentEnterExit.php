<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;

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
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

// MODULE's OWN IMPORTS
$retStr = '';
if (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($id_student) && intval($id_student) > 0 &&
    isset($classagenda_calendars_id) && intval($classagenda_calendars_id) > 0 &&
    isset($isEntering)
) {
    if ($GLOBALS['dh']->saveRollCallEnterExit($id_student, $classagenda_calendars_id, $isEntering)) {
        $retStr = (($isEntering) ? translateFN('Entrata alle: ') : translateFN('Uscita alle: ')) . Utilities::ts2tmFN(time());
        $retStr .= '<br/>';
    }
}
die($retStr);

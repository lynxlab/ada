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

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

// MODULE's OWN IMPORTS

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $selTester = null;
    if (isset($_SESSION['sess_selected_tester'])) {
        $selTester = $_SESSION['sess_selected_tester'];
    } else {
        switch ($_SESSION['sess_userObj']->getType()) {
            case AMA_TYPE_STUDENT:
                if (isset($courseID) && intval($courseID) > 0) {
                    $selTesterArr = AMACommonDataHandler::getInstance()->getTesterInfoFromIdCourse($courseID);
                    if (!AMADB::isError($selTesterArr) && is_array($selTesterArr) && isset($selTesterArr['puntatore'])) {
                        $selTester = $selTesterArr['puntatore'];
                    }
                }
                break;
            default:
                $selTester = $_SESSION['sess_userObj']->getDefaultTester();
                break;
        }
    }

    $GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($selTester));

    if (isset($instanceID) && intval($instanceID) > 0) {
        $retVal = 0;
        $result = $GLOBALS['dh']->getUniqueStudentsForCourseInstances([intval($instanceID)]);
        if (!AMADB::isError($result)) {
            $retVal = intval(count($result));
        }
        header('Content-Type: application/json');
        echo json_encode(['value' => $retVal]);
    }
}

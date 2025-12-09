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
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
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


$retArray = [
    'serviceTypeString' => translateFN('Tipo di corso sconosciuto'),
    'courseID' => 0,
    'duration_hours' => 0,
    'endDate' => '',
    'isOnline' => true,
    'isPresence' => false,
    'lessons_count' => '',
];

if (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' &&
    isset($instanceID) && intval($instanceID) > 0 && isset($courseID) && intval($courseID) > 0
) {
    $selTester = null;
    if (isset($_SESSION['sess_selected_tester'])) {
        $selTester = $_SESSION['sess_selected_tester'];
    } else {
        switch ($_SESSION['sess_userObj']->getType()) {
            case AMA_TYPE_STUDENT:
                $selTesterArr = AMACommonDataHandler::getInstance()->getTesterInfoFromIdCourse($courseID);
                if (!AMADB::isError($selTesterArr) && is_array($selTesterArr) && isset($selTesterArr['puntatore'])) {
                    $selTester = $selTesterArr['puntatore'];
                }
                break;
            default:
                $selTester = $_SESSION['sess_userObj']->getDefaultTester();
                break;
        }
    }

    $GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($selTester));
    $retArray = null;

    $courseInstanceObj = DBRead::readCourseInstanceFromDB($instanceID);

    if (!AMADB::isError($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) {
        $retArray['courseID'] = intval($courseID);
        $retArray['duration_hours'] = $courseInstanceObj->getDurationHours();
        $eventsArr = $GLOBALS['dh']->getClassRoomEventsForCourseInstance($instanceID, null);
        if (defined('MODULES_CLASSAGENDA_EVENT_CANCEL') && MODULES_CLASSAGENDA_EVENT_CANCEL) {
            $eventsArr = array_filter($eventsArr, fn ($el) => empty($el['cancelled']));
        }
        $retArray['allocated_hours'] = 0;
        $retArray['lessons_count'] = 0;
        $retArray['endDate'] = $courseInstanceObj->getEndDate();
        $serviceLevel = $courseInstanceObj->getServiceLevel();
        if (is_null($serviceLevel)) {
            $serviceLevel = DEFAULT_SERVICE_TYPE;
        }
        /**
         * service level online or presence as bool,
         * $GLOBALS defined in config/config_main.inc.php
         */
        $retArray['isOnline']   = in_array($serviceLevel, $GLOBALS['onLineServiceTypes']);
        $retArray['isPresence'] = in_array($serviceLevel, $GLOBALS['presenceServiceTypes']);

        /**
         * service level as a string
         */
        if (isset($_SESSION['service_level'][$serviceLevel])) {
            $retArray['serviceTypeString'] = $_SESSION['service_level'][$serviceLevel];
        } else {
            switch ($serviceLevel) {
                case ADA_SERVICE_ONLINECOURSE:
                    $retArray['serviceTypeString'] = translateFN('Corso Online');
                    break;
                case ADA_SERVICE_PRESENCECOURSE:
                    $retArray['serviceTypeString'] = translateFN('Corso in Presenza');
                    break;
                case ADA_SERVICE_MIXEDCOURSE:
                    $retArray['serviceTypeString'] = translateFN('Corso misto Online e Presenza');
                    break;
            }
        }

        if (!AMADB::isError($eventsArr) && is_array($eventsArr) && count($eventsArr) > 0) {
            $retArray['lessons_count'] = count($eventsArr);
            foreach ($eventsArr as $event) {
                $retArray['allocated_hours'] += $event['end'] - $event['start'];
            }
            $retArray['allocated_hours'] *= 1000;
        }
    } else {
        $retArray['duration_hours'] = 0;
    }
}
if (!is_null($retArray)) {
    header('Content-Type: application/json');
    die(json_encode($retArray));
}

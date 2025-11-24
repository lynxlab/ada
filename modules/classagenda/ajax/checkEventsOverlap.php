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

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Utilities;
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

$dh = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retVal['isOverlap'] = false;
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($start) && strlen($start) > 0 && isset($end) && strlen($end) > 0) {
        $eventID = (isset($eventID) && intval($eventID) > 0) ? intval($eventID) : null;
        $tutorID = (isset($tutorID) && intval($tutorID) > 0) ? intval($tutorID) : null;
        $classroomID = (isset($classroomID) && intval($classroomID) > 0) ? intval($classroomID) : null;
        $instanceID = (isset($instanceID) && intval($instanceID) > 0) ? intval($instanceID) : null;

        [$startDate, $startTime] = explode('T', $start);
        [$endDate, $endTime] = explode('T', $end);

        [$startYear, $startMonth, $startDay] = explode('-', $startDate);
        [$endYear, $endMonth, $endDay] = explode('-', $endDate);

        $startTS = $dh::dateToTs($startDay . '/' . $startMonth . '/' . $startYear, $startTime);
        $endTS = $dh::dateToTs($endDay . '/' . $endMonth . '/' . $endYear, $endTime);

        foreach (
            [
                'sameevent' => function () use ($dh, $startTS, $endTS, $tutorID, $classroomID, $instanceID, $eventID) {
                    $conds = [
                        'id_utente_tutor' => ((int) $tutorID === 0) ? null : (int) $tutorID,
                        'id_istanza_corso' => ((int) $instanceID === 0) ? null : (int) $instanceID,
                        'start' => $startTS,
                        'end' => $endTS,
                    ];
                    if (ModuleLoaderHelper::isLoaded('CLASSROOM')) {
                        $conds['id_classroom'] = ((int) $classroomID === 0) ? null : (int) $classroomID;
                    }
                    return $dh->checkEventsOverlap($startTS, $endTS, $conds, $eventID);
                },
                'tutor' => fn () => ($tutorID > 0 ? $dh->checkEventsOverlap($startTS, $endTS, ['id_utente_tutor' => (int) $tutorID], $eventID) : false),
                'classroom' => function () use ($dh, $startTS, $endTS, $classroomID, $eventID) {
                    if (ModuleLoaderHelper::isLoaded('CLASSROOM') && $classroomID > 0) {
                        return $dh->checkEventsOverlap($startTS, $endTS, ['id_classroom' => (int) $classroomID], $eventID);
                    }
                    return false;
                },
            ] as $what => $checkCallBack
        ) {
            if (!$retVal['isOverlap']) {
                $result = $checkCallBack();
                if (!AMADB::isError($result) && $result !== false && count($result) > 0) {
                    $retVal['isOverlap'] = true;
                    $retVal['data'] = $result;
                    $retVal['data']['date'] = Utilities::ts2dFN($result['start']);
                    $retVal['data']['start'] = substr(Utilities::ts2tmFN($result['start']), 0, -3);
                    $retVal['data']['end'] = substr(Utilities::ts2tmFN($result['end']), 0, -3);
                    $retVal['data']['what'] = $what;

                    $courseInstance = $dh->courseInstanceGet($result['id_istanza_corso']);
                    if (!AMADB::isError($courseInstance)) {
                        $course = $dh->getCourseInfoForCourseInstance($result['id_istanza_corso']);
                        if (!AMADB::isError($course)) {
                            $retVal['data']['instanceName'] = $course['titolo'] . ' &gt; ' . $courseInstance['title'];
                        }
                    }
                }
            }
        }
    }
}
header('Content-Type: application/json');
die(json_encode($retVal));

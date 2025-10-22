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
    if (isset($start) && strlen($start) > 0 && isset($end) && strlen($end) > 0 && isset($tutorID) && intval($tutorID) > 0) {
        $eventID = (isset($eventID) && intval($eventID) > 0) ? intval($eventID) : null;

        [$startDate, $startTime] = explode('T', $start);
        [$endDate, $endTime] = explode('T', $end);

        [$startYear, $startMonth, $startDay] = explode('-', $startDate);
        [$endYear, $endMonth, $endDay] = explode('-', $endDate);

        $result = $dh->checkTutorOverlap(
            $dh::dateToTs($startDay . '/' . $startMonth . '/' . $startYear, $startTime),
            $dh::dateToTs($endDay . '/' . $endMonth . '/' . $endYear, $endTime),
            intval($tutorID),
            $eventID
        );

        if (!AMADB::isError($result) && $result !== false && count($result) > 0) {
            $retVal['isOverlap'] = true;
            $retVal['data'] = $result;
            $retVal['data']['date'] = Utilities::ts2dFN($result['start']);
            $retVal['data']['start'] = substr(Utilities::ts2tmFN($result['start']), 0, -3);
            $retVal['data']['end'] = substr(Utilities::ts2tmFN($result['end']), 0, -3);

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
header('Content-Type: application/json');
die(json_encode($retVal));

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

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;

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

$retVal = '<option value=0>' . translateFN('Nessuna istanza trovata') . '</option>';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $returnHTML = '';
    $filterInstanceState =  (isset($filterInstanceState) && intval($filterInstanceState) > 0) ?
        $filterInstanceState : MODULES_CLASSAGENDA_ALL_INSTANCES;

    if (isset($_SESSION['sess_userObj']) && in_array($_SESSION['sess_userObj']->getType(), [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR])) {
        $dh = $GLOBALS['dh'];

        // first of all, get the coure list
        $courseList = $dh->findCoursesList(['titolo'], '1 ORDER BY titolo ASC');
        // first element of returned array is always the courseId, array is NOT assoc
        if (!AMADB::isError($courseList)) {
            // for each course in the list...
            foreach ($courseList as $courseItem) {
                // ... get the subscribeable course instance list...
                if ($filterInstanceState == MODULES_CLASSAGENDA_STARTED_INSTANCES) {
                    $whereClause = 'id_corso=' . $courseItem[0] . ' AND data_inizio>0 AND data_fine>=' . time() . ' and durata>0 ORDER BY title ASC';
                } elseif ($filterInstanceState == MODULES_CLASSAGENDA_NONSTARTED_INSTANCES) {
                    $whereClause = 'id_corso=' . $courseItem[0] . ' AND data_inizio<=0 ORDER BY title ASC';
                } elseif ($filterInstanceState == MODULES_CLASSAGENDA_CLOSED_INSTANCES) {
                    $whereClause = 'id_corso=' . $courseItem[0] . ' AND data_fine<' . time() . ' ORDER BY title ASC';
                } else {
                    $whereClause = 'id_corso=' . $courseItem[0] . ' ORDER BY title ASC';
                }
                $courseInstances = $dh->courseInstanceFindList(['title'], $whereClause);

                if ($_SESSION['sess_userObj']->getType() == AMA_TYPE_SWITCHER) {
                    /**
                     * all instances
                     */
                    $filterInstances = null;
                } elseif ($_SESSION['sess_userObj']->getType() == AMA_TYPE_TUTOR) {
                    /**
                     * get tutored instances
                     */
                    $tmpArr = $dh->courseTutorInstanceGet($_SESSION['sess_userObj']->getId(), $_SESSION['sess_userObj']->isSuper());
                    $filterInstances = [];
                    if (!AMADB::isError($tmpArr) && is_array($tmpArr) && count($tmpArr) > 0) {
                        // index 0 is the instance id
                        foreach ($tmpArr as $tmpEl) {
                            $filterInstances[] = $tmpEl[0];
                        }
                    }
                } else {
                    /**
                     * no instances
                     */
                    $filterInstances = [];
                }

                // first element of returned array is always the instanceId, array is NOT assoc
                if (!AMADB::isError($courseInstances) && count($courseInstances) > 0) {
                    // ...and, for each instance in the list...
                    foreach ($courseInstances as $courseInstanceItem) {
                        // ... put its ID and human readble course instance name, course title and course name as an <option> in the <select>
                        if (
                            is_null($filterInstances) ||
                            (!is_null($filterInstances) && in_array($courseInstanceItem[0], $filterInstances))
                        ) {
                            $returnHTML .= '<option value=' . $courseInstanceItem[0] . ' data-idcourse=' . $courseItem[0] . '>' . $courseItem[1] . ' > ' . $courseInstanceItem[1] . '</option>';
                        }
                    }
                }
            }
        }
    } elseif (isset($_SESSION['sess_userObj']) && $_SESSION['sess_userObj']->getType() == AMA_TYPE_STUDENT) {
        /**
         * get instances for which student is either subscribed or completed or terminated
         */
        $serviceProviders = $_SESSION['sess_userObj']->getTesters();
        $courseInstances = [];
        if (!AMADB::isError($serviceProviders) && is_array($serviceProviders) && count($serviceProviders) > 0) {
            foreach ($serviceProviders as $Provider) {
                $provider_dh = AMADataHandler::instance(MultiPort::getDSN($Provider));
                $courseInstances_provider = $provider_dh->getCourseInstancesForThisStudent($_SESSION['sess_userObj']->getId(), true);
                if (
                    !AMADB::isError($courseInstances_provider) &&
                    is_array($courseInstances_provider) &&
                    count($courseInstances_provider) > 0
                ) {
                    $courseInstances = array_merge($courseInstances, $courseInstances_provider);
                }
            }
        }
        /**
         *  filter course instance that are associated to a level of service having nonzero
         *  value in isPublic, so that all instances of public courses will not be shown here
         *  and filter course instances that user has request through form select field
         */
        $courseInstances = array_filter($courseInstances, function ($courseInstance) use ($filterInstanceState) {
            if (is_null($courseInstance['tipo_servizio'])) {
                $courseInstance['tipo_servizio'] = DEFAULT_SERVICE_TYPE;
            }
            if (intval($_SESSION['service_level_info'][$courseInstance['tipo_servizio']]['isPublic']) === 0) {
                switch ($filterInstanceState) {
                    default:
                        return false;
                    case MODULES_CLASSAGENDA_ALL_INSTANCES:
                        return true;
                    case MODULES_CLASSAGENDA_NONSTARTED_INSTANCES:
                        return $courseInstance['data_inizio'] <= 0;
                    case MODULES_CLASSAGENDA_STARTED_INSTANCES:
                        return $courseInstance['status'] == ADA_STATUS_SUBSCRIBED &&
                            $courseInstance['data_inizio'] > 0 &&
                            $courseInstance['data_fine'] >= time() && $courseInstance['durata'] > 0;
                    case MODULES_CLASSAGENDA_CLOSED_INSTANCES:
                        // trust user.php who is responsible of making dates calculations
                        // and setting the (subscription)status to proper value
                        return in_array($courseInstance['status'], [ADA_STATUS_COMPLETED, ADA_STATUS_TERMINATED]);
                }
            }
            return false;
        });

        if (!AMADB::isError($courseInstances) && is_array($courseInstances) && count($courseInstances) > 0) {
            $goodStates = [
                ADA_STATUS_SUBSCRIBED,
                ADA_STATUS_COMPLETED,
                ADA_STATUS_TERMINATED,
            ];
            foreach ($courseInstances as $tmpEl) {
                if (in_array($tmpEl['status'], $goodStates)) {
                    $returnHTML .= '<option value=' . $tmpEl['id_istanza_corso'] . ' data-idcourse=' . $tmpEl['id_corso'] . '>' . $tmpEl['titolo'] . ' > ' . $tmpEl['title'] . '</option>';
                }
            }
        }
    }


    if (strlen($returnHTML) > 0) {
        $returnHTML = "<option value='0' data-idcourse='0'>" . translateFN('Seleziona una classe') ."</option>" . $returnHTML;
        die($returnHTML);
    }
}
die($retVal);

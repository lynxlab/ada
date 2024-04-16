<?php

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2017, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Servicecomplete;

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Servicecomplete\Functions\logToFile;

/**
 * class to implement the 'are all surveys answered' complete condition.
 * The condition is satisfied if the user has answered the course
 * survey at least one time. If the course has no survey, the condition is always false
 *
 * @author giorgio
 */
class CompleteConditionAnsweredSurvey extends CompleteCondition
{
    /**
     * constants to define the type of the condition
     * and the description of the condition itself and
     * of its parameter, both to be used when building the UI.
     *
     */

    /**
     * description of the condition
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $description = 'Condizione soddisfatta se lo studente ha risposto a tutti i sondaggi del corso almeno il numero di volte specificato nel parametro';

    /**
     * String used to build the condition set summary for this rule
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $summaryStr = 'Risposte al sondaggio <em>%s</em>: <strong>%d</strong> su <strong>%d</strong>';

    /**
     * description of the condition's own parameter
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $paramDescription = 'Numero di sottomissioni dei sondaggi per cui la condizione si intende soddisfatta';

    /**
     * method that checks if the contidion is satisfied
     * for the passed id_user in the passed id_course_instance
     *
     * @param int $id_course_instance
     * @param int $id_user
     * @param array  $summary the array to ouput summary infos
     * @return boolean true if condition is satisfied
     * @access public
     */
    private function isSatisfied($id_course_instance = null, $id_student = null, &$summary = null)
    {
        $retval = false;
        if (!is_null($summary) && is_array($summary)) {
            $summary[__CLASS__] = [
                'param' => $this->param,
            ];
        }
        if (defined('MODULES_TEST') && MODULES_TEST) {
            if (isset($GLOBALS['dh'])) {
                $GLOBALS['dh']->disconnect();
            }
            $GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
            if (!AMADB::isError($GLOBALS['dh'])) {
                $courseId = $GLOBALS['dh']->getCourseIdForCourseInstance($id_course_instance);
                $test_list = $GLOBALS['dh']->testGetCourseSurveys(['id_corso' => $courseId]);
                if (!AMADB::isError($test_list) && is_array($test_list)) {
                    if (count($test_list) === 0) {
                        // define no-survey behaviour here
                        $retval = false;
                    } else {
                        $retval = true;
                        foreach ($test_list as $test_listEL) {
                            $historyArr = $GLOBALS['dh']->testGetHistoryTest([
                                    'id_corso' => $courseId,
                                    'id_istanza_corso' => $id_course_instance,
                                    'id_nodo' => $test_listEL['id_test'],
                                    'id_utente' => $id_student,
                                    'consegnato' => 1,
                            ]);
                            /**
                             * Should the course have more than one survey, the condition is true
                             * only if the student has answered at least $this->param times to EVERY survey
                             */
                            $retval = $retval && !AMADB::isError($historyArr) && is_array($historyArr) && count($historyArr) >= $this->param;
                            if (!is_null($summary) && is_array($summary)) {
                                $summary[__CLASS__]['check'][$test_listEL['id_nodo']] = [
                                    'title' => $test_listEL['titolo'],
                                    'count' => (is_array($historyArr) ? count($historyArr) : 0),
                                    'isSatisfied' => is_array($historyArr) && count($historyArr) >= $this->param,
                                ];
                            } else {
                                // if condition is not satisfied and we're not building a summary for one course, stop checking
                                if ($retval === false) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            $GLOBALS['dh']->disconnect();

            if ($this->getLogToFile()) {
                $logLines = [
                    __FILE__ . ': ' . __LINE__,
                    'running ' . __METHOD__,
                    print_r(['instance_id' => $id_course_instance, 'student_id' => $id_student], true),
                    sprintf("survey answered %d times, param is %d", count($historyArr), $this->param),
                    __METHOD__ . ' returning ' . ($retval ? 'true' : 'false'),
                ];
                logToFile($logLines);
            }

            if (!is_null($summary) && is_array($summary)) {
                $summary[__CLASS__]['isSatisfied'] = $retval;
            }

            return $retval;
        } else {
            // if no module test return true
            return true;
        }
        return $retval;
    }

    /**
     * statically build and checks if condition is satisfied
     * MUST HAVE ALWAYS 3 PARAMS, if the first is not needed use null
     *
     * @param string $param
     * @param string $id_course_instance
     * @param string $id_user
     * @param array  $summary the array to ouput summary infos
     * @return Ambigous <boolean, number>
     */
    public static function buildAndCheck($param = null, $id_course_instance = null, $id_user = null, &$summary = null)
    {
        $obj = self::build($param);
        return $obj->isSatisfied($id_course_instance, $id_user, $summary);
    }

    /**
     * return a CDOM element to build the html summary of the condition
     *
     * @param array $param
     * @return \Lynxlab\ADA\CORE\html4\CElement
     */
    public static function getCDOMSummary($param)
    {
        $cont = parent::getCDOMSummary($param);
        foreach ($param['check'] as $row) {
            $el = parent::getCDOMSummary($row);
            $el->addChild(new CText(sprintf(translateFN(self::$summaryStr), $row['title'], $row['count'], $param['param'])));
            $cont->addChild($el);
        }
        return $cont;
    }

    /**
     * staticallly build a new condition
     *
     * @param string $param
     * @return \Lynxlab\ADA\Module\Servicecomplete\CompleteConditionAnsweredSurvey
     */
    public static function build($param = null)
    {
        return new self($param);
    }
}

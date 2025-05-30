<?php

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Servicecomplete;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\History\History;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Servicecomplete\Functions\logToFile;

/**
 * class to implement the time complete condition.
 * The condition is satisfied if the user has spend
 * more than a certain amount of time in the course.
 *
 * @author giorgio
 */
class CompleteConditionTime extends CompleteCondition
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
    public static $description = 'Condizione soddisfatta se il tempo trascorso nel corso è uguale o maggiore a quello indicato nel parametro';

    /**
     * String used to build the condition set summary for this rule
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $summaryStr = 'Tempo trascorso: <strong>%s</strong> ore:minuti (minimo necessario: <strong>%s</strong>)';

    /**
     * description of the condition's own parameter
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $paramDescription = 'Tempo in minuti dopo il quale la condizione si intende soddisfatta';

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


        $history = new History($id_course_instance, $id_student);
        $id_course = $GLOBALS['dh']->getCourseIdForCourseInstance($id_course_instance);
        if (is_numeric($id_course)) {
            $history->setCourse($id_course);
        }
        $history->getVisitTime();
        if ($history->total_time > 0) {
            $timeSpentInCourse = intval($history->total_time);
        } else {
            $timeSpentInCourse = 0;
        }
        // $this->param is in minutes, $timeSpentInCourse is in seconds
        $param = $this->param * 60;
        $retval = $timeSpentInCourse >= $param;

        if ($this->getLogToFile()) {
            $logLines = [
                __FILE__ . ': ' . __LINE__,
                'running ' . __METHOD__,
                print_r(['instance_id' => $id_course_instance, 'student_id' => $id_student], true),
                sprintf("timeSpentInCourse is %d, param is %d (%d sec.)", $timeSpentInCourse, $this->param, $param),
                __METHOD__ . ' returning ' . ($retval ? 'true' : 'false'),
            ];
            logToFile($logLines);
        }

        if (!is_null($summary) && is_array($summary)) {
            $summary[self::class] = [
                'isSatisfied' => $retval,
                'param' => $param,
                'check' => $timeSpentInCourse,
            ];
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
     * @return CDOMElement
     */
    public static function getCDOMSummary($param)
    {
        $el = parent::getCDOMSummary($param);
        $formatCheck = sprintf("%02d:%02d", floor($param['check'] / 3600), floor(ceil($param['check'] / 60) % 60));
        $formatParam = sprintf("%02d:%02d", floor($param['param'] / 3600), floor(ceil($param['param'] / 60) % 60));
        $el->addChild(new CText(sprintf(translateFN(self::$summaryStr), $formatCheck, $formatParam)));
        return $el;
    }

    /**
     * staticallly build a new condition
     *
     * @param string $param
     * @return \Lynxlab\ADA\Module\Servicecomplete\CompleteConditionTime
     */
    public static function build($param = null)
    {
        return new self($param);
    }
}

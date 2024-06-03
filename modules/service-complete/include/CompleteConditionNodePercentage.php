<?php

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2018, Lynx s.r.l.
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
 * class to implement the percentage of visited nodes condition.
 * The condition is satisfied if the user has visited
 * more than a certain percentage of the course nodes.
 *
 * @author giorgio
 */
class CompleteConditionNodePercentage extends CompleteCondition
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
    public static $description = 'Condizione soddisfatta se la percentuale dei nodi visitati è uguale o maggiore a quella indicata nel parametro';

    /**
     * String used to build the condition set summary for this rule
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $summaryStr = 'Percentuale di nodi visitati: <strong>%s</strong> su <strong>%s</strong>';

    /**
     * description of the condition's own parameter
     * NOTE: THIS GOES THROUGH translateFN WHEN IT GETS USED, SO NO INTERNAZIONALIZATION PROBLEM HERE
     * cannot put here a call to translateFN because it's a static var
     *
     * @var string
     */
    public static $paramDescription = 'Percentuale di nodi visitati oltre la quale la condizione si intende soddisfatta (numero senza virgola e senza simbolo %).';

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
        /** @var \Lynxlab\ADA\Main\History\History $history */
        $history = new History($id_course_instance, $id_student);
        $id_course = $GLOBALS['dh']->getCourseIdForCourseInstance($id_course_instance);
        if (is_numeric($id_course)) {
            $history->setCourse($id_course);
        }
        $checkFloat = $history->historyNodesVisitedpercentFloatFN([ADA_GROUP_TYPE, ADA_LEAF_TYPE]);
        $retval =  $checkFloat >= floatval($this->param);

        if ($this->getLogToFile()) {
            $logLines = [
                    __FILE__ . ': ' . __LINE__,
                    'running ' . __METHOD__,
                    print_r(['instance_id' => $id_course_instance, 'student_id' => $id_student], true),
                    sprintf("History object says node percent visit is %s, param is %s", $checkFloat, $this->param),
                    __METHOD__ . ' returning ' . ($retval ? 'true' : 'false'),
            ];
            logToFile($logLines);
        }

        if (!is_null($summary) && is_array($summary)) {
            $summary[self::class] = [
                'isSatisfied' => $retval,
                'param' => floatval($this->param),
                'check' => $checkFloat,
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
        return (new self($param))->isSatisfied($id_course_instance, $id_user, $summary);
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
        $formatCheck = sprintf("%.0f%%", $param['check']);
        $formatParam = sprintf("%.0f%%", $param['param']);
        $el->addChild(new CText(sprintf(translateFN(self::$summaryStr), $formatCheck, $formatParam)));
        return $el;
    }
}

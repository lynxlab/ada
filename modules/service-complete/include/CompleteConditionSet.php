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

use Lynxlab\ADA\Module\Servicecomplete\Operation;

use function Lynxlab\ADA\Module\Servicecomplete\Functions\logToFile;

/**
 * class to represent the set of condition as defined
 * by the user.
 *
 * this is a sequence of operation represented by
 * the operations property that is an operation object
 * representing the tree of operations to be done
 *
 * @author giorgio
 */
class CompleteConditionSet
{
    /**
     * The id of the condition set, as stored in the DB
     *
     * @var int
     */
    private $id;

    /**
     * The description of the condition set
     *
     * @var string
     */
    public $description;

    /**
     * The tree operation, represented by an operation object
     *
     * @var \Lynxlab\ADA\Module\Servicecomplete\Operation
     */
    private $operation;

    /**
     * logical or arithmetical operation to be performed
     * between two groups of operands.
     *
     * For time being (02/dic/2013) a group is one single
     * column in the form used to define the condition set
     *
     * used also in the Operation::buildOperationTreeFromPOST method
     *
     * @var string
     */
    public static $opBetweenGroups = ' || ';

    /**
     * logical or arithmetical operation to be performed
     * between two operators.
     *
     * For time being (02/dic/2013) an operator is one single
     * element selected in one dropdown list of the form
     * described above for the groups
     *
     * used also in the Operation::buildOperationTreeFromPOST method
     *
     * @var string
     */
    public static $opBetweenOperands = ' && ';

    /**
     * true to write debug info in ADA log subdir
     *
     * @var boolean
     */
    protected $logToFile = false;

    /**
     * CompleteConditionSet constructor.
     */
    public function __construct($id = null, $description = null)
    {
        $this->operation = null;

        if (!is_null($id)) {
            $this->id = $id;
        }
        if (!is_null($description)) {
            $this->description = $description;
        }
        $this->setLogToFile(defined('MODULES_SERVICECOMPLETE_LOG') && MODULES_SERVICECOMPLETE_LOG === true);
    }

    /**
     * completeConditionSet operation setter
     *
     * sets the operation property to the
     * passed Operation object
     *
     * @param \Lynxlab\ADA\Module\Servicecomplete\Operation $op
     * @access public
     */
    public function setOperation(Operation $op)
    {
        $this->operation = $op;
        $this->operation->setLogToFile($this->getLogToFile());
    }

    /**
     * gets all operands for a given priority.
     * if no priority is given, it gets all operands
     * for all priorities in a 2-d array
     *
     * @param  int $priority
     * @return array|null
     * @access public
     */
    public function getOperandsForPriority($priority = null)
    {
        $data = $this->toArray();
        $retval = [];

        if (!is_null($data)) {
            foreach ($data as $op) {
                if (!isset($retval[$op['priority']]) || (isset($retval[$op['priority']]) && !is_array($retval[$op['priority']]))) {
                    $retval[$op['priority']] = [];
                }
                if (!is_null($op['operand1']) && !strstr($op['operand1'], 'expr') && !in_array($op['operand1'], $retval[$op['priority']])) {
                    $retval[$op['priority']][] = $op['operand1'];
                }
                if (!is_null($op['operand2']) && !strstr($op['operand2'], 'expr') && !in_array($op['operand2'], $retval[$op['priority']])) {
                    $retval[$op['priority']][] = $op['operand2'];
                }
            }
        }
        return !(is_null($priority)) ? $retval[$priority] : $retval;
    }

    /**
     * converts the operation object to an array
     *
     * @return array|NULL
     * @access public
     */
    public function toArray()
    {
        if (!is_null($this->operation)) {
            $test = [];
            $this->operation->toArray($test);
            return $test;
        } else {
            return null;
        }
    }

    /**
     * returns the string rapresentation of the
     * operation property
     *
     * @return null|string
     * @access public
     */
    public function toString()
    {
        return !is_null($this->operation) ? $this->operation->toString() : null;
    }

    /**
     * returns the evaluation of the operation property.
     * the returned type depends on the evaluated expression
     *
     * @param array $params the parameters to be passed for evaluation
     * @return null|mixed
     * @access public
     */
    public function evaluateSet($params)
    {
        if ($this->getLogToFile()) {
            $logLines = [
                __FILE__ . ': ' . __LINE__,
                'running ' . __METHOD__,
                print_r(array_combine(['instance_id(0)', 'student_id(1)'], $params), true),
                print_r($this->toArray(), true),
            ];
            logToFile($logLines);
        }

        $retval =  !is_null($this->operation) ? $this->operation->evaluate($params) : null;

        if ($this->getLogToFile()) {
            logToFile(__METHOD__ . ' returning ' . ($retval ? 'true' : 'false'));
        }
        return $retval;
    }

    /**
     * builds the completeConditionSet summary array by calling operation's evaluate
     *
     * @param array $params
     * @return array 'conditionClass' => [ 'isSatisfied', 'param' , 'check' ]
     * @access public
     */
    public function buildSummary($params)
    {
        $summary = [];
        // $summary will be modified by the evaluate calls
        if (!is_null($this->operation)) {
            $this->operation->evaluate($params, $summary);
        }
        return $summary;
    }

    /**
     * id getter
     *
     * @return int|null
     * @access public
     */
    public function getID()
    {
        return (intval($this->id) > 0) ? intval($this->id) : null;
    }

    /**
     * operation getter
     *
     * @return \Lynxlab\ADA\Module\Servicecomplete\Operation
     * @access public
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * logToFile setter
     *
     * @param boolean $logToFile
     * @return \Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet
     */
    public function setLogToFile($logToFile = false)
    {
        $this->logToFile = $logToFile;
        return $this;
    }

    /**
     * logToFile getter
     *
     * @return bool
     */
    public function getLogToFile()
    {
        return $this->logToFile;
    }
}

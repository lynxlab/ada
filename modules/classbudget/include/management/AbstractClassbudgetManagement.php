<?php

/**
 * Base Management Class
 *
 * @package         classbudget module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2015, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classbudget
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use ReflectionClass;
use ReflectionProperty;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * base class for module
 *
 * @author giorgio
 */

abstract class AbstractClassbudgetManagement
{
    /**
     * array of available export formats
     *
     * @var array
     */
    public static $exportFormats = ['pdf', 'csv'];

    protected $id_course_instance;
    protected $objType;
    protected $tableCaption;
    protected $grandTotal = 0;
    protected $actions;

    public $dataCostsArr;
    public $headerRowLabels;

    /**
     * name constructor
     */
    public function __construct($data = [])
    {
        if (is_array($data) && count($data) > 0) {
            $this->fillFromArray($data);
        }
    }

    /**
     * build, manage and display the module's pages
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement
     *
     * @access public
     */
    public function run($action = null)
    {

        if (is_null($this->id_course_instance)) {
            $htmlObj = CDOMElement::create('div', 'class:' . $this->objType . 'budgeterror');
            $htmlObj->addChild(new CText(translateFN('Istanza corso non valida')));
        } else {
            $htmlObj = CDOMElement::create('div', 'id:' . $this->objType . 'BudgetContainer');
            /**
             * Build our own table here, there's too much that BaseHtmlLib cannot handle
             */
            $table = CDOMElement::create('table', 'class:classbudgettable,id:' . $this->objType . 'BudgetTable');
            $table->setAttribute('data-instance-id', $this->id_course_instance);

            $c = CDOMElement::create('caption');
            $ch3 = CDOMElement::create('h3');
            $ch3->addChild(new CText($this->tableCaption));
            $c->addChild($ch3);
            $table->addChild($c);

            $thead = CDOMElement::create('thead');
            $tr = CDOMElement::create('tr');
            foreach ($this->headerRowLabels as $el) {
                $th = CDOMElement::create('th');
                $th->addChild(new CText($el));
                $tr->addChild($th);
            }
            $thead->addChild($tr);
            $table->addChild($thead);

            $tbody = CDOMElement::create('tbody');
            $this->grandTotal = 0;
            $countRow = 0;
            foreach ($this->dataCostsArr as $id_object => $data) {
                $tr = CDOMElement::create('tr', 'id:' . $this->objType . $id_object);
                if (!is_null($data['cost_' . $this->objType . '_id'])) {
                    $tr->setAttribute('data-cost-' . $this->objType . '-id', $data['cost_' . $this->objType . '_id']);
                }
                if (isset($data['applied-to-id']) && !is_null($data['applied-to-id'])) {
                    $tr->setAttribute('data-applied-to-id', $data['applied-to-id']);
                }
                $tr->setAttribute('class', ($countRow++ % 2) ? 'evenrow' : 'oddrow');

                // name
                $td = CDOMElement::create('td', 'class:displayname');
                $td->addChild(new CText($data['displayname']));
                $tr->addChild($td);
                // time
                $td = CDOMElement::create('td', 'class:totalqty');
                $td->setAttribute('data-totalqty', $data['totalqty']);
                $td->addChild(new CText($data['formatqty']));
                $tr->addChild($td);
                // hourly rate
                $td = CDOMElement::create('td', 'class:price');
                if ($action == MODULES_CLASSBUDGET_EDIT) {
                    $inputField = CDOMElement::create('text', 'id:' . $this->objType . '_unitprice[' . $id_object . ']');
                    $inputField->setAttribute('class', 'price');
                    $inputField->setAttribute('name', $this->objType . '_unitprice[' . $id_object . ']');
                    $inputField->setAttribute('value', sprintf('%.02f', $data['unitprice']));
                    $inputField->setAttribute('onchange', 'javascript:updateTotal(this);');
                    $td->addChild($inputField);
                } else {
                    $td->addChild(new CText(number_format($data['unitprice'], ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP)));
                }
                $tr->addChild($td);
                // totale
                $td = CDOMElement::create('td', 'class:price total');
                $total = $data['totalqty'] * $data['unitprice'];
                $td->setAttribute('data-total', $total);
                $this->grandTotal += $total;
                $td->addChild(new CText(number_format($total, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP)));
                $tr->addChild($td);

                if (
                    $action == MODULES_CLASSBUDGET_EDIT && property_exists($this, 'actions') &&
                    is_array($this->actions) && count($this->actions) > 0
                ) {
                    $td = CDOMElement::create('td', 'class:actions');
                    $buttonsHtml = '';
                    foreach ($this->actions as $actionButton) {
                        $buttonsHtml .= $actionButton->getHtml();
                    }
                    $buttonsHtml = str_ireplace('<cost_' . $this->objType . '_id>', $id_object, $buttonsHtml);
                    $td->addChild(new CText($buttonsHtml));
                    $tr->addChild($td);
                }

                $tbody->addChild($tr);
            }
            $table->addChild($tbody);
            $tfoot = CDOMElement::create('tfoot');
            $tr = CDOMElement::create('tr');

            $totalColumn = count($this->headerRowLabels) - 1;
            if (
                $action == MODULES_CLASSBUDGET_EDIT && property_exists($this, 'actions') &&
                is_array($this->actions) && count($this->actions) > 0
            ) {
                $totalColumn--;
            }

            foreach ($this->headerRowLabels as $index => $el) {
                $th = CDOMElement::create('th');
                if ($index == 0) {
                    $footerElement = translateFN('Totale');
                    $th->setAttribute('class', 'caption grandtotal');
                } elseif ($index == $totalColumn) {
                    $th->setAttribute('class', 'price grandtotal');
                    $th->setAttribute('data-grandtotal', $this->grandTotal);
                    $footerElement = number_format($this->grandTotal, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP);
                } else {
                    $footerElement = null;
                }
                $th->addChild(new CText($footerElement));
                $tr->addChild($th);
            }

            $tfoot->addChild($tr);
            $table->addChild($tfoot);
            $htmlObj->addChild($table);
        }
        return $htmlObj;
    }

    /**
     * grandtotal getter
     *
     * @return number
     */
    public function getGrandTotal()
    {
        return $this->grandTotal;
    }

    /**
     * Builds the cost array from the data returned by the db
     * each element of the returned array will have its key
     * set to the $this->_objType id for that row and the following fields:
     *
     * 'totalqty' => total time in milliseconds (int type)
     * 'formatqty' => total time formatted as a string in hour:minutes format (string type)
     * 'displayname' => string to be displayed (string type)
     * 'unitprice' => hourly rate for the classroom (float type)
     * 'cost_'.$this->_objType.'_id' => unique row id (if present: int type or null)
     *
     * @param array $res
     *
     * @return array
     *
     * @access private
     */
    protected function buildCostArrayFromRes($res)
    {
        $retval = [];
        foreach ($res as $row) {
            $id = $row['id_' . $this->objType];
            $retval[$id] = [];
            unset($row['id_' . $this->objType]);
            foreach ($row as $field => $value) {
                switch ($field) {
                    case 'cost_' . $this->objType . '_id':
                        $retval[$id][$field] = (isset($value) && is_numeric($value)) ? (int) $value : null;
                        break;
                    case 'totaltime':
                        $hours = floor($value / 3600);
                        $minutes = floor(($value / 60) % 60);
                        $retval[$id]['totalqty'] = (int) $value / 3600;
                        $retval[$id]['formatqty'] = sprintf("%02d:%02d", $hours, $minutes);
                        break;
                    case 'venuename':
                        $retval[$id]['displayname'] = $value . ' - ' . $row['roomname'];
                        break;
                    case 'name':
                        $retval[$id]['displayname'] = $value . ' ' . $row['lastname'];
                        break;
                    case 'default_rate':
                        /**
                         * if a rate relative to the passed istance has been returned
                         * use it, else use the default coming from the classroom table
                         */
                        if (!is_null($row['cost_rate']) && floatval($row['cost_rate']) > 0) {
                            $retval[$id]['unitprice'] = floatval($row['cost_rate']);
                        } elseif (!is_null($value) && floatval($value) > 0) {
                            $retval[$id]['unitprice'] = floatval($value);
                        } else {
                            $retval[$id]['unitprice'] = floatval(0);
                        }
                        break;
                    default:
                        break;
                } // switch
            } // foreach $row
        } // foreach $res
        return $retval;
    }

    /**
     * builds proper array to be exported in CSV format
     *
     * @return array
     *
     * @access public
     */
    public function buildCostArrayForCSV()
    {

        // add a row with tile and all empty cells
        // and the header row
        foreach ($this->headerRowLabels as $index => $headerVal) {
            if ($index == 0) {
                $retArray[0][$index] = $this->tableCaption;
            } else {
                $retArray[0][$index] = '';
            }

            $retArray[1][$index] = str_replace(
                ADA_CURRENCY_SYMBOL,
                html_entity_decode(ADA_CURRENCY_SYMBOL, ENT_COMPAT | ENT_HTML401, ADA_CHARSET),
                $headerVal
            );
        }

        // table body
        $this->grandTotal = 0;
        foreach ($this->dataCostsArr as $key => $value) {
            $total = $value['totalqty'] * $value['unitprice'];
            $this->grandTotal += $total;
            $retArray[] = [
                $value['displayname'],
                $value['formatqty'],
                number_format($value['unitprice'], ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP),
                number_format($total, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP),
            ];
        }

        $numRecord = count($retArray);
        $lastIndex = count($retArray[0]) - 1;

        // add grand total and an empty row
        foreach ($this->headerRowLabels as $index => $notused) {
            if ($index == 0) {
                $retArray[$numRecord][$index] = translateFN('Totale');
            } elseif ($index == $lastIndex) {
                $retArray[$numRecord][$index] = number_format($this->grandTotal, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP);
            } else {
                $retArray[$numRecord][$index] = '';
            }

            $retArray[$numRecord + 1][$index] = '';
        }

        return $retArray;
    }

    /**
     * fills object properties from an array
     *
     * @param array $data assoc array to get values from
     *
     * @access private
     */
    protected function fillFromArray($data)
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = is_null($val) ? null : trim($val);
            }
        }
    }

    /**
     * returns object properties as an array
     *
     * @param boolean $withPrivate if true, returns private properties as well
     *
     * @return array
     *
     * @access public
     */
    public function toArray($withPrivate = false)
    {
        $filter = ReflectionProperty::IS_PUBLIC;
        if ($withPrivate === true) {
            $filter = $filter | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED;
        }
        $retArray = [];
        $refclass = new ReflectionClass($this);
        foreach ($refclass->getProperties($filter) as $property) {
            if (!$property->isStatic()) {
                $retArray[$property->getName()] = $this->{$property->getName()};
            }
        }
        return empty($retArray) ? null : $retArray;
    }
}

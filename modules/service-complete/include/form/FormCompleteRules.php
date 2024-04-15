<?php

use Lynxlab\ADA\Module\Servicecomplete\FormCompleteRules;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use Lynxlab\ADA\CORE\html4\CBaseElement;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class FormCompleteRules was declared with namespace Lynxlab\ADA\Module\Servicecomplete. //

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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Servicecomplete\Functions\extractParam;

/**
 * form for defining the operations used to say if
 * a course (or an instance) is considered complete
 *
 * @author giorgio
 */
class FormCompleteRules
{
    /**
     * the array of the completeConditionSet
     * as loaded from the DB
     *
     * @var array
     */
    private $data;

    /**
     * the array of all the possible defined and
     * implemented conditions used to build the
     * operation as described in module's own
     * config.ini.php
     *
     * @var array
     */
    private $conditionList;

    /**
     * this holds the actual form
     *
     * @var CBaseElement
     */
    private $form;

    /**
     * FormCompleteRules constructor.
     */
    public function __construct($data = [], $conditionList = [])
    {
        $this->data = $data;
        $this->conditionList = (!is_null($conditionList) && is_array($conditionList)) ? $conditionList : [];

        $this->form = $this->content();
    }

    /**
     * renders the HTML of the form
     *
     * @return string
     * @access public
     */
    public function getHtml()
    {
        return $this->form->getHtml();
    }

    /**
     * actually generates the form.
     *
     * this form is done through core and not extending the usual
     * FForm object because as of today (03/dic/2013) the generation
     * of forms containing tables and fields of array are not supported
     *
     * @return CBaseElement
     * @access private
     */
    private function content()
    {
        $form = CDOMElement::create('form', 'id:completerules, name:completerules, method:post');

        // a hidden filed that is writable by the jQuery
        $hiddenConditionSetId = CDOMElement::create('text', 'name:conditionSetId,id:conditionSetId');
        $hiddenConditionSetId->setAttribute('style', 'display:none;');
        if (isset($this->data['conditionSetId'])) {
            $hiddenConditionSetId->setAttribute('value', $this->data['conditionSetId']);
        }
        $form->addChild($hiddenConditionSetId);

        $divEditRule = CDOMElement::create('div', 'id:ConditionSetDIV');

        $lbl = CDOMElement::create('label', 'for:ruleDescription,id:l_ruleDescription');
        $lbl->addChild(new CText(translateFN('Descrizione della regola') . ': '));

        $ruleDescr = CDOMElement::create('text', 'name:description,id:ruleDescription');
        if (isset($this->data['description'])) {
            $ruleDescr->setAttribute('value', $this->data['description']);
        }

        $divEditRule->addChild($lbl);
        $divEditRule->addChild($ruleDescr);

        $tableContainer = CDOMElement::create('table', 'id:operationsTableContainer');
        $thead = CDOMElement::create('thead');
        $tbody = CDOMElement::create('tbody');
        $tableContainer->addChild($thead);
        $tableContainer->addChild($tbody);

        $tr = CDOMElement::create('tr');

        $th = CDOMElement::create('th', 'class:containerHead0');
        $th->addChild(new CText('&nbsp;'));
        $tr->addChild($th);

        $th = CDOMElement::create('th', 'colspan:2,class:containerHead1');
        $th->addChild(new CText('OR'));
        $tr->addChild($th);

        $thead->addChild($tr);

        $tr = CDOMElement::create('tr');
        $td = CDOMElement::create('td', 'class:containerCol0');
        $td->addChild(new CText('<p>AND</p>'));
        $tr->addChild($td);

        // this cell contains the main table, that
        // will be added later on in the code
        $mainCell = CDOMElement::create('td', 'class:containerCol1');
        $tr->addChild($mainCell);
        $tbody->addChild($tr);

        $mainTable = CDOMElement::create('table', 'id:operationsTable');
        $mainCell->addChild($mainTable);

        // build main table header
        $thead = CDOMElement::create('thead');
        $tr = CDOMElement::create('tr');

        for ($i = 0; $i < NUM_RULES_SET; $i++) {
            $th = CDOMElement::create('th', 'class:colSelect');
            $th->addChild(new CText(translateFN('operazione')));
            $tr->addChild($th);

            $th = CDOMElement::create('th', 'class:colParam');
            // sets first or last class for styling purposes
            if ($i == 0) {
                $th->setAttribute('class', $th->getAttribute('class') . ' first');
            } elseif ($i == NUM_RULES_SET - 1) {
                $th->setAttribute('class', $th->getAttribute('class') . ' last');
            }
            $th->addChild(new CText(translateFN('parametro')));
            $tr->addChild($th);
        }

        $thead->addChild($tr);
        $mainTable->addChild($thead);

        $mainBody = CDOMElement::create('tbody');
        $mainTable->addChild($mainBody);

        // find number of rows by counting the
        // maximum number of operation that a column has
        if (isset($this->data['condition'])) {
            foreach ($this->data['condition'] as $cond) {
                $lengths[] = count($cond);
            }
            $numRows = max($lengths);
        } else {
            $numRows = 1;
        }

        // WARNING: the first cycle $j=-1 is used
        // to generate an empty row for the jQuery to use
        for ($j = -1; $j < $numRows; $j++) {
            $tr = CDOMElement::create('tr', 'id:operationRow_' . $j);

            for ($i = 0; $i < NUM_RULES_SET; $i++) {
                // create the select from the condition list that
                // will be added to each column
                $select = CDOMElement::create('select', 'class:selectCondition');

                $nullOption = CDOMElement::create('option', 'value:null');
                $nullOption->addChild(new CText(translateFN('nessuna operazione')));
                $select->addChild($nullOption);

                $param = CDOMElement::create('text', 'class:paramCondition');
                $param->setAttribute('size', '3');

                $setSelected = false;
                $setParam = false;

                foreach ($this->conditionList as $aKey => $aCondition) {
                    $option[$aKey] = CDOMElement::create('option', 'value:' . $aKey);
                    $option[$aKey]->addChild(new CText($aCondition));
                    /**
                     * var are as follows:
                     *
                     * $aCondition = 'completeConditionTest2'
                     *
                     * $this->_data['condition'][$i] = Array (
                     *
                     *  [0] => 'completeConditionTest4::buildAndCheck(3829)'
                     *  [1] => 'completeConditionTest4::buildAndCheck(432)'
                     * )
                     *
                     * so, must check if $aCondition is a substring of one
                     * of the elements of $this->_data['condition'][$i]
                     *
                     * once found, extract the param, set the key of the
                     * selected value and break out of the foreach
                     *
                     */
                    if ($j != -1 && $setSelected === false && isset($this->data['condition'][$i])) {
                        foreach ($this->data['condition'][$i] as $key => $checkme) {
                            if (stripos($checkme, $aCondition) !== false) {
                                $setSelected = $aKey;
                                $setParam = extractParam($checkme);
                                unset($this->data['condition'][$i][$key]);
                                break;
                            }
                        }
                    }
                    $select->addChild($option[$aKey]);
                }
                // generare a td for the selected condition
                $td = CDOMElement::create('td', 'class:colSelect');
                // set the name attribute of the above generated select element
                $select->setAttribute('name', 'condition[' . $i . '][' . $j . ']');
                // set the selected option if any
                if ($setSelected !== false) {
                    $option[$setSelected]->setAttribute('selected', 'selected');
                } else {
                    $nullOption->setAttribute('selected', 'selected');
                }
                // add the select elem to the td
                $td->addChild($select);
                // add the td to the tr
                $tr->addChild($td);
                // generare a td for the input parameter
                $td = CDOMElement::create('td', 'class:colParam');
                // sets first or last class for styling purposes
                if ($i == 0) {
                    $td->setAttribute('class', $td->getAttribute('class') . ' first');
                } elseif ($i == NUM_RULES_SET - 1) {
                    $td->setAttribute('class', $td->getAttribute('class') . ' last');
                }
                // set the name attribute of the above generated text input element
                $param->setAttribute('name', 'param[' . $i . '][' . $j . ']');
                // set the parameter value if any
                if ($setParam !== false) {
                    $param->setAttribute('value', $setParam);
                }
                // add the input parameter element to the td
                $td->addChild($param);
                // add the td to the tr
                $tr->addChild($td);
            }
            // add the tr to the mainBody table only if it's not the
            // first iteration. add it to a hidden div for jQuery to
            // use it instead.
            if ($j != -1) {
                $mainBody->addChild($tr);
            } else {
                $hiddenjQueryDIV = CDOMElement::create('div', 'id:rowTemplate');
                $hiddenjQueryDIV->setAttribute('style', 'display:none');
                $hiddenjQueryDIV->addChild(new CText(htmlentities(str_replace('-1', '#NEWID#', $tr->getHtml()), ENT_COMPAT | ENT_HTML401, ADA_CHARSET)));
                $form->addChild($hiddenjQueryDIV);
            }
        }
        // add a row with the 'add row' button to the main table

        $tr = CDOMElement::create('tr', 'class:addButtonRow');

        $td = CDOMElement::create('td');
        $td->addChild(new CText('&nbsp;'));
        $tr->addChild($td);

        // the button, its action will be controlled by javascript
        $td = CDOMElement::create('td');
        // $td->setAttribute('colspan', NUM_RULES_SET * 2);
        $addRowBtn = CDOMElement::create('button', 'id:addRowButton,class:tooltip,type:button');
        $addRowBtn->setAttribute('onclick', 'javascript:addOperationRow();');
        $addRowBtn->setAttribute('title', translateFN('clicca per aggiungere una riga di operazioni'));
        $addRowBtn->addChild(new CText(translateFN('Aggiungi Riga')));
        $td->addChild($addRowBtn);
        $tr->addChild($td);
        $tbody->addChild($tr);

        $divEditRule->addChild($tableContainer);

        $submitdiv = CDOMElement::create('div', 'id:submitDIV,class:tooltip');
        $submit = CDOMElement::create('button', 'type:submit,id:submitButton');
        $submit->setAttribute('title', translateFN('clicca per salvare la regola'));
        $submit->addChild(new CText(translateFN('Salva')));
        $submitdiv->addChild($submit);

        $form->addChild($divEditRule);
        $form->addChild($submitdiv);

        // generate the 'legend' div
        $divLegend = CDOMElement::create('div', 'id:divLegend,class:tooltip');
        $divLegend->setAttribute('title', translateFN('Legenda'));
        foreach ($this->conditionList as $className) {
            if (! isset($olElem)) {
                $olElem = CDOMElement::create('ol');
            }
            $liElem = CDOMElement::create('li');
            $string = '<p><strong>' . $className . "</strong>: ";
            $className = __NAMESPACE__ . "\\" . ucfirst($className);
            $string .= translateFN($className::$description) . '</p>' ;
            $string2 = '<p><strong>' . translateFN('Parametro') . '</strong>: ' . translateFN($className::$paramDescription) . '</p>';
            $liElem->addChild(new CText($string . $string2));
            $olElem->addChild($liElem);
        }

        if (isset($olElem)) {
            $divLegend->addChild($olElem);
            $form->addChild($divLegend);
        }

        return $form;
    }
}

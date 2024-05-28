<?php

/**
 * Tutor Budget Management Class
 *
 * @package         classbudget module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2015, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link                classbudget
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TutorBudgetManagement extends AbstractClassbudgetManagement
{
    public function __construct($id_course_instance)
    {
        $this->objType = 'tutor';
        parent::__construct(['id_course_instance' => $id_course_instance]);
    }

    /**
     * Retreives the data from the DB and builds the HTML table
     * for the Classroom Costs of the page
     *
     * (non-PHPdoc)
     * @see abstractClassbudgetManagement::run()
     */
    public function run($action = null)
    {
        $this->headerRowLabels = [
            translateFN('Tutor'),
            translateFN('Tempo impiegato'),
            translateFN('Tariffa Oraria') . ' (' . ADA_CURRENCY_SYMBOL . ')',
            translateFN('Totale') . ' (' . ADA_CURRENCY_SYMBOL . ')',
        ];
        $this->tableCaption = translateFN('Costi Tutor');

        $res = $GLOBALS['dh']->getTutorCostForInstance($this->id_course_instance);

        if (!AMADB::isError($res)) {
            $this->dataCostsArr = $this->buildCostArrayFromRes($res);
            if (count($this->dataCostsArr) > 0) {
                $htmlObj = parent::run($action);
            } else {
                $htmlObj = null;
            }
        } else {
            $htmlObj = CDOMElement::create('div', 'id:' . $this->objType . 'BudgetContainer,class:budgeterrorcontainer');
            $errorSpan = CDOMElement::create('span', 'class:' . $this->objType . ' budgeterror');
            $errorSpan->addChild(new CText(translateFN('Erorre nella lettura dei costi tutor')));
            $closeSpan =  CDOMElement::create('span', 'class:closeSpan');
            $closeSpan->setAttribute('onclick', 'javascript:closeDIV(\'' . $this->objType . 'BudgetContainer\');');
            $closeSpan->addChild(new CText('x'));
            $htmlObj->addChild($errorSpan);
            $htmlObj->addChild($closeSpan);
        }
        return $htmlObj;
    }
}

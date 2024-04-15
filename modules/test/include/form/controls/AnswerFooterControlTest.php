<?php

use Lynxlab\ADA\Module\Test\AnswerFooterControlTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class AnswerFooterControlTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 *
 * @package
 * @author      Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AnswerFooterControlTest extends FormControl
{
    protected $modifiable = true;

    /**
     * Answer Footer Control Test
     * It DOESN'T call parent constructor
     *
     */
    public function __construct($modifiable = true)
    {
        $this->controlData = [];
        $this->selected = false;
        $this->isRequired = false;
        $this->isMissing = false;
        $this->hidden = false;

        $this->validator = null;

        $this->modifiable = $modifiable;
    }

    /**
     * Control rendering
     *
     * @return string
     */
    public function render()
    {
        if ($this->modifiable) {
            $div = CDOMElement::create('div');
            $div->setAttribute('class', 'admin_link answers_footer');
            $div->addChild(new CText(' [ '));
            $a = CDOMElement::create('a');
            $a->addChild(new CText(translateFN('Aggiungi risposta')));
            $a->setAttribute('href', 'javascript:void(0);');
            $a->setAttribute('onclick', 'add_row(this);');
            $div->addChild($a);
            $div->addChild(new CText(' ] '));

            return $div->getHtml();
        } else {
            return '';
        }
    }
}

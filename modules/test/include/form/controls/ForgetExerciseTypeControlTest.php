<?php

use Lynxlab\ADA\Module\Test\ForgetExerciseTypeControlTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class ForgetExerciseTypeControlTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

class ForgetExerciseTypeControlTest extends FormControl
{
    /**
     * Answer Footer Control Test
     * It DOESN'T call parent constructor
     *
     */
    public function __construct()
    {
        $this->controlData = [];
        $this->selected = false;
        $this->isRequired = false;
        $this->isMissing = false;
        $this->hidden = false;

        $this->validator = null;
    }

    /**
     * Control rendering
     *
     * @return string
     */
    public function render()
    {
        $div = CDOMElement::create('div');
        $div->setAttribute('style', 'text-align:center;');
        $div->addChild(new CText(' [ '));
        $a = CDOMElement::create('a');
        $a->addChild(new CText(sprintf(translateFN('Cambia tipologia %s'), translateFN('Domanda'))));
        $a->setAttribute('href', $_SERVER['REQUEST_URI'] . '&forgetExerciseType');
        $div->addChild($a);
        $div->addChild(new CText(' ] '));

        return $div->getHtml();
    }
}

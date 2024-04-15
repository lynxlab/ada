<?php

use Lynxlab\ADA\Module\Test\QuestionOpenFormTest;

use Lynxlab\ADA\Module\Test\QuestionFormTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class QuestionOpenFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionOpenFormTest extends QuestionFormTest
{
    protected function content()
    {
        $this->commonElements();

        //punteggio massimo
        if (isset($this->data['correttezza'])) {
            $defaultValue = $this->data['correttezza'];
        } else {
            $defaultValue = 0;
        }
        $this->addTextInput('correttezza', translateFN('Punteggio massimo assegnabile') . ': ')
             ->setRequired()
             ->setValidator(FormValidator::NON_NEGATIVE_NUMBER_VALIDATOR)
             ->withData($defaultValue);
    }
}

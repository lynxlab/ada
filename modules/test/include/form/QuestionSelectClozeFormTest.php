<?php

use Lynxlab\ADA\Module\Test\QuestionSelectClozeFormTest;

use Lynxlab\ADA\Module\Test\QuestionFormTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class QuestionSelectClozeFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionSelectClozeFormTest extends QuestionFormTest
{
    protected function content()
    {
        $this->commonElements();

        //tipologia domanda cloze sinonimi
        $cloze_sinonimi = 'cloze_sinonimi';
        $options = [
            ADA_NORMAL_SELECT_TEST => translateFN('No'),
            ADA_SYNONYM_SELECT_TEST => translateFN('Si'),
        ];

        if (isset($this->data[$cloze_sinonimi])) {
            $defaultValue = $this->data[$cloze_sinonimi];
        } else {
            $defaultValue = ADA_NORMAL_SELECT_TEST;
        }
        $this->addRadios($cloze_sinonimi, translateFN('Nella tendina, mostrare un valore preimpostato?'), $options, $defaultValue);
    }
}

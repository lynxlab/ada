<?php

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
use Lynxlab\ADA\Module\Test\RootFormTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TestFormTest extends RootFormTest
{
    protected function content()
    {
        parent::content();

        $this->setName('testForm');

        $js = '
			var correttezza_field = "correttezza";
			var correttezza_regexp = /^[0]|[1-9][0-9]*$/;
			var barriera_field = "barriera";
			var livello_field = "livello";
			load_js("' . MODULES_TEST_HTTP . '/js/testForm.js");';
        $this->setCustomJavascript($js);

        //barriera
        $radios = [
            ADA_YES_TEST_BARRIER => translateFN('Si'),
            ADA_NO_TEST_BARRIER => translateFN('No'),
        ];
        if (!isset($this->data['barriera'])) {
            $defaultValue = ADA_NO_TEST_BARRIER;
        } else {
            $defaultValue = $this->data['barriera'];
        }
        $this->addRadios('barriera', translateFN('Test di sbarramento') . ':', $radios, $defaultValue);


        if (isset($this->data['correttezza'])) {
            $defaultValue = $this->data['correttezza'];
        } else {
            $defaultValue = 0;
        }
        $this->addTextInput('correttezza', translateFN('Punteggio minimo necessario per superare il test') . ': ')
             ->setRequired()
             ->setValidator(FormValidator::NON_NEGATIVE_NUMBER_VALIDATOR)
             ->withData($defaultValue)
             ->setHidden();


        //livello
        for ($i = 0; $i <= 25; $i++) {
            $options[$i] = $i;
        }
        $options[0] = translateFN('Nessun livello');
        if (isset($this->data['livello'])) {
            $defaultValue = $this->data['livello'];
        } else {
            $defaultValue = 0;
        }
        $this->addSelect('livello', translateFN('Livello acquisito al superamento del test') . ':', $options, $defaultValue)
             ->setHidden();
    }
}

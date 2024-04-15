<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\TranslationForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class TranslationForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TranslationForm extends FForm
{
    public function __construct($language = null)
    {
        parent::__construct();

        $this->setName('translatorForm');
        $this->addTextInput('t_name', translateFN('Cerca nella traduzione'));
        $this->addSelect('selectLanguage', translateFN('Selezionare una lingua '), $language, 1);
        $this->setMethod('POST');
        $j = 'return initDataTable();';
        $this->setOnSubmit($j);
    }
}

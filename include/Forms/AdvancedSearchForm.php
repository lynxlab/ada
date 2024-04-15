<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\AdvancedSearchForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class AdvancedSearchForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AdvancedSearchForm extends FForm
{
    public function __construct($cod = false, $action = null)
    {
        parent::__construct();

        if ($action != null) {
            $this->setAction($action);
        }
        $this->setName('advancedForm');
        $this->addTextInput('s_node_name', translateFN('Titolo'));
        $this->addTextInput('s_node_title', translateFN('Keywords'));
        $this->addTextarea('s_node_text', translateFN('Testo'));
        $this->setMethod('GET');
        $this->addHidden('s_AdvancedForm');
        $j = 'javascript:disableForm()';
        $this->setOnSubmit($j);
    }
}

<?php

use Lynxlab\ADA\Module\Impexport\FormExportToRepoDetails;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class FormExportToRepoDetails was declared with namespace Lynxlab\ADA\Module\Impexport. //

/**
 * EXPORT MODULE
 *
 * @package     export/import course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        impexport
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Impexport;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for handling export phase 3, repository details
 *
 * @author giorgio
*/

class FormExportToRepoDetails extends FForm
{
    public function __construct($formName, $exportToRepo)
    {
        parent::__construct();
        $this->setName($formName);
        $this->addHidden('exporttorepo')->withData(intval($exportToRepo));

        $this->addTextInput('repotitle', translateFN('Titolo'))->setAttribute('class', 'repotitle')
             ->setValidator(FormValidator::NOT_EMPTY_STRING_VALIDATOR)->setRequired();
        $this->addTextarea('repodescr', translateFN('Descrizione'))->setAttribute('class', 'repodescr');

        $buttonDIV = CDOMElement::create('div', 'class:step3buttons');
        $buttonPrev = CDOMElement::create('button', 'id:backButton2');
        $buttonPrev->setAttribute('type', 'button');
        $buttonPrev->setAttribute('onclick', 'javascript:return goToExportStepTwo(\'exportFormStep3\');');
        $buttonPrev->addChild(new CText('&lt;&lt;&nbsp;' . translateFN('Indietro')));
        $buttonDIV->addChild($buttonPrev);

        $this->addCDOM($buttonDIV);
        $this->setCustomJavascript("\$j('div.step3buttons').append(\$j('#submit_$formName'));");
        $this->setSubmitValue(translateFN('Esporta'));
        $this->setOnSubmit('return doExport(\'exportFormStep3\');');
    }
}

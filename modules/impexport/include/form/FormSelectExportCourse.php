<?php

use Lynxlab\ADA\Module\Impexport\FormSelectExportCourse;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class FormSelectExportCourse was declared with namespace Lynxlab\ADA\Module\Impexport. //

/**
 * EXPORT MODULE
 *
 * @package     export/import course
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            impexport
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Impexport;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for handling export phase 1, selecting a course
 *
 * @author giorgio
*/
class FormSelectExportCourse extends FForm
{
    public function __construct($formName, $courseList, $selectedCourse = 0)
    {
        parent::__construct();
        $this->setName($formName);

        $courseList[0] = translateFN('Scegli un corso da esportare');

        $this->addSelect('course', translateFN('Seleziona un corso da cui esportatre'), $courseList, $selectedCourse)
            ->setRequired()
            ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $this->addCheckboxes(
            'nomedia',
            translateFN('Se si pensa di assegnare il corso importato allo stesso autore di quello esporato, si può evitare di esportare i files multimediali'),
            ['1' => translateFN('Non esportare i media')],
            null
        );

        if (MODULES_TEST) {
            $this->addCheckboxes(
                'nosurvey',
                'Se si pensa di importare il corso in una piattaforma in cui esistano già dei sondaggi per il corso, si può evitare di esportarli',
                ['1' => translateFN('Non esportare i sondaggi')],
                1
            );
        }


        $this->setSubmitValue(translateFN('Avanti') . "&nbsp;&gt;&gt;");
        $this->setOnSubmit('return goToExportStepTwo(\'exportFormStep1\');');
    }
}

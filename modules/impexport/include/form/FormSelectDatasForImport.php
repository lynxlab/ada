<?php

use Lynxlab\ADA\Module\Impexport\FormSelectDatasForImport;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class FormSelectDatasForImport was declared with namespace Lynxlab\ADA\Module\Impexport. //

/**
 * IMPORT MODULE
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
 * class for handling author assignment form
 *
 * @author giorgio
 */
class FormSelectDatasForImport extends FForm
{
    public function __construct($formName, $authorsList, $courseList, $selAuthor = 0, $selCourse = 0)
    {
        parent::__construct();

        $authorsList[0] = translateFN('Scegli un autore per il corso');

        $courseList[0] = translateFN('Importa come nuovo corso');

        $this->setName($formName);

        $this->addHidden('importFileName');

        $this->addSelect('author', translateFN("Seleziona l'autore a cui assegnare il corso importato"), $authorsList, $selAuthor)
            ->setRequired()
            ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $this->addSelect('courseID', translateFN("Seleziona il corso in cui importare"), $courseList, $selCourse)
            ->setRequired()
            ->setValidator(FormValidator::NON_NEGATIVE_NUMBER_VALIDATOR);

        if (isset($_SESSION['service_level'])) {
            /**
             * @author giorgio 06/mag/2015
             *
             * switcher can add public courses only if:
             * - it's a multiprovider having session tester equals to PUBLIC tester
             * - it's not multiprovider
             */
            $shownServiceTypes = [];
            foreach ($_SESSION['service_level'] as $key => $val) {
                if ((bool)$_SESSION['service_level_info'][$key]['isPublic']) {
                    // this coud have been an OR, but looks more readable this way
                    if (MULTIPROVIDER && $_SESSION['sess_selected_tester'] == ADA_PUBLIC_TESTER) {
                        $shownServiceTypes[$key] = $val;
                    } elseif (!MULTIPROVIDER) {
                        $shownServiceTypes[$key] = $val;
                    }
                } else {
                    $shownServiceTypes[$key] = $val;
                }
            }

            $desc = translateFN('Tipo di corso') . ':';
            $this->addSelect('service_level', $desc, $shownServiceTypes, DEFAULT_SERVICE_TYPE);
        }

        $this->setSubmitValue(translateFN('Avanti') . "&nbsp;&gt;&gt;");

        $this->setOnSubmit('return goToImportSelectNode();');
    }
}

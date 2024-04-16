<?php

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
use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for handling file upload module form
 *
 * @author giorgio
 */
class FormUploadImportFile extends FForm
{
    public function __construct($formName, $options = [])
    {
        parent::__construct();
        $this->setName($formName);
        $this->addFileInput('importfile', translateFN('Seleziona un file .zip da importare'));

        $formElements = [];
        if (array_key_exists('forceRunImport', $options)) {
            $formElements[] = FormControl::create(FormControl::INPUT_HIDDEN, 'forceRunImport', '')->withData(1);
        }

        if (array_key_exists('isAuthorImporting', $options)) {
            $formElements[] = FormControl::create(FormControl::INPUT_HIDDEN, 'isAuthorImporting', '')->withData($options['isAuthorImporting'] ? 1 : 0);
        }

        $importURL = FormControl::create(FormControl::INPUT_TEXT, 'importURL', translateFN('URL per l\'importazione'));
        if (array_key_exists('importURL', $options)) {
            $importURL->withData($options['importURL']);
            $importURL->setAttribute('style', 'display:none;');
        }
        $formElements[] = $importURL;
        $formElements[] = FormControl::create(FormControl::INPUT_BUTTON, 'importUrlBtn', translateFN('Carica da URL e importa'));

        // creare il fieldset con i campi appena creati
        $this->addFieldset(translateFN('oppure inserisci una URL da cui importare'), 'importUrlFSet')->withData($formElements);
    }
}

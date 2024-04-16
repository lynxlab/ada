<?php

/**
 * UserSkillsForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    giorgio <g.consorti@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Description of UserSkillsForm
 *
 * @package   Default
 * @author    giorgio <g.consorti@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class UserExtraForm extends FForm
{
    public function __construct($action = null)
    {
        parent::__construct();

        if ($action != null) {
            $this->setAction($action);
        }
        $this->setName('extraDataForm');
        $this->setSubmitValue(translateFN('Salva'));
        /**
         * Following value to be set with a call
         * to fillWithArrayData made by the code
         * who's actually using this form
         */
        $this->addHidden('id_utente')->withData(0);

        self::addExtraControls($this);
    }

    public static function addExtraControls(FForm $theForm, $withforceSaveExtra = false)
    {
        $theForm->addTextInput('samplefield', translateFN('Esempio'))
        ->setRequired()
        ->setValidator(FormValidator::NOT_EMPTY_STRING_VALIDATOR);

        // add an extra field if we're embedding the controls
        // in the standard edit_user form
        if ($withforceSaveExtra) {
            $theForm->addHidden('forceSaveExtra')->withData(true);
        }
    }
}

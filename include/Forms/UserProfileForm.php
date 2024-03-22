<?php

/**
 * UserSubscriptionForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;
use Lynxlab\ADA\Main\Output\UILayout;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class UserProfileForm extends UserRegistrationForm
{
    /**
     * @author giorgio 29/mag/2013
     *
     * added extra parameter to constructor to allow editing of student confirmed registration
     *
     */
    public function __construct($languages = [], $allowEditProfile = false, $allowEditConfirm = false, $action = null)
    {
        parent::__construct();
        $this->addHidden('id_utente')->withData(0);

        /*
         *  @author:Sara  20/05/2014
         *  Workaround to remove the Google-Chrome autocomplete functionality.
         */
        $j = 'return remove_false_element()';
        $this->setOnSubmit($j);
        $false_username = FormControl::create(FormControl::INPUT_TEXT, 'false_username', '');
        $false_password = FormControl::create(FormControl::INPUT_PASSWORD, 'false_password', '');
        $false_elements_fieldset = FormControl::create(FormControl::FIELDSET, 'false_elements_fieldset', '');
        $false_elements_fieldset->setHidden();
        $false_elements_fieldset->withData([$false_username,$false_password]);
        $this->addControl($false_elements_fieldset);



        $this->addPasswordInput('password', translateFN('Password'));
        //->setValidator(FormValidator::PASSWORD_VALIDATOR);

        $this->addPasswordInput('passwordcheck', translateFN('Conferma la password'));

        /**
         * If the swithcer does not use this form to edit her own
         * profile, the avatar upload must be disabled
         */
        if ($_SESSION['sess_userObj']->getType() != AMA_TYPE_SWITCHER || !$allowEditConfirm) {
            $this->addFileInput('avatarfile', translateFN('Seleziona un file immagine per il tuo avatar'));
            $this->addTextInput('avatar', null);
        }

        if ($action != null) {
            $this->setAction($action);
        }

        /// $this->addTextInput('telefono', translateFN('Telefono'));
        $telefono = FormControl::create(FormControl::INPUT_TEXT, 'telefono', translateFN('Telefono'));
        $cap = FormControl::create(FormControl::INPUT_TEXT, 'cap', translateFN('cap'));
        $citta = FormControl::create(FormControl::INPUT_TEXT, 'citta', translateFN('Città'));
        $indirizzo = FormControl::create(FormControl::INPUT_TEXT, 'indirizzo', translateFN('Indirizzo'));
        $provincia = FormControl::create(FormControl::INPUT_TEXT, 'provincia', translateFN('Provincia'));
        $countries = CountriesList::getCountriesList($_SESSION['sess_user_language']);
        $nazione = FormControl::create(FormControl::SELECT, 'nazione', translateFN('Nazione'));
        $nazione->withData($countries);
        $this->addFieldset(translateFN('Dati residenza'), 'residenza')->withData([$indirizzo,$cap,$citta,$provincia,$nazione,$telefono]);

        //        $this->addTextInput('indirizzo', translateFN('Indirizzo'));

        //        $this->addTextInput('citta', translateFN('Città'));

        //        $this->addTextInput('provincia', translateFN('Provincia'));

        /*
                $countries = countriesList::getCountriesList($_SESSION['sess_user_language']);
                $this->addSelect(
                    'nazione',
                     translateFN('Nazione'),
                     $countries,
                'IT');
         *
         */
        $this->addTextInput('codice_fiscale', translateFN('Cod. Fiscale'));

        /**
         * @author giorgio 29/mag/2013
         *
         * added select field to allow editing of user confirmed registration status
         */
        if ($allowEditConfirm) {
            $this->addSelect(
                'stato',
                translateFN('Confermato'),
                [
                          ADA_STATUS_PRESUBSCRIBED => translateFN('No'),
                          ADA_STATUS_REGISTERED => translateFN('Si'),
                         ],
                0
            );
        }
        //->setValidator(FormValidator::PASSWORD_VALIDATOR);
        if ($allowEditProfile) {
            $this->addTextarea('profilo', translateFN('Il tuo profilo utente'));
        }

        $layoutsAr = [
          ''         => translateFN('seleziona un layout'),
        ];
        require_once ROOT_DIR . '/include/Layout.inc.php';
        $layoutObj = new UILayout();
        $avalaibleLayoutAr = $layoutObj->getAvailableLayouts();
        $layouts = array_merge($layoutsAr, $avalaibleLayoutAr);
        $this->addSelect(
            'layout',
            translateFN('Layout'),
            $layouts,
            0
        );

        if (is_array($languages) && count($languages) > 0) {
            $languagesAr[0] = translateFN('seleziona una lingua');
            $languages = array_replace($languagesAr, $languages);
            //            $languages = array_merge($languagesAr,$languages);
            $this->addSelect(
                'lingua',
                translateFN('Lingua'),
                $languages,
                0
            );
        }
    }
}

<?php

/**
 * UserRegistrationForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\Secretquestion\SecretQuestionForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Description of UserRegistrationForm
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class UserRegistrationForm extends FForm
{
    public function __construct($cod = false, $action = null)
    {
        parent::__construct();

        if ($action != null) {
            $this->setAction($action);
        }
        $this->setName('registration');

        $this->addTextInput('nome', translateFN('Nome'))
             ->setRequired()
             ->setValidator(FormValidator::FIRSTNAME_VALIDATOR);

        $this->addTextInput('cognome', translateFN('Cognome'))
             ->setRequired()
             ->setValidator(FormValidator::LASTNAME_VALIDATOR);

        $this->addTextInput('birthdate', translateFN('Data di nascita'))
             ->setRequired()
             ->setValidator(FormValidator::DATE_VALIDATOR);

        //         $this->addTextInput('birthcity', translateFN('Comune o stato estero di nascita'))
        //         ->setRequired()
        //         ->setValidator(FormValidator::NOT_EMPTY_STRING_VALIDATOR);

        //         $this->addTextInput('birthprovince', translateFN('Provincia di nascita'));

        $email = $this->addTextInput('email', translateFN('Email'));
        if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
            (new SecretQuestionForm(strcmp(static::class, self::class) === 0))->addControlsToForm($this);
        } else {
            $email->setRequired()->setValidator(FormValidator::EMAIL_VALIDATOR);
        }

        //         $this->addSelect(
        //             'sesso',
        //              translateFN('Genere'),
        //              array(
        //                  '0' => translateFN('Scegli un genere'),
        //                  'M' => translateFN('Maschio'),
        //                  'F' => translateFN('Femmina')
        //              ),
        //              '0');

        //         $this->addTextInput('matricola', translateFN('numero di matricola'));
        /*
         *
                if ($cod) {
                    $this->addTextInput('codice', translateFN('Codice'))
                         ->setRequired()
                         ->setValidator(FormValidator::NOT_EMPTY_STRING_VALIDATOR);
                }

         *
         */
        $alert = CDOMElement::create('div', 'class:ui small modal,id:registrationError');
        $aHeader = CDOMElement::create('div', 'class:header');
        $aHeader->addChild(new CText(translateFN('Attenzione')));
        $aContent = CDOMElement::create('div', 'class:content');
        $aContent->addChild(new CText('<i class="large warning icon"></i><span class="alertMSG"></span>'));

        $aActions = CDOMElement::create('div', 'class:actions');
        $button = CDOMElement::create('div', 'class:ui red button');
        $button->addChild(new CText(translateFN('OK')));
        $aActions->addChild($button);

        $alert->addChild($aHeader);
        $alert->addChild($aContent);
        $alert->addChild($aActions);
        $alert->setAttribute('data-render-hiddenparent', true);
        $this->addCDOM($alert);

        foreach (['invalidDate' => 'Data non valida', 'notAdult' => 'Devi essere maggiorenne per registrarti'] as $msgID => $message) {
            $aMSG = CDOMElement::create('span', 'id:' . $msgID);
            $aMSG->setAttribute('style', 'display:none');
            $aMSG->addChild(new CText(translateFN($message)));
            $aMSG->setAttribute('data-render-hiddenparent', true);
            $this->addCDOM($aMSG);
        }
    }
}

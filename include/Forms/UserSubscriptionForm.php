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

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;
use Lynxlab\ADA\Main\Forms\UserRegistrationForm;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class UserSubscriptionForm extends UserRegistrationForm
{
    public function __construct()
    {
        parent::__construct();
        $tipo = DataValidator::checkInputValues('tipo', 'Integer');
        if (false === $tipo) {
            $tipo = DataValidator::checkInputValues('tipo', 'Integer', INPUT_POST);
        }
        if (!in_array($tipo, [AMA_TYPE_AUTHOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SUPERTUTOR])) {
            $tipo = 0;
        }

        if (!(ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true)) {
            $this->addTextInput('username', translateFN('Nome utente'))
                 ->setRequired()
                 ->setValidator(FormValidator::EMAIL_VALIDATOR);
        }

        $this->addPasswordInput('password', translateFN('Password'))
             ->setRequired()
             ->setValidator(FormValidator::PASSWORD_VALIDATOR);

        $this->addPasswordInput('passwordcheck', translateFN('Conferma la password'))
             ->setRequired()
             ->setValidator(FormValidator::PASSWORD_VALIDATOR);

        $this->addSelect(
            'tipo',
            translateFN('Tipo Utente'),
            [
                    0 => translateFN('Scegli il tipo...'),
                    AMA_TYPE_AUTHOR => translateFN('Autore'),
                    AMA_TYPE_STUDENT => translateFN('Studente'),
                    AMA_TYPE_TUTOR => translateFN('Tutor'),
                    AMA_TYPE_SUPERTUTOR => translateFN('Super Tutor'),
                    ],
            $tipo
        )
             ->setRequired()
             ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);
    }
}

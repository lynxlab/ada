<?php

/**
 * NEWSLETTER MODULE.
 *
 * @package     newsletter module
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            newsletter
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Newsletter;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class FormFilterNewsletter extends FForm
{
    public function __construct($formName, $courseList)
    {
        parent::__construct();
        $this->setName($formName);

        $this->addHidden('id');
        $this->addHidden('recipientsCount')->withData(0);

        $this->addHidden('enqueuedmsg')->withData(translateFN('Newsletter inoltrata per l\'invio'));

        $userType = FormControl::create(FormControl::SELECT, 'userType', translateFN('Tipo Utente'))
                    ->withData(
                        [
                                0 => translateFN('Scegli il tipo...'),
                                AMA_TYPE_AUTHOR => translateFN('Autore'),
                                AMA_TYPE_STUDENT => translateFN('Studente'),
                                AMA_TYPE_TUTOR => translateFN('Tutor'),
                                AMA_TYPE_SWITCHER => translateFN('Switcher'),
                                9999 => translateFN('Tutti'),
                            ],
                        0
                    )
                    ->setRequired()
                    ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $userPlatformStatus = FormControl::create(FormControl::SELECT, 'userPlatformStatus', translateFN('Stato Studente nella Piattaforma'))
                              ->withData(
                                  [
                                        -1 => translateFN('Scegli lo stato...'),
                                        ADA_STATUS_PRESUBSCRIBED => translateFN('Non Confermato'),
                                        ADA_STATUS_REGISTERED => translateFN('Confermato'),
                                    ],
                                  -1
                              )
                              ->setAttribute('disabled', 'true')
                              ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $userCourseStatus = FormControl::create(FormControl::SELECT, 'userCourseStatus', translateFN('Stato Studente nel corso selezionato'))
                            ->withData(
                                [
                                        -1 => translateFN('Scegli lo stato...'),
                                        ADA_SERVICE_SUBSCRIPTION_STATUS_UNDEFINED => translateFN('In visita'),
                                        ADA_SERVICE_SUBSCRIPTION_STATUS_REQUESTED => translateFN('Preiscritto'),
                                        ADA_SERVICE_SUBSCRIPTION_STATUS_ACCEPTED  => translateFN('Iscritto'),
                                        ADA_SERVICE_SUBSCRIPTION_STATUS_SUSPENDED => translateFN('Rimosso'),
                                        ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED => translateFN('Completato'),
                                    ],
                                -1
                            )
                                ->setAttribute('disabled', 'true')
                                ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $this->addFieldset(translateFN('Filtro') . ' ' . translateFN('Utenti'), 'userChoice')->withData([ $userType, $userPlatformStatus, $userCourseStatus ]);

        $courseList[0]  = translateFN('Scegli il corso...');

        $courseSel = FormControl::create(FormControl::SELECT, 'idCourse', translateFN('Corso'))
                     ->withData($courseList, 0)
                     ->setAttribute('disabled', 'true')
                     ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $instanceSel = FormControl::create(FormControl::SELECT, 'idInstance', translateFN('Istanza Corso'))
                        ->setAttribute('disabled', 'true')
                        ->setValidator(FormValidator::POSITIVE_NUMBER_VALIDATOR);

        $this->addFieldset(translateFN('Filtro') . ' ' . translateFN('Corsi'), 'courseChoice')->withData([ $courseSel, $instanceSel ]);

        $this->setSubmitValue(translateFN('Invia Newsletter'));
    }
}

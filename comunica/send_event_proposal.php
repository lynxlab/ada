<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Comunica\Event\ADAEventProposal;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout','user','course','course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_STUDENT => ['layout'],
];


/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
ComunicaHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */

//$success    = HTTP_ROOT_DIR.'/comunica/list_events.php';
//$error_page = HTTP_ROOT_DIR.'/comunica/send_event.php';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     * Controllo validita' sui dati in arrivo dal form
     */

    if (isset($_SESSION['event_msg_id'])) {
        $previous_proposal_msg_id = $_SESSION['event_msg_id'];
        $event_token = '';
    } else {
        /*
         * Costruiamo qui l'identificatore della catena di proposte che portano a
         * fissare un appuntamento.
         */
        $event_token = ADAEventProposal::generateEventToken($id_user, $userObj->getId(), $id_course_instance);
    }

    /*
     * Validazione dei dati: le date proposte devono essere valide e non devono essere antecedenti
     * a quella odierna (come timestamp)
     */
    $errors = [];

    if (DataValidator::validateNotEmptyString($subject) === false) {
        $errors['subject'] = ADA_EVENT_PROPOSAL_ERROR_SUBJECT;
    }

    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $date1, $time1, $sess_selected_tester)) !== true) {
        $errors['date1'] = $value;
    }
    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $date2, $time2, $sess_selected_tester)) !== true) {
        $errors['date2'] = $value;
    }
    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $date3, $time3, $sess_selected_tester)) !== true) {
        $errors['date3'] = $value;
    }


    $datetimesAr = [
    ['date' => $date1, 'time' => $time1],
    ['date' => $date2, 'time' => $time2],
    ['date' => $date3, 'time' => $time3],
    ];

    $message_content = ADAEventProposal::generateEventProposalMessageContent($datetimesAr, $id_course_instance, $notes);

    if (count($errors) > 0) {
        $data = [
        'testo'  => $message_content,
        'titolo' => $subject,
        'flags'  => $type,
        ];
        $form = CommunicationModuleHtmlLib::getEventProposalForm($id_user, $data, $errors, $sess_selected_tester);
    } else {
        /*
         * If we are ready to send the message, we can safely unset $_SESSION['event_msg_id'])
         */
        unset($_SESSION['event_msg_id']);

        $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));

        $addresseeObj = MultiPort::findUser($id_user);



        $message_ha = [
        'tipo'        => ADA_MSG_AGENDA,
        'flags'       => ADA_EVENT_PROPOSED | $type,
        'mittente'    => $user_uname,
        'destinatari' => [$addresseeObj->username],
        'data_ora'    => 'now',
        'titolo'      => ADAEventProposal::addEventToken($event_token, $subject),
        'testo'       => $message_content,
        ];

        $res = $mh->sendMessage($message_ha);

        if (AMADataHandler::isError($res)) {
            $errObj = new ADAError(
                $res,
                translateFN('Impossibile spedire il messaggio'),
                null,
                null,
                null,
                $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio'))
            );
        }

        /*
         * If there aren't errors, redirect the user to his agenda
         */
        /*
         * SE ABBIAMO INVIATO UNA MODIFICA AD UNA PROPOSTA DI APPUNTAMENTO,
         * LA PROPOSTA PRECEDENTE DEVE ESSERE MARCATA COME CANCELLATA IN
         * DESTINATARI MESSAGGI PER L'UTENTE PRACTITIONER
         */
        if (isset($previous_proposal_msg_id)) {
            MultiPort::removeUserAppointments($userObj, [$previous_proposal_msg_id]);
        }

        /*
         * Inviamo una mail all'utente in cui lo informiamo del fatto che il
         * practitioner ha inviato delle nuove proposte
         */
        $admtypeAr = [AMA_TYPE_ADMIN];
        $admList = $common_dh->getUsersByType($admtypeAr);
        if (!AMADataHandler::isError($admList)) {
            $adm_uname = $admList[0]['username'];
        } else {
            $adm_uname = ""; // ??? FIXME: serve un superadmin nel file di config?
        }
        $clean_subject = ADAEventProposal::removeEventToken($subject);
        $message_content = sprintf(translateFN('Dear user, the practitioner %s has sent you new proposal dates for the appointment: %s.'), $userObj->getFullName(), $clean_subject);
        $message_ha = [
        'tipo'        => ADA_MSG_MAIL,
        'mittente'    => $adm_uname,
        'destinatari' => [$addresseeObj->username],
        'data_ora'    => 'now',
        'titolo'      => 'ADA: ' . translateFN('new event proposal dates'),
        'testo'       => $message_content,
        ];
        $res = $mh->sendMessage($message_ha);
        if (AMADataHandler::isError($res)) {
            $errObj = new ADAError(
                $res,
                translateFN('Impossibile spedire il messaggio'),
                null,
                null,
                null,
                $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio'))
            );
        }

        $text = translateFN("La proposta di appuntamento è stata inviata con successo all'utente ") . $addresseeObj->getFullName() . ".";
        $form = CommunicationModuleHtmlLib::getOperationWasSuccessfullView($text);
        //header('Location: '.HTTP_ROOT_DIR.'/comunica/list_events.php');
        //exit();
    }
} else {
    if (isset($msg_id)) {
        $data = MultiPort::getUserAppointment($userObj, $msg_id);
        if ($data['flags'] & ADA_EVENT_PROPOSAL_OK) {
            /*
             * The user accepted one of the three proposed dates for the appointment.
             * E' UN CASO CHE NON SI PUO' VERIFICARE, visto che vogliamo che l'appuntamento
             * venga inserito non appena l'utente accetta una data porposta dal practitioner
             */
            $form = CommunicationModuleHtmlLib::getConfirmedEventProposalForm($data);
        } else {
            /*
             * The user did not accept the proposed dates for the appointment
             */
            $_SESSION['event_msg_id'] = $msg_id;
            $id_user = $data['id_mittente'];
            $errors = [];
            $form = CommunicationModuleHtmlLib::getEventProposalForm($id_user, $data, $errors, $sess_selected_tester);
        }
    } else {
        /*
         * Build the form used to propose an event. Da modificare in modo da passare
         * eventualmente il contenuto dei campi del form nel caso si stia inviando
         * una modifica ad una proposta di appuntamento.
         */
        $errors = [];
        $data = [];
        $form = CommunicationModuleHtmlLib::getEventProposalForm($sess_id_user, $data, $errors, $sess_selected_tester);
    }
}

$title = translateFN('Invia proposta di appuntamento');

$content_dataAr = [
  'user_name'      => $user_name,
  'user_type'      => $user_type,
  'titolo'         => $titolo,
  'course_title'   => '<a href="../browsing/main_index.php">' . $course_title . '</a>',
  'status'         => $err_msg,
  'data'       => $form->getHtml(),
  'label'      => $title,
];


ARE::render($layout_dataAr, $content_dataAr);

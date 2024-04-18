<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Comunica\Event\ADAEvent;
use Lynxlab\ADA\Comunica\Event\ADAEventProposal;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\getTimezoneOffset;
use function Lynxlab\ADA\Main\Utilities\sumDateTimeFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout','user','course'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT         => ['layout'],
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
$error_page = HTTP_ROOT_DIR . '/comunica/event_proposal.php';

$newline = "\r\n";

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     * Controllo validita' sui dati in arrivo dal form
     */
    $selected_date         = $_POST['date'];
    $course_instance       = $_POST['id_course_instance'];
    $practitioner_proposal = $_SESSION['practitioner_proposal'];
    $msg_id                = $_SESSION['event_msg_id'];
    unset($_SESSION['practitioner_proposal']);
    unset($_SESSION['event_msg_id']);

    $mittente    = $user_uname;
    $destinatari = [$practitioner_proposal['mittente']];
    $subject     = $practitioner_proposal['titolo'];


    $tutor_id = $common_dh->findUserFromUsername($practitioner_proposal['mittente']);
    if (AMACommonDataHandler::isError($tutor_id)) {
        $errObj = new ADAError(null, translateFN("Errore nell'ottenimento del practitioner"));
    }
    $tutorObj = MultiPort::findUser($tutor_id);

    /*
     * Get the ada admin username, it is needed in order to send email notifications
     * to both the users as ADA.
     */
    $admtypeAr = [AMA_TYPE_ADMIN];
    $admList = $common_dh->getUsersByType($admtypeAr);
    if (!AMADataHandler::isError($admList)) {
        $adm_uname = $admList[0]['username'];
    } else {
        $adm_uname = ""; // ??? FIXME: serve un superadmin nel file di config?
    }

    /*
     * Obtain a messagehandler instance for the correct tester
     */
    if (MultiPort::isUserBrowsingThePublicTester()) {
        /*
         * In base a event_msg_id, ottenere connessione al tester appropriato
         */
        $data_Ar = MultiPort::geTesterAndMessageId($msg_id);
        $tester  = $data_Ar['tester'];
    } else {
        /*
         * We are inside a tester
         */
        $tester = $sess_selected_tester;
    }
    $tester_dsn = MultiPort::getDSN($tester);

    $mh = MessageHandler::instance($tester_dsn);


    if ($selected_date == 0) {
        /*
         * Nessuna tra le date proposte va bene
         */

        $flags = ADA_EVENT_PROPOSAL_NOT_OK | $practitioner_proposal['flags'];
        $message_content = $practitioner_proposal['testo'];

        $message_ha = [
        'tipo'        => ADA_MSG_AGENDA,
        'flags'       => $flags,
        'mittente'    => $mittente,
        'destinatari' => $destinatari,
        'data_ora'    => 'now',
        'titolo'      => $subject,
        'testo'       => $message_content,
        ];

        /*
         * This email message is sent only to the practitioner.
         * Send here.
         */
        $clean_subject = ADAEventProposal::removeEventToken($subject);
        $email_message_ha = [
        'tipo'        => ADA_MSG_MAIL,
        'mittente'    => $adm_uname,
        'destinatari' => $destinatari,
        'data_ora'    => 'now',
        'titolo'      => 'ADA: ' . translateFN('a user asks for new event proposal dates'),
        'testo'       => sprintf(translateFN('Dear practitioner, the user %s is asking you for new event dates for the appointment %s.\r\nThank you.'), $userObj->getFullName(), $clean_subject),
        ];

        /*
         * Send the email message
         */
        $res = $mh->sendMessage($email_message_ha);
        if (AMADataHandler::isError($res)) {
            $errObj = new ADAError(
                $res,
                translateFN('Impossibile spedire il messaggio'),
                null,
                null,
                null,
                $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio ERR_0'))
            );
        }
        $text = sprintf(translateFN("La richiesta di modifica delle date proposte è stata correttamente inviata all'utente %s."), $tutorObj->getFullName());
    } else {
        /*
         * L'utente ha scelto una data tra quelle proposte, creiamo l'appuntamento
         * e, se di tipo appuntamento in chat, creiamo anche la chatroom.
         */

        $tester_dh = AMADataHandler::instance($tester_dsn);

        $id_course = $tester_dh->getCourseIdForCourseInstance($course_instance);
        if (AMADataHandler::isError($id_course)) {
            $errObj = new ADAError($id_chatroom, translateFN("An error occurred."));
        }

        $tester_infoAr = $common_dh->getTesterInfoFromPointer($tester);
        if (AMACommonDataHandler::isError($tester_infoAr)) {
            $errObj = new ADAError($service_infoAr, translateFN("An error occurred."));
        }
        $tester_name = $tester_infoAr[1];

        $service_infoAr = $common_dh->getServiceInfoFromCourse($id_course);
        if (AMACommonDataHandler::isError($service_infoAr)) {
            $errObj = new ADAError($service_infoAr, translateFN("An error occurred."));
        }
        $service_name = translateFN($service_infoAr[1]);

        $date_data_Ar = explode('_', $_POST['date']);
        $date = $date_data_Ar[0];
        $time = $date_data_Ar[1];
        $time = "$time:00";

        $offset = 0;
        if ($tester === null) {
            $tester_TimeZone = SERVER_TIMEZONE;
        } else {
            $tester_TimeZone = MultiPort::getTesterTimeZone($tester);
            $offset = getTimezoneOffset($tester_TimeZone, SERVER_TIMEZONE);
        }
        $data_ora = sumDateTimeFN([$date,$time]) - $offset;

        $event_token = ADAEventProposal::extractEventToken($subject);

        $event_flag = 0;
        if (ADA_CHAT_EVENT & $practitioner_proposal['flags']) {
            $new_subject    = translateFN('Appuntamento in chat');
            //$url = HTTP_ROOT_DIR.'/comunica/chat.php';
            $event_flag = ADA_CHAT_EVENT;
        } elseif (ADA_VIDEOCHAT_EVENT & $practitioner_proposal['flags']) {
            $new_subject    = translateFN('Appuntamento in videochat');
            //$url = HTTP_ROOT_DIR.'/comunica/videochat.php';
            $event_flag = ADA_VIDEOCHAT_EVENT;
        } elseif (ADA_PHONE_EVENT & $practitioner_proposal['flags']) {
            $new_subject    = translateFN('Appuntamento telefonico');
            //$url = NULL;
            $event_flag = ADA_PHONE_EVENT;
        } elseif (ADA_IN_PLACE_EVENT & $practitioner_proposal['flags']) {
            $new_subject    = translateFN('Appuntamento in presenza');
            //$url = NULL;
            $event_flag = ADA_IN_PLACE_EVENT;
        }

        $message_text  = sprintf(translateFN('Provider: "%s".%sService: "%s".%s'), $tester_name, $newline, $service_name, $newline);
        $message_text .= ' ' . sprintf(translateFN("L'appuntamento, di tipo %s,  si terrà il giorno %s alle ore %s."), $new_subject, $date, $time);

        /**
         * In case the user is confirming a videochat or a chat appointment,
         * we will also add a link to enter the chat or videochat directly from the
         * appointment message.
         */
        if (
            (ADA_CHAT_EVENT & $practitioner_proposal['flags'])
            || (ADA_VIDEOCHAT_EVENT & $practitioner_proposal['flags'])
        ) {
            if (ADA_CHAT_EVENT & $practitioner_proposal['flags']) {
                $event_flag = ADA_CHAT_EVENT;

                $end_time = $data_ora + $service_infoAr[7]; //durata_max_incontro

                $chatroom_ha = [
                'id_course_instance' => $course_instance,
                'id_chat_owner'      => $practitioner_proposal['id_mittente'], // this is the id of the practitioner
                //'chat_type'      => $chat_type, // di default e' CLASS_CHAT
                'chat_title'       => ADAEventProposal::addEventToken($event_token, $new_subject),
                'chat_topic'       => '',
                'start_time'       => $data_ora, // parte alla stessa ora dell'appuntamento
                'end_time'         => $end_time,//$data_ora + 3600,
                 //'welcome_msg'        => $welcome_msg,  //usiamo messaggio di benvenuto di default
                //'max_users'          => $max_users     // di default 2 utenti
                ];
                $id_chatroom = ChatRoom::addChatroomFN($chatroom_ha, $tester_dsn);
                if (AMADataHandler::isError($id_chatroom)) {
                    $errObj = new ADAError(
                        $id_chatroom,
                        translateFN("Si è verificato un errore nella creazione della chatroom. L'appuntamento non è stato creato."),
                        null,
                        null,
                        null,
                        $userObj->getHomePage()
                    );
                }
            } else {
                $event_flag = ADA_VIDEOCHAT_EVENT;
            }
            $message_text .= ADAEvent::generateEventMessageAction($event_flag, $id_course, $course_instance);
        }


        $message_ha = [
        'tipo'          => ADA_MSG_AGENDA,
        'flags'       => ADA_EVENT_CONFIRMED | $event_flag,
        'mittente'    => $user_uname,
        'destinatari' => [$user_uname,$practitioner_proposal['mittente']],
        'data_ora'      => $data_ora,
        'titolo'      => ADAEventProposal::addEventToken($event_token, $new_subject),
        'testo'       => $message_text,
        ];

        /*
         * Here we send an email message as an appointment reminder.
         * We send it seprately to the user and to the practitioner, since we do not
         * want the user to know the practitioner's email address.
         */

        $appointment_type = $new_subject;
        $appointment_title = ADAEventProposal::removeEventToken($subject);
        $appointment_message = sprintf(translateFN('Provider: "%s".%sService: "%s".%s'), $tester_name, $newline, $service_name, $newline)
                         . ' ' . sprintf(translateFN('This is a reminder for the appointment %s: %s in date %s at time %s'), $appointment_title, $appointment_type, $date, $time);

        $practitioner_email_message_ha = [
        'tipo'        => ADA_MSG_MAIL,
        'mittente'    => $adm_uname,
        'destinatari' => [$practitioner_proposal['mittente']],
        'data_ora'    => 'now',
        'titolo'      => 'ADA: ' . translateFN('appointment reminder'),
        'testo'       => $appointment_message,
        ];

        $user_email_message_ha = [
        'tipo'        => ADA_MSG_MAIL,
        'mittente'    => $adm_uname,
        'destinatari' => [$user_uname],
        'data_ora'    => 'now',
        'titolo'      => 'ADA: ' . translateFN('appointment reminder'),
        'testo'       => $appointment_message,
        ];

        /*
         * Send the email message to the practitioner
         */
        $res = $mh->sendMessage($practitioner_email_message_ha);
        if (AMADataHandler::isError($res)) {
            $errObj = new ADAError(
                $res,
                translateFN('Impossibile spedire il messaggio'),
                null,
                null,
                null,
                $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio ERR_1'))
            );
        }

        /*
         * Send the email message to the user
         */
        $res = $mh->sendMessage($user_email_message_ha);
        if (AMADataHandler::isError($res)) {
            $errObj = new ADAError(
                $res,
                translateFN('Impossibile spedire il messaggio'),
                null,
                null,
                null,
                $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio ERR_2'))
            );
        }

        // TODO: al posto di $practitioner_proposal['mittente'] passare $tutorObj->getFullname()
        $text = sprintf(translateFN('Provider: "%s".%sService: "%s".%s'), $tester_name, $newline, $service_name, $newline)
                        . ' ' . sprintf(
                            translateFN("L'appuntamento con l'utente %s, in data %s alle ore %s, è stato inserito correttamente."),
                            $tutorObj->getFullName(),
                            $date,
                            $time
                        );
    }

    $res = $mh->sendMessage($message_ha);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError(
            $res,
            translateFN('Impossibile spedire il messaggio'),
            null,
            null,
            null,
            $error_page . '?err_msg=' . urlencode(translateFN('Impossibile spedire il messaggio ERR_3'))
        );
    }


    /*
     * SE NON SI SONO VERIFICATI ERRORI NELL'INVIO DELLA RISPOSTA AL TUTOR,
     * POSSO MARCARE COME ELIMINATO IL MESSAGGIO RELATIVO ALLA PROPOSTA DEL PRACTITIONER
     */

    MultiPort::removeUserAppointments($userObj, [$msg_id]);

    $form = CommunicationModuleHtmlLib::getOperationWasSuccessfullView($text);
} elseif (isset($_GET['err_msg'])) {
    $error_message = translateFN('An error occurred while processing your request, please try again later.')
          . '<br />'
          . translateFN('If the problem persists, please contact the administrator.');

    $form = CommunicationModuleHtmlLib::getOperationWasSuccessfullView($error_message);
} elseif (isset($msg_id)) {
    $data = MultiPort::getUserAppointment($userObj, $msg_id);
    $_SESSION['practitioner_proposal'] = $data;
    $_SESSION['event_msg_id'] = $msg_id;
    /*
     * Check if the user has already an appointment in one of the proposed dates
     * or if an appointment proposal is in the past.
     */
    $datetimesAr = ADAEventProposal::extractDateTimesFromEventProposalText($data['testo']);
    if ($datetimesAr === false) {
        $errObj = new ADAError(null, translateFN("Errore nell'ottenimento delle date per l'appuntamento"));
    }

    /*
     * Obtain a messagehandler instance for the correct tester
     */
    if (MultiPort::isUserBrowsingThePublicTester()) {
        /*
         * In base a event_msg_id, ottenere connessione al tester appropriato
         */
        $data_Ar = MultiPort::geTesterAndMessageId($msg_id);
        $tester  = $data_Ar['tester'];
    } else {
        /*
         * We are inside a tester
         */
        $tester = $sess_selected_tester;
    }

    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $datetimesAr[0]['date'], $datetimesAr[0]['time'], $tester)) !== true) {
        $errors['date1'] = $value;
    }
    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $datetimesAr[1]['date'], $datetimesAr[1]['time'], $tester)) !== true) {
        $errors['date2'] = $value;
    }
    if (($value = ADAEventProposal::canProposeThisDateTime($userObj, $datetimesAr[2]['date'], $datetimesAr[2]['time'], $tester)) !== true) {
        $errors['date3'] = $value;
    }

    $form = CommunicationModuleHtmlLib::getProposedEventForm($data, $errors, $tester);
}

$titolo = translateFN('Proposta di appuntamento');

$content_dataAr = [
  'user_name'      => $user_name,
  'user_type'      => $user_type,
  'titolo'         => $titolo,
  'course_title'   => '<a href="../browsing/main_index.php">' . $course_title . '</a>',
  'status'         => $err_msg,
  'data'           => $form->getHtml(),
  'label'          => $titolo,
];

ARE::render($layout_dataAr, $content_dataAr);

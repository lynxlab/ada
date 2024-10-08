<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Comunica\Functions\exitWithJSONError;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();

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
 * Questo script esegue le operazioni di ada_chat.php, tranne l'inclusione dei vari script che compongono la chat.
 */


//if ( /*!isset($_GET['user']) ||*/ !isset($_GET['chatroom']) /*|| !isset($_GET['course_instance'])*/ )
if (!isset($_POST['chatroom'])) {
    exitWithJSONError(translateFN("Errore: parametri passati allo script PHP non corretti"));
}

$id_user      = $sess_id_user;
$id_chatroom  = $_POST['chatroom'];//$_GET['chatroom'];
if (!isset($exit_reason)) {
    $exit_reason  = EXIT_REASON_QUIT;
}


/*
 * uscita dalla chatroom
 */
switch ($exit_reason) {
    case EXIT_REASON_QUIT:
        // initialize a new ChatDataHandler object

        $chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
        if (AMADataHandler::isError($chatroomObj)) {
            exitWithJSONError(translateFN("Errore nella creazione della chatroom"));
        }

        //get the type of the chatroom
        $chatroomHa = $chatroomObj->getInfoChatroomFN($id_chatroom);
        if (AMADataHandler::isError($chatroomHa)) {
            exitWithJSONError(translateFN("Errore nell'ottenimento dei dati sulla chatroom"));
        }

        // we have to distinguish the case that the chatroom is a private chatroom
        // in that case we do not have to remove the user, since if we remove him
        // he will not be able to come back once he wants to rejoin the chatroom
        // in the case of private rooms we just set his status to 'E' value
        $chat_type = $chatroomHa['tipo_chat'];
        if ($chat_type == INVITATION_CHAT) {
            $user_exits = $chatroomObj->setUserStatusFN(
                $id_user,
                $id_user,
                $id_chatroom,
                ACTION_EXIT
            );
            if (AMADataHandler::isError($user_exits)) {
                exitWithJSONError(translateFN("Errore nell'uscita dell'utente dalla chatroom"));
            }
        } else {
            // removes user form database
            $user_exits = $chatroomObj->quitChatroomFN($id_user, $id_user, $id_chatroom);
            if (AMADataHandler::isError($user_exits)) {
                exitWithJSONError(translateFN("Errore nell'uscita dell'utente dalla chatroom"));
            }
        }

        // initialize a new MessageHandler object

        //$mh = new MessageHandler(MultiPort::getDSN($sess_selected_tester));
        $mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
        if (AMADataHandler::isError($mh)) {
            exitWithJSONError(translateFN("Errore nella creazione dell'oggetto MessageHandler"));
        }
        // send a message to announce the entrance of the user
        $message_ha['tipo']     = ADA_MSG_CHAT;
        $message_ha['data_ora'] = "now";
        $message_ha['mittente'] = $user_uname;//"admin";
        $message_ha['id_group'] = $id_chatroom;
        $message_ha['testo']    = addslashes(sprintf(translateFN("L'utente %s e' uscito dalla stanza!"), $user_uname));
        // delegate sending to the message handler
        $result = $mh->sendMessage($message_ha);
        if (AMADataHandler::isError($result)) {
            exitWithJSONError(translateFN("Errore nel'invio del messaggio"));
        }
        // message to display while logging out
        $display_message1 = translateFN("Grazie per aver effettuato correttamente il logout.");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_BANNED:
        // message to display while logging out
        $display_message1 = translateFN("Non puoi partecipare a questa chatroom, accesso negato.");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_KICKED:
        // message to display while logging out
        $display_message1 = translateFN("Sei stato escluso momentaneamente dalla chatroom.");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_NOT_EXIST:
        $display_message1 = translateFN("Si e' verificato un errore, non esiste una chatroom con l'ID specificato.<br><br>Impossibile proseguire");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_NOT_STARTED:
        // initialize a new ChatDataHandler object
        $chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
        //get the type of the chatroom
        // we have to distinguish the case that the chatroom is a private chatroom
        // in that case we do not have to remove the user, since if we remove him
        // he will not be able to come back once he wants to rejoin the chatroom
        // in the case of private rooms we just set his status to 'E' value
        $chatroomHa = $chatroomObj->getInfoChatroomFN($id_chatroom);
        $chat_type = $chatroomHa['tipo_chat'];
        if ($chat_type == INVITATION_CHAT) {
            $user_exits = $chatroomObj->setUserStatusFN(
                $id_user,
                $id_user,
                $id_chatroom,
                ACTION_EXIT
            );
        } else {
            // removes user form database
            $user_exits = $chatroomObj->quitChatroomFN($id_user, $id_user, $id_chatroom);
        }
        // message to display while logging out
        $display_message1 = translateFN("La chatroom cui stai provando ad accedere non e' stata ancora avviata! Verifica l'orario di apertura e riprova piu' tardi.");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_EXPIRED:
        // initialize a new ChatDataHandler object
        $chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
        //get the type of the chatroom
        // we have to distinguish the case that the chatroom is a private chatroom
        // in that case we do not have to remove the user, since if we remove him
        // he will not be able to come back once he wants to rejoin the chatroom
        // in the case of private rooms we just set his status to 'E' value
        $chatroomHa = $chatroomObj->getInfoChatroomFN($id_chatroom);
        $chat_type = $chatroomHa['tipo_chat'];
        if ($chat_type == INVITATION_CHAT) {
            $user_exits = $chatroomObj->setUserStatusFN(
                $id_user,
                $id_user,
                $id_chatroom,
                ACTION_EXIT
            );
        } else {
            // removes user form database
            $user_exits = $chatroomObj->quitChatroomFN($id_user, $id_user, $id_chatroom);
        }
        // message to display while logging out
        $display_message1 = translateFN("La chatroom cui stai provando ad accedere e' stata terminata!");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_WRONG_ROOM:
        // message to display while logging out
        $display_message1 = translateFN("La chatroom cui stai provando ad accedere non appartiene alla tua classe oppure non sei invitato!");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;
    case EXIT_REASON_FULL_ROOM:
        // initialize a new ChatDataHandler object
        $chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
        //get the type of the chatroom
        $chatroomHa = $chatroomObj->getInfoChatroomFN($id_chatroom);
        $chat_type = $chatroomHa['tipo_chat'];
        if ($chat_type == INVITATION_CHAT) {
            $user_exits = $chatroomObj->setUserStatusFN(
                $id_user,
                $id_user,
                $id_chatroom,
                ACTION_EXIT
            );
        } else {
            // removes user form database
            $user_exits = $chatroomObj->quitChatroomFN($id_user, $id_user, $id_chatroom);
        }
        // message to display while logging out
        $display_message1 = translateFN("La chatroom cui stai provando ad accedere ha raggiunto il massimo numero di utenti che pu� ospitare! Riprova pi� tardi!");
        $display_message2 = translateFN("Arrivederci da ADA Chat.");
        break;

    default:
} // switch

/*
 * invio della risposta JSON al simulatore
 */
$error  = 0;
//$error += ($ERROR_USER)     ?  1 : 0;
//$error += ($ERROR_CHATROOM) ?  2 : 0;

$response = '{"error":' . $error . ',"data":[{"time":"","sender":"admin","text":"Sei uscito dalla chat"}]}';

print $response;

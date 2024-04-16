<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Comunica\DataHandler\UserDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Comunica\Functions\exitWithJSONError;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
$start_time = microtime(true);

require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
ComunicaHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */

/*
 * Check that this script was called with the right arguments.
 * If not, stop script execution and report an error to the caller.
 */
if (!isset($_POST['chatroom']) || !isset($_POST['message_to_send'])) {
    exitWithJSONError(translateFN("Errore: parametri passati allo script PHP non corretti, "));
}
/*
 * Get the chatroom id
 */
$id_chatroom = $_POST['chatroom'];
/*
 * Get from $_POST the text message to send in the chatroom.
 */
$message_to_send = $_POST['message_to_send'];

$mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
if (AMADataHandler::isError($mh)) {
    exitWithJSONError(translateFN("Errore nella creazione dell'oggetto MessageHandler"));
}

$chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
if (AMADataHandler::isError($chatroomObj)) {
    exitWithJSONError(translateFN("Errore nella creazione della chatroom"));
}

session_write_close();

$testo = $message_to_send;

// Initialize errors array
$errors = [];

// get the status of the user into the current chatroom
$user_status = $chatroomObj->getUserStatusFN($sess_id_user, $id_chatroom);
if (AMADataHandler::isError($user_status)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento delle informazioni sullo stato dell'utente nella chatroom"));
}

if ($user_status == STATUS_MUTE) {
    exitWithJSONError(translateFN("Non hai il permesso di parlare in questa stanza!"));
}
if ($user_status == STATUS_BAN) {
    exitWithJSONError(translateFN("Sei stato allontanato da questa stanza!"), 2);
}
/*
 * Distinguish public from private message
 */

// this is the text including even the command and the receiver name, if it is present
$initial_text = strip_tags($testo, "<B><I><LINK><URL><U>");

// get the length of the text
$lung = strlen($initial_text);

// if no command string is present
if (
    !is_integer(strpos($initial_text, "/msg "))
    and !is_integer(strpos($initial_text, "/to "))
    and !is_integer(strpos($initial_text, "/a "))
) {
    //this is a common chatroom message
    $message_type = ADA_MSG_CHAT;
    $final_text = addslashes($initial_text);
} else {
    // text including the receiver name and the text to send
    $private_text = strstr($initial_text, " ");

    // the lenght of the text
    $num_char = strlen($private_text);

    // get the text, discard the first space
    $private_text = substr($private_text, 1, $num_char);

    // get the position of the first space after the name of the receiver
    $pos = strpos($private_text, " ");

    // extract the name of the receiver
    $receiver_name = substr($private_text, 0, $pos);

    //  $udh = UserDataHandler::instance(MultiPort::getDSN($sess_selected_tester));
    $udh = UserDataHandler::instance($_SESSION['sess_selected_tester_dsn']);
    if (AMADataHandler::isError($udh)) {
        exitWithJSONError(translateFN("Errore nella creazione dell'oggetto UserDataHandler"));
    }

    // verify that the user typed a correct username
    $res_ar = $udh->findUsersList([], "username='$receiver_name'");

    if (AMADataHandler::isError($res_ar)) {
        exitWithJSONError(translateFN("Errore nella lettura dello username del destinatario del messaggio privato"));
    }

    // getting only user_id
    $id_receiver = $res_ar[0][0];

    if (empty($id_receiver)) {
        $errors["receiver"] = translateFN("Il Destinatario inserito contiene un nome utente non valido!");
    }

    //extract the text to send
    $private_text = substr($private_text, ($pos + 1), $num_char);
    // private chat messagge type
    $message_type = ADA_MSG_PRV_CHAT;
    $final_text = $private_text;
}

// a message can be sent only if no errors are found
if (count($errors) == 0) {
    //prepare message to send
    $message_ha = $send_chat_message_form ?? null;
    $message_ha['tipo'] = $message_type;

    if ($message_ha['tipo'] == ADA_MSG_PRV_CHAT) {
        $message_ha['destinatari'] = $receiver_name;
    }

    $message_ha['testo'] = $final_text;
    //$id_chatroom = $id_chatroom;
    $message_ha['data_ora'] = "now";
    $message_ha['mittente'] = $user_uname;
    $message_ha['id_group'] = $id_chatroom;

    // delegate sending to the message handler
    $res = $mh->sendMessage($message_ha);
    if (AMADataHandler::isError($res)) {
        $code = $res->errorMessage();
        exitWithJSONError(translateFN("Errore nell'invio del messaggio $code"));
    }
}// end if count

$data =  ['id_chatroom' => $id_chatroom];

$end_time = microtime(true);
$total_time = $end_time - $start_time;
/*
 * Send back JSON data to caller
 */
$response = '{"error" : 0, "execution_time" : ' . $total_time . '}';
print $response;

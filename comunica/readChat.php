<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
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

/*
 * YOUR CODE HERE
 */

/*
 * Check that this script was called with the right arguments.
 * If not, stop script execution and report an error to the caller.
 */
if (!isset($_POST['chatroom']) || !isset($_POST['lastMsgId'])) {
    exitWithJSONError(translateFN('Errore: parametri passati allo script PHP non corretti'));
}

$id_chatroom  = (isset($_POST['chatroom']) && intval($_POST['chatroom']) > 0) ? (int) $_POST['chatroom'] : null;
$lastMsgId = (isset($_POST['lastMsgId']) && intval($_POST['lastMsgId']) > -1) ? (int) $_POST['lastMsgId'] : null;
$ownerId = (isset($_POST['ownerId']) && intval($_POST['ownerId']) > 0) ? (int) $_POST['ownerId'] : null;
$studentId = (isset($_POST['studentId']) && intval($_POST['studentId']) > 0) ? (int) $_POST['studentId'] : null;

/*
 * Get Chatroom
 */
$chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
if (AMADataHandler::isError($chatroomObj)) {
    exitWithJSONError(translateFN('Errore nella creazione della chatroom'));
}

/*
 * Get chatroom info
 */
$chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
if (AMADataHandler::isError($chatroomObj)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dei dati sulla chatroom"));
}

if (is_array($chatroom_ha)) {
    // get the topic of the chatroom
    $chat_topic = $chatroom_ha['argomento_chat'];
} else {
    /*
     * Gestire uscita dalla chat
     */
    // close_chat template will be loaded
    $self = 'close_chat';
    // motivate the exit of the user
    $exit_reason = EXIT_REASON_NOT_EXIST;
    // open close_chat.php
}
/*
 * Get user status in the current chatroom
 */

$user_status = $chatroomObj->getUserStatusFN($sess_id_user, $id_chatroom);
if (AMADataHandler::isError($user_status)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dello stato dell'utente nella chatroom"));
}

/*
 * User has been banned from the chatroom
 */
if ($user_status == STATUS_BAN) {
    exitWithJSONError(translateFN('Sei stato bannato dalla chatroom'), 2);
}

/*
 * User has been kicked from the chatroom
 */
if ($user_status == STATUS_EXIT) {
    exitWithJSONError(translateFN("Sei stato cacciato dalla chatroom"), 2);
}

/*
 * lettura messaggi
 */

$mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
if (AMADataHandler::isError($mh)) {
    exitWithJSONError(translateFN("Errore nella creazione dell'oggetto MessageHandler"));
}

// set the sorting filter, we will get the list of the messages sorted by this variable
if (!isset($sort_field)) {
    $sort_field = "data_ora asc";
} elseif ($sort_field == "data_ora") {
    $sort_field .= " asc";
}

session_write_close();

$msgs_pub_ha = [];
/*
 * vito, uso $fields_list per indicare quali messaggi ottenere: quelli inviati
 * a partire dall'avvio della chat o quelli inviati a partire dall'ultima lettura
 */
// TODO: usare findMessages al posto di getMessages e passare la clausola corretta
$fields_list = $lastMsgId;
$msgs_pub_ha = $mh->getMessages($sess_id_user, ADA_MSG_CHAT, $fields_list, $sort_field);
if (AMADataHandler::isError($msgs_pub_ha)) {
    exitWithJSONError(translateFN('Errore nella lettura dei messaggi dal DB'));
}

$msgs_priv_ha = [];
$msgs_priv_ha = $mh->getMessages($sess_id_user, ADA_MSG_PRV_CHAT, $fields_list, $sort_field);
if (AMADataHandler::isError($msgs_priv_ha)) {
    exitWithJSONError(translateFN('Errore nella lettura dei messaggi dal DB'));
}

//merge the two arrays without losing their keys.
//In this case each key is the id of one message that is unique

/*
 * NUOVO CODICE MESSAGGI CHAT
 */
$current_public_message  = 0;
$current_private_message = 0;
$total_private_messages  = count($msgs_priv_ha);
$total_public_messages   = count($msgs_pub_ha);

$msgs_number = $total_public_messages + $total_private_messages;

$messages_display_Ha = [];

//$json_data = "[";

for ($i = 0; $i < $msgs_number; $i++) {
    if (
        ($current_public_message < $total_public_messages)
        && ($current_private_message < $total_private_messages)
    ) {
        if (
            $msgs_pub_ha[$current_public_message]['id_messaggio']
            < $msgs_priv_ha[$current_private_message]['id_messaggio']
        ) {
            //            $json_data .= thisChatMessageToJSON($msg_pub_ha[$current_public_message]);
            $messages_display_Ha[$i] = $msgs_pub_ha[$current_public_message];
            $current_public_message++;
        } else {
            //            $json_data .= thisChatMessageToJSON($msgs_priv_ha[$current_private_message]);
            $messages_display_Ha[$i] = $msgs_priv_ha[$current_private_message];
            $current_private_message++;
        }
    } elseif ($current_public_message < $total_public_messages) {
        //        $json_data .= thisChatMessageToJSON($msg_pub_ha[$current_public_message]);
        $messages_display_Ha[$i] = $msgs_pub_ha[$current_public_message];
        $current_public_message++;
    } elseif ($current_private_message < $total_private_messages) {
        //        $json_data .= thisChatMessageToJSON($msgs_priv_ha[$current_private_message]);
        $messages_display_Ha[$i] = $msgs_priv_ha[$current_private_message];
        $current_private_message++;
    }
}

$json_data = array_map(fn ($message) => [
'id' => $message['id_messaggio'],
'tipo' => $message['tipo'],
'time' => Utilities::ts2tmFN($message['data_ora']),
'sender' => $message['nome'] . ' ' . $message['cognome'],
'text' => stripslashes($message['testo']),
], $messages_display_Ha);

/*
 * fine di costruisce la stringa json contenente i messaggi ricevuti
 */

/*
 * invio della risposta JSON al simulatore
 */
$error  = 0;

//$response = '{"error" : '.$error.', "execution_time" : '.$total_time.'}';

header('Content-Type: application/json');
die(json_encode(['error' => $error, "data" => $json_data ]));

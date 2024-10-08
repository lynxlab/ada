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

/**
 * vito, 22 september 2008
 *
 * The original script has been modified in order to work in an AJAX-like
 * environment.
 * Now it performs only the actions needed to obtain:
 * 1. user status in the current chatroom
 * 2. the actions the user is allowed to perform in the current chatroom
 * 3. the list of the users in the current chatroom.
 *
 * Performing the user control action, which was previously a responsibility
 * for this script, is now demanded to controlChatAction.php.
 *
 * Data is returned back to the caller via JSON strings, not XML.
 */

/*
 * Check that this script was called with the right arguments.
 * If not, stop script execution and report an error to the caller.
 */
if (!isset($_POST['chatroom'])/*!isset($_GET['chatroom'])*/) {
    exitWithJSONError(translateFN("Errore: parametri passati allo script PHP non corretti"));
}

$id_chatroom = $_POST['chatroom'];//$_GET['chatroom'];
$json_data = [];

/*
 * Get an instance of the UserDataHandler class.
 */
//$udh = UserDataHandler::instance(MultiPort::getDSN($sess_selected_tester));
//if (AMADataHandler::isError($udh))
//{
//    exitWithJSONError(translateFN("Errore nella creazione dell'oggetto UserDataHandler"));
//}

/*
 * Get an instance of the MessageHandler class.
 */
//$mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
$mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);

if (AMADataHandler::isError($mh)) {
    exitWithJSONError(translateFN("Errore nella creazione dell'oggetto MessageHandler"));
}

/*
 * Get chatroom data
 */
$chatroomObj = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
if (AMADataHandler::isError($chatroomObj)) {
    exitWithJSONError(translateFN("Errore nella creazione della chatroom"));
}

$chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
if (AMADataHandler::isError($chatroom_ha)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dei dati sulla chatroom"));
}

$actual_time = time();
// time that the chat will be closed
$expiration_time = $chatroom_ha['tempo_fine'];
$chat_type       = $chatroom_ha['tipo_chat'];

// gets an array containing all the ids of the users present in the chat
/* 21 settembre 2008
 * modifica di vito in ChatDataHandler->list_users_chatroom(richiamato da $chatroomObj->listUsersChatroomFN)
 * ora restituisce id utente e username
 */
$userslist_ar = $chatroomObj->listUsersChatroomFN($id_chatroom);

$invited_userslist_ar = $chatroomObj->listUsersInvitedToChatroomFN($id_chatroom);

if (AMADataHandler::isError($userslist_ar)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dei dati relativi agli utenti presenti nella chatroom"));
}

if (AMADataHandler::isError($invited_userslist_ar)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dei dati relativi agli utenti invitati alla chatroom"));
}

/*
 *
 */
if ($expiration_time != 0) {
    // calculate the time that remains before the chatroom expires
    if (($expiration_time - $actual_time) <= TIME_BEFORE_EXPIRATION) {
        // count the users present into the chatroom
        $how_many_users = count($userslist_ar);
        if (($chat_type == CLASS_CHAT) and ($how_many_users >= USERS_REQUESTED_TO_EXTEND)) {
            // get and set chatroom details
            $chatroom_new['end_time'] = $expiration_time + TIME_TO_EXTEND;
            $title = $chatroom_ha['titolo_chat'];
            $chatroom_new['chat_title'] = addslashes($title);
            $topic = $chatroom_ha['argomento_chat'];
            $chatroom_new['chat_topic'] = addslashes($topic);
            $welcome_msg = $chatroom_ha['msg_benvenuto'];
            $chatroom_new['welcome_msg'] = addslashes($welcome_msg);

            //extend the time of this chat session
            $result = $chatroomObj->setChatroomFN($id_chatroom, $chatroom_new);
            if (AMADataHandler::isError($result)) {
                exitWithJSONError(translateFN("Errore nel tentativo di estendere la durata della chatroom"));
            }
        }
    }
}

/*
 *
 */
$still_running = $chatroomObj->isChatroomNotExpiredFN($id_chatroom);
if (AMADataHandler::isError($still_running)) {
    exitWithJSONError(translateFN("Errore nella verifica della validit&agrave; della chatroom"));
}

// verify if the closing chatroom time has arrived
if (!$still_running) {
    // close_chat template will loaded
    $self = 'close_chat';
    // motivate the exit of the user
    $exit_reason = EXIT_REASON_EXPIRED;
    // open close_chat.php
    //  $onload_func =  "top.location.href='close_chat.php?exit_reason=$exit_reason&id_chatroom=$id_chatroom&id_user=$sess_id_user'";
    $data =  ['chat_text' => $chat_text];
}

/*
 *
 */
$user_status = $chatroomObj->getUserStatusFN($sess_id_user, $id_chatroom);
if (AMADataHandler::isError($user_status)) {
    exitWithJSONError(translateFN("Errore nell'ottenimento dello stato dell'utente all'interno della chatroom"));
}

// ******************************************************
// building the available options for the user
// ******************************************************

// we convert in text the status of the user in order to print it on the screen
switch ($user_status) {
    case STATUS_OPERATOR:
        $json_data['user_status'] = translateFN("Moderatore");
        break;
    case STATUS_ACTIVE:
        $json_data['user_status'] = translateFN("Attivo");
        break;
    case STATUS_MUTE:
        $json_data['user_status'] = translateFN("Senza Voce");
        break;
    case STATUS_BAN:
        $json_data['user_status'] = translateFN("Accesso Negato");
        break;
    default:
}//end switch

/*
 *
 */
//if (($user_status == STATUS_ACTIVE)or($user_status == STATUS_OPERATOR))
//{
//    /*
//     * Common options for simple user and operators
//     */
//    $json_options_data  = '[';
//    $json_options_data .= '{"value":'.ADA_CHAT_MOOD_TYPE_ASK_FOR_ATTENTION.',"text":"'.translateFN("Chiedi Ascolto").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_MOOD_TYPE_APPLAUSE.',"text":"'.translateFN("Applaudi").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_MOOD_TYPE_DISAGREE.',"text":"'.translateFN("Dissenti").'"}';
//}
//if ($user_status == STATUS_OPERATOR)
//{
//    /*
//     * In case of operator more functions are available
//     */
//    $json_options_data .= ',{"value":'.ADA_CHAT_OPERATOR_ACTION_SET_OPERATOR.',"text":"'.translateFN("Rendi Moderatore").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_UNSET_OPERATOR.',"text":"'.translateFN("Togli Moderatore").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_MUTE_USER.',"text":"'.translateFN("Togli Voce").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_UNMUTE_USER.',"text":"'.translateFN("Dai Voce").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_BAN_USER.',"text":"'.translateFN("Nega Accesso").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_UNBAN_USER.',"text":"'.translateFN("Dai Accesso").'"},';
//    $json_options_data .= '{"value":'.ADA_CHAT_OPERATOR_ACTION_KICK_USER.',"text":"'.translateFN("Espelli").'"}';
//    // edit currenr chatroom properties
//    $edit_chatroom = "<a href=../comunica/edit_chat.php?$session_id_par"."&id_chatroom=$id_chatroom target='_blank' border='0'>Modifica Chatroom</a>";
//}
//$json_options_data .= ']';

// $userslist_ar has been retrieved at the start of the script


// proceed only if list it is not empty, get the list of the users into the chatroom
if (is_array($userslist_ar)) {
    /*
     * Create the json for the users list
     */
    $json_data['users_list'] = array_map(
        fn ($user_data) => [
            'id' => $user_data['id_utente'],
            'username' => $user_data['username'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
        ],
        $userslist_ar
    );
} else {
    $json_data['users_list'] = [];
    // Errors on $userslist_ar should have been catched on line 138.
    //  $errObj = new ADAError(translateFN("Errore durante la lettura del DataBase"),translateFN("Impossibile proseguire."));
}


//if (is_array($invited_userslist_ar))
//{
//    /*
//     * Create the json for the users list
//     */
//    $json_invited_users_list = '[';
//
//    while (count($invited_userslist_ar) > 1 )
//    {
//        $user_data        = array_shift($invited_userslist_ar);
//        $json_invited_users_list .= '{"id":"'.$user_data['id_utente'].'","username":"'.$user_data['username'].'"},';
//    }
//    if (count($invited_userslist_ar) == 1)
//    {
//        $user_data        = array_shift($invited_userslist_ar);
//        $json_invited_users_list .= '{"id":"'.$user_data['id_utente'].'","username":"'.$user_data['username'].'"}';
//    }
//    $json_invited_users_list .= ']';
//
//}// end of users list
//else
//{
//    $json_invited_users_list = '[]';
//    // Errors on $userslist_ar should have been catched on line 138.
//    //  $errObj = new ADAError(translateFN("Errore durante la lettura del DataBase"),translateFN("Impossibile proseguire."));
//}


/*
 * If the user is an operator we get also the banned users list
 */
//if ($user_status == STATUS_OPERATOR)
//{
//    // get's an array containing all the ids of the banned users into that chatroom
//    // vito, 21 settembre 2008, modifica a ChatDataHandler->list_banned_users: ottiene anche lo username , oltre allo user id
//    $bannedusers_ar = $chatroomObj->listBannedUsersChatroomFN($id_chatroom);
//    if (AMADataHandler::isError($bannedusers_ar))
//    {
//        exitWithJSONError(translateFN("Errore nell'ottenimento della lista degli utenti bannati"));
//    }
//
//  // we will tranform ids in usernames only in the case that the array it is not empty
//  $json_banned_users_list = '[';
//    if (is_array($bannedusers_ar))
//  {
//      $users_names_ha = array();
//
//      /*
//       * Create the json for the banned users list
//         */
//        while (count($bannedusers_ar) > 1 )
//        {
//            $user_data               = array_shift($bannedusers_ar);
//            $json_banned_users_list .= '{"username":"'.$user_data['username'].'"},';
//        }
//        if (count($bannedusers_ar) == 1)
//        {
//            $user_data               = array_shift($bannedusers_ar);
//            $json_banned_users_list .= '{"username":"'.$user_data['username'].'"}';
//        }
//  }//end of if($bannedusers_ar)
//    $json_banned_users_list .= ']';
//}// end of banned list

// write the time of the event into the utente_chatroom table
$last_event = $chatroomObj->setLastEventTimeFN($sess_id_user, $id_chatroom);
if (isset($bannedusers_ar) && AMADataHandler::isError($bannedusers_ar)) {
    exitWithJSONError(translateFN("Errore nell'aggiornamento del tempo relativo all'utlimo evento"));
}

/*
 * Sending back data to the caller.
 */
$error  = 0;

/*
 * Get UI labels in the user's language.
 */
$json_data['users_count'] = is_array($json_data['users_list']) ? count($json_data['users_list']) : 0;
$json_data['user_status_label'] = translateFN("Stato Utente");
$json_data['options_list_label'] = translateFN("Opzioni Utente");
$json_data['users_list_label'] = $json_data['users_count'] . ' ' . translateFN(sprintf("utent%s nella Chatroom", $json_data['users_count'] == 1 ? 'e' : 'i'));

/*
 * Li passiamo vuoit perche' in ADA non dovrebbe servire questo tipo di
 * informazioni
 */
$json_data['json_banned_users_list']  = []; // should be populated by $json_banned_users_list
$json_data['json_invited_users_list'] = []; // should be populated by $json_invited_users_list
$json_data['options_list'] = []; // should be populated by $json_options_data
$json_data['error'] = $error;

header('Content-Type: application/json');
die(json_encode($json_data));

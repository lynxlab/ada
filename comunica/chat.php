<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\getTimezoneOffset;
use function Lynxlab\ADA\Main\Utilities\ts2tmFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * vito, 24/09/2008
 *
 * The original script has been modified in order to work in an AJAX-like environment.
 * This script is now responsible for:
 * 1. user check
 * 2. course check
 * 3. obtaining page layout
 * 4. obtaining a chatroom
 * 5. do some checking on the obtained chatroom
 * 6. checking the user status in the obtained chatroom
 *
 * The javascript file used to handle AJAX interactions with ADA chat PHP scripts
 * is ada_chat_includes.js, which is included by adaChat.js.
 *
 * The PHP scripts used to implement the AJAX chat are:
 * 1. controlChat.php       - called to obtain informations about the users in the chatroom
 * 2. controlChatAction.php - called to execute a control action selected by the user
 * 3. readChat.php          - called to read the new messages in the chat
 * 4. sendChatMessage.php   - called to send a message in the chatroom
 * 5. topChat.php           - called to obtain the top chat
 */
/**
 * Base config file
 */
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
$neededObjAr = [
  AMA_TYPE_STUDENT => ['chatroom','layout'],
  AMA_TYPE_AUTHOR => ['chatroom','layout'],
  AMA_TYPE_TUTOR => ['chatroom','layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = (isset($_REQUEST['iframe']) && intval($_REQUEST['iframe']) === 1) ? 'chat_iframe' : whoami();

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
 *   session variables when coming from a course node.
 *
 *	 [sess_id_user]
 *   [sess_id_user_type]
 *   [sess_user_language]
 *   [sess_id_course]
 *   [sess_id_node]
 *   [sess_id_course_instance]
 */
if ($exit_reason == NO_EXIT_REASON) {
    $chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
    // CONTROLLARE EVENTUALE ERRORE

    if (is_array($chatroom_ha)) {
        // get the id of the owner of the chatroom
        $id_owner = $chatroom_ha['id_proprietario_chat'];
        // check if the current user is the owner of the room
        if ($id_owner == $sess_id_user || $userObj->getType() == AMA_TYPE_TUTOR) {
            // gives him moderator access
            $operator = $chatroomObj->setUserStatusFN($sess_id_user, $sess_id_user, $id_chatroom, ACTION_SET_OPERATOR);
            // restituire l'errore via JSON
        }
        $started = $chatroomObj->isChatroomStartedFN($id_chatroom);
        // restituire l'errore via JSON

        $still_running = $chatroomObj->isChatroomNotExpiredFN($id_chatroom);
        // restituire l'errore via JSON

        $status = $chatroomObj->getUserStatusFN($sess_id_user, $id_chatroom);
        // restituire l'errore via JSON

        $complete = $chatroomObj->isChatroomFullFN($id_chatroom);

        $exit_reason = NO_EXIT_REASON;

        if (($status != STATUS_BAN) and ($chatroomObj->error == 1)) {
            $exit_reason = EXIT_REASON_WRONG_ROOM;
        }
        // user is banned from chatroom
        elseif ($status == STATUS_BAN) {
            $exit_reason = EXIT_REASON_BANNED;
        }
        // chatroom session not started yet
        elseif (!$started) {
            $exit_reason = EXIT_REASON_NOT_STARTED;
        }
        // chatroom session terminated
        elseif (!$still_running) {
            $exit_reason = EXIT_REASON_EXPIRED;
        }
        // chatroom session terminated
        elseif ($complete) {
            $exit_reason = EXIT_REASON_FULL_ROOM;
        }
        // everything is ok, enter into the chat
        else {
            //$mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
            $mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
            // send a message to announce the entrance of the user
            $message_ha['tipo']     = ADA_MSG_CHAT;
            $message_ha['data_ora'] = "now";
            $message_ha['mittente'] = "admin";
            $message_ha['id_group'] = $id_chatroom;
            $message_ha['testo']    = "<span class=user_name>$user_name</span> " . translateFN("&egrave; entrato nella stanza");

            $result = $mh->sendMessage($message_ha);
            // GESTIONE ERRORE
        }
    }
}

if ($exit_reason != NO_EXIT_REASON) {
    $chat = new CText('');
    $offset = 0;
    if ($_SESSION['sess_selected_tester'] === null) {
        $tester_TimeZone = SERVER_TIMEZONE;
    } else {
        $tester_TimeZone = MultiPort::getTesterTimeZone($_SESSION['sess_selected_tester']);
        $offset = getTimezoneOffset($tester_TimeZone, SERVER_TIMEZONE);
    }
    $current_time = ts2tmFN(time() + $offset);

    $close_page_message = addslashes(translateFN("You don't have a chat appointment at this time.")) . " ($current_time)";
    $optionsAr = ['onload_func' => "close_page('$close_page_message');"];
} else {
    //$event_token = $chatroomObj->get_event_token();
    // GIORGIO 20200317: have no clue why this event_token  is commented out!
    $event_token = null;
    $request_arguments['chatroomId'] = intval($id_chatroom);
    // pass these parameters that may be used by readChat.php to filter loaded messages
    $request_arguments['ownerId'] = intval($id_owner);
    $request_arguments['studentId'] = intval($userObj->getId());
    $request_arguments['isIframe'] = isset($_GET['iframe']) && intval($_GET['iframe']) === 1;
    $chat = CommunicationModuleHtmlLib::getChat(json_encode($request_arguments), $userObj, $event_token);
    $optionsAr = ['onload_func' => 'startChat();'];
}
/*
 * Create here the close link.
 */
$exit_chat = CDOMElement::create('a');
$exit_chat->addChild(new CText(translateFN('Chiudi')));
if ($userObj instanceof ADAPractitioner) {
    // pass 1 to redirect the practitioner to the eguidance session evaluation form
    if (!empty($event_token)) {
        $_SESSION['sess_event_token'] = $event_token;
        $onclick = "exitChat(1,'event_token=$event_token');";
    } else {
        $onclick = 'exitChat(0,0);';
    }
    $exit_chat->setAttribute('onclick', $onclick);
} else {
    // pass 0 to close the chat window
    $onclick = 'exitChat(0,0);';
    $exit_chat->setAttribute('onclick', $onclick);
}

/*
* Last access link
*/

if (isset($_SESSION['sess_id_course_instance'])) {
    $last_access = $userObj->getLastAccessFN(($_SESSION['sess_id_course_instance']), "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
} else {
    $last_access = $userObj->getLastAccessFN(null, "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
}

if ($last_access == '' || is_null($last_access)) {
    $last_access = '-';
}

$content_dataAr = [
  'chat'       => $chat->getHtml(),
  'exit_chat'  => $exit_chat->getHtml(),
  'user_name'  => $user_name,
  'user_type'  => $user_type,
  'user_level' => $user_level,
  'onclick'    => $onclick,
  'last_visit' => $last_access,
  'status'     => translateFN('Chatroom'),
];


ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

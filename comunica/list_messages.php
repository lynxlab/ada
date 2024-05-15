<?php

use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;

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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT      => ['layout'],
  AMA_TYPE_TUTOR        => ['layout'],
  AMA_TYPE_SWITCHER     => ['layout'],
];


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
 * YOUR CODE HERE
 */

$user_id = $userObj->getId();

if (!isset($op)) {
    $op = 'default';
}

if (isset($status)) {
    $status = urldecode($status);
}

$title = translateFN('ADA - Lista messaggi');


// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users

$online_users_listing_mode = 2;
if (isset($sess_id_course_instance) && !empty($sess_id_course_instance)) {
    $online_users = ADALoggableUser::getOnlineUsersFN($sess_id_course_instance, $online_users_listing_mode);
} else {
    $online_users = '';
}
// CHAT, BANNER etc


// Has the form been posted?

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // build array of messages ids to be set as read
    if (isset($form['read']) && count($form['read'])) {
        $to_set_as_read_ar = $form['read'];
    } else {
        $to_set_as_read_ar = [];
    }

    // set all read messages
    //$res = $mh->setMessages($user_id, $to_set_as_read_ar, 'R');
    $res = MultiPort::markUserMessagesAsRead($userObj, $to_set_as_read_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore'));
    }

    // set all unread messages
    // first, get all the messages in the user's spool
    //$msgs_ha = $mh->getMessages($user_id, ADA_MSG_SIMPLE, array('read_timestamp'));
    $msgs_ha = MultiPort::getUserMessages($userObj);
    if (AMADataHandler::isError($msgs_ha)) {
        $errObj = new ADAError($msgs_ha, translateFN('Errore in lettura messaggi utente'));
    }

    // then fill the array of ids to set as unread

    $to_set_as_unread_ar = [];
    foreach ($msgs_ha as $pointer => $msgs_tester_Ar) {
        $id_tester_Ar = $common_dh->getTesterInfoFromPointer($pointer);
        if (AMADataHandler::isError($id_tester_Ar)) {
            $errObj = new ADAError($id_tester_Ar, translateFN('Errore'));
        } else {
            foreach ($msgs_tester_Ar as $msg_id => $msg_ar) {
                $msg_id_tester = $id_tester_Ar[0] . '_' . $msg_id;
                if (!in_array($msg_id_tester, $to_set_as_read_ar)) {
                    $to_set_as_unread_ar[] = $msg_id_tester;
                }
            }
        }
    }

    // last, invoke, the setMessages method
    //$res = $mh->setMessages($user_id, $to_set_as_unread_ar, 'N');
    $res = MultiPort::markUserMessagesAsUnread($userObj, $to_set_as_unread_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore'));
    }

    // build array of messages ids to be removed
    if (isset($form['del']) && count($form['del'])) {
        $to_remove_ar = $form['del'];
    } else {
        $to_remove_ar = [];
    }

    // manage messages removal
    //$res = $mh->removeMessages($user_id, $to_remove_ar);
    $res = MultiPort::removeUserMessages($userObj, $to_remove_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore durante la cancellazione dei messaggi'));
    }
}


// remove single message if requested
if (isset($del_msg_id) and !empty($del_msg_id)) {
    //$res = $mh->removeMessages($user_id, array($del_msg_id));
    $res = MultiPort::removeUserMessages($userObj, [$del_msg_id]);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore durante la cancellazione del messaggio'));
    }
}

// analyze the sorting info
if (!isset($sort_field)) {
    $sort_field = "data_ora desc";
} elseif ($sort_field == "data_ora") {
    $sort_field .= " desc";
} elseif ($sort_field == "titolo") {
    $sort_field .= " asc";
} else {
    $sort_field .= " asc, data_ora desc";
}

$testers_dataAr = MultiPort::getTestersPointersAndIds();
if (isset($_GET['messages']) && $_GET['messages'] == 'sent') {
    $dataAr   = MultiPort::getUserSentMessages($userObj);
    $messages = CommunicationModuleHtmlLib::getSentMessagesAsForm($dataAr, $testers_dataAr);
    $label   = translateFN('Messaggi inviati');
    $displayedMsgs = 'sent';
} else {
    $dataAr   = MultiPort::getUserMessages($userObj);

    $messages = CommunicationModuleHtmlLib::getReceivedMessagesAsForm($dataAr, $testers_dataAr);
    $label   = translateFN('Messaggi ricevuti');
    $displayedMsgs = 'received';
}

$node_title = ""; // empty
$menu_03 = "";

// FIXME: verificare se ha senso in ADA
if (!isset($course_title)) {
    $course_title = "";
} else {
    $course_title = '<a href="../browsing/main_index.php">' . $course_title . '</a>';
}

if (!isset($status)) {
    $status = "";
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
  'course_title' => $course_title,
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'user_level'   => $user_level,
  'messages'     => $messages->getHtml() . '<br/>',
  'status'       => $status,
  'chat_users'   => $online_users,
  'label'        => $label,
  'last_visit' => $last_access,
];

/**
 * @author giorgio 18/apr/2014 12:55:21
 *
 * added jquery, datatables and initDoc function call
 */
$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_DATATABLE,
        JQUERY_DATATABLE_DATE,
        JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        JQUERY_DATATABLE_CSS,
];

if (isset($options_Ar) && is_array($options_Ar) && isset($options_Ar['onload_func'])) {
    $options_Ar['onload_func'] = 'initDoc(\'' . $displayedMsgs . '\'); ' . $options_Ar['onload_func'];
} else {
    $options_Ar['onload_func'] = 'initDoc(\'' . $displayedMsgs . '\');';
}

ARE::render($layout_dataAr, $content_dataAr, null, $options_Ar);

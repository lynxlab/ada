<?php

use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\todayDateFN;
use function Lynxlab\ADA\Main\Utilities\todayTimeFN;
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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT => ['layout'],
  AMA_TYPE_SWITCHER => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
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
if (!isset($op)) {
    $op = 'default';
}

$title = translateFN('ADA - Lista eventi');

// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users

$online_users_listing_mode = 2;
$online_users = ADALoggableUser::getOnlineUsersFN($sess_id_course_instance, $online_users_listing_mode);

// CHAT, BANNER etc


// default status:
if ((empty($status)) or (!isset($status))) {
    $status = translateFN('Lista appuntamenti del') . ' ' . todayDateFN() . ' - ' . todayTimeFN();
} else {
    $status = urldecode($status);
}


// Has the form been posted?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // build array of messages ids to be set as read
    if (
        isset($form) and in_array('read', array_keys($form))
        and (count($form['read']))
    ) {
        $to_set_as_read_ar = $form['read'];
    } else {
        $to_set_as_read_ar = [];
    }

    // set all read events

    //$res = $mh->setMessages($sess_id_user, $to_set_as_read_ar, 'R');
    $res = MultiPort::markUserAppointmentsAsRead($userObj, $to_set_as_read_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore'));
    }
    // set all unread events

    // first, get all the events in the user's spool
    //$msgs_ha = $mh->getMessages($sess_id_user, ADA_MSG_AGENDA, array("read_timestamp"));
    $msgs_ha = MultiPort::getUserAgenda($userObj);
    if (AMADataHandler::isError($msgs_ha)) {
        $errObj = new ADAError($msgs_ha, translateFN('Errore in lettura appuntamenti'));
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
                    $to_set_as_unread_ar[] = $msg_id;
                }
            }
        }
    }

    // last, invoke, the set_events method
    //$res = $mh->setMessages($sess_id_user, $to_set_as_unread_ar, 'N');
    $res = MultiPort::markUserAppointmentsAsUnread($userObj, $to_set_as_unread_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore'));
    }

    // build array of messages ids to be removed
    if (
        isset($form) and in_array('del', array_keys($form))
        and (count($form['del']))
    ) {
        $to_remove_ar = $form['del'];
    } else {
        $to_remove_ar = [];
    }
    // manage events removal
    //$mh->removeMessages($sess_id_user, $to_remove_ar);
    $res = MultiPort::removeUserAppointments($userObj, $to_remove_ar);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore durante la cancellazione dei messaggi'));
    } else {
        $status = translateFN('Cancellazione eseguita');
    }
} // end if POST


// remove single event if requested

if (isset($del_msg_id) and !empty($del_msg_id)) {
    $res = MultiPort::removeUserAppointments($userObj, [$del_msg_id]);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError($res, translateFN('Errore durante la cancellazione del messaggio'));
    } else {
        $status = translateFN('Cancellazione eseguita');
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


$dataAr         = MultiPort::getUserAgenda($userObj);
$testers_dataAr = MultiPort::getTestersPointersAndIds();
$messages       = CommunicationModuleHtmlLib::getAgendaAsForm($dataAr, $testers_dataAr);
$node_title = ""; // empty

if (!isset($course_title)) {
    $course_title = "";
} else {
    $course_title = '<a href="../browsing/main_index.php">' . $course_title . '</a>';
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
  'go_back'      => $go_back ?? '',
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'user_level'   => $user_level,
  'last_visit' => $last_access,
  'messages'     => $messages->getHtml(),
  'status'       => $status,
  'chat_users'   => $online_users,
];

ARE::render($layout_dataAr, $content_dataAr);

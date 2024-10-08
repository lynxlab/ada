<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'], // Access only invitation chat
    AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';

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

$self =  "list_chatrooms"; //Utilities::whoami();
$log_type = "db";
/* 1. getting data about user

*/
$ymdhms = Utilities::todayDateFN();


/*
 * vito, 16 mar 2009, when called by tutor.php, receives the course instance id
 * passed by GET in $id_instance.
 */
if (!isset($sess_id_course_instance) && isset($id_instance)) {
    $sess_id_course_instance = $id_instance;
}

$title = translateFN("ADA - Chat Log");

if (!isset($id_chatroom)) {
    // if id_chatroom is not set, try the id_room $_GET parameter
    $id_chatroom = DataValidator::checkInputValues('id_room', 'Integer', INPUT_GET, null);
}
$chatroomObj = new ChatRoom($id_chatroom);
if (is_object($chatroomObj) && !AMADataHandler::isError($chatroomObj)) {
    //get the array with all the current info of the chatoorm
    $id_course_instance = $chatroomObj::$id_course_instance;
    $id_course = $dh->getCourseIdForCourseInstance($id_course_instance);
    // ******************************************************
    // get  course object
    $courseObj = DBRead::readCourse($id_course);
    if ((is_object($courseObj)) && (!AMADB::isError($courseObj))) {
        $course_title = $courseObj->titolo; //title
        $id_toc = $courseObj->id_nodo_toc;  //id_toc_node
    }
}


if (empty($media_path)) {
    $media_path = MEDIA_PATH_DEFAULT;
}


if (isset($id_chatroom)) {
    $menuOptions['id_room'] = $id_chatroom;
}
if (isset($id_course)) {
    $menuOptions['id_course'] = $id_course;
}
// $op
if (!isset($op)) {
    $op = null;
}
switch ($op) {
    case 'rooms':
    case '':
        switch ($log_type) {
            case 'file':
                // versione che legge da file

                $chat_log_file =  "$root_dir/chat/chat/log/chat_" . $stanza . ".log";

                if (file_exists($chat_log_file) == 1) {
                    $chat_dataAr = [];
                    $chat_logAr = file($chat_log_file);
                    $usersHa = [];
                    $chat_msg = 0;
                    foreach ($chat_logAr as $chat_row_string) {
                        $chat_rowAr = explode("|", $chat_row_string);
                        $date =  $chat_rowAr[0];
                        $user =  $chat_rowAr[1];
                        $message =  $chat_rowAr[2];
                        $chat_msg++;
                        $row = [
                            translateFN('Data e ora') => Utilities::ts2dFN($date) . " " . Utilities::ts2tmFN($date),
                            translateFN('Utente') => $user,
                            translateFN('Messaggio') => strip_tags($message),
                            ];
                        array_push($chat_dataAr, $row);
                        if (in_array($user, array_keys($usersHa))) {
                            $n = $usersHa[$user];
                            $usersHa[$user] = $n + 1;
                            // echo  $user.":".$usersHa[$user]."<br>";
                        } else {
                            $usersHa[$user] = 1;
                        }
                    }

                    $user_chat_report = translateFN("Totale messaggi:") . " " . $chat_msg . "<br />";
                    $user_chat_report .= translateFN("Ultimo messaggio:") . " " . Utilities::ts2dFN($date) . " " . Utilities::ts2tmFN($date) . "<br />";
                    $user_chat_report .= translateFN("Utenti / messaggi:") . "<br /><br />";

                    foreach ($usersHa as $k => $v) {
                        $user_chat_report .= "$k: $v<br/>\n";
                    }
                    $tObj = new Table();
                    $tObj->initTable('0', 'right', '1', '0', '90%', '', '', '', '', '1', '0');
                    // Syntax: $border,$align,$cellspacing,$cellpadding,$width,$col1, $bcol1,$col2, $bcol2
                    $caption = translateFN("Resoconto della chat di classe");
                    $summary = sprintf(translateFN("Chat fino al %s"), $ymdhms);
                    $tObj->setTable($chat_dataAr, $caption, $summary);
                    $tabled_chat_dataHa = $tObj->getTable();
                    $menuOptions['id_instance'] = $sess_id_course_instance;
                    $menuOptions['id_course'] = $id_course;
                    $menuOptions['days'] = $days;
                    $menuOptions['id_chatroom'] = $sess_id_course_instance;
                } else {
                }
                $tabled_chat_dataHa = translateFN("Nessuna chat disponibile.");
                break;
            case 'db':
                $chat_report = "";

                if (!isset($id_chatroom)) { // ???
                    if (isset($id_instance)) {
                        $id_chatroom = $id_instance;
                    } elseif (isset($sess_id_course_instance)) {
                        $id_chatroom = $sess_id_course_instance;
                    }
                }

                $mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
                if (!isset($sess_user_id)) {
                    $sess_user_id = null;
                }
                $chat_data = $mh->findChatMessages($sess_user_id, ADA_MSG_CHAT, $id_chatroom, $fields_list = "", $clause = "", $ordering = "");
                if (is_array($chat_data)) {
                    $chat_dataAr = [];
                    $chat_data_simpleAr = [];
                    $c = 0;
                    $tbody_data = [];
                    foreach ($chat_data as $chat_msgAr) {
                        if (is_numeric($chat_msgAr[0])) {
                            $sender_dataHa = $dh->getUserInfo($chat_msgAr[0]);

                            $user = $sender_dataHa['nome'] . ' ' . $sender_dataHa['cognome'];
                            $message = $chat_msgAr[1];
                            $data_ora = Utilities::ts2dFN($chat_msgAr[2]) . " " . Utilities::ts2tmFN($chat_msgAr[2]);
                            $tbody_data[] = [
                                $data_ora,
                                $user,
                                strip_tags($message),
                            ];
                            $chat_report .= "$data_ora $user: $message<br/>\n";
                            $c++;
                        }
                    }
                    $user_chat_report = translateFN("Totale messaggi:") . " " . $c . "<br />";
                    if (isset($data_ora) && strlen($data_ora) > 0) {
                        $user_chat_report .= translateFN("Ultimo messaggio:") . " " . $data_ora . "<br />";
                    }
                    $user_chat_report .= translateFN("Utenti / messaggi:") . "<br /><br />";
                    $user_chat_report .= $chat_report;

                    $thead_data = [translateFN('Data e ora'), translateFN('Utente'), translateFN('Messaggio')];
                    $table_Mess = BaseHtmlLib::tableElement('class:sortable', $thead_data, $tbody_data);
                    $tabled_chat_dataHa = $table_Mess->getHtml();
                    $menuOptions['id_chatroom'] = $id_chatroom;
                    if (isset($days)) {
                        $menuOptions['days'] = $days;
                    }
                } else {
                    $tabled_chat_dataHa = translateFN("Nessuna chat disponibile.");
                }
                //      }
                break;
        }
        break;
    case 'index':
        $class_chatrooms_ar = [];
        $class_chatrooms = ChatRoom::getAllClassChatroomsFN($sess_id_course_instance);
        if (is_array($class_chatrooms)) {
            $class_chatrooms_ar[] = $class_chatrooms;
        }
        // get only the ids of the chatrooms
        foreach ($class_chatrooms_ar as $value) {
            foreach ($value as $id) {
                $chatrooms_class_ids_ar[] = $id;
            }
        }
        //initialize the array of the chatrooms to be displayed on the screen
        $list_chatrooms = "";
        // start the construction of the table contaning all the chatrooms
        foreach ($chatrooms_class_ids_ar as $id_chatroom) {
            // vito, 16 mar 2009
            if (!is_object($id_chatroom)) {
                $chatroomObj = new ChatRoom($id_chatroom);
                //get the array with all the current info of the chatoorm
                $chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
            }
            $list_chatrooms .= "<a href=\"report_chat.php?id_chatroom=$id_chatroom\">{$chatroom_ha['titolo_chat']}</a><br />";
        }
        $tabled_chat_dataHa  = $list_chatrooms;
        break;
    case 'export': //file as TXT :
    case 'exportTable': // XLS-like
        $chat_report = "";

        if (!isset($id_chatroom)) { // ???
            if (isset($id_instance)) {
                $id_chatroom = $id_instance;
            } elseif (isset($sess_id_course_instance)) {
                $id_chatroom = $sess_id_course_instance;
            }
        }
        $mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
        $chat_data = $mh->findChatMessages($sess_id_user, ADA_MSG_CHAT, $id_chatroom, $fields_list = "", $clause = "", $ordering = "");
        if (is_array($chat_data)) {
            $chat_dataAr = [];
            $c = 0;
            $tbody_data = [];
            $export_log = translateFN('Data e ora') . ';' . translateFN('Utente') . ';' . translateFN('Messaggio') . PHP_EOL;
            foreach ($chat_data as $chat_msgAr) {
                if (is_numeric($chat_msgAr[0])) {
                    $sender_dataHa = $dh->getUserInfo($chat_msgAr[0]);

                    $user = $sender_dataHa['nome'] . ' ' . $sender_dataHa['cognome'];
                    $message = $chat_msgAr[1];
                    $data_ora = Utilities::ts2dFN($chat_msgAr[2]) . " " . Utilities::ts2tmFN($chat_msgAr[2]);
                    /*
                     *
                    $row = array(
                    translateFN('Data e ora')=>$data_ora,
                    translateFN('Utente')=>$user,
                    translateFN('Messaggio')=>strip_tags($message)
                    );
                     */
                    $export_log .= $data_ora . ';' . $user . ';' . strip_tags($message) . PHP_EOL;
                    //array_push($chat_dataAr,$row);
                    $chat_report .= "$data_ora $user: $message<br/>PHP_EOL";
                    $c++;
                }
            }
            //}
            $user_chat_report = translateFN("Totale messaggi:") . " " . $c . "<br />";
            $user_chat_report .= translateFN("Ultimo messaggio:") . " " . $data_ora . "<br />";
            $user_chat_report .= translateFN("Utenti / messaggi:") . "<br /><br />";
            $user_chat_report .= $chat_report;
        } else {
            $export_log = translateFN("Nessuna chat disponibile.");
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");          // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");                          // HTTP/1.0
        header("Content-Type: text/plain");

        if ($op == 'export') {
            header("Content-Length: " . strlen($user_chat_report)); //?
            header("Content-Disposition: attachment; filename=chat_course_" . $id_course . "_class_" . $id_course_instance . ".html");
            echo stripslashes(str_replace('PHP_EOL', '<br/>', $user_chat_report));
        } else {
            //        header("Content-Type: application/vnd.ms-excel");
            //              header("Content-Length: ".filesize($chat_log_file)); //?
            $course_title .= ' - ' . translateFN('id classe') . ': ' . $id_course_instance;
            header("Content-Disposition: attachment; filename=class_" . $id_course_instance . '_chat_' . $id_chatroom . ".csv");
            echo $export_log;
            //              header ("Connection: close");
        }
        exit;
        break;
    default:
}
$help = translateFN("Questa &egrave; il report della chat di classe");


if (!isset($course_title)) {
    $course_title = "";
} else {
    $course_title .= ' - ' . translateFN('id classe') . ': ' . $id_course_instance;
}
if (!isset($status)) {
    $status = "";
}

$chatrooms_link = '<a href="' . HTTP_ROOT_DIR . '/comunica/list_chatrooms.php">' . translateFN('Lista chatrooms');

$content_dataAr = [
               'course_title' =>  translateFN('Report della chat') . ' - ' . translateFN('Corso') . ': ' . $course_title,
               'home' => isset($homepage) ? "<a href=\"$homepage\">home</a>" : '',
               'user_name' => $user_name,
               'user_type' => $user_type,
               'level' => $user_level,
               'help' => $help,
               'data' => $tabled_chat_dataHa,
               'status' => $status,
               'chatrooms' => $chatrooms_link,
               'chat_users' => $online_users ?? '',
               'messages' => $user_messages ?? '',
               'agenda' => $user_agenda ?? '',
              ];

ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions ?? null);

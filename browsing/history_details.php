<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Output\Html;
use Lynxlab\ADA\Main\User\ADAUser;

use function Lynxlab\ADA\Browsing\Functions\menuDetailsFN;
use function Lynxlab\ADA\Main\AMA\DBRead\readCourseFromDB;
use function Lynxlab\ADA\Main\AMA\DBRead\readLayoutFromDB;
use function Lynxlab\ADA\Main\AMA\DBRead\readUserFromDB;
use function Lynxlab\ADA\Main\ModuleInit\sessionControlFN;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

$ada_config_path = realpath(dirname(__FILE__) . '/..');

$history = "" ;
$self = "history";

sessionControlFN();

// ******************************************************
// Clear node and layout variable
$whatAR = [];
array_push($whatAR, 'node');
array_push($whatAR, 'layout');
array_push($whatAR, 'user');
array_push($whatAR, 'course');
/**
 * Giorgio
 * commented out 2024/03/26.
 * Function does not exist. Did de author meant to call clearDataFN ?
 */
// clear_data($whatAR);

$sess_id_course = $_SESSION['sess_id_course'];
$sess_id_course_instance = $_SESSION['sess_id_course_instance'];
$sess_id_user = $_SESSION['sess_id_user'];

//import_request_variables("gP","");
extract($_GET, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);
extract($_POST, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);

// ******************************************************
if ($sess_id_course) {
    // get object course
    $courseObj = readCourseFromDB($sess_id_course);
    if ($dh->isError($courseObj)) {
        $errObj = $courseObj;
        $msg =   $errObj->errorMessage();
        header("Location:$error_page?err_msg=$msg");
        exit;
    } else {
        $course_title = $courseObj->titolo; //title
        $id_toc = $courseObj->id_nodo_toc;  //id_toc_node
        $course_family = $courseObj->template_family;
    }
} else {
    $errObj = new ADA_error(translateFN("Corso non trovato"), translateFN("Impossibile proseguire."));
}




// ******************************************************
// get user object
$userObj = readUserFromDB($sess_id_user);
if ((is_object($userObj)) && (!AMA_dataHandler::isError($userObj))) {
    $id_profile = $userObj->tipo;
    $user_name =  $userObj->username;
    $user_type = $userObj->convertUserTypeFN($id_profile);
    $user_historyObj = $userObj->history;
    $user_level = $userObj->getStudentLevel($sess_id_user, $sess_id_course_instance);
    $user_family = $userObj->template_family;
} else {
    $errObj = new ADA_error(translateFN("Utente non trovato"), translateFN("Impossibile proseguire."));
}

// ******************************************************
// LAYOUT

if ((isset($family))  and (!empty($family))) { // from GET parameters
    $template_family = $family;
} elseif ((isset($node_family))  and (!empty($node_family))) { // from node definition
    $template_family = $node_family;
} elseif ((isset($course_family))  and (!empty($course_family))) { // from course definition
    $template_family = $course_family;
} elseif ((isset($user_family)) and (!empty($user_family))) { // from user's profile
    $template_family = $user_family;
} else {
    $template_family = ADA_TEMPLATE_FAMILY; // default template famliy
}

$layoutObj = readLayoutFromDB($id_profile, $template_family);
$layout_CSS = $layoutObj->CSS_filename;
$layout_template = $layoutObj->template;

// END LAYOUT
// *****************************************************



/* 1.
Retrieving node's data filtered by user'properties

*/

// lettura dei dati dal database
//$userObj->get_history_dataFN($id_course_instance) ;


if ($period != "all") {
    // Nodi visitati negli ultimi n giorni. Periodo in giorni.
    $history .= "<p>";
    //    $history .= translateFN("Nodi visitati negli ultimi $period giorni:") ;
    $history .= $user_historyObj->historyNodesListFilteredFN($period) ;
    $history .= "</p>";
} else {
    // Full history
    $history .= "<p>";
    //    $history .= translateFN("Cronologia completa:") ;
    $history .= $user_historyObj->getHistoryFN() ;
    $history .= "</p>";
}


// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users

$online_users_listing_mode = 2;
$online_users = ADAUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);


$last_visited_node_id = $userObj->getLastAccessFN($sess_id_course_instance, 'N');
if (!empty($last_visited_node_id)) {
    $last_node = $dh->getNodeInfo($last_visited_node_id);
    $last_visited_node_name = $last_node['name'];
    $last_node_visited = "<a href=view.php?id_node=$last_visited_node_id>" . translateFN("torna") . "</a>";
} else {
    $last_node_visited = "";
}

// Menu nodi visitati per periodo
$menu = menuDetailsFN();
$menu .= "<a href=history.php>" . translateFN("cronologia") . "</a><br>";
$menu .= $last_node_visited;


/* 2.
getting todate-information on user
MESSAGES adn EVENTS
*/

if (is_object($userObj)) {
    if (empty($userObj->error_msg)) {
        $user_messages = $userObj->getMessagesFN($sess_id_user);
        $user_agenda =  $userObj->getAgendaFN($sess_id_user);
    } else {
        $user_messages =  $userObj->error_msg;
        $user_agenda = translateFN("Nessun'informazione");
    }
} else {
    $user_messages = $userObj;
    $user_agenda = "";
}


// CHAT, BANNER etc

// $chat_link = "<a href=\"$http_root_dir/chat/chat/index.php3?L=italian&Ver=H&U=" . $user_name . "&PWD_Hash=d41d8cd98f00b204e9800998ecf8427e&R='$course_title'&T=2&D=5&N=20&Reload=NNResize&frameset=fol\" target=_blank>".translateFN("chat")."</a>";

// Costruzione del link per la chat.
// per la creazione della stanza prende solo la prima parola del corso (se piu' breve di 24 caratteri)
// e ci aggiunge l'id dell'istanza corso
$char_num = strpos(trim($course_title), " ");
if ($char_num > 24) {
    $char_num = 24;
}
$tmp = substr(trim($course_title), 0, $char_num);
$stanza = urlencode(trim($tmp) . "_" . $sess_id_course_instance);
$chat_link = "<a href=\"$http_root_dir/chat/chat/index.php3?L=italian&Ver=H&U=" . $user_name . "&PWD_Hash=d41de&R=$stanza&T=2&D=5&N=20&Reload=NNResize&frameset=fol\" target=_blank>" . translateFN("chat") . "</a>";

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


/* 3.
HTML page building
*/


$htmlObj = new Html($layout_template, $layout_CSS, $course_title, $node_title);

$node_data = [


                   'chat_link' => $chat_link,
                   'course_title' => '<a href="main_index.php">' . $course_title . '</a>',
                   'menu' => $menu,
                   'user_name' => $user_name,
                   'user_type' => $user_type,
                   'status' => $status,
                   'level' => $user_level,
                   'path' => $node_path,
                   'history' => $history,
                   'last_visit' => $last_access,
                   'messages' => $user_messages,
                   'agenda' => $user_agenda,
                   'chat_users' => $online_users,
                  ];


$htmlObj->fillinTemplateFN($node_data);

$imgpath = (dirname($layout_template));
$htmlObj-> resetImgSrcFN($imgpath);
$htmlObj->applyStyleFN();

/* 5.
sending all the stuff to the  browser
*/

$htmlObj->outputFN('page');

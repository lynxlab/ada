<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\AMA\DBRead\readUserFromDB;
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
$variableToClearAR = ['layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT,AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['node', 'layout', 'tutor', 'course', 'course_instance'],
    AMA_TYPE_TUTOR => ['node', 'layout', 'course', 'course_instance'],
    AMA_TYPE_AUTHOR => ['node', 'layout', 'course'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = whoami();

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
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
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
BrowsingHelper::init($neededObjAr);

if (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
}
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $self = 'defaultSelfInstruction';
} elseif ($userObj->tipo == AMA_TYPE_AUTHOR) {
    $self = whoami() . 'Author';
} else {
    $self = whoami();
}


$debug = 0;
$mylog_mode = 0; // default: only one file for user
//$log_extension = ".txt";
$log_extension = ".htm";

//$self =  whoami();  // = mylog

//$classi_dichiarate = get_declared_classes();
//mydebug(__LINE__,__FILE__,$classi_dichiarate);

$ymdhms = todayDateFN();

//import_request_variables("gP","");

// ******************************************************
$reg_enabled = true; // link to edit bookmarks
$log_enabled = true; // link to history
$mod_enabled = true; // link to modify nod/tes
$com_enabled = true;  // link to comunicate among users
// Get user object
$userObj = readUserFromDB($sess_id_user);
//print_r($userObj);
if ((is_object($userObj)) && (!AMADataHandler::isError($userObj))) {
    $id_profile = $userObj->tipo;
    switch ($id_profile) {
        case AMA_TYPE_TUTOR:
        case AMA_TYPE_STUDENT:
        case AMA_TYPE_AUTHOR:
            break;
        case AMA_TYPE_ADMIN:
            $homepage = $http_root_dir . "/browsing/student.php";
            $msg =   urlencode(translateFN("Ridirezionamento automatico"));
            header("Location: $homepage?err_msg=$msg");
            exit;
            break;
    }
    $user_type = $userObj->convertUserTypeFN($id_profile);
    $user_name =  $userObj->username;
    $user_family = $userObj->template_family;
} else {
    $errObj = new ADAError(translateFN("Utente non trovato"), translateFN("Impossibile proseguire."));
}

// set the  title:
$module_title = translateFN("Diario");

// building file name
// rootdir  + media path + author_id + filename
$public_dir = "/services/media/";
// a public access directory where log files can be written
// building file name

if (isset($sess_id_course) and  (!($sess_id_course == ""))) {
    // finding course's author
    $course_ha = $dh->getCourse($sess_id_course);
    if (AMADataHandler::isError($course_ha)) { // not enrolled yet?
        $msg = $course_ha->getMessage();
        header("Location: " . $http_root_dir . "/browsing/student.php?status=$msg");
    }
    // look for the author, starting from author's id
    $author_id = $course_ha['id_autore'];
    if ($mylog_mode == 1) {
        // a log file for every instance of course in which user is enrolled in:
        // id_course_instance + user_id
        $name_tmp = 'log_' . $sess_id_course_instance . "_" . $sess_id_user . $log_extension;
    } else { // default
        // only 1 log file for user:
        $name_tmp = 'log_' . $sess_id_user . $log_extension;
    }

    $logfile = $root_dir . "/services/media/" . $author_id . "/" . $name_tmp;
} else {
    $logfile = $root_dir . $public_dir . "log" . $sess_id_user . $log_extension;
}

if (!file_exists($logfile)) {
    $fp = fopen($logfile, 'w');
}

//set the  body:

if (isset($_POST['Submit'])) {
    if (isset($_POST['log_today'])) {
        $log = $_POST['log_text'] . "<br/>" . $_POST['log_today'];
        $i = fopen($logfile, 'w');
        $res = fwrite($i, $log);
        $res = fclose($i);
    }
    $msg = translateFN("Le informazioni sono state registrate.");
}
// } else {

if ($fp = fopen($logfile, 'r')) {
    $log_text = fread($fp, 16000);
} else {
    $log_text = "";
}
fclose($fp);
if (isset($op) && ($op == "export")) {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    // always modified
    header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");                          // HTTP/1.0
    //header("Content-Type: text/plain");
    header("Content-Type: text/html");
    //header("Content-Length: ".filesize($name));
    header("Content-Disposition: attachment; filename=$name_tmp");
    echo $log_text;
    exit;
} else {
    $date = todayDateFN() . " " . todayTimeFN() . "\n";
    $log_form = new Form();
    $log_data = [
    [
        'label' => "",
        'type' => 'textarea',
        'name' => 'log_today',
        'rows' => '10',
        'cols' => '80',
        'wrap' => 'virtual',
    'value' => $date,
        ],
    [
        'label' => "",
        'type' => 'hidden',
        'name' => 'log_text',
        'value' => $log_text,
        ],
    [
        'label' => '',
        'type' => 'submit',
        'name' => 'Submit',
        'value' => 'Salva',
        ],
    ];

    $log_form->initForm("$http_root_dir/browsing/mylog.php", "POST", "multipart/form-data");
    $log_form->setForm($log_data);
    $log_data = $log_form->getForm();
    $log_data .= $log_text;
}

$export_log_link = "<a href=$http_root_dir/browsing/mylog.php?op=export>" . translateFN("Esporta") . "</a><br/>";

// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users

$online_users_listing_mode = 2;
$online_users = ADALoggableUser::getOnlineUsersFN($sess_id_course_instance, $online_users_listing_mode);


/*
 $online_users_listing_mode = 0;

// vito 19 gennaio 2009
//$online_users = User::getOnlineUsersFN($id_course_instance,$online_users_listing_mode);
if(isset($sess_id_course_instance) && !empty($sess_id_course_instance)) {
  $online_users = User::getOnlineUsersFN($sess_id_course_instance,$online_users_listing_mode);
}
else {
  $online_users = '';
}
*/

$menu = $export_log_link;
// vito 19 gennaio 2009
if (isset($sess_id_course_instance) && !empty($sess_id_course_instance)) {
    $last_visited_node_id = $userObj->getLastAccessFN($sess_id_course_instance, "N");
    $node_path = $nodeObj->findPathFN();
} else {
    $last_visited_node_id = '';
}
$last_node_visited = "";
if (!empty($last_visited_node_id)) {
    $last_node = $dh->getNodeInfo($last_visited_node_id);
    if (!AMADB::isError($last_node)) {
        $last_visited_node_name = $last_node['name'];
        $last_node_visited = "<a href=view.php?id_node=$last_visited_node_id>" . translateFN("torna") . "</a><br>";
    }
}


$menu .= $last_node_visited;


$help = translateFN("Nel Diario si possono inserire i propri commenti privati, oppure esportarli per conservarli.");
// CHAT, BANNER etc


//
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

/*
  'user_name'=>$user_name,
  'user_type'=>$user_type,
  'level'=>$user_level,
  'index'=>$node_index,
  'title'=>$node_title,
  'author'=>$node_author,
  'text'=>$data['text'],
  'link'=>$data['link'],
  'messages'=>$user_messages->getHtml(),
  'agenda'=>$user_agenda->getHtml(),
  'events'=>$user_events->getHtml(),
  'chat_users'=>$online_users

*/

$body_onload = "includeFCKeditor('log_today');";
$options = ['onload_func' => $body_onload];

if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $layout_dataAR['JS_filename'] = [
    ROOT_DIR . '/js/browsing/mylog.js'];   //for defaultSelfInstruction.tpl
} else {
    $layout_dataAR = [];
}

$node_data = [
                   'course_title' => '<a href="main_index.php">' . $course_title . '</a>',
                   'today' => $ymdhms,
                   'path' => $node_path ?? '',
                   'user_name' => $userObj->nome,
                   'user_type' => $user_type,
                   'user_level' => $user_level,
                   'last_visit' => $last_access,
                   'data' => $log_data,
           'menu' => $menu,
           'help' => $help,
                   'bookmarks' => $user_bookmarks ?? '',
                   'status' => $status,
                   'profilo' => $profilo ?? '',
                   'myforum' => $my_forum ?? '',
                   'title' => $node_title ?? '',
                   'edit_profile' => $userObj->getEditProfilePage(),
                  ];

if ($com_enabled) {
    $node_data['messages'] = $user_messages->getHtml();
    $node_data['agenda'] = $user_agenda->getHtml();
    $node_data['events'] = $user_events->getHtml();
    $node_data['chat_users'] = $online_users;
} else {
    $node_data['messages'] = translateFN("messaggeria non abilitata");
    $node_data['agenda'] = translateFN("agenda non abilitata");
    $node_data['chat_users'] = "";
}
if (isset($msg)) {
    $help = CDOMElement::create('label');
    $help->addChild(new CText(translateFN(ltrim($msg))));
    $node_data['help'] = $help->getHtml();
}

if (isset($self_instruction)) {
    $menuOptions['self_instruction'] = $self_instruction;
}
ARE::render($layout_dataAR, $node_data, null, $options, $menuOptions ?? null);

/* Versione XML:

 $xmlObj = new XML($layout_template,$layout_CSS,$imgpath);
 $xmlObj->fillinTemplateFN($node_data);
 $xmlObj->outputFN('page','XML');

*/

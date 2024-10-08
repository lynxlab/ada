<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_VISITOR => ['node', 'layout', 'course'],
    AMA_TYPE_STUDENT => ['node', 'layout', 'tutor', 'course', 'course_instance'],
    AMA_TYPE_TUTOR => ['node', 'layout', 'course', 'course_instance'],
    AMA_TYPE_AUTHOR => ['node', 'layout', 'course'],
];

//FIXME: course_instance is needed by videochat BUT not for guest user


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

if ($userObj instanceof ADAGuest) {
    $self = 'guest_view';
} else {
    $self = Utilities::whoami();
}

/**
 * Backurl: if user bookmarked an address and tried to get it directly...
 *
 * if (isset($_SESSION['sess_backurl'])) {
 * unset($_SESSION['sess_backurl']);
 * }
 *
 * if (!isset($_SESSION['sess_id_user'])) {
 * $_SESSION['sess_backurl'] = $_SERVER['REQUEST_URI'];
 * header("Location: $http_root_dir"); // to login page
 * exit();
 * }
 */
/**
 * ANONYM Browsing
 *
 * if status of course_instance is ADA_STATUS_PUBLIC
 * user can visit the node but:
 * - no history
 * - no messagery
 * - no logging
 */
/**
 * Guided browsing:
 * if a guide is selected, the node id selected by the  student is overrided
 * $guide_user_id = id number of tutor
 */

// node
$id_node = $nodeObj->id;
$node_type = $nodeObj->type;
$node_title = $nodeObj->name;
$node_keywords = ltrim($nodeObj->title);
$node_level = $nodeObj->level;
$node_date = $nodeObj->creation_date;
$node_icon = $nodeObj->icon;
$node_version = $nodeObj->version;
if (is_array($nodeObj->author)) {
    $authorHa = $nodeObj->author;
    $node_author_id = $authorHa['id'];
    $node_author = $authorHa['nome'] . " " . $authorHa['cognome'];
    $author_uname = $authorHa['username'];
} else {
    $node_author = "";
    $author_uname = "";
}
$node_parent = $nodeObj->parent_id;
$node_path = $nodeObj->findPathFN();
$node_index = $nodeObj->indexFN('', 1, $user_level, $user_history, $id_profile);
$node_family = $nodeObj->template_family;
$next_node_id = $nodeObj->next_id;
$sess_id_node = $id_node;
$data = $nodeObj->filterNodeFN($user_level, $user_history, $id_profile, '');


// info on author and tutor
if (isset($tutor_uname) && isset($tutor_id)) {
    $tutor_info_link = "<a href=\"$http_root_dir/admin/zoom_user.php?id=$tutor_id\">$tutor_uname</a>";
} else {
    $tutor_info_link = '';
}

if (isset($node_author) && isset($node_author_id)) {
    $author_info_link = "<a href=\"$http_root_dir/admin/zoom_user.php?id=$node_author_id\">$node_author</a>";
} else {
    $author_info_link = '';
}



// E-portal
$eportal = PORTAL_NAME;

//show course istance name if isn't empty - valerio
if (!empty($courseInstanceObj->title)) {
    $course_title .= ' - ' . $courseInstanceObj->title;
}

/**
 * content_data
 * @var array
 */
$content_dataAr = [
    'eportal' => $eportal,
    'course_title' => "<a href='main_index.php'>" . $course_title . "</a>",
    'user_name' => $user_name,
    'user_type' => $user_type,
    'user_level' => $user_level,
    'user_score' => $user_score,
    'status' => $status,
    'node_level' => $node_level,
    'visited' => $visited ?? '',
    'path' => $node_path,
    'title' => $node_title,
    'version' => $node_version,
    'date' => $node_date,
    // FIXME: non esiste ancora...??
    //   'icon' => CourseViewer::getClassNameForNodeType($node_type),
    'icon' => $node_icon,
    'keywords' => "<a href=\"search.php?s_node_title=$node_keywords&submit=cerca&l_search=all\">$node_keywords</a>",
    'author' => $author_info_link, //'author'=>$node_author,
    'tutor' => $tutor_info_link, //'tutor'=>$tutor_uname,
];

//dynamic data from $nodeObj->filterNodeFN

$content_dataAr['text'] = $data['text'];
/* @FIXME
 * $data should NOT contain a translated string for null values but just NULL
 */

if ($data['link'] != translateFN("Nessuno")) {
    $content_dataAr['link'] = $data['link'];
} else {
    $content_dataAr['link'] = "";
}
$content_dataAr['media'] = $data['media'];
$content_dataAr['user_media'] = $data['user_media'];
if ($data['exercises'] != translateFN("Nessuno<p>")) {
    $content_dataAr['exercises'] = $data['exercises'];
} else {
    $content_dataAr['exercises'] = "";
}
if ($node_index != translateFN("Nessuno<p>")) {
    $content_dataAr['index'] = $node_index;
} else {
    $content_dataAr['index'] = "";
}
$content_dataAr['notes'] = $data['notes'];
$content_dataAr['personal'] = $data['private_notes'];
if ($node_type == ADA_GROUP_WORD_TYPE or $node_type == ADA_LEAF_WORD_TYPE) {
    $content_dataAr['text'] .= $data['extended_node'];
    /*
     * generate dattilo images DISABLED IN ADA

    $img_dir = $root_dir.'/browsing/dattilo/img';
    $url_dir = $http_root_dir.'/browsing/dattilo/img';
    if (file_exists($img_dir.'/a.jpg')) {
        $dattilo = Utilities::convertiDattiloFN($node_title,$url_dir);
        $content_dataAr['dattilo'] = $dattilo;
    }
    * */
}

$PRINT_optionsAr = [
        'id' => $id_node,
        /**
         * @author giorgio 10/dic/2014
         *
         * maybe someone meant the full current document url with the following?
         *
         * 'url'=>$_SERVER['URI'],
         *
         * find below correct code:
         */
        'url' => HTTP_ROOT_DIR . '/' . $_SERVER['REQUEST_URI'],
        'course_title' => strip_tags($content_dataAr['course_title']),
        'portal' => $eportal,
        'onload_func' => 'window.print();',
];

$layout_dataAR = [];

ARE::render($layout_dataAR, $content_dataAr, ARE_PRINT_RENDER, $PRINT_optionsAr);

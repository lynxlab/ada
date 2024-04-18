<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\HistoryManagementTest;

use function Lynxlab\ADA\Main\AMA\DBRead\readCourseInstanceFromDB;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'course', 'course_instance', 'user'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

if ($courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
}
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $self = 'tutorSelfInstruction';
} else {
    $self = 'tutor';
}

if (!isset($course_instanceObj) || !is_a($course_instanceObj, 'CourseInstance')) {
    $course_instanceObj = readCourseInstanceFromDB($_GET['id_course_instance']);
}

$management = new HistoryManagementTest(
    $_GET['op'],
    $courseObj,
    $course_instanceObj,
    $_SESSION['sess_id_user'],
    $_GET['id_test'] ?? null,
    $_GET['id_history_test'] ?? null
);
$return = $management->render();
$text = $return['html'];
$title = $return['title'];
$path = $return['path'];

/*
 * Output
 */
$content_dataAr = [
    'status' => translateFN('Navigazione'),
    'path' => $path,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'user_level' => $user_level,
    'visited' => '-',
    'icon' => $icon ?? '',
    //'navigation_bar' => $navBar->getHtml(),
    'text' =>  $text,
    'title' => $title,
    'author' => $author ?? '',
    'node_level' => 'livello nodo',
    'edit_profile' => $userObj->getEditProfilePage(),
    //'course_title' => '<a href="'.HTTP_ROOT_DIR.'/tutor/tutor.php">'.translateFN('Modulo Tutor').'</a> > ',
    //'media' => 'media',
];

$content_dataAr['notes'] = $other_node_data['notes'] ?? null;
$content_dataAr['personal'] = $other_node_data['private_notes'] ?? null;

if ($reg_enabled) {
    $content_dataAr['addBookmark'] = $addBookmark ?? "";
} else {
    $content_dataAr['addBookmark'] = "";
}

$content_dataAr['bookmark'] = $bookmark ?? "";
$content_dataAr['go_bookmarks_1'] = $go_bookmarks ?? "";
$content_dataAr['go_bookmarks_2'] = $go_bookmarks ?? "";

if ($com_enabled) {
    $content_dataAr['ajax_chat_link'] = $ajax_chat_link ?? "";
    $content_dataAr['messages'] = $user_messages->getHtml();
    $content_dataAr['agenda'] = $user_agenda->getHtml();
    $content_dataAr['events'] = $user_events->getHtml();
    $content_dataAr['chat_users'] = $online_users ?? "";
} else {
    $content_dataAr['chat_link'] = translateFN("chat non abilitata");
    $content_dataAr['messages'] = translateFN("messaggeria non abilitata");
    $content_dataAr['agenda'] = translateFN("agenda non abilitata");
    $content_dataAr['chat_users'] = "";
}

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_NO_CONFLICT,
    MODULES_TEST_PATH . '/js/dragdrop.js',
    ROOT_DIR . '/js/browsing/virtual_keyboard.js',
];

if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $layout_dataAr['JS_filename'][] =
        ROOT_DIR . '/modules/test/js/tutor.js';   //for tutorSelfInstruction.tpl
}

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $layout_dataAr['CSS_filename'][] =
        ROOT_DIR . '/modules/test/layout/ada_blu/css/tutor.css';   //for tutorSelfInstruction.tpl
}
$menuOptions['self_instruction'] = $self_instruction;
ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\SwitcherManagementTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
SwitcherHelper::init($neededObjAr);

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$self = whoami();

if (!is_a($courseObj, 'Course')) {
    $courseObj = DBRead::readCourseFromDB($_GET['id_course']);
}

$management = new SwitcherManagementTest($courseObj);
$return = $management->render();
$text = $return['html'];
$title = $return['title'];
$path = $return['path'];

/*
 * Go back link
 */
$navigation_history = $_SESSION['sess_navigation_history'];
$last_visited_node  = $navigation_history->lastModule();
$go_back_link = CDOMElement::create('a', 'href:' . $last_visited_node);
$go_back_link->addChild(new CText(translateFN('Indietro')));

$content_dataAr = [
    'path' => $path,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $title,
    'title' => $title,
    'help' => $help ?? '',
    'text' => $text,
    'go_back' => $go_back_link->getHtml(),
    'module' => $module ?? '',
    'ajax_chat_link' => '<a href="' . HTTP_ROOT_DIR . '/comunica/list_chatrooms.php">' . translateFN('Lista chatrooms'),
    'messages' => $user_messages->getHtml(),
    'course_title' => '<a href="' . HTTP_ROOT_DIR . '/switcher/switcher.php">' . translateFN('Modulo Switcher') . '</a> > ',
    'back_link' => $go_back_link->getHtml(),
];

if (isset($other_node_data['notes'])) {
    $content_dataAr['notes'] = $other_node_data['notes'];
}
if (isset($other_node_data['private_notes'])) {
    $content_dataAr['personal'] = $other_node_data['private_notes'];
}

if ($reg_enabled) {
    $content_dataAr['addBookmark'] = $addBookmark;
} else {
    $content_dataAr['addBookmark'] = "";
}

if (isset($bookmark)) {
    $content_dataAr['bookmark'] = $bookmark;
}
if (isset($go_bookmarks)) {
    $content_dataAr['go_bookmarks_1'] = $go_bookmarks;
}
if (isset($go_bookmarks)) {
    $content_dataAr['go_bookmarks_2'] = $go_bookmarks;
}

if ($com_enabled) {
    $content_dataAr['ajax_chat_link'] = $ajax_chat_link;
    $content_dataAr['messages'] = $user_messages->getHtml();
    $content_dataAr['agenda'] = $user_agenda->getHtml();
    $content_dataAr['events'] = $user_events->getHtml();
    $content_dataAr['chat_users'] = $online_users;
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
];
$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

ARE::render($layout_dataAr, $content_dataAr);

<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\AnswersManagementTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_AUTHOR => ['layout', 'node', 'course', 'course_instance'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

//$self =  Utilities::whoami();
$self = 'answers';

ServiceHelper::init($neededObjAr);
$layout_dataAr['node_type'] = $self;

$online_users_listing_mode = 2;
$online_users = ADAGenericUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);


//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$question = $dh->testGetNode($_GET['id_question']);
$management = new AnswersManagementTest($_GET['id_question']);
$form_return = $management->run();

if (!AMATestDataHandler::isError($question) && !empty($question)) {
    $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');

    /*template fields for new menu*/
    $edit_question = 'action=mod&id_question=' . $question['id_nodo'] . $get_topic;
    $delete_question = 'action=del&id_question=' . $question['id_nodo'] . $get_topic;
}

// per la visualizzazione del contenuto della pagina

$content_dataAr = [
        'head' => $head_form,
        'path' => $form_return['path'],
        'form' => $form_return['html'],
        'status' => $form_return['status'],
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'title' => $node_title,
        'course_title' => $course_title,
        'back' => $back,
        'edit_question' => $edit_question,
        'delete_question' => $delete_question,
];

$content_dataAr['notes'] = $other_node_data['notes'];
$content_dataAr['personal'] = $other_node_data['private_notes'];

if ($reg_enabled) {
    $content_dataAr['addBookmark'] = $addBookmark;
} else {
    $content_dataAr['addBookmark'] = "";
}

$content_dataAr['bookmark'] = $bookmark;
$content_dataAr['go_bookmarks_1'] = $go_bookmarks;
$content_dataAr['go_bookmarks_2'] = $go_bookmarks;

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

<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\QuestionManagementTest;

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
$self = 'form';

ServiceHelper::init($neededObjAr);
$layout_dataAr['node_type'] = $self;

$online_users_listing_mode = 2;
$online_users = ADAGenericUser::getOnlineUsersFN($id_course_instance ?? 0, $online_users_listing_mode);

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

/*
 * Generazione dei form per l'inserimento dell'esercizio.
 *
*/
$management = new QuestionManagementTest($_GET['action'], $_GET['id_question'] ?? null, $_GET['id_test'] ?? null);
$form_return = $management->run();

// per la visualizzazione del contenuto della pagina

$content_dataAr = [
        'head' => $head_form ?? null,
        'path' => $form_return['path'],
        'form' => $form_return['html'],
        'status' => $form_return['status'],
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'title' => $node_title ?? null,
        'course_title' => $course_title ?? null,
        'back' => $back ?? null,
];

// $content_dataAr['notes'] = $other_node_data['notes'];
// $content_dataAr['personal'] = $other_node_data['private_notes'];


if ($reg_enabled) {
    $content_dataAr['addBookmark'] = $addBookmark ?? null;
} else {
    $content_dataAr['addBookmark'] = "";
}

$content_dataAr['bookmark'] = $bookmark ?? null;
$content_dataAr['go_bookmarks_1'] = $go_bookmarks ?? null;
$content_dataAr['go_bookmarks_2'] = $go_bookmarks ?? null;

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
    JQUERY_NO_CONFLICT,
];

ARE::render($layout_dataAr, $content_dataAr);

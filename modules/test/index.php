<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\NodeTest;
use Lynxlab\ADA\Module\Test\SurveyTest;
use Lynxlab\ADA\Module\Test\TestTest;

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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'tutor', 'node', 'course', 'course_instance'],
    AMA_TYPE_TUTOR => ['layout', 'node', 'course', 'course_instance'],
    AMA_TYPE_AUTHOR => ['layout', 'node', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */

/*if isset $_GET['unload'] means that the system is closing test, so there is no need to save
  the page in NavigationHistory  */
if (isset($_GET['unload'])) {
    $trackPageToNavigationHistory = false;
}
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$self = Utilities::whoami();

$test = NodeTest::readTest($_GET['id_test']);
if (!AMATestDataHandler::isError($test)) {
    /**
     * If user has completed or has a terminated status for the instance,
     * redirect to $test->id_nodo_riferimento or its parent depending on
     * ADA_REDIRECT_TO_TEST being true or false
     */
    if (
        $userObj->getType() == AMA_TYPE_STUDENT && isset($sess_id_course_instance) && intval($sess_id_course_instance) > 0 &&
            in_array(
                $userObj->getStudentStatus($userObj->getId(), $sess_id_course_instance),
                [ADA_STATUS_COMPLETED, ADA_STATUS_TERMINATED]
            )
    ) {
        /**
         * @author giorgio 07/apr/2015
         *
         * if user has the terminated status for the course instance, redirect to view
         */
        $id_node = $sess_id_course . '_0'; // if nothing better is found, redirect to course root node
        if (!ADA_REDIRECT_TO_TEST) {
            /**
             * if !ADA_REDIRECT_TO_TEST, redirecting to $test->id_nodo_riferimento
             * shall not cause a redirection loop
             */
            $id_node = $test->id_nodo_riferimento;
        } else {
            /**
             * else redirect to the parent of $test->id_nodo_riferimento
             */
            $nodeInfo = $GLOBALS['dh']->getNodeInfo($test->id_nodo_riferimento);
            if (!AMADB::isError($nodeInfo) && isset($nodeInfo['parent_id']) && strlen($nodeInfo['parent_id']) > 0) {
                $id_node = $nodeInfo['parent_id'];
            }
        }
        Utilities::redirect(HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $id_node . '&id_course=' . $sess_id_course .
                '&id_course_instance=' . $sess_id_course_instance);
    }

    $test->run();
    $text = $test->render(true);
    $title = $test->titolo;

    $nodeObj = new Node($test->id_nodo_riferimento); //present in session
    $path = $nodeObj->findPathFN();

    $courseObj = new Course($sess_id_course); //present in session
    $course_title = $courseObj->titolo;

    $courseInstanceObj = new CourseInstance($sess_id_course_instance); //present in session
    if (!empty($courseInstanceObj->title)) {
        $course_title .= ' - ' . $courseInstanceObj->title;
    }

    if ($id_profile == AMA_TYPE_AUTHOR) {
        if (is_a($test, TestTest::class)) {
            $what = translateFN('test');
            $mode = 'test';
        } elseif (is_a($test, SurveyTest::class)) {
            $what = translateFN('sondaggio');
            $mode = 'survey';
        }

        $edit_test_link = CDOMElement::create('a', 'href:edit_test.php?mode=' . $mode . '&action=mod&id_test=' . $test->id_nodo);
        $edit_test_link->addChild(new CText(sprintf(translateFN('Modifica %s'), $what)));
        $edit_test_link = $edit_test_link->getHtml();

        $delete_test_link = CDOMElement::create('a', 'href:edit_test.php?mode=' . $mode . '&action=del&id_test=' . $test->id_nodo);
        $delete_test_link->addChild(new CText(sprintf(translateFN('Cancella %s'), $what)));
        $delete_test_link = $delete_test_link->getHtml();
    }
} else {
    $text = translateFN('Impossibile trovare il test');
    $title = translateFN('Si è verificato un errore');
    $course_title = translateFN('Si è verificato un errore');
}

/*
 * Go back link
 */
$navigation_history = $_SESSION['sess_navigation_history'];
$last_visited_node  = $navigation_history->lastModule();
$go_back_link = CDOMElement::create('a', 'href:' . $last_visited_node);
$go_back_link->addChild(new CText(translateFN('Indietro')));

$back_link = CDOMElement::create('a', 'href:' . $last_visited_node);
$back_link->addChild(new CText(translateFN('Torna')));


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
    'go_back' => $go_back_link->getHtml(),
    'back_link' => $back_link->getHtml(),
    'edit_test' => $edit_test_link ?? '',
    'delete_test' => $delete_test_link ?? '',
    'add_topic' => $add_topic_link ?? '',
    'add_question' => $add_question_link ?? '',
    'title' => $title ?? '',
    'author' => $author ?? '',
    'node_level' => 'livello nodo',
    'course_title' => isset($course_title) ? '<a href="../../browsing/main_index.php">' . $course_title . '</a> > ' : '',
    //'media' => 'media',
];

if (isset($other_node_data['notes'])) {
    $content_dataAr['notes'] = $other_node_data['notes'];
}
if (isset($other_node_data['private_notes'])) {
    $content_dataAr['personal'] = $other_node_data['private_notes'];
}

if ($reg_enabled && isset($addBookmark)) {
    $content_dataAr['addBookmark'] = $addBookmark ?? null;
} else {
    $content_dataAr['addBookmark'] = "";
}

$content_dataAr['bookmark'] = $bookmark ?? '';
$content_dataAr['go_bookmarks_1'] = $go_bookmarks ?? '';
$content_dataAr['go_bookmarks_2'] = $go_bookmarks ?? '';

if ($com_enabled) {
    $content_dataAr['ajax_chat_link'] = $ajax_chat_link ?? '';
    $content_dataAr['messages'] = $user_messages->getHtml();
    $content_dataAr['agenda'] = $user_agenda->getHtml();
    $content_dataAr['events'] = $user_events->getHtml();
    $content_dataAr['chat_users'] = $online_users ?? '';
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
    MODULES_TEST_PATH . '/js/variation.js',
    MODULES_TEST_PATH . '/js/erasehighlight.js',
    ROOT_DIR . '/js/browsing/virtual_keyboard.js',
];
$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

/**
 * added here to test new menu
 */
if ($id_profile == AMA_TYPE_AUTHOR) {
    $content_dataAr['edit_test'] = 'mode=' . $mode . '&action=mod&id_test=' . $test->id_nodo;
    $content_dataAr['delete_test'] = 'mode=' . $mode . '&action=del&id_test=' . $test->id_nodo;
}
$content_dataAr['go_back'] = $last_visited_node;
$content_dataAr['what'] = $what ?? '';


ARE::render($layout_dataAr, $content_dataAr);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\SurveyTest;

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
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
$dh = $GLOBALS['dh'];

$self = Utilities::whoami();

/** @var \Lynxlab\ADA\Main\Course\CourseInstance $course_instanceObj */
if (!isset($course_instanceObj) || !is_a($course_instanceObj, CourseInstance::class)) {
    $course_instanceObj = DBRead::readCourseInstanceFromDB($_GET['id_course_instance']);
}

$reportData = SurveyTest::getSurveysReportForCourseInstance($course_instanceObj);

if (array_key_exists('output', $_GET)) {
    if ($_GET['output'] === 'json') {
        header('Content-Type: application/json');
        die(json_encode($reportData));
    } elseif ($_GET['output'] === 'csv') {
        $filename = 'survey-report-' . $course_instanceObj->getCourseId() . '-' . $course_instanceObj->getId() . '.csv';
        // send response headers to the browser
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $filename);
        $fp = fopen('php://output', 'w');
        foreach ($reportData['surveys'] as $surveyData) {
            foreach (SurveyTest::buildSurveyReportTable($surveyData, true) as $row) {
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
        die();
    }
}

if (defined('ADA_SURVEY_TO_CSV') && ADA_SURVEY_TO_CSV) {
    $reportData['surveys'] = array_filter(
        $reportData['surveys'],
        fn ($el) => !empty(SurveyTest::buildCSVFileInfo(
            $_SESSION['sess_id_user'],
            $_SESSION['sess_id_course'],
            $_SESSION['sess_id_course_instance'],
            $el['nome']
        ))
    );
}
$hasSurveys = !empty($reportData['surveys']);

if (array_key_exists('surveys', $reportData) && $hasSurveys) {
    $data = CDOMElement::create('div', 'class:surveyreport container');
    foreach ($reportData['surveys'] as $surveyData) {
        if (defined('ADA_SURVEY_TO_CSV') && ADA_SURVEY_TO_CSV) {
            if ($hasSurveys) {
                $hasSurveys = false;
                $header = CDOMElement::create('h2', 'class:ui header');
                $header->addChild(CDOMElement::create('i', 'class:download icon'));
                $content = CDOMElement::create('div', 'class:content');
                $content->addChild(new CText('Download risposte sondaggi'));
                $subh = CDOMElement::create('div', 'class:sub header');
                $subh->addChild(new CText(translateFN('clicca su un bottone per scaricare il csv delle risposte al relativo sondaggio')));
                $header->addChild($content);
                $header->addChild($subh);
                $data->addChild($header);
            }
            $fileInfo = SurveyTest::buildCSVFileInfo(
                $_SESSION['sess_id_user'],
                $_SESSION['sess_id_course'],
                $_SESSION['sess_id_course_instance'],
                $surveyData['nome'],
                true
            );
            $link = CDOMElement::create('a', 'class: ui fluid button, href:' . $fileInfo['fileName']);
            $link->setAttribute('style', 'margin-top: 1em;');
            $link->addChild(new CText($surveyData['nome'] . ' (' . Utilities::ts2dFN($fileInfo['filemtime']) . ')'));
            $data->addChild($link);
        } else {
            $tObj = SurveyTest::buildSurveyReportTable($surveyData);
            $tObj->setAttribute('class', $tObj->getAttribute('class') . ' default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
            $data->addChild($tObj);
        }
    }
} else {
    $data = CDOMElement::create('div', 'class:ui info icon large message');
    $data->addChild(CDOMElement::create('i', 'class:info icon'));
    $MSGcontent = CDOMElement::create('div', 'class:content');
    $MSGheader = CDOMElement::create('div', 'class:header');
    $MSGtext = CDOMElement::create('span', 'class:message');

    $data->addChild($MSGcontent);
    $MSGcontent->addChild($MSGheader);
    $MSGcontent->addChild($MSGtext);

    $MSGheader->addChild(new CText(translateFN('Nessun sondaggio associato all\'istanza')));
    $MSGtext->addChild(new CText(translateFN('Il coordinatore deve prima assegnare un sondaggio a questa istanza')));
}

/*
 * Go back link
 */
$navigation_history = $_SESSION['sess_navigation_history'];
$last_visited_node  = $navigation_history->lastModule();
$go_back_link = CDOMElement::create('a', 'href:' . $last_visited_node);
$go_back_link->addChild(new CText(translateFN('Indietro')));

/*
 * Output
 */
$content_dataAr = [
    'status' => translateFN('Navigazione'),
    'path' => translateFN('Report sondaggi'),
    'user_name' => $user_name,
    'user_type' => $user_type,
    'user_level' => $user_level,
    'visited' => '-',
    'icon' => $icon ?? '',
    //'navigation_bar' => $navBar->getHtml(),
    'text' =>  isset($data) ? $data->getHtml() : translateFN('Errore sconosciuto'),
    'go_back' => $go_back_link->getHtml(),
    'title' => $title ?? '',
    'author' => $author ?? '',
    'node_level' => 'livello nodo',
    'course_title' => '<a href="' . HTTP_ROOT_DIR . '/tutor/tutor.php">' . translateFN('Modulo Tutor') . '</a> > ',
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

if (isset($bookmark)) {
    $content_dataAr['bookmark'] = $bookmark ?? null;
}
if (isset($go_bookmarks)) {
    $content_dataAr['go_bookmarks_1'] = $go_bookmarks ?? null;
}
if (isset($go_bookmarks)) {
    $content_dataAr['go_bookmarks_2'] = $go_bookmarks ?? null;
}

if ($com_enabled) {
    if (isset($ajax_chat_link)) {
        $content_dataAr['ajax_chat_link'] = $ajax_chat_link;
    }
    $content_dataAr['messages'] = $user_messages->getHtml();
    $content_dataAr['agenda'] = $user_agenda->getHtml();
    $content_dataAr['events'] = $user_events->getHtml();
    if (isset($online_users)) {
        $content_dataAr['chat_users'] = $online_users;
    }
} else {
    $content_dataAr['chat_link'] = translateFN("chat non abilitata");
    $content_dataAr['messages'] = translateFN("messaggeria non abilitata");
    $content_dataAr['agenda'] = translateFN("agenda non abilitata");
    $content_dataAr['chat_users'] = "";
}


$layout_dataAr['JS_filename'] = [
    JQUERY_UI,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

$options['onload_func'] = 'initDoc(' . ($hasSurveys ? 'true' : 'false') . ')';
ARE::render($layout_dataAr, $content_dataAr, null, $options);

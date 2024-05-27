<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\TutorModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();

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
TutorHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */

/*
 * If id course instance is not set or is not valid,
 * return to user's home page
 */
$id_course_instance = DataValidator::isUinteger($_GET['id_course_instance']);
if ($id_course_instance === false) {
    $errObj = new ADAError(
        null,
        translateFN('Impossibile accedere al modulo'),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
}

/*
 * If id user is not set or is not valid,
 * return to user's home page
 */
$id_user = DataValidator::isUinteger($_GET['id_user']);
if ($id_user === false) {
    $errObj = new ADAError(
        null,
        translateFN('Impossibile accedere al modulo'),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
}

$page = DataValidator::isUinteger($_GET['page']);
if ($page === false) {
    $page = 1;
}

$tutoredUserObj = MultiPort::findUser($id_user);

/*
 * Obtain service information and eguidance data for the given id_course_instance
 */
$id_course = $dh->getCourseIdForCourseInstance($id_course_instance);
if (AMADataHandler::isError($id_course)) {
    $errObj = new ADAError(
        null,
        translateFN("Errore nell'ottenimento dell'id del servzio"),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
}

$service_infoAr = $common_dh->getServiceInfoFromCourse($id_course);
if (AMACommonDataHandler::isError($service_infoAr)) {
    $errObj = new ADAError(
        null,
        translateFN("Errore nell'ottenimento delle informazioni sul servizio"),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
}

$eguidance_session_datesAr = $dh->getEguidanceSessionDates($id_course_instance);
if (AMADataHandler::isError($eguidance_session_datesAr)) {
    $errObj = new ADAError(
        null,
        translateFN("Errore nell'ottenimento delle informazioni sul servizio"),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
}

$eguidance_sessions_count = count($eguidance_session_datesAr);
if ($page > $eguidance_sessions_count) {
    $page = $eguidance_sessions_count;
}

/*
 * Obtain and display an eguidance session evaluation sheet.
 */
$eguidance_session_dataAr = $dh->getEguidanceSession($id_course_instance, $page - 1);
if (
    AMADataHandler::isError($eguidance_session_dataAr)
    && $eguidance_session_dataAr->code != AMA_ERR_GET
) {
    $errObj = new ADAError(
        null,
        translateFN("Errore nell'ottenimento delle informazioni sul servizio"),
        null,
        null,
        null,
        $userObj->getHomePage()
    );
} elseif (AMADataHandler::isError($eguidance_session_dataAr)) {
    // Mostrare messaggio non ci sono dati
    $data = new CText(translateFN("There aren't evaluation sheets available"));
    $htmlData = $data->getHtml();
} else {
    $base_href = 'eguidance_sessions_summary.php?id_course_instance='
        . $id_course_instance . '&id_user=' . $id_user;

    $p = 1;
    $page_titles = [];
    foreach ($eguidance_session_datesAr as $d) {
        $page_titles[$p++] = Utilities::ts2dFN($d['data_ora']);
    }

    $pagination_bar = BaseHtmlLib::getPaginationBar($page, $page_titles, $base_href);

    $data = TutorModuleHtmlLib::displayEguidanceSessionData($tutoredUserObj, $service_infoAr, $eguidance_session_dataAr);

    $htmlData = $pagination_bar->getHtml() . $data->getHtml();
}

/*
 *
 */
$label = translateFN('Eguidance session summary');

$home_link = CDOMElement::create('a', 'href:tutor.php');
$home_link->addChild(new CText(translateFN("Epractitioner's home")));
$path = $home_link->getHtml() . ' > ' . $label;

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status'    => $status,
    'label'     => $label,
    'dati'      => $htmlData,
    'path'      => $path,
];

ARE::render($layout_dataAr, $content_dataAr);

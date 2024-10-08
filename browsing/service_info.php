<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\GuestHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Service\Service;
use Lynxlab\ADA\Main\Service\ServiceImplementor;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Service\Functions\getCourseInstanceInfo;

/* Questa versione è diversa dalla versione ADA;
 * se non è passato nessun parametro,
 * 		redirect a /info.php
 * se è passato il parametro $id_course o $id_service
 * 		le info sul servizio:
 * 			tester, titolo, descrizione, (livello), durata, il testo del nodo $id_toc
 * 		se esiste in sessione sess_id_user ed se è passato il parametro $id_course_instance
 * 				mostra le info specifiche sulla storia del servizio:
 * 				data di inizio, tutor ( messaggi inviati e ricevuti)
 */

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Performs basic controls before entering this module
 */
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_VISITOR      => ['layout'],
    AMA_TYPE_STUDENT         => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR       => ['layout'],
    AMA_TYPE_SWITCHER       => ['layout'],
    AMA_TYPE_ADMIN       => ['layout'],

];

require_once ROOT_DIR . '/include/module_init.inc.php';


/**
 * Get needed objects
 */

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
$common_dh = AMACommonDataHandler::getInstance();

$self = Utilities::whoami();   // serve per scegliere il template

//$dh = AMADataHandler::instance(); // init AMA

$sess_id_user = $_SESSION['sess_id_user'];

$status    = translateFN('Navigazione');
// if (!isset($sess_id_user) || ($sess_id_user == 0)){

if ($userObj instanceof ADAGuest) {
    // $home="<a href=\"$http_root_dir/index.php\">home</a>";
    $home = $userObj->getHomePage();
    $enroll_link = "";
    $register_link = "<a href=\"browsing/registration.php\">" . translateFN("registrazione") . "</a><br>";
    $agenda_link = "";
    $msg_link = "";
    $user_name = translateFN('Guest');
    $user_type = translateFN('Guest');
} else {
    switch ($id_profile) {
        case AMA_TYPE_VISITOR:
        default:
            $enroll_link = "";
            $register_link = "<a href=\"browsing/registration.php\">" . translateFN("registrazione") . "</a><br>";
            $home = "<a href=\"$http_root_dir/index.php\">home</a>";
            break;
        case AMA_TYPE_STUDENT:
        case AMA_TYPE_TUTOR:
        case AMA_TYPE_ADMIN:
        case AMA_TYPE_AUTHOR:
        case AMA_TYPE_SWITCHER:
            $enroll_link = ""; //"<a href=$http_root_dir/iscrizione/student_course_instance_menu.php>".translateFN("Iscriviti ad un corso")."</a><br>";
            $register_link = "";
            $home = $userObj->getHomePage();
            break;
    }
} // end if

if (
    (isset($_REQUEST['id_course'])) ||
    (isset($_REQUEST['id_course_instance'])) ||
    (isset($_REQUEST['id_service']))
) { // there is a specific request about a service
    // service from course ??

    /* 1. info about a service implementation*/
    if (isset($_REQUEST['id_course'])) { // get specific info about a service implementation
        $id_course = $_REQUEST['id_course'];


        $serviceObj = Service::findServiceFromImplementor($id_course);

        $serviceAr = $serviceObj->getServiceInfo();

        // Deve essere mostrato comunque?

        $service_data = GuestHtmlLib::displayServiceData($userObj, $serviceAr, $optionsAr);

        //$service_data = _get_service_info($_REQUEST['id_service']); // from services_functions


        $serviceImplementationObj = ServiceImplementor::findImplementor($id_course);

        // $serviceImplementationObj = new ServiceImplementor($id_course);

        $serviceImplementationAr = $serviceImplementationObj->getImplementorInfo();


        $impl_service_data = GuestHtmlLib::displayImplementationServiceData($userObj, $serviceImplementationAr, $optionsAr);

        /*
        $courseAr = $serviceObj->getImplementor($id_course);
        $course_dataList = BaseHtmlLib::plainListElement("",$courseAr);
        $impl_service_data = $course_dataList->getHtml();
        */

        // $impl_service_data = _get_course_info($id_course); // from services_functions


        if ($_REQUEST['norequest'] != 1) {
            if ($id_profile == AMA_TYPE_STUDENT) {
                $submit_linkObj = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR . '/browsing/subscribe.php?id_course=' . $_REQUEST['id_course']);
                $submit_linkObj->addChild(new CText(translateFN("Richiedi")));
                $submit_div = CDOMElement::create('div', "class:ask_service");
                $submit_div->addChild($submit_linkObj);
                $submit_link = $submit_div->getHtml();
            } elseif ($id_profile == AMA_TYPE_VISITOR) {
                $submit_linkObj = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR . '/browsing/registration.php?id_course=' . $_REQUEST['id_course']);
                $submit_linkObj->addChild(new CText(translateFN("Richiedi")));
                $submit_div = CDOMElement::create('div', "class:ask_service");
                $submit_div->addChild($submit_linkObj);
                $submit_link = $submit_div->getHtml();
            } else {
                $submit_link = "";
            }
        } else {
            $submit_link = "";
        }

        /* 2. info about a requested service  */
        if (
            (isset($_REQUEST['id_course_instance'])) and (isset($_REQUEST['id_course']))
        ) {
            $requested_service_data = getCourseInstanceInfo($_REQUEST['id_course'], $_REQUEST['id_course_instance']); // from services_functions
        } // id_course_instance
        // end if id_course
    } elseif (isset($_REQUEST['id_service'])) { // get general info about a service
        /* 3. info only about a service  */
        $service_dataHa = $common_dh->getServiceInfo($_REQUEST['id_service']); //var_dump($service_dataHa);
        $serviceObj = new Service($service_dataHa); // var_dump($serviceObj);

        //$serviceObj = new Service($_REQUEST['id_service']);
        $serviceAr = $serviceObj->getServiceInfo();
        $service_data = GuestHtmlLib::displayServiceData($userObj, $serviceAr, $optionsAr);


        //  $service_data = _get_service_info($_REQUEST['id_service']); // from services_functions
    } // end if id_service
} else {    // service request is empty
    /* 3.only  info about all activated services (courses)*/

    $parms = implode("&", $_GET);
    $redirectUrl = HTTP_ROOT_DIR . "/info.php?" . $parms;
    header("Location: " . $redirectUrl);
}

$course_title = "";
$course_author_name = "";

if (isset($service_data)) {
    $infomsg .= $service_data . $submit_link;
}

if (isset($impl_service_data)) {
    $infomsg .= $impl_service_data;
}

if (isset($requested_service_data)) {
    $infomsg .= $requested_service_data;
}


$menu = "<a href=\"#course_list\">" . translateFN("dettaglio servizio") . "</a><br>";
$menu .= $register_link;
$menu .= $enroll_link;

$title = translateFN('Informazioni');

$content_dataAr = [
    'home' => $home,
    'menu' => $menu,
    'text' => $infomsg,
    // 'help'=>$hlpmsg,
    'message' => $message,
    'agenda_link' => $agenda_link,
    'msg_link' => $msg_link,
    'course_list' => $total_course_data,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
];



/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);


// end module

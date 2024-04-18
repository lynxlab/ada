<?php

use Lynxlab\ADA\Comunica\Event\ADAEventProposal;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\HtmlLibrary\TutorModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

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

$sess_navigationHistory = $_SESSION['sess_navigation_history'];
if (
    $sess_navigationHistory->callerModuleWas('quitChatroom')
    || $sess_navigationHistory->callerModuleWas('close_videochat')
    || $sess_navigationHistory->callerModuleWas('list_events')
    || isset($_GET['popup'])
) {
    $self = whoami();
    $is_popup = true;
} else {
    $self =  'tutor';
    $is_popup = false;
}

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

/*
 * YOUR CODE HERE
 */

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Genera CSV a partire da contenuto $_POST
    // e crea CSV forzando il download

    if (isset($_POST['is_popup'])) {
        $href_suffix = '&popup=1';
        unset($_POST['is_popup']);
    } else {
        $href_suffix = '';
    }
    $eguidance_dataAr = $_POST;
    $eguidance_dataAr['id_tutor'] = $userObj->getId();

    if (isset($eguidance_dataAr['id_eguidance_session'])) {
        /*
         * Update an existing eguidance session evaluation
         */
        $result = $dh->updateEguidanceSessionData($eguidance_dataAr);
        if (AMADataHandler::isError($result)) {
            $errObj = new ADAError($result);
        }
    } else {
        /*
         * Save a new eguidance session evaluation
         */
        $result = $dh->addEguidanceSessionData($eguidance_dataAr);
        if (AMADataHandler::isError($result)) {
            $errObj = new ADAError($result);
        }
    }
    //createCSVFileToDownload($_POST);

    //$text = translateFN('The eguidance session data were correctly saved.');
    //$form = CommunicationModuleHtmlLib::getOperationWasSuccessfullView($text);
    /*
     * Redirect the practitioner to user service detail
     */
    $tutored_user_id    = $eguidance_dataAr['id_utente'];
    $id_course_instance = $eguidance_dataAr['id_istanza_corso'];
    header('Location: user_service_detail.php?id_user=' . $tutored_user_id . '&id_course_instance=' . $id_course_instance . $href_suffix);
    exit();
} else {
    /*
     * Obtain event_token from $_GET.
     */

    if (isset($_GET['event_token'])) {
        $event_token = DataValidator::validateEventToken($_GET['event_token']);
        if ($event_token === false) {
            $errObj = new ADAError(
                null,
                translateFN("Dati in input per il modulo eguidance_tutor_form non corretti"),
                null,
                null,
                null,
                $userObj->getHomePage()
            );
        }
    } else {
        $errObj = new ADAError(
            null,
            translateFN("Dati in input per il modulo eguidance_tutor_form non corretti"),
            null,
            null,
            null,
            $userObj->getHomePage()
        );
    }

    $id_course_instance = ADAEventProposal::extractCourseInstanceIdFromThisToken($event_token);

    /*
     * Get service info
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

    $users_infoAr = $dh->courseInstanceStudentsPresubscribeGetList($id_course_instance);
    if (AMADataHandler::isError($users_infoAr)) {
        $errObj = new ADAError(
            $users_infoAr,
            translateFN("Errore nell'ottenimento dei dati dello studente"),
            null,
            null,
            null,
            $userObj->getHomePage()
        );
    }


    /*
     * Get tutored user info
     */
    /*
     * In ADA only a student can be subscribed to a specific course instance
     * if the service has level < 4.
     * TODO: handle form generation for service with level = 4 and multiple users
     * subscribed.
     */
    $user_infoAr = $users_infoAr[0];
    $id_user = $user_infoAr['id_utente_studente'];
    $tutoredUserObj = MultiPort::findUser($id_user);

    $service_infoAr['id_istanza_corso'] = $id_course_instance;
    $service_infoAr['event_token']      = $event_token;

    /*
     * Check if an eguidance session with this event_token exists. In this case,
     * use this data to fill the form.
     */
    $eguidance_session_dataAr = $dh->getEguidanceSessionWithEventToken($event_token);
    if (!AMADataHandler::isError($eguidance_session_dataAr)) {
        if ($is_popup) {
            $eguidance_session_dataAr['is_popup'] = true;
        }
        $form = TutorModuleHtmlLib::getEditEguidanceDataForm($tutoredUserObj, $service_infoAr, $eguidance_session_dataAr);
    } else {
        $last_eguidance_session_dataAr = $dh->getLastEguidanceSession($id_course_instance);
        if (AMADataHandler::isError($last_eguidance_session_dataAr)) {
            $errObj = new ADAError(
                $users_infoAr,
                translateFN("Errore nell'ottenimento dei dati della precedente sessione di eguidance"),
                null,
                null,
                null,
                $userObj->getHomePage()
            );
        }

        if ($is_popup) {
            $last_eguidance_session_dataAr['is_popup'] = true;
        }
        $form = TutorModuleHtmlLib::getEguidanceTutorForm($tutoredUserObj, $service_infoAr, $last_eguidance_session_dataAr);
    }
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status'    => $status,
    'dati'      => $form->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

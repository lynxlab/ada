<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Comunica\Event\ADAEventProposal;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\EguidanceSession;
use Lynxlab\ADA\Main\HtmlLibrary\TutorModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Tutor\Eguidance\Utils;

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
if (isset($_GET['popup'])) {
    $self = 'eguidance_tutor_form';
    $href_suffix = '&popup=1';
} else {
    $self =  'default';//Utilities::whoami();
    $href_suffix = '';
}
TutorHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */
/*
 * deve mostrare
 * 1. il report delle interazioni concluse, con la durata (?) e la tipologia
 * 2. gli appuntamenti ancora da effettuare con l'utente
 * 3. le sue note private sull'utente
 */
if (isset($_GET['op']) && $_GET['op'] == 'csv') {
    $event_token = DataValidator::validateEventToken($_GET['event_token']);
    if ($event_token === false) {
        $errObj = new ADAError(
            null,
            translateFN("Dati in input per il modulo user_service_detail non corretti"),
            null,
            null,
            null,
            $userObj->getHomePage()
        );
    }
    /*
     * type_of_guidance
     * user_fullname
     * user_country
     * service_duration
     */

    $eguidance_session_dataAr = $dh->getEguidanceSessionWithEventToken($event_token);
    if (AMADataHandler::isError($eguidance_session_dataAr)) {
        $errObj = new ADAError($eguidance_session_dataAr);
    } else {
        $tutoredUserObj = MultiPort::findUser($eguidance_session_dataAr['id_utente']);
        $eguidance_session_dataAr['user_fullname'] = $tutoredUserObj->getFullName();
        $eguidance_session_dataAr['user_country']  = $tutoredUserObj->getCountry();
        $eguidance_session_dataAr['service_duration'] = '';

        Utils::createCSVFileToDownload($eguidance_session_dataAr);
        /*
         * exits here.
         */
    }
} else {
    $id_user = DataValidator::isUinteger($_GET['id_user']);
    $id_course_instance = DataValidator::isUinteger($_GET['id_course_instance']);
    if ($id_user === false || $id_course_instance === false) {
        $errObj = new ADAError(
            null,
            translateFN("Dati in input per il modulo user_servide_detail non corretti"),
            null,
            null,
            null,
            $userObj->getHomePage()
        );
    }

    /*
     * User data to display
     */
    $tutoredUserObj = MultiPort::findUser($id_user);
    $user_data = TutorModuleHtmlLib::getEguidanceSessionUserDataTable($tutoredUserObj);

    /*
     * Service data to display
     */
    $id_course = $dh->getCourseIdForCourseInstance($id_course_instance);
    if (!AMADataHandler::isError($id_course)) {
        $service_infoAr = $common_dh->getServiceInfoFromCourse($id_course);
        if (!AMACommonDataHandler::isError($service_infoAr)) {
            $service_data = TutorModuleHtmlLib::getServiceDataTable($service_infoAr);
        } else {
            $service_data = new CText('');
        }
    }

    /*
     * Eguidance sessions data to display
     */
    $eguidance_sessionsAr = $dh->getEguidanceSessions($id_course_instance);
    if (AMADataHandler::isError($eguidance_sessionsAr) || count($eguidance_sessionsAr) == 0) {
        $eguidance_data = new CText('');
    } else {
        $thead_data = [translateFN('Eguidance sessions conducted'), '', '',''];
        $tbody_data = [];
        foreach ($eguidance_sessionsAr as $eguidance_sessionAr) {
            $eguidance_date = Utilities::ts2dFN($eguidance_sessionAr['data_ora']);
            $eguidance_type = EguidanceSession::textForEguidanceType($eguidance_sessionAr['tipo_eguidance']);
            $href = 'eguidance_tutor_form.php?event_token=' . $eguidance_sessionAr['event_token'] . $href_suffix;
            $eguidance_form = CDOMElement::create('a', "href:$href");
            $eguidance_form->addChild(new CText('edit'));

            $href = 'user_service_detail.php?op=csv&event_token=' . $eguidance_sessionAr['event_token'];
            $download_csv = CDOMElement::create('a', "href:$href");
            $download_csv->addChild(new CText('download csv'));

            $tbody_data[] = [$eguidance_date, $eguidance_type, $eguidance_form, $download_csv];
        }
        $eguidance_data = BaseHtmlLib::tableElement('', $thead_data, $tbody_data);
        $eguidance_data->setAttribute('class', $eguidance_data->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
    }

    /*
     * Future appointments with this user
     *
     *
     * Potremmo avere una classe
     * $agenda = new ADAAgenda($userObj);
     * $appointments = $agenda->futureAppointmentsWithUser($tutoredUserObj->getId());
     *
     */
    $fields_list_Ar = ['data_ora', 'titolo'];
    $clause         = ' data_ora > ' . time()
                  . ' AND id_mittente=' . $tutoredUserObj->getId()
                  . ' AND (flags & ' . ADA_EVENT_CONFIRMED . ')';

    $sort_field     = ' data_ora desc';

    $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
    $msgs_ha = $mh->findMessages(
        $userObj->getId(),
        ADA_MSG_AGENDA,
        $fields_list_Ar,
        $clause,
        $sort_field
    );
    if (AMADataHandler::isError($msgs_ha) || count($msgs_ha) == 0) {
        $appointments_data = new CText('');
    } else {
        $thead_data = [translateFN('Date'), translateFN('Appointment type')];
        $tbody_data = [];
        foreach ($msgs_ha as $appointment) {
            $tbody_data[] = [Utilities::ts2dFN($appointment[0]), ADAEventProposal::removeEventToken($appointment[1])];
        }
        $appointments_data = BaseHtmlLib::tableElement('', $thead_data, $tbody_data);
        $appointments_data->setAttribute('class', $appointments_data->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
    }
    $data = $appointments_data->getHtml()
        . $user_data->getHtml()
        . $service_data->getHtml()
        . $eguidance_data->getHtml()
    ;
}

$label = translateFN('user service details');
$help  = translateFN("Details");

$home_link = CDOMElement::create('a', 'href:tutor.php');
$home_link->addChild(new CText(translateFN("Practitioner's home")));
$module = $home_link->getHtml() . ' > ' . $label;



$content_dataAr = [
  'user_name' => $user_name,
  'user_type' => $user_type,
  'status'    => $status,
  'path'      => $module,
  'label'     => $label,
  'dati'      => $data,
];

ARE::render($layout_dataAr, $content_dataAr);

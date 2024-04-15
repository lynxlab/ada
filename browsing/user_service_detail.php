<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Module\EtherpadIntegration\Utils;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Service\Service;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\ARE;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\Course\CourseInstance;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;

use Lynxlab\ADA\Main\ADAError;

use function \translateFN;

/**
 * Displays information about users
 *
 * @package
 * @author    Stefano Penge <steve@lynxlab.com>
 * @author    Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author    Vito Modena <vito@lynxlab.com>
 * @copyright Copyright (c) 2009, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version   0.1
 */

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Comunica\Event\ADAEventProposal;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\EguidanceSession;
use Lynxlab\ADA\Main\HtmlLibrary\TutorModuleHtmlLib;
use Lynxlab\ADA\Tutor\Eguidance\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  'default';//whoami();

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
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

include_once '../tutor/include/eguidance_tutor_form_functions.inc.php';

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
        //  $tutoredUserObj = MultiPort::findUser($eguidance_session_dataAr['id_utente']);
        $eguidance_session_dataAr['user_fullname'] = $userObj->getFullName();
        $eguidance_session_dataAr['user_country']  = $userObj->getCountry();
        $eguidance_session_dataAr['service_duration'] = '';

        Utils::createCSVFileToDownload($eguidance_session_dataAr);
        /*
         * exits here.
         */
    }
} else {
    // $id_user = DataValidator::isUinteger($_GET['id_user']);
    // $id_course_instance = DataValidator::isUinteger($_GET['id_course_instance']);
    $id_user = $_SESSION['sess_id_user'];
    $id_course_instance = $_SESSION['sess_id_course_instance'];

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

    // $tutoredUserObj = MultiPort::findUser($id_user);
    $user_data = TutorModuleHtmlLib::getEguidanceSessionUserDataTable($userObj);

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
            $eguidance_date = ts2dFN($eguidance_sessionAr['data_ora']);
            $eguidance_type = EguidanceSession::textForEguidanceType($eguidance_sessionAr['tipo_eguidance']);
            $href = 'eguidance_tutor_form.php?event_token=' . $eguidance_sessionAr['event_token'];
            $eguidance_form = CDOMElement::create('a', "href:$href");
            $eguidance_form->addChild(new CText('edit'));

            $href = 'user_service_detail.php?op=csv&event_token=' . $eguidance_sessionAr['event_token'];
            $download_csv = CDOMElement::create('a', "href:$href");
            $download_csv->addChild(new CText('download csv'));

            $tbody_data[] = [$eguidance_date, $eguidance_type, $eguidance_form, $download_csv];
        }
        $eguidance_data = BaseHtmlLib::tableElement('class:' . ADA_SEMANTICUI_TABLECLASS, $thead_data, $tbody_data);
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
                  . ' AND id_mittente=' . $userObj->getId()
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
            $tbody_data[] = [ts2dFN($appointment[0]), ADAEventProposal::removeEventToken($appointment[1])];
        }
        $appointments_data = BaseHtmlLib::tableElement('class:' . ADA_SEMANTICUI_TABLECLASS, $thead_data, $tbody_data);
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
  'edit_profile' => $userObj->getEditProfilePage(),
  'dati'      => $data,
];

ARE::render($layout_dataAr, $content_dataAr);

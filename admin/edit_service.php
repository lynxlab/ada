<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_ADMIN];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_ADMIN => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();  // = admin!

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
AdminHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     * Handle data from $_POST:
     * 1. validate user submitted data
     * 2. if there are errors, display the add user form updated with error messages
     * 3. if there aren't errors, add this user to the common database and to
     *    the tester databases associated with this user.
     */


    /*
     * Validazione dati
     */
    $errorsAr = [];

    if (DataValidator::validateNotEmptyString($_POST['service_name']) === false) {
        $errorsAr['service_name'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['service_description']) === false) {
        $errorsAr['service_description'] = true;
    }

    if (DataValidator::isUinteger($_POST['service_level']) === false) {
        $errorsAr['service_level'] = true;
    }

    if (DataValidator::isUinteger($_POST['service_duration']) === false) {
        $errorsAr['service_duration'] = true;
    }

    if (DataValidator::isUinteger($_POST['service_min_meetings']) === false) {
        $errorsAr['service_min_meetings'] = true;
    }

    if (DataValidator::isUinteger($_POST['service_max_meetings']) === false) {
        $errorsAr['service_max_meetings'] = true;
    }

    if (DataValidator::isUinteger($_POST['service_meeting_duration']) === false) {
        $errorsAr['service_meeting_duration'] = true;
    }

    if (count($errorsAr) > 0) {
        $service_dataAr = $_POST;
        $form = AdminModuleHtmlLib::getEditServiceForm($testersAr, $service_dataAr, $errorsAr);
    } else {
        unset($_POST['submit']);
        $service_dataAr = $_POST;
        $result = $common_dh->setService($_POST['service_id'], $service_dataAr);
        if (AMACommonDataHandler::isError($result)) {
            $errObj = new ADAError($result);
        } else {
            header('Location: ' . $userObj->getHomePage());
            exit();
        }
    }
} else {
    /*
     * Display the add user form
     */
    $id_service = DataValidator::isUinteger($_GET['id_service']);
    if ($id_service !== false) {
        $service_infoAr = $common_dh->getServiceInfo($id_service);
        if (AMACommonDataHandler::isError($service_infoAr)) {
            $errObj = new ADAError($service_infoAr);
        } else {
            $testersAr = [];
            $service_dataAr = [
            'service_id'               => $service_infoAr[0],
            'service_name'             => $service_infoAr[1],
            'service_description'      => $service_infoAr[2],
            'service_level'            => $service_infoAr[3],
            'service_duration'         => $service_infoAr[4],
            'service_min_meetings'     => $service_infoAr[5],
            'service_max_meetings'     => $service_infoAr[6],
            'service_meeting_duration' => $service_infoAr[7],
            ];
            $form = AdminModuleHtmlLib::getEditServiceForm($testersAr, $service_dataAr);
        }
    } else {
        $form = new CText('');
    }
}
$label = translateFN("Modifica servizio");

$help  = translateFN("Da qui l'amministratore puo' apportare modifiche ad un servizio esistente");

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $form->getHtml(),
  'module'       => $label,
];

ARE::render($layout_dataAr, $content_dataAr);

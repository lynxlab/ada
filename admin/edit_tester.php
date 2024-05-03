<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
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

    if (DataValidator::validateNotEmptyString($_POST['tester_name']) === false) {
        $errorsAr['tester_name'] = true;
    }

    if (DataValidator::validateString($_POST['tester_rs']) === false) {
        $errorsAr['tester_rs'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_address']) === false) {
        $errorsAr['tester_address'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_province']) === false) {
        $errorsAr['tester_province'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_city']) === false) {
        $errorsAr['tester_city'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_country']) === false) {
        $errorsAr['tester_country'] = true;
    }

    if (DataValidator::validatePhone($_POST['tester_phone']) === false) {
        $errorsAr['tester_phone'] = true;
    }

    if (DataValidator::validateEmail($_POST['tester_email']) === false) {
        $errorsAr['tester_email'] = true;
    }

    if (DataValidator::validateString($_POST['tester_desc']) === false) {
        $errorsAr['tester_desc'] = true;
    }

    if (DataValidator::validateString($_POST['tester_resp']) === false) {
        $errorsAr['tester_resp'] = true;
    }

    if (DataValidator::validateTestername($_POST['tester_pointer'], MULTIPROVIDER) === false) {
        $errorsAr['tester_pointer'] = true;
    }

    if (array_key_exists('tester_iban', $_POST) && strlen(trim($_POST['tester_iban'])) > 0 && DataValidator::validateIban(trim($_POST['tester_iban'])) === false) {
        $errorsAr['tester_iban'] = true;
    }

    if (count($errorsAr) > 0) {
        $tester_dataAr = $_POST;
        $form = AdminModuleHtmlLib::getEditTesterForm($testersAr, $tester_dataAr, $errorsAr);
    } else {
        unset($_POST['submit']);
        $tester_dataAr = $_POST;

        $result = $common_dh->setTester($tester_dataAr['tester_id'], $tester_dataAr);
        if (AMACommonDataHandler::isError($result)) {
            $errObj = new ADAError($result);
            $form = new CText('');
        } else {
            header('Location: ' . HTTP_ROOT_DIR . '/admin/tester_profile.php?id_tester=' . $tester_dataAr['tester_id']);
            exit();
        }
    }
} else {
    /*
     * Display the add user form
     */
    $id_tester = DataValidator::checkInputValues('id_tester', 'Integer', INPUT_GET);
    if ($id_tester !== false) {
        $tester_infoAr = $common_dh->getTesterInfoFromId($id_tester);
        if (AMACommonDataHandler::isError($tester_infoAr)) {
            $errObj = new ADAError($tester_infoAr);
        } else {
            $testersAr = [];
            $tester_dataAr = [
            'tester_id'       => $tester_infoAr[0],
            'tester_name'     => $tester_infoAr[1],
            'tester_rs'       => $tester_infoAr[2],
            'tester_address'  => $tester_infoAr[3],
            'tester_city'     => $tester_infoAr[4],
            'tester_province' => $tester_infoAr[5],
            'tester_country'  => $tester_infoAr[6],
            'tester_phone'    => $tester_infoAr[7],
            'tester_email'    => $tester_infoAr[8],
            'tester_resp'     => $tester_infoAr[9],
            'tester_pointer'  => $tester_infoAr[10],
            'tester_desc'     => $tester_infoAr[11],
            'tester_iban'     => $tester_infoAr[12],
            ];

            $form = AdminModuleHtmlLib::getEditTesterForm($testersAr, $tester_dataAr);
        }
    } else {
        $form = new CText('');
    }
}
$label = translateFN("Modifica provider");

$help  = translateFN("Da qui l'amministratore puo' apportare modifiche ad un provider esistente");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));
$tester_profile_link = CDOMElement::create('a', 'href:tester_profile.php?id_tester=' . $id_tester);
$tester_profile_link->addChild(new CText(translateFN("Profilo del provider")));
$module = $home_link->getHtml() . ' > ' . $tester_profile_link->getHtml() . ' > ' . $label;

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $form->getHtml(),
  'module'       => $module,
];

ARE::render($layout_dataAr, $content_dataAr);

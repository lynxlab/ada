<?php

/**
 * Add user - this module provides add user functionality
 *
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\Admin\AdminUtils;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAAdmin;
use Lynxlab\ADA\Main\User\ADAAuthor;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\User\ADASwitcher;
use Lynxlab\ADA\Main\User\ADAUser;

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

include_once 'include/AdminUtils.inc.php';
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

    if ($_POST['user_tester'] == 'none') {
        $errorsAr['user_tester'] = true;
    }

    if (DataValidator::is_uinteger($_POST['user_type']) === false) {
        $errorsAr['user_type'] = true;
    }

    if (DataValidator::validate_firstname($_POST['user_firstname']) === false) {
        $errorsAr['user_firstname'] = true;
    }

    if (DataValidator::validate_lastname($_POST['user_lastname']) === false) {
        $errorsAr['user_lastname'] = true;
    }

    if (DataValidator::validate_email($_POST['user_email']) === false) {
        $errorsAr['user_email'] = true;
    }

    if (DataValidator::validate_username($_POST['user_username']) === false) {
        $errorsAr['user_username'] = true;
    }

    if (DataValidator::validate_password($_POST['user_password'], $_POST['user_passwordcheck']) === false) {
        $errorsAr['user_password'] = true;
    }

    if (DataValidator::validate_string($_POST['user_address']) === false) {
        $errorsAr['user_address'] = true;
    }

    if (DataValidator::validate_string($_POST['user_city']) === false) {
        $errorsAr['user_city'] = true;
    }

    if (DataValidator::validate_string($_POST['user_province']) === false) {
        $errorsAr['user_province'] = true;
    }

    if (DataValidator::validate_string($_POST['user_country']) === false) {
        $errorsAr['user_country'] = true;
    }

    if (DataValidator::validate_string($_POST['user_fiscal_code']) === false) {
        $errorsAr['user_fiscal_code'] = true;
    }

    if (DataValidator::validate_birthdate($_POST['user_birthdate']) === false) {
        $errorsAr['user_birthdate'] = true;
    }

    if (DataValidator::validate_not_empty_string($_POST['user_birthcity']) === false) {
        $errorsAr['user_birthcity'] = true;
    }

    if (DataValidator::validate_string($_POST['user_birthprovince']) === false) {
        $errorsAr['user_birthprovince'] = true;
    }

    if (DataValidator::validate_string($_POST['user_sex']) === false) {
        $errorsAr['user_sex'] = true;
    }

    if (DataValidator::validate_phone($_POST['user_phone']) === false) {
        $errorsAr['user_phone'] = true;
    }


    if (count($errorsAr) > 0) {
        unset($_POST['submit']);
        $user_dataAr = $_POST;
        $testers_dataAr = $common_dh->get_all_testers(['id_tester','nome']);

        if (AMA_Common_DataHandler::isError($testers_dataAr)) {
            $errObj = new ADA_Error($testersAr, translateFN("Errore nell'ottenimento delle informazioni sui provider"));
        } else {
            $testersAr = [];
            foreach ($testers_dataAr as $tester_dataAr) {
                $testersAr[$tester_dataAr['puntatore']] = $tester_dataAr['nome'];
            }
            $form = AdminModuleHtmlLib::getAddUserForm($testersAr, $user_dataAr, $errorsAr);
        }
    } else {
        if ($_POST['user_layout'] == 'none') {
            $user_layout = '';
        } else {
            $user_layout = $_POST['user_layout'];
        }

        $user_dataAr = [
        'nome'      => $_POST['user_firstname'],
        'cognome'   => $_POST['user_lastname'],
        'tipo'      => $_POST['user_type'],
        'email'     => $_POST['user_email'],
        'username'  => $_POST['user_username'],
        'layout'    => $user_layout,
        'indirizzo' => $_POST['user_address'],
        'citta'     => $_POST['user_city'],
        'provincia' => $_POST['user_province'],
        'nazione'   => $_POST['user_country'],
        'codice_fiscale' => $_POST['user_fiscal_code'],
        'datanascita'    => $_POST['user_birthdate'],
        'birthdate'      => $_POST['user_birthdate'],
        'sesso'          => $_POST['user_sex'],
        'telefono'               => $_POST['user_phone'],
        'stato'                  => 0,//DataValidator::validate_string($_POST['user_status'])
        'birthcity'      => $_POST['user_birthcity'],
        'birthprovince'  => $_POST['user_birthprovince'],
        ];

        switch ($_POST['user_type']) {
            case AMA_TYPE_STUDENT:
                $userObj = new ADAUser($user_dataAr);
                break;
            case AMA_TYPE_AUTHOR:
                $userObj = new ADAAuthor($user_dataAr);
                break;
            case AMA_TYPE_SUPERTUTOR:
            case AMA_TYPE_TUTOR:
                $userObj = new ADAPractitioner($user_dataAr);
                break;
            case AMA_TYPE_SWITCHER:
                $userObj = new ADASwitcher($user_dataAr);
                break;
            case AMA_TYPE_ADMIN:
                $userObj = new ADAAdmin($user_dataAr);
                break;
        }
        $userObj->setPassword($_POST['user_password']);
        $result = MultiPort::addUser($userObj, [$_POST['user_tester']]);
        if ($result > 0) {
            if ($userObj instanceof ADAAuthor) {
                AdminUtils::performCreateAuthorAdditionalSteps($userObj->getId());
            } elseif ($userObj instanceof ADASwitcher || $userObj instanceof ADAPractitioner) {
                AdminUtils::createUploadDirForUser($userObj->getId());
            }
            $message = translateFN("Utente aggiunto con successo");
            header('Location: ' . $userObj->getHomePage($message));
            exit();
        } else {
            /*
             * Qui bisogna ricreare il form per la registrazione passando in $errorsAr['registration_error']
             * $result e portando li' dentro lo switch su $result
             */
            $errorsAr['registration_error'] = $result;

            unset($_POST['submit']);
            $user_dataAr = $_POST;
            $testers_dataAr = $common_dh->get_all_testers(['id_tester','nome']);

            if (AMA_Common_DataHandler::isError($testers_dataAr)) {
                $errObj = new ADA_Error($testersAr, translateFN("Errore nell'ottenimento delle informazioni sui tester"));
            } else {
                $testersAr = [];
                foreach ($testers_dataAr as $tester_dataAr) {
                    $testersAr[$tester_dataAr['puntatore']] = $tester_dataAr['nome'];
                }
                $form = AdminModuleHtmlLib::getAddUserForm($testersAr, $user_dataAr, $errorsAr);
            }
        }
    }
} else {
    /*
     * Display the add user form
     */
    $testers_dataAr = $common_dh->get_all_testers(['id_tester','nome']);

    if (AMA_Common_DataHandler::isError($testers_dataAr)) {
        $errObj = new ADA_Error($testersAr, translateFN("Errore nell'ottenimento delle informazioni sui provider"));
    } else {
        $testersAr = [];
        foreach ($testers_dataAr as $tester_dataAr) {
            $testersAr[$tester_dataAr['puntatore']] = $tester_dataAr['nome'];
        }
        $form = AdminModuleHtmlLib::getAddUserForm($testersAr);
    }
}
$label = translateFN("Aggiunta utente");
$help  = translateFN("Da qui l'amministratore puo' creare un nuovo utente");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));
$module = $home_link->getHtml() . ' > ' . $label;

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
];

$optionsAr['onload_func'] = 'initDateField();';

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $form->getHtml(),
  'module'       => $module,
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

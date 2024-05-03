<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

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
$self = 'default';// whoami();  // = admin!

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

    if (isset($_POST['user_tester']) && $_POST['user_tester'] == 'none') {
        $errorsAr['user_tester'] = true;
    }

    if (DataValidator::isUinteger($_POST['user_type']) === false) {
        $errorsAr['user_type'] = true;
    }

    if (DataValidator::validateFirstname($_POST['user_firstname']) === false) {
        $errorsAr['user_firstname'] = true;
    }

    if (DataValidator::validateLastname($_POST['user_lastname']) === false) {
        $errorsAr['user_lastname'] = true;
    }

    if (DataValidator::validateEmail($_POST['user_email']) === false) {
        $errorsAr['user_email'] = true;
    }

    if (DataValidator::validateUsername($_POST['user_username']) === false) {
        $errorsAr['user_username'] = true;
    }

    if (trim($_POST['user_password']) != '') {
        if (DataValidator::validatePassword($_POST['user_password'], $_POST['user_passwordcheck']) === false) {
            $errorsAr['user_password'] = true;
        }
    }

    if (DataValidator::validateString($_POST['user_address']) === false) {
        $errorsAr['user_address'] = true;
    }

    if (DataValidator::validateString($_POST['user_city']) === false) {
        $errorsAr['user_city'] = true;
    }

    if (DataValidator::validateString($_POST['user_province']) === false) {
        $errorsAr['user_province'] = true;
    }

    if (DataValidator::validateString($_POST['user_country']) === false) {
        $errorsAr['user_country'] = true;
    }

    if (DataValidator::validateString($_POST['user_fiscal_code']) === false) {
        $errorsAr['user_fiscal_code'] = true;
    }

    if (DataValidator::validateBirthdate($_POST['user_birthdate']) === false) {
        $errorsAr['user_birthdate'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['user_birthcity']) === false) {
        $errorsAr['user_birthcity'] = true;
    }

    if (DataValidator::validateString($_POST['user_birthprovince']) === false) {
        $errorsAr['user_birthprovince'] = true;
    }

    if (DataValidator::validateString($_POST['user_sex']) === false) {
        $errorsAr['user_sex'] = true;
    }

    if (DataValidator::validatePhone($_POST['user_phone']) === false) {
        $errorsAr['user_phone'] = true;
    }


    if (count($errorsAr) > 0) {
        unset($_POST['submit']);
        $user_dataAr = $_POST;
        $testers_dataAr = $common_dh->getAllTesters(['id_tester','nome']);

        if (AMACommonDataHandler::isError($testers_dataAr)) {
            $errObj = new ADAError($testersAr, translateFN("Errore nell'ottenimento delle informazioni sui provider"));
        } else {
            $testersAr = [];
            foreach ($testers_dataAr as $tester_dataAr) {
                $testersAr[$tester_dataAr['puntatore']] = $tester_dataAr['nome'];
            }
            $form = AdminModuleHtmlLib::getEditUserForm($testersAr, $user_dataAr, $errorsAr);
        }
    } else {
        if ($_POST['user_layout'] == 'none') {
            $user_layout = '';
        } else {
            $user_layout = $_POST['user_layout'];
        }

        $userToEditObj = MultiPort::findUser($_POST['user_id']);

        /*
         * Update user fields
         */

        $userToEditObj->setFirstName($_POST['user_firstname']);
        $userToEditObj->setLastName($_POST['user_lastname']);
        $userToEditObj->setEmail($_POST['user_email']);
        //$userToEditObj->setUsername($_POST['user_username']);
        if (trim($_POST['user_password']) != '') {
            $userToEditObj->setPassword($_POST['user_password']);
        }
        $userToEditObj->setLayout($user_layout);
        $userToEditObj->setAddress($_POST['user_address']);
        $userToEditObj->setCity($_POST['user_city']);
        $userToEditObj->setProvince($_POST['user_province']);
        $userToEditObj->setCountry($_POST['user_country']);
        $userToEditObj->setFiscalCode($_POST['user_fiscal_code']);
        $userToEditObj->setBirthDate($_POST['user_birthdate']);
        $userToEditObj->setGender($_POST['user_sex']);
        $userToEditObj->setPhoneNumber($_POST['user_phone']);
        $userToEditObj->setBirthCity($_POST['user_birthcity']);
        $userToEditObj->setBirthProvince($_POST['user_birthprovince']);

        if ($userToEditObj instanceof ADAPractitioner) {
            $userToEditObj->setProfile($_POST['user_profile']);
        }


        MultiPort::setUser($userToEditObj, [], true);

        $navigationHistoryObj = $_SESSION['sess_navigation_history'];
        $location = $navigationHistoryObj->lastModule();
        header('Location: ' . $location);
        exit();
    }
} else {
    /*
     * Display the add user form
     */
    $id_user = DataValidator::checkInputValues('id_user', 'Integer', INPUT_GET);
    if ($id_user === false) {
        $form = new CText('');
    } else {
        $userToEditObj = MultiPort::findUser($id_user);

        $user_dataAr = $userToEditObj->toArray();

        $testers_for_userAr = $common_dh->getTestersForUser($id_user);
        /*
         * FIXME: selects just one tester. if the user is of type ADAUser
         * we have to display all the associated testers.
         */
        if (!AMACommonDataHandler::isError($testers_for_userAr) && count($testers_for_userAr) > 0) {
            $tester = $testers_for_userAr[0];
        } else {
            $tester = null;
        }

        $dataAr = [
        'user_id' => $user_dataAr['id_utente'],
        'user_firstname' => $user_dataAr['nome'],
        'user_lastname' => $user_dataAr['cognome'],
        'user_type' => $user_dataAr['tipo'],
        'user_email' => $user_dataAr['e_mail'],
        'user_username' => $user_dataAr['username'],
        'user_layout' => $user_dataAr['layout'],
        'user_address' => $user_dataAr['indirizzo'],
        'user_city' => $user_dataAr['citta'],
        'user_province' => $user_dataAr['provincia'],
        'user_country' => $user_dataAr['nazione'],
        'user_fiscal_code' => $user_dataAr['codice_fiscale'],
        'user_birthdate' => $user_dataAr['birthdate'],
        'user_sex' => $user_dataAr['sesso'],
        'user_phone' => $user_dataAr['telefono'],
     //'user_status'=> $user_dataAr['stato']
        'user_tester' => $tester,
        'user_profile' => $user_dataAr['profilo'] ?? null,
        'user_birthcity' => $user_dataAr['birthcity'],
        'user_birthprovince' => $user_dataAr['birthprovince'],
        ];


        $testers_dataAr = $common_dh->getAllTesters(['id_tester','nome']);

        if (AMACommonDataHandler::isError($testers_dataAr)) {
            $errObj = new ADAError($testersAr, translateFN("Errore nell'ottenimento delle informazioni sui provider"));
        } else {
            $testersAr = [];
            foreach ($testers_dataAr as $tester_dataAr) {
                $testersAr[$tester_dataAr['puntatore']] = $tester_dataAr['nome'];
            }




            $form = AdminModuleHtmlLib::getEditUserForm($testersAr, $dataAr);
        }
    }
}
$label = translateFN("Modifica dati utente");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));


if (isset($id_tester)) {
    $tester_profile_link = CDOMElement::create('a', 'href:tester_profile.php?id_tester=' . $id_tester);
    $tester_profile_link->addChild(new CText(translateFN("Profilo del provider")));
    $list_users_link = CDOMElement::create('a', 'href:list_users.php?id_tester=' . $id_tester . '&page=' . $page);
    $list_users_link->addChild(new CText(translateFN("Lista utenti")));
}

$module = $home_link->getHtml();
if (isset($tester_profile_link)) {
    $module .= ' > ' . $tester_profile_link->getHtml();
}
if (isset($list_users_link)) {
    $module .= ' > ' . $list_users_link->getHtml();
}
$module .= ' > ' . $label;

$help  = translateFN("Lista degli utenti presenti sul provider");

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $form->getHtml(),
  'module'       => $module,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
];

$optionsAr['onload_func'] = 'initDateField();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

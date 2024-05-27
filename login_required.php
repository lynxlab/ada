<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserRegistrationForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\UserModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprAcceptPoliciesForm;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use Lynxlab\ADA\Module\Login\AbstractLogin;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

require_once realpath(__DIR__) . '/config_path.inc.php';

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
$allowedUsersAr = [AMA_TYPE_VISITOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_VISITOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);
$self = Utilities::whoami();
/*
 * YOUR CODE HERE
 */
$redirectURL = $_SESSION['subscription_page'];
$navigationHistoryObj = $_SESSION['sess_navigation_history'];


if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     *
    $navigationHistoryObj = $_SESSION['sess_navigation_history'];
    $lastModule = $navigationHistoryObj->lastModule();
    print_r($form);
     *
     */
    /**
     * Perform login
     */
    if (isset($gdprAccepted) && intval($gdprAccepted) === 1 &&  array_key_exists(GdprPolicy::SESSIONKEY, $_SESSION) && array_key_exists('post', $_SESSION[GdprPolicy::SESSIONKEY])) {
        extract($_SESSION[GdprPolicy::SESSIONKEY]['post']);
    }
    unset($_SESSION[GdprPolicy::SESSIONKEY]);
    if (isset($p_login) || (isset($selectedLoginProvider) && strlen($selectedLoginProvider) > 0)) {
        if (isset($p_login)) {
            $username = DataValidator::validateUsername($p_username);
            $password = DataValidator::validatePassword($p_password, $p_password);
        } else {
            $username = DataValidator::validateNotEmptyString($p_username);
            $password = DataValidator::validateNotEmptyString($p_password);
        }

        if (!isset($p_remindme)) {
            $p_remindme = false;
        }

        if (isset($p_login)) {
            if ($username !== false && $password !== false) {
                //User has correctly inserted un & pw
                $userObj = MultiPort::loginUser($username, $password);
                $loginObj = null;
            } else {
                // Utente non loggato perche' informazioni in username e password non valide
                // es. campi vuoti o contenenti caratteri non consentiti.
                $login_error_message = translateFN("Username  e/o password non valide");
            }
        } elseif (
            ModuleLoaderHelper::isLoaded('LOGIN') &&
                isset($selectedLoginProvider) && strlen($selectedLoginProvider) > 0
        ) {
            $className = AbstractLogin::getNamespaceName() . "\\" . $selectedLoginProvider;
            if (class_exists($className)) {
                $loginProviderID = $selectedLoginProviderID ?? null;
                $loginObj = new $className($selectedLoginProviderID);
                $userObj = $loginObj->doLogin($username, $password, $p_remindme, $p_selected_language);
                if ((is_object($userObj)) && ($userObj instanceof Exception)) {
                    // try the adalogin before giving up the login process
                    $lastTry = MultiPort::loginUser($username, $password);
                    if ((is_object($lastTry)) && ($lastTry instanceof ADALoggableUser)) {
                        $loginObj = null;
                        $userObj = $lastTry;
                    }
                }
            }
        }

        if ((is_object($userObj)) && ($userObj instanceof ADALoggableUser)) {
            if (isset($_SESSION['subscription_page'])) {
                $redirectURL = $_SESSION['subscription_page'];
                unset($_SESSION['subscription_page']);
            } else {
                $redirectURL = $navigationHistoryObj->lastModule();
            }

            if (!ADALoggableUser::setSessionAndRedirect($userObj, $p_remindme, $p_selected_language, $loginObj, $redirectURL)) {
                //  Utente non loggato perché stato <> ADA_STATUS_REGISTERED
                $login_error_message = translateFN("Utente non abilitato");
            }
        } elseif ((is_object($userObj)) && ($userObj instanceof Exception)) {
            $login_error_message = $userObj->getMessage();
            if ($userObj->getCode() !== 0) {
                $login_error_message .= ' (' . $userObj->getCode() . ')';
            }
        } else {
            // Utente non loggato perché coppia username password non corretta
            $login_error_message = translateFN("Username  e/o password non valide");
        }
    }
    if (isset($login_error_message)) {
        $data = new CText($login_error_message);
    }
}
/**
 * Negotiate login page language
 */
Translator::loadSupportedLanguagesInSession();
$supported_languages = Translator::getSupportedLanguages();
$login_page_language_code = Translator::negotiateLoginPageLanguage($lang_get ?? null);
$_SESSION['sess_user_language'] = $login_page_language_code;

$form_action = HTTP_ROOT_DIR ;
$form_action .= '/' . Utilities::whoami() . '.php';
$data = UserModuleHtmlLib::loginForm($form_action, $supported_languages, $login_page_language_code, $login_error_message ?? '');

$registration_action = HTTP_ROOT_DIR . '/browsing/registration.php';
$cod = false;
$registration_data = new UserRegistrationForm($cod, $registration_action);
//    $form = new UserRegistrationForm();
//    $data = $form->render();

$help = translateFN('Per poter proseguire, è necessario che tu sia un utente registrato.');
$title = translateFN('Richiesta di autenticazione');

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
];
$layout_dataAr['CSS_filename'] = [ ROOT_DIR . '/layout/' . $_SESSION['sess_userObj']->template_family . '/css/main/index.css' ];
if (ModuleLoaderHelper::isLoaded('LOGIN')) {
    $layout_dataAr['CSS_filename'] = array_merge(
        $layout_dataAr['CSS_filename'],
        [
                    MODULES_LOGIN_PATH . '/layout/support/login-form.css',
            ]
    );
}

$optionsAr['onload_func'] = 'initDateField();';

if (ModuleLoaderHelper::isLoaded('GDPR') === true && isset($registration_data)) {
    $gdprApi = new GdprAPI();
    GdprAcceptPoliciesForm::addPolicies($registration_data, [
        'policies' => $gdprApi->getPublishedPolicies(),
        'extraclass' => 'ui form',
        'isRegistration' => true,
    ]);

    $layout_dataAr['CSS_filename'][] = MODULES_GDPR_PATH . '/layout/' . ADA_TEMPLATE_FAMILY . '/css/acceptPolicies.css';
    $layout_dataAr['JS_filename'][] =  MODULES_GDPR_PATH . '/js/acceptPolicies.js';
    $layout_dataAr['JS_filename'][] = ROOT_DIR . '/js/browsing/registration.js';
    $optionsAr['onload_func'] .= 'initRegistration();';
}

if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
    $layout_dataAr['JS_filename'][] = MODULES_SECRETQUESTION_PATH . '/js/modules_define.js.php';
}

$registrationDataHtml = '';
if (is_object($registration_data)) {
    $registrationDataHtml = $registration_data->getHtml();
}

$content_dataAr = [
    'course_title' => $title,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $title,
    'help' => $help,
    'data' => $data->getHtml(),
    'registration_data' => $registrationDataHtml,
];

/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\UserModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use Lynxlab\ADA\Module\Login\AbstractLogin;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

if (is_file(realpath(__DIR__) . '/config_path.inc.php')) {
    require_once realpath(__DIR__) . '/config_path.inc.php';
} else {
    header('Location: install.php', true, 307);
    die();
}

/**
 * redirect to install if ADA is NOT installed, either with install script or manually
 */
if (!defined('ROOT_DIR') || !is_dir(ROOT_DIR . '/clients') || count(glob(ROOT_DIR . "/clients/*/client_conf.inc.php")) === 0) {
    header('Location: install.php', true, 307);
    die();
}

/**
 * Clear node and layout variable in $_SESSION
 * $_SESSION was destroyed, so we do not need to clear data in session.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN, AMA_TYPE_SWITCHER];
/**
 * Performs basic controls before entering this module
 */

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami(); // index
$common_dh = AMACommonDataHandler::getInstance();

/**
 * Template Family
 */
$template_family = ADA_TEMPLATE_FAMILY; // default template famliy
$_SESSION['sess_template_family'] = $template_family;

/**
 * LAYOUT
 */
$layout_dataAr = [
    'node_type'      => null,
    'family'         => $template_family,
    'node_author_id' => null,
    'node_course_id' => null,
    'module_dir'     => null,
];

$lang_get = DataValidator::checkInputValues('lang', 'Language', INPUT_GET, null);
/**
 * sets language if it is not multiprovider
 * if commented, then language is handled by ranslator::negotiateLoginPageLanguage
 * that will check if the browser language is supported by ADA and set it accordingly
 */

// if (!MULTIPROVIDER && defined('PROVIDER_LANGUAGE')) $lang_get = PROVIDER_LANGUAGE;


/**
 * Negotiate login page language
 */
Translator::loadSupportedLanguagesInSession();
$supported_languages = Translator::getSupportedLanguages();
$login_page_language_code = Translator::negotiateLoginPageLanguage($lang_get);
$_SESSION['sess_user_language'] = $login_page_language_code;

/**
 *
 */
$_SESSION['ada_remote_address'] = Utilities::getUserIpAddr();

/**
 * giorgio 12/ago/2013
 * if it isn't multiprovider, loads proper files into clients directories
 */
if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
    $files_dir = $root_dir . '/clients/' . $GLOBALS['user_provider'];
} else {
    $files_dir = $root_dir;
}

/*
 * Load news file
 */
$newsfile = 'news_' . $login_page_language_code . '.txt';
$infofile = 'info_' . $login_page_language_code . '.txt';
$helpfile = 'help_' . $login_page_language_code . '.txt';

$infomsg = '';
$newsmsg = '';
$hlpmsg  = '';

if ($newsmsg == '') {
    $newsfile = $files_dir . '/docs/news/' . $newsfile; //  txt files in ada browsing directory
    if ($fid = @fopen($newsfile, 'r')) {
        while (!feof($fid)) {
            $newsmsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $newsmsg = '<p>' . translateFN("File news non trovato") . '</p>';
    }
}

if ($hlpmsg == '') {
    $helpfile = $files_dir . '/docs/help/' . $helpfile;  //  txt files in ada browsing directory
    if ($fid = @fopen($helpfile, 'r')) {
        while (!feof($fid)) {
            $hlpmsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $hlpmsg = '<p>' . translateFN("File help non trovato") . '</p>';
    }
}

if ($infomsg == '') {
    $infofile = $files_dir . '/docs/info/' . $infofile;  //  txt files in ada browsing directory
    if ($fid = @fopen($infofile, 'r')) {
        while (!feof($fid)) {
            $infomsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $infomsg = '<p>' . translateFN("File info non trovato") . '</p>';
    }
}


$login_error_message = '';
/**
 * Perform login
 */
if (class_exists(GdprPolicy::class, true)) {
    if (isset($gdprAccepted) && intval($gdprAccepted) === 1 &&  array_key_exists(GdprPolicy::SESSIONKEY, $_SESSION) && array_key_exists('post', $_SESSION[GdprPolicy::SESSIONKEY])) {
        extract($_SESSION[GdprPolicy::SESSIONKEY]['post']);
    }
    unset($_SESSION[GdprPolicy::SESSIONKEY]);
}

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
        if (!ADALoggableUser::setSessionAndRedirect($userObj, $p_remindme, $p_selected_language, $loginObj)) {
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

/**
 * Show login page
 */
$form_action = HTTP_ROOT_DIR ;
$form_action .= '/index.php';
$login = UserModuleHtmlLib::loginForm($form_action, $supported_languages, $login_page_language_code, $login_error_message);

//$login = UserModuleHtmlLib::loginForm($supported_languages,$login_page_language_code, $login_error_message);
/**
 * giorgio 12/ago/2013
 * set up proper link path and tester for getting the news in a multiproivder environment
 */
if (!MULTIPROVIDER) {
    if (isset($GLOBALS['user_provider']) && !empty($GLOBALS['user_provider'])) {
        $testerName = $GLOBALS['user_provider'];
    } else {
        /**
         * overwrite $newsmsg with generated available providers listing
         */
        $allTesters = $common_dh->getAllTesters(['nome']);
        $addHtml = false;

        foreach ($allTesters as $aTester) {
            // skip testers having punatore like 'clientXXX'
            if (
                !preg_match('/^(?:client)[0-9]{1,2}$/', $aTester['puntatore']) &&
                is_dir(ROOT_DIR . '/clients/' . $aTester['puntatore'])
            ) {
                if (!$addHtml) {
                    $providerListUL = CDOMElement::create('ol');
                }
                $addHtml = true;
                $testerLink = CDOMElement::create('a', 'href:' . preg_replace("/(http[s]?:\/\/)(\w+)[.]{1}(\w+)/", "$1" . $aTester['puntatore'] . ".$3", HTTP_ROOT_DIR));
                $testerLink->addChild(new CText($aTester['nome']));

                $providerListElement = CDOMElement::create('li');
                $providerListElement->addChild($testerLink);
                $providerListUL->addChild($providerListElement);
            }
        }
        $newsmsg = $addHtml ? $providerListUL->getHtml() : translateFN('Nessun fornitore di servizi &egrave; stato configurato');
    }
} else {
    $testers = $_SESSION['sess_userObj']->getTesters();
    $testerName = (!is_null($testers) && count($testers) > 0) ? $testers[0] : null;
} // end if (!MULTIPROVIDER)

$forget_div  = CDOMElement::create('div');
$forget_linkObj = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR . '/browsing/forget.php?lan=' . $_SESSION['sess_user_language']);
$forget_linkObj->addChild(new CText(translateFN("Did you forget your password?")));
$forget_link = $forget_linkObj->getHtml();
//  $status = translateFN('Explore the web site or register and ask for a practitioner');
$status = "";

$message = CDOMElement::create('div');
if (isset($GLOBALS['moduleerrors'])) {
    $message->addChild(new CText($GLOBALS['moduleerrors']));
    unset($GLOBALS['moduleerrors']);
}
$getMessage = DataValidator::checkInputValues('message', 'Message', INPUT_GET);
if ($getMessage !== false) {
    $message->addChild(new CText($getMessage));
} else {
    $expired = DataValidator::checkInputValues('expired', 'Integer', INPUT_GET);
    if (($expired !== false) && intval($expired) === 1) {
        $sessExpMsg = '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
        $sessExpMsg .= '<div class="header">' . translateFN('La tua sessione è scaduta') . '</div>';
        $sessExpMsg .= '<p>' . translateFN('Rifare il login') . '</p>';
        $sessExpMsg .= '</div></div>';
        $message->addChild(new CText($sessExpMsg));
    }
}

/**
 *  @author giorgio 25/feb/2014
 *
 *  News from public course indicated in PUBLIC_COURSE_ID_FOR_NEWS
 *  are loaded in the bottomnews template_field with a widget, pls
 *  see widgets/main/index.xml file
 */

$content_dataAr = [
    'form' => $login->getHtml() . $forget_link,
    'newsmsg' => $newsmsg,
    'helpmsg' => $hlpmsg,
    'infomsg' => $infomsg,
    // 'bottomnews' => $bottomnewscontent,
    'status' => $status,
    'message' => $message->getHtml(),
];

if (isset($_SESSION['sess_userObj']) && $_SESSION['sess_userObj']-> getType() != AMA_TYPE_VISITOR) {
    $userObj = $_SESSION['sess_userObj'];
    $user_type = $userObj->getTypeAsString();
    $user_name = $userObj->nome;
    $user_full_name = $userObj->getFullName();

    $imgAvatar = $userObj->getAvatar();
    $avatar = CDOMElement::create('img', 'src:' . $imgAvatar);
    $avatar->setAttribute('class', 'img_user_avatar');

    $content_dataAr['user_modprofilelink'] = $userObj->getHomePage(); //getEditProfilePage();
    $content_dataAr['user_avatar'] = $avatar->getHtml();
    $content_dataAr['status'] = translateFN('logged in');
    $content_dataAr['user_name'] = $user_name;
    $content_dataAr['user_full_name'] = $user_full_name;
    $content_dataAr['user_type'] = $user_type;

    unset($content_dataAr['form']);
    $onload_function = 'initDoc(true);';
} else {
    $onload_function = 'initDoc();';
    $content_dataAr['form'] = $login->getHtml() . $forget_link;
    if (isset($content_dataAr['user_modprofilelink'])) {
        unset($content_dataAr['user_modprofilelink']);
    }
    if (isset($content_dataAr['user_avatar'])) {
        unset($content_dataAr['user_avatar']);
    }
    if (isset($content_dataAr['user_name'])) {
        unset($content_dataAr['user_name']);
    }
    if (isset($content_dataAr['user_type'])) {
        unset($content_dataAr['user_type']);
    }
}
/**
 * @author giorgio 26/set/2013
 *
 * if you have some widget in the page and need to
 * pass some parameter to it, you can do it this way:
 *
 * $layout_dataAr['widgets']['<template_field_name>'] = array ("<param_name>"=>"<param_value>");
 */

/**
 * Sends data to the rendering engine
 *
 * @author giorgio 25/set/2013
 * REMEMBER!!!! If there's a widgets/main/index.xml file
 * and the index.tpl has some template_field for the widget
 * it will be AUTOMAGICALLY filled in!!
 */
// ARE::render($layout_dataAr,$content_dataAr);
$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
        ROOT_DIR . "/js/main/index.js",
];
/**
 * @author giorgio
 * include the jQuery and uniform css for proper styling
 */
$layout_dataAr['CSS_filename'] =  [
        JQUERY_UI_CSS,
];
if (ModuleLoaderHelper::isLoaded('LOGIN')) {
    $layout_dataAr['CSS_filename'] = array_merge(
        $layout_dataAr['CSS_filename'],
        [
        MODULES_LOGIN_PATH . '/layout/support/login-form.css',
        ]
    );
}

$optionsAr['onload_func'] = $onload_function;

ARE::render($layout_dataAr, $content_dataAr, null, ($optionsAr ?? null));

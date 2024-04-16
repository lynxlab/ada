<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\UserModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Destroy session
 */
session_start();
session_unset();
session_destroy();

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 * $_SESSION was destroyed, so we do not need to clear data in session.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN];
/**
 * Performs basic controls before entering this module
 */

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  index;
include_once 'include/' . $self . '_functions.inc.php';



/**
 * Negotiate login page language
 */
Translator::loadSupportedLanguagesInSession();
$supported_languages = Translator::getSupportedLanguages();
$login_page_language_code = Translator::negotiateLoginPageLanguage();
$_SESSION['sess_user_language'] = $login_page_language_code;

/**
 * Track kiosk accesses
 */
$_SESSION['ada_access_from'] = ADA_KIOSK_ACCESS;
$_SESSION['ada_remote_address'] = $_SERVER['REMOTE_ADDR'];

/*
 * Load news file
 */
$newsfile = 'news_' . $login_page_language_code . '.txt';
$infofile = 'info_' . $login_page_language_code . '.txt';
$helpfile = 'help_' . $login_page_language_code . '.txt';

/*
   $infomsg = '';
   $newsmsg = '';
   $hlpmsg = '';
*/

if ($newsmsg == '') {
    $newsfile = $root_dir . "/browsing/" . $newsfile; //  txt files in ada browsing directory
    if ($fid = @fopen($newsfile, 'r')) {
        while (!feof($fid)) {
            $newsmsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $newsmsg = translateFN("File news non trovato");
    }
}

if ($hlpmsg == '') {
    $helpfile = $root_dir . "/browsing/" . $helpfile;  //  txt files in ada browsing directory
    if ($fid = @fopen($helpfile, 'r')) {
        while (!feof($fid)) {
            $hlpmsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $hlpmsg = translateFN("File help non trovato");
    }
}

if ($infomsg == '') {
    $infofile = $root_dir . "/browsing/" . $infofile;  //  txt files in ada browsing directory
    if ($fid = @fopen($infofile, 'r')) {
        while (!feof($fid)) {
            $infomsg .= fread($fid, 4096);
        }
        fclose($fid);
    } else {
        $infomsg = translateFN("File info non trovato");
    }
}



/**
 * Perform login
 */
if (isset($p_login)) {
    $username = DataValidator::validateUsername($p_username);
    $password = DataValidator::validatePassword($p_password, $p_password);

    if ($username !== false && $password !== false) {
        $userObj = MultiPort::loginUser($username, $password);
        //User has correctly logged in
        if ($userObj instanceof ADALoggableUser) {
            $_SESSION['sess_user_language'] = $p_selected_language;

            $_SESSION['sess_id_user'] = $userObj->getId();
            $GLOBALS['sess_id_user']  = $userObj->getId();

            $_SESSION['sess_id_user_type'] = $userObj->getType();
            $GLOBALS['sess_id_user_type']  = $userObj->getType();

            $_SESSION['sess_userObj'] = $userObj;

            $user_default_tester = $userObj->getDefaultTester();
            if ($user_default_tester !== null) {
                $_SESSION['sess_selected_tester'] = $user_default_tester;
            }

            header('Location:' . $userObj->getHomePage());
            exit();
        } else {
            // Utente non loggato perché coppia username password non corretta
            $login_error_message = translateFN("Username  e/o password non valide");
        }
    } else {
        // Utente non loggato perche' informazioni in username e password non valide
        // es. campi vuoti o contenenti caratteri non consentiti.
        $login_error_message = translateFN("Username  e/o password non valide");
    }
}

/**
 * Show login page
 */
$form_action = HTTP_ROOT_DIR . '/kiosk.php';
$login = UserModuleHtmlLib::loginForm($form_action, $supported_languages, $login_page_language_code, $login_error_message);

$message = CDOMElement::create('div');
if (isset($_GET['message'])) {
    $message->addChild(new CText($_GET['message']));
}

$content_dataAr = [
  'form' => $login->getHtml(),
  'text' => $newsmsg,
  'help' => $hlpmsg,
  'message' => $message->getHtml(),
];

/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);

<?php

use Lynxlab\ADA\Admin\HtmlAdmOutput;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Token\TokenFinder;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\redirect;

require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
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

$common_dh = $GLOBALS['common_dh'];

//$self =  "guest";
$self =  "registration";
/**
 * Negotiate login page language
 */

$lang_get = $_GET['lan'] ?? null;

Translator::loadSupportedLanguagesInSession();
$supported_languages = Translator::getSupportedLanguages();
$login_page_language_code = Translator::negotiateLoginPageLanguage($lang_get);
$_SESSION['sess_user_language'] = $login_page_language_code;



if (isset($_POST['username'])) {
    $case = 2;
    $op = "check_username"; // and send email
} elseif ((isset($_POST['user']['password'])) && (isset($_POST['user']['username']))) {
    $case = 4;
    $op = "change_password";
} elseif ((isset($_GET['tok'])) && (isset($_GET['uid']))) {
    $case = 3;
    $op = "form_password";
} else {
    // first time here
    $case = 1;
    $op  = "insert_username";
}


if (isset($_GET['status'])) {
    $status = $_GET['status'];
} else {
    $status = translateFN("New password");
}

switch ($op) {
    case "check_username":
        $username = $_POST['username'];
        if ($username != null) {
            $user_id = $common_dh->findUserFromUsername($username);
            if (AMACommonDataHandler::isError($user_id)) {
                // Utente non esistente o non loggable
                /*
                 * Verifico se esiste un utente che ha come email  il contenuto del
                 * campo $username
                 */
                //        $user_id = $common_dh->findUserFromEmail($username);
                //        if(AMACommonDataHandler::isError($user_id)) {
                $message = translateFN("Username is not valid");
                $redirect_to = HTTP_ROOT_DIR . "/browsing/forget.php?message=$message";
                header('Location:' . $redirect_to);
                exit();
                //        }
            }
            $userObj =   MultiPort::findUser($user_id);//readUser($user_id);  // ?
            if ((is_object($userObj)) && ($userObj instanceof ADALoggableUser)) {
                // user is recognized as loggable
                $userStatus = $userObj->getStatus();
                /*
                if ($userStatus == ADA_STATUS_REGISTERED)  // FIXME: practitioner and others?
                {
                  // user is a ADAuser with status set to 0 OR
                  // user is admin, author or switcher whose status is by default = 0

                  $_SESSION['sess_user_language'] = $p_selected_language;
                  $_SESSION['sess_id_user'] = $userObj->getId();
                  $_SESSION['sess_id_user_type'] = $userObj->getType();
                  $_SESSION['sess_userObj'] = $userObj;
                  $GLOBALS['sess_id_user']  = $_SESSION['sess_id_user'];
                  $GLOBALS['sess_id_user_type']  = $_SESSION['sess_id_user_type'];

                  $user_default_tester = $userObj->getDefaultTester();
                  if($user_default_tester !== NULL) {
                  $_SESSION['sess_selected_tester'] = $user_default_tester;
                  }
                }
                 *
                 */
            } else {
                // Utente non esistente o non loggable
                $message = translateFN("Username is not valid");
                $redirect_to = HTTP_ROOT_DIR . "/browsing/forget.php?message=$message";
                header('Location:' . $redirect_to);
                exit();
            }
        } else {
            // vuoto
            $message = translateFN("Username cannot be empty");
            $redirect_to = HTTP_ROOT_DIR . "/browsing/forget.php?message=$message";
            header('Location:' . $redirect_to);
            exit();
        }

        if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
            /**
             * MODULES_SECRETQUESTION will handle questioning and answer check
             */
            redirect(MODULES_SECRETQUESTION_HTTP . '/askQuestion.php?userId=' . $user_id);
        } else {
            $admtypeAr = [AMA_TYPE_ADMIN];
            $admList = $common_dh-> getUsersByType($admtypeAr);
            // $admList = $tester_dh-> getUsersByType($admtypeAr); ???

            if (!AMADataHandler::isError($admList)) {
                $adm_uname = $admList[0]['username'];
            } else {
                $adm_uname = ""; // ??? FIXME: serve un superadmin nel file di config?
            }
            /*
             * Create a token to authorize this user to change his/her password
             */
            $tokenObj = TokenManager::createTokenForPasswordChange($userObj);
            if ($tokenObj == false) {
                $message = translateFN('An error occurred while performing your request. Please try again later.');
                header('Location:' . HTTP_ROOT_DIR . "/browsing/forget.php?message=$message");
                exit();
            }
            $token    = $tokenObj->getTokenString();

            $titolo = translateFN("Richiesta cambio password");
            $testo = sprintf(translateFN("Abbiamo ricevuto una richiesta di cambio password per l'utente %s della piattaforma %s."), $username, PORTAL_NAME);
            $testo .= '<br/><br/>';
            $link = HTTP_ROOT_DIR . "/browsing/forget.php?uid=$user_id&tok=$token";
            $testo .= sprintf(translateFN("Se hai fatto tu la richiesta, apri questo link %s"), BaseHtmlLib::link($link, $link)->getHtml());

            // $mh = MessageHandler::instance(MultiPort::getDSN($tester)); /* FIXME */
            // should we user common DB?
            $common_db_dsn = ADA_COMMON_DB_TYPE . '://' . ADA_COMMON_DB_USER . ':'
            . ADA_COMMON_DB_PASS . '@' . ADA_COMMON_DB_HOST . '/'
            . ADA_COMMON_DB_NAME;
            $mh = MessageHandler::instance($common_db_dsn);

            // prepare message to send
            $message_ha = [];
            $message_ha['titolo'] = $titolo;
            $message_ha['testo'] = $testo;
            $message_ha['destinatari'] = [$username];
            $message_ha['data_ora'] = "now";
            $message_ha['tipo'] = ADA_MSG_MAIL;
            $message_ha['mittente'] = $adm_uname;

            // delegate sending to the message handler
            $res = $mh->sendMessage($message_ha);

            if (AMADataHandler::isError($res)) {
                //    $errObj = new ADAError($res,translateFN('Impossibile spedire il messaggio'),
                //    NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
            }
            //    } else {
            $message = translateFN("A message has been sent to  you with informations on how to change your password.");
            $redirect_to = HTTP_ROOT_DIR . "/browsing/forget.php?message=$message";
            header('Location:' . $redirect_to);
            exit();
        }
        //  }
        break;
    case "change_password":
        /*
         * Third time here.
         * After filling the change password form.
         */

        $userid   = $_POST['user']['uid'];
        $username = $_POST['user']['username'];
        $token    = $_POST['token'];

        $tokenObj = TokenFinder::findTokenForPasswordChange($userid, $token);
        if ($tokenObj == false) {
            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
            $errObj = new ADAError(
                $userType,
                translateFN('It was impossible to confirm the password change: token not valid'),
                null,
                null,
                null,
                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change: token not valid'))
            );
            exit();
        }

        $userObj = MultiPort::findUser($userid);
        $userStatus = $userObj->getStatus();
        if (AMADataHandler::isError($userObj)) {
            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
            $errObj = new ADAError(
                $userType,
                translateFN('It was impossible to confirm the password change: user unknown'),
                null,
                null,
                null,
                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change: user unknown'))
            );
            exit();
        } else {
            $message = '';
            // cut off extra spaces
            $password      = trim($_POST['user']['password']);
            $passwordcheck = trim($_POST['user']['passwordcheck']);

            /**
             * Check that the user entered a valid password and confirmed it correctly
             */
            if (DataValidator::validatePassword($password, $passwordcheck) === false) {
                $errors = true;
                $message .= translateFN('Le password digitate non corrispondo o contengono caratteri non validi.') . '<br />';
                header("Location: " . HTTP_ROOT_DIR . "/browsing/forget.php?message=$message&uid=$userid&tok=$token");
                exit();
            } else {
                $userObj->setPassword($password);

                $new_testers = [];
                $resPass = MultiPort::setUser($userObj, $new_testers, true); // TRUE to modify user data

                if (AMADataHandler::isError($resPass)) {
                    $msg = $result->getMessage();
                    $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
                    $errObj = new ADAError(
                        $requestInfo,
                        translateFN('It was impossible to confirm the password change'),
                        null,
                        null,
                        null,
                        $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change'))
                    );
                    exit();
                } else {
                    // change status of user ON Common AND ON  TESTER ?
                    switch ($userStatus) {
                        case ADA_STATUS_PRESUBSCRIBED:
                            $userObj->setStatus(ADA_STATUS_REGISTERED);

                            $resSet = MultiPort::setUser($userObj, $new_testers, true);
                            /*
                             $adh->setUserStatus(ADA_STATUS_REGISTERED);
                             $common_dh->setUserStatus(ADA_STATUS_REGISTERED);
                             */
                            break;
                        case ADA_STATUS_REGISTERED:
                            break;
                        case ADA_STATUS_REMOVED:
                        default:
                            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
                            $errObj = new ADAError(
                                $requestInfo,
                                translateFN('It was impossible to confirm the password change: user unknown'),
                                null,
                                null,
                                null,
                                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change: user unknown'))
                            );
                            exit();
                    }


                    $message = translateFN("Password cambiata con successo.");
                    // FIXME: add a get parameter to help user to login ??
                    //header('Location: '.$redirectPage."?message=$message&user=$username");

                    $tokenObj->markAsUsed();
                    TokenManager::updateToken($tokenObj);

                    header('Location: ' . HTTP_ROOT_DIR . "/browsing/forget.php?message=$message");
                    exit();
                }
            }
        }
        break;

    case "form_password":
        /*
         * Second time here.
         * Show the password change form.
         */

        $token  = DataValidator::validateActionToken($_GET['tok']);
        $userid = DataValidator::isUinteger($_GET['uid']);

        if ($token == false || $userid == false) {
            /*
             * Invalid data in input
             */
            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
            $errObj = new ADAError(
                $requestInfo,
                translateFN('It was impossible to confirm the password change'),
                null,
                null,
                null,
                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change'))
            );
            exit();
        }

        $tokenObj = TokenFinder::findTokenForPasswordChange($userid, $token);
        if ($tokenObj === false) {
            /*
             * There isn't a token corresponding to input data, do not proceed.
             */
            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
            $errObj = new ADAError(
                $requestInfo,
                translateFN('It was impossible to confirm the password change'),
                null,
                null,
                null,
                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change'))
            );
            exit();
        }

        $userObj = MultiPort::findUser($userid);
        if (AMADataHandler::isError($userObj)) {
            $error_page = HTTP_ROOT_DIR . "/browsing/forget.php";
            $errObj = new ADAError(
                $userType,
                translateFN('It was impossible to confirm the password change: user unknown'),
                null,
                null,
                null,
                $error_page . '?message=' . urlencode(translateFN('It was impossible to confirm the password change: user unknown'))
            );
            exit();
        }

        if ($tokenObj->isValid()) {
            if (!isset($username)) {
                $usernameStr = '';
            } else {
                $usernameStr = ', ' . $username;
            }
            $help  = translateFN('Per favore inserisci la tua password:');
            // $status = translateFN("Modifica password utente");
            $welcome = "<br />" . translateFN('Benvenuto') . $usernameStr . "<br />";
            $welcome .= translateFN('Ora devi cambiare la tua password. Puoi usare lettere, numeri e trattini. Lunghezza minima 8 lettere') . "<br />";
            $home = 'user.php';
            $menu = '';

            $op   = new HtmlAdmOutput();

            $dati = $op->formConfirmpassword('forget.php', $home, $username, $userid, $id_course ?? null, $token);
            $dati = $welcome . $dati;
            $title = translateFN('ADA - Modifica Dati Utente');
        } else {
            /*
             * Informiamo l'utente che il token per il cambio password è scaduto e che
             * deve richiedere nuovamente di cambiare la password
             */
            $title = translateFN('');
            $dati  = sprintf(translateFN("Dear user %s, the web address you have clicked to change your password has expired. You have to require a new one by clicking on the following link. "), $userObj->getUserName());
            $forget_linkObj = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR . '/browsing/forget.php?lan=' . $_SESSION['sess_user_language']);
            $forget_linkObj->addChild(new CText(translateFN("Did you forget your password?")));
            $dati .= $forget_linkObj->getHtml();
        }
        break;

    case "insert_username":
    default:
        // first time here
        $help  = translateFN('Did you forget your password?');

        $welcome = "<br />" . translateFN('Welcome, user') . "<br />";
        $welcome .= translateFN('If you forgot your password, please insert your username. We will send you a message with instructions
     to change your password');
        if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
            $welcome .= ' ' . translateFN('or ask your secret question');
        }
        $welcome .= ".<br />";

        $home = $userObj->getHomepage();
        $menu = '';
        $op   = new HtmlAdmOutput();

        $dati = $op->formGetUsername('forget.php');
        $dati = $welcome . $dati;
        $title = translateFN('ADA - Changing password');
        break;
} // end switch

if (isset($message) && strlen($message) > 0) {
    $help = $message;
    unset($message);
}

$content_dataAr = [
  'title'     => $title,
  'menu'      => $menu,
  'data'      => $dati, // FIXME: move to message field
  'help'      => $help,
  'user_type' => $userType ?? null,
  // 'message'   => $message // FIXME: not visible !
];

ARE::render($layout_dataAr, $content_dataAr);

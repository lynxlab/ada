<?php

/**
 * registration.php file
 *
 * This script is responsible for the user registration process.
 *
 * @package     Default
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009-2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        registration
 * @version     0.1
 */

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Forms\UserRegistrationForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Module\GDPR\GdprAcceptPoliciesForm;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\Secretquestion\AMASecretQuestionDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR];
/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout'],
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

$self =  whoami();
/**
 * Negotiate login page language
 */
if (!isset($_SESSION['sess_user_language'])) {
    Translator::loadSupportedLanguagesInSession();
    $login_page_language_code = Translator::negotiateLoginPageLanguage();
    $_SESSION['sess_user_language'] = $login_page_language_code;
}
$supported_languages = Translator::getSupportedLanguages();


if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     * Validate the user submitted data and proceed to the user registration.
     */
    $form = new UserRegistrationForm();
    $form->fillWithPostData();
    if ($form->isValid()) {
        $user_dataAr = $form->toArray();
        if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
            $user_dataAr['username'] = trim($_POST['uname']);
            $passw = trim($_POST['upass']);
            $ustatus = ADA_STATUS_REGISTERED;
        } else {
            $user_dataAr['username'] = $_POST['email'];
            // Random password.
            $passw = sha1(time());
            $ustatus = ADA_STATUS_PRESUBSCRIBED;
        }

        $userObj = new ADAUser($user_dataAr);
        $userObj->setLayout('');
        $userObj->setType(AMA_TYPE_STUDENT);
        $userObj->setStatus($ustatus);
        $userObj->setPassword($passw);
        $emailed = false;

        /**
         * giorgio 19/ago/2013
         *
         * if it's not multiprovider, must register the user
         * in the selected tester only.
         * if it is multiprovider, must register the user
         * in the public tester only.
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
            $regProvider =  [$GLOBALS['user_provider']];
        } else {
            $regProvider =  [ADA_PUBLIC_TESTER];
        }

        $id_user = MultiPort::addUser($userObj, $regProvider);
        if ($id_user < 0) {
            $message = translateFN('Impossibile procedere. Un utente con questi dati esiste?')
                     . ' ' . urlencode(
                         (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) ?
                         $userObj->getUserName() :
                         $userObj->getEmail()
                     );
            header('Location:' . HTTP_ROOT_DIR . '/browsing/registration.php?message=' . $message);
            exit();
        }

        /**
         * before doing anything, save the accepted privacy policies here
         */
        try {
            if (defined('MODULES_GDPR') && MODULES_GDPR === true) {
                $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
                if (array_key_exists('acceptPolicy', $postParams) && is_array($postParams['acceptPolicy']) && count($postParams['acceptPolicy']) > 0) {
                    $postParams['userId'] = $userObj->getId();
                    (new GdprAPI())->saveUserPolicies($postParams);
                }
            }
        } catch (Exception $e) {
            $message = translateFN('Errore nel salvataggio delle politiche sulla privacy');
            header('Location:' . HTTP_ROOT_DIR . '/browsing/registration.php?message=' . $message);
            exit();
        }

        if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
            /**
             * Save secret question and answer and set the registration as successful
             */
            $sqdh = AMASecretQuestionDataHandler::instance();
            $sqdh->saveUserQandA($id_user, $_POST['secretquestion'], $_POST['secretanswer']);
        } else {
            /**
             * Create a registration token for this user and send it to the user
             * with the confirmation request.
             */
            $tokenObj = TokenManager::createTokenForUserRegistration($userObj);
            if ($tokenObj == false) {
                $message = translateFN('An error occurred while performing your request. Pleaser try again later.');
                header('Location:' . HTTP_ROOT_DIR . "/browsing/registration.php?message=$message");
                exit();
            }
            $token = $tokenObj->getTokenString();

            $admTypeAr = [AMA_TYPE_ADMIN];
            $extended_data = true;
            $admList = $dh->get_users_by_type($admTypeAr, $extended_data);
            if (!AMA_DataHandler::isError($admList) && array_key_exists('username', $admList[0]) && $admList[0]['username'] != '' && $admList[0]['username'] != null) {
                $adm_uname = $admList[0]['username'];
                $adm_email = strlen($admList[0]['e_mail']) ? $admList[0]['e_mail'] : ADA_NOREPLY_MAIL_ADDRESS;
            } else {
                $adm_uname = ADA_ADMIN_MAIL_ADDRESS;
                $adm_email = ADA_ADMIN_MAIL_ADDRESS;
            }

            $title = PORTAL_NAME . ': ' . translateFN('ti chiediamo di confermare la registrazione.');

            $confirm_link_html = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR . "/browsing/confirm.php?uid=$id_user&tok=$token");
            $confirm_link_html->addChild(new CText(translateFN('conferma registrazione')));
            $confirm_link_html_rendered = $confirm_link_html->getHtml();

            $PLAINText = sprintf(
                translateFN('Gentile %s, ti chiediamo di confermare la registrazione ai %s.'),
                $userObj->getFullName(),
                PORTAL_NAME
            )
                    . PHP_EOL . PHP_EOL
                    . translateFN('Il tuo nome utente è il seguente:')
                    . ' ' . $userObj->getUserName()
                    . PHP_EOL . PHP_EOL
                    . sprintf(
                        translateFN('Puoi confermare la tua registrazione a %s seguendo questo link:'),
                        PORTAL_NAME
                    )
                    . PHP_EOL
                    . ' ' . HTTP_ROOT_DIR . "/browsing/confirm.php?uid=$id_user&tok=$token"
                    . PHP_EOL . PHP_EOL
                    . translateFN('La segreteria di') . ' ' . PORTAL_NAME;

            $HTMLText = sprintf(
                translateFN('Gentile %s, ti chiediamo di confermare la registrazione ai %s.'),
                $userObj->getFullName(),
                PORTAL_NAME
            )
                    . '<BR />' . '<BR />'
                    . translateFN('Il tuo nome utente è il seguente:')
                    . ' ' . $userObj->getUserName()
                    . '<BR />' . '<BR />'
                    . sprintf(
                        translateFN('Puoi confermare la tua registrazione ai %s seguendo questo link:'),
                        PORTAL_NAME
                    )
                    . '<BR />'
                    . $confirm_link_html_rendered
                    . '<BR />' . '<BR />'
                    . translateFN('La segreteria di') . ' ' . PORTAL_NAME;

            $message_ha = [
                'titolo' => $title,
                'testo' => $PLAINText,
                'destinatari' => [$userObj->getUserName()],
                'data_ora' => 'now',
                'tipo' => ADA_MSG_SIMPLE,
                'mittente' => $adm_uname,
            ];
            $tester ??= null;
            $mh = MessageHandler::instance(MultiPort::getDSN($tester));
            /**
             * Send the message as an internal message
             */
            $result = $mh->send_message($message_ha);
            if (AMA_DataHandler::isError($result)) {
            }
            /**
             * Send the message an email message
             * via PHPMailer
             */
            $phpmailer = new ADAPHPMailer();
            $phpmailer->CharSet = ADA_CHARSET;
            $phpmailer->configSend();
            $phpmailer->SetFrom($adm_email);
            $phpmailer->AddReplyTo($adm_email);
            $phpmailer->IsHTML(true);
            $phpmailer->Subject = $title;
            $phpmailer->AddAddress($userObj->getEmail(), $userObj->getFullName());
            $phpmailer->AddBCC($adm_email);
            $phpmailer->Body = $HTMLText;
            $phpmailer->AltBody = $PLAINText;
            $emailed = $phpmailer->Send();

            /**
             * Send the message an email message
             * via ADA spool
            $message_ha['tipo'] = ADA_MSG_MAIL;
            $result = $mh->send_message($message_ha);
            if(AMA_DataHandler::isError($result)) {
            }
             */
        }

        if (
            defined('ADA_SUBSCRIBE_FROM_LOGINREQUIRED') && (true === ADA_SUBSCRIBE_FROM_LOGINREQUIRED) &&
            isset($_SESSION['subscription_page']) && strlen($_SESSION['subscription_page']) > 0
        ) {
            $subUrl = $_SESSION['subscription_page'];
            /**
             * setSessionAndRedirect wants the user to be in ADA_STATUS_REGISTERED status
             */
            $oldStatus = $userObj->getStatus();
            $userObj->setStatus(ADA_STATUS_REGISTERED);
            ADALoggableUser::setSessionAndRedirect($userObj, false, $_SESSION['sess_user_language'], null, null, false);
            $userObj->setStatus($oldStatus);
            /**
             * parse the query string coming from subscription_page and include
             * info.php to reproduce the subscription sequence
             */
            $qs = parse_url($subUrl, PHP_URL_QUERY);
            if (strlen($qs) > 0) {
                parse_str($qs, $_GET);
                require_once ROOT_DIR . '/info.php';
            }
            /**
             * clean unwanted stuff
             */
            unset($_SESSION['subscription_page']);
            session_destroy();
        }

        /*
         * Redirect the user to the "registration succeeded" page.
         */
        header('Location: ' . HTTP_ROOT_DIR . '/browsing/registration.php?op=success' . ($emailed !== false ? '&emailed=1' : ''));
        exit();
    } else {
        header('Location: ' . HTTP_ROOT_DIR . '/browsing/registration.php');
        exit();
    }
} elseif (isset($_GET['op']) && $_GET['op'] == 'success') {
    /*
     * The user registration was completed with success.
     * Generate a feedback message for the user.
     */
    $help = '';
    $data  = translateFN('Richiesta di registrazione completata.');
    if (isset($_GET['emailed']) && intval($_GET['emailed']) === 1) {
        $data .= '<br />' . translateFN('You will receive an email with informations on how to login.');
    }
} else {
    /**
     * giorgio 21/ago/2013
     * if it's not a multiprovider environment and the provider is not
     * selected, must redirect to index
     */
    if (!MULTIPROVIDER) {
        // if provider is not set the redirect
        if (!isset($GLOBALS['user_provider']) || empty($GLOBALS['user_provider'])) {
            header('Location: ' . HTTP_ROOT_DIR);
            die();
        }
    }

    /*
     * Display the registration form.
     */
    $help = translateFN('Da questa pagina puoi effettuare la registrazione ad ADA');
    if (isset($message) && strlen($message) > 0) {
        $help = $message;
        unset($message);
    }
    $form = new UserRegistrationForm();

    if (defined('MODULES_GDPR') && MODULES_GDPR === true) {
        $gdprApi = new GdprAPI();
        GdprAcceptPoliciesForm::addPolicies($form, [
            'policies' => $gdprApi->getPublishedPolicies(),
            'extraclass' => 'ui form',
            'isRegistration' => true,
        ]);

        $layout_dataAr['CSS_filename'] = [
            MODULES_GDPR_PATH . '/layout/' . ADA_TEMPLATE_FAMILY . '/css/acceptPolicies.css',
        ];
    }

    $data = $form->render();
}

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
];

if (isset($gdprApi)) {
    $layout_dataAr['JS_filename'][] =  MODULES_GDPR_PATH . '/js/acceptPolicies.js';
}

if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
    $layout_dataAr['JS_filename'][] = MODULES_SECRETQUESTION_PATH . '/js/modules_define.js.php';
}

$optionsAr['onload_func'] = 'initDateField(); initRegistration();';

$title = translateFN('Informazioni');

$content_dataAr = [
  'user_name'  => $user_name ?? '',
  'home'       => $home ?? '',
  'data'       => $data ?? '',
  'help'       => $help ?? '',
  'message'    => $message ?? '',
  'status'     => $status ?? '',
];

if (isset($msg)) {
    $help = CDOMElement::create('label');
    $help->addChild(new CText(translateFN(ltrim($msg))));
    $divhelp = CDOMElement::create('div');
    $divhelp->setAttribute('id', 'help');
    $divhelp->addChild($help);
    $content_dataAr['help'] = $divhelp->getHtml();
}
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

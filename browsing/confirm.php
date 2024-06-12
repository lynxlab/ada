<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\ConfirmPasswordForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Token\TokenFinder;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprAPI;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';
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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

$self =  'registration';
/**
 * Negotiate login page language
 */
Translator::loadSupportedLanguagesInSession();
$supported_languages = Translator::getSupportedLanguages();
$login_page_language_code = Translator::negotiateLoginPageLanguage();
$_SESSION['sess_user_language'] = $login_page_language_code;

if (
    //(isset($_SERVER['REQUEST_METHOD'])) &&
    ($_SERVER['REQUEST_METHOD'] == 'POST') &&
    (isset($_POST['token'])) &&
    (isset($_POST['userId']))
) {
    $op = 'confirm_password';
} elseif (
    //(isset($_SERVER['REQUEST_METHOD'])) &&
    ($_SERVER['REQUEST_METHOD'] == 'GET') &&
    (isset($_GET['tok']))
) {
    $op = 'set_new_password';
} else {
    $op = 'redirect_to_login';
}

// if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
switch ($op) {
    case 'confirm_password':
        /*
         * second time here after changing the password
         */
        $userid = $_POST['userId'];//$_POST['user']['uid'];
        $token  = $_POST['token'];

        $password      = trim($_POST['password']);
        $passwordcheck = trim($_POST['passwordcheck']);
        if (DataValidator::validatePassword($password, $passwordcheck) === false) {
            $message = translateFN('Le password digitate non corrispondono o contengono caratteri non validi.');
            header("Location: confirm.php?uid=$userid&cid=$id_course&tok=$token&message=$message");
            exit();
        }

        $tokenObj = TokenFinder::findTokenForUserRegistration($userid, $token);
        if ($tokenObj == false) {
            $error_page = HTTP_ROOT_DIR;
            $errObj = new ADAError(
                $requestInfo,
                translateFN('Impossibile confermare la richiesta di iscrizione'),
                null,
                null,
                null,
                $error_page . '?message='
                . urlencode(translateFN('Impossibile confermare la richiesta'))
            );
        }

        $userObj = MultiPort::findUser($userid);

        if ((($userObj instanceof ADAUser) || ($userObj instanceof ADAPractitioner)) && $userObj->getStatus() == ADA_STATUS_PRESUBSCRIBED) {
            $username = $userObj->getUserName();
            /*
            * Update user password and change his/her subscription status
            */
            $userObj->setPassword($password);
            $userObj->setStatus(ADA_STATUS_REGISTERED);
            MultiPort::setUser($userObj, [], true);
            /*
             * This token can't be reused.
             */
            $tokenObj->markAsUsed();
            TokenManager::updateToken($tokenObj);

            $userObj = MultiPort::loginUser($username, $password);
            if ((is_object($userObj)) && ($userObj instanceof ADALoggableUser)) {
                $status = $userObj->getStatus();
                if ($status == ADA_STATUS_REGISTERED) {
                    /*
                    *  In case the user has done a subscription to a course class
                    *  he/she will redirect to the subscription page
                    */
                    if (isset($_SESSION['subscription_page'])) {
                        $redirectURL = $_SESSION['subscription_page'];
                        unset($_SESSION['subscription_page']);
                    }
                    if (isset($redirectURL) && ModuleLoaderHelper::isLoaded('GDPR') === true) {
                        // check if user has accepted the mandatory privacy policies
                        $gdprApi = new GdprAPI();
                        if ($gdprApi->checkMandatoryPoliciesForUser($userObj)) {
                            // language has already been negotiated
                            // $_SESSION['sess_user_language'] = $p_selected_language;
                            $_SESSION['sess_id_user'] = $userObj->getId();
                            $GLOBALS['sess_id_user'] = $userObj->getId();
                            $_SESSION['sess_id_user_type'] = $userObj->getType();
                            $GLOBALS['sess_id_user_type'] = $userObj->getType();
                            $_SESSION['sess_userObj'] = $userObj;
                            $user_default_tester = $userObj->getDefaultTester();
                            if ($user_default_tester !== null) {
                                $_SESSION['sess_selected_tester'] = $user_default_tester;
                            }
                            Utilities::redirect($redirectURL);
                        }
                    }
                }
            } else {
                $data = new CText('Utente non trovato');
            }
        } else {
            /*
             * Only presubscribed ADAUsers OR ADAPractitioners can access this module.
             */
            $message = translateFN("Non hai bisogno di confermare la tua registrazione a") . ' ' . PORTAL_NAME . ' (2)';
            //    $message = translateFN("You don't need to confirm your registration to ICON").'(2)';
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        }

        /*
         * La registrazione è andata a buon fine. L'utente può accedere a ADA
         * dalla pagina di login utilizzando come username l'indirizzo di email
         * fornito durante la registrazione e come password quella appena inserita.
         */

        $title = translateFN('Registrazione confermata');
        //  $title = translateFN('Registration confirmed');
        $dati  = sprintf(translateFN('Hai confermato la tua registrazione a %s. %s Puoi accedere a %s inserendo il tuo username (%s) e la tua password nella pagina di login di %s.'), PORTAL_NAME, '<br /><br />', PORTAL_NAME, $userObj->getUserName(), PORTAL_NAME);
        //  $dati  = sprintf(translateFN('You have successfully confirmed your registration to ICON. %s You can access ICON by entering your username (%s) and your password in the ADA login page.'),'<br />', $userObj->getUserName());

        $login_page_link = CDOMElement::create('a', 'class:ui button,href:' . HTTP_ROOT_DIR);
        $login_page_link->addChild(new CText(translateFN('Pagina di login')));
        $dati .= '<br /><br />' . $login_page_link->getHtml();

        break;

    case 'set_new_password':
        /*
         * first time here: we show only the password and passwordcheck fields
         */

        /*
         * Data validation
         */
        $token     = DataValidator::validateActionToken($_GET['tok']);
        $userid    = DataValidator::isUinteger($_GET['uid']);

        if ($token == false || $userid == false) {
            /*
             * Token or userid not valid, do not proceed.
             */
            header('Location: ' . HTTP_ROOT_DIR);
            exit();
        }

        $tokenObj = TokenFinder::findTokenForUserRegistration($userid, $token);
        if ($tokenObj == false) {
            /*
             * There isn't a token corresponding to input data, do not proceed.
             */
            $message = translateFN('Did not find a token that matches your request');
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        }

        $userObj = MultiPort::findUser($userid);

        if (($userObj instanceof ADAUser) || ($userObj instanceof ADAPractitioner)) {
            // se stato != preiscritto mostrare un messaggio adeguato
            if ($userObj->getStatus() != ADA_STATUS_PRESUBSCRIBED) {
                $message = translateFN('Forse un utente con questi dati ha già confermato la tua registrazione');
                //      $message = translateFN('Maybe a user with these data has already confirmed his/her registration to ADA');
                header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
                exit();
            }
        } else {
            /*
     * Only ADAUser OR ADAPractitioner right now can use this module; else redirect to ADA index page.
     */
            $message = sprintf(translateFN("Non hai bisogno di confermare la tua registrazione a %s"), PORTAL_NAME);
            //      $message = translateFN("You don't need to confirm your registration to ADA");
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        }

        if ($tokenObj->isValid()) {
            /*
           * We have a valid token, a ADAUser with status presubscribed.
           * We can show the password confirmation form.
           */
            $status  = translateFN('Impostazione password utente');
            $help    = translateFN('Per favore imposta la tua password:');

            $text = translateFN('Benvenuto') . ' ' . $userObj->getUserName()
                . '<br />'
                //. translateFN('Ora devi impostare la tua password. Puoi usare lettere, numeri e trattini. Lunghezza minima 8 lettere');
                .  translateFN('Ora devi impostare una password di tua scelta. La lunghezza minima della password è di 8 caratteri, e puoi usare lettere (maiuscole e minuscole), numeri e trattini bassi.');
            $formData = [
            'userId' => $userObj->getId(),
            'token' => $token,
            ];
            $form = new ConfirmPasswordForm();
            $form->fillWithArrayData($formData);
            $dati = $text . $form->render();
        } elseif ($tokenObj->alreadyUsed()) {
            /*
     * This token was already used, redirect the user to ADA index page.
     */
            //$message = translateFN('Maybe a user with these data has already confirmed his/her registration to ADA').'(2)';
            $message = translateFN('Forse un utente con questi dati ha già confermato la tua registrazione') . '(2)';
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        } elseif ($tokenObj->isExpired()) {
            /*
     * Token mai utilizzato ma scaduto.
     * Invalidiamo il token corrente e salviamo modifica.
     * Generiamo un nuovo token per la richiesta di iscrizione di questo utente.
     * Inviamo una nuova mail di conferma registrazione all'utente.
     * Mostriamo un messaggio sulla pagina che informa l'utente di questo.
     */
            $tokenObj->markAsUsed();
            TokenManager::updateToken($tokenObj);

            $newTokenObj = TokenManager::createTokenForUserRegistration($userObj);
            $tokenString = $newTokenObj->getTokenString();

            /*
     * Send a new email to the user
     */
            $admtypeAr = [AMA_TYPE_ADMIN];
            $admList = $common_dh->getUsersByType($admtypeAr);
            if (!AMADataHandler::isError($admList)) {
                $adm_uname = $admList[0]['username'];
            } else {
                $adm_uname = ""; // ??? FIXME: serve un superadmin nel file di config?
            }
            $new_confirm_link = HTTP_ROOT_DIR . '/browsing/confirm.php?uid=' . $userObj->getId()
                    . '&tok=' . $tokenString;

            $message_text = sprintf(translateFN("L'indirizzo utilizzato per la conferma della registrazione per l'utente %s (username: %s) è scaduto."), $userObj->getFullName(), $userObj->getUserName())
                . "\r\n"
                .  translateFN('Per confermare la registrazione cliccare sul nuovo indirizzo fornito qui di seguito:')
                . "\r\n"
                . $new_confirm_link;
            $message_ha = [
            'tipo'        => ADA_MSG_MAIL,
            'data_ora'    => 'now',
            'mittente'    => $adm_uname,
            'destinatari' => [$userObj->getUserName()],
            'titolo'      => translateFN('Richiesta di registrazione'),
            'testo'       => $message_text,
            ];

            $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
            $result = $mh->sendMessage($message_ha);
            if (AMADataHandler::isError($result)) {
                $title = translateFN('Registration process error');
                $dati  = translateFN('An error occurred while processing your request. Try later');
            } else {
                /*
                 * La registrazione non è ancora completa perché l'indirizzo per la
                 * conferma utilizzato dall'utente era scaduto. Abbiamo inviato una
                 * nuova email all'utente contenente un nuovo indirizzo per la conferma
                 * della registrazione.
                 */
                $title = translateFN('Registrazione');
                //      $title = translateFN('Registration');
                $dati  = translateFN('Riceverai una email contenente le istruzioni per confermaare la tua richiesta di registrazione');
                //$dati  = translateFN('You will receive an email message containing the address to be used in order to confirm your request');
            }
        }
        $title = translateFN('ADA - Modifica Dati Utente');

        break;

    case 'redirect_to_login':
    default:
        /*
       * La registrazione è stata effettuata insieme all'iscrizione ad un servizio
       QUesta pagina serve solo per mostrare il messaggio all'utente
       */
        $break = '<br />'; // FIXME: CORE...
        $title = translateFN('Subscription confirmation');
        $message = DataValidator::checkInputValues('message', 'Message', INPUT_GET);
        //  $dati  = translateFN('Servizio richiesto correttamente. Ti verrà inviato un messaggio contenente le proposte di appuntamento.');
        $dati1 = translateFN('Thank you for registering in ADA and for asking our services!');
        $dati2 = translateFN('To complete your registration, you have to choose a password following the link sent to you by e-mail.');
        $dati3 = translateFN('To provide the service you requested, we need to assign you a practitioner, who will contact you to arrange an appointment.');
        $dati4 = translateFN('Check your e-mail box; in the meanwhile you may continue navigating the ADA portal.');

        $dati = $dati1 . $break . $dati2 . $break . $dati3 . $break . $dati4 . $break;
        $login_page_link = CDOMElement::create('a', 'href:' . HTTP_ROOT_DIR);
        $login_page_link->addChild(new CText(translateFN('Login Page')));
        $dati .= $login_page_link->getHtml();

        $status = translateFN('Subscription confirmation');
        $username = "";
        $userType = "";
}

$content_dataAr = [
  'title'     => $title,
  'message'   => $message ?? '',
  'menu'      => $menu ?? '',
  'data'      => $dati,
  'help'      => ($help ?? '') . ($message ?? ''),
  'status'    => $status,
  'user_name' => $userObj->getUserName(),
  'user_type' => $userType ?? '',
];

ARE::render($layout_dataAr, $content_dataAr);

<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Service\Service;
use Lynxlab\ADA\Main\Token\TokenFinder;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/*
 *
 * Comportamento
 * se viene passato id_course:
 * 	se utente non loggato:
 * 		se viene passato id_user e token:
 * 			iscrive a quel corso e invia le mail relative
 * 	altrimenti:
 *  		iscrive a quel corso e invia le mail relative
 * altrimenti:
 * 			redirect to index
 */
/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR,AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
AMA_TYPE_STUDENT         => ['layout'],
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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

$self =  Utilities::whoami();
$error_page = HTTP_ROOT_DIR . "/index.php";

$isRegistration = false; // user is asking  for subscription just after registration ?
$isSubscription = false; // user is already registered ?


$id_course = DataValidator::isUinteger($_GET['id_course']);
$r_id_user = DataValidator::isUinteger($_GET['id_user']);
$token     = DataValidator::validateActionToken($_GET['token']);
/*
 * If a valid course id was not given, do not proceed.
 * (Note: we are not checking $id_course !== false,
 *  since we do not accept as valid a course id set to 0)
 */
if ($id_course != false) {
    if ($r_id_user != false && $token !== false) {
        /*
         * Handle a subscription request made by a user that has also asked for registration.
         * To proceed, we have to check that the given token exists for this user and that
         * the token is valid too.
         * Additionally we have to check that the user has been correctly registered and
         * that needs to confirm his/her registration.
         */
        $tokenObj = TokenFinder::findTokenForUserRegistration($r_id_user, $token);
        if ($tokenObj === false || !$tokenObj->isValid()) {
            /*
             * There isn't a token corresponding to input data, do not proceed.
             */
            $message = translateFN('An error occurred while processing your request. Try later');
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        }

        $userObj = MultiPort::findUser($r_id_user);
        if ($userObj instanceof ADAUser && $userObj->getStatus() == ADA_STATUS_PRESUBSCRIBED) {
            $isRegistration = true;
        } else {
            /*
             * Wrong type of user or wrong user status. Do not proceed.
             */
            $message = translateFN('An error occurred while processing your request. Try later') . '(2)';
            header('Location: ' . HTTP_ROOT_DIR . '/index.php?message=' . urlencode($message));
            exit();
        }
    } elseif (isset($_SESSION['sess_id_user'])) {
        $isSubscription = true; // user is already registered
        // $id_user = $_SESSION['sess_id_user'];
        // $userObj = $_SESSION['sess_userObj'];
        $id_user =  $userObj->getId();
    } else {
        $message = urlencode(translateFN('Impossibile richiedere il servizio'));
        $errObj = new ADAError($res, $message, null, null, null, $error_page . '?message=' . $message);
        exit();
    }

    $name = $userObj->getFirstName();
    $surname = $userObj->getLastName();
    $username = $userObj->getUserName();

    $testersAr = []; // serve anche a adduser();

    $tester_infoHa = $common_dh->getTesterInfoFromIdCourse($id_course);
    if (AMADataHandler::isError($tester_infoHa)) {
        $message = urlencode(translateFN('Impossibile richiedere il servizio'));
        $errObj = new ADAError($tester_infoHa, $message, null, null, null, $error_page . '?message=' . $message);
        exit();
    }
    $tester = $tester_infoHa['puntatore'];
    $testersAr[0] = $tester; // it is a pointer (string)
    $testerId = $tester_infoHa['id_tester']; // it is an integer
    // find tester DH from tester pointer
    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));

    $serviceObj = Service::findServiceFromImplementor($id_course);
    $serviceAr = $serviceObj->getServiceInfo();
    $service_name = $serviceAr[0];


    //  get service from course
    $serviceinfoAr = $common_dh->getServiceInfoFromCourse($id_course);
    if (AMADataHandler::isError($serviceinfoAr)) {
        $message = urlencode(translateFN('Impossibile richiedere il servizio'));
        $errObj = new ADAError($serviceinfoAr, $message, null, null, null, $error_page . '?message=' . $message);
        exit();
    }

    $start_date1 = 0;
    $start_date2 = AMADataHandler::dateToTs("now");
    $days = $serviceinfoAr[4];

    $istanza_ha = [
        'data_inizio' => $start_date1,
        'durata' => $days,
        'data_inizio_previsto' => $start_date2,
        'id_layout' => null,
    ];

    // add user to tester DB
    $id_tester_user = Multiport::setUser($userObj, $testersAr, $update_user_data = false);

    if ($id_tester_user === false) {
        $message = urlencode(translateFN("Error while assigning user to provider."));
        //  header('Location:'.$userObj->getHomepage($message));
        // exit();
    }

    // add an instance to tester db
    $res_inst_add = $tester_dh->courseInstanceAdd($id_course, $istanza_ha);

    if ((!AMADataHandler::isError($res_inst_add)) or ($res_inst_add->code == AMA_ERR_UNIQUE_KEY)) {
        // we add an instance OR there already was one with same data

        // get an instance
        $clause = "id_corso = $id_course AND data_inizio_previsto = $start_date2 AND durata  = $days";
        $course_instanceAr = $tester_dh->courseInstanceFindList(null, $clause);
        $id_instance = $course_instanceAr[0][0];

        // presubscribe user to the instance
        $res_presub = $tester_dh->courseInstanceStudentPresubscribeAdd($id_instance, $id_user);
    } else {
        $message = urlencode(translateFN("Errore nella richiesta di servizio: 1"));
        $errorObj = new ADAError($res_inst_add, $message, null, null, null, $error_page . '?message=' . $message);
    }

    $admtypeAr = [AMA_TYPE_ADMIN];
    $admList = $common_dh->getUsersByType($admtypeAr);
    // $admList = $tester_dh-> getUsersByType($admtypeAr); ???

    if (!AMADataHandler::isError($admList)) {
        $adm_uname = $admList[0]['username'];
    } else {
        $adm_uname = ""; // ??? FIXME: serve un superadmin nel file di config?
    }
    if ((!AMADataHandler::isError($res_presub))  or   ($res_presub->code == AMA_ERR_UNIQUE_KEY)) {
        // we presubscribed the user to an instance OR there already was one with same data
        // we have to send message to:
        //   the admin (for monitoring purposes)
        //   the switcher (if a real service was asked for)
        //   the user himself

        // 1. send a message to the admin
        $titolo = translateFN("Richiesta di servizio");
        $destinatari = [$adm_uname];
        $testo = translateFN("Un utente con id: ");
        $testo .= $id_user;
        $testo .= "(" . $name . " " . $surname . ")";
        $testo .= translateFN(" ha richiesto il servizio id: ");
        $testo .= $id_course; // NOTE: it is the service implementation ID, not the service ID
        $testo .= translateFN(" nel tester: ");
        $testo .= $tester . ". ";
        $mh = MessageHandler::instance(MultiPort::getDSN($tester));

        // prepare message to send
        $message_ha = [];
        $message_ha['titolo'] = $titolo;
        $message_ha['testo'] = $testo;
        $message_ha['destinatari'] = $destinatari;
        $message_ha['data_ora'] = "now";
        $message_ha['tipo'] = ADA_MSG_SIMPLE; // oppure mail?
        $message_ha['mittente'] = $adm_uname;
        $res = $mh->sendMessage($message_ha);
        if (AMADataHandler::isError($res)) {
            //  $errObj = new ADAError($res,translateFN('Impossibile spedire il messaggio'),
            // NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
        }
        unset($mh); // perché altrimenti manda due volte lo stesso messaggio?

        //  2. send a message to the switcher  if any
        $swtypeAr = [AMA_TYPE_SWITCHER];
        $switcherList = $tester_dh->getUsersByType($swtypeAr);
        if (!AMADataHandler::isError($switcherList)) {
            $switcher_uname = $switcherList[0]['username']; // FIXME: there should be only one sw per tester
            $titolo = translateFN("Richiesta di assegnazione");
            $destinatari = [$switcher_uname];
            $testo = translateFN("Un utente con id:") . " ";
            $testo .= $id_user;
            $testo .= " " . translateFN("ha richiesto il servizio:") . " " ;
            $testo .= $service_name . " ($id_course)."; // NOTE: it is the service implementation ID, not the service ID
            $testo .= " " . translateFN("nel tester:") . " ";
            $testo .= $tester . ". ";
            $testo .= translateFN("Please visit this link as soon as it is possible:");
            $link = HTTP_ROOT_DIR . "/switcher/assign_practitioner.php";
            $testo .= " " . $link;
            $mh = MessageHandler::instance(MultiPort::getDSN($tester));

            // prepare message to send
            $message2_ha = [];
            $message2_ha['titolo'] = $titolo;
            $message2_ha['testo'] = $testo;
            $message2_ha['destinatari'] = $destinatari;
            $message2_ha['data_ora'] = "now";
            $message2_ha['tipo'] = ADA_MSG_MAIL;
            $message2_ha['mittente'] = $adm_uname;

            $res2 = $mh->sendMessage($message2_ha);
            if (AMADataHandler::isError($res2)) {
                //  $errObj = new ADAError($res,translateFN('Impossibile spedire il messaggio'),
                // NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
            }
        } else {
            $switcher_uname = ""; // probably was a public service or an error
        }

        // 3. send a message to the user (a mail, an SMS, ...)
        $titolo = 'ADA: ' . translateFN('richiesta di servizio');

        $testo = translateFN("Un utente con dati: ");
        $testo .= $name . " " . $surname;
        $testo .= translateFN(" ha richiesto  il servizio: ");
        $testo .= $service_name . ".";
        $testo .= translateFN(" Riceverai un messaggio contenente le proposte di appuntamento. ");

        // $mh = MessageHandler::instance(MultiPort::getDSN($tester));
        // using the previous  MH if exists
        //if (!isset($mh))
        $mh = MessageHandler::instance(MultiPort::getDSN($tester));

        // prepare message to send
        $destinatari =  [$username];
        $message3_ha = [];
        $message3_ha['titolo'] = $titolo;
        $message3_ha['testo'] = $testo;
        $message3_ha['destinatari'] = $destinatari;
        $message3_ha['data_ora'] = "now";
        $message3_ha['tipo'] = ADA_MSG_MAIL;
        $message3_ha['mittente'] = $adm_uname;

        // delegate sending to the message handler
        $res3 = $mh->sendMessage($message3_ha);

        if (AMADataHandler::isError($res3)) {
            // $errObj = new ADAError($res,translateFN('Impossibile spedire il messaggio'),
            //NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
        }
        unset($mh);
        $message = urlencode(translateFN("Servizio richiesto correttamente. Ti verrà inviato un messaggio contenente le proposte di appuntamento."));
    } else { // a real error
        $message = urlencode(translateFN("Errore nella richiesta di servizio: 2"));
        //$AMAErrorObject=NULL,$errorMessage=NULL, $callerName=NULL, $ADAErrorCode=NULL, $severity=NULL, $redirectTo=NULL, $delayErrorHandling=FALSE
        $errorObj = new ADAError($res_presub, $message, null, null, null, $error_page . '?message=' . $message);
    }
} else { // id_course is null or was not set
    $error_page = $userObj->getHomePage();
    $message = urlencode(translateFN('Impossibile richiedere il servizio'));
    $errObj = new ADAError($res, $message, null, null, null, $error_page . '?message=' . $message);
    exit();
}

if ($isRegistration) {
    $redirect_to = HTTP_ROOT_DIR . "/browsing/confirm.php?op=confirm&message=$message";
} elseif ($isSubscription) {
    $redirect_to = $userObj->getHomePage($message);
} else {
    $message = urlencode(translateFN('Impossibile richiedere il servizio'));
    $redirect_to = $userObj->getHomePage($message);
}
// header("Location:".$userObj->getHomePage($message));
header("Location:" . $redirect_to);
exit();

<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\ARE;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\Course\CourseInstance;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

/**
 * SUBSCRIPTION.
 *
 * @package     main
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        registration
 * @version     0.1
 */

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/* Questa versione consento solo la sottoscrizione ad un servizio ma non la registrazione
 * deve essere passato l'id del servizio (id_corse?)
 * l'utente deve essere in sessione
 *
 */

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
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout'],
  AMA_TYPE_STUDENT         => ['layout'],
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


if ($_REQUEST['id_course']) {
    $testersAr = []; // serve anche a setuser()?;

    //FIXME: deve prendere il livello del servizio

    $tester_infoHa = $common_dh->getTesterInfoFromIdCourse($_REQUEST['id_course']);
    $tester = $tester_infoHa['puntatore'];
    $testersAr[0] = $tester; // it is a pointer (string)
    $testerId = $tester_infoHa['id_tester']; // it is an integer

    //  get service from course
    $id_course = $_REQUEST['id_course'];
    $serviceinfoAr = $common_dh->getServiceInfoFromCourse($id_course);
    if (AMADataHandler::isError($serviceinfoAr)) {
        // TODO: gestione errore
    }

    // find tester DH from tester pointer
    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));

    $start_date1 = 0;
    //  get the present date-time as timestamp
    $start_date2 = AMADataHandler::dateToTs("now");

    $days = $serviceinfoAr[4];
    $istanza_ha = [
       'data_inizio' => $start_date1,
           'durata' => $days,
           'data_inizio_previsto' => $start_date2,
           'id_layout' => null,
           ];

    // add an instance to tester db
    //echo "added $id_course to $tester";
    $res_inst_add = $tester_dh->courseInstanceAdd($id_course, $istanza_ha);
    if ((!AMADataHandler::isError($res_inst_add)) or ($res_inst_add->code == AMA_ERR_UNIQUE_KEY)) {
        // we add an instance OR there already was one with same data

        // get instance
        $clause = "id_corso = $id_course AND data_inizio_previsto = $start_date2 AND durata  = $days";
        $course_instanceAr = $tester_dh->courseInstanceFindList(null, $clause);
        $id_instance = $course_instanceAr[0][0];
        // presubscribe user to instance
        $res_presub = $tester_dh->courseInstanceStudentPresubscribeAdd($id_instance, $id_user);
    } else {
        $message = translateFN("Errore nella richiesta di servizio: 1");
        $errorObj = new ADA_Error($res_inst_add, $message, "registration.php");
    }

    $admtypeAr = [AMA_TYPE_ADMIN];
    $admList = $common_dh-> getUsersByType($admtypeAr);
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
        $testo .= translateFN(" ha richiesto il servizio: ");
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
            //  $errObj = new ADA_Error($res,translateFN('Impossibile spedire il messaggio'),
            // NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
        }
        unset($mh); // perché altrimenti manda due volte lo stesso messaggio?

        //  2. send a message to the switcher  if any
        $swtypeAr = [AMA_TYPE_SWITCHER];
        $switcherList = $tester_dh-> getUsersByType($swtypeAr);
        if (!AMADataHandler::isError($switcherList)) {
            $switcher_uname = $switcherList[0]['username']; // there should be only one sw per tester
            $titolo = translateFN("Richiesta di servizio");
            $destinatari = [$switcher_uname];
            $testo = translateFN("Un utente con id: ");
            $testo .= $id_user;
            $testo .= translateFN(" ha richiesto il servizio: ");
            $testo .= $id_course; // NOTE: it is the service implementation ID, not the service ID
            $testo .= translateFN(" nel tester: ");
            $testo .= $tester . ". ";
            $testo .= translateFN("Appena possibile visita questo ");
            $link = "<a href='" . HTTP_ROOT_DIR . "/switcher/assign_practitioner.php'>link</a>";
            $testo .= $link;
            $mh = MessageHandler::instance(MultiPort::getDSN($tester));

            // prepare message to send
            $message_ha = [];
            $message_ha['titolo'] = $titolo;
            $message_ha['testo'] = $testo;
            $message_ha['destinatari'] = $destinatari;
            $message_ha['data_ora'] = "now";
            $message_ha['tipo'] = ADA_MSG_SIMPLE; // oppure mail?
            $message_ha['mittente'] = $adm_uname; // admin?

            $res = $mh->sendMessage($message_ha);
            if (AMADataHandler::isError($res)) {
                //  $errObj = new ADA_Error($res,translateFN('Impossibile spedire il messaggio'),
                // NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
            }
            unset($mh);
        } else {
            $switcher_uname = ""; // probably was a public service or an error
        }

        // 3. send a message to the user (a mail, an SMS, ...)
        $titolo = translateFN("Richiesta di servizio");

        $testo = translateFN("Un utente con dati: ");
        $testo .= $name . " " . $surname;
        $testo .= translateFN(" ha richiesto  il servizio id: ");
        $testo .= $id_course; //FIXME: should be the service's name...'
        $testo .= translateFN(" La richiesta verr&agrave; trattata nelle prossime ore");
        $testo .= translateFN(" e ti verr&agrave; inviato un messaggio contenente le proposte di appuntamento. ");

        // $mh = MessageHandler::instance(MultiPort::getDSN($tester));
        // using the previous  MH if exists
        //if (!isset($mh))
        $mh = MessageHandler::instance(MultiPort::getDSN($tester));

        // prepare message to send
        $message2_ha = [];
        $message2_ha['titolo'] = $titolo;
        $message2_ha['testo'] = $testo;
        $message2_ha['destinatari'] = [$username];
        $message2_ha['data_ora'] = "now";
        $message2_ha['tipo'] = ADA_MSG_MAIL;
        $message2_ha['mittente'] = $adm_uname;

        // delegate sending to the message handler
        $res2 = $mh->sendMessage($message2_ha);

        if (AMADataHandler::isError($res2)) {
            // $errObj = new ADA_Error($res,translateFN('Impossibile spedire il messaggio'),
            //NULL,NULL,NULL,$error_page.'?err_msg='.urlencode(translateFN('Impossibile spedire il messaggio')));
        }
        unset($mh);
    } else { // a real error
        $message = translateFN("Errore nella richiesta di servizio: 2");
        //$AMAErrorObject=NULL,$errorMessage=NULL, $callerName=NULL, $ADAErrorCode=NULL, $severity=NULL, $redirectTo=NULL, $delayErrorHandling=FALSE
        $errorObj = new ADA_Error($res_presub, $message, "subscription.php");
    }
} else {
    $errorObj = new ADA_Error($res_inst_add, $message, "subscription.php");
}

$menu = "<a href=\"#course_list\">" . translateFN("elenco servizi") . "</a><br>";
$menu .= $register_link;
$menu .= $enroll_link;

$title = translateFN('Informazioni');

$content_dataAr = [
  'user_name'   => $user_name,
  'home'        => $home,
  'iscrivibili' => $form,
  'help'        => $hlpmsg,
  'menu'        => $menu,
  'message'     => $message,
  'status'      => $status,
];



/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);


// end module

<?php

namespace Lynxlab\ADA\Main\Service\Functions;

use Lynxlab\ADA\CORE\HmtlElements\Table;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;

function _get_course_instance_info($id_course, $id_course_instance)
{

    $common_dh = $GLOBALS['common_dh'];
    $dh = $GLOBALS['dh'];
    $sess_id_user = $_SESSION['sess_id_user'];
    $userObj = $_SESSION['sess_userObj'];


    $course_dataHa = $common_dh->get_service_info_from_course($id_course);
    $service_title = $course_dataHa[1];
    $service_level = $course_dataHa[3];
    //..


    $provider_dataHa = $common_dh->get_tester_info_from_id_course($id_course);
    if (!AMA_DataHandler::isError($provider_dataHa)) {
        $provider_pointer = $provider_dataHa['puntatore'];
        $provider_name =  $provider_dataHa['nome'];
        $provider_dsn = Multiport::getDSN($provider_pointer);
        if ($provider_dsn != null) {
            $provider_dh = AMA_DataHandler::instance($provider_dsn);
            $sub_courses = $provider_dh->get_subscription($sess_id_user, $id_course_instance);
            // if (!AMA_DataHandler::isError($sub_courses)&&$sub_courses['tipo'] == 2) { // introducing status 3 (suspended) and 5 (completed)
            if (!AMA_DataHandler::isError($sub_courses)) { // introducing status 3 (suspended) and 5 (completed)
                $info_dataHa = [];
                $id_tutor = $dh->course_instance_tutor_get($id_course_instance);
                // vito, 27 may 2009
                if ($id_tutor !== false) {
                    $tutor = $dh->get_tutor($id_tutor);
                    // vito, 27 may 2009
                    if (!AMA_DataHandler::isError($tutor) && is_array($tutor)) {
                        $tutor_name = $tutor['nome'] . " " . $tutor['cognome'];
                        if (empty($tutor_name)) {
                            $tutor_info = translateFN('Non assegnato');
                        } else {
                            //  if (isset($sess_id_user)){
                            // $tutor_info = "<a href=\"$http_root_dir/admin/zoom_tutor.php?id=$id_tutor\">$tutor_name</a>";
                            // } else{
                            $tutor_info = $tutor_name;
                            //  }
                        }
                    }
                } else {
                    // vito, 27 may 2009
                    $tutor_info =  translateFN('Non assegnato');
                }

                $start_date = ts2dFN($sub_courses['istanza_ha']['data_inizio']);

                // messaggi
                $messages_list = ""; // FIXME


                // appuntamenti
                $msgs_ha = MultiPort::getUserAgenda($userObj);
                if (AMA_DataHandler::isError($msgs_ha)) {
                    $errObj = new ADA_Error($msgs_ha, translateFN('Errore in lettura appuntamenti'));
                }
                $testers_dataAr = MultiPort::getTestersPointersAndIds();
                $meeting_List   = CommunicationModuleHtmlLib::getAgendaAsForm($dataAr, $testers_dataAr);


                //  $label_provider = translateFN('Fornitore');
                //  $label_title = translateFN('Titolo');
                $label_date = translateFN('Data di inizio');
                $label_tutor = translateFN('Tutor');
                $label_meeting = translateFN('Appuntamenti');
                $label_messages = translateFN('Messaggi');

                $row = [
              //    $label_provider=>$tester_name, // attenzione: è l'ultimo della lista!!!!'
              //    $label_title=>$service_title,
                "<img src=\"img/flag.png\" border=0> " . $label_date => $start_date,

                $label_tutor => $tutor_info,
                $label_meeting => $meeting_list,
                $label_messages => $messages_list,

                //        "<img src=\"img/author.png\" border=0> ".translateFN('Autore')=>$author_info
                ];

                array_push($info_dataHa, $row);

                $tObj = new Table();
                $tObj->initTable('1', 'center', '0', '1', '100%', '', '', '', '', 1, 1);
                $caption = "<strong>" . translateFN("Storico del servizio") . "</strong>";
                $summary = translateFN("Storico del servizio");
                $tObj->setTable($info_dataHa, $caption, $summary);
                $requested_service_data = $tObj->getTable();
            } else {
                $requested_service_data = sprintf(translateFN("Nessun'informazione disponibile sul servizio %d.", $id_course_instance));
            }
        } else {
            $requested_service_data = sprintf(translateFN("Nessun'informazione disponibile sul servizio %d.", $id_course_instance));
        }
    } else {
        $requested_service_data = sprintf(translateFN("Nessun'informazione disponibile sul servizio %d.", $id_course_instance));
    }

    return $requested_service_data;
}

function level2descriptionFN($level)
{
    // FIXME: it would be better if we had a DB table for this...
    switch ($level) {
        case 1:
        case "1":
        default:
            $levelAsDescription = "Information on educational and professional issues are provided by the
tester organisations in order to be self-consulted by users.
The user of one country will also be able to search for educational and vocational information
concerning another one of the partner countries. Information is available in English and in the
language of the information provider.";
            break;
        case 2:
        case "2":
            $levelAsDescription = "At the end of the self-guidance path the user could need customised advice with a
guidance practitioner on the information found out. It is thus possible to have an interactive interview with
the e-guidance practitioner through the use of chat rooms, free-phone calls, videoconference.
The user of one country could require customised advice on the above-mentioned issues concerning another
partner country. In that case, advice will be delivered in English by the officer/practitioner.";
            break;
        case 3:
        case "3":
            $levelAsDescription = "A user could need a deep counselling interview in order to receive help in finding a job, tips
on a job interview, CV, information resources and other issues already detailed under the list of the counselling
on educational and vocational issues activity. It is thus possible to have an interactive interview with the e-guidance practitioner through the use of chat rooms, free-phone calls, videoconference and other ICT-based
tools.";
            break;
        case 4:
        case "4":
            $levelAsDescription = "A user could need a specialised guidance action, highly customised and long ones such as:<br />" .
                "Group counselling for the active job search<br /> Skills assessment paths<br /> Tutoring and support paths to employability for people with more difficulties.";
            break;
    }
    return translateFN($levelAsDescription);
}

function level2stringFN($level)
{

    switch ($level) {
        case 1:
        case "1":
        default:
            $levelAsString = "1: Informazioni";
            break;
        case 2:
        case "2":
            $levelAsString = "2: Colloquio di orientamento";
            break;
        case 3:
        case "3":
            $levelAsString = "3: Consulenza";
            break;
        case 4:
        case "4":
            $levelAsString = "4: Consulenza specialistica";
            break;
    }
    return translateFN($levelAsString);
}

function subscriptionType2stringFN($tipo)
{
    /*
    define('ADA_SERVICE_SUBSCRIPTION_STATUS_UNDEFINED' , 0);
    define('ADA_SERVICE_SUBSCRIPTION_STATUS_REQUESTED' , 1);
    define('ADA_SERVICE_SUBSCRIPTION_STATUS_ACCEPTED'  , 2);
    define('ADA_SERVICE_SUBSCRIPTION_STATUS_SUSPENDED' , 3);
    define('ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED' , 5);
    */
    switch ($tipo) {
        case ADA_SERVICE_SUBSCRIPTION_STATUS_UNDEFINED:
        default: //ADA_STATUS_REGISTERED:default:
            $typeAsString = "Registrato";
            break;
        case ADA_SERVICE_SUBSCRIPTION_STATUS_REQUESTED: //ADA_STATUS_PRESUBSCRIBED:
            $typeAsString = "Preiscritto";
            break;
        case ADA_SERVICE_SUBSCRIPTION_STATUS_ACCEPTED: //ADA_STATUS_SUBSCRIBED:
            $typeAsString = "Iscritto";
            break;
        case ADA_SERVICE_SUBSCRIPTION_STATUS_SUSPENDED: //ADA_STATUS_REMOVED:
            $typeAsString = "Sospeso";
            break;
        case ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED:
            $typeAsString = "Completato";
            break;
        case ADA_STATUS_VISITOR:
            $typeAsString = "Visitatore";
            break;
    }
    return translateFN($typeAsString);
}

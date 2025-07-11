<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Output\PdfClass;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Tutor\Functions\getCoursesTutorFN;
use function Lynxlab\ADA\Tutor\Functions\getStudentCoursesFN;
use function Lynxlab\ADA\Tutor\Functions\getStudentDataFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();

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
TutorHelper::init($neededObjAr);
/*
 * YOUR CODE HERE
 */
include_once ROOT_DIR . '/config/config_class_report.inc.php';
$mode = DataValidator::checkInputValues('mode', 'Value', INPUT_GET, 'load');
$speed_mode = DataValidator::checkInputValues('speed_mode', 'Value', INPUT_GET, true) ? true : false;
// $speed_mode = (!isset($_GET['speed_mode']) || (isset($_GET['speed_mode']) && $_GET['speed_mode'] !== 'false'));

if (!isset($op)) {
    $op = null;
}

/**
 * check if it's not a supertutor asking for op='tutor'
 * then set $op to make the default action
 */
if (!$userObj->isSuper() && $op == 'tutor') {
    $op = null;
}

switch ($op) {
    case 'tutor':
        $help = '';
        $fieldsAr = ['nome','cognome','username'];
        $tutorsAr = $dh->getTutorsList($fieldsAr);
        if (!AMADB::isError($tutorsAr) && is_array($tutorsAr) && count($tutorsAr) > 0) {
            $tableDataAr = [];
            $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/details_open.png');
            $imgDetails->setAttribute('title', translateFN('visualizza/nasconde i dettagli del tutor'));
            $imgDetails->setAttribute('style', 'cursor:pointer;');
            $imgDetails->setAttribute('class', 'tooltip');

            $mh = MessageHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

            foreach ($tutorsAr as $aTutor) {
                // open details button
                $imgDetails->setAttribute('onclick', "toggleTutorDetails(" . $aTutor[0] . ",this);");
                // received messages
                $receivedMessages = 0;
                $msgs_ha = $mh->getMessages($aTutor[0], ADA_MSG_SIMPLE);
                if (!AMADataHandler::isError($msgs_ha)) {
                    $receivedMessages = count($msgs_ha);
                }
                // sent messages
                $sentMessages = 0;
                $msgs_ha = $mh->getSentMessages($aTutor[0], ADA_MSG_SIMPLE);
                if (!AMADataHandler::isError($msgs_ha)) {
                    $sentMessages = count($msgs_ha);
                }
                $tableDataAr[] = array_merge([$imgDetails->getHtml()], $aTutor, [$receivedMessages,$sentMessages]);
            }
        }
        $thead = [null,
                translateFN('Id'),
                translateFN('Nome'),
                translateFN('Cognome'),
                translateFN('username'),
                translateFN('Msg Ric'),
                translateFN('Msg Inv'),
        ];
        $tObj = BaseHtmlLib::tableElement('id:listTutors', $thead, $tableDataAr, null, translateFN('Elenco dei tutors'));
        $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
        $data = $tObj->getHtml();
        break;
    case 'student_level':
        $studenti_ar = [$id_student];
        $info_course = $dh->getCourse($id_course);
        if (AMADataHandler::isError($info_course)) {
        } else {
            $updated = $dh->setStudentLevel($id_instance, $studenti_ar, $level);
            if (AMADataHandler::isError($updated)) {
                // GESTIRE ERRORE.
            } else {
                header('Location: tutor.php?op=student&id_instance=' . $id_instance . '&id_course=' . $id_course . '&mode=update');
                exit();
            }
        }
        break;
    case 'student':
    case 'class_report': // Show the students subscribed in selected course and a report
        if (!isset($id_course)) {
            $id_course = $dh->getCourseIdForCourseInstance($id_instance);
            if (AMADataHandler::isError($id_course)) {
                $id_course = 0;
            }
        }
        /*
        if ($mode=='update') {
        */
        if (!isset($order)) {
            $order = null;
        }
        $courses_student = getStudentCoursesFN($id_instance, $id_course, $order, "HTML", $speed_mode);

        if (!is_null($courses_student)) {
            if (isset($courses_student['report_generation_date']) && !is_null($courses_student['report_generation_date'])) {
                $report_generation_TS = $courses_student['report_generation_date'];
                unset($courses_student['report_generation_date']);
            }
            $thead = array_shift($courses_student);
            $tfoot = array_pop($courses_student);
            $tObj = BaseHtmlLib::tableElement('id:table_Report', $thead, $courses_student, $tfoot, null);
            $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
            $data = $tObj->getHtml();
        } else {
            /*
            if ($mode=='update') {
            */
            $data = translateFN("Non ci sono studenti in questa classe");
            /*
            } else {
//              $http_root_dir = $GLOBALS['http_root_dir'];
//              $data  = translateFN("Non è presente un report dell'attivita' della classe aggiornato alla data odierna. ");
//              $data .= "<a href=\"$http_root_dir/tutor/tutor.php?op=student&id_instance=$id_instance&id_course=$id_course&mode=update\">";
//              $data .= translateFN("Aggiorna il report.");
//              $data .= "</a>";
            */
            /**
             * @author giorgio 27/ott/2014
             *
             * if no class report was ever generated, redirect the user to the mode=update page
             */
            /*
                Utilities::redirect("$http_root_dir/tutor/tutor.php?op=student&id_instance=$id_instance&id_course=$id_course&mode=update");
            }
            */
        }

        $info_course = $dh->getCourse($id_course); // Get title course
        if (AMADataHandler::isError($info_course)) {
            $msg = $info_course->getMessage();
            $data = $msg;
        } else {
            if (isset($sess_id_course_instance) && !empty($sess_id_course_instance)) {
                $id_chatroom = $sess_id_course_instance;
            } elseif (isset($id_instance) && !empty($id_instance)) {
                $id_chatroom = $id_instance;
            }

            $course_title = $info_course['titolo'];

            $sess_id_course_instance = $id_instance;

            $sess_id_course = $id_course;

            $chat_link = "<a href=\"$http_root_dir/comunica/chat.php\" target=_blank>" . translateFN('chat di classe') . '</a>';

            $instance_course_ha = $dh->courseInstanceGet($id_instance); // Get the instance courses data
            $start_date =  AMADataHandler::tsToDate($instance_course_ha['data_inizio'], ADA_DATE_FORMAT);

            $help = translateFN("Studenti del corso") . " <strong>$course_title</strong>  - " .
                    translateFN("Classe") . " " . $instance_course_ha['title'] . " (" .
                    $id_instance . ") - " . translateFN("Iniziato il ");
            $help .= "&nbsp;<strong>$start_date</strong>" ;
            $help .= '<br />' . translateFN("Cliccando sui dati si accede al dettaglio.");
            $help .= '<br />' . translateFN('Ricordarsi di aggiornare il report dopo aver finito le modifiche ai livelli degli studenti.');
            $help .= '<br />' . translateFN('L\'operazione di aggiornamento del report può richiedere qualche minuto');
            if (isset($report_generation_TS)) {
                $updateDIV = CDOMElement::create('div', 'class:updatelink');
                $updateSPAN = CDOMElement::create('span');
                $updateSPAN->addChild(new CText(translateFN('Report aggiornato al') . ' ' . Utilities::ts2dFN($report_generation_TS)));
                $updateBtnCont = CDOMElement::create('div', 'class:updateButtoncont');
                $updateLink = CDOMElement::create('a', 'class:ui tiny green button,href:javascript:void(0);');
                $confirmMessage = translateFN('Questa operazione può richiedere qualche minuto');
                $updateLink->setAttribute('onclick', 'javascript:' .
                        'if (confirm(decodeURI(\'' . urlencode($confirmMessage) . '\').replace(/\+/g, \' \'))) ' .
                        'self.document.location.href=\'' .
                        $http_root_dir .
                        '/tutor/tutor.php?op=student&id_instance=' . $id_instance .
                        '&id_course=' . $id_course . '&mode=update' .
                        '\';');
                $updateLink->addChild(new CText(' ' . translateFN("Aggiorna il report")));
                $updateDIV->addChild($updateSPAN);
                $updateBtnCont->addChild($updateLink);
                $updateDIV->addChild($updateBtnCont);
                $help .= $updateDIV->getHtml();
            }
        }

        break;

    case 'student_notes':   // nodi inseriti dallo studente
    case 'student_notes_export':
        $student_dataHa = $dh->getUserInfo($id_student);
        $studente_username = $student_dataHa['username'];
        //          if (isset($id_course)){    // un corso (e un'istanza...) alla volta ?
        $sub_course_dataHa = [];
        $today_date = $dh->dateToTs("now");
        $clause = "data_inizio <= '$today_date' AND data_inizio != '0'";
        $field_ar = ['id_corso','data_inizio','data_inizio_previsto'];
        $all_instance = $dh->courseInstanceFindList($field_ar, $clause);
        if (is_array($all_instance)) {
            $added_nodesHa = [];
            foreach ($all_instance as $one_instance) {
                //Utilities::mydebug(__LINE__,__FILE__,$one_instance);
                $id_course_instance = $one_instance[0];
                //check on tutor:
                //           $tutorId = $dh->courseInstanceTutorGet($id_course_instance);
                //           if (($tutorId == $sess_id_user)  AND ($id_course_instance == $sess_id_course_instance))
                // warning: 1 tutor per class ! ELSE: $tutored_instancesAr = $dh->courseTutorInstanceGet($sess_id_user); etc
                // check only on course_instance
                if ($id_course_instance == $id_instance) {
                    $id_course = $one_instance[1];
                    $data_inizio = $one_instance[2];
                    $data_previsto = $one_instance[3];
                    $sub_courses = $dh->getSubscription($id_student, $id_instance);
                    //Utilities::mydebug(__LINE__,__FILE__,$sub_courses);
                    if ($sub_courses['tipo'] == 2) {
                        $out_fields_ar = ['nome','titolo','id_istanza','data_creazione','testo'];
                        $clause = "tipo = '2' AND id_utente = '$id_student'";
                        $nodes = $dh->findCourseNodesList($out_fields_ar, $clause, $id_course);
                        $course = $dh->getCourse($id_course);
                        $course_title = $course['titolo'];
                        $node_index = translateFN("Nodi aggiunti dallo studente:") . $studente_username . "\n\n";
                        foreach ($nodes as $one_node) {
                            $row = [
                                    translateFN('Corso') => $course_title,
                                    //      translateFN('Edizione')=>$id_course_instance."(".Utilities::ts2dFN($data_inizio).")",
                                    translateFN('Data') => Utilities::ts2dFN($one_node[4]),
                                    translateFN('Nodo') => $one_node[0],
                                    translateFN('Titolo') => "<a href=\"$http_root_dir/browsing/view.php?id_node=" . $one_node[0] . "&id_course=$id_course&id_course_instance=$id_instance\">" . $one_node[1] . "</a>",
                                    //    translateFN('Keywords')=>$one_node[2]
                            ];
                            array_push($added_nodesHa, $row);
                            // exporting  to RTF
                            $note =  Utilities::ts2dFN($one_node[4]) . "\n" .
                                    $one_node[1] . "\n" . // title
                                    $one_node[5] . "\n"; //text

                            $node_index .= $note . "\n____________________________\n";
                        }
                    }
                }
            }
        }


        /*
             global $debug; $debug=1;
             Utilities::mydebug(__LINE__,__FILE__,$added_nodesHa);
             $debug=0;
        */

        if ($op == 'student_notes_export') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            // always modified
            header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");                          // HTTP/1.0
            //header("Content-Type: text/plain");
            header("Content-Type: application/rtf");
            //header("Content-Length: ".filesize($name));
            header("Content-Disposition: attachment; filename=forum_" . $id_course . "_class_" . $id_instance . "_student_" . $id_student . ".rtf");
            echo $node_index;
            exit;
        } else {
            $tObj = new Table();
            $tObj->initTable('1', 'center', '0', '1', '100%', '', '', '', '', '1', '1');
            // Syntax: $border,$align,$cellspacing,$cellpadding,$width,$col1, $bcol1,$col2, $bcol2
            $caption = "<strong>" . translateFN("Nodi inseriti nel forum") . "</strong>";
            $summary = translateFN("Nodi inseriti nel forum del corso");
            $tObj->setTable($added_nodesHa, $caption, $summary);
            $added_notesHa = $tObj->getTable();
            $data = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $added_notesHa, 1); // replace first occurence of class
            $status = translateFN('note aggiunte dallo studente');

            //           $data['chat_users']=$online_users;
            $help = translateFN('Da qui il Tutor può leggere le note aggiunte nel forum da questo studente.');
        }
        break;

    case 'zoom_student':
        //$sess_id_course_instance = $id_instance;
        if (isset($id_course)) {
            $info_course = $dh->getCourse($id_course); // Get title course
            if (AMADataHandler::isError($info_course)) {
                $msg = $info_course->getMessage();
            } else {
                $course_title = $info_course['titolo'];
            }
        }
        // Who's online
        // $online_users_listing_mode = 0 (default) : only total numer of users online
        // $online_users_listing_mode = 1  : username of users
        // $online_users_listing_mode = 2  : username and email of users

        $online_users_listing_mode = 2;
        $online_users = ADALoggableUser::getOnlineUsersFN($id_instance, $online_users_listing_mode);

        $chat_link = '<a href="' . HTTP_ROOT_DIR
                   . '/comunica/adaChat.php target=_blank>'
                   . translateFN('chat') . '</a>';

        $data = getStudentDataFN($id_student, $id_instance);
        $data = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $data, 1); // replace first occurence of class

        $status = translateFN('caratteristiche dello studente');

        $help = translateFN('Da qui il Tutor può consultare le caratteristiche di uno studente.');
        break;

    case 'export': // outputs the users of selected course as a file excel
        // $courses_student = get_student_courses_tableFN($id_instance, $id_course);

        /**
         * @author giorgio 14/mag/2013
         *
         * set allowed types of export and if $type is not in the list
         * than default to xls type export.
         *
         */
        $allowed_export_types =  ['xls', 'pdf'];
        if (!in_array($type, $allowed_export_types)) {
            $type = 'xls';
        }

        // get needed data
        $courses_student = getStudentCoursesFN($id_instance, $id_course, '', ($type == 'xls') ? 'HTML' : 'FILE', $speed_mode);

        // build the caption
        // 0. Get title course
        $info_course = $dh->getCourse($id_course);
        if (AMADB::isError($info_course)) {
            $course_title = '';
        } else {
            $course_title =  $info_course['titolo'];
        }
        // 1. Get the instance courses data
        $instance_course_ha = $dh->courseInstanceGet($id_instance);
        if (AMADB::isError($instance_course_ha)) {
            $start_date = '';
            $instance_title = '';
        } else {
            $start_date =  AMADataHandler::tsToDate($instance_course_ha['data_inizio'], ADA_DATE_FORMAT);
            $instance_title = $instance_course_ha['title'];
        }

        $caption = translateFN("Studenti del corso") . " <strong>$course_title</strong>  - " .
                translateFN("Classe") . " " . $instance_title . " (" .
                $id_instance . ") - " . translateFN("Iniziato il ") . "&nbsp;<strong>$start_date</strong>" ;

        // build up filename to be streamed out
        $filename = 'course_' . $id_course . '_class_' . $id_instance . '.' . $type;

        if ($type === 'pdf') {
            $pdf = new PdfClass('landscape', strip_tags(html_entity_decode($courses_student['caption'] ?? '')));

            $pdf->addHeader(
                strip_tags(html_entity_decode($caption)),
                ROOT_DIR . '/layout/' . $userObj->template_family . '/img/header-logo.png',
                14
            )
                ->addFooter(translateFN("Report") . " " . translateFN("generato") . " " . translateFN("il") . " " . date("d/m/Y") . " " .
                             translateFN("alle") . " " . date("H:i:s"));

            // prepare header row
            foreach ($courses_student[0] as $key => $val) {
                // skip level up and down images, cannot be done in config file
                // because it would remove cols from html too, and this is not good
                if (preg_match('/img/', $val) !== 0) {
                    continue;
                }
                $cols[$key] = trim(strip_tags(html_entity_decode($val)));
            }

            array_shift($courses_student);
            // prepare data rows
            $data = [];
            $i = 0;
            foreach ($courses_student as $num => $elem) {
                foreach ($elem as $key => $val) {
                    $data[$i][$key] = trim(strip_tags(html_entity_decode($val)));
                }
                $i++;
            }
            array_shift($data);
            $pdf->ezTable(
                $data,
                $cols,
                strip_tags(html_entity_decode($courses_student['caption'] ?? '')),
                ['width' => $pdf->ez['pageWidth'] - $pdf->ez['leftMargin'] - $pdf->ez['rightMargin']]
            );
            $pdf->saveAs($filename);
        } elseif ($type === 'xls') {
            $tObj = BaseHtmlLib::tableElement('id:table_Report', array_shift($courses_student), $courses_student, [], null);
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");          // always modified
            header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");                          // HTTP/1.0
            header("Content-Type: application/vnd.ms-excel");
            // header("Content-Length: ".filesize($name));
            header("Content-Disposition: attachment; filename=course_" . $id_course . "_class_" . $id_instance . ".xls");
            echo  $tObj->getHtml();
            // header ("Connection: close");
        }
        exit();
        break;


    case 'list_courses':
    default:
        if (!isset($status) || empty($status)) {
            $data['status'] = translateFN('lista dei corsi tutorati');
        }
        $isSuper = (isset($userObj) && $userObj instanceof ADAPractitioner && $userObj->isSuper());
        $data = getCoursesTutorFN($_SESSION['sess_id_user'], $isSuper);
        $help = translateFN("Da qui il Tutor può visualizzare l'elenco dei corsi di cui è attualmente tutor.");
        $online_users_listing_mode = 2;
        if (!isset($id_course_instance)) {
            $id_course_instance = null;
        }
        $online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);


        $chat_link = "<a href=\"../comunica/adaChat.php\" target=_blank>" . translateFN("chat") . "</a>";

        break;
}


$online_users_listing_mode = 2;
//$online_users = ADAGenericUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);
if (!isset($id_course_instance)) {
    $id_course_instance = null;
}
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);

if (!empty($id_instance)) {
    $courseInstanceObj = new CourseInstance($id_instance);
    $courseObj = new Course($courseInstanceObj->id_corso);
    $course_title = $courseObj->titolo;
    //show course istance name if isn't empty - valerio
    if (!empty($courseInstanceObj->title)) {
        $course_title .= ' - ' . $courseInstanceObj->title;
    }

    if (!isset($nodeObj) || !is_object($nodeObj)) {
        if (!isset($node)) {
            $node = null;
        }
        $nodeObj = DBRead::readNodeFromDB($node);
    }

    if (!ADAError::isError($nodeObj) and isset($courseObj->id)) {
        $_SESSION['sess_id_course'] = $courseObj->id;
        $node_path = $nodeObj->findPathFN();
    }
}

if (isset($courseObj) && $courseObj instanceof Course && strlen($courseObj->getTitle()) > 0) {
    $course_title = ' > <a href="' . HTTP_ROOT_DIR . '/browsing/main_index.php">' . $courseObj->getTitle() . '</a>';
}

$content_dataAr = [
    'course_title' => translateFN('Modulo tutor') . (isset($course_title) ? (' ' . $course_title) : null),
    'path' => $node_path ?? '',
    'user_name' => $user_name,
    'user_type' => $user_type,
    'edit_profile' => $userObj->getEditProfilePage(),
    'level' => $user_level,
    'messages' => $user_messages->getHtml(),
//        'events'=>$user_events->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'help'  => $help,
    'dati'  => $data,
    'status' => $status,
    'chat_users' => $online_users,
    'chat_link' => $chat_link ?? '',
 ];

$layout_dataAr['CSS_filename'] =  [
        JQUERY_UI_CSS,
        SEMANTICUI_DATATABLE_CSS,
];
$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_DATATABLE,
        SEMANTICUI_DATATABLE,
        JQUERY_DATATABLE_DATE,
        ROOT_DIR . '/js/include/jquery/dataTables/formattedNumberSortPlugin.js',
        JQUERY_NO_CONFLICT,
];
$menuOptions = [];
if (isset($id_course)) {
    $menuOptions['id_course'] = $id_course;
}
if (isset($id_instance)) {
    $menuOptions['id_instance'] = $id_instance;
}
if (isset($id_instance)) {
    $menuOptions['id_course_instance'] = $id_instance;
}
if (isset($id_student)) {
    $menuOptions['id_student'] = $id_student;
}
/**
 * add a define for the supertutor menu item to appear
 */
if ($userObj instanceof ADAPractitioner && $userObj->isSuper()) {
    define('IS_SUPERTUTOR', true);
} else {
    define('NOT_SUPERTUTOR', true);
}

$optionsAr['onload_func'] = 'initDoc(';
if (isset($id_course) && intval($id_course) > 0 && isset($id_instance) && intval($id_instance) > 0) {
    $optionsAr['onload_func'] .= $id_course . ',' . $id_instance;
}
$optionsAr['onload_func'] .= ');';

if (ModuleLoaderHelper::isLoaded('SERVICECOMPLETE') && in_array($op, ['student', 'class_report'])) {
    $layout_dataAr['JS_filename'][] = MODULES_SERVICECOMPLETE_PATH . '/js/condition-recap-modal.js';
    $layout_dataAr['CSS_filename'][] = MODULES_SERVICECOMPLETE_PATH . '/layout/' . $template_family . '/css/condition-recap.css';
    $optionsAr['onload_func'] .= 'initSummaryModal(\'' . str_replace(ROOT_DIR, '', MODULES_SERVICECOMPLETE_PATH) . '\')';
}

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr, $menuOptions);

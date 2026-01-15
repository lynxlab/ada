<?php

namespace Lynxlab\ADA\Tutor\Functions;

use InvalidArgumentException;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\Student;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

function getCoursesTutorFN($id_user, $isSuper = false)
{
    $dh = $GLOBALS['dh'];
    $ymdhms = $GLOBALS['ymdhms'];
    $http_root_dir = $GLOBALS['http_root_dir'];

    $all_instance = [];
    $sub_course_dataHa = [];
    $dati_corso = [];
    $today_date = $dh->dateToTs("now");
    $all_instance = $dh->courseTutorInstanceGet($id_user, $isSuper); // Get the instance courses monitorated by the tutor

    $num_courses = 0;
    $id_corso_key    = translateFN('Corso');
    $titolo_key      = translateFN('Titolo corso');
    $id_classe_key   = translateFN('Classe');
    $nome_key      = translateFN('Nome classe');
    $data_inizio_key = translateFN('Inizio');
    $durata_key      = translateFN('Durata');
    $azioni_key      = translateFN('Azioni');
    $msg = "";
    if (is_array($all_instance)) {
        foreach ($all_instance as $one_instance) {
            $num_courses++;
            $id_instance = $one_instance[0];
            $instance_course_ha = $dh->courseInstanceGet($id_instance); // Get the instance courses data
            if (AMADataHandler::isError($instance_course_ha)) {
                $msg .= $instance_course_ha->getMessage() . "<br />";
            } else {
                $id_course = $instance_course_ha['id_corso'];
                if (!empty($id_course)) {
                    $info_course = $dh->getCourse($id_course); // Get title course
                    if (AMADataHandler::isError($dh)) {
                        $msg .= $dh->getMessage() . "<br />";
                    }
                    if (!AMADB::isError($info_course)) {
                        $titolo = $info_course['titolo'];
                        $id_toc = $info_course['id_nodo_toc'];
                        $durata_corso = sprintf(translateFN('%d giorni'), $instance_course_ha['durata']);
                        $actions = [];
                        $naviga = BaseHtmlLib::link(
                            $http_root_dir . '/browsing/view.php?id_node=' . $id_course . '_' . $id_toc . '&id_course=' . $id_course . '&id_course_instance=' . $id_instance,
                            '<img src="img/timon.png"  alt="' . translateFN('naviga') . '" title="' . translateFN('naviga') . '" class="tooltip" border="0">'
                        );
                        $actions[] = $naviga;
                        $valuta = BaseHtmlLib::link(
                            $http_root_dir . '/tutor/tutor.php?op=student&id_instance=' . $id_instance . '&id_course=' . $id_course . '">',
                            '<img src="img/magnify.png"  alt="' . translateFN('valuta') . '" title="' . translateFN('valuta') . '" class="tooltip" border="0">'
                        );
                        $actions[] = $valuta;
                        $data_inizio = AMADataHandler::tsToDate($instance_course_ha['data_inizio'], "%d/%m/%Y");

                        $dati_corso[$num_courses][$id_corso_key] = $instance_course_ha['id_corso'];
                        $dati_corso[$num_courses][$titolo_key] = $titolo;
                        $dati_corso[$num_courses][$id_classe_key] =  $id_instance;
                        $dati_corso[$num_courses][$nome_key] =  $instance_course_ha['title'];
                        $dati_corso[$num_courses][$data_inizio_key] = $data_inizio;
                        $dati_corso[$num_courses][$durata_key] = $durata_corso;

                        if (defined('VIDEOCHAT_REPORT') && VIDEOCHAT_REPORT) {
                            $videochatlog = BaseHtmlLib::link(
                            $http_root_dir . '/tutor/videochatlog.php?id_course=' . $id_course . '&id_course_instance=' . $id_instance,
                            '<img src="img/videochatlog.png"  alt="' . translateFN('log videochat') . '" title="' . translateFN('log videochat') . '" class="tooltip" border="0">'
                            );
                            $actions[] = $videochatlog;
                        }

                        if (ModuleLoaderHelper::isLoaded('TEST')) {
                            $survey_title = translateFN('Report Sondaggi');
                            $survey_img = CDOMElement::create('img', 'src:img/_exer.png,alt:' . $survey_title . ',class:tooltip,title:' . $survey_title);
                            $survey_link = BaseHtmlLib::link(MODULES_TEST_HTTP . '/surveys_report.php?id_course_instance=' . $id_instance . '&id_course=' . $id_course, $survey_img->getHtml());
                            $actions[] = $survey_link;
                        }
                        if (ModuleLoaderHelper::isLoaded('BADGES')) {
                            $badges_title = translateFN('Badges disponibili');
                            $badges_img = CDOMElement::create('img', 'src:' . MODULES_BADGES_HTTP . '/layout/' . $_SESSION['sess_template_family'] . '/img/course-badges.png,alt:' . $badges_title . ',class:tooltip,title:' . $badges_title);
                            $badges_link = BaseHtmlLib::link(MODULES_BADGES_HTTP . '/user-badges.php?id_instance=' . $id_instance . '&id_course=' . $id_course, $badges_img->getHtml());
                            $actions[] = $badges_link;
                        }
                        $dati_corso[$num_courses][$azioni_key] = implode('', array_map(fn ($e) => $e->getHtml(), $actions));
                    }
                }
            }
        }

        $courses_list = "";
        if ((count($dati_corso) > 0) && (empty($msg))) {
            $caption = translateFN("Corsi monitorati al") . " $ymdhms";
            $tObj = BaseHtmlLib::tableElement(
                'id:listCourses',
                [  $id_corso_key, $titolo_key, $id_classe_key,
                            $nome_key,
                $data_inizio_key,
                $durata_key,
                $azioni_key],
                $dati_corso,
                null,
                $caption
            );
            $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
            $courses_list = $tObj->getHtml();
        } else {
            $courses_list = $msg;
        }
    } else {
        $tObj = new Table();
        $tObj->initTable('0', 'center', '0', '1', '', '', '', '', '', '1');
        $caption = sprintf(translateFN("Non ci sono corsi monitorati da te al %s"), $ymdhms);
        $summary = translateFN("Elenco dei corsi monitorati");
        $tObj->setTable($dati_corso, $caption, $summary);
        $courses_list = $tObj->getTable();
    }
    if (empty($courses_list)) {
        $courses_list = translateFN('Non ci sono corsi di cui sei tutor.');
    }
    return $courses_list;
}

// @author giorgio 14/mag/2013
// added type parameter that defaults to 'xls'
function getStudentCoursesFN($id_course_instance, $id_course, $order = "", $type = 'HTML', $speed_mode = true)
{
    // wrapper for Class \Lynxlab\ADA\Main\Course\Student
    // 2nd parameter empty string means get all students
    $student_classObj = new Student($id_course_instance, '');
    return $student_classObj->getClassReportFN($id_course, $order, '', $type, $speed_mode);
}

function getStudentDataFN($id_student, $id_instance)
{
    $dh = $GLOBALS['dh'];
    $http_root_dir = $GLOBALS['http_root_dir'];

    $student_info_ha = $dh->getUserInfo($id_student); // Get info of each student
    if (AMADataHandler::isError($student_info_ha)) {
        $msg = $student_info_ha->getMessage();
        return $msg;
    }

    $instance_course_ha = $dh->courseInstanceGet($id_instance); // Get the instance courses data
    if (AMADataHandler::isError($instance_course_ha)) {
        $msg = $instance_course_ha->getMessage();
        return $msg;
    }

    $id_course = $instance_course_ha['id_corso'];
    $start_date =  AMADataHandler::tsToDate($instance_course_ha['data_inizio'], ADA_DATE_FORMAT);

    $info_course = $dh->getCourse($id_course); // Get title course
    if (AMADataHandler::isError($info_course)) {
        $msg = $info_course->getMessage();
        return $msg;
    }
    $course_title = $info_course['titolo'];

    $name = $student_info_ha['nome'];
    $name_desc = "<B>" . translateFN("Nome") . "</B>";
    $surname = $student_info_ha['cognome'];
    $surname_desc = "<B>" . translateFN("Cognome") . "</B>";
    $email = $student_info_ha['email'];
    $email_desc = "<B>" . translateFN("Email") . "</B>";
    $phone_n = $student_info_ha['telefono'];
    $phone_desc = "<B>" . translateFN("Telefono") . "</B>";
    $user = $student_info_ha['username'];
    $user_desc = "<B>" . translateFN("User Name") . "</B>";
    $course_desc = "<B>" . translateFN("Titolo del Corso") . "</B>";
    $start_desc = "<B>" . translateFN("Data di inizio") . "</B>";

    $dati_stude[0]['name_desc'] = $name_desc;
    $dati_stude[0]['name'] = $name;
    $dati_stude[1]['surname_desc'] = $surname_desc;
    $dati_stude[1]['surname'] = $surname;
    $dati_stude[2]['email_desc'] = $email_desc;
    $dati_stude[2]['email'] = $email;
    $dati_stude[3]['phone_desc'] = $phone_desc;
    $dati_stude[3]['phone'] = $phone_n;
    $dati_stude[4]['user_desc'] = $user_desc;
    $dati_stude[4]['user'] = $user;
    $dati_stude[5]['course_desc'] = $course_desc;
    $dati_stude[5]['course'] = $course_title;
    $dati_stude[6]['start_desc'] = $start_desc;
    $dati_stude[6]['start'] = $start_date;

    $tObj = new Table();
    // $tObj->initTable('0','center','0','1','100%','black','white','black','white');
    $tObj->initTable('1', 'center', '0', '1', '', '', '', '', '', '1');
    // Syntax: $border,$align,$cellspacing,$cellpadding,$width,$col1, $bcol1,$col2, $bcol2
    $caption = translateFN("Studente selezionato: <B>") . $id_student . "</B> ";
    // $summary = translateFN("Elenco dei corsi monitorati");
    $summary = "";
    // $tObj->setTable($dati_stude,$caption,$summary);
    $tObj->setTable($dati_stude, $caption, $summary);
    $student_info = $tObj->getTable();

    return $student_info;
}

function menuDetailsFN($id_student, $id_course_instance, $id_course)
{
    // Menu nodi visitati per periodo
    $menu_history = translateFN("Nodi visitati recentemente:") . "<br>\n" ;
    $menu_history .= "<a href=\"tutor_history_details.php?period=1&id_student=" . $id_student;
    $menu_history .= "&id_course_instance=" . $id_course_instance . "&id_course=" . $id_course . "\">" . translateFN("1 giorno") . "</a><br>\n";

    $menu_history .= "<a href=\"tutor_history_details.php?period=5&id_student=" . $id_student;
    $menu_history .= "&id_course_instance=" . $id_course_instance . "&id_course=" . $id_course . "\">" . translateFN("5 giorni") . "</a><br>\n";

    $menu_history .= "<a href=\"tutor_history_details.php?period=15&id_student=" . $id_student;
    $menu_history .= "&id_course_instance=" . $id_course_instance . "&id_course=" . $id_course . "\">" . translateFN("15 giorni") . "</a><br>\n";

    $menu_history .= "<a href=\"tutor_history_details.php?period=30&id_student=" . $id_student;
    $menu_history .= "&id_course_instance=" . $id_course_instance . "&id_course=" . $id_course . "\">" . translateFN("30 giorni") . "</a><br>\n";

    $menu_history .= "<a href=\"tutor_history_details.php?period=all&id_student=" . $id_student;
    $menu_history .= "&id_course_instance=" . $id_course_instance . "&id_course=" . $id_course . "\">" . translateFN("tutto") . "</a><br>\n";


    return $menu_history;
}

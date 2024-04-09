<?php

/**
 * Student class
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        courses_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Course;

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Bookmark\Bookmark;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Module\Badges\RewardedBadge;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\dt2tsFN;
use function Lynxlab\ADA\Main\Utilities\masort;
use function Lynxlab\ADA\Main\Utilities\today_dateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;

class Student
{
    public $id;
    public $id_course_instance;
    public $student_list;
    public $full;


    public function __construct($id_course_instance, $status = null)
    {
        $dh = $GLOBALS['dh'];
        // constructor
        if (is_null($status)) {
            $status = ADA_STATUS_SUBSCRIBED; // we want only subscribed students
        }
        $dataHa = $dh->course_instance_students_presubscribe_get_list($id_course_instance, $status); // Get student list of selected course

        if (AMA_DataHandler::isError($dataHa)) { // || (!is_array($dataHa))){ ** Se non e' un array non deve chiamare getMessage 12/05/2004
            $msg = $dataHa->getMessage();
            // header("Location: $error?err_msg=$msg");
        } else {
            if (!empty($dataHa[0]['id_utente_studente'])) {
                $this->full = 1;
                $this->student_list = $dataHa;
                $this->id = $id_course_instance;
            }
        }
    }

    public function get_student_coursesFN($id_course, $order = "", $speed_mode = true)
    {
        return $this->get_class_reportFN($id_course, $order, $speed_mode);
    }


    public function get_class_report_from_dbFN($id_course, $id_course_instance)
    {
        // last data from db tble
        $dh = $GLOBALS['dh'];
        $info_course = $dh->get_course($id_course); // Get title course
        if (AMA_DataHandler::isError($info_course)) {
            $msg = $info_course->getMessage();
            return $msg;
        }
        $course_title = $info_course['titolo'];
        $instance_course_ha = $dh->course_instance_get($id_course_instance); // Get the instance courses data
        if (AMA_DataHandler::isError($instance_course_ha)) {
            $msg = $instance_course_ha->getMessage();
            return $msg;
        }
        $start_date =  AMA_DataHandler::ts_to_date($instance_course_ha['data_inizio'], ADA_DATE_FORMAT);

        /**
         * @author giorgio 27/ott/2014
         * from now on, passing a null date to read_class_data
         * will get the most updated class report, so it's
         * safe to get rid of the following 2 lines of code
         *
         */
        //         $ymdhms = today_dateFN();
        //         $utime = dt2tsFN($ymdhms);

        $utime = null;
        $report_dataHa = $this->read_class_data($id_course, $id_course_instance, $utime);
        // vito, 16 luglio 2008, gestione dell'errore relativo alla chiamata a read_class_data
        if (AMA_DataHandler::isError($report_dataHa)) {
            $msg = $report_dataHa->getMessage();
            return $msg;
        }

        $num_student = count($report_dataHa);
        if ($num_student > 0) {
            /*
           * vito, 27 mar 2009. Add links to table data.
            */

            $totalHistory = 0;
            $totalExercies = 0;
            $totalExerciesMax = 0;
            $totalTest = 0;
            $totalTestMax = 0;
            $totalSurvey = 0;
            $totalSurveyMax = 0;
            $totalAddedNodes = 0;
            $totalReadNotes = 0;
            $totalMessageCountIn = 0;
            $totalMessageCountOut = 0;
            $totalChat = 0;
            $totalBookmarks = 0;
            $totalIndex = 0;
            $totalLevel = 0;
            $row = -1;

            $returnArray = [];
            if (isset($report_dataHa['report_generation_date'])) {
                $report_generation_TS = $report_dataHa['report_generation_date'];
                unset($report_dataHa['report_generation_date']);
            } else {
                $report_generation_TS = null;
            }

            if (MODULES_BADGES) {
                RewardedBadge::loadInstanceRewards($id_course, $id_course_instance);
            }

            foreach ($report_dataHa as $currentReportRow) {
                // returnArray elements order (keys) MUST be
                // the same as returned by get_class_report_from_db
                $returnArray[++$row]['id'] = $currentReportRow['id_stud'];
                $returnArray[$row]['student'] = '<a href="tutor.php?op=zoom_student&id_student=' . $currentReportRow['id_stud'] . '&id_course=' . $id_course . '&id_instance=' . $id_course_instance . '">' . $currentReportRow['student'] . '</a>';
                $returnArray[$row]['history']  = '<a href="tutor_history.php?id_student=' . $currentReportRow['id_stud'] . '&id_course=' . $id_course . '&id_course_instance=' . $id_course_instance . '">' . $currentReportRow['visits'] . '</a>';
                $returnArray[$row]['last_access'] = '<a href="tutor_history_details.php?period=1&id_student=' . $currentReportRow['id_stud'] . '&id_course=' . $id_course . '&id_course_instance=' . $id_course_instance . '">' . substr(ts2dFN($currentReportRow['date']), 0, -5) . '</a>';
                $returnArray[$row]['exercises'] = '<a href="tutor_exercise.php?id_student=' . $currentReportRow['id_stud'] . '&id_course_instance=' . $id_course_instance . '" class="dontwrap">' . $currentReportRow['score'] .
                    ' ' . translateFN('su') . ' ' . $currentReportRow['exercises'] * ADA_MAX_SCORE . '</a>';

                if (MODULES_TEST) {
                    $st_score_test = $currentReportRow['score_test'];
                    $st_score_norm_test = str_pad($st_score_test, 5, "0", STR_PAD_LEFT);
                    $st_exer_number_test = $currentReportRow['exercises_test'];
                    $returnArray[$row]['exercises_test'] = '<!-- ' . $st_score_norm_test . ' --><a href="' . MODULES_TEST_HTTP . '/tutor.php?op=test&id_course_instance=' . $id_course_instance . '&id_course=' . $id_course . '&id_student=' . $currentReportRow['id_stud'] . '" class="dontwrap">' . $st_score_test . ' ' . translateFN('su') . ' ' . $st_exer_number_test . '</a>';

                    $st_score_survey = $currentReportRow['score_survey'];
                    $st_score_norm_survey = str_pad($st_score_survey, 5, "0", STR_PAD_LEFT);
                    $st_exer_number_survey = $currentReportRow['exercises_survey'];
                    $returnArray[$row]['exercises_survey'] = '<!-- ' . $st_score_norm_survey . ' --><a href="' . MODULES_TEST_HTTP . '/tutor.php?op=survey&id_course_instance=' . $id_course_instance . '&id_course=' . $id_course . '&id_student=' . $currentReportRow['id_stud'] . '" class="dontwrap">' . $st_score_survey . ' ' . translateFN('su') . ' ' . $st_exer_number_survey . '</a>';
                }
                $returnArray[$row]['added_notes'] = '<a href="tutor.php?op=student_notes&id_student=' . $currentReportRow['id_stud'] . '&id_instance=' . $id_course_instance . '">' . $currentReportRow['notes_out'] . '</a>';
                $returnArray[$row]['read_notes'] = ($currentReportRow['notes_in'] > 0) ? $currentReportRow['notes_in'] : '-';
                $returnArray[$row]['message_count_in'] = $currentReportRow['msg_in'];
                $returnArray[$row]['message_count_out'] = $currentReportRow['msg_out'];
                $returnArray[$row]['chat'] = $currentReportRow['chat'];
                $returnArray[$row]['bookmarks'] = $currentReportRow['bookmarks'];
                $returnArray[$row]['index'] = $currentReportRow['indice_att'];
                $returnArray[$row]['status'] = sprintf("<!-- %d -->%s", $currentReportRow['status'], Subscription::subscriptionStatusArray()[$currentReportRow['status']]);
                if (MODULES_BADGES) {
                    $returnArray[$row]['badges'] = RewardedBadge::buildStudentRewardHTML($id_course, $id_course_instance, $currentReportRow['id_stud'])->getHtml();
                }
                $returnArray[$row]['level'] = '<span id="studentLevel_' . $currentReportRow['id_stud'] . '">' . $currentReportRow['level'] . '</span>';
                $forceUpdate = false;
                $linksHtml = $this->generateLevelButtons($currentReportRow['id_stud'], $forceUpdate);
                $returnArray[$row]['level_plus'] = (!is_null($linksHtml)) ? $linksHtml : '-';


                // UPDATE TOTALS

                $totalHistory         += $currentReportRow['visits'];
                $totalExercies        += $currentReportRow['exercises'];
                $totalExerciesMax     += $currentReportRow['exercises'] * ADA_MAX_SCORE;
                $totalTest            += $currentReportRow['score_test'];
                $totalTestMax         += $currentReportRow['exercises_test'];
                $totalSurvey          += $currentReportRow['score_survey'];
                $totalSurveyMax       += $currentReportRow['exercises_survey'];
                $totalAddedNodes      += $currentReportRow['notes_out'];
                $totalReadNotes       += $currentReportRow['notes_in'];
                $totalMessageCountIn  += $currentReportRow['msg_in'];
                $totalMessageCountOut += $currentReportRow['msg_out'];
                $totalChat            += $currentReportRow['chat'];
                $totalBookmarks       += $currentReportRow['bookmarks'];
                $totalIndex           += $currentReportRow['indice_att'];
                $totalLevel           += $currentReportRow['level'];
            }

            // generate and add footer (average) row
            $total = ++$row;
            $returnArray[++$row] = [
                'id' => '-',
                'student' => translateFN("Media"),
                'history' => round($totalHistory / $total, 2),
                'last_access' => '-',
                'exercises' => round($totalExercies / $total, 2) . ' ' . translateFN('su') . ' ' . floor($totalExerciesMax / $total),
            ];

            if (MODULES_TEST) {
                $returnArray[$row]['exercises_test'] = round($totalTest / $total, 2) . ' ' . translateFN('su') . ' ' .
                    floor($totalTestMax / $total);
                $returnArray[$row]['exercises_survey'] = round($totalSurvey / $total, 2) . ' ' . translateFN('su') . ' ' .
                    floor($totalSurveyMax / $total);
            }

            $returnArray[$row]['added_notes'] = round($totalAddedNodes / $total, 2);
            $returnArray[$row]['read_notes'] = round($totalReadNotes / $total, 2);
            $returnArray[$row]['message_count_in'] = round($totalMessageCountIn / $total, 2);
            $returnArray[$row]['message_count_out'] = round($totalMessageCountOut / $total, 2);
            $returnArray[$row]['chat'] = round($totalChat / $total, 2);
            $returnArray[$row]['bookmarks'] = round($totalBookmarks / $total, 2);
            $returnArray[$row]['index'] = round($totalIndex / $total, 2);
            $returnArray[$row]['status'] = '-';
            if (MODULES_BADGES) {
                $rew = RewardedBadge::getInstanceRewards();
                $returnArray[$row]['badges'] = round(array_sum($rew['studentsRewards']) / $total, 2) . ' ' . translateFN('su') . ' ' . $rew['total'];
            }
            $returnArray[$row]['level'] = '<span id="averageLevel">' . round($totalLevel / $total, 2) . '</span>';
            $returnArray[$row]['level_plus'] = '-';

            // TABLE LABELS

            $table_labels[0] = $this->generate_class_report_header();

            /**
             * @author giorgio 27/ott/2014
             *
             * unset the unwanted columns data and labels. unwanted cols are defined in config/config_class_report.inc.php
             */

            $arrayToUse = 'reportHTMLColArray';
            $this->clean_class_reportFN($arrayToUse, $table_labels, $returnArray);

            return ['report_generation_date' => $report_generation_TS] + array_merge($table_labels, $returnArray);
        }
        return null;
    }

    // @author giorgio 14/mag/2013
    // added type parameter that defaults to 'xls'
    public function get_class_reportFN($id_course, $order = "", $index_att = "", $type = 'HTML', $speed_mode = true)
    {
        $dh = $GLOBALS['dh'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $debug  = $GLOBALS['debug'] ?? null;
        $npar = $GLOBALS['npar'] ?? null;
        $hpar = $GLOBALS['hpar'] ?? null;
        $mpar = $GLOBALS['mpar'] ?? null;
        $epar = $GLOBALS['epar'] ?? null;
        $bpar = $GLOBALS['bpar'] ?? null;
        $cpar = $GLOBALS['cpar'] ?? null;
        $spar = $GLOBALS['spar'] ?? null;

        // default parameters for activity index are in configuration file
        if (empty($npar)) {
            $npar = NOTE_PAR; // notes
        }
        if (!isset($hpar)) {
            $hpar = HIST_PAR; // history
        }
        if (!isset($mpar)) {
            $mpar = MSG_PAR; //messages
        }
        if (!isset($epar)) {
            $epar = EXE_PAR; // exercises
        }
        if (!isset($bpar)) {
            $bpar = (defined('BKM_PAR')) ? BKM_PAR : null; //bookmarks
        }
        if (!isset($cpar)) {
            $cpar = (defined('CHA_PAR')) ? CHA_PAR : null; // chat
        }
        if (!isset($spar)) {
            $spar = (defined('SURV_PAR')) ? SURV_PAR : null; // surveys
        }

        $student_list_ar = $this->student_list;
        $id_instance = $this->id;
        if ($student_list_ar != 0) {
            $info_course = $dh->get_course($id_course); // Get title course
            if (AMA_DataHandler::isError($info_course)) {
                $msg = $info_course->getMessage();
                return $msg;
            }
            $course_title = $info_course['titolo'];

            $instance_course_ha = $dh->course_instance_get($id_instance); // Get the instance courses data
            if (AMA_DataHandler::isError($instance_course_ha)) {
                $msg = $instance_course_ha->getMessage();
                return $msg;
            }

            $start_date =  AMA_DataHandler::ts_to_date($instance_course_ha['data_inizio'], ADA_DATE_FORMAT);
            $tot_history_count = 0;
            $tot_time_in_course = 0;
            $tot_exercises_score = 0;
            $tot_exercises_number = 0;
            $tot_added_notes = 0;
            $tot_read_notes  = 0;
            $tot_message_count = 0;
            $tot_message_count_in = 0;
            $tot_message_count_out = 0;
            $tot_bookmarks_count = 0;
            $tot_chatlines_count_out = 0;
            $tot_index = 0;
            $tot_level = 0;
            $tot_exercises_score_test = 0;
            $tot_exercises_number_test = 0;
            $tot_exercises_score_survey = 0;
            $tot_exercises_number_survey = 0;
            /**
             * @author giorgio 27/ott/2014
             *
             * change to:
             * $report_generation_TS = time();
             *
             * to have full date & time generation of report
             * but be warned that table log_classi may grow A LOT!
             */
            $report_generation_TS = dt2tsFN(today_dateFN());
            if ($speed_mode === true) {
                // in  $data_to_get we choose what fields to get back and the order of fields
                $columns = [
                    REPORT_COLUMN_HISTORY => 'history',
                    REPORT_COLUMN_TIME_IN_COURSE => 'time_in_course',
                    REPORT_COLUMN_LAST_ACCESS => 'last_access',
                    REPORT_COLUMN_EXERCISES_TEST => 'exercises_test',
                    REPORT_COLUMN_EXERCISES_SURVEY => 'exercises_survey',
                    REPORT_COLUMN_ADDED_NOTES => 'added_notes',
                    REPORT_COLUMN_READ_NOTES   => 'read_notes',
                    REPORT_COLUMN_MESSAGE_COUNT_IN  => 'message_count_in',
                    REPORT_COLUMN_MESSAGE_COUNT_OUT  => 'message_count_out',
                    REPORT_COLUMN_CHAT  => 'chat',
                    REPORT_COLUMN_BOOKMARKS  => 'bookmarks',
                    REPORT_COLUMN_INDEX  => 'index',
                    REPORT_COLUMN_STATUS => 'status',
                    REPORT_COLUMN_BADGES => 'badges',
                    REPORT_COLUMN_LEVEL  => 'level',
                    REPORT_COLUMN_LEVEL_PLUS  => 'level_plus',
                    REPORT_COLUMN_LEVEL_LESS  => 'level_less',
                ];
                $weights = [
                    REPORT_COLUMN_HISTORY => $hpar,
                    REPORT_COLUMN_EXERCISES_TEST => $epar,
                    REPORT_COLUMN_EXERCISES_SURVEY => $spar,
                    REPORT_COLUMN_ADDED_NOTES => $npar,
                    REPORT_COLUMN_READ_NOTES => $npar,
                    REPORT_COLUMN_MESSAGE_COUNT_IN => $mpar,
                    REPORT_COLUMN_MESSAGE_COUNT_OUT => $mpar,
                    REPORT_COLUMN_CHAT => $cpar,
                    REPORT_COLUMN_BOOKMARKS => $bpar,
                ];
                $to_not_get = $GLOBALS['report' . $type . 'ColArray'];

                foreach ($to_not_get as $to_delete) {
                    if (isset($columns[constant($to_delete)])) {
                        unset($columns[constant($to_delete)]);
                    }
                    if (isset($weights[constant($to_delete)])) {
                        unset($weights[constant($to_delete)]);
                    }
                }

                $ticIndex = null;
                if (array_key_exists(REPORT_COLUMN_TIME_IN_COURSE, $columns)) {
                    $tmp = array_search(REPORT_COLUMN_TIME_IN_COURSE, array_keys($columns), true);
                    if ($tmp !== false) {
                        // add two to take into account id ands tudent name columns
                        $ticIndex = $tmp + 2;
                    }
                }

                if (array_key_exists(REPORT_COLUMN_BADGES, $columns)) {
                    if (MODULES_BADGES) {
                        RewardedBadge::loadInstanceRewards($id_course, $id_instance);
                    } else {
                        unset($columns[REPORT_COLUMN_BADGES]);
                    }
                }

                if (!MODULES_TEST) {
                    if (isset($columns[REPORT_COLUMN_EXERCISES_TEST])) {
                        unset($columns[REPORT_COLUMN_EXERCISES_TEST]);
                    }
                    if (isset($columns[REPORT_COLUMN_EXERCISES_SURVEY])) {
                        unset($columns[REPORT_COLUMN_EXERCISES_SURVEY]);
                    }
                }

                $stausIsButton = false;
                if (defined('MODULES_SERVICECOMPLETE') && MODULES_SERVICECOMPLETE) {
                    // need the service-complete module data handler
                    $mydh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                    // load the conditionset for this course
                    $conditionSet = $mydh->get_linked_conditionset_for_course($id_course);
                    $stausIsButton = $conditionSet instanceof CompleteConditionSet;
                    $mydh->disconnect();
                }


                $dati_stude = $dh->get_students_report($id_instance, $id_course, $columns, $weights);

                foreach ($dati_stude as $key => $user) {
                    // counters for statistics
                    $id_student = $dati_stude[$key]["id"];
                    $tot_history_count += $dati_stude[$key]["history"];
                    $tot_added_notes += $dati_stude[$key]["added_notes"];
                    $tot_read_notes += $dati_stude[$key]["read_notes"];
                    $tot_message_count_in += $dati_stude[$key]['message_count_in'];
                    $tot_message_count_out += $dati_stude[$key]['message_count_out'];
                    $tot_chatlines_count_out += $dati_stude[$key]['chat'];
                    $tot_bookmarks_count += $dati_stude[$key]['bookmarks'] ?? 0;
                    $tot_index += $dati_stude[$key]['index'] ?? 0;
                    $tot_level += $dati_stude[$key]['level'];

                    if (array_key_exists(REPORT_COLUMN_TIME_IN_COURSE, $columns) && !empty($ticIndex)) {
                        $history = new History($id_instance, $id_student);
                        if (is_numeric($id_course)) {
                            $history->setCourse($id_course);
                        }
                        $history->get_visit_time();
                        $tic = ($history->total_time > 0) ? $history->total_time : 0;
                        $dati_stude[$key] = array_merge(
                            array_slice($dati_stude[$key], 0, $ticIndex),
                            [$columns[REPORT_COLUMN_TIME_IN_COURSE] => sprintf("%02d:%02d", floor($tic / 3600), floor(($tic / 60) % 60))],
                            array_slice($dati_stude[$key], $ticIndex)
                        );
                        $tot_time_in_course += $tic;
                    }

                    //if in this installation module test is active
                    if (MODULES_TEST) {
                        if (array_key_exists(REPORT_COLUMN_EXERCISES_TEST, $columns)) {
                            $st_score_test = explode(" " . translateFN("su") . " ", $dati_stude[$key]["exercises_test"])[0];
                            $st_exer_number_test = explode(" " . translateFN("su") . " ", $dati_stude[$key]["exercises_test"])[1];
                            $dati_stude[$key]["exercises_test"] = '<a href="' . MODULES_TEST_HTTP . '/tutor.php?op=test&id_course_instance=' . $id_instance . '&id_course=' . $id_course . '&id_student=' . $id_student . '" class="dontwrap">' . $st_score_test . ' ' . translateFN('su') . ' ' . $st_exer_number_test . '</a>';
                            $tot_exercises_score_test += $st_score_test;
                            $tot_exercises_number_test += $st_exer_number_test;
                        }

                        if (array_key_exists(REPORT_COLUMN_EXERCISES_SURVEY, $columns)) {
                            $st_score_survey = explode(" " . translateFN("su") . " ", $dati_stude[$key]["exercises_survey"])[0];
                            $st_exer_number_survey = explode(" " . translateFN("su") . " ", $dati_stude[$key]["exercises_survey"])[1];
                            $dati_stude[$key]["exercises_survey"] = '<a href="' . MODULES_TEST_HTTP . '/tutor.php?op=survey&id_course_instance=' . $id_instance . '&id_course=' . $id_course . '&id_student=' . $id_student . '" class="dontwrap">' . $st_score_survey . ' ' . translateFN('su') . ' ' . $st_exer_number_survey . '</a>';
                            $tot_exercises_score_survey += $st_score_survey;
                            $tot_exercises_number_survey += $st_exer_number_survey;
                        }
                        //others counter for statistics
                    }

                    if (array_key_exists(REPORT_COLUMN_BADGES, $columns) && MODULES_BADGES) {
                        $dati_stude[$key]['badges'] = RewardedBadge::buildStudentRewardHTML($id_course, $id_instance, $id_student)->getHtml();
                    }

                    [$firstanme, $lastname] = explode('::', $dati_stude[$key]["student"]);
                    // build HTML for name and surname
                    $st_name = "<a href=" .  $http_root_dir . "/tutor/tutor.php?op=zoom_student&id_student=" . $id_student;
                    $st_name .= "&id_course=" . $id_course . "&id_instance=" . $id_instance . ">";
                    $st_name .= $firstanme . "</a>";
                    $dati_stude[$key]["student"] = $st_name;

                    $st_lastname = "<a href=" .  $http_root_dir . "/tutor/tutor.php?op=zoom_student&id_student=" . $id_student;
                    $st_lastname .= "&id_course=" . $id_course . "&id_instance=" . $id_instance . ">";
                    $st_lastname .= $lastname . "</a>";

                    // insert lastname after student (aka firstname)
                    $nameIndex = 1 + array_search("student", array_keys($dati_stude[$key]));
                    $dati_stude[$key] = array_slice($dati_stude[$key], 0, $nameIndex, true) +
                        ["lastname" => $st_lastname] +
                        array_slice($dati_stude[$key], $nameIndex, count($dati_stude[$key]) - $nameIndex, true);

                    if (array_key_exists(REPORT_COLUMN_ADDED_NOTES, $columns)) {
                        // build HTML for added_notes
                        $dati_stude[$key]["added_notes"] = "<a href=$http_root_dir/tutor/tutor.php?op=student_notes&id_instance=$id_instance&id_student=$id_student>" . $dati_stude[$key]["added_notes"] . "</a>";
                    }

                    if (array_key_exists(REPORT_COLUMN_HISTORY, $columns)) {
                        // build HTML for history
                        $st_history = "<a href=" .  $http_root_dir . "/tutor/tutor_history.php?id_student=" . $id_student;
                        $st_history .= "&id_course=" . $id_course . "&id_course_instance=" . $id_instance . ">" . $dati_stude[$key]["history"] . "</a>";
                        $dati_stude[$key]["history"] = $st_history;
                    }

                    if (array_key_exists(REPORT_COLUMN_LAST_ACCESS, $columns)) {
                        // if has at least 1 access then build last_access HTML
                        if ($dati_stude[$key]["last_access"] != "-") {
                            $dati_stude[$key]["last_access"] = "<a href=\"$http_root_dir/tutor/tutor_history_details.php?period=1&id_student=$id_student&id_course_instance=$id_instance&id_course=$id_course\">" . $dati_stude[$key]["last_access"] . "</a>";
                        }
                    }

                    if (array_key_exists(REPORT_COLUMN_LEVEL, $columns)) {
                        //build level HTML
                        $dati_stude[$key]['level'] = '<span id="studentLevel_' . $id_student . '">' . $dati_stude[$key]['level'] . '</span>';
                    }

                    if (array_key_exists(REPORT_COLUMN_STATUS, $columns)) {
                        //build level HTML
                        if (defined('MODULES_SERVICECOMPLETE') && MODULES_SERVICECOMPLETE && $stausIsButton) {
                            $stBtn = CDOMElement::create('button', 'class:ui tiny button servicecomplete-summary-modal');
                            $stBtn->setAttribute('data-student-id', $id_student);
                            $stBtn->setAttribute('data-instance-id', $id_instance);
                            $stBtn->setAttribute('data-course-id', $id_course);
                            $stBtn->addChild(new CText(Subscription::subscriptionStatusArray()[$dati_stude[$key]['status']]));
                            $dati_stude[$key]['status'] = $stBtn->getHtml();
                        } else {
                            $dati_stude[$key]['status'] = Subscription::subscriptionStatusArray()[$dati_stude[$key]['status']];
                        }
                    }

                    if (array_key_exists(REPORT_COLUMN_LEVEL_PLUS, $columns) or in_array(REPORT_COLUMN_LEVEL_LESS, $columns)) {
                        //build level's buttons HTML
                        $forceUpdate = false;
                        $linksHtml = $this->generateLevelButtons($id_student, $forceUpdate);
                        $dati_stude[$key]['level_plus'] = (!is_null($linksHtml)) ? $linksHtml : '-';
                    }
                }

                $tot_students = count($dati_stude);
                // prevent division by zero
                $tot_students = $tot_students == 0 ? 1 : $tot_students;
                // set av_student to the last dati_stude array key plus 1
                $av_student = 1 + intval(key(array_slice($dati_stude, -1, 1, true)));
                $dati_stude[$av_student]['id'] = "-";
                $dati_stude[$av_student]["student"] = translateFN("Media");
                $dati_stude[$av_student]['lastname'] = "&nbsp;";

                $tableHeader['id'] = translateFN("Id");
                $tableHeader["student"] = translateFN("Nome");
                $tableHeader["lastname"] = translateFN("Cognome");

                if (array_key_exists(REPORT_COLUMN_HISTORY, $columns)) {
                    $tableHeader['history'] = translateFN("Visite");
                    $av_history = ($tot_history_count / $tot_students);
                    $dati_stude[$av_student]['history'] = round($av_history, 2);
                }

                if (array_key_exists(REPORT_COLUMN_TIME_IN_COURSE, $columns)) {
                    $tableHeader[$columns[REPORT_COLUMN_TIME_IN_COURSE]] = translateFN("Tempo");
                    $av_time_in_course = floor($tot_time_in_course / $tot_students);
                    $dati_stude[$av_student][$columns[REPORT_COLUMN_TIME_IN_COURSE]] = sprintf("%02d:%02d", floor($av_time_in_course / 3600), floor(($av_time_in_course / 60) % 60));
                }

                if (array_key_exists(REPORT_COLUMN_LAST_ACCESS, $columns)) {
                    $tableHeader['last_access'] = translateFN("Recente");
                    $dati_stude[$av_student]['last_access'] = "-";
                }

                if (MODULES_TEST) {
                    if (array_key_exists(REPORT_COLUMN_EXERCISES_TEST, $columns)) {
                        $tableHeader['exercises_test'] = translateFN("Punti Test");
                        $av_exercises_test = round($tot_exercises_score_test / $tot_students, 2); // . ' ' . translateFN('su') . ' ' . floor($tot_exercises_number_test / $tot_students);
                        $dati_stude[$av_student]['exercises_test'] = '<span class="dontwrap">' . $av_exercises_test . '</span>';
                    }
                    if (array_key_exists(REPORT_COLUMN_EXERCISES_SURVEY, $columns)) {
                        $tableHeader['exercises_survey'] = translateFN("Punti Sondaggio");
                        $av_exercises_survey = round($tot_exercises_score_survey / $tot_students, 2); // . ' ' . translateFN('su') . ' ' . floor($tot_exercises_number_survey / $tot_students);
                        $dati_stude[$av_student]['exercises_survey'] = '<span class="dontwrap">' . $av_exercises_survey . '</span>';
                    }
                }

                if (array_key_exists(REPORT_COLUMN_ADDED_NOTES, $columns)) {
                    $tableHeader['added_notes'] = translateFN("Note Scri");
                    $av_added_notes = ($tot_added_notes / $tot_students);
                    $dati_stude[$av_student]['added_notes'] = round($av_added_notes, 2);
                }
                if (array_key_exists(REPORT_COLUMN_READ_NOTES, $columns)) {
                    $tableHeader['read_notes'] = translateFN("Note Let");
                    $av_read_notes = ($tot_read_notes / $tot_students);
                    $dati_stude[$av_student]['read_notes'] = round($av_read_notes, 2);
                }
                if (array_key_exists(REPORT_COLUMN_MESSAGE_COUNT_IN, $columns)) {
                    $tableHeader['message_count_in'] = translateFN("Msg Ric");
                    $av_message_count_in = ($tot_message_count_in / $tot_students);
                    $dati_stude[$av_student]['message_count_in'] = round($av_message_count_in, 2);
                }
                if (array_key_exists(REPORT_COLUMN_MESSAGE_COUNT_OUT, $columns)) {
                    $tableHeader['message_count_out'] = translateFN("Msg Inv");
                    $av_message_count_out = ($tot_message_count_out / $tot_students);
                    $dati_stude[$av_student]['message_count_out'] = round($av_message_count_out, 2);
                }
                if (array_key_exists(REPORT_COLUMN_CHAT, $columns)) {
                    $tableHeader['chat'] = translateFN("Chat ");
                    $av_chat_count_out = ($tot_chatlines_count_out / $tot_students);
                    $dati_stude[$av_student]['chat'] = round($av_chat_count_out, 2);
                }
                if (array_key_exists(REPORT_COLUMN_BOOKMARKS, $columns)) {
                    $tableHeader['bookmarks'] = translateFN("Bkms ");
                    $av_bookmarks_count = ($tot_bookmarks_count / $tot_students);
                    $dati_stude[$av_student]['bookmarks'] = round($av_bookmarks_count, 2);
                }
                if (array_key_exists(REPORT_COLUMN_INDEX, $columns)) {
                    $tableHeader['index'] = translateFN("Attivita'");
                    $av_index = ($tot_index / $tot_students);
                    $dati_stude[$av_student]['index'] = round($av_index, 2);
                }
                if (array_key_exists(REPORT_COLUMN_STATUS, $columns)) {
                    $tableHeader['status'] = translateFN("Stato");
                    $dati_stude[$av_student]['status'] = '-';
                }
                if (array_key_exists(REPORT_COLUMN_LEVEL, $columns)) {
                    $tableHeader['level'] = translateFN("Livello");
                    $av_level = ($tot_level / $tot_students);
                    $dati_stude[$av_student]['level'] = '<span id="averageLevel">' . round($av_level, 2) . '</span>';
                }
                if (array_key_exists(REPORT_COLUMN_BADGES, $columns) && MODULES_BADGES) {
                    $tableHeader['badges'] = translateFN("Badges");
                    $rew = RewardedBadge::getInstanceRewards();
                    $dati_stude[$av_student]['badges'] = round(array_sum($rew['studentsRewards']) / $tot_students, 2) . ' ' . translateFN('su') . ' ' . $rew['total'];
                }
                if (array_key_exists(REPORT_COLUMN_LEVEL_PLUS, $columns)) {
                    $tableHeader['level_plus'] = translateFN("Modifica livello");
                    $dati_stude[$av_student]['level_plus'] = "-";
                }

                if (!empty($order)) {
                    $dati_stude = masort($dati_stude, $order, 1, SORT_NUMERIC);
                }
                // TABLE LABELS
                $table_labels[0] = $tableHeader;
            } else {
                $num_student = -1;
                if (MODULES_TEST) {
                    $test_db = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                    $test_score = $test_db->getStudentsScores($id_course, $id_instance);
                }
                if (MODULES_BADGES) {
                    RewardedBadge::loadInstanceRewards($id_course, $id_instance);
                }
                foreach ($student_list_ar as $one_student) {
                    $id_student = $one_student['id_utente_studente'];
                    $student_level = $one_student['livello'];
                    $status_student = $one_student['status'];
                    $dati['id'] = $id_student;
                    $dati['level'] = $student_level;
                    $ymdhms = today_dateFN();
                    $utime = dt2tsFN($ymdhms);
                    $dati['date'] = $report_generation_TS;

                    $goodStatuses = [ADA_STATUS_SUBSCRIBED, ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED, ADA_STATUS_TERMINATED];
                    if (!empty($id_student) && in_array($status_student, $goodStatuses)) {
                        $studentObj = MultiPort::findUser($id_student); //new Student($id_student,$id_instance);

                        if ($studentObj->full != 0) { //==0) {
                            $err_msg = $studentObj->error_msg;
                        } else {
                            $num_student++; //starts with 0

                            if ($studentObj instanceof ADAPractitioner) {
                                /**
                                 * @author giorgio 14/apr/2015
                                 *
                                 * If student is actually a tutor, build a new student
                                 * object for history and evaluation purposes
                                 */
                                $studentObj = $studentObj->toStudent();
                            }
                            $student_name = $studentObj->getFirstName(); //$studentObj->nome." ".$studentObj->cognome;
                            $student_lastname = $studentObj->getLastName();

                            // vito
                            $studentObj->set_course_instance_for_history($id_instance);
                            //$studentObj->history->setCourseInstance($id_instance);
                            $studentObj->history->setCourse($id_course);

                            $studentObj->get_exercise_dataFN($id_instance, $id_student);
                            $st_exercise_dataAr = $studentObj->user_ex_historyAr;
                            $st_score = 0;
                            $st_exer_number = 0;
                            if (is_array($st_exercise_dataAr)) {
                                foreach ($st_exercise_dataAr as $exercise) {
                                    $st_score += $exercise[7];
                                    $st_exer_number++;
                                }
                            }
                            $dati['exercises'] = $st_exer_number;
                            $dati['score'] = $st_score;

                            if (MODULES_TEST) {
                                $st_score_test = $test_score[$id_student]['score_test'] ?? 0;
                                $st_exer_number_test = $test_score[$id_student]['max_score_test'] ?? 0;
                                $dati['exercises_test'] = $st_exer_number_test;
                                $dati['score_test'] = $st_score_test;

                                $st_score_survey = $test_score[$id_student]['score_survey'] ?? 0;
                                $st_exer_number_survey = $test_score[$id_student]['max_score_survey'] ?? 0;
                                $dati['exercises_survey'] = $st_exer_number_survey;
                                $dati['score_survey'] = $st_score_survey;
                            }

                            $sub_courses = $dh->get_subscription($id_student, $id_instance);

                            if ($sub_courses['tipo'] == ADA_STATUS_SUBSCRIBED) {
                                $out_fields_ar = ['nome', 'titolo', 'id_istanza', 'data_creazione'];
                                $clause = "tipo = '" . ADA_NOTE_TYPE . "' AND id_utente = '$id_student'";
                                $nodes = $dh->find_course_nodes_list($out_fields_ar, $clause, $id_course);
                                $added_nodes_count = count($nodes);
                                $added_nodes_count_norm = str_pad($added_nodes_count, 5, "0", STR_PAD_LEFT);

                                $added_notes = "<!-- $added_nodes_count_norm --><a href=$http_root_dir/tutor/tutor.php?op=student_notes&id_instance=$id_instance&id_student=$id_student>" . $added_nodes_count . "</a>";
                                //$added_notes = $added_nodes_count;
                            } else {
                                $added_notes = "<!-- 0 -->-";
                            }
                            $read_notes_count = $studentObj->total_visited_notesFN($id_student, $id_course);
                            if ($read_notes_count > 0) {
                                $read_nodes_count_norm = str_pad($read_notes_count, 5, "0", STR_PAD_LEFT);
                                $read_notes = "<!-- $read_nodes_count_norm -->$read_notes_count";
                            } else {
                                $read_notes = "<!-- 0 -->-";
                            }

                            $st_history_count = "0";
                            $debug = 0;
                            $st_history_count = $studentObj->total_visited_nodesFN($id_student, ADA_LEAF_TYPE);
                            // vito, 11 mar 2009. Ottiene solo il numero di visite a nodi di tipo foglia.
                            // vogliamo anche il numero di visite a nodi di tipo gruppo.
                            $st_history_count += $studentObj->total_visited_nodesFN($id_student, ADA_GROUP_TYPE);

                            $dati['visits'] = $st_history_count;

                            $st_name = "<!-- $student_name --><a href=" .  $http_root_dir . "/tutor/tutor.php?op=zoom_student&id_student=" . $id_student;

                            $st_name .= "&id_course=" . $id_course . "&id_instance=" . $id_instance . ">";
                            $st_name .= $student_name . "</a>";

                            $st_lastname = "<!-- $student_lastname --><a href=" .  $http_root_dir . "/tutor/tutor.php?op=zoom_student&id_student=" . $id_student;

                            $st_lastname .= "&id_course=" . $id_course . "&id_instance=" . $id_instance . ">";
                            $st_lastname .= $student_lastname . "</a>";

                            $st_history_count_norm = str_pad($st_history_count, 5, "0", STR_PAD_LEFT);
                            $st_history = "<!-- $st_history_count_norm --><a href=" .  $http_root_dir . "/tutor/tutor_history.php?id_student=" . $id_student;
                            $st_history .= "&id_course=" . $id_course . "&id_course_instance=" . $id_instance . ">";
                            $st_history .=  $st_history_count . "</a>";

                            $st_history_last_access = $studentObj->get_last_accessFN($id_instance, "T");
                            //$dati['date'] = $st_history_last_access;

                            $st_score_norm = str_pad($st_score, 5, "0", STR_PAD_LEFT);
                            $st_exercises = "<!-- $st_score_norm --><a href=" .  $http_root_dir . "/tutor/tutor_exercise.php?id_student=" . $id_student;
                            $st_exercises .= "&id_course_instance=" . $id_instance . " class='dontwrap'>";
                            $st_exercises .=  $st_score . " " . translateFN("su") . " " . ($st_exer_number * ADA_MAX_SCORE) . "</a>";

                            if (MODULES_TEST) {
                                $st_score_norm_test = str_pad($st_score_test, 5, "0", STR_PAD_LEFT);
                                $st_exercises_test = '<!-- ' . $st_score_norm_test . ' --><a href="' . MODULES_TEST_HTTP . '/tutor.php?op=test&id_course_instance=' . $id_instance . '&id_course=' . $id_course . '&id_student=' . $id_student . '" class="dontwrap">' . $st_score_test . ' ' . translateFN('su') . ' ' . $st_exer_number_test . '</a>';

                                $st_score_norm_survey = str_pad($st_score_survey, 5, "0", STR_PAD_LEFT);
                                $st_exercises_survey = '<!-- ' . $st_score_norm_survey . ' --><a href="' . MODULES_TEST_HTTP . '/tutor.php?op=survey&id_course_instance=' . $id_instance . '&id_course=' . $id_course . '&id_student=' . $id_student . '" class="dontwrap">' . $st_score_survey . ' ' . translateFN('su') . ' ' . $st_exer_number_survey . '</a>';
                            }

                            // user data
                            $dati_stude[$num_student]['id'] = $id_student;
                            $dati_stude[$num_student]['student'] = $st_name;
                            $dati_stude[$num_student]['lastname'] = $st_lastname;

                            // history
                            $dati_stude[$num_student]['history'] = $st_history;
                            $tot_history_count += $st_history_count;

                            // time in course
                            $studentObj->history->get_visit_time();
                            $tic = ($studentObj->history->total_time > 0) ? $studentObj->history->total_time : 0;
                            $tot_time_in_course += $tic;
                            $dati_stude[$num_student]['time_in_course'] = sprintf("%02d:%02d", floor($tic / 3600), floor(($tic / 60) % 60));

                            if ($st_history_last_access != "-") {
                                $dati_stude[$num_student]['last_access'] = "<a href=\"$http_root_dir/tutor/tutor_history_details.php?period=1&id_student=$id_student&id_course_instance=$id_instance&id_course=$id_course\">" . $st_history_last_access . "</a>";
                                $dati['last_access'] = $studentObj->get_last_accessFN($id_instance, 'UT');
                            } else {
                                $dati_stude[$num_student]['last_access'] = $st_history_last_access;
                                $dati['last_access'] = null;
                            }

                            // exercises
                            $tot_exercises_score += $st_score;
                            $tot_exercises_number += $st_exer_number;
                            $dati_stude[$num_student]['exercises'] = $st_exercises;
                            $dati['exercises'] = $st_exer_number;

                            if (MODULES_TEST) {
                                $tot_exercises_score_test += $st_score_test;
                                $tot_exercises_number_test += $st_exer_number_test;
                                $dati_stude[$num_student]['exercises_test'] = $st_exercises_test;
                                $dati['exercises_test'] = $st_exer_number_test;

                                $tot_exercises_score_survey += $st_score_survey;
                                $tot_exercises_number_survey += $st_exer_number_survey;
                                $dati_stude[$num_student]['exercises_survey'] = $st_exercises_survey;
                                $dati['exercises_survey'] = $st_exer_number_survey;
                            }

                            // forum notes written
                            $dati_stude[$num_student]['added_notes'] = $added_notes;
                            $tot_added_notes += $added_nodes_count;
                            $dati['added_notes'] = $added_nodes_count;
                            // forum notes read
                            $dati_stude[$num_student]['read_notes'] = $read_notes;
                            $tot_read_notes += $read_notes_count;
                            $dati['read_notes'] = $read_notes_count;
                            // messages
                            //$mh = new MessageHandler("%d/%m/%Y - %H:%M:%S");

                            $mh = MessageHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                            $sort_field = "data_ora desc";

                            // messages received

                            $msgs_ha = $mh->get_messages(
                                $id_student,
                                ADA_MSG_SIMPLE,
                                ["id_mittente", "data_ora"],
                                $sort_field
                            );
                            if (AMA_DataHandler::isError($msgs_ha)) {
                                $err_code = $msgs_ha->code;
                                $dati_stude[$num_student]['message_count_in'] = "-";
                            } else {
                                $user_message_count =  count($msgs_ha);
                                $dati_stude[$num_student]['message_count_in'] = $user_message_count;

                                $tot_message_count += $user_message_count;
                            }
                            $tot_message_count_in += $user_message_count;
                            $dati['msg_in'] = $user_message_count;



                            // messages sent

                            $msgs_ha = $mh->get_sent_messages(
                                $id_student,
                                ADA_MSG_SIMPLE,
                                ["id_mittente", "data_ora"],
                                $sort_field
                            );
                            if (AMA_DataHandler::isError($msgs_ha)) {
                                $err_code = $msgs_ha->code;
                                $dati_stude[$num_student]['message_count_out'] = "-";
                            } else {
                                $user_message_count =  count($msgs_ha);
                                $dati_stude[$num_student]['message_count_out'] = $user_message_count;
                                $tot_message_count += $user_message_count;
                            }
                            $tot_message_count_out += $user_message_count;
                            $dati['msg_out'] = $user_message_count;

                            //chat..
                            $msgs_ha = $mh->get_sent_messages(
                                $id_student,
                                ADA_MSG_CHAT,
                                ["id_mittente", "data_ora"],
                                $sort_field
                            );
                            if (AMA_DataHandler::isError($msgs_ha)) {
                                $err_code = $msgs_ha->code;
                                $dati_stude[$num_student]['chat'] = "-";
                            } else {
                                $chatlines_count_out =  count($msgs_ha);
                                $dati_stude[$num_student]['chat'] = $chatlines_count_out;
                                $tot_chatlines_count_out += $chatlines_count_out;
                            }
                            $tot_chatlines_count_out += $chatlines_count_out;
                            $dati['chat'] = $chatlines_count_out;

                            //bookmarks..
                            $bookmarks_count = count(Bookmark::get_bookmarks($id_student));
                            $dati_stude[$num_student]['bookmarks'] = $bookmarks_count;
                            $tot_bookmarks_count += $bookmarks_count;
                            $dati['bookmarks']  = $bookmarks_count;

                            // activity index
                            if (empty($index_att)) { // parametro passato alla funzione
                                if (empty($GLOBALS['index_activity_expression'])) {
                                    //
                                    if (!isset($bcount)) {
                                        $bcount = 1;
                                    }
                                    $index = ($added_nodes_count * $npar) + ($st_history_count * $hpar)  + ($user_message_count * $mpar) + ($st_exer_number * $epar) + ($bookmarks_count * $bcount) + ($chatlines_count_out * $cpar);
                                } else {
                                    $index = eval($GLOBALS['index_activity_expression']);
                                }
                            } else {
                                $index = eval($index_att);
                            }

                            $dati_stude[$num_student]['index'] = $index;
                            //echo $index;
                            $tot_index += $index;
                            $dati['index'] = $index;

                            // status
                            $dati['status'] = $status_student;
                            $dati_stude[$num_student]['status'] = sprintf("<!-- %d -->%s", $status_student, Subscription::subscriptionStatusArray()[$status_student]);

                            if (MODULES_BADGES) {
                                $dati_stude[$num_student]['badges'] = RewardedBadge::buildStudentRewardHTML($id_course, $id_instance, $id_student)->getHtml();
                            }

                            // level
                            $tot_level += $student_level;
                            $dati_stude[$num_student]['level'] = '<span id="studentLevel_' . $id_student . '">' . $student_level . '</span>';
                            $forceUpdate = false;
                            $linksHtml = $this->generateLevelButtons($id_student, $forceUpdate);

                            $dati_stude[$num_student]['level_plus'] = (!is_null($linksHtml)) ? $linksHtml : '-';

                            // inserting a row in table log_classi

                            $this->log_class_data($id_course, $id_instance, $dati);
                        }
                    }
                }

                // average data
                $tot_students = ($num_student == 0) ? 1 : $num_student + 1;
                $av_history = ($tot_history_count / $tot_students);
                $av_time_in_course = floor($tot_time_in_course / $tot_students);
                $av_exercises = ($tot_exercises_score / $tot_students) . " " . translateFN("su") . " " . floor($tot_exercises_number * ADA_MAX_SCORE / $tot_students);

                if (MODULES_TEST) {
                    $av_exercises_test = round($tot_exercises_score_test / $tot_students, 2) . ' ' . translateFN('su') . ' ' . floor($tot_exercises_number_test / $tot_students);
                    $av_exercises_survey = round($tot_exercises_score_survey / $tot_students, 2) . ' ' . translateFN('su') . ' ' . floor($tot_exercises_number_survey / $tot_students);
                }
                $av_added_notes = ($tot_added_notes / $tot_students);
                $av_read_notes = ($tot_read_notes / $tot_students);
                $av_message_count_in = ($tot_message_count_in / $tot_students);
                $av_message_count_out = ($tot_message_count_out / $tot_students);
                $av_chat_count_out = ($tot_chatlines_count_out / $tot_students);
                $av_bookmarks_count = ($tot_bookmarks_count / $tot_students);
                $av_index = ($tot_index / $tot_students);
                $av_level = ($tot_level / $tot_students);

                // set av_student to the last dati_stude array key plus 1
                $av_student = 1 + intval(key(array_slice($dati_stude, -1, 1, true)));
                $dati_stude[$av_student]['id'] = "-";
                $dati_stude[$av_student]['student'] = translateFN("Media");
                $dati_stude[$av_student]['lastname'] = "&nbsp;";
                $dati_stude[$av_student]['history'] = round($av_history, 2);
                $dati_stude[$av_student]['time_in_course'] = sprintf("%02d:%02d", floor($av_time_in_course / 3600), floor(($av_time_in_course / 60) % 60));
                $dati_stude[$av_student]['last_access'] = "-";
                $dati_stude[$av_student]['exercises'] = '<span class="dontwrap">' . $av_exercises . '</span>';

                if (MODULES_TEST) {
                    $dati_stude[$av_student]['exercises_test'] = '<span class="dontwrap">' . $av_exercises_test . '</span>';
                    $dati_stude[$av_student]['exercises_survey'] = '<span class="dontwrap">' . $av_exercises_survey . '</span>';
                }

                $dati_stude[$av_student]['added_notes'] = round($av_added_notes, 2);
                $dati_stude[$av_student]['read_notes'] = round($av_read_notes, 2);
                $dati_stude[$av_student]['message_count_in'] = round($av_message_count_in, 2);
                $dati_stude[$av_student]['message_count_out'] = round($av_message_count_out, 2);
                $dati_stude[$av_student]['chat'] = round($av_chat_count_out, 2);
                $dati_stude[$av_student]['bookmarks'] = round($av_bookmarks_count, 2);

                $dati_stude[$av_student]['index'] = round($av_index, 2);
                $dati_stude[$av_student]['status'] = "-";
                if (MODULES_BADGES) {
                    $rew = RewardedBadge::getInstanceRewards();
                    $dati_stude[$av_student]['badges'] = round(array_sum($rew['studentsRewards']) / $tot_students, 2) . ' ' . translateFN('su') . ' ' . $rew['total'];
                }
                $dati_stude[$av_student]['level'] = '<span id="averageLevel">' . round($av_level, 2) . '</span>';
                $dati_stude[$av_student]['level_plus'] = "-";
                // @author giorgio 16/mag/2013
                // was $dati_stude[$av_student]['level_minus'] = "-";
                // $dati_stude[$av_student]['level_less'] = "-";

                if (!empty($order)) {
                    //var_dump($dati_stude);
                    $dati_stude = masort($dati_stude, $order, 1, SORT_NUMERIC);
                }

                // TABLE LABELS
                $table_labels[0] = $this->generate_class_report_header();

                /**
                 * @author giorgio 16/mag/2013
                 *
                 * unset the unwanted columns data and labels. unwanted cols are defined in config/config_class_report.inc.php
                 */

                $arrayToUse = 'report' . $type . 'ColArray';
                $this->clean_class_reportFN($arrayToUse, $table_labels, $dati_stude);
            }

            return ['report_generation_date' => $report_generation_TS] + array_merge($table_labels, $dati_stude);
        } else {
            return null;
        }
    }

    /**
     * generates buttons for increasing and decreasing user level
     *
     * @param number  $id_student
     * @param boolean $forceUpdate true if the javascript must reload the page in update mode
     *
     * @return string|NULL
     */
    private function generateLevelButtons($id_student, $forceUpdate)
    {
        // convert $forceUpdate to string to be properly passed to the JS
        $forceUpdate = ($forceUpdate) ? 'true' : 'false';

        $ButtonPlus =  CDOMElement::create('button', 'class: button_Increase');
        $ButtonPlus->setAttribute('onclick', 'javascript:updateLevel(' . $id_student . ',1,' . $forceUpdate . ');');

        $ButtonMinus = CDOMElement::create('button', 'class: button_Decrease');
        $ButtonMinus->setAttribute('onclick', 'javascript:updateLevel(' . $id_student . ',-1,' . $forceUpdate . ');');

        $links = [];
        $links[0] = CDOMElement::create('li', 'class:liactions');
        $links[0]->addChild($ButtonPlus);

        $links[1] = CDOMElement::create('li', 'class:liactions');
        $links[1]->addChild($ButtonMinus);

        if (!empty($links)) {
            $linksul = CDOMElement::create('ul', 'class:ulactions');
            foreach ($links as $link) {
                $linksul->addChild($link);
            }
            return $linksul->getHtml();
        }
        return null;
    }

    /**
     * @author giorgio 24/ott/2014
     *
     * generate class report table header
     *
     * @return array the generated table header array
     *
     * @access private
     */
    private function generate_class_report_header()
    {

        $tableHeader['id'] = translateFN("Id");
        $tableHeader['student'] = translateFN("Nome");
        $tableHeader['lastname'] = translateFN("Cognome");
        $tableHeader['history'] = translateFN("Visite");
        $tableHeader['time_in_course'] = translateFN("Tempo");
        $tableHeader['last_access'] = translateFN("Recente");
        $tableHeader['exercises'] = translateFN("Punti A");

        if (MODULES_TEST) {
            $tableHeader['exercises_test'] = translateFN("Punti Test");
            $tableHeader['exercises_survey'] = translateFN("Punti Sondaggio");
        }

        $tableHeader['added_notes'] = translateFN("Note Scri");
        $tableHeader['read_notes'] = translateFN("Note Let");
        $tableHeader['message_count_in'] = translateFN("Msg Ric");
        $tableHeader['message_count_out'] = translateFN("Msg Inv");
        $tableHeader['chat'] = translateFN("Chat ");
        $tableHeader['bookmarks'] = translateFN("Bkms ");

        $tableHeader['index'] = translateFN("Attivita'");
        $tableHeader['status'] = translateFN("Stato");
        if (MODULES_BADGES) {
            $tableHeader['badges'] = translateFN("Badges");
        }
        $tableHeader['level'] = translateFN("Livello");
        $tableHeader['level_plus'] = translateFN("Modifica livello");

        return $tableHeader;
    }

    /**
     * @author giorgio 24/ott/2014
     *
     * remove unwanted columns from the class report
     * unwanted cols are defined in config/config_class_report.inc.php
     *
     * @param array $arrayToUse
     * @param array $table_labels NOTE: passed by ref, this method will modify the array!
     * @param array $dati_stude   NOTE: passed by ref, this method will modify the array!
     *
     * @access private
     */
    private function clean_class_reportFN($arrayToUse, &$table_labels, &$dati_stude)
    {

        if (CONFIG_CLASS_REPORT && is_array($GLOBALS[$arrayToUse]) && count($GLOBALS[$arrayToUse])) {
            foreach ($GLOBALS[$arrayToUse] as $reportCol) {
                if (constant($reportCol) > 0) {
                    preg_match("/^REPORT_COLUMN_([A-Z_]*)$/", $reportCol, $output_array);
                    $arrayKey = strtolower($output_array[1]);
                    unset($table_labels[0][$arrayKey]);

                    foreach ($dati_stude as $key => $oneRow) {
                        unset($dati_stude[$key][$arrayKey]);
                    }
                }
            }
        }
    }

    public function log_class_data($id_course, $id_course_instance, $dati_stude)
    {
        $dh = $GLOBALS['dh'];
        $debug  = $GLOBALS['debug'] ?? null;
        $dataHa = $dh->add_class_report($id_course, $id_course_instance, $dati_stude);
        if (AMA_DataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            // header("Location: $error?err_msg=$msg");
        } else {
            $msg = null;
        }
        return $msg;
    }

    public function read_class_data($id_course, $id_course_instance, $date)
    {
        $dh     = $GLOBALS['dh'];
        $debug  = $GLOBALS['debug'] ?? '';
        $dataHa = $dh->get_class_report($id_course, $id_course_instance, $date);
        // vito, 16 luglio 2008. Lasciamo la gestione dell'errore al chiamante.
        return $dataHa;
    }

    public function read_student_data($id_course, $id_course_instance, $id_student)
    {
        $dh = $GLOBALS['dh'];
        $debug  = $GLOBALS['debug'];
        $dataHa = $dh->get_class_report($id_course, $id_course_instance, $id_student);
        if (AMA_DataHandler::isError($dataHa) || (!is_array($dataHa))) {
            $msg = $dataHa->getMessage();
            // header("Location: $error?err_msg=$msg");
        } else {
            return $dataHa;
        }
    }
    public function find_student_index_att($id_course, $id_course_instance, $id_student)
    {
        // returns an array
        // last element is index
        $dh = $GLOBALS['dh'];
        $debug  = $GLOBALS['debug'];
        $clause = "";
        $out_fields_ar = ['indice_att'];
        $dataHa = $dh->find_student_report($id_student, $id_course_instance, $clause, $out_fields_ar);
        if (AMA_DataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            // header("Location: $error?err_msg=$msg");
        } else {
            $last_index = count($dataHa);
            $student_dataAr = $dataHa[$last_index - 1];
            $student_dataHa['id_log'] = $student_dataAr[0];
            $student_dataHa['id_course_instance'] = $student_dataAr[1];
            $student_dataHa['id_user'] = $student_dataAr[2];
            $student_dataHa['index_att'] = $student_dataAr[3];

            return $student_dataHa;
        }
    }
}

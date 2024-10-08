<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout'],
];

$trackPageToNavigationHistory = false;
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

$retArray = [];

if (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' &&
    isset($_GET['id_tutor']) && intval($_GET['id_tutor']) > 0
) {
    $id_tutor = intval($_GET['id_tutor']);
    $caption = translateFN('Dettagli tutor');
    $thead_data = [
        translateFN('Corso'),
        translateFN('Edizione'),
        translateFN('N° iscritti'),
        translateFN('Autoistruzione'),
        translateFN('Note Scri'),
        translateFN('Note Let'),
        translateFN('File Inviati'),
        translateFN('Chat'),
    ];

    $DetailsAr = $dh->getTutorsAssignedCourseInstance($id_tutor);
    if (!AMADB::isError($DetailsAr) && is_array($DetailsAr) && count($DetailsAr) > 0) {
        $DetailsAr = $DetailsAr[$id_tutor];
    }

    $detailsResults = [];

    if (!AMADB::isError($DetailsAr) && is_array($DetailsAr) && count($DetailsAr) > 0) {
        $totalSubscribedStudents = 0;
        $totalSelfInstrucionCourses = 0;
        $totalAddedNotes = 0;
        $totalReadNotes = 0;
        $totalChatlines = 0;
        $totalUploadedFiles = 0;

        foreach ($DetailsAr as $course) {
            // count number of subscribed users to instance
            $subscribedStudents = 0;
            if (isset($course['id_istanza_corso'])) {
                $studentsAr = $dh->getStudentsForCourseInstance($course['id_istanza_corso']);
                foreach ($studentsAr as $student) {
                    if (
                        (str_starts_with($student['status'], ADA_STATUS_SUBSCRIBED)) ||
                        (str_starts_with($student['status'], ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED))
                    ) {
                        $subscribedStudents++;
                    }
                }
            }
            $totalSubscribedStudents += $subscribedStudents;

            // self instruction
            if (isset($course['self_instruction']) && intval($course['self_instruction']) > 0) {
                $totalSelfInstrucionCourses++;
                $isSelfInstruction = translateFN('Sì');
            } else {
                $isSelfInstruction = translateFN('No');
            }

            $added_nodes_count = 0;
            $read_notes_count = 0;
            $chatlines_count = 0;
            if (isset($course['id_corso']) && isset($course['id_istanza_corso'])) {
                $out_fields_ar = [];
                // count written (aka added) forum notes
                $clause =  "tipo = '" . ADA_NOTE_TYPE . "' AND id_utente = " . $id_tutor .
                           " AND id_nodo LIKE '" . $course['id_corso'] . "\_%'" .
                           " AND id_istanza=" . $course['id_istanza_corso'];
                $nodes = $dh->findCourseNodesList($out_fields_ar, $clause, $course['id_corso']);
                $added_nodes_count = count($nodes);

                /**
                 * get tutor visit for course instance (to count read notes)
                 * the method name refers to student, but works ok for a tutor as well
                 */
                $visits = $GLOBALS['dh']->getStudentVisitsForCourseInstance($id_tutor, $course['id_corso'], $course['id_istanza_corso']);
                if (!AMADB::isError($visits) && is_array($visits) && count($visits) > 0) {
                    foreach ($visits as $visit) {
                        if (
                            $visit['tipo'] == ADA_NOTE_TYPE &&
                            // $visit['id_utente'] != $id_tutor &&
                            intval($visit['numero_visite']) > 0
                        ) {
                            $read_notes_count++;
                        }
                    }
                }

                /**
                 * count class chat messages written by the tutor
                 */
                $class_chatrooms = ChatRoom::getAllClassChatroomsFN($course['id_istanza_corso']);
                if (!AMADB::isError($class_chatrooms) && is_array($class_chatrooms) && count($class_chatrooms) > 0) {
                    foreach ($class_chatrooms as $aChatRoom) {
                        $mh = MessageHandler::instance($_SESSION['sess_selected_tester_dsn']);
                        $chat_data = $mh->findChatMessages($id_tutor, ADA_MSG_CHAT, $aChatRoom, '', 'id_mittente=' . $id_tutor);
                        if (!AMADB::isError($chat_data) && is_array($chat_data) && count($chat_data) > 0) {
                            $chatlines_count = count($chat_data);
                        }
                    }
                }

                /**
                 * count files uploaded, for each course
                 */
                $courseObj = new Course($course['id_corso']);
                $uploadedFiles = 0;
                if ($courseObj->isFull()) {
                    // 01. find the course media path
                    if ($courseObj->media_path != "") {
                        $media_path = $courseObj->media_path;
                    } else {
                        $media_path = MEDIA_PATH_DEFAULT . $courseObj->id_autore;
                    }
                    $download_path = $root_dir . $media_path;
                    $elencofile = Utilities::leggidir($download_path);
                    // 02. loop the $media_path dir looking for files
                    // uploaded by $id_tutor in the current course and course instance
                    if (!is_string($elencofile) && !is_null($elencofile)) {
                        foreach ($elencofile as $singleFile) {
                            $complete_file_name = $singleFile['file'];
                            $filenameAr = explode('_', $complete_file_name);
                            $course_instance = $filenameAr[0] ?? null;
                            $id_sender  = $filenameAr[1] ?? null;
                            $id_course = $filenameAr[2] ?? null;
                            if (
                                $id_course == $course['id_corso'] &&
                                $course_instance == $course['id_istanza_corso'] &&
                                $id_sender == $id_tutor
                            ) {
                                $uploadedFiles++;
                            }
                        }
                    }
                }
            }
            $totalAddedNotes += $added_nodes_count;
            $totalReadNotes += $read_notes_count;
            $totalChatlines += $chatlines_count;
            $totalUploadedFiles += $uploadedFiles;

            $detailsResults[] = [
                $course['titolo'],
                $course['title'],
                $subscribedStudents,
                $isSelfInstruction,
                $added_nodes_count,
                $read_notes_count,
                $uploadedFiles,
                $chatlines_count];
        }

        $tfoot_data =  [
                count($DetailsAr) . ' ' . translateFN('Corsi totali'),
                '&nbsp;',
                $totalSubscribedStudents,
                $totalSelfInstrucionCourses,
                $totalAddedNotes,
                $totalReadNotes,
                $totalUploadedFiles,
                $totalChatlines,
        ];

        $result_table = BaseHtmlLib::tableElement('class:tutor_table', $thead_data, $detailsResults, $tfoot_data, $caption);
        $result = $result_table->getHtml();
        $retArray['columnDefs'][] = [
                'sClass' => 'centerAlign',
                'aTargets' => [2,3,4,5,6,7],
        ];
        $retArray['status'] = 'OK';
        $retArray['html'] = $result;
    } else {
        $span_error = CDOMElement::create('span');
        $span_error->setAttribute('class', 'ErrorSpan');
        $span_error->addChild(new CText(translateFN('Nessun dato trovato')));
        $retArray['status'] = 'ERROR';
        $retArray['html'] = $span_error->getHtml();
    }
    echo json_encode($retArray);
}

<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'course', 'course_instance'],
];
/**
 * Performs basic controls before entering this module
 */
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
 * @var \Lynxlab\ADA\Main\User\ADAUser $userObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

if ($courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
}
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $self = 'defaultSelfInstruction';
} else {
    $self = 'default';
}

if ($userObj instanceof ADALoggableUser) {
    $last_visited_node_id = $userObj->getLastAccessFN($sess_id_course_instance, 'N');
    if (!empty($last_visited_node_id)) {
        $last_node_visited = '<a href="view.php?id_node=' . $last_visited_node_id . '">'
                . translateFN('torna') . '</a>';
    } else {
        $last_node_visited = '';
    }
    /*
     * Retrieve student's data
     */
    $userObj->setCourseInstanceForHistory($sess_id_course_instance);
    $user_historyObj = $userObj->getHistoryInCourseInstance($sess_id_course_instance);
    $visited_nodes_table = $user_historyObj->historyNodesVisitedFN();
    $history = '';
    if (isset($op) && $op == 'list') {
        // Nodi visitati e numero di visite per ciascun nodo
        $history .= '<p>';
        $history .= $visited_nodes_table;
        $history .= '</p>';
    } else {
        // Sommario
        $history .= '<p align="center">';
        $history .= $user_historyObj->historySummaryFN($sess_id_course);
        $history .= '</p>';
        // Percentuale nodi visitati
        $history .= '<p align="center">';
        $history .= translateFN('Percentuale nodi visitati/totale: ');
        $nodes_percent = $user_historyObj->historyNodesVisitedpercentFN() . '%';
        $history .= '<b>' . $nodes_percent . '</b>';
        $history .= '</p>';
        // grafico
        $history .= '<p align="center">';
        $history .= '<img src="include/graph_pies.inc.php?nodes_percent=' . urlencode($nodes_percent) . '" border=0 align="center">';
        $history .= '</p>';
        // Tempo di visita nodi
        $history .= '<p align="center">';
        $history .= translateFN('Tempo totale di visita dei nodi (in ore:minuti): ');
        $history .= '<b>' . $user_historyObj->historyNodesTimeFN() . '</b><br />';
        // Media di visita nodi
        $history .= translateFN('Tempo medio di visita dei nodi (in minuti:secondi): ');
        $history .= '<b>' . $user_historyObj->historyNodesAverageFN() . '</b>';
        $history .= '</p>';
        // Exercises, messages, notes ...
        $npar = 7; // notes
        $hpar = 2; // history
        $mpar = 5; //messages
        $epar = 3; // exercises

        $userObj->getExerciseDataFN($sess_id_course_instance, $sess_id_user);
        $st_exercise_dataAr = $userObj->user_ex_historyAr;
        $st_score = 0;
        $st_exer_number = 0;
        if (is_array($st_exercise_dataAr)) {
            foreach ($st_exercise_dataAr as $exercise) {
                $st_score += $exercise[4];
                $st_exer_number++;
            }
        }

        $sub_courses = $dh->getSubscription($sess_id_user, $sess_id_course_instance);
        if ($sub_courses['tipo'] == ADA_STATUS_SUBSCRIBED) {
            $out_fields_ar = ['nome', 'titolo', 'id_istanza', 'data_creazione'];
            $clause = "TIPO = " . ADA_NOTE_TYPE . " AND ID_UTENTE = $sess_id_user";
            $clause .= " AND ID_ISTANZA = " . $sess_id_course_instance;
            $nodes = $dh->findCourseNodesList($out_fields_ar, $clause, $sess_id_course);
            $added_nodes_count = count($nodes);
            $added_notes = $added_nodes_count;
        } else {
            $added_notes = '-';
        }

        $st_history_count = '0';
        $st_history_count = $userObj->totalVisitedNodesFN($sess_id_user);
        $st_exercises = $st_score . " " . translateFN("su") . " " . ($st_exer_number * 100);
        // summary of activities
        $history .= '<p align="center">';
        $history .= translateFN('Punteggio esercizi:') . '&nbsp;<strong>' . $st_exercises . '</strong>&nbsp;';
        // forum
        $history .= translateFN('Note inviate:') . '&nbsp;<strong>' . $added_notes . '</strong>&nbsp;';
        // messages
        $msgs_ha = MultiPort::getUserSentMessages($userObj);
        if (AMADataHandler::isError($msgs_ha)) {
            $user_message_count = '-';
        } else {
            $user_message_count = count($msgs_ha);
        }
        $history .= translateFN('Messaggi inviati:') . '&nbsp;<strong>' . $user_message_count . '</strong>&nbsp;';
        // activity index
        $index = ($added_notes * $npar) + ($st_history_count * $hpar) + ($user_message_count * $mpar) + ($st_exer_number * $epar);
        $history .= translateFN('Indice attivit&agrave;') . '&nbsp;<strong>' . $index . '</strong>&nbsp;';
        $history .= '<p align="center">';
        // Ultime 10 visite
        $history .= '<p>';
        $history .= $user_historyObj->historyLastNodesFN('10');
        $history = preg_replace('/class="/', 'class="historytable ' . ADA_SEMANTICUI_TABLECLASS . ' ', $history, 1); // replace first occurence of class
        $history .= '</p>';
    }
} else {
    $history = translateFN('Cronologia non disponibile.');
}

/*
 * Last access link
 */

if (isset($_SESSION['sess_id_course_instance'])) {
    $last_access = $userObj->getLastAccessFN(($_SESSION['sess_id_course_instance']), "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
} else {
    $last_access = $userObj->getLastAccessFN(null, "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
}
if ($last_access == '' || is_null($last_access)) {
    $last_access = '-';
}

$content_dataAr = [
    'course_title' => '<a href="main_index.php">' . $course_title . '</a>',
    'user_name' => $user_name,
    'user_type' => $user_type,
    'user_level' => $user_level,
    'last_visit' => $last_access,
    'status' => $status,
    'path' => $node_path,
    'data' => $history,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'chat_users' => $online_users ?? null,
    'edit_profile' => $userObj->getEditProfilePage(),
 ];
$menuOptions['self_instruction'] = $self_instruction;
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions);

<?php

/**
 * History class
 *
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        user_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\History;

use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class History
{
    public $id_course;
    public $id_course_instance;
    public $id_student;

    public $course_data;            // array associativo contenente per ogni nodo del corso:
    // id_nodo, nome, tipo, numero_visite
    public $nodes_count;            // nodi presenti nel corso
    public $visited_nodes_count;    // nodi diversi visitati
    public $node_visits_count;      // array contenente il totale visite per ogni tipo di nodo.
    public $node_visits_ratio;      // visite per nodo
    public $total_time;             // tempo di visita totale

    public function __construct($id_course_instance, $id_student)
    {

        $this->id_course_instance = (int) $id_course_instance;
        $this->id_student         = (int) $id_student;
        $this->nodes_count         = 0;
        $this->node_visits_count   = [ADA_LEAF_TYPE  => 0,
        ADA_GROUP_TYPE => 0,
        ADA_NOTE_TYPE  => 0];
        $this->node_visits_ratio   = 0;
        $this->visited_nodes_count = 0;
    }

    public function setCourseInstance($id_course_instance)
    {
        $this->id_course_instance = $id_course_instance;
    }

    public function setCourse($id_course)
    {
        $this->id_course = $id_course;
    }

    /**
     * get_total_visited_nodes
     * If a node type is specified, returns the number of student visits for $node_type nodes.
     * If a node type is not specified, returns the sum of student visits for all the nodes in this course.
     *
     * @param int $node_type - ADA node type, as defined in ada_config.inc.php
     * @return int - number of visited nodes
     */
    public function getTotalVisitedNodes($node_type = null)
    {
        if (!isset($this->course_data)) {
            $this->getCourseData();
        }

        if (!is_null($node_type) && strlen($node_type) > 0) {
            return $this->node_visits_count[$node_type];
        }

        $total_visited_nodes = 0;
        foreach ($this->node_visits_count as $node_type_visits) {
            $total_visited_nodes += $node_type_visits;
        }
        return $total_visited_nodes;
    }

    /**
     * history_summary_FN
     * Outputs an html string with some statistics about $this->id_student user activity in
     * $this->id_course_instance course instance.
     *
     * @param int $id_course - optional
     * @return string $html_string
     */
    public function historySummaryFN()
    {
        if (!isset($this->course_data)) {
            $this->getCourseData();
        }

        $html_string  = '<p>';
        $html_string .= translateFN('Nodi diversi visitati:') . "<b> $this->visited_nodes_count </b>";
        $html_string .= translateFN('su un totale di:') . "<b> $this->nodes_count </b><br>";
        $html_string .= translateFN('Totale visite:') . "<b>" . $this->getTotalVisitedNodes() . " </b><br>";
        $html_string .= translateFN('Visite per nodo:') . "<b> $this->node_visits_ratio </b><br>";
        return $html_string;
    }

    public function historyNodesVisitedpercentFN($node_types = null)
    {
        return number_format($this->historyNodesVisitedpercentFloatFN($node_types), 0, '.', '');
    }
    /**
     * history_nodes_visitedpercent_float_FN
     *
     * @param int|array $node_types - ADA node typeor array of node types, as defined in ada_config.inc.php
     * @return int - number of visited nodes
     */
    public function historyNodesVisitedpercentFloatFN($node_types = null)
    {
        $nodes_percent = $visited = $total = 0;
        if (!isset($this->course_data)) {
            $this->getCourseData();
        }
        if (!is_null($node_types)) {
            if (!is_array($node_types)) {
                $node_types = [$node_types];
            }
            // filter nodes of type LEAF and GROUP
            $filteredAr = array_filter($this->course_data, fn ($el) => is_array($el) && array_key_exists('tipo', $el) && in_array($el['tipo'], $node_types));
            // each node with a 'numero_visite' greater than zero tells that the node has been visited
            $visited = array_reduce($filteredAr, function ($carry, $el) {
                if (array_key_exists('numero_visite', $el) && intval($el['numero_visite']) > 0) {
                    $carry += 1;
                }
                return $carry;
            }, 0);
            $total = count($filteredAr);
        } else {
            $visited = $this->visited_nodes_count;
            $total = $this->nodes_count;
        }
        if ($total > 0) {
            $nodes_percent = $visited / $total * 100;
        }
        return floatval($nodes_percent);
    }

    public function getLastNodes($num)
    {
        $dh = $GLOBALS['dh'];
        $result = $dh->getLastVisitedNodes($this->id_student, $this->id_course_instance, $num);
        //verificare il controllo degli errori
        if (AMADataHandler::isError($result)) {
            $errObj = new ADAError($result, translateFN('Errore nella lettura dei dati'));
        }
        return $result;
    }

    /**
     * history_last_nodes_FN
     *
     * @param int $nodes_num - the number of last accessed nodes for which display infos.
     * @return string $t->getTable() - an html string
     */

    /**
     * @author giorgio 15/mag/2013
     * added $returnHTML parameter
     */
    public function historyLastNodesFN($nodes_num, $returnHTML = true)
    {
        $result = $this->getLastNodes($nodes_num);
        $formatted_data = $this->formatHistoryDataFN($result, $returnHTML);

        if ($returnHTML) {
            $t = new Table();
            $t->initTable('0', 'center', '1', '1', '90%', '', '', '', '', '0', '1');
            $caption = sprintf(translateFN("Ultime %d visite"), $nodes_num);
            $t->setTable($formatted_data, $caption, $caption);
            return $t->getTable();
        } else {
            return $formatted_data;
        }
    }

    /*
     * controllare bene i metodi time, c'è qualcosa che non quadra nel calcolo del tempo.
     */
    public function historyNodesTimeFN()
    {
        if (!isset($this->total_time)) {
            $this->getVisitTime();
        }

        // conversione del valore da secondi ad ore e formattazione
        $int_hours = floor($this->total_time / 3600);
        $rest_sec = $this->total_time - ($int_hours * 3600);
        $int_mins = floor($rest_sec / 60);
        $int_secs = floor($this->total_time - ($int_hours * 3600) - ($int_mins * 60));

        $res = sprintf("%02d:%02d:%02d", $int_hours, $int_mins, $int_secs);
        return $res;
    }

    public function historyNodesAverageFN()
    {
        if (!isset($this->course_data)) {
            $this->getCourseData();
        }
        if (!isset($this->total_time)) {
            $this->getVisitTime();
        }
        $average = $this->total_time / $this->nodes_count;

        $int_hours = floor($average / 3600);

        $rest_sec = $average - ($int_hours * 3600);

        $int_mins = floor($rest_sec / 60);

        $int_secs = floor($average - ($int_hours * 3600) - ($int_mins * 60));

        $res = sprintf("%02d:%02d:%02d", $int_hours, $int_mins, $int_secs);

        return $res;
    }

    /**
     * history_nodes_visited_FN
     *
     * @return string - an html string for a table
     */

    /**
     * @author giorgio 15/mag/2013
     * added $returnHTML parameter
     */
    public function historyNodesVisitedFN($returnHTML = true)
    {
        $http_root_dir = $GLOBALS['http_root_dir'];

        if (!isset($this->course_data)) {
            $this->getCourseData();
        }

        // visualizzazione
        $data = [];
        foreach ($this->course_data as $visita) {
            if ($visita['numero_visite'] != null) {
                $label1 = translateFN("Nodo:");
                $label2 = translateFN("n visite:");
                $id_node = $visita['id_nodo'];
                $name = $visita['nome'];
                $tot_visit = $visita['numero_visite'];

                $css_classname = $this->getClassNameForNodeType($visita['tipo']);
                if ($returnHTML) {
                    $label1Value = "<span class=\"$css_classname\"><a href=\"$http_root_dir/browsing/view.php?id_node=$id_node\">$name</a></span>";
                } else {
                    $label1Value = $name;
                }
                $histAr = [
                $label1 => $label1Value,
                $label2 => $tot_visit,
                ];
                array_push($data, $histAr);
            }
        }
        if ($returnHTML) {
            $t = new Table();
            $t->initTable('0', 'center', '1', '1', '90%', '', '', '', '', '0', '1');
            $t->setTable($data, translateFN("Nodi ordinati per numero di visite"), translateFN("Nodi ordinati per numero di visite"));
            $res = $t->getTable();
            return $res;
        } else {
            return $data;
        }
    }

    /**
     * history_nodes_list_filtered_FN
     *
     * @param int $period - number of days for which display user activity in $this->id_course_instance.
     * @return string $t->getTable() - an html string
     */
    /**
     * @author giorgio 16/mag/2013
     * added $returnHTML parameter
     */
    public function historyNodesListFilteredFN($period, $returnHTML = true)
    {
        $dh = $GLOBALS['dh'];

        $start = ($period > 0) ? (time() - $period * 86400) : 0;

        $result = $dh->getLastVisitedNodesInPeriod($this->id_student, $this->id_course_instance, $start);
        //verificare il controllo degli errori
        if (AMADataHandler::isError($this->course_data)) {
            $errObj = new ADAError($this->course_data, translateFN("Errore nella lettura dei dati"));
        }

        if ($period != 0) {
            $caption = translateFN("Nodi visitati negli ultimi $period giorni");
        } else {
            $caption = translateFN("Tutti i nodi visitati");
        }

        $formatted_data = $this->formatHistoryDataFN($result);

        if ($returnHTML) {
            $t = new Table();
            $t->initTable('0', 'center', '1', '1', '90%', '', '', '', '', '0', '1');

            $t->setTable($formatted_data, $caption, $caption);
            if (!empty($formatted_data)) {
                return $t->getTable();
            } else {
                return "Nessun nodo trovato";
            }
        } else {
            $formatted_data['caption'] = $caption;
        }
        return  $formatted_data;
    }

    /**
     * get_historyFN
     *
     * Returns an html string containing a table with all of the user activity in $this->id_course_instance.
     * @return string - an html string
     */
    /**
     * @author giorgio 16/mag/2013
     * added $returnHTML parameter
     */
    public function getHistoryFN($returnHTML = true)
    {
        return $this->historyNodesListFilteredFN(0, $returnHTML);
    }

    /**
     * PRIVATE METHODS
     */

    /**
     * get_course_data
     * Fetches an associative array containing id_node, node name, node type, visits number
     * for each node in the course instance $this->id_course_instance.
     * Visits number refers to visits made by student with id $this->id_student.
     *
     * @param int $id_course - optional
     * Sets $this->nodes_count, $this->node_visits_count, $this->node_visits_ratio.
     */
    public function getCourseData()
    {
        $dh = $GLOBALS['dh'];

        if (!isset($this->id_course) || (isset($this->id_course) && is_null($this->id_course))) {
            //print("<BR>query su id_corso<BR>");
            $this->id_course = $dh->getCourseIdForCourseInstance($this->id_course_instance);
            if (AMADataHandler::isError($this->id_course)) {
                $errObj = new ADAError($this->id_course, translateFN("Errore nella lettura dei dati"));
            }
        }

        $this->course_data = $dh->getStudentVisitsForCourseInstance($this->id_student, $this->id_course, $this->id_course_instance);
        //verificare il controllo degli errori
        if (AMADataHandler::isError($this->course_data)) {
            $errObj = new ADAError($this->course_data, translateFN("Errore nella lettura dei dati"));
        }
        // in this case, for counting nodes we are taking in account notes too.
        $this->nodes_count = count($this->course_data);
        foreach ($this->course_data as $course_node) {
            if ($course_node['numero_visite'] != null) {
                $this->visited_nodes_count++;
                $this->node_visits_count[(int)$course_node['tipo']] += $course_node['numero_visite'];
            }
            // in this case we do not take in account notes
            //if ( $course_node['tipo'] < 2 )
            //{
            //    $this->nodes_count++;
            //}
        }
        if ($this->visited_nodes_count > 0) {
            $this->node_visits_ratio = round($this->getTotalVisitedNodes() / $this->visited_nodes_count, 2);
        } else {
            $this->node_visits_ratio = 0;
        }
    }

    /**
     * Performs the calculation of how much time the student has spent in the course.
     *
     * @param array $visit_time array of rows as returned by AMATesterDataHandler::getStudentVisitTime
     *
     * @return int time in seconds
     */
    public static function applyTimeCalc($visit_time)
    {
        $nodes_time = 0;
        if (isset($visit_time[0])) {
            $n_session = $visit_time[0]['session_id'];
            $n_start = $visit_time[0]['data_visita'];
            $n_time_prec = $visit_time[0]['data_visita'];
        } else {
            $n_session = null;
            $n_start = null;
            $n_time_prec = null;
        }
        $num_nodi = count($visit_time);
        foreach ($visit_time as $key => $val) {
            // controlla se vi e' stato cambio del valore del session_id
            if ($val['session_id'] != $n_session) {
                $nodes_time  = $nodes_time + ($n_time_prec - $n_start); // + ADA_SESSION_TIME;
                $n_session   = $val['session_id'];
                $n_start     = $val['data_visita'];
                $n_time_prec = $val['data_visita']; //ora di entrata nel primo nodo visitato nella sessione
                // assegna il valore di data uscita del "nodo precedente"
            } elseif ($key == ($num_nodi - 1)) {
                $nodes_time = $nodes_time + $val['data_visita'] - $n_start;
            } else {
                $n_time_prec = $val['data_uscita'];
            }
        }
        return $nodes_time;
    }

    /**
     * get_visit_time
     * Fetches an associative array containing history information for nodes in $this->id_course_instance
     * visited by student $this->id_student.
     * Uses the fetched array to calculate $this->total_time time spent by student visiting
     * the course instance.
     */
    public function getVisitTime()
    {
        $dh = $GLOBALS['dh'];
        $visit_time = $dh->getStudentVisitTime($this->id_student, $this->id_course_instance);
        //verificare il controllo degli errori
        if (AMADataHandler::isError($visit_time)) {
            $errObj = new ADAError($visit_time, translateFN("Errore nella lettura dei dati"));
        }
        $this->total_time = static::applyTimeCalc($visit_time);
        unset($visit_time);
    }

    /**
     * @author giorgio 15/mag/2013
     * added $returnHTML parameter
     */
    public function formatHistoryDataFN($user_historyAr, $returnHTML = true)
    {
        //global $dh, $http_root_dir;
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        // $debug = $GLOBALS['debug'];

        $data = [];
        //if (!$user_historyAr) {
        //    $user_historyAr = $this->historyAr;
        //}
        foreach ($user_historyAr as $historyHa) {
            $visit_date = $historyHa['data_visita'];
            $exit_date = $historyHa['data_uscita'];
            //$id_course = $historyHa[4];
            $id_visited_node = $historyHa['id_nodo'];
            // $id_visited_node = $id_course."_".$historyHa[0]; Id_node gia' completo
            $u_time_spent = ($exit_date - $visit_date);

            $int_hours = floor($u_time_spent / 3600);
            $rest_sec = $u_time_spent - ($int_hours * 3600);
            $int_mins = floor($rest_sec / 60);
            $int_secs = floor($u_time_spent - ($int_hours * 3600) - ($int_mins * 60));

            $time_spent = sprintf("%02d:%02d:%02d", $int_hours, $int_mins, $int_secs);

            if ($time_spent == '00:00:00') {
                $time_spent = '-';
            }

            $date = Utilities::ts2dFN($visit_date);
            $time = Utilities::ts2tmFN($visit_date);
            // $dh = new Node($id_visited_node);
            //$dataHa = $dh->getNodeInfo($id_visited_node);
            // $dataHa = Node::getNodeInfo($id_visited_node);


            $name = $historyHa['nome'];
            // vito, 16 feb 2009
            //            $icon = $this->getIcon($historyHa['tipo']);
            $css_classname = $this->getClassNameForNodeType($historyHa['tipo']);
            $label = translateFN('Nodo');
            $label2 = translateFN('Data');
            $label3 = translateFN('tempo trascorso');
            // vito, 16 feb 2009
            //            $histAr = array($label=>"<a href=" . $http_root_dir . "/browsing/view.php?id_node=$id_visited_node>".$icon." $name</a>",
            //                            $label2=>$date." ".$time,
            //                            $label3=>$time_spent);

            if ($returnHTML) {
                $link_to_node = '<span class="' . $css_classname . '"><a href="' . $http_root_dir . '/browsing/view.php?id_node=' . $id_visited_node . '">' . $name . '</a></span>';
            } else {
                $link_to_node = $name;
            }
            $histAr = [$label => $link_to_node,
            $label2 => $date . " " . $time,
            $label3 => $time_spent];

            array_push($data, $histAr);
        }
        return $data;
    }

    /**
     * getIcon
     * Used to get the right icon based on node type
     * @param int $node_type
     * @return string - an html string containing <img> tag.
     */
    public function getIcon($node_type)
    {
        switch ($node_type) {
            case ADA_GROUP_TYPE:
                $icon = "<img src=\"img/group_ico.png\" border=0>";
                break;
            case ADA_PRIVATE_NOTE_TYPE:
                $icon = "<img src=\"img/p_nota_pers.png\" border=0>";
                break;
            case ADA_NOTE_TYPE:
                $icon = "<img src=\"img/note_ico.png\" border=0>";
                break;
            case ADA_LEAF_TYPE:
                $icon = "<img src=\"img/node_ico.png\" border=0>";
                break;
            case ADA_STANDARD_EXERCISE_TYPE:
            default:
                $icon = "<img src=\"img/exer_ico.png\" border=0>";
                break;
        }
        return $icon;
    }
    public function getClassNameForNodeType($node_type)
    {
        switch ($node_type) {
            case ADA_NOTE_TYPE:
                return "ADA_NOTE_TYPE";

            case ADA_PRIVATE_NOTE_TYPE:
                return "ADA_PRIVATE_NOTE_TYPE";

            case ADA_GROUP_TYPE:
                return "ADA_GROUP_TYPE";

            case ADA_LEAF_TYPE:
            default:
                return "ADA_LEAF_TYPE";
        }
    }
}

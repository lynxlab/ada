<?php

/**
 * AbstractCourse class
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        courses_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Course;

use Lynxlab\ADA\CORE\HmtlElements\IList;
use Lynxlab\ADA\Main\Bookmark\Bookmark;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\User\ADAUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\masort;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;

abstract class AbstractCourse
{
    public $id;

    public $nome;
    public $titolo;
    public $id_autore;
    public $id_layout;
    public $id_lingua;
    public $descr;
    public $d_create;
    public $d_publish;
    public $id_nodo_iniziale;
    public $id_nodo_toc;
    public $media_path;

    public $full;
    public $error_msg;
    public $template_family;
    public $static_mode;
    public $crediti;
    public $duration_hours;
    public $service_level;

    public function __construct($id_course)
    {
        $dh = $GLOBALS['dh'];

        $dataHa = $dh->get_course($id_course);
        if (AMA_DataHandler::isError($dataHa) || (!is_array($dataHa))) {
            $this->full = 0;
        } else {
            if (!empty($dataHa['nome'])) {
                $this->full = 1;
                /* fare attenzione ad eventuali modifiche ai nomi delle colonne
         * nella tabella modello_corso.
         * devono coincidere con i nomi degli attributi di questa classe.
                */
                foreach ($dataHa as $key => $value) {
                    $this->$key = $value;
                }

                $this->id  = $id_course;
                $id_layout = $this->id_layout;
                // Table Layout is not available.
                // $layoutHa  = $dh->_get_layout($id_layout);
                // $this->template_family = $layoutHa['family'];
            }
        }
    }

    public function getId()
    {
        return $this->id;
    }
    /* RIASSUNTO:
   main_indexFN: mostra nodi e gruppi, per studente (no autore, tutor e admin)
   explode_nodesFN : ricorsiva, chiamata per default e se $order=struct
   explode_nodes_iterativeFN : iterativa, chiamata se $order=alfa

   se hide_visits=1 mostrano anche le visite dello studente

   class_indexFN: mostra nodi e gruppi,per tutor e autore  (no studente e admin)
   class_explode_nodesFN : ricorsiva, chiamata per default e se $order=struct
   class_explode_nodes_iterativeFN : iterativa, chiamata se $order=alfa

   se hide_visits=1 mostrano anche le visite della classe (tutor) o di tutti (autore)

   forum_main_indexFN: mostra  solo note, per studente, tutor  (no admin e autore)
   forum_explode_nodesFN : ricorsiva, chiamata se $order=struct
   forum_explode_nodes_iterativeFN : iterativa, chiamata per default e se $order=chrono

   *se hide_visits=1 mostrano anche le visite della classe (tutor)
    */



    public function main_indexFN($id_toc = '', $depth = 1, $user_level = 1, $user_history = '', $user_type = AMA_TYPE_STUDENT, $order = 'struct', $expand = 0, $mode = 'standard')
    {
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $sess_id_course = $_SESSION['sess_id_course'];

        if (empty($id_toc)) {
            $id_toc = $sess_id_course . "_" . ADA_DEFAULT_NODE;
        }
        $base = new Node($id_toc, 0);  // da dove parte
        $alt = translateFN("Gruppo principale");
        $icon = "_gruppo.png";
        $index = "<p>";
        if ($order == 'struct') {
            $index .= "<img name=\"nodo\" alt=$alt src=\"img/$icon\"> <a href=view.php?id_node=" . $id_toc . ">" . translateFN("Principale") . "</a>";
        }
        $index .= $this->tabled_explode_nodesFN(1, $user_level, $id_toc, $user_type, $order, $expand, $mode);
        $index .= "</p>";
        return $index;
    }


    public function tabled_explode_nodesFN($depth, $user_level, $id_parent, $id_profile, $order, $expand, $mode)
    {
        $lObj = new IList();
        if ($order == 'alfa') {
            $data =  $this->explode_nodes_iterativeFN($depth, $user_level, $id_parent, $id_profile, $order, $expand, $mode);
            $lObj->initList('1', '1', 1);
        } else {    // = 'r'
            $data =  $this->explode_nodesFN($depth, $user_level, $id_parent, $id_profile, $order, $expand, $mode);
            $lObj->initList(0, '', 1);
        }
        $lObj->setList($data);
        $tabled_index = $lObj->getList();

        return $tabled_index;
    }



    public function explode_nodes_iterativeFN($depth, $user_level, $id_parent, $id_profile, $order, $expand, $mode)
    {
        // returns an Array
        // only students
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];

        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $id_course  = $GLOBALS['id_course'];
        $hide_visits = $GLOBALS['hide_visits'];
        $with_icons = $GLOBALS['with_icons'];
        $with_dates = $GLOBALS['with_dates'];
        $with_authors = $GLOBALS['with_authors'];


        $tot_notes = 0;
        $childnumber = 0;
        $out_fields_ar = ['nome', 'tipo'];
        $clause = "";
        $childrenAr = $dh->find_course_nodes_list($out_fields_ar, $clause, $sess_id_course);
        $childrenAr = masort($childrenAr, 1); // il campo 1 �il nome del nodo
        $k = 0;
        $indexAr = [];
        foreach ($childrenAr as $nodeHa) {
            $k++;
            $index_item = "";
            $id_child = $nodeHa[0];
            if (!empty($id_child)) {
                $childnumber++;
                $child_dataHa = $dh->get_node_info($id_child);
                $node_instance = $child_dataHa['instance'];
                $id_node_parent = $child_dataHa['parent_id'];
                $creation_date = $child_dataHa['creation_date'];
                $version = $child_dataHa['version'];
                $node_authorHa =   $child_dataHa['author'];
                $node_author_name = $node_authorHa['nome'];
                $node_author_surname = $node_authorHa['cognome'];
                $parent_dataHa = $dh->get_node_info($id_node_parent);
                if (($id_node_parent == null) or (!is_array($parent_dataHa))) { // map
                    continue;
                }
                $parent_type = $parent_dataHa['type'];
                if ($parent_type >= ADA_STANDARD_EXERCISE_TYPE) {
                    $node_type = 'answer';
                } else {
                    $node_type = $child_dataHa['type'];
                }

                switch ($node_type) {
                    case 'answer':
                        break;
                    case ADA_LEAF_TYPE:    //node
                        if ($child_dataHa['level'] <= $user_level) {
                            $alt = translateFN("Nodo inferiore");
                            $icon = "_nodo.png";
                            if (!isset($hide_visits) or $hide_visits == 0) {
                                $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                            }
                            if (empty($visit_count)) {
                                if ($with_icons) {
                                    $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> ";
                                }
                                $index_item .= "<b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>";
                            } else {
                                if ($with_icons) {
                                    $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> ";
                                }
                                $index_item .= "<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                   ($visit_count)";
                            }

                            // is user visiting this node?
                            if ($id_child == $sess_id_node) {
                                if ($with_icons) {
                                    $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                }
                            }
                            // has user bookmarked this node?
                            $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                            if ($id_bk) {
                                if ($with_icons) {
                                    $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\"  border=\"0\"></a>";
                                }
                            }
                        } else {
                            $alt = translateFN("Nodo non visitabile");
                            $icon = "_nododis.png"; // _nododis.png
                            if ($with_icons) {
                                $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> ";
                            }
                            $index_item .= $child_dataHa['name'];
                        }

                        break;
                    case ADA_GROUP_TYPE:    //group
                        if ($child_dataHa['level'] <= $user_level) {
                            $alt = translateFN("Approfondimento");
                            $icon = "_gruppo.png";
                            if (!isset($hide_visits) or $hide_visits == 0) {
                                $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                            }
                            if (empty($visit_count)) {
                                if ($with_icons) {
                                    $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\"> ";
                                }
                                $index_item .= "<b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>";
                            } else {
                                if ($with_icons) {
                                    $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\"> ";
                                }
                                $index_item .= "<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                   ($visit_count)";
                            }


                            // is user visiting this node?
                            if ($id_child == $sess_id_node) {
                                $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                            }

                            // has user bookmarked this node?
                            $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                            if ($id_bk) {
                                $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\" border=\"0\"></a>";
                            }
                        } else {
                            $alt = translateFN("Approfondimento non visitabile");
                            $icon = "_gruppodis.png";
                            if ($with_icons) {
                                $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\" >";
                            }
                            $index_item .= $child_dataHa['name'];
                        }
                        break;
                    case ADA_NOTE_TYPE:    // note added by users
                    case ADA_PRIVATE_NOTE_TYPE:    // note added by users
                        $index_item = "";
                        break;
                    default: // exercise, etc
                        $index_item = "";
                        break;
                } // end case
            }  // end if

            if (!empty($index_item)) {
                if ($with_dates) {
                    $index_item .= " $creation_date";
                }
                if ($with_authors) {
                    $index_item .= " $node_author_name $node_author_surname";
                }
                $indexAr[] = $index_item;
            }
        }   // end foreach
        return $indexAr;
    }


    public function explode_nodesFN($depth, $user_level, $id_parent, $id_profile, $order, $expand)
    {
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];

        $id_course  = $GLOBALS['id_course'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $sess_id_course = $GLOBALS['sess_id_course'];
        $hide_visits = $GLOBALS['hide_visits'];

        // recursive
        $indexAr = [];
        if (!empty($expand)  && ($expand > $depth)) {
            $childrenAr = $dh->get_node_children($id_parent);
            if (is_array($childrenAr)) {
                $depth++;
                $childnumber = 0;

                $index_item = [];
                foreach ($childrenAr as $id_child) {
                    if (!empty($id_child)) {
                        $sub_indexAr = "";
                        $childnumber++;
                        $visit_count = 0;
                        $child_dataHa = $dh->get_node_info($id_child);
                        if (is_array($child_dataHa)) {
                            $node_type = $child_dataHa['type'];
                            switch ($node_type) {
                                case ADA_LEAF_TYPE:    //node
                                    if ($child_dataHa['level'] <= $user_level) {
                                        $alt = translateFN("Nodo inferiore");
                                        $icon = "_nodo.png";

                                        switch ($id_profile) {
                                            case AMA_TYPE_STUDENT:
                                            default:
                                                if (!isset($hide_visits) or $hide_visits == 0) {
                                                    $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                                                }
                                                break;
                                            case AMA_TYPE_TUTOR:
                                                /*
                         if (!isset($hide_visits) OR $hide_visits==0) {
                         $visit_count  = User::is_visited_by_classFN($id_child,$sess_id_course_instance,$sess_id_course);
                         }
                                            */
                                                break;
                                            case AMA_TYPE_AUTHOR:
                                                /*
                         * if (!isset($hide_visits) OR $hide_visits==0) {
                         $visit_count  = User::is_visitedFN($id_child);
                         }
                                            */
                                        }
                                        if ($visit_count == 0) {
                                            $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">
                                   <b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>";
                                        } else {
                                            $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">
                                   <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                   ($visit_count)";
                                        }
                                    } else {
                                        $alt = translateFN("Nodo non visitabile");
                                        $icon = "_nododis.png"; // _nododis.png
                                        $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">" . $child_dataHa['name'];
                                    }
                                    // is user visiting this node?
                                    if ($id_child == $sess_id_node) {
                                        $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                    }

                                    // has user bookmarked this node?
                                    $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                                    if ($id_bk) {
                                        $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\"  border=\"0\"></a>";
                                    }


                                    break;
                                case ADA_GROUP_TYPE:    //group
                                    if ($child_dataHa['level'] <= $user_level) {
                                        $alt = translateFN("Approfondimento");
                                        $icon = "_gruppo.png";

                                        switch ($id_profile) {
                                            case AMA_TYPE_STUDENT:
                                            default:
                                                if (!isset($hide_visits) or $hide_visits == 0) {
                                                    $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                                                }
                                                break;
                                            case AMA_TYPE_TUTOR:
                                                /*
                               if (!isset($hide_visits) OR $hide_visits==0) {
                               $visit_count  = User::is_visited_by_classFN($id_child,$sess_id_course_instance,$sess_id_course);
                               }
                                            */
                                                break;
                                            case AMA_TYPE_AUTHOR:
                                                /*
                               if (!isset($hide_visits) OR $hide_visits==0) {
                               $visit_count  = User::is_visitedFN($id_child);
                               }
                                            */
                                                break;
                                            case AMA_TYPE_ADMIN:
                                                break;
                                        }
                                        if ($visit_count == 0) {
                                            $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\">
                                   <b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>$expand_link";
                                        } else {
                                            $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\">
                                   <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                   ($visit_count)";
                                        }


                                        // is user visiting this node?
                                        if ($id_child == $sess_id_node) {
                                            $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                        }

                                        // has user bookmarked this node?
                                        $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                                        if ($id_bk) {
                                            $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\" border=\"0\"></a>";
                                        }

                                        // recurses...
                                        //$sub_indexAr = array();
                                        $sub_indexAr = $this->explode_nodesFN($depth, $user_level, $id_child, $id_profile, $order, $expand);
                                    } else {
                                        $alt = translateFN("Approfondimento non visitabile");
                                        $icon = "_gruppodis.png";
                                        $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">" . $child_dataHa['name'];
                                    }
                                    break;
                                case ADA_NOTE_TYPE:    // node added by users
                                    // we don't want to show notes here
                                    $index_item = "";
                                    break;
                                case ADA_PRIVATE_NOTE_TYPE:    // node added by users
                                    // we don't want to show private notes here
                                    $index_item = "";
                                    break;
                                case 3: // exercise
                                case 4: // exercise
                                case 5: // exercise
                                case 6: // exercise
                                case 7: // exercise
                                    $out_fields_ar = ['data_visita', 'punteggio', 'ripetibile'];
                                    $history_exerc = $dh->find_ex_history_list($out_fields_ar, $sess_id_user, $sess_id_course_instance, $id_child);
                                    if (is_array($history_exerc)) {
                                        $h_exerc = array_shift($history_exerc);
                                        if (is_array($h_exerc)) {
                                            $already_executed = !$h_exerc[3];
                                        } else {
                                            $already_executed = "";
                                        }
                                    } else {
                                        $already_executed = "";
                                    }

                                    //$debug=1;mydebug(__LINE__,__FILE__,$already_executed[1]); $debug=0;
                                    if (!$already_executed) {
                                        $alt = translateFN("Esercizio");
                                        $icon = "_exer.png";
                                        $index_item = "<img name=\"esercizio\" alt=\"$alt\" src=\"img/$icon\"> <a href=exercise.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>";
                                    } else {
                                        $date = ts2dFN($history_exerc[0][1]);
                                        $alt = translateFN("Esercizio eseguito il ") . $date;
                                        $icon = "_exerdis.png"; // _gruppodis.png
                                        $index_item = "<img name=\"esercizio\" alt=\"$alt\" src=\"img/$icon\">" . $child_dataHa['name'];
                                    }

                                    // is user visiting this node?
                                    if ($id_child == $sess_id_node) {
                                        $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                    }

                                    // has user bookmarked this node?
                                    $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                                    if ($id_bk) {
                                        $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\"></a>";
                                    }

                                    break;
                                default: //?
                                    $index_item = "";
                                    /*
                               $icon = "_nodo.png";
                               $alt = translateFN("Nodo");
                               $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>";
                                */
                            } // end case
                        }
                    }  // end if
                    if (!empty($index_item)) {
                        $indexAr[] = $index_item;
                        if (is_array($sub_indexAr)) {
                            array_push($indexAr, $sub_indexAr);
                        }
                    }
                }   // end foreach
                // mydebug(__LINE__,__FILE__,$index);
                return $indexAr;
            } else {
                if (is_object($childrenAr)) { // is it an error?
                    return "";
                } else {
                    return "";
                }
            }
        } else {
            return "";
        }
    }

    public function getMaxLevel()
    {
        $dh = $GLOBALS['dh'];

        return $dh->get_course_max_level($this->id);
    }

    /* sara - Execute advaced search: first in AND, then progressively in OR
         *
         * es:
         *
         * field_1 AND field_2 AND field_3 if empty:
         * field_1 AND field_2 OR field_3  if empty:
         * field_1 OR field_2 AND field_3  if empty:
         * field_1 OR field_2 OR field_3
         *
         */
    public function executeSearch($name, $title, $text, $dh, $count, $id_user)
    {
        $out_fields_ar = ['nome', 'titolo', 'testo', 'tipo', 'id_utente', 'livello'];
        $operator = [0 => ' AND ', 1 => ' OR ', 2 => ' AND ', 3 => ' OR '];
        $operator2 = [0 => ' AND ', 1 => ' AND ', 2 => ' OR ', 3 => ' OR '];
        if ($count == 2) {
            $operator2 = [0 => ' AND ', 1 => ' OR '];
        }
        if ($count == 3) {
            $count = $count + 1;
        }

        for ($i = 0; $i < $count; $i++) {
            if (!empty($name)) {
                $clause = "nome LIKE '%$name%'";
            }
            if (!empty($title)) { //keywors
                if (isset($clause)) {
                    if ($operator[$i] == ' OR ' && $operator2[$i] == ' AND ') {
                        $clause = '(' . $clause . $operator[$i] . "titolo LIKE '%$title%')";
                    } else {
                        if ($operator[$i] == ' AND ' && $operator2[$i] == ' OR ') {
                            $clause = $clause . $operator[$i] . "( titolo LIKE '%$title%'";
                        } else {
                            $clause = $clause . $operator[$i] . " titolo LIKE '%$title%'";
                        }
                    }
                } else {
                    $clause = "titolo LIKE '%$title%'";
                }
            }
            if (!empty($text)) {
                if (isset($clause)) {
                    if ($operator[$i] == ' AND ' && $operator2[$i] == ' OR ') {
                        $clause = $clause . $operator2[$i] . "testo LIKE '%$title%')";
                    } else {
                        $clause = $clause . $operator2[$i] . "testo LIKE '%$text%'";
                    }
                } else {
                    $clause = "testo LIKE '%$text%'";
                }
            }
            $clause = '(' . $clause . ') and ((tipo <> ' . ADA_PRIVATE_NOTE_TYPE . ') OR (tipo =' . ADA_PRIVATE_NOTE_TYPE . ' AND id_utente = ' . $id_user . '))';
            $resHa = $dh->find_course_nodes_list($out_fields_ar, $clause, $_SESSION['sess_id_course']);
            if (!AMA_DataHandler::isError($resHa) and is_array($resHa) and !empty($resHa)) {
                break;
            }
            $clause = "";
        }
        return $resHa;
    }
}

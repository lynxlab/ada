<?php

/**
 * AbstractCourseInstance class
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

use function Lynxlab\ADA\Main\Utilities\masort;

abstract class AbstractCourseInstance
{
    public $id;
    public $id_corso;
    public $data_inizio;
    public $durata;
    public $data_inizio_previsto;
    public $id_layout;
    public $data_fine;
    public $template_family;
    public $self_instruction;
    public $self_registration;
    public $title;
    public $duration_subscription;
    public $price;
    public $start_level_student;
    public $open_subscription;
    public $duration_hours;
    public $service_level;
    public $full;

    public function __construct($id_course_instance)
    {
        $dh = $GLOBALS['dh'];

        // constructor
        $dataHa = $dh->course_instance_get($id_course_instance, true);
        if (AMA_DataHandler::isError($dataHa) || (!is_array($dataHa))) {
            $this->full = 0;
        } else {
            if (!empty($dataHa['id_corso'])) {
                $this->full = 1;
                foreach ($dataHa as $key => $value) {
                    $this->$key = $value;
                }
                $this->id = $id_course_instance;
                $id_layout = $this->id_layout;
                // Table Layout is not available.
                //$layoutHa = $dh->_get_layout($id_layout);
                //$this->template_family = $layoutHa['family'];
            }
        }
    }

    public function class_main_indexFN($id_toc = '', $depth = 1, $id_profile = AMA_TYPE_STUDENT, $order = 'struct', $expand = 1)
    {
        // indice di classe
        //  this version is intended for  tutor  or author use only
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $with_icons = $GLOBALS['with_icons'];
        $sess_id_course = $_SESSION['sess_id_course'];
        if (empty($id_toc)) {
            $id_toc = $sess_id_course . "_" . ADA_DEFAULT_NODE;
        }
        $base = new Node($id_toc, 0);  // da dove parte
        $alt = translateFN("Gruppo principale");
        $icon = "_gruppo.png";
        $index = "<p>";
        if ($order == 'struct') {
            if ($with_icons) {
                $index .= "<img name=\"nodo\" alt=$alt src=\"img/$icon\"> <a href=view.php?id_node=" . $id_toc . ">" . translateFN("Principale") . "</a>";
            } else {
                $index .= "<a href=view.php?id_node=" . $id_toc . ">" . translateFN("Principale") . "</a>";
            }
        }
        $index .= $this->tabled_class_explode_nodesFN(1, $id_toc, $id_profile, $order, $expand);
        $index .= "</p>";
        return $index;
    }

    public function tabled_class_explode_nodesFN($depth, $id_parent, $id_profile, $order, $expand = 1)
    {
        $lObj = new Ilist();
        if ($order == 'alfa') {
            $data =  $this->class_explode_nodes_iterativeFN($depth, $id_parent, $id_profile, $order, $expand);
            $lObj->initList('1', '1', 1);
        } else {    // = 'r'
            $data =  $this->class_explode_nodesFN($depth, $id_parent, $id_profile, $order, $expand);
            $lObj->initList(0, '', 1);
        }
        $lObj->setList($data);
        $tabled_index = $lObj->getList();

        return $tabled_index;
    }

    public function class_explode_nodes_iterativeFN($depth, $id_parent, $id_profile, $order, $expand = 1)
    {
        //  this version is intended for  tutor  or author use only
        // returns an array


        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $id_course  = $GLOBALS['id_course'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $hide_visits = $GLOBALS['hide_visits'];

        $tot_notes = 0;
        $childnumber = 0;
        $indexAr = [];
        $out_fields_ar = ['nome', 'tipo'];
        $clause = "";
        $childrenAr = $dh->find_course_nodes_list($out_fields_ar, $clause, $sess_id_course);
        $childrenAr = masort($childrenAr, 1); // il campo 1 �il nome del nodo
        foreach ($childrenAr as $nodeHa) {
            $index_item = "";
            $id_child = $nodeHa[0];
            if (!empty($id_child)) {
                $childnumber++;
                $child_dataHa = $dh->get_node_info($id_child);
                $node_instance = $child_dataHa['instance'];
                $id_node_parent = $child_dataHa['parent_id'];
                $node_keywords = $child_dataHa['title'];
                $parent_dataHa = $dh->get_node_info($id_node_parent);
                if ((!AMA_datahandler::isError($parent_dataHa)) && ($parent_dataHa['type'] >= ADA_STANDARD_EXERCISE_TYPE)) {
                    $node_type = 'answer';
                } else {
                    $node_type = $child_dataHa['type'];
                }

                switch ($node_type) { // exercises?
                    case 'answer':
                        break;
                    case ADA_LEAF_TYPE:    //node
                        $alt = translateFN("Nodo inferiore");
                        $icon = "_nodo.png";
                        if (!isset($hide_visits) or $hide_visits == 0) {
                            $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                        }
                        if (empty($visit_count)) {
                            $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">&nbsp;<b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>\n";
                        } else {
                            $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">&nbsp;<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a> ($visit_count)\n";
                        }
                        // has user bookmarked this node?
                        $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                        if ($id_bk) {
                            $index_item .= "&nbsp;<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\"  border=\"0\"></a>";
                        }

                        break;
                    case ADA_GROUP_TYPE:    //group
                        $alt = translateFN("Approfondimento");
                        $icon = "_gruppo.png";
                        if (!isset($hide_visits) or $hide_visits == 0) {
                            $visit_count  = ADAUser::is_visited_by_userFN($id_child, $sess_id_course_instance, $sess_id_user);
                        }
                        if (empty($visit_count)) {
                            $index_item .= "<img name=\"nodo\" alt=$alt src=\"img/$icon\">&nbsp;<b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>\n";
                        } else {
                            $index_item .= "<img name=\"nodo\" alt=$alt src=\"img/$icon\">&nbsp;<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>($visit_count)";
                        }


                        // is user visiting this node?
                        if ($id_child == $sess_id_node) {
                            $index_item .= "&nbsp;<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">&nbsp;";
                        }

                        // has user bookmarked this node?
                        $id_bk = Bookmark::is_node_bookmarkedFN($sess_id_user, $id_child);
                        if ($id_bk) {
                            $index_item .= "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\"><img name=\"bookmark\" alt=\"bookmark\" src=\"img/check.png\" border=\"0\"></a>&nbsp;";
                        }
                        break;
                    case ADA_NOTE_TYPE:    // note added by users
                    case ADA_PRIVATE_NOTE_TYPE:    // private note added by users
                        $index_item = "";
                        break;
                    default: // ?
                        $index_item = "";
                        break;
                } // end case
            }  // end if
            if (!empty($index_item)) {
                $indexAr[] = $index_item;
            }
        }   // end foreach
        return $indexAr;
    }

    public function class_explode_nodesFN($depth, $id_parent, $id_profile, $order, $expand)
    {
        //  this version is intended for  tutor  or author use only
        // returns an array

        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $id_course  = $GLOBALS['id_course'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $hide_visits = $GLOBALS['hide_visits'];

        // recursive

        if (!empty($expand)  && ($expand > $depth)) {
            $childrenAr = $dh->get_node_children($id_parent);
            $indexAr = [];
            if (is_array($childrenAr)) {
                $index_item = "";
                $sub_indexAr = [];
                $depth++;
                $childnumber = 0;
                foreach ($childrenAr as $id_child) {
                    if (!empty($id_child)) {
                        $childnumber++;
                        $visit_count = 0;
                        $child_dataHa = $dh->get_node_info($id_child);
                        $node_type = $child_dataHa['type'];
                        switch ($node_type) {
                            case ADA_LEAF_TYPE:    //node
                                $alt = translateFN("Nodo");
                                $icon = "_nodo.png";

                                switch ($id_profile) {
                                    case AMA_TYPE_STUDENT:
                                        break;
                                    case AMA_TYPE_TUTOR:
                                        if (!isset($hide_visits) or $hide_visits == 0) {
                                            $visit_count  = ADAUser::is_visited_by_classFN($id_child, $sess_id_course_instance, $sess_id_course);
                                        }
                                        break;

                                    case AMA_TYPE_AUTHOR:
                                        if (!isset($hide_visits) or $hide_visits == 0) {
                                            $visit_count  = ADAUser::is_visitedFN($id_child);
                                        }
                                        // no break
                                    case AMA_TYPE_ADMIN:
                                        break;
                                } //end switch $id_profile
                                if ($visit_count == 0) {
                                    $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">
                                   <b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>\n";
                                } else {
                                    $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">
                                   <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                    (" . translateFN("visite") . ": $visit_count)\n";
                                }


                                // is user visiting this node?
                                if ($id_child == $sess_id_node) {
                                    $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                }

                                // is someone else there?
                                $is_someone = ADAUser::is_someone_thereFN($sess_id_course_instance, $id_child);
                                if ($is_someone >= 1) {
                                    $index_item .= "<img  name=\"altri\" alt=\"altri\" src=\"img/_student.png\">";
                                }

                                break;
                            case ADA_GROUP_TYPE:    //group
                                $alt = translateFN("Approfondimento");
                                $icon = "_gruppo.png";

                                switch ($id_profile) {
                                    case AMA_TYPE_TUTOR:
                                        if (!isset($hide_visits) or $hide_visits == 0) {
                                            $visit_count  = ADAUser::is_visited_by_classFN($id_child, $sess_id_course_instance, $sess_id_course);
                                        }
                                        break;
                                    case AMA_TYPE_AUTHOR:
                                        if (!isset($hide_visits) or $hide_visits == 0) {
                                            $visit_count  = ADAUser::is_visitedFN($id_child);
                                        }
                                } // end switch $id_profile

                                if ($visit_count == 0) {
                                    $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\">
                                   <b><a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a></b>\n";
                                } else {
                                    $index_item = "<img name=\"nodo\" alt=$alt src=\"img/$icon\">
                                   <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>
                                    (" . translateFN("visite") . ": $visit_count)\n";
                                }
                                // is user visiting this node?
                                if ($id_child == $sess_id_node) {
                                    $index_item .= "<img  name=\"attuale\" alt=\"attuale\" src=\"img/_anchor.png\">";
                                }

                                // is someone else there?
                                $is_someone = ADAUser::is_someone_thereFN($sess_id_course_instance, $id_child);
                                if ($is_someone >= 1) {
                                    $index_item .= "<img  name=\"altri\" alt=\"altri\" src=\"img/_student.png\">";
                                }

                                $sub_indexAr = $this->class_explode_nodesFN($depth, $id_child, $id_profile, $order, $expand);

                                break;
                            case ADA_NOTE_TYPE:    // note added by users
                            case ADA_PRIVATE_NOTE_TYPE:    // private note added by users
                                $index_item = "";
                                // we don't want to show notes here
                                break;
                            case 3: // exercise
                            case 4: // exercise
                            case 5: // exercise
                            case 6: // exercise
                                $alt = translateFN("Esercizio");
                                $icon = "_exer.png";
                                if (($id_profile == AMA_TYPE_AUTHOR) or ($id_profile == AMA_TYPE_TUTOR)) {
                                    $index_item = "<img name=\"esercizio\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>\n";
                                } else {
                                    $index_item = "<img name=\"esercizio\" alt=\"$alt\" src=\"img/$icon\"> <a href=exercise.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>\n";
                                }

                                break;
                            default:
                                $icon = "_nodo.png";
                                $alt = translateFN("Nodo");
                                $index_item = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>\n";
                        } // end switch $node_type
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

    public function forum_main_indexFN($id_toc = '', $depth = 1, $id_profile = AMA_TYPE_STUDENT, $order = 'chrono', $id_student = -1, $mode = 'standard')
    {
        if ($id_student == -1) {
            return '';
        }
        // class function
        // only notes are showed
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $id_course  = $GLOBALS['id_course'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $hide_visits = $GLOBALS['hide_visits'];



        if (empty($id_toc)) {
            $id_toc = $sess_id_course . "_" . ADA_DEFAULT_NODE;
        }
        $base = new Node($id_toc, 0);  // da dove parte
        $alt = translateFN("Gruppo principale");
        $icon = "_gruppo.png";

        $out_fields_ar = ['data_creazione', 'tipo'];
        $clause = "id_istanza =  $sess_id_course_instance AND tipo = " . ADA_NOTE_TYPE;
        $childrenAr = $dh->find_course_nodes_list($out_fields_ar, $clause, $sess_id_course);
        $note_count = count($childrenAr);
        $index = $note_count . translateFN(" note attualmente presenti nel Forum di classe.");
        $index .= "<p>";
        if ($order == 'struct') {
            // $index .= "<img name=\"nodo\" alt=$alt src=\"img/$icon\"> <a href=view.php?id_node=".$id_toc.">".translateFN("Principale")."</a>";
            $index .= $this->tabled_forum_explode_nodesFN(1, $id_toc, $id_profile, $order, $id_student, $mode);
        } else { //order=chrono
            $index .= $this->tabled_forum_explode_nodesFN(1, $id_toc, $id_profile, $order, $id_student, $mode);
        }
        $index .= "</p>";
        return $index;
    }


    public function tabled_forum_explode_nodesFN($depth, $id_parent, $id_profile, $order, $id_student, $mode = 'standard')
    {
        // returns an html list
        $lObj = new Ilist();

        if ($order == 'chrono') {
            $data =  $this->forum_explode_nodes_iterativeFN($depth, $id_parent, $id_profile, $order, $id_student, $mode);
            $lObj->initList('1', '1', 1);
        } else {    // = 'struct'
            $data =  $this->forum_explode_nodesFN($depth, $id_parent, $id_profile, $order, $id_student, $mode);
            $lObj->initList(0, '', 1);
        }
        $lObj->setList($data);
        $tabled_index = $lObj->getList();
        return $tabled_index;
    }

    public function forum_explode_nodes_iterativeFN($depth, $id_parent, $id_profile, $order, $id_student, $mode = 'standard')
    {
        // only notes are showed !
        // returns an array

        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $id_course  = $GLOBALS['id_course'];
        $id_node_exp =  $GLOBALS['id_node_exp'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $hide_visits = $GLOBALS['hide_visits'];
        $with_icons = $GLOBALS['with_icons'];

        $tot_notes = 0;
        $childnumber = 0;
        $indexAr = [];
        $out_fields_ar = ['data_creazione', 'tipo'];
        $clause = "id_istanza =  $sess_id_course_instance";
        $childrenAr = $dh->find_course_nodes_list($out_fields_ar, $clause, $sess_id_course);
        // $debug=1; mydebug(__LINE__,__FILE__,$childrenAr);
        $childrenAr = masort($childrenAr, 1, -1);
        foreach ($childrenAr as $nodeHa) {
            $index_item = "";
            $id_child = $nodeHa[0];
            if (!empty($id_child)) {
                $childnumber++;
                $child_dataHa = $dh->get_node_info($id_child);
                $node_type = $child_dataHa['type'];
                $node_instance = $child_dataHa['instance'];
                switch ($node_type) {
                    case ADA_LEAF_TYPE:    //node
                        break;
                    case ADA_GROUP_TYPE:    //group
                        break;
                    case ADA_NOTE_TYPE:    // note added by users
                    case ADA_PRIVATE_NOTE_TYPE:
                        $tot_notes++;
                        // we want to show ONLY notes here
                        // notes doesn't have levels !
                        $node_date = $child_dataHa['creation_date'];
                        $autoreHa = $child_dataHa['author'];
                        $autore =  $autoreHa['id'];
                        $is_note_visibile = 0;
                        $class_tutor_id = $dh->course_instance_tutor_get($sess_id_course_instance);
                        $expand_link = "<a href=\"main_index.php?op=forum&id_course=$sess_id_course&id_course_instance=$sess_id_course_instance&id_node_exp=$id_child\"><img src=\"img/_expand.png\" border=0></a>&nbsp;";
                        $contract_link = "<a href=\"main_index.php?op=forum&id_course=$sess_id_course&id_course_instance=$sess_id_course_instance\"><img src=\"img/_contract.png\" border=0></a>&nbsp;";

                        if ($class_tutor_id == $autore) { //Nota del tutor
                            $is_note_visibile = 1;
                            $alt = translateFN("Nota del tutor");
                            $icon = "_nota_tutor.png";
                            if ($sess_id_user == $autore) {
                                $author_name = "<strong>" . $autoreHa['username'] . "</strong>";
                            } else {
                                $author_name = $autoreHa['username'];
                            }
                        } else {
                            if (($node_type == ADA_PRIVATE_NOTE_TYPE) && ($id_student == $autore)) { // nota dello studente
                                $is_note_visibile = 1;
                                $alt = translateFN("Nota privata");
                                $icon = "_nota_pers.png";
                                $author_name = "<strong>" . $autoreHa['username'] . "</strong>";
                            } else {
                                //   $author_dataHa =  $dh->get_subscription($autore, $sess_id_course_instance);
                                //   if (!AMA_DB::isError($author_dataHa) AND (!VIEW_PRIVATE_NOTES_ONLY)){
                                $is_note_visibile = 1;
                                $alt = translateFN("Nota di un altro studente");
                                $icon = "_nota.png";
                                $author_name = $autoreHa['username'];
                            }
                        }
                        if ($is_note_visibile) {
                            if ((($id_profile == AMA_TYPE_TUTOR)  or ($id_profile == AMA_TYPE_STUDENT)) and (!isset($hide_visits) or $hide_visits == 0)) {
                                $visit_count  = "(" . ADAUser::is_visited_by_classFN($id_child, $sess_id_course_instance, $sess_id_course) . ")";
                            } else {
                                $visit_count = "";
                            }
                            switch ($mode) {
                                case 'export_all':
                                    $index_item = "\n $node_date -  $author_name\n" .
                                        $child_dataHa['name'] . $visit_count . "\n" .
                                        $child_dataHa['text'] . "\n";
                                    break;
                                case 'export_single':
                                    if ($autore == $id_student) {
                                        $index_item = "\n $node_date \n" .
                                            $child_dataHa['name'] . $visit_count . "\n" .
                                            $child_dataHa['text'] . "\n";
                                    }
                                    break;
                                case 'standard':
                                default:
                                    if ($with_icons) {
                                        $index_item = "$node_date <img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">&nbsp;<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a> (" . $author_name . ") $visit_count \n";
                                    } else {
                                        $index_item = "$node_date <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a> (" . $author_name . ") $visit_count \n";
                                    }
                                    if ((!empty($id_node_exp)) and ($id_node_exp == $id_child)) { // node to expand INLINE
                                        $index_item =   "<hr><dl><dd class=\"nota\">" . $contract_link . $index_item;
                                        $index_item .=   "<a name=$id_node_exp>" . $child_dataHa['text'] . "</dd></dl>\n";
                                    } else {
                                        $index_item =   "<hr>" . $expand_link . $index_item;
                                    }

                                    // is someone else there?
                                    $is_someone = ADAUser::is_someone_thereFN($sess_id_course_instance, $id_child);
                                    if ($is_someone >= 1) {
                                        if ($with_icons) {
                                            $index_item .= "&nbsp;<img  name=\"altri\" alt=\"altri\" src=\"img/_student.png\">";
                                        } else {
                                            $index_item .= " +";
                                        }
                                    }
                            }
                        }

                        break;
                    case ADA_STANDARD_EXERCISE: // exercise
                    default:
                        break;
                } // end case
            }  // end if
            if (!empty($index_item)) {
                $indexAr[] = $index_item;
            }
        }   // end foreach
        return $indexAr;
    }

    public function forum_explode_nodesFN($depth, $id_parent, $id_profile, $order, $id_student, $mode = 'standard')
    {
        // recursive (slow!)
        // only notes are showed
        // returns an array

        $sess_id_user = $_SESSION['sess_id_user'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $id_course  = $GLOBALS['id_course'];
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $hide_visits = $GLOBALS['hide_visits'];
        $with_icons = $GLOBALS['with_icons'];

        static $tot_notes;


        // recursive
        if (!isset($indexAr)) {
            $indexAr = [];
        }

        if (!isset($tot_notes)) {
            $tot_notes = 0;
        }

        $childrenAr = $dh->get_node_children($id_parent, $sess_id_course_instance);

        if (is_array($childrenAr)) {
            $depth++;
            $childnumber = 0;
            $class_tutor_id = $dh->course_instance_tutor_get($sess_id_course_instance);
            foreach ($childrenAr as $id_child) {
                if (!empty($id_child)) {
                    $sub_indexAr = "";
                    $index_item = "";
                    $childnumber++;
                    $child_dataHa = $dh->get_node_info($id_child);
                    $node_type = $child_dataHa['type'];
                    $node_instance = $child_dataHa['instance'];
                    switch ($node_type) {
                        case ADA_LEAF_TYPE:    //node
                        case ADA_GROUP_TYPE:    //group
                            $sub_indexAr = $this->forum_explode_nodesFN($depth, $id_child, $id_profile, $order, $id_student);
                            break;
                        case ADA_NOTE_TYPE:    // node added by users
                        case ADA_PRIVATE_NOTE_TYPE:
                            $tot_notes++;
                            // we want to show ONLY notes here
                            $node_date = $child_dataHa['creation_date'];
                            $autoreHa = $child_dataHa['author'];
                            $autore =  $autoreHa['id'];
                            $is_note_visibile = 0;

                            // echo "TUTOR $class_tutor_id == AUTORE $autore == USER $sess_id_user";
                            if ($class_tutor_id == $autore) {
                                if ($node_instance == $sess_id_course_instance) {
                                    $is_note_visibile = 1;
                                    $alt = translateFN("Nota del tutor");
                                    $icon = "_nota_tutor.png";
                                    if ($sess_id_user == $autore) { // per ora c'e' un solo tutor per classe...
                                        $author_name = "<strong>" . $autoreHa['username'] . "</strong>";
                                    } else {
                                        $author_name = $autoreHa['username'];
                                    }
                                }
                            } else {
                                /*
                   * vito, 8 ottobre 2008 corretto il nome della costante in ADA_PRIVATE_NOTE_TYPE
                                */
                                /*
                   if (($node_type == ADA_PRIVATE_NOTE_TYPE) &&($id_student == $autore)){
                   if ($node_instance == $sess_id_course_instance) {
                   $is_note_visibile = 1;
                   $alt = translateFN("Nota privata");
                   $icon = "_nota_pers.png";
                   $author_name = "<strong>".$autoreHa['username']."</strong>";
                   }
                   } else {
                   // $author_dataHa =  $dh->get_subscription($autore, $sess_id_course_instance);
                   // if ((!AMA_DB::isError($author_dataHa))  AND (!VIEW_PRIVATE_NOTES_ONLY)){
                   if ($node_instance == $sess_id_course_instance) {
                   $is_note_visibile = 1;
                   $alt = translateFN("Nota di un altro studente");
                   $icon = "_nota.png";
                   $author_name = $autoreHa['username'];
                   }
                   }
                   }
                                */
                                if (
                                    $node_type == ADA_PRIVATE_NOTE_TYPE
                                    && $id_student == $autore
                                    && $node_instance == $sess_id_course_instance
                                ) {
                                    $is_note_visibile = 1;
                                    $alt = translateFN("Nota privata");
                                    $icon = "_nota_pers.png";
                                    $author_name = "<strong>" . $autoreHa['username'] . "</strong>";
                                } elseif (
                                    $node_type == ADA_NOTE_TYPE
                                    && $node_instance == $sess_id_course_instance
                                ) {
                                    // $author_dataHa =  $dh->get_subscription($autore, $sess_id_course_instance);
                                    // if ((!AMA_DB::isError($author_dataHa))  AND (!VIEW_PRIVATE_NOTES_ONLY)){
                                    $is_note_visibile = 1;
                                    $icon = "_nota.png";

                                    if ($id_student == $autore) {
                                        $alt = translateFN("Tua nota pubblica");
                                        $author_name = "<strong>" . $autoreHa['username'] . "</strong>";
                                    } else {
                                        $alt = translateFN("Nota di un altro studente");
                                        $author_name = $autoreHa['username'];
                                    }
                                }
                            } // end else   riga 1079

                            if ($is_note_visibile) {
                                if ((($id_profile == AMA_TYPE_TUTOR) or ($id_profile == AMA_TYPE_STUDENT)) and (!isset($hide_visits) or $hide_visits == 0)) {
                                    $visit_count  = "(" . ADAUser::is_visited_by_classFN($id_child, $sess_id_course_instance, $sess_id_course) . ")";
                                } else {
                                    $visit_count = "";
                                }

                                if ($with_icons) {
                                    $index_item .= "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">&nbsp;<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a> ($author_name) - $node_date $visit_count \n";
                                } else {
                                    $index_item .= "<a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a> ($author_name) - $node_date $visit_count \n";
                                }

                                // is someone else there?
                                //  TOO SLOW !  $is_someone = User::is_someone_thereFN($sess_id_course_instance,$id_child);
                                if ($is_someone >= 1) {
                                    $index_item .= "<img  name=\"altri\" alt=\"altri\" src=\"img/_student.png\">";
                                } else {
                                    $index_item .= " ";
                                }
                            }
                            // echo "<br> $tot_notes $id_child $node_type $is_note_visibile";
                            $children2Ar = $dh->get_node_children($id_child, $sess_id_course_instance);
                            if (is_array($children2Ar)) { // there are sub-notes
                                $sub_indexAr = $this->forum_explode_nodesFN($depth, $id_child, $id_profile, $order, $id_student);
                            }
                            break;
                        case ADA_TYPE_STANDARD_EXERCISE: // exercise
                        default:
                            break;
                    } // end case $type
                }  // end if
                if (!empty($index_item)) {
                    $indexAr[] = $index_item;
                }
                if (is_array($sub_indexAr)) {
                    array_push($indexAr, $sub_indexAr);
                }
                //        print_r($index_ar);
                // unset($sub_indexAr);
                unset($children2Ar);
            }   // end foreach

            return $indexAr;
        } else {
            if (is_object($childrenAr)) { // is it an error?
                return "";
            } else {
                return "";
            }
        }
    }
}

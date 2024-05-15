<?php

/**
 * Node, Media, Link classes
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        node_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Node;

use Lynxlab\ADA\CORE\html4\CBaseElement;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\IList;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Media\MediaViewer;
use Lynxlab\ADA\Main\Node\Media;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsNode;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Node
{
    //media
    public $text;
    public $audio;
    public $image = '';
    public $video;
    public $notes;
    public $private_notes;
    public $links;
    public $media;

    //user vars
    public $author;
    public $creation_date;
    public $visited;
    public $next_id;
    // var $previous_id; not defined
    public $order;
    public $level;
    public $position;
    public $icon;
    public $copyright;
    public $template_family;
    public $title;
    public $contacts;
    public $ordine;
    public $extended_node;


    //system vars

    public $name;
    public $type;
    public $id;
    public $parent_id;
    public $version;
    public $bgcolor;
    public $color;
    public $correctness;
    public $instance;

    public $published;
    public $language;

    public $children;
    public $full = 0;
    public $error_msg;

    public $open_vars = ['title', 'position', 'icon', 'text', 'audio', 'video', 'links'];
    public $logical_path = [];

    public $isForkedPaths;
    public $hyphenation;
    public $grammar;
    public $semantic;
    public $examples;


    //  public static function findNode($id_node, $extended_data_required=2) {
    //
    //    $dh = $GLOBALS['dh'];
    //
    //    // TODO: validare anche qui id_node?
    //    $dataHa = $dh->getNodeInfo($id_node);
    //    if (AMADataHandler::isError($dataHa) || !is_array($dataHa)) {
    //      return null;
    //    }
    //
    //    $nodeObj = new Node($dataHa);
    //      // qui il codice che setta node children, links e media
    //    return $nodeObj;
    //  }
    //
    //  public function __construct($nodeHa=array()) {
    //
    //    $this->author        = $nodeHa['author'];
    //    $this->position      = $nodeHa['position'];
    //    $this->name          = $nodeHa['name'];
    //    $this->title         = $nodeHa['title'];
    //    $this->text          = $nodeHa['text'];
    //    $this->type          = $nodeHa['type'];
    //    $this->creation_date = $nodeHa['creation_date'];
    //    $this->parent_id     = $nodeHa['parent_id'];
    //    $this->ordine        = $nodeHa['ordine'];
    //    $this->order         = $nodeHa['order'];
    //    $this->level         = $nodeHa['level'];
    //    $this->version       = $nodeHa['version'];
    //    $this->contacts      = $nodeHa['contacts'];
    //    $this->icon          = $nodeHa['icon'];
    //    $this->color         = $nodeHa['color'];
    //    $this->bgcolor       = $nodeHa['bgcolor'];
    //    $this->correctness   = $nodeHa['correctness'];
    //    $this->copyright     = $nodeHa['copyright'];
    //    $this->instance      = $nodeHa['instance'];
    //  }

    public function __construct($id_node, $extended_data_required = 2)
    {
        /**
         * @var \Lynxlab\ADA\Main\AMA\AMADataHandler $dh
         */
        $dh            =   $GLOBALS['dh'] ?? null;
        $error         =   $GLOBALS['error'] ?? null;
        $debug         =   $GLOBALS['debug'] ?? null;
        $root_dir      =   $GLOBALS['root_dir'] ?? null;
        $http_root_dir =   $GLOBALS['http_root_dir'] ?? null;

        $dataHa = $dh->getNodeInfo($id_node);

        if (AMADataHandler::isError($dataHa) || !is_array($dataHa)) {
            // FIXME: prima restituiva una stringa di testo
            $this->full = 0;
        } else {
            // TODO: verificare che imposti correttamente gli attributi del nodo e che
            // non ne crei di nuovi.
            foreach ($dataHa as $key => $value) {
                $this->$key = $value;
            }

            $this->full = 1;
            $this->id = $id_node;

            /*
             * extended node data
             */
            if ($extended_data_required) {
                /*
                 * node children
                 */
                $childrenAr = $dh->getNodeChildren($id_node);

                if (AMADataHandler::isError($childrenAr) || !is_array($childrenAr)) {
                    $this->children = '';
                } elseif (count($childrenAr) > 0) {
                    $this->children = $childrenAr;
                }

                $next_node = $this->nextNodeFN();
                if ($next_node != "") {
                    $this->next_id = $next_node;
                }

                if ($extended_data_required > 1) {
                    /*
                     * Links
                     */
                    $linksAr = $dh->getNodeLinks($id_node);
                    if (AMADataHandler::isError($linksAr) || !is_array($linksAr)) {
                        $this->links = '';
                    } elseif (count($linksAr) > 0) {
                        $this->links = $linksAr;
                    }
                    /*
                     * Media
                     */
                    $mediaAr = $dh->getNodeResources($id_node);

                    if (AMADataHandler::isError($mediaAr) || !is_array($mediaAr)) {
                        $this->media = "";
                    } elseif (is_array($mediaAr)) {
                        $this->media = $mediaAr;
                    }
                }
                /*
                 * Extended node (glossary)
                 */
                if ($dataHa['type'] == ADA_LEAF_WORD_TYPE || $dataHa['type'] == ADA_GROUP_WORD_TYPE) {
                    $extended_data_nodeHA = $dh->getExtendedNode($id_node);
                    if (AMADataHandler::isError($extended_data_nodeHA) || !is_array($extended_data_nodeHA)) {
                        $this->extended_node = "";
                    } elseif (is_array($extended_data_nodeHA)) {
                        foreach ($extended_data_nodeHA as $key => $value) {
                            $this->$key = $value;
                        }
                    }
                }
            }

            if (ModuleLoaderHelper::isLoaded('FORKEDPATHS')) {
                $this->isForkedPaths = ForkedPathsNode::checkNode($this);
            } else {
                $this->isForkedPaths = false;
            }
        }
    }

    public function object2arrayFN()
    {

        if ($this->id) {
            $dataHa = [];
            $dataHa['id'] = $this->id;
            $dataHa['author'] = $this->author;
            $dataHa['position'] = $this->position;             // the position (array: (x0, y0, x1, y1))
            $dataHa['name'] = $this->name;
            $dataHa['title'] = $this->title;
            $dataHa['text'] = $this->text;
            $dataHa['type'] = $this->type;                        //the type of node
            $dataHa['order'] = $this->order;
            $dataHa['creation_date'] = $this->creation_date; // the date of creation of the node (the format is specified in ADA_DATE_FORMAT)
            $dataHa['parent_id'] = $this->parent_id;
            $dataHa['next_id'] = $this->next_id;
            // $dataHa['previous_id'] = $this->previous_id;    // not defined
            $dataHa['livello'] = $this->level;                 // the level at which the node is visible in the course (0 - 3)
            $dataHa['version'] = $this->version;             //- version of the node (not used yet)
            $dataHa['contacts'] = $this->contacts;           //- number of contacts that the node has received
            $dataHa['icon'] = $this->icon;
            $dataHa['color'] = $this->color;
            $dataHa['correctness'] = $this->correctness;     // if the node is of type 3 or 4 (answers), give the correctness
            //   (0-10 or 0-100 or 0-whateverYouLike) of the answer, else it must be NULL
            // vito, 11 mar 2009. $this has instance and not id_instance.
            //$dataHa['id_instance'] = $this->id_instance;    // if node is a forum note
            $dataHa['id_instance'] = $this->instance;    // if node is a forum note
            $dataHa['copyright'] = $this->copyright;
            $dataHa['published'] = $this->published;    // if node is a forum note
            $dataHa['language'] = $this->language;
        } else {
            $dataHa = $this->error_msg;
        }

        return $dataHa;
    }

    public function copy($new_id_node)
    {
        //global $dh,$error,$debug;
        $dh =   $GLOBALS['dh'];
        $error =   $GLOBALS['error'];
        $debug =   $GLOBALS['debug'];
        $root_dir =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];
        // duplicates a node

        if ($new_id_node != $this->id) {
            $dataHa = $dh->getNodeInfo($this->id);
            //Utilities::mydebug(__LINE__,__FILE__,$dataHa);
            if (AMADataHandler::isError($dataHa) || (!is_array($dataHa))) {
                $msg = $dataHa->getMessage();
                if (!strstr($msg, 'record was not found')) {
                    header("Location: $error?err_msg=$msg");
                    exit;
                } else {
                    return $msg;
                }
            }
            $dataHa->node_id = $new_id_node;
            $dh->addNode($dataHa); // Array
        } else {
            return translateFN("Un nodo con questo id &egrave; gi&agrave; presente");
        }
    }

    public function graphIndexFN($id_toc = '', $depth = 1, $user_level = 1, $user_history = '')
    {
        // usata per la mappa grafica
        //global $dh,$error;
        //global $root_dir,$http_root_dir,$media_path;
        $dh =   $GLOBALS['dh'];
        $error =   $GLOBALS['error'];
        $media_path =   $GLOBALS['media_path'];
        $root_dir =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];
        if (empty($id_toc)) {
            $id_toc = $this->id;
        }
        $course_icons_media_path = $root_dir . $media_path;

        $base = new Node($id_toc, 1);  // da dove parte
        $children = $base->children;
        $type = $base->type;
        // Utilities::mydebug(__LINE__,__FILE__,$children);

        $children_ha = [];
        if (!empty($children) && (($type == ADA_GROUP_TYPE) || ($type == ADA_GROUP_WORD_TYPE))) {
            foreach ($children as $id_child) {
                $linked_ha = [];
                if (!empty($id_child)) {
                    $child_dataHa = $dh->getNodeInfo($id_child);
                    // Vengono mostrati nella mappa solo i nodi dei tipi che sono nel seguente array
                    $nodeTypesToShow = [
                        ADA_LEAF_TYPE, ADA_GROUP_TYPE, ADA_LEAF_WORD_TYPE,
                        ADA_GROUP_WORD_TYPE, ADA_PERSONAL_EXERCISE_TYPE, ADA_STANDARD_EXERCISE_TYPE,
                    ];
                    if (in_array(strval($child_dataHa['type'])[0], $nodeTypesToShow)) {
                        //Utilities::mydebug(__LINE__,__FILE__,$child_dataHa);
                        $linksAr = [];

                        $links_result = $dh->getNodeLinks($id_child); // Array
                        if (!is_object($links_result)) {
                            $linksAr = $links_result;

                            // filtro link
                            $ok_link = $this->getFilterLinksFN($linksAr, $user_level);
                            // fine filtro link
                            if (!empty($ok_link)) {
                                foreach ($ok_link as $link) {
                                    $id_node_to = $link['id_node_to'];
                                    $meaning_link = $link['meaning_link'];
                                    $action_link = $link['action_link'];
                                    array_push($linked_ha, ['id_node_to' => $id_node_to, 'meaning_link' => $meaning_link, 'action_link' => $action_link]);
                                }
                            }
                        }

                        $children_count = 0;
                        $children_count_res = $dh->getNodeChildren($id_child);
                        if (!AMADB::isError($children_count_res) && is_array($children_count_res)) {
                            $children_count = count($children_count_res);
                        }

                        if (file_exists($course_icons_media_path . $child_dataHa['icon'])) {
                            $icon_child = $course_icons_media_path . $child_dataHa['icon'];
                        } else {
                            $icon_child = $child_dataHa['icon'];
                        }
                        //Utilities::mydebug(__LINE__,__FILE__,$course_icons_media_path.$child_dataHa['icon']);

                        $array_child = [
                            'id_child' => $id_child,
                            'name_child' => $child_dataHa['name'],
                            'position_child' => $child_dataHa['position'],
                            'type_child' => $child_dataHa['type'],
                            'icon_child' => $icon_child,
                            'level_child' => $child_dataHa['level'],
                            'bgcolor_child' => $child_dataHa['bgcolor'],
                            'color_child' => $child_dataHa['color'],
                            'linked' => $linked_ha,
                            'children_count' => $children_count,
                        ];
                        array_push($children_ha, $array_child);
                        //Utilities::mydebug(__LINE__,__FILE__,$array_child);
                    }
                }
            }
            return $children_ha;
        } else {
            return  $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }

    public function indexFN($id_toc = '', $depth = 1, $user_level = 1, $user_history = '', $user_type = '3')
    {
        // class function
        // filtering nodes by level
        // notes are shown only if created by users of this course
        // AND of this instance

        $dh                      = $GLOBALS['dh'] ?? null;
        $error                   = $GLOBALS['error'] ?? null;
        $sess_id_course          = $_SESSION['sess_id_course'] ?? null;
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user            = $_SESSION['sess_id_user'] ?? null;

        $visit_count = 0;
        if (empty($id_toc)) {
            $id_toc = $this->id;
        }

        //    $base = new Node($id_toc,1);  // da dove parte
        $children = $dh->getNodeChildrenComplete($id_toc);
        //    $children = $base->children;

        if (!empty($children) && (!AMADB::isError($children))) {
            $dataAr = [];
            //           $parent_link = array("<img name=gruppo alt=\"Questo gruppo\" src=\"img/_gruppo.png\"> <a href=view.php?id_node=".$base->id.">".$base->name."</a>",
            //           $alt = translateFN("Questo gruppo");
            //           $parent_link = array("<img name=gruppo alt=\"$alt\" src=\"img/_gruppo.png\">".$base->name);
            $parent_link = [];
            for ($k = 1; $k <= $depth; $k++) {
                array_push($parent_link, "&nbsp;");
            }
            array_push($dataAr, $parent_link);

            foreach ($children as $child_dataHa) {
                if (!empty($child_dataHa)) {
                    $id_child = $child_dataHa['id_nodo'];
                    $ok = false;
                    //          $child_dataHa = $dh->getNodeInfo($id_child);
                    $node_type = $child_dataHa['tipo'];
                    $child_dataHa['name'] = $child_dataHa['nome'];
                    $child_dataHa['level'] = $child_dataHa['livello'];
                    $child_dataHa['type'] = $child_dataHa['tipo'];
                    $node_type_family = strval($node_type)[0];
                    switch ($node_type_family) {
                        case ADA_LEAF_TYPE:
                        case ADA_LEAF_WORD_TYPE:
                            $base_type ??= null;
                            if (($node_type_family == ADA_LEAF_WORD_TYPE and $base_type == ADA_GROUP_TYPE) || ($node_type_family == ADA_LEAF_TYPE and $base_type == ADA_GROUP_WORD_TYPE)) {
                                break;
                            }
                            if ($child_dataHa['level'] <= $user_level) {
                                // vito 12 gennaio 2009
                                //  $alt = translateFN("Nodo inferiore");
                                //  $icon = "_nodo.png";
                                switch ($user_type) {
                                    case AMA_TYPE_STUDENT:
                                    default:
                                        $visit_count  = ADALoggableUser::isVisitedByUserFN($id_child, $sess_id_course_instance, $sess_id_user);
                                        break;
                                    case AMA_TYPE_TUTOR:
                                        // TOO SLOW !
                                        $visit_count  = ADALoggableUser::isVisitedByClassFN($id_child, $sess_id_course_instance, $sess_id_course);
                                        break;
                                    case AMA_TYPE_AUTHOR:
                                        $visit_count  = ADALoggableUser::isVisitedFN($id_child);
                                }
                                if ($visit_count <= 0) {
                                    // vito 12 gennaio 2009
                                    //  $children_link = array("<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> <a class='node_not_visited' href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>");
                                    $anchor_class = 'node_not_visited';
                                } else {
                                    // vito 12 gennaio 2009
                                    //  $children_link = array("<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a> ($visit_count)");
                                    $anchor_class = '';
                                }
                                $css_classname = 'ADA_LEAF_TYPE';
                                if ($node_type_family == ADA_LEAF_WORD_TYPE) {
                                    $css_classname = 'ADA_LEAF_WORD_TYPE';
                                }
                                $children_link = ['<div class="' . $css_classname . '"><a class="' . $anchor_class . '" href="view.php?id_node=' . $id_child . '">' . $child_dataHa['name'] . '</a></div>'];
                            } else {
                                // vito 12 gennaio 2009
                                //  $alt = translateFN("Nodo non visitabile");
                                //  $icon = "_nododis.png"; // _nododis.png
                                //  $children_link = array("<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> ".$child_dataHa['name']);
                                $css_classname = 'ADA_LEAF_TYPE NODE_NOT_VIEWABLE';
                                $children_link = ['<div class="' . $css_classname . '">' . $child_dataHa['name'] . '</div>'];
                            }

                            break;
                        case ADA_GROUP_TYPE:
                            if ($child_dataHa['level'] <= $user_level) {
                                //vito 12 gennaio 2009
                                //$alt = translateFN("Approfondimenti");
                                //$icon = "_gruppo.png";
                                switch ($user_type) {
                                    case AMA_TYPE_STUDENT:
                                    default:
                                        $visit_count  = ADALoggableUser::isVisitedByUserFN($id_child, $sess_id_course_instance, $sess_id_user);
                                        break;
                                    case AMA_TYPE_TUTOR:
                                        // TOO SLOW !
                                        //      $visit_count  = ADALoggableUser::isVisitedByClassFN($id_child,$sess_id_course_instance,$sess_id_course);
                                        //
                                        break;
                                    case AMA_TYPE_AUTHOR:
                                        $visit_count  = ADALoggableUser::isVisitedFN($id_child);
                                }
                                if ($visit_count == 0) {
                                    // vito 12 gennaio 2009
                                    //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> <a class='node_not_visited' href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>");
                                    $anchor_class = 'node_not_visited';
                                } else {
                                    // vito 12 gennaio 2009
                                    //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a> ($visit_count)");
                                    $anchor_class = '';
                                }
                                $css_classname = 'ADA_GROUP_TYPE';
                                $children_link = ['<div class="' . $css_classname . '"><a class="' . $anchor_class . '" href="view.php?id_node=' . $id_child . '">' . $child_dataHa['name'] . '</a></div>'];
                            } else {
                                //vito 12 gennaio 2009
                                //$alt = translateFN("Approfondimenti non visitabili");
                                //$icon = "_gruppodis.png"; // _gruppodis.png
                                //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> ".$child_dataHa['name']);
                                $css_classname = 'ADA_GROUP_TYPE NODE_NOT_VIEWABLE';
                                $children_link = ['<div class="' . $css_classname . '">' . $child_dataHa['name'] . '</div>'];
                            }
                            break;
                        case ADA_NOTE_TYPE:    // node added by users
                        case ADA_PRIVATE_NOTE_TYPE:    // node added by users
                            /*
                             $autore = $child_dataHa['author'];

                             switch (VIEW_PRIVATE_NOTES_ONLY){
                             case 0: // every node added by student of this course are visible
                             $author_dataHa =  $dh->getSubscription($autore, $sess_id_course_instance);
                             if (!AMADB::isError($author_dataHa)){
                             $alt = translateFN("Nota pubblica");
                             $icon = "_nota.png";
                             $children_link = array("<img name=\"nota\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>");
                             }
                             break;
                             case 1: // only nodes added by this user are visible
                             if ($autore==$sess_id_user){
                             $alt = translateFN("Nota privata");
                             $icon = "_nota.png";
                             $children_link = array("<img name=\"nota\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>");
                             }
                             default:
                             $ok = false;
                             $alt = translateFN("Nota non visitabile");
                             $icon = "_unknown.png";
                             $children_link = array("<img name=\"nota\" alt=\"$alt\" src=\"img/$icon\"> ".$child_dataHa['name']);
                             }
                             */
                            unset($children_link);
                            break;
                        case ADA_STANDARD_EXERCISE_TYPE: // exercise
                        case 4: // exercise...
                        case 5: // exercise...
                        case 6: // exercise...
                            unset($children_link);
                            break;
                        default:
                            if ($node_type == ADA_GROUP_WORD_TYPE) {
                                if ($child_dataHa['level'] <= $user_level) {
                                    //vito 12 gennaio 2009
                                    //$alt = translateFN("Approfondimenti");
                                    //$icon = "_gruppo.png";
                                    switch ($user_type) {
                                        case AMA_TYPE_STUDENT:
                                        default:
                                            $visit_count  = ADALoggableUser::isVisitedByUserFN($id_child, $sess_id_course_instance, $sess_id_user);
                                            break;
                                        case AMA_TYPE_TUTOR:
                                            // TOO SLOW !
                                            //      $visit_count  = ADALoggableUser::isVisitedByClassFN($id_child,$sess_id_course_instance,$sess_id_course);
                                            //
                                            break;
                                        case AMA_TYPE_AUTHOR:
                                            $visit_count  = ADALoggableUser::isVisitedFN($id_child);
                                    }
                                    if ($visit_count == 0) {
                                        // vito 12 gennaio 2009
                                        //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> <a class='node_not_visited' href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a>");
                                        $anchor_class = 'node_not_visited';
                                    } else {
                                        // vito 12 gennaio 2009
                                        //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=".$id_child.">".$child_dataHa['name']."</a> ($visit_count)");
                                        $anchor_class = '';
                                    }
                                    $css_classname = 'ADA_GROUP_WORD_TYPE';
                                    $children_link = ['<div class="' . $css_classname . '"><a class="' . $anchor_class . '" href="view.php?id_node=' . $id_child . '">' . $child_dataHa['name'] . '</a></div>'];
                                } else {
                                    //vito 12 gennaio 2009
                                    //$alt = translateFN("Approfondimenti non visitabili");
                                    //$icon = "_gruppodis.png"; // _gruppodis.png
                                    //$children_link = array("<img name=\"gruppo\" alt=\"$alt\" src=\"img/$icon\"> ".$child_dataHa['name']);
                                    $css_classname = 'ADA_GROUP_WORD_TYPE NODE_NOT_VIEWABLE';
                                    $children_link = ['<div class="' . $css_classname . '">' . $child_dataHa['name'] . '</div>'];
                                }
                            } else {
                                //vito 12 gennaio 2009
                                //$alt = translateFN("Nodo non visitabile");
                                //$icon = "_unknown.png";
                                //$children_link = array("<img name=\"sconosciuto\" alt=\"$alt\" src=\"img/$icon\"> ".$child_dataHa['name']);
                                //$css_classname = 'NODE_NOT_VIEWABLE';
                                //$children_link = array('<div class="'.$css_classname.'">'.$child_dataHa['name'].'</div>');
                                unset($children_link);
                            }
                    }
                    if (isset($children_link)) {
                        for ($k = 1; $k <= $depth; $k++) {
                            array_unshift($children_link, "&nbsp;");
                        }
                        array_push($dataAr, $children_link);
                    }
                }
            }

            $dataAr = $this->removeEmptyElements($dataAr);

            $t = new Table();
            $t->initTable('0', 'center', '0', '0', '100%', '', '', '', '', '0', '0');
            $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice dei nodi inferiori"));
            $t->getTable();

            return $t->data;
        } else {
            return  $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml() . "<p>";
        }
    }

    // Inizio funzioni wrapper per Main Index
    // le funzioni vere appartengono alla classe Course
    /* RIASSUNTO:
     main_indexFN: mostra nodi e gruppi, per studente (no autore, tutor e admin)
     explodeNodesFN : ricorsiva, chiamata per default e se $order=struct
     explodeNodesIterativeFN : iterativa, chiamata se $order=alfa

     se hide_visits=1 mostrano anche le visite dello studente

     class_indexFN: mostra nodi e gruppi,per tutor e autore  (no studente e admin)
     classExplodeNodesFN : ricorsiva, chiamata per default e se $order=struct
     classExplodeNodesIterativeFN : iterativa, chiamata se $order=alfa

     se hide_visits=1 mostrano anche le visite della classe (tutor) o di tutti (autore)

     forum_main_indexFN: mostra  solo note, per studente, tutor  (no admin e autore)
     forumExplodeNodesFN : ricorsiva, chiamata se $order=struct
     forumExplodeNodesIterativeFN : iterativa, chiamata per default e se $order=chrono

     *se hide_visits=1 mostrano anche le visite della classe (tutor)
     */

    public function mainIndexFN($id_toc = '', $depth = 1, $user_level = 1, $user_history = '', $user_type = AMA_TYPE_STUDENT, $order = 'struct', $expand = 0)
    {
        $sess_id_course = $GLOBALS['sess_id_course'];
        //  this version is intended for  studentes use only
        $CourseObj = new Course($sess_id_course);
        $index = $CourseObj->mainIndexFN($id_toc, $depth, $user_level, $user_history, $user_type, $order, $expand);
        return $index;
    }

    public function classMainIndexFN($id_toc = '', $depth = 1, $id_profile = AMA_TYPE_STUDENT, $order = 'struct', $expand = 1)
    {
        //  this version is intended for  tutor  or author use
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $CourseInstanceObj = new CourseInstance($sess_id_course_instance);
        $index = $CourseInstanceObj->classMainIndexFN($id_toc, $depth, $id_profile, $order, $expand);
        return $index;
    }

    public function forumMainIndexFN($id_toc = '', $depth = 1, $id_profile = AMA_TYPE_STUDENT, $order = 'chrono', $id_student = -1, $mode = 'standard')
    {
        //  this version is intended for  tutor  and studente use
        // only notes are showed
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $CourseInstanceObj = new CourseInstance($sess_id_course_instance);
        $index = $CourseInstanceObj->forumMainIndexFN($id_toc, $depth, $id_profile, $order, $id_student, $mode);
        return $index;
    }

    public function getAllChildrenFN($depth, $user_level, $id_parent, $dataAr, $id_profile)
    {
        // recursive
        //global $dh,$id_course,$sess_id_course_instance,$sess_id_course;
        $dh =   $GLOBALS['dh'];
        $error =   $GLOBALS['error'];
        $sess_id_course =   $_SESSION['sess_id_course'];
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'];
        $sess_id_user =   $_SESSION['sess_id_user'];

        $depth++;
        $childrenAr = $dh->getNodeChildren($id_parent);
        if (!is_object($childrenAr)) { // it is an Error
            $childnumber = 0;
            foreach ($childrenAr as $id_child) {
                if (!empty($id_child)) {
                    $childnumber++;
                    $child_dataHa = $dh->getNodeInfo($id_child);
                    $node_type = $child_dataHa['type'];
                    $node_type_family = $node_type[0]; // first char

                    $ok = false;
                    switch ($node_type_family) {
                        case ADA_GROUP_TYPE:
                            if ($child_dataHa['level'] <= $user_level) {
                                $alt = translateFN("Nodo inferiore");
                                $icon = "_nodo.png";
                                $ok = true;
                            } else {
                                $alt = translateFN("Nodo non visitabile");
                                $icon = "_nododis.png"; // _nododis.png
                                $ok = false;
                            }

                            break;
                        case ADA_LEAF_TYPE:
                            if ($child_dataHa['level'] <= $user_level) {
                                $alt = translateFN("Gruppo inferiore");
                                $icon = "_gruppo.png";
                                $ok = true;
                            } else {
                                $alt = translateFN("Gruppo non visitabile");
                                $icon = "_gruppodis.png"; // _gruppodis.png
                                $ok = false;
                            }
                            break;
                        case ADA_PRIVATE_NOTE_TYPE:    // node added by users
                            // notes doesn't have levels !
                            $autore = $child_dataHa['author'];
                            //$author_dataHa =  $dh->getSubscription($autore, $sess_id_course_instance);
                            if ($autore == $sess_id_user) {
                                $alt = translateFN("Nota privata");
                                $icon = "__nota_pers.png";
                                $ok = true;
                            }
                            break;

                        case ADA_NOTE_TYPE:    // node added by users
                            // notes doesn't have levels !
                            $autore = $child_dataHa['author'];
                            $author_dataHa =  $dh->getSubscription($autore, $sess_id_course_instance);
                            if (!AMADB::isError($author_dataHa)) {
                                $alt = translateFN("Nota pubblica");
                                $icon = "_nota.png";
                                $ok = true;
                            }
                            break;
                        default:
                            $icon = "_nodo.png";
                            $ok = true;
                    }
                    if ($ok) {
                        switch ($id_profile) {
                            case AMA_TYPE_STUDENT:
                            default:
                                $visit_count  = ADALoggableUser::isVisitedByUserFN($id_child, $sess_id_course_instance, $sess_id_user);
                                break;
                            case AMA_TYPE_TUTOR:
                                $visit_count  = ADALoggableUser::isVisitedByClassFN($id_child, $sess_id_course_instance, $sess_id_course);
                                break;
                            case AMA_TYPE_AUTHOR:
                                $visit_count  = ADALoggableUser::isVisitedFN($id_child);
                        }


                        $dataAr[$depth][$childnumber] = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\"> <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>";
                    } else {
                        $dataAr[$depth][$childnumber] = "<img name=\"nodo\" alt=\"$alt\" src=\"img/$icon\">" . $child_dataHa['name'];
                    }
                    $dataAr[$depth][$childnumber] = "<img name=gruppo alt=\"Nodo inferiore\" src=\"img/$icon\"> <a href=view.php?id_node=" . $id_child . ">" . $child_dataHa['name'] . "</a>";
                    Node::getAllChildrenFN($depth, $user_level, $id_child, $dataAr, $id_profile);
                }
            }
        } else {
            return false;
        }
    }

    public function findPathFN($target = "")
    {
        $pathAr =  $this->findLogicalPathFN();
        $path = $this->logicalPath2HtmlFN($pathAr, $target);
        return $path;
    }

    public function findLogicalPathFN()
    {
        // NON ricorsivamente tenta di risalire al nodo id X_0
        // ritorna un array bidimensionale di coppie id_node/name
        $dh =   $GLOBALS['dh'];
        $error =   $GLOBALS['error'];
        $self =   $GLOBALS['self'];
        $sess_id_course =   $_SESSION['sess_id_course'];

        /*
         $courseObj = new Course($sess_id_course);
         $id_toc =  $sess_id_course."_".$courseObj->id_nodo_toc;
         */
        $pathAr = [];
        $course_Ha = $dh->getCourse($sess_id_course);
        if ((is_array($course_Ha))) {
            $id_toc = $sess_id_course . "_" . $course_Ha['id_nodo_toc'];
            // $id_toc = $sess_id_course."_0";
            $id_node =  $this->id;
            $name = $this->name;
            $pathAr[] = [$id_node, $name]; //""
            // vito, 12 giugno 2009
            //         if ($id_node==$id_toc){   // are we at main group level?
            //           // vito, 12 giugno 2009
            //           //  $pathAr[] = "";
            //         }
            //         else {
            if ($id_node != $id_toc) {
                //$name = $this->name;
                $id_parent = $this->parent_id;

                while ($id_node != $id_toc  && (!empty($id_node)) && ($id_node != "NULL")) {
                    // $debug=1;
                    //Utilities::mydebug(__LINE__,__FILE__,array('node'=>$id_node,'parent'=>$id_parent));
                    //$debug=0;
                    $dataHa = $dh->getNodeInfo($id_parent);

                    if ((!AMADataHandler::isError($dataHa)) && (is_array($dataHa))) {
                        $name = $dataHa['name'];
                        $id_node = $dataHa['parent_id'];
                        if ($id_parent != $id_toc) {
                            //$node_parentObj = new Node($id_node);
                            //$parent_type = $node_parentObj->type;
                            $node_parent_dataHa =  $dh->getNodeInfo($id_node);
                            if (!is_object($node_parent_dataHa)) {
                                $parent_type = $node_parent_dataHa['type'];
                                //if ($parent_type ==ADA_GROUP_TYPE) {
                                $pathAr[] = [$id_parent, $name];
                                //Utilities::mydebug(__LINE__,__FILE__,array('name'=>$name,'parent'=>$id_node));
                            } else {
                            }
                            $id_parent = $id_node;
                        }
                    } else {
                        $id_node = "";
                    }
                }


                // we are at the first level
                $dataHa = $dh->getNodeInfo($id_toc);
                if (is_array($dataHa)) {
                    $tocname = $dataHa['name'];
                    $pathAr[] = [$id_toc, $tocname];
                }
            }
        }
        return $pathAr;
    }

    public function logicalPath2HtmlFN($pathAr, $target)
    {
        // converts array to html code
        // used by findPathFN
        if (!$target) {
            $target = HTTP_ROOT_DIR . "/browsing/view"; //else: map
        }
        $path = "";
        $n = 0;
        $path_len = count($pathAr);

        foreach ($pathAr as $path_element) {
            $n++;
            if ($n == 1) {
                $path = "<a class='selected' >" . $path_element[1] . "</a>";
            } else {
                if ($n < $path_len) {
                    $path = "<a  href=$target.php?id_node=" . $path_element[0] . ">" . $path_element[1] . "</a> > " . $path;
                } else {
                    $path = "<a href=$target.php?id_node=" . $path_element[0] . ">" . $path_element[1] . "</a> > " . $path;
                }
            }
        }
        //$path = substr($path,0,strlen($path)-3); to eliminate A tag closure
        return $path;
    }

    public function getFilterLinksFN($linksAR, $user_level = 1)
    {
        // filtro sui link:
        // verifica se i nodi linkati hanno livello<= a quello dell'utente
        // torna un array associativo
        $ok_link = [];
        $data_Ar = [];
        if (!empty($linksAR)) {
            $linkAr = $linksAR;
            foreach ($linkAr as $id_link) {
                $linkObj = new Link($id_link);
                $id_linked_node = $linkObj->to_node_id;
                $link_meaning = $linkObj->meaning;
                $link_action = $linkObj->action;
                $link_style = $linkObj->style;
                $link_type = $linkObj->type;

                // $node = $sess_id_course."_".$id_linked_node;
                $node = $id_linked_node;
                $tempNodeObj = new Node($node, 0);
                $linked_node_name = $tempNodeObj->name;
                $linked_node_level = $tempNodeObj->level;
                if (($linked_node_level <= $user_level) && (!empty($id_linked_node))) {
                    $ok_link = ['id_link' => $id_link, 'id_node_to' => $id_linked_node, 'meaning_link' => $link_meaning, 'type_link' => $link_type, 'action_link' => $link_action];
                    //Utilities::mydebug(__LINE__,__FILE__,$ok_link);
                    array_push($data_Ar, $ok_link);
                    // Utilities::mydebug(__LINE__,__FILE__,$data_Ar);
                }
            }
            return $data_Ar;
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }

    public function filterNodeFN($user_level, $user_history, $id_profile = '3', $querystring = '')
    {
        /*
         restituisce i dati visibili per l'utente in un array associativo

         filtri attivi:
         - media (in base al browser, dovrebbe farlo in base al profilo ,$id_profile)
         - links (in base allo userlevel)
         -

         */



        if (!isset($id_profile)) {
            $id_profile = AMA_TYPE_STUDENT;
        }

        if ($this->type == ADA_LEAF_TYPE || $this->type == ADA_GROUP_TYPE || $this->type == ADA_NOTE_TYPE || $this->type == ADA_PRIVATE_NOTE_TYPE) {
            if (SEARCH_WORD_IN_NODE) {
                $this->text = $this->searchTextInGlosary($this->text);
            }
        }

        $htmldataHa['text'] = $this->getTextFN($user_level, $querystring);
        $htmldataHa['media'] = $this->getMediaFN($user_level);
        $htmldataHa['user_media'] = $this->getUserMediaFN($user_level);
        $htmldataHa['link'] = $this->getLinksFN($user_level, $id_profile);
        $htmldataHa['exercises'] = $this->getExercisesFN($user_level);
        $htmldataHa['notes'] = $this->getNotesFN($user_level, $id_profile);
        $htmldataHa['private_notes'] = $this->getPrivateNotesFN($user_level, $id_profile);
        $htmldataHa['extended_node'] = '';
        if (SHOW_NODE_EXTENDED_FIELDS) {
            $htmldataHa['extended_node'] = $this->getExtendedNodeFN($user_level, $id_profile);
        }
        /*
          if ($this->type == ADA_LEAF_TYPE || $this->type == ADA_GROUP_TYPE || $this->type == ADA_NOTE_TYPE || $this->type == ADA_PRIVATE_NOTE_TYPE) {
              if (SEARCH_WORD_IN_NODE)
              $htmldataHa['text'] = $this->search_text_in_glosary($htmldataHa['text']);
          }
         *
         */

        return $htmldataHa;
    }

    public function searchTextInGlosary($text)
    {
        $dh = $GLOBALS['dh'];
        $id_node_text = $this->id;
        $id_course = strstr($id_node_text, '_', true) . "_";
        //    preg_match_all('/\b\w+\b/',$text,$textAR);
        //    preg_match_all($pattern, $text, $textAR);
        //    $textAR = explode(" ",$text);
        $textAR = preg_split('# |&nbsp;|<p>|</p>#', $text);

        $leaf_word = ADA_LEAF_WORD_TYPE;
        $group_word = ADA_GROUP_WORD_TYPE;
        for ($i = 0; $i < count($textAR); $i++) {
            //    foreach ($textAR as $word) {
            $word = strip_tags(trim($textAR[$i]));
            //        var_dump($word);

            if (strlen($word) > 2) {
                $out_fields_ar = ['nome'];
                $clause = "nome = '$word' AND (tipo = $leaf_word OR  tipo = $group_word)";
                $clause .= " AND id_nodo LIKE '%$id_course%'";

                $wordsAR = $dh->doFindNodesList($out_fields_ar, $clause);

                if (!AMADB::isError($wordsAR) && $wordsAR != "" && count($wordsAR) > 0) {
                    $id_node_word = $wordsAR[0][0];
                    $href = HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $id_node_word;
                    $text_link = $word;
                    $link_node = BaseHtmlLib::link($href, $text_link);
                    $link_to_word = $link_node->getHtml();
                    $text = str_replace($word, $link_to_word, $text);
                    $textAR[$i] = $link_to_word;
                }
            }
        }
        //        $text = implode(" ",$textAR);
        return $text;
    }

    /*
     * vito, 6 ottobre 2008: nuova versione di get_textFN
     * con l'integrazione della classe MediaViewer per la visualizzazione dei
     * contenuti del nodo
     */
    public function getTextFN($user_level, $querystring)
    {
        return self::parseInternalLinkMedia($this->text, $this->level, $user_level, $querystring);
    }

    /**
     * static method that search and replace media tag found in text
     *
     * @access public
     *
     * @param $text text (string)
     * @param $node_level if specified, compare the node level with student level
     * @param $student_level if specified, use this value instead of the one stored in session
     * @param $querystring
     * @param $media_path if boolean true, use the global variable 'media_path', else defines a new media_path
     *
     * @return string
     */
    public static function parseInternalLinkMedia($text, $node_level = null, $student_level = null, $querystring = null, $media_path = true)
    {
        // filtro sul testo
        // verifica se il nodo ha livello<= a quello dell'utente
        // sostituisce i link e i media  di tipo img  se IMG_VIEWING_MODE=2


        //global $dh,$media_path,$root_dir,$http_root_dir;
        //global $sess_id_course;
        $dh = $GLOBALS['dh'];
        if ($media_path === true) {
            $media_path = $GLOBALS['media_path'];
            $media_path_global = true;
        } else {
            $media_path_global = false;
        }
        $root_dir = ROOT_DIR;
        $http_root_dir = HTTP_ROOT_DIR;
        $sess_id_course = $_SESSION['sess_id_course'];

        /**
         * if node_level <0 we've been called from a test node and no level
         * check is needed here, but must be done by the test itself
         */
        $level_filter   = ($node_level >= 0);
        $link_filter    = 1;
        $extlink_filter = 1;
        $media_filter   = 1;
        $document_filter = 1;
        $query_filter   = 1;

        if (is_null($student_level)) {
            $student_level = $_SESSION['sess_userObj']->livello;
        }

        if ($level_filter) {
            if (
                isset($_SESSION['sess_id_user_type']) &&
                $_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT && $node_level > $student_level
            ) {
                return translateFN('Il contenuto di questo nodo non &egrave; accessibile ad utenti di livello ') . $student_level;
            }
        }

        //media
        /* type:
        img= 0
        audio = 1
        video = 2
        linkext = 3
        */
        $VIEWINGPREFERENCES = [
            _IMAGE => IMG_VIEWING_MODE,
            _SOUND => AUDIO_PLAYING_MODE,
            _VIDEO => VIDEO_PLAYING_MODE,
            INTERNAL_LINK => 0,
            _DOC => DOC_VIEWING_MODE,
            _LINK => 0,
            _PRONOUNCE => AUDIO_PLAYING_MODE,
            _FINGER_SPELLING => VIDEO_PLAYING_MODE,
            _LABIALE => VIDEO_PLAYING_MODE,
            _LIS => VIDEO_PLAYING_MODE,
            _MONTESSORI => IMG_VIEWING_MODE,
        ];
        // vito, 27 mar 2009, added id course to user data.
        $user_data = ['level' => $student_level, 'id_course' => $sess_id_course];

        $media_type    = '';
        $media_value   = '';
        $or            = '';

        /*
         * If media filter is enabled, set media type and value to search for
         */
        if ($media_filter) {
            if (!$media_path_global) {
                $file_path      = $root_dir . $media_path;
                $http_file_path = $media_path;
            } elseif (MEDIA_LOCAL_PATH) {
                $file_path      = MEDIA_LOCAL_PATH . $media_path;
                $http_file_path = MEDIA_LOCAL_PATH . $media_path;
            } else {
                $file_path      = $root_dir . $media_path;
                $http_file_path = $http_root_dir . $media_path;
            }

            $media_type  .= _IMAGE . '|' . _SOUND . '|' . _VIDEO . '|' . _PRONOUNCE . '|' . _MONTESSORI . '|' . _LABIALE . '|' . _LIS . '|' . _FINGER_SPELLING; //'0|1|2|4|....';
            $media_value .= '(?:[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]{3,4})';
            $or = '|';
        }

        /*
         * If external link filter is enabled, set external link type and value to search for
         */
        if ($extlink_filter) {
            $media_type  .= $or . _LINK;
            //$media_value .= $or.'(?:[a-z0-9\-\/\.:]+)';
            //$media_value .= $or.'(?:[a-zA-Z0-9_\-\/\.:]+)';
            $url_pattern = '(?:[a-zA-Z0-9_\-\/\.?~+%=&,$\'\(\):;*@\[\]]+)';
            $media_value .= $or . $url_pattern;

            $or = '|';
        }

        if ($document_filter) {
            $media_type  .= $or . _DOC;

            if (!$media_filter) {
                $media_value .= $or . '(?:[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]{3,4})';
            }
        }
        /*
         * Create a mediaviewer
         */
        $media_title = '';
        $mediaviewer = new MediaViewer($http_file_path, $user_data, $VIEWINGPREFERENCES, $media_title);

        /*
         * If at least one among media filter, external link filter, document filter is enabled, search and
         * replace corresponding media tags
         */

        if ($media_filter || $extlink_filter || $document_filter) {
            // Create an instance of MediaViewer class, used to get the appropriate viewer for the selected media
            $text = self::extractMediaTags($media_type, $media_value, $text, $mediaviewer, 'getViewer');
        }

        /*
         * If internal link filter is enabled
         */
        if ($link_filter) {
            $text = self::extractLinkTags($text, $mediaviewer, 'displayLink');
        }

        /*
         * Graffio 24 aprile 2012
                 * @todo:
                 * trova tutto ciò che non è html
                 * (?<=^|>)[^><]+?(?=<|$)
                 *
         */
        //      if ($query_filter == 1 && isset($querystring) && !empty($querystring)) {
        if ($query_filter == 5555 && isset($querystring) && !empty($querystring)) {
            $replacement_string = [];
            $search_string_regexp = [];
            $wordsAr = explode(' ', $querystring);
            foreach ($wordsAr as $word) {
                $replacement_string[] = '<span class="querystring">' . $word . '</span>';
                $search_string_regexp[] = '/' . $word . '/i';
            }
            $text = preg_replace($search_string_regexp, $replacement_string, $text);

            /*
             * vito 26 gennaio 2009
             */
            /*
            $search_string_ar = array();
            $regexp = '/([a-z]+)/i';
            if (preg_match($regexp, $querystring, $search_string_ar)) {
              $search_string        = $search_string_ar[1];
              $search_string_regexp = '/'.$search_string.'/i';
              $replacement_string   = '<span class="querystring">'.$search_string.'</span>';
              $filtered_text = preg_replace($search_string_regexp,$replacement_string, $filtered_text);
            }
            */
        }

        // CHECK iframe src
        $iframe_src_regex = "<"; // 1 start of the tag
        $iframe_src_regex .= "\\s*"; // 2 zero or more whitespace
        $iframe_src_regex .= "iframe"; // 3 the 'iframe' of the tag itself
        $iframe_src_regex .= "\\s+"; // 4 one or more whitespace
        $iframe_src_regex .= "[^>]*"; // 5 zero or more of any character that is _not_ the end of the tag
        $iframe_src_regex .= "src"; // 6 the src bit of the tag
        $iframe_src_regex .= "\\s*"; // 7 zero or more whitespace
        $iframe_src_regex .= "="; // 8 the = of the tag
        $iframe_src_regex .= "\\s*"; // 9 zero or more whitespace
        $iframe_src_regex .= "[\\\"']?"; // 10 none or one of " or '
        $iframe_src_regex .= "("; // 11 opening parenthesis, start of the bit we want to capture
        $iframe_src_regex .= "[^\\\"' >]+"; // 12 one or more of any character _except_ our closing characters
        $iframe_src_regex .= ")"; // 13 closing parenthesis, end of the bit we want to capture
        $iframe_src_regex .= "[\\\"' >]"; // 14 closing chartacters of the bit we want to capture

        // Pattern Modifier - i: makes regex case insensative
        // Pattern Modifier - s: makes a dot metacharater in the pattern match all characters, including newlines
        // Pattern Modifier - U: makes the regex ungready
        preg_match_all("/" . $iframe_src_regex . "/isU", $text, $matches);

        if (isset($matches[1]) && count($matches[1]) > 0) {
            $unique = array_unique($matches[1]);
            $nwURL = HTTP_ROOT_DIR . '/adaProxy.php?q=';
            $urlhashes = [];
            foreach ($unique as $i => $url) {
                if (stripos($url, 'vimeo.com') === false && stripos($url, 'youtube.com') === false && !str_starts_with($url, HTTP_ROOT_DIR)) {
                    $enc = openssl_encrypt($url, 'BF-ECB', ADAPROXY_ENC_KEY);
                    if (false === $enc) {
                        $enc = $url;
                    }
                    $urlhashes[$i] = $nwURL . urlencode($enc);
                } else {
                    $urlhashes[$i] = $url;
                }
            }
            $text = str_replace($unique, $urlhashes, $text);
        }

        return $text;
    }

    public static function extractMediaTags($media_type, $media_value, $text, $instance, $function)
    {
        $matches = self::extractLinkMediaTags($text);
        $searches = [];
        $replaces = [];
        if (!empty($matches)) {
            foreach ($matches as $k => $v) {
                if (strtoupper($v['tag']) == 'MEDIA' && preg_match('/' . $media_type . '/', $v['type']) && preg_match('/' . $media_value . '/', $v['value'])) {
                    $instance->setMediaPath($v);
                    $searches[$k] = $v['str'];
                    $replaces[$k] = $instance->{$function}($v);
                }
            }
        }
        $searches[] = '</media>';
        $replaces[] = '';
        return str_replace($searches, $replaces, $text);
    }

    public static function extractLinkTags($text, $instance, $function)
    {
        $matches = self::extractLinkMediaTags($text);
        $searches = [];
        $replaces = [];
        if (!empty($matches)) {
            foreach ($matches as $k => $v) {
                if (strtoupper($v['tag']) == 'LINK' && strtoupper($v['type']) == 'INTERNAL' && is_numeric($v['value'])) {
                    $searches[$k] = $v['str'];
                    $replaces[$k] = $instance->{$function}($v);
                }
            }
        }

        return str_replace($searches, $replaces, $text);
    }

    public static function extractLinkMediaTags($text)
    {
        $dh = $GLOBALS['dh'];
        $regexp = '/<((MEDIA|LINK)[^>]*)>/imU';

        preg_match_all($regexp, $text, $matches, PREG_SET_ORDER);

        $array = [];
        if (!empty($matches)) {
            foreach ($matches as $k => $v) {
                preg_match('/TYPE="([^"]+)"/i', $v[1], $type);
                $type = $type[1] ?? null;
                preg_match('/VALUE="([^"]+)"/i', $v[1], $value);
                $value = $value[1] ?? null;
                $add_title = preg_match('/TITLE="([^"]+)"/i', $v[1], $title);
                $title = $title[1] ?? null;
                $add_width = preg_match('/WIDTH="([^"]+)"/i', $v[1], $width);
                $width = $width[1] ?? null;
                $add_height = preg_match('/HEIGHT="([^"]+)"/i', $v[1], $height);
                $height = $height[1] ?? null;
                $id_node = $_SESSION['sess_id_node'] ?? null;
                $array[$k] = [
                    'str' => $v[0],
                    'tag' => $v[2],
                    'type' => $type,
                    'value' => $value,
                    'title' => ($add_title) ? $title : null,
                    'width' => ($add_width) ? $width : null,
                    'height' => ($add_height) ? $height : null,
                    'owner' => null,
                ];
                if (strcasecmp($type, 'INTERNAL') !== 0) {
                    $mediaInfoAr = $dh->getRisorsaEsternaInfoFromFilename($value, $id_node);
                    $array[$k]['owner'] = $mediaInfoAr['id_utente'] ?? null;
                }
            }
        }
        return $array;
    }

    /**
     * static method that search and replace media tag found in text
     * to be displayed in WYSIWYG Editor
     *
     * @access public
     *
     * @param $text text (string)
     *
     * @return string
     * @see parseInternalLinkMedia
     */
    public static function prepareInternalLinkMediaForEditor($text)
    {
        $matches = self::extractLinkMediaTags($text);
        $searches = [];
        $replaces = [];

        if (!empty($matches)) {
            foreach ($matches as $k => $v) {
                $searches[$k] = $v['str'];
                $replaces[$k] = self::callbackInternalLinkMediaForEditor($v['tag'], $v['type'], $v['value'], $v['title'], $v['width'], $v['height']);
            }
        }

        return str_replace($searches, $replaces, $text);
    }

    /**
     * static method callback called by prepareInternalLinkMediaForEditor
     *
     * @access public
     *
     * @param $params params coming from preg_replace_callback
     *
     * @return string
     * @see prepareInternalLinkMediaForEditor
     */
    public static function callbackInternalLinkMediaForEditor($tag, $type, $title, $rel = null, $width = null, $height = null)
    {
        if ($tag == 'LINK' && $type == 'INTERNAL') {
            $type = INTERNAL_LINK;
        }

        if (isset($_SESSION['sess_userObj']->template_family) && !empty($_SESSION['sess_userObj']->template_family)) {
            $template_family = $_SESSION['sess_userObj']->template_family;
        } else {
            $template_family = ADA_TEMPLATE_FAMILY;
        }

        $path = HTTP_ROOT_DIR . '/layout/' . $template_family . '/img/';

        $src = [
            _IMAGE =>           $path . '_img.png',
            _SOUND =>           $path . '_audio.png',
            _VIDEO =>           $path . '_video.png',
            _LINK =>            $path . '_linkext.png',
            _DOC =>             $path . '_doc.png',
            _EXE =>             $path . '_exe.png',
            INTERNAL_LINK =>    $path . '_linka.png',
            // POSSIBLE_TYPE =>    $path . '_linka.png',
            _MONTESSORI =>      $path . '_img_montessori.png',
            _PRONOUNCE =>       $path . '_audio_pronounce.png',
            _FINGER_SPELLING => $path . '_video_finger_spelling.png',
            _LABIALE =>         $path . '_video_labiale.png',
            _LIS =>             $path . '_video_lis.png',
        ];

        $str = '<img title="' . $title . '" type="' . $type . '" alt="ada_media" src="' . $src[$type] . '"';
        if (!is_null($rel)) {
            $str .= ' rel="' . $rel . '"';
        }
        if (!is_null($width)) {
            $str .= ' width="' . $width . '"';
        }
        if (!is_null($height)) {
            $str .= ' height="' . $height . '"';
        }
        $str .= '/>';

        return $str;
    }

    /**
     * static method that search and replace media tag found in text
     * to be stored in database
     *
     * @access public
     *
     * @param $text text (string)
     *
     * @return string
     * @see parseInternalLinkMedia
     */
    public static function prepareInternalLinkMediaForDatabase($text)
    {
        $regexp = '/<(img[^>]*alt="ada_media"[^>]*)>/mU';
        return preg_replace_callback($regexp, 'self::callbackInternalLinkMediaForDatabase', $text);
    }

    /**
     * static method callback called by prepareInternalLinkMediaForDatabase
     *
     * @access public
     *
     * @param $params params coming from preg_replace_callback
     *
     * @return string
     * @see prepareInternalLinkMediaForDatabase
     */
    public static function callbackInternalLinkMediaForDatabase($params)
    {
        preg_match('/type="([0-9]+)"/', $params[1], $type);
        $type = $type[1];
        preg_match('/title="([^"]+)"/', $params[1], $value);
        $value = $value[1];
        $add_title = preg_match('/rel="([^"]+)"/', $params[1], $title);
        $title = $title[1];
        $add_width = preg_match('/width="([^"]+)"/', $params[1], $width);
        $width = $width[1];
        $add_height = preg_match('/height="([^"]+)"/', $params[1], $height);
        $height = $height[1];

        $tag = 'MEDIA';
        if ($type == INTERNAL_LINK) {
            $tag = 'LINK';
            $type = 'INTERNAL';
        }

        $str = '<' . $tag . ' TYPE="' . $type . '" VALUE="' . $value . '"';
        if ($add_title) {
            $str .= ' TITLE="' . $title . '"';
        }
        if ($add_width) {
            $str .= ' WIDTH="' . $width . '"';
        }
        if ($add_height) {
            $str .= ' HEIGHT="' . $height . '"';
        }
        $str .= '>';

        return $str;
    }

    public function getExtendedNodeFN($user_level, $id_profile)
    {
        $dh =   $GLOBALS['dh'] ?? null;
        $error =   $GLOBALS['error'] ?? null;
        $debug =   $GLOBALS['debug'] ?? null;
        $sess_id_course =   $_SESSION['sess_id_course'] ?? null;
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user =   $_SESSION['sess_id_user'] ?? null;
        $node_level = $this->level;
        if ($node_level > $user_level && $id_profile == AMA_TYPE_STUDENT) {
            return translateFN("Il contenuto di questo nodo non &egrave; accessibile ad utenti di livello") . $user_level;
        }

        $glossary_div = CDOMElement::create('DIV');
        $glossary_div->setAttribute('id', 'glossary');
        // hyphenation
        $extended_info = "";

        if (property_exists($this, 'hyphenation')) {
            $hyphenation_label = CDOMElement::create('DIV');
            $hyphenation_label->setAttribute('class', 'label_extended');
            $hyphenation_label->addChild(new CText(translateFN('hyphenation')));
            $glossary_div->addChild($hyphenation_label);
            //$extended_info .= $hyphenation_label
            $hyphenation = CDOMElement::create('DIV');
            $hyphenation->setAttribute('class', 'content_extended');
            $hyphenation->addChild(new CText($this->hyphenation));
            $glossary_div->addChild($hyphenation);
        }

        if (property_exists($this, 'grammar')) {
            // grammar
            $grammar_label = CDOMElement::create('DIV');
            $grammar_label->setAttribute('class', 'label_extended');
            $grammar_label->addChild(new CText(translateFN('grammar')));
            $glossary_div->addChild($grammar_label);

            $grammar = CDOMElement::create('DIV');
            $grammar->setAttribute('class', 'content_extended');
            $grammar->addChild(new CText($this->grammar));
            $glossary_div->addChild($grammar);
        }

        if (property_exists($this, 'semantic')) {
            // semantic
            $semantic_label = CDOMElement::create('DIV');
            $semantic_label->setAttribute('class', 'label_extended');
            $semantic_label->addChild(new CText(translateFN('semantic')));
            $glossary_div->addChild($semantic_label);

            $semantic = CDOMElement::create('DIV');
            $semantic->setAttribute('class', 'content_extended');
            $semantic->addChild(new CText($this->semantic));
            $glossary_div->addChild($semantic);
        }

        if (property_exists($this, 'notes')) {
            // notes
            $notes_label = CDOMElement::create('DIV');
            $notes_label->setAttribute('class', 'label_extended');
            $notes_label->addChild(new CText(translateFN('notes')));
            $glossary_div->addChild($notes_label);

            $notes = CDOMElement::create('DIV');
            $notes->setAttribute('class', 'content_extended');
            $notes->addChild(new CText($this->notes));
            $glossary_div->addChild($notes);
        }

        if (property_exists($this, 'examples')) {
            // examples
            $examples_label = CDOMElement::create('DIV');
            $examples_label->setAttribute('class', 'label_extended');
            $examples_label->addChild(new CText(translateFN('examples')));
            $glossary_div->addChild($examples_label);

            $examples = CDOMElement::create('DIV');
            $examples->setAttribute('class', 'content_extended');
            $examples->addChild(new CText($this->examples));
            $glossary_div->addChild($examples);
        }

        //  $gloassary_div->getHtml();

        return $glossary_div->getHtml();
    }

    public function getLinksFN($user_level, $id_profile)
    {
        //global $dh,$error,$debug;
        //global $sess_id_course,$sess_id_course_instance,$sess_id_user;
        $dh =   $GLOBALS['dh'] ?? null;
        $error =   $GLOBALS['error'] ?? null;
        $debug =   $GLOBALS['debug'] ?? null;
        $sess_id_course =   $_SESSION['sess_id_course'] ?? null;
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user =   $_SESSION['sess_id_user'] ?? null;

        // filtro sui link:
        // verifica se i nodi linkati hanno livello<= a quello dell'utente

        if (!empty($this->links)) {
            $linkAr = $this->links;
            // Utilities::mydebug(__LINE__,__FILE__,$linkAr);
            $dataAr = [];
            foreach ($linkAr as $id_link) {
                $linkObj = new Link($id_link);
                $id_linked_node = $linkObj->to_node_id;
                $link_meaning = translatefN("Tipo:") . $linkObj->meaning;
                $node = $id_linked_node;
                $tempNodeObj = new Node($node, 0);

                if ($tempNodeObj->full == 1) {
                    $linked_node_name = $tempNodeObj->name;
                    $linked_node_level = $tempNodeObj->level;
                    if ($linked_node_level <= $user_level) {
                        switch ($id_profile) {
                            case AMA_TYPE_STUDENT:
                            default:
                                $visit_count  = ADALoggableUser::isVisitedByUserFN($node, $sess_id_course_instance, $sess_id_user);
                                break;
                            case AMA_TYPE_TUTOR:
                                $visit_count  = ADALoggableUser::isVisitedByClassFN($node, $sess_id_course_instance, $sess_id_user);
                                break;
                            case AMA_TYPE_AUTHOR:
                                $visit_count  = ADALoggableUser::isVisitedFN($node);
                        }

                        if ($visit_count <= 0) {
                            $ok_link = ["<img data-type='link' src=\"img/_linka.png\">", "&nbsp;<a data-type='link' class='node_not_visited' href=view.php?id_node=$node alt=\"$link_meaning\">$linked_node_name</a>"];
                        } else {
                            $ok_link = ["<img data-type='link' src=\"img/_linka.png\">", "&nbsp;<a data-type='link' href=view.php?id_node=$node alt=\"$link_meaning\">$linked_node_name</a> ($visit_count)"];
                        }
                    } else {
                        $ok_link = ["<img data-type='link-disabled' src=\"img/_linkdis.png\">", "&nbsp;<span data-type='link-disabled'> " . $linked_node_name . '</span>'];
                        // $ok_link = array("<img src=\"templates/default/img/_linkdis.png\">",$linked_node_name);
                    }
                } else {
                    //$ok_link =array("&nbsp;","&nbsp;");
                    $ok_link = ["<img data-type='link-disabled' src=\"img/_linkdis.png\" alt=\"$node\">", "&nbsp;<span data-type='link-disabled'> " . translateFN("nodo non trovato") . '</span>'];
                }
                array_push($dataAr, $ok_link);
            }

            $dataAr = $this->removeEmptyElements($dataAr);

            $t = new Table();
            $rules = '';
            $style = 'table_link';
            $t->initTable('0', 'center', '0', '0', '100%', '', '', '', '', '0', '0', $rules, $style);
            $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice dei nodi collegati"));
            return $t->getTable();
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }
    // fine filtro links

    public function getExercisesFN($user_level)
    {
        //global $dh,$error;
        //global $sess_id_user, $sess_id_course_instance;
        $dh =   $GLOBALS['dh'] ?? null;
        $error =   $GLOBALS['error'] ?? null;
        $debug =   $GLOBALS['debug'] ?? null;
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user =   $_SESSION['sess_id_user'] ?? null;

        // filtering exercises:
        // have the exercises been already  executed?
        // HTML EMBEDDED VERSION
        if (!empty($this->children)) {
            $exerc_Ar = $this->children;
            // Utilities::mydebug(__LINE__,__FILE__,$exerc_Ar);
            $dataAr = [];
            foreach ($exerc_Ar as $id_exerc) {
                $temp = $dh->getNodeInfo($id_exerc);
                $type = $temp['type'];
                $exercise_type_family = strval($type)[0]; // first char = family (3 multiple, 4 open manual 5 open automatic 6 cloze etc)
                /*
                switch (strlen($exercise_type)){
                case 1:
                default:
                $exercise_type_mode = 0;// single
                $exercise_type_interaction = 0; // r+feedback
                break;
                case 2:
                $exercise_type_mode = $exercise_type[1];// second char = mode (0 single, 1 sequence, 2 random)
                $exercise_type_interaction = 0; // r+feedback
                break;
                case 3:
                $exercise_type_mode = $exercise_type[1];// second char = mode (0 single, 1 sequence, 2 random)
                $exercise_type_interaction = $exercise_type[2]; // third char = interaction (0 r+feedback 1 feedback 2 blind)
                break;
                }
                */
                if (static::isNodeExercise($exercise_type_family)) {
                    //      if ($exercise_type_family >= ADA_STANDARD_EXERCISE_TYPE) {
                    // versione che legge nel DB la storia dell'esercizio
                    /*
                     $exercise = $dh->getNodeInfo($id_exerc);
                    // Utilities::mydebug(__LINE__,__FILE__,$exercObj);
                    $exerc_title = $exercise['name'];
                    $out_fields_ar = array('data_visita','ripetibile');
                    $history_exerc = $dh->findExHistoryList($out_fields_ar,$sess_id_user, $sess_id_course_instance, $id_exerc);
                    if (is_array($history_exerc)){
                      $h_exerc = array_shift($history_exerc);
                      // global $debug; $debug = 1; Utilities::mydebug(__LINE__,__FILE__,$h_exerc); $debug=0;
                      if (is_array($h_exerc))
                      $already_executed = !$h_exerc[2];
                      else
                      $already_executed = "";
                    } else {
                      $already_executed = "";
                    }
                    */
                    // versione che utilizza la classe apposita
                    $exercise   = ExerciseDAO::getExercise($id_exerc);
                    $exerc_title = $exercise->getTitle();
                    $already_executed = !$exercise->getRepeatable();

                    if (!$already_executed) {                   // not yet viewed  or repeatable
                        $alt = translateFN("Esercizio");
                        $icon = "_exer.png";
                        $ok = true;
                    } else {
                        $alt = translateFN("Esercizio gi&agrave; eseguito");
                        $icon = "_exerdis.png"; // _gruppodis.png
                        $ok = false;
                    }

                    if ($ok) {
                        $exerc_ok = ["<img name=gruppo alt=\"$alt\" src=\"img/$icon\"> <a href=exercise.php?id_node=$id_exerc>$exerc_title</a>"];
                    } else {
                        $exerc_ok = ["<img name=gruppo alt=\"$alt\" src=\"img/$icon\">$exerc_title"];
                    }

                    array_push($dataAr, $exerc_ok);
                }
            }

            $dataAr = $this->removeEmptyElements($dataAr);

            $rules = '';
            $style = 'table_link';
            $t = new Table();
            $t->initTable('0', 'center', '0', '0', '100%', '', '', '', '', '0', '0', $rules, $style);
            $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice degli esercizi collegati"));
            return $t->getTable();
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml() . "<p>";
        }
    }
    // fine filtro esercizi

    public function getNotesFN($user_level, $id_profile)
    {
        $dh =   $GLOBALS['dh'] ?? null;
        $error =   $GLOBALS['error'] ?? null;
        $debug =   $GLOBALS['debug'] ?? null;
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user =   $_SESSION['sess_id_user'] ?? null;
        $id_node_base = $this->id;

        if (!empty($this->children)) {
            //    $notesHa = $dh->getNodeChildrenComplete($id_node_base);
            $notes_Ar = $this->children;
            $dataAr = [];

            // vito 12 gennaio 2009
            $class_tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);

            foreach ($notes_Ar as $id_note) {
                //      $nodeObj = new Node($id_note,0);
                $nodeInfo = $dh->getNodeInfo($id_note);
                //      $type = $nodeObj->type;
                $type = $nodeInfo['type'];
                //      $node_instance = $nodeObj->instance;
                $node_instance = $nodeInfo['instance'];
                if ($type == ADA_NOTE_TYPE) { // notes
                    //        $name = $nodeObj->name;
                    //        $level =  $nodeObj->level;
                    $name = $nodeInfo['name'];
                    $level =  $nodeInfo['level'];

                    if ($sess_id_course_instance == $node_instance) {
                        // every node added by student or tutor of THIS course  and of THIS instance are visible
                        $is_note_visible = false;
                        /*
                         * vito 12gennaio2009
                         * Added CSS classnames, removed img elements.
                         */
                        $css_classname = 'ADA_NOTE_TYPE';

                        /*
                         * Check if this is a tutor note
                         */
                        if (
                            !AMADataHandler::isError($class_tutor_id)
                            //          && $nodeObj->author['id'] == $class_tutor_id) {
                            && $nodeInfo['author']['id'] == $class_tutor_id
                        ) {
                            //  $alt            = translateFN('Nota del tutor');
                            $css_classname .= ' TUTOR_NOTE';
                        } else {
                            //  $alt = translateFN('Nota dello studente');
                        }


                        /* mod. 07/03/11
                 showing author name and surname as displayname
                        */
                        $node_author    = $nodeInfo['author']['username'];
                        $node_author_name    = $nodeInfo['author']['nome'];
                        $node_author_surname    = $nodeInfo['author']['cognome'];

                        /* mod. 07/03/11
                 showing creation date
                  */
                        $node_creation_date = $nodeInfo['creation_date'];

                        $node_display_name = '(' . $node_author_name . "&nbsp;" . $node_author_surname . ')';
                        $node_display_date = "&nbsp;-&nbsp;" . $node_creation_date . "&nbsp;-&nbsp;";


                        /*
                         * Check if this note was added by the currently
                         * logged user
                         */
                        if ($nodeInfo['author']['id'] == $sess_id_user) {
                            $css_classname .= ' YOUR_NOTE';
                        }

                        /*
                         * Check if note is visible to the currently logged user
                         */
                        switch ($id_profile) {
                            case AMA_TYPE_TUTOR:
                                $is_note_visible = true;
                                break;

                            case AMA_TYPE_STUDENT:
                                if ($nodeInfo['author']['tipo'] == AMA_TYPE_TUTOR && $nodeInfo['type'] == ADA_NOTE_TYPE) {
                                    $is_note_visible = true;
                                } else {
                                    $author_dataHa =  $dh->getSubscription($nodeInfo['author']['id'], $sess_id_course_instance);
                                    if (!AMADataHandler::isError($author_dataHa)) {
                                        $is_note_visible = true;
                                    }
                                }

                                break;

                            case AMA_TYPE_AUTHOR:
                            case AMA_TYPE_ADMIN:
                            default:
                                $is_note_visible = false;
                                break;
                        }

                        if ($is_note_visible) {
                            $note_link = ['<div class="' . $css_classname . '"><a href="view.php?id_node=' . $id_note . '">' . $name . '</a>' . $node_display_date . $node_display_name . '</div>'];
                            array_push($dataAr, $note_link);
                        }
                    } else {
                        //          //$alt = translateFN("Nota non visitabile");
                        //          $css_classname = 'ADA_NOTE_TYPE NOTE_NOT_VIEWABLE';
                        //          $note_link = array('<div class="'.$css_classname.'">'.$nodeObj->name.'</div>');
                        //          array_push($dataAr,$note_link);
                    }
                } else {
                    $is_note_visible = false;
                }
            }

            $dataAr = $this->removeEmptyElements($dataAr);

            $t = new Table();
            $t->initTable('0', 'center', '0', '0', '100%', 'white', '', 'white', '', '0', '0');
            $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice delle note"));
            return $t->getTable();
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }
    // fine filtro note

    public function getPrivateNotesFN($user_level, $id_profile)
    {
        //global $dh,$error,$debug;
        //global $sess_id_user, $sess_id_course_instance;
        $dh =   $GLOBALS['dh'] ?? null;
        $error =   $GLOBALS['error'] ?? null;
        $debug =   $GLOBALS['debug'] ?? null;
        $sess_id_course_instance =   $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_user =   $_SESSION['sess_id_user'] ?? null;

        if (!empty($this->children)) {
            $notes_Ar = $this->children;
            $dataAr = [];

            // vito 12gennaio2009
            $class_tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);

            foreach ($notes_Ar as $id_note) {
                $nodeObj = new Node($id_note, 0);
                //$GLOBALS['debug']=1; Utilities::mydebug(__LINE__,__FILE__,$nodeObj); $GLOBALS['debug']=0;
                $type = $nodeObj->type;
                $node_instance = $nodeObj->instance;
                if ($type == ADA_PRIVATE_NOTE_TYPE) { // notes
                    $name = $nodeObj->name;
                    $level =  $nodeObj->level;
                    //$authorHa = $nodeObj->author;
                    //$autore = $authorHa['id'];
                    //$GLOBALS['debug']=1; Utilities::mydebug(__LINE__,__FILE__,$authorHa); $GLOBALS['debug']=0;
                    // $node_author =  $authorHa['nome']." ". $authorHa['cognome'];
                    /*
                    * vito 12 gennaio2009
                    * Added CSS classnames, removed img elements.
                    */
                    if (
                        $sess_id_course_instance == $node_instance
                        && $nodeObj->author['id'] == $sess_id_user
                    ) {
                        $css_classname = 'ADA_NOTE_TYPE';

                        if (
                            !AMADataHandler::isError($class_tutor_id)
                            && $class_tutor_id == $nodeObj->author['id']
                        ) {
                            $css_classname .= ' TUTOR_NOTE';
                        }

                        $css_classname .= ' YOUR_NOTE ADA_PRIVATE_NOTE_TYPE';
                        $note_link = ['<div class="' . $css_classname . '"><a href="view.php?id_node=' . $id_note . '">' . $name . '</a></div>'];
                        array_push($dataAr, $note_link);
                    } else {
                        //vito 12 gennaio 2009
                        //  $css_classname = 'ADA_NOTE_TYPE NOTE_NOT_VIEWABLE';
                        //  $note_link = array('<div class="'.$css_classname.'">'.$node_data_Ha['name'].'</div>');
                        //  array_push($dataAr,$note_link);
                    }
                }
            }

            $dataAr = $this->removeEmptyElements($dataAr);

            $t = new Table();
            $t->initTable('0', 'center', '0', '0', '100%', 'white', '', 'white', '', '0', '0');
            $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice delle note private"));
            return $t->getTable();
        } else {
            return translateFN("Nessuna") . "<br/>";
        }
    }
    // fine filtro note private

    public function getMediaFN($user_level)
    {

        if ($this->level <= $user_level) {
            $dataAr = [];
            if (is_array($this->media)) {
                foreach ($this->media as $mediaId) {
                    $mediaObj = Media::findById($mediaId);
                    //                print_r($mediaObj);
                    if ($mediaObj->isFull()) {
                        //                    array_push($dataAr,$mediaObj->getLinkToMedia());
                        $dataAr[] = ['media' => $mediaObj->getLinkToMedia()];
                    }
                }
            }
            //        print_r($dataAr);
            if (count($dataAr) > 0) {
                $rules = '';
                $style = 'table_link';

                $dataAr = $this->removeEmptyElements($dataAr);

                $t = new Table();
                $t->initTable('0', 'center', '2', '0', '100%', '', '', '', '', '0', '0', $rules, $style);
                $t->setTable($dataAr, $caption = "", $summary = translateFN("Indice dei Media collegati"));
                $t->getTable();
                return $t->data;
            } else {
                return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
            }
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }

    public function getUserMediaFN($user_level)
    {
        // indexing files
        $root_dir = $GLOBALS['root_dir'] ?? null;
        $http_root_dir = $GLOBALS['http_root_dir'] ?? null;
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_course = $_SESSION['sess_id_course'] ?? null;
        $sess_id_node = $_SESSION['sess_id_node'] ?? null;
        //$sess_id_node = $GLOBALS['sess_id_node'];

        $dh = $GLOBALS['dh'];

        $course_ha = $dh->getCourse($sess_id_course);
        if (AMADataHandler::isError($course_ha)) { // not enrolled yet?
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
        $author_id = $course_ha['id_autore'];
        $elencofile = $this->readUserDirFN("$root_dir/services/media/$author_id");

        if ($elencofile == null) { //($stop<1)
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }

        $fcount = count($elencofile);
        $media = "";
        $dataAr = [];

        $lObj = new IList();
        $lObj->initList('0', 'disc', 1);
        /*
         for  ($i=0; $i<$fcount; $i++){
         $data = $elencofile[$i]['data'];
         $complete_file_name = $elencofile[$i]['file'];
         // rebuilding true file name
         // rootdir  + media path + author_id + id_course_instance + user_id + course_id + node_id + filename
         // ex. 111_27_113_0_example.txt'
         $filenameAr = explode('_',$complete_file_name);
         $stop = count($filenameAr)-1;
         $course_instance = $filenameAr[0];
         $id_sender  = $filenameAr[1];
         if (is_numeric($id_sender)) {
         $id_node =  $filenameAr[2]."_".$filenameAr[3];
         $filename = "";
         for ($k = 4; $k<=$stop;$k++){
         $filename .=  $filenameAr[$k];
         if ($k<$stop)
         $filename .= "_";
         }
         $senderObj = readUserFromDB($id_sender);
         if ((is_object($senderObj)) || (!empty($senderObj->error_msg))) {
         $id_profile = $senderObj->tipo;
         switch ($id_profile){
         case   AMA_TYPE_STUDENT:
         case   AMA_TYPE_AUTHOR:
         case   AMA_TYPE_TUTOR:
         $user_name = $senderObj->username;
         break;
         default:
         // errore
         $sender_error = 1;
         }
         }

         if ((!$sender_error) && ($course_instance == $sess_id_course_instance)){
         if (($id_node == $sess_id_node))
         array_push($dataAr,"<a href=\"$http_root_dir/user/index.php?module=download.php&file=$complete_file_name\" target=_blank>$filename</a> <br> $user_name : $data");
         }
         }
         }
         */

        // vito, 30 mar 2009
        /*
        * Create a mediaviewer
        */
        $media_path = $GLOBALS['media_path'];

        if (MEDIA_LOCAL_PATH) {
            $http_file_path = MEDIA_LOCAL_PATH . $media_path;
        } else {
            $http_file_path = $http_root_dir . $media_path;
        }

        $VIEWINGPREFERENCES = [
            _IMAGE => IMG_VIEWING_MODE,
            _SOUND => AUDIO_PLAYING_MODE,
            _VIDEO => VIDEO_PLAYING_MODE,
            INTERNAL_LINK => 0,
            _DOC => DOC_VIEWING_MODE,
            _LINK => 0,
        ];
        $user_data          = ['level' => $user_level, 'id_course' => $sess_id_course];

        $mediaviewer = new MediaViewer($http_file_path, $user_data, $VIEWINGPREFERENCES);
        // end of vito, 30 mar 2009
        $dataAr = [];
        for ($i = 0; $i < $fcount; $i++) {
            $data = $elencofile[$i]['data'];
            $complete_file_name = $elencofile[$i]['file'];
            // rebuilding true file name
            // rootdir  + media path + author_id + filename + id_course_instance + user_id + node_id
            // ex. 111_27_113_0_example.txt'
            $filenameAr = explode('_', $complete_file_name);

            $stop = count($filenameAr) - 1;

            $course_instance = $filenameAr[0];

            $id_sender  = $filenameAr[1] ?? null;

            if (is_numeric($id_sender)) {
                $fid_node =  $filenameAr[2] . "_" . ($filenameAr[3] ?? '');
                $filename = "";

                // vito, 30 mar 2009
                $this_file_type = $filenameAr[4] ?? null;

                //for ($k = 4; $k<=$stop;$k++){
                for ($k = 5; $k <= $stop; $k++) {
                    $filename .=  $filenameAr[$k];
                    if ($k < $stop) {
                        $filename .= "_";
                    }
                }
                $sender_error = 0;
                $user_name    = "";
                // too slow !
                /*
                $senderObj = readUserFromDB($id_sender);
                if ((is_object($senderObj))) {
                $id_profile = $senderObj->tipo;
                switch ($id_profile){
                case   AMA_TYPE_STUDENT:
                case   AMA_TYPE_AUTHOR:
                case   AMA_TYPE_TUTOR:
                $user_name = $senderObj->username;
                break;
                default:
                // errore
                $sender_error = 1;
                }
                }
                */
                if ((!$sender_error) && ($course_instance == $sess_id_course_instance)) {
                    // if (!isset($fid_node) || ($fid_node == $sess_id_node)) ??
                    if ($fid_node == $sess_id_node) {
                        //array_push($dataAr,"<a href=\"$http_root_dir/user/index.php?module=download.php&amp;file=$complete_file_name\" target=_blank>".substr($filename,0,8)."...</a> <br> $user_name : $data");
                        // vito, 30 mar 2009
                        //array_push($dataAr,"<a href=\"$http_root_dir/browsing/download.php?file=$complete_file_name\" target=_blank>".substr($filename,0,8)."...</a> <br> $user_name : $data");
                        if (is_numeric($this_file_type)) {
                            $dataAr[] = $mediaviewer->getMediaLink([null, $this_file_type, $filename, $elencofile[$i]['file'], $elencofile[$i]['path_to_file']]);
                        }
                    }
                }
            }
        }

        if (count($dataAr)) {
            $lObj->setList($dataAr);
            $var = $lObj->getList();
            $media .= "$var</p>\n";
            return $media;
        } else {
            return $this->wrapTextInSpan(translateFN('Nessuno'), 'noitem')->getHtml();
        }
    }

    // functions
    public function readUserDirFN($dir)
    {
        return Utilities::readDir($dir); // from utilities.inc.php
    }

    /* function next_nodeFN
     *
     * @param  $orderParm a string (key for node parent's children ordering; default: 'ordine', cpuld be 'nome')
     *
     * @return string      - the id of the next node of the same group (if accessible)
     */

    public function nextNodeFN($orderParm = 'ordine')
    {
        $dh = $GLOBALS['dh'];
        //$sess_id_course = $_SESSION['sess_id_course'];
        $node_order = $this->ordine;
        $name = $this->name;
        $node_type = $this->type;
        $parent_id = $this->parent_id;
        if (($parent_id != null) && ($parent_id != "NULL")) { // in nod table, id_nodo_parent for the first node of a course is always "NULL"
            $childrenHA = $dh->getNodeChildrenInfo($parent_id, "", $orderParm);

            if (is_array($childrenHA)) {
                //      $childrenAr = Utilities::masort ($childrenAr, 'ordine',1,SORT_STRING);
                // parametric order key
                //      $childrenHA = Utilities::masort ($childrenHA, $orderParm,1,SORT_STRING);
                // no more needed, we order inside the query
                // var_dump($childrenHA);
                foreach ($childrenHA as $child) {
                    if ($child['id_nodo'] != $this->id) {
                        //$achild = current($childrenHA);
                        // var_dump($child);
                        $id_child = $child['id_nodo'];
                        $child_type = $child['tipo'];
                        $child_order = $child['ordine'];
                        // we should test the node level *before* returning it
                        // $child_level = $child['livello'];

                        if ($node_type == ADA_LEAF_TYPE || $node_type == ADA_GROUP_TYPE || Node::isNodeExercise($node_type)) {
                            if (
                                ($child_type == ADA_LEAF_TYPE) ||
                                ($child_type == ADA_GROUP_TYPE) ||
                                (Node::isNodeExercise($child_type))
                            ) {
                                //                      if ($child_order>$node_order){
                                if (($orderParm != 'ordine') || ($child_order > $node_order)) {
                                    $next_id = $id_child;
                                    return $next_id;
                                }
                            }
                        } elseif ($node_type == ADA_GROUP_WORD_TYPE || $node_type == ADA_LEAF_WORD_TYPE) {
                            if ($child_type == ADA_GROUP_WORD_TYPE || $child_type == ADA_LEAF_WORD_TYPE) {
                                //                          if ($child_order>$node_order){
                                if (($orderParm != 'ordine') || ($child_order > $node_order)) {
                                    $next_id = $id_child;
                                    return $next_id;
                                }
                            }
                        }
                    }
                }
            }
        }
        return "";
    }

    /* da implementare ancora:    */

    public function isAllowedFN($command, $id_profile)
    {
        $dh = $GLOBALS['dh'];

        // $dataHa = $dh->get_info($id_profile);


        switch ($id_profile) {
            case AMA_TYPE_AUTHOR:
                return true;
                break;
            case AMA_TYPE_STUDENT:
            case AMA_TYPE_TUTOR:
                return false;
                break;
            default:
                return false;
        }
    }

    public function edit($id_profile)
    {
        // va al form di modifica del nodo attuale se l'utente ha le permission giuste
        $sess_id_node = $_SESSION['sess_id_node'] ?? null;
        $id_node = $this->id;
        if ($this->isAllowedFN('modify', $id_profile)) {
            header("Location: ../services/edit_node.php?op=edit&id_node=$sess_id_node");
        }
    }

    public function delete($id_profile)
    {
        // elimina il nodo attuale se l'utente ha le permission giuste
        $sess_id_node = $_SESSION['sess_id_node'] ?? null;
        if ($this->isAllowedFN('delete', $id_profile)) {
            header("Location: ../services/edit_node.php?op=delete&id_node=$sess_id_node");
        }
    }

    public static function isNodeExercise($type)
    {
        switch (strval($type)[0] ?? null) { // type can be a string of 5 chars, like 30001
            case ADA_STANDARD_EXERCISE_TYPE:
                return true;
                break;
            case ADA_OPEN_MANUAL_EXERCISE_TYPE:
                return true;
                break;
            case ADA_OPEN_AUTOMATIC_EXERCISE_TYPE:
                return true;
                break;
            case ADA_CLOZE_EXERCISE_TYPE:
                return true;
                break;
            case ADA_OPEN_UPLOAD_EXERCISE_TYPE:
                return true;
                break;
            case ADA_PERSONAL_EXERCISE_TYPE:
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * @author giorgio 29/ago/2014
     * wrap returned text inside a span
     *
     * @param string $text the text to be wrapped
     * @param string $class if passed, the css class assigned to the span
     *
     * @return CBaseElement on success, null on failure
     *
     * @access private
     */
    private function wrapTextInSpan($text, $class = null)
    {
        if (strlen($text) > 0) {
            $retel = CDOMElement::create('span');
            if (!is_null($class) && strlen($class) > 0) {
                $retel->setAttribute('class', $class);
            }
            $retel->addChild(new CText($text));
            return $retel;
        } else {
            return null;
        }
    }

    /**
     * @author giorgio 29/ago/2014
     * remove empty $dataAr elements
     *
     * @param array $dataAr data to operate on
     *
     * @return array cleaned array
     *
     * @access private
     */
    private function removeEmptyElements($dataAr)
    {
        foreach ($dataAr as $index => $row) {
            if (
                is_array($row) && count($row) == 1 && isset($row[0]) &&
                ($row[0] == '&nbsp;' || empty($row[0]))
            ) {
                $firstElement = reset($row);
                if ($firstElement == '&nbsp;' || empty($firstElement)) {
                    unset($dataAr[$index]);
                }
            }
        }
        return $dataAr;
    }
}

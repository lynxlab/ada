<?php

use Lynxlab\ADA\Browsing\MapFunctions;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Services\NodeEditing\NodeEditing;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT,AMA_TYPE_AUTHOR,AMA_TYPE_TUTOR, AMA_TYPE_VISITOR, AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_VISITOR => ['node', 'layout', 'course'],
    AMA_TYPE_STUDENT => ['node', 'layout', 'tutor', 'course', 'course_instance'],
    AMA_TYPE_TUTOR => ['node', 'layout', 'course', 'course_instance'],
    AMA_TYPE_AUTHOR => ['node', 'layout', 'course'],
    AMA_TYPE_SWITCHER => ['node', 'layout', 'course'],
];

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
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

if ($userObj instanceof ADAGuest) {
    $self = 'guest_map';
} else {
    $self = Utilities::whoami();
}

/*
 * YOUR CODE HERE
 */

// redirect sul nodo nel caso in cui venga cliccato un nodo anzichè un gruppo
if ($nodeObj->type == ADA_LEAF_TYPE || $nodeObj->type == ADA_LEAF_WORD_TYPE) {
    header('Location: view.php?id_node=' . $nodeObj->id);
    exit();
}

//$node_path = $nodeObj->findPathFN();  // default: link to view.php
$node_path = $nodeObj->findPathFN('map');



// THE MAP
//$data = "<div><b>MAPPA DEL GRUPPO {$nodeObj->name}</b></div>\n\n";
$data = "<h1 class='ui header map-title'>{$nodeObj->name}</h1>\n\n";
$data .= "<div id=\"map_content\" style=\"position:relative;top:0px;left:0px;\">\n";

$nodeList = $nodeObj->graphIndexFN();
$otherPos = [0,0,0,0];
$tipo_mappa = MapFunctions::returnMapType();

if (!AMADB::isError($nodeList) && is_array($nodeList) && count($nodeList) > 0) {
    // AND HIS CHILDS
    foreach ($nodeList as $key) {
        if ($userObj->getType() == AMA_TYPE_AUTHOR || $nodeObj->level <= $userObj->livello) {
            //        print_r($key);
            $nodePostId = 'input_' . $key['id_child']; // node id for javascript
            $childNodeObj = DBRead::readNodeFromDB($key['id_child']);
            if ($childNodeObj instanceof Node) {
                // saving new positions
                if (isset($_POST[$nodePostId])) {
                    $nodeArray = $childNodeObj -> object2arrayFN();
                    $nodeArray['position'] = $_POST[$nodePostId]; // it is a string as requested by NodeEditing::saveNode()
                    $nodeArray['icon'] = $key['icon_child']; // it does not function: NodeEditing::saveNode(), lines 210-214

                    //                 $res = NodeEditing::saveNode($nodeArray);
                    $res = NodeEditing::saveNodePosition($nodeArray);
                    if ($res == true) {
                        // read from here new Position
                        $p = explode(",", $_POST[$nodePostId]);
                        $width = ($p[2] - $p[0]);
                        if ($width < 0) {
                            $width *= -1;
                        }
                        $nodeChildPos = [ $p[0], $p[1], 100, 100 ];
                    } else {
                        // code here
                        $nodeChildPos = MapFunctions::returnAdaNodePos($key['position_child'], $key['id_child']);
                    }
                } else {
                    $nodeChildPos = MapFunctions::returnAdaNodePos($key['position_child'], $key['id_child']);
                }
            } else {
                // code here
            }

            //settings style, id etc etc etc for javascript
            $thisNodeStyle = 'left:' . $nodeChildPos[0] . 'px;top:' . $nodeChildPos[1] . 'px;width:' . $nodeChildPos[2] . 'px;height:auto;';
            $node_type = MapFunctions::returnAdaNodeType($key['type_child']);
            if ((($node_type == "lemma" || $node_type == 'gruppo_lemmi') && $tipo_mappa == "lemma") || (($node_type == "gruppo" || $node_type == 'nodo' || $node_type == 'test') && $tipo_mappa != "lemma")) {
                $data .= '<div class="newNodeMap" style="position:absolute;' . $thisNodeStyle . '" id="' . $key['id_child'] . '">';
                $data .= '<img src="' . MapFunctions::returnAdaNodeIcon($key['icon_child'], $key['type_child']) . '"/>';

                // setting icon
                if ($key['type_child'] == ADA_GROUP_TYPE) {
                    if (isset($key['children_count']) && $key['children_count'] > 0) {
                        $linkFile = '';
                    } else {
                        $linkFile = HTTP_ROOT_DIR . '/browsing/view.php';
                    }
                    $data .= '<a href="' . $linkFile . '?id_node=' . $key['id_child'] . '">' . $key['name_child'] . '</a>';
                } elseif ($key['type_child'] == ADA_GROUP_WORD_TYPE) {
                    if (isset($key['children_count']) && $key['children_count'] > 0) {
                        $linkFile = '';
                    } else {
                        $linkFile = HTTP_ROOT_DIR . '/browsing/view.php';
                    }
                    $data .= '<a href="' . $linkFile . '?id_node=' . $key['id_child'] . '&map_type=lemma">' . $key['name_child'] . '</a>';
                } else {
                    if (strval($key['type_child'])[0] == ADA_STANDARD_EXERCISE_TYPE) {
                        $linkFile = 'exercise';
                    } else {
                        $linkFile = 'view';
                    }
                    $data .= '<a href="' . HTTP_ROOT_DIR . '/browsing/' . $linkFile . '.php?id_node=' . $key['id_child'] . '">' . $key['name_child'] . '</a>';
                }
                // hidden div whit information for javascript
                $data .= '<div style="display:none">' . MapFunctions::returnAdaNodeLink($key['linked']) . '</div>';
                $data .= '</div>';
            };
        }
    }
}

$data .= '</div>';

//form button to save data (only for author)
if ($userObj-> tipo == AMA_TYPE_AUTHOR && $mod_enabled) {
    $id_node_parent = $nodeObj->id;
    $data .= '<form method="POST" action="map.php?map_type=' . $tipo_mappa . '&id_node=' . $id_node_parent . '" id="form_map"><input type="hidden" name="mod_map"/></form>';
};
//$data .= '<script type="text/javascript">document.getElementById("help").onclick=function(){alert($("map_content").map.nodeList)}</script>';


//$data .= "<div>LIVELLO STUDENTE: ".$userObj->livello."</div>";
/*
 * TO HERE
 */



$help = BaseHtmlLib::link(HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $nodeObj->id, translateFN('Torna al contenuto del nodo'))->getHtml();

$label = translateFN('mappa');

//$help = translateFN('mappa');

$menuOptions['self_instruction'] = isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance ? $courseInstanceObj->getSelfInstruction() : 0;
$menuOptions['id_course'] = $sess_id_course;
$menuOptions['id_course_instance'] = $sess_id_course_instance;
$menuOptions['id_node'] = $sess_id_node;
$menuOptions['id_parent'] = $nodeObj->parent_id;
$menuOptions['id_student'] = $userObj->getId();
$menuOptions['type'] = $nodeObj->type;

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => translateFN('mappa'),
    'path' => $node_path,
    'data' => $data,
    'edit_profile' => $userObj->getEditProfilePage(),
    'help' => $help ?? '',
    'id_node_parent' => strcasecmp('null', $nodeObj->parent_id) != 0 ? $nodeObj->parent_id : $nodeObj->id,
];
$options = ['onload_func' => "var map = new Map()"];
ARE::render($layout_dataAr, $content_dataAr, null, $options, $menuOptions);

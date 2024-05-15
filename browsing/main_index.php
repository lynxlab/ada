<?php

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\AMA\DBRead\readUser;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */

$neededObjAr = [
  AMA_TYPE_VISITOR => ['layout','course'],
  AMA_TYPE_STUDENT => ['layout','tutor','course','course_instance'],
  AMA_TYPE_TUTOR   => ['layout','course','course_instance'],
  AMA_TYPE_AUTHOR  => ['layout','course'],
  AMA_TYPE_SWITCHER  => ['layout','course'],
];


/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = 'index';

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

if (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
}

$self = 'index';

if (!isset($hide_visits)) {
    $hide_visits = 1; // default: no visits countg
}

if (!isset($order)) {
    $order = 'struct'; // default
}

if (!isset($op)) {
    $op = null;
}

if (!isset($expand)) {
    if ($op == 'forum') {
        $expand = 1;
    } elseif ($op == 'glossary') {
        $expand = 2; // default: 1 level of nodes
    } else {
        $expand = 3; // default: 1 level of nodes
    }
}
$with_icons = 1; // 0 or 1; valid only for forum display

// FIXME: verificare se servono.
//if (isset($id_course)){
//  $_SESSION['sess_id_course'] = $id_course;
//  $sess_id_course = $id_course;
//}
//
//if (isset($id_course_instance)){
//  $_SESSION['sess_id_course_instance'] = $id_course_instance;
//  $sess_id_course_instance = $id_course_instance;
//}
// ******************************************************
// get user object
$userObj = DBRead::readUser($sess_id_user);
if (is_object($userObj) && (!AMADataHandler::isError($userObj))) {
    if (isset($_POST['s_node_name'])) {
        header("Location: search.php?submit=1&s_node_text=$s_node_name&l_search=$l_search");
        exit;
    } else {
        // FIXME: verificare se compare in browsing_init.inc.php
        //    if ($id_profile == AMA_TYPE_STUDENT_STUDENT) {
        //      $user_level = $userObj->getStudentLevel($sess_id_user,$sess_id_course_instance);
        //    }
        //    else {
        //      $user_level = ADA_MAX_USER_LEVEL;
        //    }



        // dynamic mode:
        // ******************************************************

        /*
        $exp_link = translateFN("Profondit&agrave;");
        for ($e=1;$e<11;$e++){
        if ((isset($expand)) AND ($e == $expand))
            $label_exp = "<strong>$e</strong>";
        else
            $label_exp = $e;
        $exp_link .= "<a href=\"main_index.php?op=$op&amp;order=struct&amp;hide_visits=$hide_visits&amp;expand=$e\">$label_exp</a> |";
        }
        $exp_link .="<br>\n";
        */

        $div_link = CDOMElement::create('div');
        $link_expand = CDOMElement::create('a', 'class:ui small button');
        $link_expand->setAttribute('id', 'expandNodes');
        $link_expand->setAttribute('href', 'javascript:void(0);');
        $link_expand->setAttribute('onclick', "toggleVisibilityByDiv('structIndex','show');");
        $link_expand->addChild(CDOMElement::create('i', 'class:add icon'));
        $link_expand->addChild(new CText(translateFN('Apri Nodi')));
        $link_collapse = CDOMElement::create('a', 'class:ui small button');
        $link_collapse->setAttribute('href', 'javascript:void(0);');
        $link_collapse->setAttribute('onclick', "toggleVisibilityByDiv('structIndex','hide');");
        $link_collapse->addChild(CDOMElement::create('i', 'class:minus icon'));
        $link_collapse->addChild(new CText(translateFN('Chiudi Nodi')));

        $div_link->addChild($link_expand);
        $div_link->addChild($link_collapse);

        $exp_link = $div_link->getHtml();


        if ((isset($op)) && (($op == 'forum') || ($op == 'diary'))) {
            /* listing mode:
             *  standard o null
             *  export
             *  export_single
             */

            // template for forum index

            //$self = 'forum_index';

            if (!isset($list_mode) or ($list_mode == "")) {
                $list_mode = "standard";
            } else { // export_all , export_single
                $node_index = $course_instance_Obj->forumMainIndexFN('', 1, $id_profile, $order, $id_student, $list_mode);
                $node_index = strip_tags($node_index);

                //  $node_index = unhtmlentities($node_index);
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                // always modified
                header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");                          // HTTP/1.0
                //header("Content-Type: text/plain");
                header("Content-Type: application/rtf");

                //header("Content-Length: ".filesize($name));
                header("Content-Disposition: attachment; filename=" . $op . "_" . $id_course . "_class_" . $sess_id_course_instance . ".rtf");
                echo $node_index;
                exit;
            }
            $legenda = CDOMElement::create('div', 'id:legenda');
            $label   = CDOMElement::create('span', 'class:text');
            $label->addChild(new CText(translateFN('Legenda:')));
            $legenda->addChild($label);

            $group_item = CDOMElement::create('span', 'class:ADA_GROUP_TYPE');
            $group_item->addChild(new CText(translateFN('gruppo')));
            $legenda->addChild($group_item);

            $note_item = CDOMElement::create('span', 'class:ADA_NOTE_TYPE');
            $note_item->addChild(new CText(translateFN('nota di classe di un altro studente')));
            $legenda->addChild($note_item);
            $tutor_note_item = CDOMElement::create('span');
            $tutor_note_item->setAttribute('class', 'ADA_NOTE_TYPE TUTOR_NOTE');
            $tutor_note_item->addChild(new CText(translateFN('nota di classe del tutor')));
            $legenda->addChild($tutor_note_item);
            $your_note_item = CDOMElement::create('span');
            $your_note_item->setAttribute('class', 'ADA_NOTE_TYPE YOUR_NOTE');
            $your_note_item->addChild(new CText(translateFN('tua nota di classe')));
            $legenda->addChild($your_note_item);
            $private_note_item = CDOMElement::create('span');
            $private_note_item->setAttribute('class', 'ADA_NOTE_TYPE YOUR_NOTE ADA_PRIVATE_NOTE_TYPE');
            $private_note_item->addChild(new CText(translateFN('nota personale')));
            $legenda->addChild($private_note_item);

            $order_div = CDOMElement::create('div', 'id:ordering');
            if (isset($order) && $order == 'chrono') {
                $alfa = CDOMElement::create('span', 'class:selected ui small button disabled');
                $alfa->addChild(new CText(translateFN('Ordina per data')));
                $order_div->addChild($alfa);
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=struct&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per struttura')));
                $struct->addChild($link);
                $order_div->addChild($struct);
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=alfa&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per titolo')));
                $struct->addChild($link);
                $order_div->addChild($struct);
                $expand_nodes = false;
            } elseif (isset($order) && $order == 'alfa') {
                $order = 'alfa';
                $alfa = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=chrono&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per data')));
                $alfa->addChild($link);
                $order_div->addChild($alfa);
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=struct&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per struttura')));
                $struct->addChild($link);
                $order_div->addChild($struct);
                $struct = CDOMElement::create('span', 'class:selected ui small button disabled');
                $struct->addChild(new CText(translateFN('Ordina per titolo')));
                $order_div->addChild($struct);
                $expand_nodes = true;
            } else {
                $order = 'struct';
                $alfa = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=chrono&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per data')));
                $alfa->addChild($link);
                $order_div->addChild($alfa);
                $struct = CDOMElement::create('span', 'class:selected ui small button disabled');
                $struct->addChild(new CText(translateFN('Ordina per struttura')));
                $order_div->addChild($struct);
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=alfa&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per titolo')));
                $struct->addChild($link);
                $order_div->addChild($struct);
                $expand_nodes = true;
            }


            switch ($hide_visits) {
                case 0:
                    $span = CDOMElement::create('span', 'class:selected ui small button disabled');
                    $span->addChild(new CText(translateFN('mostra anche le visite')));
                    $order_div->addChild($span);
                    break;
                case 1:
                default:
                    $span = CDOMElement::create('span', 'class:not_selected ui small button');
                    $link = CDOMElement::create('a', "href:main_index.php?op=$op&order=$order&hide_visits=0&expand=$expand");
                    $link->addChild(new CText(translateFN('mostra anche le visite')));
                    $span->addChild($link);
                    $order_div->addChild($span);
                    break;
            }


            $index_link = $order_div->getHtml();

            if ($expand_nodes) {
                $node_index  = $exp_link;
            }

            //$menu = "<a href=\"main_index.php?op=$op&amp;order=chrono&amp;list_mode=export_all\">".translateFN("Esporta")."</a>";
            //vito, 8 giugno 2009
            $menu = CourseViewer::displayForumMenu($op, $userObj);
            //$node_index .= CourseViewer::displayForumIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance, $with_icons, 'structIndex');
            // vito, 26 nov 2008: $forum_index is a CORE object.


            $forum_index = CourseViewer::displayForumIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance, $with_icons, 'structIndex');

            $node_index .= $forum_index->getHtml();

            $node_index .= $legenda->getHtml();
            // NODES & GROUPS INDEX
        } elseif ((isset($op)) && ($op == 'glossary')) { // glossary index
            $legenda = CDOMElement::create('div', 'id:legenda');
            $label   = CDOMElement::create('span', 'class:text');
            $label->addChild(new CText(translateFN('Legenda:')));
            $legenda->addChild($label);
            $node_item = CDOMElement::create('span', 'class:ADA_LEAF_WORD_TYPE');
            $node_item->addChild(new CText(translateFN('nodo')));
            $legenda->addChild($node_item);
            $group_item = CDOMElement::create('span', 'class:ADA_GROUP_WORD_TYPE');
            $group_item->addChild(new CText(translateFN('gruppo')));
            $legenda->addChild($group_item);
            $unreachable_item = CDOMElement::create('span', 'class:NODE_NOT_VIEWABLE');
            $unreachable_item->addChild(new CText(translateFN('nodo non raggiungibile')));
            $legenda->addChild($unreachable_item);
            //$exercise_item = CDOMElement::create('span','class:ADA_LEAF_TYPE');
            //$exercise_item->addChild(new CText(translateFN('esercizio')));
            //$legenda->addChild($exercise_item);
            //$executed_exercise_item = CDOMElement::create('span','class:ADA_LEAF_TYPE');
            //$executed_exercise_item->addChild(new CText(translateFN('esercizio gi&agrave; eseguito')));
            //$legenda->addChild($executed_exercise_item);

            $order_div = CDOMElement::create('div', 'id:ordering');
            if (isset($order) && $order == 'alfa') {
                $alfa = CDOMElement::create('span', 'class:selected ui small button disabled');
                $alfa->addChild(new CText(translateFN('Ordina per titolo')));
                $order_div->addChild($alfa);
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?order=struct&expand=$expand&op=$op");
                $link->addChild(new CText(translateFN('Ordina per struttura')));
                $struct->addChild($link);
                $order_div->addChild($struct);
            } else {
                $order = 'struct';
                $alfa = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?order=alfa&expand=$expand&op=$op");
                $link->addChild(new CText(translateFN('Ordina per titolo')));
                $alfa->addChild($link);
                $order_div->addChild($alfa);
                $struct = CDOMElement::create('span', 'class:selected ui small button disabled');
                $struct->addChild(new CText(translateFN('Ordina per struttura')));
                $order_div->addChild($struct);
            }


            switch ($hide_visits) {
                case 0:
                    $span = CDOMElement::create('span', 'class:selected ui small button disabled');
                    $span->addChild(new CText(translateFN('mostra anche le visite')));
                    $order_div->addChild($span);
                    break;
                case 1:
                default:
                    $span = CDOMElement::create('span', 'class:not_selected ui small button');
                    $link = CDOMElement::create('a', "href:main_index.php?order=$order&hide_visits=0&expand=$expand&op=$op");
                    $link->addChild(new CText(translateFN('mostra anche le visite')));
                    $span->addChild($link);
                    $order_div->addChild($span);
                    break;
            }

            $index_link = $order_div->getHtml();

            $search_label = translateFN('Cerca nell\'Indice:');
            $node_type = 'standard_node';
            $node_index  = $exp_link;
            $glossary_index = CourseViewer::displayGlossaryIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance, 'courseIndex');
            if (!AMADataHandler::isError($glossary_index)) {
                $node_index .= $glossary_index->getHtml();
            }

            //vito 26 gennaio 2009
            //$node_index .= $legenda;
            $node_index .= $legenda->getHtml();
        } else { //normal index
            $legenda = CDOMElement::create('div', 'id:legenda');
            $label   = CDOMElement::create('span', 'class:text');
            $label->addChild(new CText(translateFN('Legenda:')));
            $legenda->addChild($label);
            $node_item = CDOMElement::create('span', 'class:ADA_LEAF_TYPE');
            $node_item->addChild(new CText(translateFN('nodo')));
            $legenda->addChild($node_item);
            $group_item = CDOMElement::create('span', 'class:ADA_GROUP_TYPE');
            $group_item->addChild(new CText(translateFN('gruppo')));
            $legenda->addChild($group_item);
            $unreachable_item = CDOMElement::create('span', 'class:NODE_NOT_VIEWABLE');
            $unreachable_item->addChild(new CText(translateFN('nodo non raggiungibile')));
            $legenda->addChild($unreachable_item);
            //$exercise_item = CDOMElement::create('span','class:ADA_LEAF_TYPE');
            //$exercise_item->addChild(new CText(translateFN('esercizio')));
            //$legenda->addChild($exercise_item);
            //$executed_exercise_item = CDOMElement::create('span','class:ADA_LEAF_TYPE');
            //$executed_exercise_item->addChild(new CText(translateFN('esercizio gi&agrave; eseguito')));
            //$legenda->addChild($executed_exercise_item);

            $order_div = CDOMElement::create('div', 'id:ordering');
            if (isset($order) && $order == 'alfa') {
                $struct = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?order=struct&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per struttura')));
                $struct->addChild($link);
                $order_div->addChild($struct);
                $alfa = CDOMElement::create('span', 'class:selected ui small button disabled');
                $alfa->addChild(new CText(translateFN('Ordina per titolo')));
                $order_div->addChild($alfa);
                $expand_nodes = false;
            } else {
                $order = 'struct';
                $struct = CDOMElement::create('span', 'class:selected ui small button disabled');
                $struct->addChild(new CText(translateFN('Ordina per struttura')));
                $order_div->addChild($struct);
                $alfa = CDOMElement::create('span', 'class:not_selected ui small button');
                $link = CDOMElement::create('a', "href:main_index.php?order=alfa&expand=$expand");
                $link->addChild(new CText(translateFN('Ordina per titolo')));
                $alfa->addChild($link);
                $order_div->addChild($alfa);
                $expand_nodes = true;
            }


            switch ($hide_visits) {
                case 0:
                    $span = CDOMElement::create('span', 'class:not_selected ui small button');
                    $link = CDOMElement::create('a', "href:main_index.php?order=$order&hide_visits=1&expand=$expand");
                    $link->addChild(new CText(translateFN('nascondi le visite')));
                    $span->addChild($link);
                    $order_div->addChild($span);
                    break;
                case 1:
                default:
                    $span = CDOMElement::create('span', 'class:not_selected ui small button');
                    $link = CDOMElement::create('a', "href:main_index.php?order=$order&hide_visits=0&expand=$expand");
                    $link->addChild(new CText(translateFN('mostra anche le visite')));
                    $span->addChild($link);
                    $order_div->addChild($span);
                    break;
            }

            $index_link = $order_div->getHtml();

            $search_label = translateFN('Cerca nell\'Indice:');
            $node_type = 'standard_node';
            /*
             * vito, 23 luglio 2008
             */
            if ($expand_nodes) {
                $node_index  = $exp_link;
            }


            //$node_index .= CourseViewer::displayMainIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance,'structIndex');

            // vito, 26 nov 2008: $main_index is a CORE object

            $main_index = CourseViewer::displayMainIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance, 'structIndex');
            if (!AMADataHandler::isError($main_index)) {
                $node_index .= $main_index->getHtml();
            }

            //vito 26 gennaio 2009
            //$node_index .= $legenda;
            $node_index .= $legenda->getHtml();
        }


        /* 2.
         getting todate-information on user
         MESSAGES adn EVENTS
         */
        /*
         * Non dovrebbe servire
         */
        //    if (empty($userObj->error_msg)){
        //      // FIXME: MULTIPORTARE
        //      //$user_messages = $userObj->getMessagesFN($sess_id_user);
        //      //$user_agenda =  $userObj->getAgendaFN($sess_id_user);
        //    } else {
        //      $user_messages =  $userObj->error_msg;
        //      $user_agenda   = translateFN("Nessun'informazione");
        //
        //    }
    }
} else {
    //  $user_messages = $userObj;
    //  $user_agenda = "";
    $errObj = new ADAError($userObj, translateFN('Utente non trovato, impossibile proseguire'));
}

/*
 *  Who's online
 */
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users
$online_users_listing_mode = 2;
$id_course_instance ??= null;
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);

/*
 * Search form (redirects to search.php)
 */
$search_data = [
  [
    'label'     => $search_label ?? null,
    'type'      => 'text',
    'name'      => 's_node_name',
    'size'      => '20',
    'maxlength' => '40',
    'value'     => "",
  ],
  [
    'label' => '',
    'type'  => 'submit',
    'name'  => 'Submit',
    'value' => translateFN('Cerca'),
  ],
  [
    'label'    => '',
    'type'     => 'hidden',
    'name'     => 'l_search',
    'size'     => '20',
    'maxlength' => '40',
    'value'    => $node_type ?? null,
  ],
];
$fObj = new Form();
$fObj->setForm($search_data);
$search_form = $fObj->getForm();


//show course istance name if isn't empty - valerio
if (!empty($courseInstanceObj->title)) {
    $course_title .= ' - ' . $courseInstanceObj->title;
}

if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $user_type = $user_type . ' livello ' . $user_level;
    $user_level = '';
    $layout_dataAr['JS_filename'] = [ROOT_DIR . '/js/include/menu_functions.js'];
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

$title = '';
if (isset($index_link)) {
    $title .= $index_link;
}
if (isset($index_no_visits_link)) {
    $title .= $index_no_visits_link;
}

$content_dataAr = [
  'course_title' => "<a href='main_index.php'>" . $course_title . "</a>",
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'user_level'   => $user_level,
  'last_visit' => $last_access,
  'status'       => $status,
  'title'        => $title,
  'index'        => $node_index,
  'search_form'  => $search_form,//."<br>".$menu,
  'forum_menu'   => $menu ?? '',
  'messages'     => $user_messages->getHtml(),
  'agenda'       => $user_agenda->getHtml(),
  'events'       => $user_events->getHtml(),
  'edit_profile' => $userObj->getEditProfilePage(),
  'chat_users'   => $online_users,
 ];

$layout_dataAr['CSS_filename'] = [
 'main_index.css',
];

ARE::render($layout_dataAr, $content_dataAr);

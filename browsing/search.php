<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Forms\AdvancedSearchForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsNode;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['node', 'layout', 'course', 'course_instance','user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout','course', 'course_instance'],
  AMA_TYPE_STUDENT         => ['layout','course', 'course_instance'],
  AMA_TYPE_TUTOR => ['layout','course', 'course_instance'],
  AMA_TYPE_AUTHOR       => ['layout','course', 'course_instance'],
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
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADAAbstractUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

if ($courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
}
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $self = 'searchSelfInstruction';
} else {
    $self = Utilities::whoami();
}

if (isset($_REQUEST['submit'])) {
    $submit = 'simpleForm';
} elseif (isset($_REQUEST['submit_advancedForm'])) {
    $submit = 'advancedForm';
} else {
    $submit = null;
}

if (isset($_REQUEST['s_node_name'])) {
    $s_node_name = $_REQUEST['s_node_name'];
} else {
    $s_node_name = '';
}
if (isset($_REQUEST['s_node_title'])) {
    $s_node_title = $_REQUEST['s_node_title'];
} else {
    $s_node_title = '';
}
if (isset($_REQUEST['s_node_text'])) {
    $s_node_text = $_REQUEST['s_node_text'];
} else {
    $s_node_text = '';
}

if (!is_null($submit)) {
    $out_fields_ar = ['nome','titolo','testo','tipo','id_utente','livello'];
    $clause = '';
    $or = ' OR ';
    $and = ' AND ';

    /*
     * Versione campo unico
     *
     *
     */
    if (!empty($s_UnicNode_text)) {
        $s_UnicNode_text = trim($s_UnicNode_text);
        $clause = "(";
        $clause = $clause . "nome LIKE '%$s_UnicNode_text%'";
        $clause = $clause . $or . "titolo LIKE '%$s_UnicNode_text%'";
        $clause = $clause . $or . "testo LIKE '%$s_UnicNode_text%'";
        $clause = $clause . ")";
    } else {
        $s_UnicNode_text = "";
    }

    /*
     * Versione campo diversi
     *
     *
     */

    $resHa = [];
    if ($s_UnicNode_text == "") {
        $count = 0;

        if (empty($s_node_name) && empty($s_node_title)  && empty($s_node_text)) {
            $clause = '((tipo <> ' . ADA_PRIVATE_NOTE_TYPE . ') OR (tipo =' . ADA_PRIVATE_NOTE_TYPE . ' AND id_utente = ' . $sess_id_user . '))';
            $resHa = $dh->findCourseNodesList($out_fields_ar, $clause, $_SESSION['sess_id_course']);
        } else {
            if (!empty($s_node_name)) {
                $count++;
            }
            if (!empty($s_node_title)) { //keywors
                $count++;
            }
            if (!empty($s_node_text)) {
                $count++;
            }

            $resHa = $courseObj->executeSearch(trim($s_node_name), trim($s_node_title), trim($s_node_text), $dh, $count, $sess_id_user);
        }
    } elseif (empty($resHa)) {
        $clause = '(' . $clause . ')' . $and . ' ((tipo <> ' . ADA_PRIVATE_NOTE_TYPE . ') OR (tipo =' . ADA_PRIVATE_NOTE_TYPE . ' AND id_utente = ' . $sess_id_user . '))';
        $resHa = $dh->findCourseNodesList($out_fields_ar, $clause, $_SESSION['sess_id_course']);
    }


    if (!AMADataHandler::isError($resHa)) {
        // se studente, filtra i nodi con livello > di quello dello studente
        if (isset($userObj) && $userObj->getType() == AMA_TYPE_STUDENT) {
            $studenLevel = (int) $userObj->getStudentLevel($userObj->getId(), $courseInstanceObj->getId());
            $resHa = array_filter($resHa, fn ($row) =>
                // livello is row[6]
                (int) $row[6] <= $studenLevel);
        }
    }

    if (!AMADataHandler::isError($resHa) and is_array($resHa) and !empty($resHa)) {
        $total_results = [];
        $group_count = 0;
        $node_count = 0;
        $note_count = 0;
        $exer_count = 0;

        foreach ($resHa as $row) {
            $res_id_node = $row[0];
            $res_name = $row[1];
            $res_course_title = $row[2];
            if (ModuleLoaderHelper::isLoaded('FORKEDPATHS')) {
                $res_course_title = ForkedPathsNode::removeMagicWordFromTitle($res_course_title);
            }
            $res_text = $row[3];
            $res_type =  $row[4];

            switch ($res_type) {
                case ADA_GROUP_TYPE:
                    //$icon = "<img src=\"img/group_ico.png\" border=0>";
                    $class_name = 'ADA_GROUP_TYPE';
                    $group_count++;
                    break;
                case ADA_LEAF_TYPE:
                    //$icon = "<img src=\"img/node_ico.png\" border=0>";
                    $class_name = 'ADA_LEAF_TYPE';
                    $node_count++;
                    break;
                case ADA_GROUP_WORD_TYPE:
                    //$icon = "<img src=\"img/group_ico.png\" border=0>";
                    $class_name = 'ADA_GROUP_WORD_TYPE';
                    $group_count++;
                    break;
                case ADA_LEAF_WORD_TYPE:
                    //$icon = "<img src=\"img/node_ico.png\" border=0>";
                    $class_name = 'ADA_LEAF_WORD_TYPE';
                    $node_count++;
                    break;
                case ADA_PERSONAL_EXERCISE_TYPE:
                    $class_name = 'ADA_PERSONAL_EXERCISE_TYPE';
                    $node_count++;
                    break;
            }
            $s_node_text_enc = urlencode($s_node_text);
            if ($res_type == ADA_GROUP_TYPE || $res_type == ADA_LEAF_TYPE || ADA_GROUP_WORD_TYPE || $res_type == ADA_LEAF_WORD_TYPE || $res_type == ADA_NOTE_TYPE || $res_type == ADA_PRIVATE_NOTE_TYPE) {
                $html_for_result = "<span class=\"$class_name\"><a href=\"view.php?id_node=$res_id_node&querystring=$s_node_text_enc\">$res_name</a></span>";
            }
            $temp_results = [translateFN('Titolo') => $html_for_result,translateFN('keywords') => $res_course_title];
            array_push($total_results, $temp_results);
        }
        $thead_data = [
            translateFN('Titolo'),
            translateFN('Keywords'),
        ];
        $result_table = BaseHtmlLib::tableElement('id:table_result,class:' . ADA_SEMANTICUI_TABLECLASS, $thead_data, $total_results);
        $results = $result_table->getHtml();
        $results = 'Risultati: ' . $results;
    } else {
        $results = translateFN("Non &egrave; stato trovato nessun nodo.");
    }
}  // end Submit

$menu  = "<p>" . translateFN("Scrivi la o le parole che vuoi cercare, poi clicca su Cerca. ");
$menu .= "<br>" . translateFN("Il sistema restituir&agrave; una lista con i nodi che contengono TUTTE le parole inserite.");
$menu .= "<br>" . translateFN("Le parole vengono trovate anche all'interno di altre parole e senza distinzioni tra maiuscole e minuscole.") . "</p>";
;

/*menù advanced search*/

$menuAdvanced_search  = "<p>" . translateFN("Scrivi la o le parole che vuoi cercare, poi clicca su Cerca.");
$menuAdvanced_search .= "<br>" . translateFN("Puoi effettuare la ricerca su più campi contemporaneamente. ");
$menuAdvanced_search .= "<br>" . translateFN("Il sistema proverà a restituituire una lista dei nodi contenenti tutte le parole indicate; ");
$menuAdvanced_search .= translateFN("se ciò non è possibile restituirà la lista dei nodi che ne contengono almeno una.");
$menuAdvanced_search .= "<br>" . translateFN("Le parole vengono trovate anche all'interno di altre parole e senza distinzioni tra maiuscole e minuscole.") . "</p>";

// $menu .= "<br>".translateFN("Se vuoi cercare tra i media collegati (immagini, suoni, siti) usa la ")."<a href=search_media.php>".translateFN("Ricerca sui Media")."</a></p>";
// $menu .= "<br>".translateFN("Se non sai esattamente cosa cercare, prova a consultare il ")."<a href=lemming.php>".translateFN("Lessico")."</a></p>";



/* 5.
search form

*/

// versione con campo UNICO

$l_search = 'standard_node';
$form_dataHa = [
 // SEARCH FIELDS
 [
   'label' => translateFN('Parola') . "<br>",
   'type' => 'text',
   'name' => 's_UnicNode_text',
   'size' => '20',
   'maxlength' => '40',
   'value' => $s_UnicNode_text,
 ],
  [
   'label' => '',
   'type' => 'hidden',
   'name' => 'l_search',
   'value' => $l_search,
 ],
  [
   'label' => '',
   'type' => 'submit',
   'name' => 'submit',
   'value' => translateFN('Cerca'),
 ],
];

if (isset($op) && $op == 'lemma') {
    $form_dataHa[] =  [
    'label' => '',
    'type' => 'hidden',
    'name' => 'op',
    'value' => $op,
    ];
}

$fObj = new Form();
$action = Utilities::whoami() . ".php";
/*set get method to prevent the confirmation data on back button's browser*/
$fObj->initForm($action, 'GET');
$fObj->setForm($form_dataHa);
$search_form = $fObj->getForm();

if (isset($_GET['s_AdvancedForm'])) {
    $form_AdvancedSearch = new AdvancedSearchForm(false, 'search.php');

    if ($form_AdvancedSearch->isValid()) {
        $AdvancedSearchAr = [
        's_node_name' => ($_GET['s_node_name']),
        's_node_title' => ($_GET['s_node_title']),
        's_node_text' => ($_GET['s_node_text']),
        's_AdvancedForm' => ($_GET['s_AdvancedForm']),
        ];

        $form_AdvancedSearch->fillWithArrayData($AdvancedSearchAr);
        $advancedSearch_form = $form_AdvancedSearch->getHtml();
    }
} else {
    $form_AdvancedSearch = new AdvancedSearchForm(false, 'search.php');
    $action = Utilities::whoami() . ".php";
    $advancedSearch_form = $form_AdvancedSearch->getHtml();
}
$online_users_listing_mode = 2;
$online_users = ADALoggableUser::getOnlineUsersFN($sess_id_course_instance, $online_users_listing_mode);

// CHAT, BANNER etc
$SimpleSearchlabel = translateFN('Ricerca semplice');
$AdvanceSearchlabel = translateFN('Ricerca avanzata');
$Simple_searchLink = "<a class='simple-search' href='#'onClick=simpleSearch()>$SimpleSearchlabel</a>";
$advanced_searchLink = "<a class='advanced-search' href='#'onClick=advancedSearch()>$AdvanceSearchlabel</a>";
/* 8.
costruzione della pagina HTML
*/

$imgAvatar = $userObj->getAvatar();
$avatar = CDOMElement::create('img', 'src:' . $imgAvatar);
$avatar->setAttribute('class', 'img_user_avatar');

$spanSimple_search = CDOMElement::create('span', 'id:span_simpleSearch');
$spanSimple_search->addChild(new CText("<strong>" . translateFN('Ricerca semplice') . "</strong>"));

$spanAdvanced_search = CDOMElement::create('span', 'id:span_advancedSearch');
$spanAdvanced_search->setAttribute('style', 'display:none');
$spanAdvanced_search->addChild(new CText("<strong>" . translateFN('Ricerca avanzata') . "</strong>"));

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

if (isset($_GET['s_AdvancedForm'])) {
    $result_AdvancedSearch = $results;
    unset($results);
} else {
    $result_AdvancedSearch = null;
}

$content_dataAr = [
 'form' => $search_form,
 'advancedSearch_form' => $advancedSearch_form,
 'menuAdvanced_search' => $menuAdvanced_search,
 'results' => $results ?? '',
 'help' => $spanSimple_search->getHtml() . $spanAdvanced_search->getHtml(),
 'result_AdvancedSearch' => $result_AdvancedSearch,
 'simpleSearchLink' => $Simple_searchLink,
 'advanced_searchLink' => $advanced_searchLink,
 'menu' => $menu,
 'course_title' => translateFN(' Ricerca '),
 'user_name' => $user_name,
 'user_type' => $user_type,
 'user_level' => $user_level,
 'status' => $status,
 'last_visit' => $last_access,
 'index' => $node_index,
 //'title'=>$node_title,
 'author' => $node_author ?? '',
 // 'text'=>$data['text'],
 // 'link'=>$data['link'],
 'messages' => $user_messages->getHtml(),
 'agenda' => $user_agenda->getHtml(),
 'events' => $user_events->getHtml(),
 'chat_users' => $online_users,
 'user_avatar' => $avatar->getHtml(),
 ];

/**
 * Sends data to the rendering engine
 */
$layout_dataAr['JS_filename'] = [
       JQUERY,
       JQUERY_UI,
       JQUERY_DATATABLE,
       SEMANTICUI_DATATABLE,
       JQUERY_NO_CONFLICT,
       JQUERY_MASKEDINPUT];

$layout_dataAr['CSS_filename'] =  [
       JQUERY_UI_CSS,
       SEMANTICUI_DATATABLE_CSS];

if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    array_push($layout_dataAr['JS_filename'], ROOT_DIR . '/js/browsing/search.js');
}

$options['onload_func'] = 'dataTablesExec()';
$menuOptions['self_instruction'] = $self_instruction;
//"\$j('input, a.button, button').uniform();"
ARE::render($layout_dataAr, $content_dataAr, null, ['onload_func' => "initDoc();"], $menuOptions);

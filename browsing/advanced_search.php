<?php

use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;

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
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR => ['layout'],
  AMA_TYPE_STUDENT => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_AUTHOR => ['layout'],
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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/* if($userObj instanceof ADAGuest) {
$self = 'guest_view'; FIXME: we have to create a guest_search template
}
else { */
// $self = Utilities::whoami();
$self = 'search';
/*} */

$id_course = $_SESSION['sess_id_course'];

if (isset($submit)) { //&& (!empty($s_node_text))) {
    $out_fields_ar = ['nome','titolo','testo','tipo'];
    $clause = '';
    $or = ' OR ';
    $and = ' AND ';
    /*
     * Versione campo unico
     *
     *
     */
    if (!empty($s_node_text)) {
        $clause = "(";
        $clause = $clause . "nome LIKE '%$s_node_text%'";
        $clause = $clause . $or . "titolo LIKE '%$s_node_text%'";
        $clause = $clause . $or . "testo LIKE '%$s_node_text%'";
        $clause = $clause . ")";
    } else {
        $s_node_text = "";
    }


    /*
     * Versione campo diversi
     *
     *
     */

    $out_fields_ar = ['nome','titolo','testo','tipo','id_utente'];
    $clause = '';
    $or = '';
    $and = '';

    if (!empty($s_node_name)) {
        $clause = "nome LIKE '%$s_node_name%'";
    }
    if (!empty($s_node_title)) { //keywors
        if ($clause) {
            //$and = " AND ";
            $operator = $s_node_search_mode;
        }
        $clause = $clause . $operator . "titolo LIKE '%$s_node_title%'";
    }
    if (!empty($s_node_text)) {
        if ($clause) {
            //$and = " AND ";
            $operator = $s_node_search_mode;
        }
        $clause = $clause . $operator . "testo LIKE '%$s_node_text%'";
    } else {
        $s_node_text = "";
    }
    // authors
    /*if (!empty($s_node_author)){
$s_node_authorAR = explode(' ',$s_node_author);
$node_author_name = $s_node_authorAR[0];
$auth_clause = "nome LIKE '$node_author_name' ";
if (count($s_node_authorAR)>1){ //name & surname
$node_author_surname = $s_node_authorAR[1];
$auth_clause .= "AND cognome LIKE '$node_author_surname' ";
}
else {
$auth_clause .= "OR cognome LIKE '$s_node_author' ";
$auth_clause .= "OR username LIKE '$s_node_author' ";
}
$auth_field_list_ar = array('username');
$authorAR = $dh->findAuthorsList($auth_field_list_ar,$auth_clause);
if (is_object($authorAR) OR (!isset($authorAR)) ) { //error
$id_author = 'nobody';
}
else {
$id_author = $authorAR[0][0]; // id
}
if ($clause) {
//$and = " AND ";
$operator=$s_node_search_mode;
}
$clause = $clause . $operator. "id_utente LIKE '$id_author'";
}*/
    // node types
    if (isset($l_search)) {
        switch ($l_search) {
            case 'standard_node': // group OR nodes NOT notes
                if ($clause) {
                    $and = " AND ";
                }
                $clause = '( ' . $clause . ')' . $and . " (tipo = " . ADA_GROUP_TYPE . " OR tipo = " . ADA_LEAF_TYPE . ")";
                $checked_standard = "checked";
                break;
            case 'group':
            case ADA_GROUP_TYPE:
                $type = ADA_GROUP_TYPE;
                if ($clause) {
                    $and = " AND ";
                }
                $clause = $clause . $and . "tipo = '$type'";
                $checked_group = "checked";
                break;
            case 'node':
                //case ADA_LEAF_TYPE:
                $type = ADA_LEAF_TYPE;
                if ($clause) {
                    $and = " AND ";
                }
                $clause = $clause . $and . "tipo = '$type'";
                $checked_node = "checked";
                break;
            case 'note':
            case ADA_NOTE_TYPE: //ricerca nel forum
                $type = ADA_NOTE_TYPE;
                if ($clause) {
                    $and = " AND ";
                }
                $clause = '(' . $clause . ')' . $and . "tipo = '$type'";
                $checked_note = "checked";
                break;
            case 'private_note':
            case ADA_PRIVATE_NOTE_TYPE:
                $type = ADA_PRIVATE_NOTE_TYPE;
                if ($clause) {
                    $and = " AND ";
                }
                /* vito, 16 giugno 2009, vogliamo che l'utente veda tra i risultati della
    ricerca eventualmente solo le SUE note personali e non quelle di
    altri utenti.
    $clause = $clause . $and. "tipo = '$type'";*/
                $clause = $clause . $and . "(tipo = '$type' and id_utente='$sess_id_user')";
                $checked_note = "checked";
                break;
            case '':
            default:
            case 'all': // group OR nodes OR notes
                $checked_all = "checked";
                /* vito, 16 giugno 2009, vogliamo che l'utente veda tra i risultati della
    ricerca eventualmente solo le SUE note personali e non quelle di
    altri utenti.*/
                if ($clause) {
                    $and = " AND ";
                }
                $clause = '(' . $clause . ')' . $and . ' ((tipo <> ' . ADA_PRIVATE_NOTE_TYPE . ') OR (tipo =' . ADA_PRIVATE_NOTE_TYPE . ' AND id_utente = ' . $sess_id_user . '))';
                break;
        }
    }

    /* ricerca su tutti i corsi pubblici
* if (il tester è quello pubblico){
* $resHa = $dh->find_public_course_nodes_list($out_fields_ar, $clause,$sess_id_course);
* }
*/

    // $resHa = $dh->findCourseNodesList($out_fields_ar, $clause,$sess_id_course);
    $resHa = $dh->findCourseNodesList($out_fields_ar, $clause, $id_course);

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
            $res_text = $row[3];
            $res_type = $row[4];

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
                case ADA_NOTE_TYPE:
                    //$icon = "<img src=\"img/note_ico.png\" border=0>";
                    $class_name = 'ADA_NOTE_TYPE';
                    $note_count++;
                    break;
                case ADA_PRIVATE_NOTE_TYPE:
                    //$icon = "<img src=\"img/_nota_pers.png\" border=0>";
                    $class_name = 'ADA_PRIVATE_NOTE_TYPE';
                    $note_count++;
                    break;
                case ADA_STANDARD_EXERCISE_TYPE:
                default:
                    $class_name = 'ADA_STANDARD_EXERCISE_TYPE';
                    //$icon = "<img src=\"img/exer_ico.png\" border=0>";
                    $exer_count++;
            }
            //$temp_results = array(translateFN("Titolo")=>"<a href=view.php?id_node=$res_id_node&querystring=$s_node_text>$icon $res_name</a>");

            if ($res_type == ADA_GROUP_TYPE || $res_type == ADA_LEAF_TYPE || $res_type == ADA_NOTE_TYPE || $res_type == ADA_PRIVATE_NOTE_TYPE) {
                $html_for_result = "<span class=\"$class_name\"><a href=\"view.php?id_node=$res_id_node&querystring=$s_node_text\">$res_name</a></span>";
            }
            /*else {
            $html_for_result = "<span class=\"$class_name\"><a href=\"exercise.php?id_node=$res_id_node\">$res_name</a></span>";
            }*/
            $temp_results = [translateFN('Titolo') => $html_for_result];
            //$temp_results = array(translateFN("Titolo")=>$title,translateFN("Testo")=>$res_text);
            array_push($total_results, $temp_results);
        }

        $tObj = new Table();
        $tObj->initTable('0', 'center', '2', '1', '100%', 'black', 'white', 'black', 'white');
        $summary = translateFN("Elenco dei nodi che soddisfano la ricerca al ") . $ymdhms;
        // $caption = translateFN("Sono stati trovati")." $group_count ".translateFN("gruppi").", $node_count ".translateFN("nodi").", $exer_count ".translateFN("esercizi").", $note_count ".translateFN("note.");
        $caption = translateFN("Sono stati trovati") . " $group_count " . translateFN("gruppi") . ", $node_count " . translateFN("nodi");
        $tObj->setTable($total_results, $caption, $summary);
        $search_results = $tObj->getTable();
        $search_results = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $search_results, 1); // replace first occurence of class
        // diretto:
        //header("Location: view.php?id_node=$res_id_node");
    } else {
        $search_results = translateFN("Non &egrave; stato trovato nessun nodo.");
    }
}

$menu = "<p>" . translateFN("Scrivi la o le parole che vuoi cercare, scegli quali oggetti cercare, e poi clicca su Cerca.");
$menu .= "<br>" . translateFN("ADA restituir&agrave; una lista con i nodi che contengono TUTTE le parole inserite.");
$menu .= "<br>" . translateFN("Le parole vengono trovate anche all'interno di altre parole, e senza distinzioni tra maiuscole e minuscole.") . "</p>";
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
'name' => 's_node_text',
'size' => '20',
'maxlength' => '40',
'value' => $s_node_text,
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


// versione con ricerca sui campi specifici:
if (!isset($s_node_name)) {
    $s_node_name = "";
}
if (!isset($s_node_title)) {
    $s_node_title = "";
}
if (!isset($s_node_author)) {
    $s_node_author = "";
}
// if (!isset($s_node_media))
// $s_node_media = "";
if (!isset($s_node_text)) {
    $s_node_text = "";
}
if (!isset($checked_standard)) {
    $checked_standard = "";
}
if (!isset($checked_note)) {
    $checked_note = "";
}
if (!isset($checked_all)) {
    $checked_all = "";
}

// vito, 10 june 2009

if ($checked_standard == "" && $checked_note == "" && $checked_all == "") {
    $checked_all = 'checked';
}

$form_dataHa = [
// SEARCH FIELDS
[
'label' => translateFN('Nome') . "<br>",
'type' => 'text',
'name' => 's_node_name',
'size' => '20',
'maxlength' => '40',
'value' => $s_node_name,
],
[
'label' => translateFN('Keywords') . "<br>",
'type' => 'text',
'name' => 's_node_title',
'size' => '20',
'maxlength' => '40',
'value' => $s_node_title,
],
[
'label' => translateFN('Testo') . "<br>",
'type' => 'textarea',
'name' => 's_node_text',
'size' => '40',
'maxlength' => '80',
'value' => $s_node_text,
],
[
'label' => '',
'type' => 'submit',
'name' => 'submit',
'value' => translateFN('Cerca'),
]];
$fObj = new Form();
$action = Utilities::whoami() . ".php";
/*set get method to prevent the confirmation data on back button's browser*/
$fObj->initForm($action, 'GET');
$fObj->setForm($form_dataHa);
$search_form = $fObj->getForm();
$Simple_searchLink = "<a href='search.php'>Ricerca semplice</a>";

/* 6.
recupero informazioni aggiornate relative all'utente
ymdhms: giorno e ora attuali
*/

/*

if ((is_object($userObj)) && (!AMADataHandler::isError($userObj))) {
if (empty($userObj->error_msg)){
$user_messages = $userObj->getMessagesFN($sess_id_user);
$user_agenda = $userObj->getAgendaFN($sess_id_user);
}
else {
$user_messages = $userObj->error_msg;
$user_agenda = translateFN("Nessun'informazione");
}
}
else {
$user_messages = $userObj;
$user_agenda = "";
}
*/

// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1 : username of users
// $online_users_listing_mode = 2 : username and email of users

$online_users_listing_mode = 2;
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);
/* 8.
costruzione della pagina HTML
*/
$Simple_searchLink = "<a href='#'onClick=simpleSearch()>Ricerca semplice</a>";

$content_dataAr = [
  'form' => $search_form,
  'results' => $search_results,
  'simpleSearch' => $Simple_searchLink,
  'menu' => $menu,
  'course_title' => '<a href="main_index.php">' . $course_title . '</a>',
  'user_name' => $user_name,
  'user_type' => $user_type,
  'level' => $user_level,
  'index' => $node_index,
  'title' => $node_title,
  'author' => $node_author,
  'text' => $data['text'],
  'link' => $data['link'],
  'messages' => $user_messages->getHtml(),
  'agenda' => $user_agenda->getHtml(),
  'events' => $user_events->getHtml(),
  'chat_users' => $online_users,
];

/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);

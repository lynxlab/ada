<?php

use Lynxlab\ADA\CORE\HtmlElements\IList;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

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
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();  // = author!

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
ServiceHelper::init($neededObjAr);
/*
 * YOUR CODE HERE
 */
$sess_id_user            = $_SESSION['sess_id_user'] ?? null;
$sess_id_course          = $_SESSION['sess_id_course'] ?? null;
$sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;


if (!isset($msg)) {
    $msg = translateFN("pronto");
}

$help = translateFN("Da qui l'autore pu&ograve; vedere vedere un report generale sui suoi corsi, modificare un corso oppure aggiungerne un nuovo.
");

// Who's online
// $online_users_listing_mode = 0 (default) : only total numer of users online
// $online_users_listing_mode = 1  : username of users
// $online_users_listing_mode = 2  : username and email of users

// FIXME: servono gli utenti online in questo modulo?
//$online_users_listing_mode = 2;
//$online_users = ADAAuthor::getOnlineUsersFN($id_course_instance,$online_users_listing_mode);

// find all course available

$field_list_ar = ['nome','titolo','data_creazione','media_path','id_nodo_iniziale'];
$key = $sess_id_user;
$search_fields_ar = ['id_utente_autore'];
$dataHa = $dh->findCoursesList($field_list_ar, 'id_utente_autore=' . $key);

if (AMADataHandler::isError($dataHa)) {
    /*
     * Qui, se codice di errore == AMA_ERR_NOT_FOUND, tutto ok, semplicemente non
     * ci sono corsi.
     * Altrimenti ADAError
     */
    $err_msg = $dataHa->getMessage();
    //header("Location: $error?err_msg=$msg");
} else {
    // courses array
    $course_dataHa = [];

    foreach ($dataHa as $course) {
        // mydebug(__LINE__,__FILE__,array('Course'=>$course[1]));
        $id_course = $course[0];
        $nome = $course[1];
        $titolo = $course[2];
        $data = ts2dFN($course[3]);
        $media_path =  $course[4];
        if (!$media_path) {
            $media_path = translateFN("default");
        }
        $id_nodo_iniziale = $course[5];

        // vito, 8 apr 2009
        $confirm_dialog_message = translateFN('Sei sicuro di voler eliminare questo corso?');
        $onclick = "confirmCriticalOperationBeforeRedirect('$confirm_dialog_message','delete_course.php?id_course=$id_course');";

        $row = [
        translateFN('Nome') => $nome,
        translateFN('Titolo') => $titolo,
        translateFN('Data') => $data,
        translateFN('Path') => $media_path,
        translateFN('Naviga') => "<a href=\"../browsing/view.php?id_course=$id_course&id_node=" . $id_course . "_" . $id_nodo_iniziale . "\"><img src=\"img/timon.png\" border=0></a>",
        translateFN('Report') => "<a href=\"author_report.php?id_course=$id_course\"><img src=\"img/report.png\" border=0></a>",
        translateFN('Aggiungi') => "<a href=\"addnode.php?id_course=$id_course\"><img src=\"img/_nodo.png\" border=0></a>",
      //translateFN('XML')=> "<a href=\"author_report.php?mode=xml&amp;id_course=$id_course\"><img src=\"img/xml.png\" border=0></a>",
      //translateFN('Elimina')=> "<a href=\"#\" onclick=\"$onclick\"><img src=\"img/delete.png\" border=0></a>"
        ];
        if (defined('MODULES_SLIDEIMPORT') && MODULES_SLIDEIMPORT) {
            $row[translateFN('Importa')] = "<a href=\"" . MODULES_SLIDEIMPORT_HTTP . "/?id_course=$id_course\"><img src=\"" . MODULES_SLIDEIMPORT_HTTP . "/layout/img/slideimport.png\" border=0></a>";
        }

        if (defined('MODULES_IMPEXPORT') && MODULES_IMPEXPORT && defined('MODULES_IMPEXPORT_REPODIR') && strlen(MODULES_IMPEXPORT_REPODIR) > 0) {
            $row[translateFN('Repository')] = "<a href=\"" .
            MODULES_IMPEXPORT_HTTP . "/export.php?exporttorepo=1&id_course=" . $id_course .
            "\"><img src=\"" . MODULES_IMPEXPORT_HTTP . "/layout/" . $_SESSION['sess_template_family'] . "/img/export-to-repo.png\"/></a>";
        }

        array_push($course_dataHa, $row);
    }
    $caption = translateFN("Corsi inviati e attivi il") . " $ymdhms";
    $tObj = BaseHtmlLib::tableElement('id:authorTable, class:doDataTable', array_keys(reset($course_dataHa)), $course_dataHa, null, $caption);
    $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
    $total_course_data = $tObj->getHtml();
    $optionsAr['onload_func'] = 'initDoc();';
    $layout_dataAr['CSS_filename'] =  [
        JQUERY_UI_CSS,
        SEMANTICUI_DATATABLE_CSS,
    ];
    $layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_DATATABLE,
        SEMANTICUI_DATATABLE,
        JQUERY_DATATABLE_DATE,
        JQUERY_NO_CONFLICT,
    ];
}

if (isset($err_msg)) {
    $total_course_data = translateFN("Nessun corso assegnato all'autore.");
}
// menu' table


$lObj = new IList() ;
$data =  [
    BaseHtmlLib::link('author_report.php', translateFN('report'))->getHtml(),
    BaseHtmlLib::link("edit_author.php?id=$sess_id_user", translateFN('modifica il tuo profilo'))->getHtml(),
  ];

$lObj->setList($data);
$menu_ha = $lObj->getList();


$title = translateFN('Home Autore');

//if (empty($user_messages)) {
//  $user_messages = translateFN('Non ci sono nuovi messaggi');
//}
// SERVICE:  BANNER

$content_dataAr = [
  //        'form'=>$menu_ha,
  'course_title' => translateFN('Lista dei servizi'),
  'menu'         => $menu_ha,
  'status'       => $msg,
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'help'         => $help,
  'form'         => $total_course_data,
  'edit_profile' => $userObj->getEditProfilePage(),
  'agenda'       => $user_agenda->getHtml(),
  'messages'     => $user_messages->getHtml(),
];
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr, null, ($optionsAr ?? null));

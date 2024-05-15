<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

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
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout','course'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();  // = author_report!

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

$menu = '';
/*
 * 2. Building nodes summary
*/
if ((empty($id_node)) or (!isset($mode))) {
    $mode = 'summary';
}

switch ($mode) {
    case 'zoom':
        $status = translateFN('zoom di un nodo');
        $help = translateFN("Da qui l'Autore del corso può vedere  in dettaglio le caratteristiche di un nodo.");

        $out_fields_ar = ['data_visita','id_utente_studente','id_istanza_corso'];
        $clause = "id_nodo = '$id_node'";

        $visits_ar = $dh->findNodesHistoryList($out_fields_ar, $clause);
        if (AMADataHandler::isError($visits_ar)) {
            $msg = $visits_ar->getMessage();
            print '$msg';
            //header('Location: $error?err_msg=$msg');
            //exit;
        }
        $visits_dataHa = [];
        $count_visits = count($visits_ar);
        if ($count_visits) {
            foreach ($visits_ar as $visit) {
                $user_id = $visit[2];
                if ($user_id > 0) {
                    $student = $dh->getUserInfo($visit[2]);
                    //global $debug;$debug=1;Utilities::mydebug(__LINE__,__FILE__,$student);$debug=0;
                    $studentname = $student['username'];
                } else {
                    $studentname = translateFN('Guest');
                }
                $visits_dataHa[] = [
                        translateFN('Data') => Utilities::ts2dFN($visit[1]),
                        translateFN('Ora') => Utilities::ts2tmFN($visit[1]),
                        translateFN('Studente') => $studentname,
                        translateFN('Edizione del corso') => $visit[3],
                        // etc etc
                ];
            }
            $caption = translateFN('Dettaglio delle visite al nodo') . ' ' . $id_node;
            $tObj = BaseHtmlLib::tableElement('id:authorZoom', array_keys(reset($visits_dataHa)), $visits_dataHa, null, $caption);
            $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
            $tabled_visits_dataHa = $tObj->getHtml();
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
        } else {
            $tabled_visits_dataHa = translateFN('Nessun dato disponibile');
        }
        $menu .= '<a href="author_report.php?mode=summary">' . translateFN('report') . '</a>';
        break;

    case 'xml':
        $filename = $id_course . '.xml';
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');    // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');          // always modified
        header('Cache-Control: no-store, no-cache, must-revalidate');  // HTTP/1.1
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');                          // HTTP/1.0
        header('Content-Type: text/xml');
        // header('Content-Length: '.filesize($name));
        header('Content-Disposition: attachment; filename=$filename');
        readfile('$http_root_dir/courses/media/$sess_id_user/$filename');
        //              header ('Connection: close');
        exit;
        break;

    case 'summary':
    default:
        $status = translateFN('elenco dei nodi');
        $help = translateFN("Da qui l'Autore del corso può vedere la lista dei nodi di cui è autore.");
        $course_id = isset($_GET['id_course']) ? intval($_GET['id_course']) : null;
        $courseHa = $dh->getCourse($course_id);
        if (AMADataHandler::isError($courseHa)) {
            $err_msg = $courseHa->getMessage();
            //header('Location: $error?err_msg=$msg');
            //exit;
        } else {
            $course_title = $courseHa['titolo'];
            $clause = "id_nodo LIKE '{$course_id}\_%' AND ";
            $field_list_ar = ['nome','id_utente'];
            $clause .= "id_utente='$sess_id_user'";
            $dataHa = $dh->doFindNodesList($field_list_ar, $clause);
            if (AMADataHandler::isError($dataHa)) {
                $err_msg = $dataHa->getMessage();
                //header('Location: $error?err_msg=$msg');
                //exit;
            }
            $total_visits = 0;
            $visits_dataHa = [];
            foreach ($dataHa as $visited_node) {
                $id_node = $visited_node[0];
                $nome =  $visited_node[1];
                $out_fields_ar = ['data_visita'];
                $clause = "id_nodo = '$id_node'";

                // FIXME: verificare quale fra queste due usare
                //         $visits = $dh->findNodesHistoryList($out_fields_ar,'', '', $node_id);
                $visits = $dh->findNodesHistoryList($out_fields_ar, $clause);

                if (AMADataHandler::isError($visits)) {
                    $msg = $visits->getMessage();
                    print '$msg';
                    //header('Location: $error?err_msg=$msg');
                    //exit;
                }
                $count_visits = count($visits);

                $total_visits = $total_visits + count($visits);
                $row = [
                        translateFN('Id')     => $id_node,
                        translateFN('Nome')   => $nome,
                        translateFN('Visite') => $count_visits,
                ];

                if ($count_visits > 0) {
                    $row[translateFN('Zoom')] = "<a href=\"author_report.php?mode=zoom&id_node=$id_node\"><img src=\"img/magnify.png\"' border=0></a>";
                } else {
                    $row[translateFN('Zoom')] = '&nbsp;';
                }
                $id_course_and_nodeAr = explode('_', $id_node);
                $id_course = $id_course_and_nodeAr[0];
                $row[translateFN('Naviga')] = "<a href=\"$http_root_dir/browsing/view.php?id_course=$id_course&id_node=$id_node\"><img src=\"img/timon.png\" border=0></a>";
                array_push($visits_dataHa, $row);
            }
        }

        if (isset($err_msg) || !is_array($visits_dataHa) || count($visits_dataHa) <= 0) {
            $tabled_visits_dataHa = translateFN("Nessun corso assegnato all'autore.");
        } else {
            $caption = translateFN('Corso:') . " <strong>$course_title</strong> " . translateFN('- Report al ') . " <strong>$ymdhms</strong>";
            $tObj = BaseHtmlLib::tableElement('id:authorReport, class: doDataTable', array_keys($visits_dataHa[0]), $visits_dataHa, null, $caption);
            $tObj->setAttribute('class', 'default_table doDataTable ' . ADA_SEMANTICUI_TABLECLASS);
            $tabled_visits_dataHa = $tObj->getHtml();
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
                    ROOT_DIR . '/js/include/jquery/dataTables/formattedNumberSortPlugin.js',
                    JQUERY_NO_CONFLICT,
            ];
        }
}

// SERVICE:  BANNER

$content_dataAr = [
        'course_title' => translateFN('Report del corso'),
        'menu'         => $menu,
        'user_name'    => $user_name,
        'user_type'    => $user_type,
        'help'         => $help,
        'status'       => $status,
        //'head'         => translateFN('Report'),
        'dati'         => $tabled_visits_dataHa,
        'agenda'       => $user_agenda->getHtml(),
        'messages'     => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr, null, ($optionsAr ?? null));

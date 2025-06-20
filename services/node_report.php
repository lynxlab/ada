<?php

use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
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
  AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();

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
 * Module
 */
$menu  = '<a href="index.php">' . translateFN('home') . '</a><br>'
       . "<a href=\"../browsing/view.php?id_node=$id_node\">"
       . translateFN('torna') . '</a><br>';

$status = translateFN('zoom di un nodo');
$help = translateFN('Da qui ogni autore di un nodo  può vederne  in dettaglio le caratteristiche');

$out_fields_ar = ['data_visita','id_utente_studente','id_istanza_corso'];
$clause = "id_nodo = '$id_node'";

$visits_ar = $dh->doFindNodesHistoryList($out_fields_ar, $clause);
if (AMADataHandler::isError($visits_ar)) {
    $msg = $visits_ar->getMessage();
}
$visits_dataHa = [];
$count_visits = count($visits_ar);
if ($count_visits) {
    foreach ($visits_ar as $visit) {
        $student = $dh->getUserInfo($visit[2]);
        $studentname = $student['username'];
        $visits_dataHa[] = [
                translateFN('Data') => Utilities::ts2dFN($visit[1]),
                translateFN('Ora') => Utilities::ts2tmFN($visit[1]),
                translateFN('Studente') => $studentname,
                // translateFN('Edizione del corso')=>$visit[3]
                // etc etc
        ];
    }
    $tObj = new Table();
    // $tObj->initTable('1','center','2','1','100%');
    $tObj->initTable('0', 'right', '1', '0', '90%', '', '', '', '', '1', '0');

    // Syntax: $border,$align,$cellspacing,$cellpadding,$width,$col1, $bcol1,$col2, $bcol2
    $caption = translateFN('Dettaglio');
    $summary = translateFN('Dettaglio delle visite al nodo') . ' ' . $id_node;
    $tObj->setTable($visits_dataHa, $caption, $summary);
    $tabled_visits_dataHa = $tObj->getTable();
    $tabled_visits_dataHa = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $tabled_visits_dataHa, 1); // replace first occurence of class
} else {
    $tabled_visits_dataHa = translateFN('Nessun dato disponibile');
}

$content_dataAr = [
        'menu' => $menu,
        'user_name' => $user_name,
        'user_type' => $user_type,
        'help' => $help,
        'status' => $status,
        'head' => translateFN('Dettaglio delle visite al nodo') . ' ' . $id_node,
        'dati' => $tabled_visits_dataHa,
        'agenda' => $user_agenda->getHtml(),
        'messages' => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

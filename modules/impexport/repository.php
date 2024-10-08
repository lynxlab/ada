<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
];

if (array_key_exists('id_course', $_REQUEST) || array_key_exists('id_node', $_REQUEST)) {
    $neededObjAr[AMA_TYPE_AUTHOR] = ['node', 'layout', 'course'];
}

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

$self = Utilities::whoami();

$data = null;

$tableData = '';
if (array_key_exists('id_course', $_REQUEST) || array_key_exists('id_node', $_REQUEST)) {
    $tableData = sprintf("data-import-course-id=%d data-import-node-id=%s", $courseObj->getId(), $nodeObj->id);
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => ucfirst(translateFN('Repository corsi')) . ' &gt; ' . translateFN('Elenco esportazioni'),
    'data' => $data,
    'modalID' => 'deleteConfirm',
    'modalHeader' => translateFN('Conferma cancellazione'),
    'modalContent' => '<p>' . translateFN("Questo cancellerà l'esportazione definitivamente") . '</p>',
    'modalYES' => translateFN('S&igrave;'),
    'modalNO' => translateFN('NO'),
    'tabledata' => $tableData,
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JS_VENDOR_DIR . '/datatables.net-rowgroup/js/dataTables.rowGroup.js',
    JS_VENDOR_DIR . '/datatables.net-rowgroup-se/js/rowGroup.semanticui.min.js',
    JQUERY_NO_CONFLICT,
];

/**
 * include proper jquery ui css file depending on wheter there's one
 * in the template_family css path or the default one
 */
$templateFamily = (isset($userObj->template_family) && strlen($userObj->template_family) > 0) ? $userObj->template_family : ADA_TEMPLATE_FAMILY;
$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    JS_VENDOR_DIR . '/datatables.net-rowgroup-se/css/rowGroup.semanticui.min.css',
    SEMANTICUI_DATATABLE_CSS,
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

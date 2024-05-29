<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classagenda
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;
use Lynxlab\ADA\Module\Classagenda\CalendarsManagement;

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
$variableToClearAR = ['layout',  'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course', 'course_instance'],
    AMA_TYPE_TUTOR =>    ['layout', 'course', 'course_instance'],
    AMA_TYPE_STUDENT =>  ['layout', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
SwitcherHelper::init($neededObjAr);

$self = Utilities::whoami();

if (isset($_GET['type']) && $_GET['type'] == 'csv') {
    $type = 'csv';
} else {
    $type = 'pdf';
}

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$calendarsManager = new CalendarsManagement();
$data = $calendarsManager->exportCalendar($courseObj, $courseInstanceObj, $type);

if ($type == 'pdf') {
    $content_dataAr = [
        'coursename' => $courseObj->getTitle(),
        'instancename' => $courseInstanceObj->getTitle(),
        'data' => (!is_null($data) && isset($data['htmlObj'])) ? $data['htmlObj']->getHtml() : translateFN('Nessun evento trovato'),
    ];
    $GLOBALS['adafooter'] = translateFN(PDF_EXPORT_FOOTER);
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
    ARE::render($layout_dataAr, $content_dataAr, ARE_PDF_RENDER, ['outputfile' => $courseInstanceObj->getTitle()]);
} elseif ($type == 'csv') {
    // output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=' . strtolower(ADA_CHARSET));
    header('Content-Disposition: attachment; filename=' . urlencode($courseInstanceObj->getTitle()) . '.csv');
    if (is_null($data)) {
        $data = [];
    }
    $out = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}
die();

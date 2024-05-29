<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2020, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classagenda
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;
use Lynxlab\ADA\Module\Classagenda\RollcallManagement;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course', 'course_instance'],
    AMA_TYPE_TUTOR =>    ['layout', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
SwitcherHelper::init($neededObjAr);

$self = Utilities::whoami();
$type = 'csv';
$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

// $id_course_instance is coming from $_GET
$rollcallManager = new RollcallManagement($id_course_instance);
$expData = $rollcallManager->exportRollCallHistory();

if ($type == 'csv') {
    $data = [
        [
            translateFN('Corso'),
            $courseObj->getTitle() . ' (ID:' . $courseObj->getId() . ')',
        ],
        [
            translateFN('Classe'),
            $courseInstanceObj->getTitle() . ' (ID:' . $courseInstanceObj->getId() . ')',
        ],
        [
            'URL',
            rtrim(HTTP_ROOT_DIR, '/') . '/browsing/view.php?id_node=' . $courseObj->getId() . '_' . $courseObj->id_nodo_toc . '&id_course=' . $courseObj->getId() . '&id_course_instance=' . $courseInstanceObj->getId(),
        ],
        [
            translateFN('Data e ora di generazione del file'),
            Utilities::todayDateFN() . ' ' . Utilities::todayTimeFN(),
        ],
        [], // empty csv line
    ];
    if (count($expData) > 0) {
        if (array_key_exists('header', $expData)) {
            array_push($data, $expData['header']);
        }
        foreach ($expData['studentsList'] as $rows) {
            array_push($data, $rows);
        }
    }

    // output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=' . strtolower(ADA_CHARSET));
    header('Content-Disposition: attachment; filename=' . urlencode($courseInstanceObj->getTitle()) . '.csv');
    $out = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}
die();

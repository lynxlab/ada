<?php

use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\InstancesReport\InstancesReportActions;
use Lynxlab\ADA\Switcher\Subscription;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(InstancesReportActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
// neededObjArr grants access to switcher only
SwitcherHelper::init($neededObjAr);

// globals set by SwitcherHelper::init
/** @var \Course $courseObj */

$reportRows = [
    [
        translateFN("id_studente"),
        translateFN("classe"),
        translateFN("nome"),
        translateFN("cognome"),
        translateFN("visite"),
        translateFN("tempo"),
        translateFN("ultimo accesso"),
        translateFN("stato"),
    ],
];
$reportRows[0] = array_map('strtoupper', $reportRows[0]);

$fieldsAr = ['title', 'tipo_servizio'];
$instancesAr = $dh->courseInstanceGetList($fieldsAr, $courseObj->getId());
if (is_array($instancesAr) && count($instancesAr) > 0) {
    foreach ($instancesAr as $instance) {
        $allSubscriptions = true;
        $htmlFromHistory = false;
        $subscriptions = Subscription::findSubscriptionsToClassRoom($instance['id_istanza_corso'], $allSubscriptions);
        if (is_array($subscriptions) && count($subscriptions) > 0) {
            /** @var \Lynxlab\ADA\Switcher\Subscription $subscription */
            foreach ($subscriptions as $subscription) {

                /** @var \Lynxlab\ADA\Main\History\History $historyObj */
                $historyObj = new History($instance['id_istanza_corso'], $subscription->getSubscriberId());
                $visits = $historyObj->getTotalVisitedNodes();
                $time = $historyObj->historyNodesTimeFN();
                $last = $historyObj->historyLastNodesFN(1, $htmlFromHistory);
                if (is_array($last) && count($last) > 0) {
                    $last = reset($last);
                    if (array_key_exists('Data', $last)) {
                        $lastVisit = new DateTime();
                        // Extract date only.
                        $lastVisit->setTimestamp(Utilities::dt2tsFN(explode(" ", $last['Data'])[0]));
                    }
                }

                if (isset($time)) {
                    [$h, $m, $s] = explode(":", $time);
                    $timeXLS = (($h * 3600) + ($m * 60) + $s) / 86400;
                }

                $reportRows[] = [
                    (int) $subscription->getSubscriberId(),
                    $instance['title'],
                    // (int) $instance['id_istanza_corso'],
                    ucfirst(strtolower($subscription->getSubscriberFirstname())),
                    ucfirst(strtolower($subscription->getSubscriberLastname())),
                    isset($visits) ? (int)$visits : 0,
                    // tempo come lo vuole excel,
                    $timeXLS ?? '00:00:00',
                    // ultimo accesso,
                    isset($lastVisit) ? Date::PHPToExcel($lastVisit) : '',
                    // stato, come stringa
                    Subscription::subscriptionStatusArray()[$subscription->getSubscriptionStatus()],
                ];

                unset($timeXLS);
                unset($lastVisit);
            }
        }
    }
}

$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($userObj->getFullName())
    ->setLastModifiedBy($userObj->getFullName())
    ->setTitle('Report ' . $courseObj->getTitle());

$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray($reportRows, null, 'A1', true);

// Retrieve Highest Column (e.g AE).
$highestColumn = $sheet->getHighestColumn();
// Retrieve the highest row index
$highestRow = $sheet->getHighestRow();
// Set autosize columns.
for ($i = 'A'; $i <=  $highestColumn; $i++) {
    $sheet->getColumnDimension($i)->setAutoSize(true);
}

// Set formats, excluding 1st row.
$sheet->getStyle("E")->getNumberFormat()
    ->setFormatCode(NumberFormat::FORMAT_NUMBER);

$sheet->getStyle("F")->getNumberFormat()
    ->setFormatCode('[HH]:MM:SS');

$sheet->getStyle("G")->getNumberFormat()
    ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);

// 1st row to bold.
$sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
// Freeze first row.
$sheet->freezePane('A2');

$filename = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $courseObj->nome . 'T' . date('Ymd-His')), '-'));
$format = 'xlsx';

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '.' . $format . '"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = IOFactory::createWriter($spreadsheet, ucfirst($format));
$writer->save('php://output');
exit;

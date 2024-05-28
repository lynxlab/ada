<?php

/**
 * CLASSBUDGET MODULE.
 *
 * @package        classbudget module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2015, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classbudget
 * @version        0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Classbudget\AbstractClassbudgetManagement;
use Lynxlab\ADA\Module\Classbudget\AMAClassbudgetDataHandler;
use Lynxlab\ADA\Module\Classbudget\ClassbudgetAPI;
use Lynxlab\ADA\Module\Classbudget\ClassroomBudgetManagement;
use Lynxlab\ADA\Module\Classbudget\TutorBudgetManagement;

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
$variableToClearAR = ['node', 'layout', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
SwitcherHelper::init($neededObjAr);

$self = 'classbudget';

$GLOBALS['dh'] = AMAClassbudgetDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

if (isset($_GET['export']) && in_array($_GET['export'], AbstractClassbudgetManagement::$exportFormats)) {
    $export = $_GET['export'];
} else {
    $export = false;
}

$help = translateFN('Gestione Budget e Costi per il corso') . ': ' . $courseObj->getTitle() . ' - ' .
    translateFN('Classe') . ': ' . $courseInstanceObj->getTitle();

$data = '';
$totalcost = 0;
$somethingFound = false;
if ($export !== false) {
    if ($export === 'pdf') {
        $action = null;
        $render = ARE_PDF_RENDER;
        $GLOBALS['adafooter'] = translateFN(PDF_EXPORT_FOOTER);
        $self .= 'PDF';
    } elseif ($export === 'csv') {
        $action = MODULES_CLASSBUDGET_CSV_EXPORT;
        $render = ARE_FILE_RENDER;
    } else {
        die(translateFN('Formato non supportato'));
    }
} else {
    $action = MODULES_CLASSBUDGET_EDIT;
    $render = null;
}

if (isset($GLOBALS['classBudgetComponents'])) {
    $classBudgetComponents = $GLOBALS['classBudgetComponents'];
} else {
    $classBudgetComponents = [];
}
/**
 * add classroom and tutor cost management as needed
 */
if (ModuleLoaderHelper::isLoaded('MODULES_CLASSAGENDA')) {
    if (ModuleLoaderHelper::isLoaded('MODULES_CLASSROOM')) {
        $classBudgetComponents[] = ['classname' => ClassroomBudgetManagement::class];
    }
}
$classBudgetComponents[] = ['classname' => TutorBudgetManagement::class];

foreach ($classBudgetComponents as $component) {
    if (class_exists($component['classname'])) {
        // $id_course_instance is coming from get
        $obj = new $component['classname']($courseInstanceObj->getId());
        $html = $obj->run($action);
        $totalcost += $obj->getGrandTotal();
        $somethingFound = $somethingFound || !empty($obj->dataCostsArr);

        if ($export === false || $render == ARE_PDF_RENDER) {
            if (!is_null($html)) {
                $data .= $html->getHtml();
            }
        } elseif ($render == ARE_FILE_RENDER) {
            // store data to export
            if (!isset($exportData)) {
                $exportData = [];
            }
            $exportData[] = $obj->buildCostArrayForCSV();
        }
    }
}

if ($render != ARE_PDF_RENDER) {
    if (strlen($data) > 0 || $somethingFound) {
        // add buttons
        $buttonDIV = CDOMElement::create('div', 'id:buttonswrapper');
        $saveButton = CDOMElement::create('button', 'class:budgetsave');
        $saveButton->setAttribute('onclick', 'javascript:saveBudgets();');
        $saveButton->addChild(new CText(translateFN('salva')));
        $cancelButton = CDOMElement::create('button', 'class:budgetcancel');
        $cancelButton->setAttribute('onclick', 'javascript:self.document.location.reload();');
        $cancelButton->addChild(new CText(translateFN('annulla')));
        $buttonDIV->addChild($saveButton);
        $buttonDIV->addChild($cancelButton);
        $data .= $buttonDIV->getHtml();
        $data .= CDOMElement::create('div', 'class:clearfix')->getHtml();
    } else {
        $div = CDOMElement::create('div', 'class:budgeterrorcontainer');
        $div->addChild(new CText(translateFN('Problemi nella generazione delle voci di costo, controllare l\'installazione del modulo')));
        $data .= $div->getHtml();
    }
}

$budgetAPI = new ClassbudgetAPI();
$budgetObj = $budgetAPI->getBudgetCourseInstance($courseInstanceObj->getId());
$budget = (isset($budgetObj->budget)) ? floatval($budgetObj->budget) : 0.00;
$balance = $budget - $totalcost;

$balanceclass = ($balance >= 0) ? 'balancegreen' : 'balancered';

$budgetStr = number_format($budget, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP);
$totalcostStr = number_format($totalcost, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP);
$balanceStr = number_format($balance, ADA_CURRENCY_DECIMALS, ADA_CURRENCY_DECIMAL_POINT, ADA_CURRENCY_THOUSANDS_SEP);

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => '',
    'help' => $help ?? '',
    'data' => $data ?? '',
    'currency' => ADA_CURRENCY_SYMBOL,
    'budgetStr' => $budgetStr,
    'totalcostStr' => $totalcostStr,
    'balanceStr' => $balanceStr,
    'balanceclass' => $balanceclass,
    'budget' => $budget,
    'totalcost' => $totalcost,
    'balance' => $balance,
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

$optionsAr['onload_func'] = 'initDoc();';

if ($render === ARE_FILE_RENDER && $export === 'csv') {
    // output headers so that the file is downloaded rather than displayed
    header('Content-Type: text/csv; charset=' . strtolower(ADA_CHARSET));
    header('Content-Disposition: attachment; filename=Budget-' . urlencode($courseInstanceObj->getTitle()) . '.csv');
    // build a resume array
    $resumeArr = [
        [translateFN('Budget'), $budgetStr],
        [translateFN('Costo totale'), $totalcostStr],
        [translateFN('Differenza'), $balanceStr],
        [],
    ];
    // put it as first exported data element
    array_unshift($exportData, $resumeArr);
    $out = fopen('php://output', 'w');
    foreach ($exportData as $section) {
        foreach ($section as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
} else {
    $menuOptions['id_course'] = $courseObj->getId();
    $menuOptions['id_course_instance'] = $courseInstanceObj->getId();
    ARE::render($layout_dataAr, $content_dataAr, $render, $optionsAr, $menuOptions);
}

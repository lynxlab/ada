<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$GLOBALS['dh'] = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $courseId = isset($_GET['courseId']) ? (int) $_GET['courseId'] : 0;
    $courseInstanceId = isset($_GET['instanceId']) ? (int) $_GET['instanceId'] : 0;
    $studentId = isset($_GET['studentId']) ? (int) $_GET['studentId'] : 0;

    $modal = CDOMElement::create('div', 'class:ui small modal');

    $header = CDOMElement::create('div', 'class:header');
    $header->addChild(new CText(translateFN('Resoconto condizioni di completamento')));

    $contentDIV = CDOMElement::create('div', 'class:content');
    $content = new CText(translateFN('Erroe nella generazione del resoconto'));

    $actions = CDOMElement::create('div', 'class:actions');
    $okbtn = CDOMElement::create('div', 'class:ui green button');
    $okbtn->addChild(new CText(translateFN('OK')));
    $actions->addChild($okbtn);

    $modal->addChild($header);
    $modal->addChild($contentDIV);
    $modal->addChild($actions);

    if ($courseId > 0 && $instanceId > 0 && $studentId > 0) {
        // load the conditionset for this course
        $conditionSet = $GLOBALS['dh']->getLinkedConditionsetForCourse($courseId);
        if ($conditionSet instanceof CompleteConditionSet) {
            $condString = $conditionSet->toString();
            if (strlen($condString) > 0) {
                $codeCont = CDOMElement::create('div', 'id:conditionCodeCont');
                $codeBtn = CDOMElement::create('button', 'class:ui icon blue small right floated button');
                $codeBtn->setAttribute('onclick', 'javascript:$j(\'#conditionCode\').slideToggle();');
                $codeBtn->addChild(CDOMElement::create('i', 'class:code icon'));
                $codeCont->addChild($codeBtn);
                $codeCont->addChild(CDOMElement::create('div', 'class:clearfix'));

                $codeDIV = CDOMElement::create('div', 'id:conditionCode');
                $h3 = CDOMElement::create('h3');
                $h3->addChild(new CText(translateFN('Codice della condizione')));
                $pre = CDOMElement::create('span', 'class:pre');
                $pre->addChild(new CText(str_replace('::buildAndCheck', '', $condString)));
                $codeDIV->addChild($h3);
                $codeDIV->addChild($pre);
                $codeCont->addChild($codeDIV);
                $contentDIV->addChild($codeCont);
            }
            // evaluate the conditionset for this instance ID and course ID
            $summary = $conditionSet->buildSummary([$courseInstanceId, $studentId]);
            if (is_array($summary) && count($summary) > 0) {
                $content = CDOMElement::create('div', 'class:ui large list');
                foreach ($summary as $condition => $condData) {
                    $content->addChild($condition::getCDOMSummary($condData));
                }
            }
        }
    }

    $contentDIV->addChild($content);
    die($modal->getHtml());
}
die();

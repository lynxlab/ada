<?php

use Lynxlab\ADA\Main\User\ADAUser;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\AMA\AMADB;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

/**
 * ADA course status widget
 *
 * @package     widget
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 *
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        widget
 * @version     0.1
 *
 * supported params you can pass either via XML or php array:
 *
 *  name="courseId"         mandatory,  value: course instance id from which to load the status
 *  name="courseInstanceId" mandatory,  value: course instance id from which to load the status
 *  name="userId"           mandatory,  value: user id from which to load the status
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;
use Lynxlab\ADA\Widgets\Widget;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Servicecomplete\Functions\extractParam;
use function Lynxlab\ADA\Widgets\Functions\prettyPrintHourMin;

/**
 * Common initializations and include files
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    session_write_close();
    extract($_GET);
    if (!isset($widgetMode)) {
        $widgetMode = Widget::ADA_WIDGET_ASYNC_MODE;
    }

    /**
     * checks and inits to be done if this has been called in async mode
     * (i.e. with a get request)
     */
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (
            $widgetMode != Widget::ADA_WIDGET_SYNC_MODE &&
            preg_match("#^" . trim(HTTP_ROOT_DIR, "/") . "($|/.*)#", $_SERVER['HTTP_REFERER']) != 1
        ) {
            die('Only local execution allowed.');
        }
    }
}

/**
 * Your code starts here
 */
try {
    if (!isset($courseId)) {
        throw new Exception(translateFN("Specificare un id di corso"));
    }
    if (!isset($courseInstanceId)) {
        throw new Exception(translateFN("Specificare un id di istanza corso"));
    }
    if (!isset($userId)) {
        throw new Exception(translateFN("Specificare un id studente"));
    }
    /**
     * get the correct testername
     */
    if (!MULTIPROVIDER) {
        if (isset($GLOBALS['user_provider']) && !empty($GLOBALS['user_provider'])) {
            $testerName = $GLOBALS['user_provider'];
        } else {
            throw new Exception(translateFN('Nessun fornitore di servizi &egrave; stato configurato'));
        }
    } else {
        $testerInfo = $GLOBALS['common_dh']->getTesterInfoFromIdCourse($courseId);
        if (!AMADB::isError($testerInfo) && is_array($testerInfo) && isset($testerInfo['puntatore'])) {
            $testerName = $testerInfo['puntatore'];
        }
    } // end if (!MULTIPROVIDER)

    if (isset($testerName)) {
        $tester_dh = AMADataHandler::instance(MultiPort::getDSN($testerName));
        // setting of the global is needed to load the course object
        $GLOBALS['dh'] = $tester_dh;
    } else {
        throw new Exception(translateFN('Spiacente, non so a che fornitore di servizi sei collegato'));
    }

    /**
     * @var ADAUser $userObj
     * @var History $historyObj
     */
    $userObj->setCourseInstanceForHistory($courseInstanceId);
    $historyObj = $userObj->getHistoryInCourseInstance($courseInstanceId);
    $historyObj->getCourseData();
    $nodeSuff = $historyObj->visited_nodes_count > 0 ? 'i' : 'o';
    $nodesTxt = sprintf(translateFN("Hai visitato <strong>%d</strong> nod$nodeSuff su un totale di <strong>%d</strong>"), $historyObj->visited_nodes_count, $historyObj->nodes_count);
    [$spentH, $spentM] = explode(':', $historyObj->historyNodesTimeFN());
    $timeArr = [];
    if ($spentH > 0 || $spentM > 0) {
        $timeArr[] = ['label' => ' ' . translateFN('e hai studiato per') . ' '];
        foreach (prettyPrintHourMin($spentH, $spentM) as $line) {
            $timeArr[] = $line;
        }
    } else {
        $timeArr[] = ['label' => ' ' . translateFN('e hai iniziato adesso a studiare')];
    }

    /**
     * get complete condition to have target time to complete the course, if any
     */
    if ($userObj->getType() == AMA_TYPE_STUDENT && defined('MODULES_SERVICECOMPLETE') && MODULES_SERVICECOMPLETE) {
        $goalTime = 0;
        $completeTimeClassName = 'completeConditionTime';
        $mydh = AMACompleteDataHandler::instance(MultiPort::getDSN($testerName));
        // load the conditionset for this course
        $conditionSet = $mydh->getLinkedConditionsetForCourse($courseId);
        $mydh->disconnect();
        if ($conditionSet instanceof CompleteConditionSet) {
            foreach ($conditionSet->getOperandsForPriority() as $condArr) {
                foreach ($condArr as $k => $condStr) {
                    if (stripos($condStr, $completeTimeClassName) !== false) {
                        $goalTime = max($goalTime, intval(extractParam($condStr)));
                    }
                }
            }
        }
        if ($goalTime > 0) {
            $goalH = floor($goalTime / 60);
            $goalM = $goalTime - floor($goalTime / 60) * 60;
            if ($goalH > 0 || $goalM > 0) {
                $timeArr[] = ['label' => translateFN(', il tempo minimo di completamento del corso è fissato in') . ' '];
                foreach (prettyPrintHourMin($goalH, $goalM) as $line) {
                    $timeArr[] = $line;
                }
            }
        }
    }

    $timeTxt = '';
    if (count($timeArr) > 0) {
        foreach ($timeArr as $tval) {
            if (array_key_exists('label', $tval)) {
                if (array_key_exists('val', $tval)) {
                    $timeTxt .= sprintf($tval['label'], $tval['val']);
                } else {
                    $timeTxt .= $tval['label'];
                }
            }
        }
    }

    $divClass = 'warning large';
    $divMessage = '<i class="checkered flag icon"></i>' . $nodesTxt . $timeTxt;
} catch (Exception $e) {
    $divClass = 'error';
    $divMessage = basename($_SERVER['PHP_SELF']) . ': ' . $e->getMessage();
} finally {
    if (!isset($divMessage)) {
        $divMessage = translateFN('Errore sconosciuto');
        $divClass = 'error';
    }
    $outDIV = CDOMElement::create('div', "class:ui $divClass message");
    $closeIcon = CDOMElement::create('i', 'class:close icon');
    $closeIcon->setAttribute('onclick', 'javascript:$j(this).parents(\'.ui.message\').remove();');
    $outDIV->addChild($closeIcon);
    $errorSpan = CDOMElement::create('span');
    $errorSpan->addChild(new CText($divMessage));
    $outDIV->addChild($errorSpan);
    $output = $outDIV->getHtml();
    /**
     * Common output in sync or async mode
     */
    switch ($widgetMode) {
        case Widget::ADA_WIDGET_SYNC_MODE:
            return $output;
            break;
        case Widget::ADA_WIDGET_ASYNC_MODE:
        default:
            echo $output;
    }
}

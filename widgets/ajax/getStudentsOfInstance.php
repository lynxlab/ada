<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Switcher\Subscription;
use Lynxlab\ADA\Widgets\Widget;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Common initializations and include files
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SUPERTUTOR, AMA_TYPE_SWITCHER];
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

    $output = '';

    $subscriptions = Subscription::findSubscriptionsToClassRoom($courseInstanceId, true);
    if (is_array($subscriptions) && count($subscriptions) > 0) {
        if (isset($filterStatus)) {
            $subscriptions = array_filter($subscriptions, fn ($v) => $filterStatus == $v->getSubscriptionStatus());
        }
        if (count($subscriptions) > 0) {
            usort($subscriptions, fn ($a, $b) => strcasecmp($a->getSubscriberFullname(), $b->getSubscriberFullname()));
            $outCont = CDOMElement::create('div', 'class:widget get-students-of-instance');
            $cssString = [];
            if (isset($styleHeight) && strlen($styleHeight) > 0) {
                $cssString[] = 'height:' . $styleHeight;
            }
            if (isset($styleOverflow) && strlen($styleOverflow) > 0) {
                $cssString[] = 'overflow:' . $styleOverflow;
            }
            if (count($cssString) > 0) {
                $outCont->setAttribute('style', implode(' ', $cssString));
            }
            $outDIV = CDOMElement::create('div', 'class:ui large list');
            foreach ($subscriptions as $s) {
                $sDIV = CDOMElement::create('div', 'class:item');
                $fns = CDOMElement::create('div', 'class:header');
                $fns->addChild(new CText($s->getSubscriberFullname()));
                $sDIV->addChild($fns);
                $extras = [];
                if (isset($showStatus) && $showStatus == 1) {
                    if (strlen($s->subscriptionStatusAsString()) > 0) {
                        $extras[] = $s->subscriptionStatusAsString();
                    }
                }
                if (isset($showEmail) && $showEmail == 1) {
                    if (strlen($s->getSubscriberEmail()) > 0) {
                        if (isset($emailIsLink) && $emailIsLink == 1) {
                            $maillink = CDOMElement::create('a', 'class:dontcapitalize');
                            $maillink->setAttribute('href', 'mailto:' . $s->getSubscriberEmail());
                            $maillink->addChild(new CText($s->getSubscriberEmail()));
                            $extras[] = $maillink->getHtml();
                        } else {
                            $extras[] = $s->getSubscriberEmail();
                        }
                    }
                }
                if (count($extras) > 0) {
                    $sDIV->addChild(new CText(implode('<br/>', $extras)));
                }
                $outDIV->addChild($sDIV);
            }

            if (isset($addHeader) && $addHeader == 1) {
                $h = CDOMElement::create('h3', 'class:ui header');
                $h->addChild(new CText(translateFN('Elenco iscritti al corso')));
                $outCont->addChild($h);
            }
            $outCont->addChild($outDIV);
            $output = $outCont->getHtml();
        }
    }
} catch (Exception $e) {
    $divClass = 'error';
    $divMessage = basename($_SERVER['PHP_SELF']) . ': ' . $e->getMessage();
    $outDIV = CDOMElement::create('div', "class:ui $divClass message");
    $closeIcon = CDOMElement::create('i', 'class:close icon');
    $closeIcon->setAttribute('onclick', 'javascript:$j(this).parents(\'.ui.message\').remove();');
    $outDIV->addChild($closeIcon);
    $errorSpan = CDOMElement::create('span');
    $errorSpan->addChild(new CText($divMessage));
    $outDIV->addChild($errorSpan);
    $output = $outDIV->getHtml();
} finally {
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

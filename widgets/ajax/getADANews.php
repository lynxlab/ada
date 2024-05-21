<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Widgets\Widget;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Widgets\Functions\truncateHtml;

/**
 * Common initializations and include files
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN];
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
$common_dh = AMACommonDataHandler::getInstance();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
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

if (!isset($course_id) || intval($course_id) <= 0) {
    $course_id = PUBLIC_COURSE_ID_FOR_NEWS;
}
if (!isset($showDescription) || !is_numeric($showDescription)) {
    $showDescription = 0;
}
if (!isset($count) || !is_numeric($count)) {
    $count = NEWS_COUNT;
}
if (!isset($orderby) || strlen($orderby) <= 0) {
    $orderby = 'data_creazione DESC';
}

/**
 * get the correct testername
 */
if (!MULTIPROVIDER) {
    if (isset($GLOBALS['user_provider']) && !empty($GLOBALS['user_provider'])) {
        $testerName = $GLOBALS['user_provider'];
    } else {
        $errsmsg = translateFN('Nessun fornitore di servizi &egrave; stato configurato');
    }
} else {
    $testerInfo = $common_dh->getTesterInfoFromIdCourse($course_id);
    if (!AMADB::isError($testerInfo) && is_array($testerInfo) && isset($testerInfo['puntatore'])) {
        $testerName = $testerInfo['puntatore'];
    }
} // end if (!MULTIPROVIDER)

if (isset($testerName)) {
    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($testerName));
    // setting of the global is needed to load the course object
    $GLOBALS['dh'] = $tester_dh;

    // load course
    $courseObj = new Course($course_id);
    $courseOK = false;
    if ($courseObj instanceof Course && $courseObj->isFull()) {
        // it it's public, go on and show contents
        $courseOK = $courseObj->getIsPublic();
        if (!$courseOK && isset($_SESSION['sess_userObj']) && $_SESSION['sess_userObj'] instanceof ADALoggableUser) {
            // if it's not public, check if user is subscribed to course
            $instanceCheck = $tester_dh->getCourseInstanceForThisStudentAndCourseModel($_SESSION['sess_userObj']->getId(), $courseObj->getId(), true);
            if (!AMADB::isError($instanceCheck) && is_array($instanceCheck) && count($instanceCheck) > 0) {
                $goodStatuses = [ADA_STATUS_SUBSCRIBED, ADA_STATUS_COMPLETED, ADA_STATUS_TERMINATED];
                $instance = reset($instanceCheck);
                do {
                    $courseOK = in_array($instance['status'], $goodStatuses);
                } while ((($instance = next($instanceCheck)) !== false) && !$courseOK);
            }
        }
    }
    // courseOK is true either if course is public or the user is subscribed to it
    if ($courseOK) {
        // select nome or empty string (whoever is not null) as title to diplay for the news
        $newscontent = $tester_dh->findCourseNodesList(
            ["COALESCE(if(nome='NULL' OR ISNULL(nome ),NULL, nome), '')", "testo"],
            "tipo IN (" . ADA_LEAF_TYPE . "," . ADA_GROUP_TYPE . ") ORDER BY $orderby LIMIT " . $count,
            $course_id
        );

        // watch out: $newscontent is NOT associative
        $output = '';
        $maxLength = 600;
        if (!AMADB::isError($newscontent) && count($newscontent) > 0) {
            $newsContainer = CDOMElement::create('div', 'class:ui three column divided stackable grid');
            $newsContainer->setAttribute('data-courseID', $course_id);
            $newsRow = CDOMElement::create('div', 'class:equal height row');
            $newsContainer->addChild($newsRow);

            foreach ($newscontent as $num => $aNews) {
                // @author giorgio 01/ott/2013
                // remove unwanted div ids: tabs
                // NOTE: slider MUST be removed BEFORE tabs because tabs can contain slider and not viceversa
                $removeIds = ['slider', 'tabs'];

                if (strlen(trim($aNews[2])) > 0) {
                    $aNewsDIV = CDOMElement::create('div', 'class:column news,id:news-' . ($num + 1));
                    $newsRow->addChild($aNewsDIV);
                    $aNewsTitle = CDOMElement::create('a', 'class:newstitle ui header,href:' . HTTP_ROOT_DIR . '/browsing/view.php?id_course=' .
                        $course_id . '&id_node=' . $aNews[0]);
                    $aNewsTitle->addChild(new CText($aNews[1]));
                    $aNewsDIV->addChild($aNewsTitle);
                    $html = new DOMDocument('1.0', ADA_CHARSET);
                    /**
                     * HTML uses the ISO-8859-1 encoding (ISO Latin Alphabet No. 1) as default per it's specs.
                     * So add a meta the should do the encoding hint, and output some PHP warings as well that
                     * are being suppressed with the @
                     */
                    @$html->loadHTML('<meta http-equiv="content-type" content="text/html; charset=' . ADA_CHARSET . '">' . trim($aNews[2]));

                    foreach ($removeIds as $removeId) {
                        $removeElement = $html->getElementById($removeId);
                        if (!is_null($removeElement)) {
                            $removeElement->parentNode->removeChild($removeElement);
                        }
                    }

                    // output in newstext only the <body> of the generated html
                    $addContinueLink = false;
                    if ($showDescription) {
                        $newstext = '';
                        foreach ($html->getElementsByTagName('body')->item(0)->childNodes as $child) {
                            $newstext .= $html->saveXML($child);
                        }
                        // strip off html tags
                        $newstext = strip_tags($newstext, '<p><a><br>');
                        // check if content is too long...
                        if (strlen(strip_tags($newstext)) > $maxLength) {
                            // cut the content to the first $maxLength characters of words
                            $newstext = truncateHtml($newstext, $maxLength, '');
                            $addContinueLink = true;
                        }

                        $aNewsDIV->addChild(new CText("<p class='newscontent'>" . $newstext . '</p>'));
                    }

                    if ($addContinueLink) {
                        $contLink = CDOMElement::create('a', 'class:continuelink,href:' . HTTP_ROOT_DIR . '/browsing/view.php?id_course=' .
                            $course_id . '&id_node=' . $aNews[0]);
                        $contLink->addChild(new CText(translateFN('Continua...')));
                        $aNewsDIV->addChild($contLink);
                    }
                    // $output .= $aNewsDIV->getHtml();
                }
            }
            $output = $newsContainer->getHtml();
        } else {
            $output = translateFN('Spiacente, non ci sono corsi che hanno l\'id richiesto');
        }
    } else {
        $output = translateFN('Corso non valido o utente non iscritto al corso specificato');
    }
} else {
    $output = translateFN('Spiacente, non so a che fornitore di servizi sei collegato');
}

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

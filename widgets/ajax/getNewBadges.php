<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\Badge;
use Lynxlab\ADA\Module\Badges\RewardedBadge;
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
    } elseif (isset($courseId)) {
        $testerInfo = $GLOBALS['common_dh']->getTesterInfoFromIdCourse($courseId);
        if (!AMADB::isError($testerInfo) && is_array($testerInfo) && isset($testerInfo['puntatore'])) {
            $testerName = $testerInfo['puntatore'];
        }
    } // end if (!MULTIPROVIDER)

    if (!isset($testerName)) {
        throw new Exception(translateFN('Spiacente, non so a che fornitore di servizi sei collegato'));
    }

    if ($userObj->getType() == AMA_TYPE_STUDENT && defined('MODULES_BADGES') && MODULES_BADGES) {
        $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($testerName));
        $findByArr['id_utente'] = $userObj->getId();
        $findByArr['notified'] = 0;
        $findByArr['approved'] = 1;
        if (isset($courseId)) {
            $findByArr['id_corso'] = $courseId;
        }
        if (isset($courseInstanceId)) {
            $findByArr['id_istanza_corso'] = $courseInstanceId;
        }

        $rewardsList = $bdh->findBy('RewardedBadge', $findByArr);
        $outputArr = [];
        if (!AMADB::isError($rewardsList) && is_array($rewardsList) && count($rewardsList) > 0) {
            /** @var RewardedBadge  $reward */
            foreach ($rewardsList as $reward) {
                $badge = $bdh->findBy('Badge', [ 'uuid' => $reward->getBadgeUuid() ]);
                if (!AMADB::isError($badge) && is_array($badge) && count($badge) === 1) {
                    /** @var Badge $badge */
                    $badge = reset($badge);
                    $div = CDOMElement::create('div', 'class:ui blue icon floating message,id:' . $reward->getUuid());
                    $div->setAttribute('data-badge', $badge->getUuid());
                    $closeIcon = CDOMElement::create('i', 'class:close icon');
                    $closeIcon->setAttribute('onclick', 'javascript:$j(this).parents(\'.ui.message\').fadeOut(function(){ $j(this).remove(); });');
                    $div->addChild($closeIcon);

                    $div->addChild(CDOMElement::create('img', 'class:ui small left floated image,style:margin-bottom:0,src:' . $badge->getImageUrl()));

                    $headerMSG = translateFN('Congratulazioni!') . ' ' . translateFN('Hai ottenuto il badge') . ': ' . $badge->getName();
                    $header = CDOMElement::create('div', 'class:header');
                    $header->addChild(new CText($headerMSG));
                    $div->addChild($header);

                    if (strlen($badge->getDescription()) > 0) {
                        $div->addChild(new CText('<p style="margin-top_0.8em; font-weight:700;">' . nl2br($badge->getDescription()) . '</p>'));
                    }

                    array_push($outputArr, $div);
                }
                // set the notified flag to false for the reward
                $reward->setNotified(true);
                $bdh->saveRewardedBadge($reward->toArray());
            }
        }
        $output = implode(PHP_EOL, array_map(fn ($el) => $el->getHtml(), $outputArr));
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
}

if (!isset($output)) {
    $output = '';
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

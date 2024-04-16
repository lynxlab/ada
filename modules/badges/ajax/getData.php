<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\Badge;
use Lynxlab\ADA\Module\Badges\BadgesActions;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(BadgesActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
require_once(ROOT_DIR . '/browsing/include/browsing_functions.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMABadgesDataHandler $GLOBALS['dh']
 */
if (array_key_exists('sess_selected_tester', $_SESSION)) {
    $GLOBALS['dh'] = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
}

$data = ['error' => translateFN('errore sconosciuto')];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

    if (class_exists(AMABadgesDataHandler::MODELNAMESPACE . $params['object'])) {
        if ($params['object'] == 'Badge') {
            $badgesData = [];
            $badgesList = $GLOBALS['dh']->findAll($params['object']);
            if (!AMADB::isError($badgesList)) {
                /**
                 * @var \Lynxlab\ADA\Module\Badges\Badge $badge
                 */
                foreach ($badgesList as $badge) {
                    $links = [];
                    $linksHtml = "";

                    for ($j = 0; $j < 2; $j++) {
                        switch ($j) {
                            case 0:
                                if (BadgesActions::canDo(BadgesActions::EDIT_BADGE)) {
                                    $type = 'edit';
                                    $title = translateFN('Modifica badge');
                                    $link = 'editBadge(\'' . $badge->getUuid() . '\');';
                                }
                                break;
                            case 1:
                                if (BadgesActions::canDo(BadgesActions::TRASH_BADGE)) {
                                    $type = 'delete';
                                    $title = translateFN('Cancella badge');
                                    $link = 'deleteBadge($j(this), \'' . $badge->getUuid() . '\');';
                                }
                                break;
                        }

                        if (isset($type)) {
                            $links[$j] = CDOMElement::create('li', 'class:liactions');

                            $linkshref = CDOMElement::create('button');
                            $linkshref->setAttribute('onclick', 'javascript:' . $link);
                            $linkshref->setAttribute('class', $type . 'Button tooltip');
                            $linkshref->setAttribute('title', $title);
                            $links[$j]->addChild($linkshref);
                            // unset for next iteration
                            unset($type);
                        }
                    }

                    if (!empty($links)) {
                        $linksul = CDOMElement::create('ul', 'class:ulactions');
                        foreach ($links as $link) {
                            $linksul->addChild($link);
                        }
                        $linksHtml = $linksul->getHtml();
                    } else {
                        $linksHtml = '';
                    }

                    $tmpelement = CDOMElement::create('img', 'class:ui tiny image,src:' . $badge->getImageUrl() . '?t=' . time());
                    $badgesData[] = [
                        // NOTE: the timestamp parameter added to the png will prevent caching
                        $tmpelement->getHtml(),
                        $badge->getName(),
                        nl2br($badge->getDescription()),
                        nl2br($badge->getCriteria()),
                        $linksHtml,
                    ];
                }
            } // if (!AMADB::isError($badgesList))
            $data = [ 'data' => $badgesData ];
        } elseif ($params['object'] == 'CourseBadge') {
            $cdh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

            $badgesData = [];
            $badgesList = $GLOBALS['dh']->findBy($params['object'], ['id_corso' => $params['courseId']]);

            if (!AMADB::isError($badgesList)) {
                /**
                 * @var \Lynxlab\ADA\Module\Badges\CourseBadges $cb
                 */
                foreach ($badgesList as $cb) {
                    $links = [];
                    $linksHtml = "";

                    $badge = $GLOBALS['dh']->findBy('Badge', [ 'uuid' => $cb->getBadgeUuid() ]);
                    $cs = $cdh->getCompleteConditionSet($cb->getIdConditionset());
                    if (is_array($badge) && count($badge) == 1) {
                        $badge = reset($badge);
                    }

                    if ($badge instanceof Badge && $cs instanceof CompleteConditionSet) {
                        for ($j = 0; $j < 1; $j++) {
                            switch ($j) {
                                case 0:
                                    if (BadgesActions::canDo(BadgesActions::BADGE_COURSE_TRASH)) {
                                        $type = 'delete';
                                        $title = translateFN('Cancella');
                                        $link = 'deleteCourseBadge($j(this), ' .
                                            htmlspecialchars(json_encode(
                                                [
                                                    'badge_uuid' => $badge->getUuid(),
                                                    'id_corso' => $params['courseId'],
                                                    'id_conditionset' => $cs->getID(),
                                                ]
                                            ), ENT_QUOTES, ADA_CHARSET)
                                        . ');';
                                    }
                                    break;
                            }

                            if (isset($type)) {
                                $links[$j] = CDOMElement::create('li', 'class:liactions');

                                $linkshref = CDOMElement::create('button');
                                $linkshref->setAttribute('onclick', 'javascript:' . $link);
                                $linkshref->setAttribute('class', $type . 'Button tooltip');
                                $linkshref->setAttribute('title', $title);
                                $links[$j]->addChild($linkshref);
                                // unset for next iteration
                                unset($type);
                            }
                        }
                        if (!empty($links)) {
                            $linksul = CDOMElement::create('ul', 'class:ulactions');
                            foreach ($links as $link) {
                                $linksul->addChild($link);
                            }
                            $linksHtml = $linksul->getHtml();
                        } else {
                            $linksHtml = '';
                        }

                        $badgesData[] = [
                            $badge->getName(),
                            $cs->description,
                            $linksHtml,
                        ];
                    }
                }
            } // if (!AMADB::isError($badgesList))
            $data = [ 'data' => $badgesData ];
        }
    } else {
        $data = [ 'error' => translateFN('Oggetto non valido')];
    }
}

if (array_key_exists('error', $data)) {
    header(' ', true, 404);
    $data['data'] = [];
}
header('Content-Type: application/json');
die(json_encode($data));

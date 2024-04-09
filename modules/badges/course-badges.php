<?php

/**
 * @package     badges module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\Badges\BadgesActions;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\CourseBadgeForm;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');

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
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = 'course-badges';

/**
 * @var AMABadgesDataHandler
 */
$GLOBALS['dh'] = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

/**
 * generate HTML for 'New Association' form and the table
 */
$badgesIndexDIV = CDOMElement::create('div', 'id:coursebadgesindex');
$cdh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

if (BadgesActions::canDo(BadgesActions::BADGE_COURSE_LINK)) {
    $badges = $GLOBALS['dh']->findAll('Badge');
    $rulesList = $cdh->get_completeConditionSetList();
    $cbForm = new CourseBadgeForm($badges, $rulesList, 'cbform', null, $courseObj->getId());
    $badgesIndexDIV->addChild(new CText($cbForm->toSemanticUI()->getHtml()));
}

$badgesIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));

$labels = [
    translateFN('badge'), translateFN('regola di acquisizione'), translateFN('azioni'),
];

$badgesTable = BaseHtmlLib::tableElement(
    'id:completeCourseBadgesList',
    $labels,
    [],
    '',
    translateFN('Elenco dei badges associati al corso') . ' ' . $courseObj->getTitle()
);
$badgesTable->setAttribute('class', $badgesTable->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
$badgesIndexDIV->addChild($badgesTable);

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => ucfirst(translateFN('badges')) . ' &gt; ' . translateFN('Associa badges al corso') . ' ' . $courseObj->getTitle(),
    'data' => $badgesIndexDIV->getHtml(),
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_UI,
    MODULES_BADGES_PATH . '/js/dropzone.js',
    JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
    MODULES_BADGES_PATH . '/layout/tooltips.css',
];

$optionsAr['onload_func'] = 'initDoc(' . $courseObj->getId() . ');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

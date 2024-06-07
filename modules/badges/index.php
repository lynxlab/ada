<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Badges\BadgesActions;

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
[$allowedUsersAr, $neededObjAr] = array_values(BadgesActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = 'badges';

/**
 * generate HTML for 'New Badge' button and the empty table
 */
$badgesIndexDIV = CDOMElement::create('div', 'id:badgesindex');

if (BadgesActions::canDo(BadgesActions::NEW_BADGE)) {
    $newButton = CDOMElement::create('button');
    $newButton->setAttribute('class', 'newButton top');
    $newButton->setAttribute('title', translateFN('Clicca per creare un nuovo badge'));
    $newButton->setAttribute('onclick', 'javascript:editBadge(null);');
    $newButton->addChild(new CText(translateFN('Nuovo Badge')));
    $badgesIndexDIV->addChild($newButton);
}

$badgesIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));

$labels = [ '&nbsp;',
    translateFN('nome'), translateFN('descrizione'),
    translateFN('criterio'), translateFN('azioni'),
];

$badgesTable = BaseHtmlLib::tableElement('id:completeBadgesList', $labels, [], '', translateFN('Elenco dei badges'));
$badgesTable->setAttribute('class', $badgesTable->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
$badgesIndexDIV->addChild($badgesTable);

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => ucfirst(translateFN('badges')),
    'data' => $badgesIndexDIV->getHtml(),
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_UI,
    JS_VENDOR_DIR . '/dropzone/dist/min/dropzone.min.js',
    JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
    MODULES_BADGES_PATH . '/layout/tooltips.css',
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

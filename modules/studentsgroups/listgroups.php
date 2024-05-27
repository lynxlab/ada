<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\StudentsGroups\Groups;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsActions;

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
[$allowedUsersAr, $neededObjAr] = array_values(StudentsGroupsActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = Utilities::whoami();

/**
 * generate HTML for 'New Group' button and the empty table
 */
$groupsIndexDIV = CDOMElement::create('div', 'id:groupsindex');

if (StudentsGroupsActions::canDo(StudentsGroupsActions::NEW_GROUP)) {
    $newButton = CDOMElement::create('button');
    $newButton->setAttribute('class', 'newButton top');
    $newButton->setAttribute('title', translateFN('Clicca per creare un nuovo gruppo'));
    $newButton->setAttribute('onclick', 'javascript:editGroup(null);');
    $newButton->addChild(new CText(translateFN('Nuovo Gruppo')));
    $groupsIndexDIV->addChild($newButton);
}

$groupsIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));

$labels = [
    '&nbsp;',
    translateFN('nome'),
];

foreach (Groups::getCustomFieldLbl() as $cLbl) {
    array_push($labels, translateFN($cLbl));
}

$labels[] = translateFN('azioni');

$groupsTable = BaseHtmlLib::tableElement('id:completeGropusList', $labels, [], '', translateFN('Elenco dei gruppi'));
$groupsTable->setAttribute('class', $groupsTable->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
$groupsIndexDIV->addChild($groupsTable);

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => ucfirst(translateFN('Gruppi di Studenti')),
    'data' => $groupsIndexDIV->getHtml(),
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_UI,
    MODULES_STUDENTSGROUPS_PATH . '/js/dropzone.js',
    JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

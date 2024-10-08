<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
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
[$allowedUsersAr, $neededObjAr] = array_values(GdprActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = Utilities::whoami();

try {
    if (!GdprActions::canDo(GdprActions::LIST_POLICIES)) {
        throw new GdprException(translateFN("Solo un utente abilitato può vedere tutte le politiche di privacy"));
    }

    $canEdit = GdprActions::canDo(GdprActions::EDIT_POLICY);

    $tableID = 'list_policies';
    $dataForJS = ['canEdit' => $canEdit];

    $table = BaseHtmlLib::tableElement('id:' . $tableID . ',class:hover row-border display ' . ADA_SEMANTICUI_TABLECLASS, GdprPolicy::getTableHeader($canEdit), []);
    $data = $table->getHtml();

    $layout_dataAr['JS_filename'] = [
        JQUERY_DATATABLE,
        SEMANTICUI_DATATABLE,
        JQUERY_DATATABLE_DATE,
    ];

    $layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        JQUERY_DATATABLE_CSS,
        SEMANTICUI_DATATABLE_CSS,
    ];

    $optionsAr['onload_func'] = 'initDoc(\'' . $tableID . '\',' . htmlentities(json_encode($dataForJS, JSON_FORCE_OBJECT), ENT_COMPAT, ADA_CHARSET) . ');';
} catch (Exception $e) {
    $message = CDOMElement::create('div', 'class:ui icon error message');
    $message->addChild(CDOMElement::create('i', 'class:attention icon'));
    $mcont = CDOMElement::create('div', 'class:content');
    $mheader = CDOMElement::create('div', 'class:header');
    $mheader->addChild(new CText(translateFN('Errore politiche privacy GDPR')));
    $span = CDOMElement::create('span');
    $span->addChild(new CText($e->getMessage()));
    $mcont->addChild($mheader);
    $mcont->addChild($span);
    $message->addChild($mcont);
    $data = $message->getHtml();
    $optionsAr = null;
}

$content_dataAr = [
    'user_name' => $userObj->getFirstName(),
    'user_homepage' => $userObj->getHomePage(),
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'data' => $data,
    'title' => translateFN('Elenco Politiche Privacy GDPR'),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

ini_set('display_errors', '0');
error_reporting(E_ALL);
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
[$allowedUsersAr, $neededObjAr] = array_values(GdprActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = whoami();

$showAll = array_key_exists('showall', $_REQUEST) && intval($_REQUEST['showall']) === 1;
$showUUID = array_key_exists('uuid', $_REQUEST);

try {
    if (intval($_SESSION['sess_userObj']->getType()) === AMA_TYPE_VISITOR && $showUUID !== true) {
        throw new GdprException(translateFN("L'utente non registrato può solo vedere il suo numero di pratica"));
    } elseif ($showAll === true && !GdprActions::canDo(GdprActions::ACCESS_ALL_REQUESTS)) {
        throw new GdprException(translateFN("Solo un utente abilitato può vedere tutte le richieste"));
    }

    if ($showUUID && !UUid::isValid(trim($_REQUEST['uuid']))) {
        throw new GdprException(translateFN("Numero di pratica non valido"));
    }

    $tableID = 'list_requests';
    $dataForJS = [];
    if ($showAll) {
        $dataForJS['showall'] = intval($showAll);
    }
    if ($showUUID) {
        $dataForJS['uuid'] = trim($_REQUEST['uuid']);
    }

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

    $requestClass = 'Lynxlab\ADA\Module\GDPR\GdprRequest';
    if ($showAll) {
        $layout_dataAr['JS_filename'][]  = MODULES_GDPR_PATH . '/js/jeditable-2.0.1/jquery.jeditable.min.js';
    }

    $table = BaseHtmlLib::tableElement('id:' . $tableID . ',class:hover row-border display ' . ADA_SEMANTICUI_TABLECLASS, $requestClass::getTableHeader($showAll), []);
    $data = $table->getHtml();

    $optionsAr['onload_func'] = 'initDoc(\'' . $tableID . '\',' . htmlentities(json_encode($dataForJS), ENT_COMPAT, ADA_CHARSET) . ');';
} catch (Exception $e) {
    $message = CDOMElement::create('div', 'class:ui icon error message');
    $message->addChild(CDOMElement::create('i', 'class:attention icon'));
    $mcont = CDOMElement::create('div', 'class:content');
    $mheader = CDOMElement::create('div', 'class:header');
    $mheader->addChild(new CText(translateFN('Errore elenco richieste GDPR')));
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
    'title' => translateFN('Elenco Richieste GDPR'),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

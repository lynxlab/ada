<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use Lynxlab\ADA\Module\GDPR\GdprPolicyForm;

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

try {
    if (!GdprActions::canDo(GdprActions::EDIT_POLICY)) {
        throw new GdprException(translateFN("Solo un utente abilitato può modificare le politiche di privacy"));
    }

    if (isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
        $policy = (new GdprAPI())->findBy('GdprPolicy', ['policy_content_id' => intval($_REQUEST['id'])], null, AMAGdprDataHandler::getPoliciesDB());
        if (count($policy) > 0) {
            $policy = reset($policy);
        } else {
            throw new GdprException('Impossible trovare la policy da modificare');
        }
    } else {
        $policy = new GdprPolicy();
    }

    $form = new GdprPolicyForm($policy, 'gdprpolicy', null);
    $data = $form->withSubmit()->toSemanticUI()->getHtml();
    $optionsAr['onload_func'] = 'initDoc(\'' . $form->getName() . '\');';
} catch (Exception $e) {
    $message = CDOMElement::create('div', 'class:ui icon error message');
    $message->addChild(CDOMElement::create('i', 'class:attention icon'));
    $mcont = CDOMElement::create('div', 'class:content');
    $mheader = CDOMElement::create('div', 'class:header');
    $mheader->addChild(new CText(translateFN('Errore modifica policy GDPR')));
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
    'title' => translateFN('Modifca policy GDPR'),
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_NO_CONFLICT,
        '../../external/fckeditor/fckeditor.js',
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

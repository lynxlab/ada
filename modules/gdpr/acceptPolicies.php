<?php

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprAcceptPoliciesForm;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprLoginRepeaterForm;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;

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
    $dataAr = [];
    $submitTo = HTTP_ROOT_DIR . '/index.php';
    $data = '';
    $userId = null;
    if (array_key_exists(GdprPolicy::SESSIONKEY, $_SESSION)) {
        if (array_key_exists('redirectURL', $_SESSION[GdprPolicy::SESSIONKEY])) {
            $dataAr['redirectURL'] = $_SESSION[GdprPolicy::SESSIONKEY]['redirectURL'];
            unset($_SESSION[GdprPolicy::SESSIONKEY]['redirectURL']);
        }
        if (array_key_exists('loginRepeaterSubmit', $_SESSION[GdprPolicy::SESSIONKEY])) {
            $submitTo = HTTP_ROOT_DIR . '/' . $_SESSION[GdprPolicy::SESSIONKEY]['loginRepeaterSubmit'];
            unset($_SESSION[GdprPolicy::SESSIONKEY]['loginRepeaterSubmit']);
        }

        if (array_key_exists('userId', $_SESSION[GdprPolicy::SESSIONKEY])) {
            $userId = $_SESSION[GdprPolicy::SESSIONKEY]['userId'];
            unset($_SESSION[GdprPolicy::SESSIONKEY]['userId']);
        }
    } else {
        $userId = $_SESSION['sess_userObj']->getId();
        $message = CDOMElement::create('div', 'class:ui icon warning message');
        $message->addChild(CDOMElement::create('i', 'class:attention icon'));
        $mcont = CDOMElement::create('div', 'class:content');
        $mheader = CDOMElement::create('div', 'class:header');
        $mheader->addChild(new CText(translateFN('ATTENZIONE! Al terrmine del salvataggio verrà effettuata la disconnessione dalla piattaforma')));
        $mcont->addChild($mheader);
        $message->addChild($mcont);
        $data = $message->getHtml();
    }

    $gdprApi = new GdprAPI();

    $loginForm = new GdprLoginRepeaterForm('loginRepeater', $submitTo, $dataAr);
    $policiesForm = new GdprAcceptPoliciesForm('acceptPolicies', null, [
        'policies' => $gdprApi->getPublishedPolicies(),
        'userAccepted' => $gdprApi->getUserAcceptedPolicies($userId),
        'userId' => $userId,
    ]);
    $optionsAr['onload_func'] = 'initDoc(\'loginRepeater\',\'acceptPolicies\');';

    $data .= $loginForm->getHtml() . $policiesForm->toSemanticUI()->getHtml();
} catch (\Exception $e) {
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
    'status' => $status,
    'data' => $data,
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

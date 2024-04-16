<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\FormMail\AMAFormmailDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_STUDENT, AMA_TYPE_SUPERTUTOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_TUTOR => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
        AMA_TYPE_STUDENT => ['layout'],
        AMA_TYPE_SUPERTUTOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = 'formmail';

$GLOBALS['dh'] = AMAFormmailDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$helpTypes = $GLOBALS['dh']->getHelpTypes($userObj->getType());
$helpTypesHTML = '';

if (!AMADB::isError($helpTypes) && is_array($helpTypes) && count($helpTypes) > 0) {
    foreach ($helpTypes as $helpType) {
        $helpTypesDOM = CDOMElement::create('div', 'class:helptype item');
        $helpTypesDOM->setAttribute('data-value', $helpType[AMAFormmailDataHandler::$PREFIX . 'helptype_id']);
        $helpTypesDOM->setAttribute('data-email', $helpType['recipient']);
        $helpTypesDOM->addChild(new CText(translateFN($helpType['description'])));
        $helpTypesHTML .= $helpTypesDOM->getHtml();
    }
}


$content_dataAr = [
    'user_name' => $userObj->getFirstName(),
    'user_homepage' => $userObj->getHomePage(),
    'helptypes' => (strlen($helpTypesHTML) > 0 ? $helpTypesHTML : null),
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => translateFN('formmail'),
];

$layout_dataAr['JS_filename'] = [
    MODULES_FORMMAIL_PATH . '/js/dropzone.js',
];

$optionsAr['onload_func'] = 'initDoc(' . $userObj->getId() . ');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

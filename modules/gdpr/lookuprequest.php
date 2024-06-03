<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprLookupRequestForm;

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

/**
 * sample valid but not found uuid is: Uuid:NIL (00000000-0000-0000-0000-000000000000)
 */

$form = new GdprLookupRequestForm('gdprrequestlookup');
$data = $form->withSubmit()->toSemanticUI()->getHtml();
$optionsAr['onload_func'] = 'initDoc(\'' . $form->getName() . '\',\'checkimg\');';


$content_dataAr = [
    'user_name' => $userObj->getFirstName(),
    'user_homepage' => $userObj->getHomePage(),
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'data' => $data,
    'title' => translateFN('Cerca Richiesta GDPR'),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * This module is responsible for generating client id and client secret pairs for each user
 * requesting them. Typically, this is done by a switcher. The generated (or retreived from the
 * common DB) pairs are then used to get an access token by the ada-php-sdk (or by the developer
 * not using the sdk by itself).
 *
 * curl examples for getting the access token and using it to obtain a resource are:
 *
 * # using HTTP Basic Authentication
 * $ curl -u TestClient:TestSecret https://api.mysite.com/token -d 'grant_type=client_credentials'
 * # using POST Body
 * $ curl https://api.mysite.com/token -d 'grant_type=client_credentials&client_id=TestClient&client_secret=TestSecret'
 */

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
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

$self = whoami();

/**
 * TODO: Add your own code here
 */

$container = CDOMElement::create('div', 'id:gettokenpage');

$getButton = CDOMElement::create('button', 'id:getButton');
$getButton->setAttribute('onclick', 'javascript:getAppSecretAndID(' . $userObj->getId() . ');');
$getButton->addChild(new CText(translateFN('Generate API App ID and Secret NOW!')));

$output = CDOMElement::create('div', 'id:outputtoken');
$output->setAttribute('style', 'display:none');

$container->addChild($getButton);
$container->addChild($output);

$data = $container->getHtml();

/**
 * include proper jquery ui css file depending on wheter there's one
 * in the template_family css path or the default one
*/
if (!is_dir(MODULES_APPS_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui')) {
    $layout_dataAr['CSS_filename'] = [
            JQUERY_UI_CSS,
    ];
} else {
    $layout_dataAr['CSS_filename'] = [
            MODULES_APPS_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui/jquery-ui-1.10.3.custom.min.css',
    ];
}

$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'title' => translateFN('oauth2'),
        'data' => $data,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

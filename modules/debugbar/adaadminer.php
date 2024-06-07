<?php

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Module\DebugBar\ADAAdminerHelper;
use Lynxlab\ADA\Module\DebugBar\ADAAdminerPlugin;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_ADMIN, AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_ADMIN => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

$client = DataValidator::checkInputValues('client', 'Testername', INPUT_GET, null);
if ($client !== null) {
    $_SESSION['x-adaadminer-client'] = $client;
} else {
    $client = $_SESSION['x-adaadminer-client'];
}

function adminer_object()
{
    // read client from "outside"
    global $client;
    $plugins = [];
    return new ADAAdminerPlugin($plugins, $client);
}

if (!isset($_GET['db'])) {
    $postData = ADAAdminerHelper::getPOSTData($client);
    if (!empty($postData)) {
        $_POST['auth'] = $postData;
    }
}

require_once './adminer/adminer.php';

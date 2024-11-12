<?php

/**
 * Base config file
 */

use DebugBar\OpenHandler;
use Lynxlab\ADA\Module\DebugBar\ADADebugBar;

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
$neededObjAr = [
    AMA_TYPE_ADMIN => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_SUPERTUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
];
$allowedUsersAr = array_keys($neededObjAr);

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
session_write_close();

$dbg = ADADebugBar::getInstance();
$openHandler = new OpenHandler($dbg);
$response = $openHandler->handle(null, false);

// buffer the output, close the connection with the browser and run a "background" task
ob_end_clean();
ignore_user_abort(true);
// capture output
ob_start();
echo $response;
// these headers tell the browser to close the connection
header("HTTP/1.1 200 OK");
// flush all output
ob_end_flush();
flush();
@ob_end_clean();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// clear the storage after 5 sec.
sleep(5);
$dbg->getStorage()->clear();
die();

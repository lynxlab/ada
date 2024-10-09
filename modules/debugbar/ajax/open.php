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

$openHandler = new OpenHandler(ADADebugBar::getInstance());
$openHandler->handle();
die();

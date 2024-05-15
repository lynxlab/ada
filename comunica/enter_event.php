<?php

use Lynxlab\ADA\Main\DataValidator;

use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout','user','course'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT         => [],
  AMA_TYPE_TUTOR => [],
];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();

$event = DataValidator::checkInputValues('event', 'Value', INPUT_GET);

if ($event == ADA_CHAT_EVENT) {
    header('Location: ' . HTTP_ROOT_DIR . '/comunica/chat.php');
    exit();
} elseif ($event == ADA_VIDEOCHAT_EVENT) {
    header('Location: ' . HTTP_ROOT_DIR . '/comunica/videochat.php');
    exit();
} else {
    // dovrebbe chiudersi
}

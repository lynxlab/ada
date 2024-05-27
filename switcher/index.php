<?php

/**
 * Redirect.
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\User\ADAGenericUser;

/**
 * Base config file
 */
require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */


$variableToClearAR = ['node', 'layout', 'user', 'course'];
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
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = 'index';

$userObj = DBRead::readUser($sess_id_user);
if ($userObj instanceof ADAGenericUser) {
    $homepage = $userObj->getHomePage();
    header('Location: ' . $homepage);
    exit();
}

header('Location: ' . HTTP_ROOT_DIR);
exit();

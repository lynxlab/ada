<?php

/**
 * SLIDEIMPORT MODULE.
 *
 * @package        slideimport module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           slideimport
 * @version        0.1
 */

use Lynxlab\ADA\Main\Helper\BrowsingHelper;

/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR   => ['layout'],
        AMA_TYPE_TUTOR    => ['layout'],
        AMA_TYPE_STUDENT  => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sessionVar']) && strlen(trim($_POST['sessionVar'])) > 0) {
    $sessionVar = trim($_POST['sessionVar']);
    if (isset($_SESSION[$sessionVar]['filename']) && strlen($_SESSION[$sessionVar]['filename']) > 0) {
        unlink($_SESSION[$sessionVar]['filename']);
        unset($_SESSION[$sessionVar]['filename']);
    }
}

<?php

/**
 * LOGIN MODULE
 *
 * @package     login module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2015-2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Module\Login\AMALoginDataHandler;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_LOGIN', true);
define('MODULES_LOGIN_NAME', join('', $moduledir->toArray()));
define('MODULES_LOGIN_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_LOGIN_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

/**
 * To prevent `module_login_history_login` table to grow up forever
 * limit here how many logins per provider ADA must keep in history
 */
AMALoginDataHandler::$MODULES_LOGIN_HISTORY_LIMIT = 10;

/**
 * default name implementing default login the first entry in login
 * provider of this class cannot be deleted and cannot be disabled if
 * it's the only login provider in the control panel
 */
AMALoginDataHandler::$MODULES_LOGIN_DEFAULT_LOGINPROVIDER = 'adaLogin';

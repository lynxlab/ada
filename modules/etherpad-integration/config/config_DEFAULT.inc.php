<?php

/**
 * @package     etherpad module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_ETHERPAD', true);
define('MODULES_ETHERPAD_NAME', join('', $moduledir->toArray()));
define('MODULES_ETHERPAD_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_ETHERPAD_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

/**
 * constants for etherpad API host, port and apikey
 * In a non multiprovider environment,
 * each provider must define its own somewhere in its config files
 */
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_HOST'))) {
    define('MODULES_ETHERPAD_HOST', getenv('ETHERPAD_HOST') ?: 'https://etherpad.ada72.lynxlab.com');
}
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_PORT'))) {
    define('MODULES_ETHERPAD_PORT', getenv('ETHERPAD_PORT') ?: '');
}
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_APIBASEURL'))) {
    define('MODULES_ETHERPAD_APIBASEURL', getenv('ETHERPAD_APIBASEURL') ?: 'api');
}
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_APIKEY'))) {
    define('MODULES_ETHERPAD_APIKEY', getenv('ETHERPAD_APIKEY') ?: '6668fd3f4172007d8f73b29ed6603c5525d05898958fbede1f10102ce6b81a2b');
}
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_INSTANCEPAD'))) {
    define('MODULES_ETHERPAD_INSTANCEPAD', getenv('ETHERPAD_INSTANCEPAD') ?: true);
}
if (MULTIPROVIDER || (!MULTIPROVIDER && !defined('MODULES_ETHERPAD_NODEPAD'))) {
    define('MODULES_ETHERPAD_NODEPAD', getenv('ETHERPAD_NODEPAD') ?: true);
}

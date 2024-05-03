<?php

/**
 * @package     bigbluebutton module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_BIGBLUEBUTTON', true);
define('MODULES_BIGBLUEBUTTON_NAME', join('', $moduledir->toArray()));
define('MODULES_BIGBLUEBUTTON_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_BIGBLUEBUTTON_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

/**
 * NOTE
 * BBB_SERVER_BASE_URL and BBB_SECRET must be defined in:
 * comunica/include/BigBlueButton.config.inc.php
 */

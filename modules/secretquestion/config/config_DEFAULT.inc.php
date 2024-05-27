<?php

/**
 * @package     secretquestion module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_SECRETQUESTION', true);
define('MODULES_SECRETQUESTION_NAME', join('', $moduledir->toArray()));
define('MODULES_SECRETQUESTION_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_SECRETQUESTION_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

/**
 * set to true to have a case-insesitive answer comapre
 */
define('SECRETQUESTION_CI_ANSWER', true);

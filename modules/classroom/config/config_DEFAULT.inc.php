<?php

/**
 * CLASSROOM MODULE.
 *
 * @package			classroom module
 * @author			Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright		Copyright (c) 2014, Lynx s.r.l.
 * @license			http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link			classroom
 * @version			0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_CLASSROOM', true);
define('MODULES_CLASSROOM_NAME', join('', $moduledir->toArray()));
define('MODULES_CLASSROOM_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_CLASSROOM_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

define('MODULES_CLASSROOM_EDIT_VENUE',            1); // edit venue action code
define('MODULES_CLASSROOM_EDIT_CLASSROOM',        2); // edit classroom action code

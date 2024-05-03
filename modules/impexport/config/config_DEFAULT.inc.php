<?php

/**
 * @package     import/export course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Course\Course;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_IMPEXPORT', true);
define('MODULES_IMPEXPORT_NAME', join('', $moduledir->toArray()));
define('MODULES_IMPEXPORT_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_IMPEXPORT_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

define('XML_EXPORT_FILENAME', 'ada_export.xml');
define('MODULES_IMPEXPORT_LOGDIR', ROOT_DIR . '/log/impexport/');

define('MODULES_IMPEXPORT_REPOBASEDIR', Course::MEDIA_PATH_DEFAULT);
define('MODULES_IMPEXPORT_REPODIR', 'exported');

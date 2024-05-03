<?php

/**
 * @package     zoom integration module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_ZOOMCONF', true);
define('MODULES_ZOOMCONF_NAME', join('', $moduledir->toArray()));
define('MODULES_ZOOMCONF_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_ZOOMCONF_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

/**
 * NOTE
 * ZOOMCONF_SERVER, ZOOMCONF_APIKEY and ZOOMCONF_APISECRET must be defined in:
 * comunica/include/ZoomConf.config.inc.php
 */

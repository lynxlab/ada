<?php

/**
 * @package     forked-paths module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsNode;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_FORKEDPATHS', true);
define('MODULES_FORKEDPATHS_NAME', join('', $moduledir->toArray()));
define('MODULES_FORKEDPATHS_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_FORKEDPATHS_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

ForkedPathsNode::$REMOVE_CHILDREN_FROM_INDEX = true;

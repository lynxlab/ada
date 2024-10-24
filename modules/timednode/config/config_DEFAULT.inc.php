<?php

/**
 * @package     timednode module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\Timednode\EventSubscriber;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_TIMEDNODE', true);
define('MODULES_TIMEDNODE_NAME', join('', $moduledir->toArray()));
define('MODULES_TIMEDNODE_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_TIMEDNODE_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

define('MODULES_TIMEDNODE_DEFAULTDURATION', 0);

if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
    ADAEventDispatcher::getInstance()->addSubscriber(new EventSubscriber());
} else {
    throw new Exception(
        json_encode([
            'header' => MODULES_TIMEDNODE_NAME . ' module will not work because Event dispatcher module is not working!',
            'message' => 'Please install <code>Event dispatcher</code> module first',
        ])
    );
}

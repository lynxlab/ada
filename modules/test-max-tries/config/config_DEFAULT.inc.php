<?php

/**
 * @package     maxtries module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\MaxTries\EventSubscriber;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_MAXTRIES', true);
define('MODULES_MAXTRIES_NAME', join('', $moduledir->toArray()));
define('MODULES_MAXTRIES_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_MAXTRIES_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

// 0 means no MAXTRIES (module will not do its logic)
define('MODULES_MAXTRIES_COUNT', 0);

if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
    if (ModuleLoaderHelper::isLoaded('test') && MODULES_MAXTRIES_COUNT > 0) {
        ADAEventDispatcher::getInstance()->addSubscriber(new EventSubscriber());
    }
} else {
    throw new Exception(
        json_encode([
            'header' => MODULES_MAXTRIES_NAME . ' module will not work because Event dispatcher module is not working!',
            'message' => 'Please install <code>Event dispatcher</code> module first',
        ])
    );
}

<?php

/**
 * @package     cloneinstance module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2022, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\CloneInstance\EventSubscriber;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_CLONEINSTANCE', true);
define('MODULES_CLONEINSTANCE_NAME', join('', $moduledir->toArray()));
define('MODULES_CLONEINSTANCE_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_CLONEINSTANCE_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
    ADAEventDispatcher::getInstance()->addSubscriber(new EventSubscriber());
} else {
    throw new Exception(
        json_encode([
            'header' => 'Clone Instance module will not work because Event dispatcher module is not working!',
            'message' => 'Please install <code>Event dispatcher</code> module first',
        ])
    );
}

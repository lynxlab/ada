<?php

/**
 * @package     studentsgroups module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\StudentsGroups\EventSubscriber;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_STUDENTSGROUPS', true);
define('MODULES_STUDENTSGROUPS_NAME', join('', $moduledir->toArray()));
define('MODULES_STUDENTSGROUPS_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_STUDENTSGROUPS_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

// how many fields are expected in each imported csv file row
define('MODULES_STUDENTSGROUPS_FIELDS_IN_CSVROW', 4);

if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
    ADAEventDispatcher::getInstance()->addSubscriber(new EventSubscriber());
} else {
    throw new Exception(
        json_encode([
            'header' => MODULES_STUDENTSGROUPS_NAME . ' module will not work because Event dispatcher module is not working!',
            'message' => 'Please install <code>Event dispatcher</code> module first',
        ])
    );
}

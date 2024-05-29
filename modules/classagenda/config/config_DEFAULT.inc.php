<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package        classagenda module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2014, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classagenda
 * @version        0.1
 */

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\Classagenda\EventSubscriber;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_CLASSAGENDA', true);
define('MODULES_CLASSAGENDA_NAME', join('', $moduledir->toArray()));
define('MODULES_CLASSAGENDA_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_CLASSAGENDA_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

define('MODULES_CLASSAGENDA_EDIT_CAL', 1); // edit calendar action code
define('MODULES_CLASSAGENDA_DO_ROLLCALL', 2); // do the class roll call action code
define('MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY', 3); // roll call history action code

define('MODULES_CLASSAGENDA_ALL_INSTANCES', 1); // filter all course instances
define('MODULES_CLASSAGENDA_STARTED_INSTANCES', 2); // filter started course instances
define('MODULES_CLASSAGENDA_NONSTARTED_INSTANCES', 3); // filter non started course instances
define('MODULES_CLASSAGENDA_CLOSED_INSTANCES', 4); // filter closed course instances

define('MODULES_CLASSAGENDA_EMAIL_REMINDER', true); // false to disable emailed reminders

// html template for the event reminder e-mail
define('MODULES_CLASSAGENDA_REMINDER_HTML', MODULES_CLASSAGENDA_PATH . '/doc/reminderTemplate.htm');

define('MODULES_CLASSAGENDA_LOGDIR', ROOT_DIR . '/log/classagenda/');
define('MODULES_CLASSAGENDA_EMAILS_PER_HOUR', 60); // numer of emails per hour to be sent out

define('PDF_EXPORT_FOOTER', 'ADA è un software opensource rilasciato sotto licenza GPL © Lynx s.r.l. - Roma');

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

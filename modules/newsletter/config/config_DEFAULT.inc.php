<?php

/**
 * NEWSLETTER MODULE.
 *
 * @package     newsletter module
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            newsletter
 * @version     0.1
 */

use Jawira\CaseConverter\Convert;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_NEWSLETTER', true);
define('MODULES_NEWSLETTER_NAME', join('', $moduledir->toArray()));
define('MODULES_NEWSLETTER_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_NEWSLETTER_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

define('DEFAULT_FILTER_SENTENCE', 'Imposta i filtri per sapere a chi verr&agrave; inviata la newsletter');
define('MODULES_NEWSLETTER_LOGDIR', ROOT_DIR . '/log/newsletter/');
define('MODULES_NEWSLETTER_EMAILS_PER_HOUR', 60); // numer of emails per hour to be sent out
define('MODULES_NEWSLETTER_DEFAULT_EMAIL_ADDRESS', ADA_NOREPLY_MAIL_ADDRESS);

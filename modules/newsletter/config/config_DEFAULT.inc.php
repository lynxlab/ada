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

try {
    if (!@include_once(MODULES_NEWSLETTER_PATH . '/vendor/autoload.php')) {
        // @ - to suppress warnings,
        throw new Exception(
            json_encode([
                'header' => 'Newsletter module will not work because autoload file cannot be found!',
                'message' => 'Please run <code>composer install</code> in the module subdir',
            ])
        );
    } else {
        // MODULE'S OWN DEFINES HERE
        // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
        define('DEFAULT_FILTER_SENTENCE', 'Imposta i filtri per sapere a chi verr&agrave; inviata la newsletter');
        define('MODULES_NEWSLETTER_LOGDIR', ROOT_DIR . '/log/newsletter/');
        define('MODULES_NEWSLETTER_EMAILS_PER_HOUR', 60); // numer of emails per hour to be sent out
        define('MODULES_NEWSLETTER_DEFAULT_EMAIL_ADDRESS', ADA_NOREPLY_MAIL_ADDRESS);
        // phpcs:enable
        return true;
    }
} catch (Exception $e) {
    $text = json_decode($e->getMessage(), true);
    // populating $_GET['message'] is a dirty hack to force the error message to appear in the home page at least
    if (!isset($_GET['message'])) {
        $_GET['message'] = '';
    }
    $_GET['message'] .= '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
    if (array_key_exists('header', $text) && strlen($text['header']) > 0) {
        $_GET['message'] .= '<div class="header">' . $text['header'] . '</div>';
    }
    if (array_key_exists('message', $text) && strlen($text['message']) > 0) {
        $_GET['message'] .= '<p>' . $text['message'] . '</p>';
    }
    $_GET['message'] .= '</div></div>';
}
return false;

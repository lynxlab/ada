<?php

/**
 * FORMMAIL MODULE.
 *
 * @package        formmail module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           formmail
 * @version        0.1
 */

try {
    if (!@include_once(MODULES_FORMMAIL_PATH . '/vendor/autoload.php')) {
        // @ - to suppress warnings,
        throw new Exception(
            json_encode([
                'header' => 'FormMail module will not work because autoload file cannot be found!',
                'message' => 'Please run <code>composer install</code> in the module subdir',
            ])
        );
    } else {
        if (!function_exists('menuEnableFormMail')) {
            /**
             * callback function used to check if formmail menu item is to be enabled
             *
             * @param array $allowedTypes optional, can be defined in the DB enabledON field or in the function body
             *
             * @return true if menu must be enabled
             */
            function menuEnableFormMail($allowedTypes = null)
            {
                if (is_null($allowedTypes)) {
                    /**
                     * Add here user types for which the formmail menu must be enabled
                     */
                    $allowedTypes = [AMA_TYPE_SWITCHER];
                }

                return defined('MODULES_FORMMAIL') && MODULES_FORMMAIL && isset($_SESSION['sess_userObj']) && in_array($_SESSION['sess_userObj']->getType(), $allowedTypes);
            }
        }
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

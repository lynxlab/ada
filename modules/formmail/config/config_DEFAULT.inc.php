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

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;

// MODULE'S OWN DEFINES HERE

$moduledir = new Convert(str_replace(MODULES_DIR . DIRECTORY_SEPARATOR, '', realpath(__DIR__ . '/..')));

define('MODULES_FORMMAIL', true);
define('MODULES_FORMMAIL_NAME', join('', $moduledir->toArray()));
define('MODULES_FORMMAIL_PATH', MODULES_DIR . DIRECTORY_SEPARATOR . $moduledir->getSource());
define('MODULES_FORMMAIL_HTTP', HTTP_ROOT_DIR . str_replace(ROOT_DIR, '', MODULES_DIR) . '/' . $moduledir->getSource());

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

        return ModuleLoaderHelper::isLoaded('formmail') && isset($_SESSION['sess_userObj']) && in_array($_SESSION['sess_userObj']->getType(), $allowedTypes);
    }
}

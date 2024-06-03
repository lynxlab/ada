<?php

/**
 * bootstrap.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Turn off logging to HTML for all error phases.
 */
$GLOBALS['ADA_ERROR_POLICY'][ADA_ERROR_PHASE_DEVELOP][ADA_ERROR_SEVERITY_FATAL]  = ADA_ERROR_LOG_TO_FILE;
$GLOBALS['ADA_ERROR_POLICY'][ADA_ERROR_PHASE_DEVELOP][ADA_ERROR_SEVERITY_NORMAL] = ADA_ERROR_LOG_TO_FILE;
$GLOBALS['ADA_ERROR_POLICY'][ADA_ERROR_PHASE_DEVELOP][ADA_ERROR_SEVERITY_LIGHT]  = ADA_ERROR_LOG_TO_FILE;
$GLOBALS['ADA_ERROR_POLICY'][ADA_ERROR_PHASE_DEVELOP][ADA_ERROR_SEVERITY_NONE]   = ADA_ERROR_LOG_TO_FILE;

/**
 * Set the globals of the user_provider guessed from 3rd level domain
 */
if (!MULTIPROVIDER) {
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $servername = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $servername = $_SERVER['SERVER_NAME'];
        }
        [$client] = explode('.', preg_replace('/(http[s]?:\/\/)/', '', $servername));
    }

    if (isset($client) && !empty($client) && is_dir(ROOT_DIR . '/clients/' . $client)) {
        $tmpcommon = AMACommonDataHandler::instance();
        // $_SESSION['sess_user_provider'] = $client;
        $GLOBALS['user_provider'] = $tmpcommon->getPointerFromThirdLevel($client);
        unset($tmpcommon);
        // other session vars per provider may go here...
    } else {
        unset($GLOBALS['user_provider']);
    }
}

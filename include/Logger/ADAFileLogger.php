<?php

/**
 *
 * Requires PHP >= 5.2.2
 *
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        logger
 * @version     0.2
 */

namespace Lynxlab\ADA\Main\Logger;

/**
 *
 * @author vito
 *
 */
class ADAFileLogger extends ADASimpleLogger
{
    /**
     *
     * @param $text
     */
    public static function log($text, $filename = ADA_FILE_LOGGER_OUTPUT_FILE)
    {

        // if(defined('ADA_FILE_LOGGER_OUTPUT_FILE')
        //    && is_writable(ADA_FILE_LOGGER_OUTPUT_FILE)) {
        if (is_file($filename) && is_writable($filename)) {
            $log =  self::getDebugDate() . " $text\n";

            // available from PHP 5
            //file_put_contents(ADA_FILE_LOGGER_OUTPUT_FILE, $log, FILE_APPEND);
            file_put_contents($filename, $log, FILE_APPEND);

            // richiamare clearstatcache() ?
        } else {
            //  ADAScreenLogger::log('FileLogger: output file ' . $filename .' not writable, redirecting log to screen');
            //  ADAScreenLogger::log($text);
        }
    }

    public static function logError($text, $filename = ADA_LOG_ERROR_FILE_LOG_OUTPUT_FILE)
    {

        if (is_file($filename) && is_writable($filename)) {
            /*
             * $text already has date and time infos
             */
            //$log =  self::getDebugDate() . " $text\n";
            $log = " $text\n";
            // available from PHP 5
            file_put_contents($filename, $log, FILE_APPEND);
            // richiamare clearstatcache() ?
            return true;
        }
        return false;
    }
}

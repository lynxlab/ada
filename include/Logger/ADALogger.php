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
class ADALogger
{
    /**
     * handles logging of generic messages (not error messages or db messages)
     *
     * @param $text   the message to log
     * @return void
     */
    public static function log($text)
    {
        if (ADA_LOGGING_LEVEL & ADA_LOG_GENERIC) {
            switch (ADA_LOG_GENERIC_SELECTED_LOGGER) {
                case ADA_LOGGER_SCREEN_LOG:
                    ADAScreenLogger::log($text);
                    break;

                case ADA_LOGGER_FILE_LOG:
                    ADAFileLogger::log($text, ADA_LOG_GENERIC_FILE_LOG_OUTPUT_FILE);
                    break;

                case ADA_LOGGER_NULL_LOG:
                default:
            }
        }
    }

    /**
     * handles logging of db messages
     *
     * @param $text   the message to log
     * @return void
     */
    public static function log_db($text)
    {
        if (ADA_LOGGING_LEVEL & ADA_LOG_DB) {
            switch (ADA_LOG_DB_SELECTED_LOGGER) {
                case ADA_LOGGER_SCREEN_LOG:
                    ADAScreenLogger::log($text);
                    break;

                case ADA_LOGGER_FILE_LOG:
                    ADAFileLogger::log($text, ADA_LOG_DB_FILE_LOG_OUTPUT_FILE);
                    break;

                case ADA_LOGGER_NULL_LOG:
                default:
            }
        }
    }
    /**
     * handles logging of error messages
     *
     * @param $text   the message to log
     * @return void
     */
    public static function log_error($text)
    {
        /*
         * Always log errors.
         */
        //if(ADA_LOGGING_LEVEL & ADA_LOG_ERROR) {
        switch (ADA_LOG_ERROR_SELECTED_LOGGER) {
            case ADA_LOGGER_SCREEN_LOG:
                ADAScreenLogger::log_error($text);
                break;

            case ADA_LOGGER_FILE_LOG:
                ADAFileLogger::log_error($text, ADA_LOG_ERROR_FILE_LOG_OUTPUT_FILE);
                break;

            case ADA_LOGGER_NULL_LOG:
            default:
        }
        //}
    }
}

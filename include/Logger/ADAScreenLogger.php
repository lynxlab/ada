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
class ADAScreenLogger extends ADASimpleLogger
{
    /**
     *
     * @param $text
     * @return void
     */

    // FIXME: Strict Standards
    /*
     * Strict standards: date() [function.date]: It is not safe to rely on the system's
     * timezone settings. Please use the date.timezone setting, the TZ environment
     * variable or the date_default_timezone_set() function. In case you used any
     * of those methods and you are still getting this warning, you most likely
     * misspelled the timezone identifier.
     * We selected 'Europe/Berlin' for 'CEST/2.0/DST' instead in
     * /var/www/html/ada/include/logger_class.inc.php on line 46
     */
    public static function log($text)
    {
        echo '<b>' . self::getDebugDate() . '</b> ' . $text . '<br />';
    }

    public static function log_error($text)
    {
        echo $text . '<br />';
    }
}

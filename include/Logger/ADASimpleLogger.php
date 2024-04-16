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

abstract class ADASimpleLogger
{
    /**
     *
     * @return unknown_type
     */

    protected static function getDebugDate()
    {
        /*
         * It seems there are issues with date, date_format, date_create and
         * microtime.
         * Here we manually add the microseconds part to the date.
         */
        return date('d/m/Y H:i:s') . substr((string)microtime(), 1, 8);
    }
}

<?php

/**
 * WithInstance trait
 *
 * use this trait when you need a datahandler with the static instance method.
 *
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\Traits;

trait WithInstance
{
    /**
     * Returns an instance of the datahandler.
     *
     * @param  string $dsn - optional, a valid data source name.
     * @return self an instance of the data handler.
     */
    public static function instance($dsn = null)
    {
        return parent::instance($dsn);
    }
}

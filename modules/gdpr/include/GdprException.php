<?php

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

use Exception;

/**
 * GdprException class to handle custom exceptions
 *
 * @author giorgio
 */
class GdprException extends Exception
{
    public const CAPTCHA_EMPTY = 0;
    public const CAPTCHA_NOMATCH = 1;

    // custom string representation of object
    public function __toString(): string
    {
        return self::class . ": [{$this->code}]: {$this->message}\n";
    }
}

<?php

/**
 * @package     ADA Zoom Meeting Integration
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\ZoomIntegration;

use Exception;

/**
 * ZoomIntegrationException class to handle custom exceptions
 *
 * @author giorgio
 */
class ZoomIntegrationException extends Exception
{
    // custom string representation of object
    public function __toString(): string
    {
        return self::class . ": [{$this->code}]: {$this->message}\n";
    }
}

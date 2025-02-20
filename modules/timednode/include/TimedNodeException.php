<?php

/**
 * @package     timednode module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Timednode;

use Exception;

class TimedNodeException extends Exception
{
    // custom string representation of object
    public function __toString(): string
    {
        return self::class . ": [{$this->code}]: {$this->message}\nStack trace:\n" . $this->getTraceAsString();
    }
}

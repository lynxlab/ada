<?php

/**
 * WithExceptionClass trait
 *
 * this trait defines a method to get the EXCEPTIONCLASS
 * of the class the uses it and builds and excpetion
 *
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\Traits;

use Exception;
use ReflectionClass;
use Throwable;

trait WithExceptionClass
{
    /**
     * Finds the caller EXCPETIONCLASS and builds the exception.
     *
     * @param string $message
     * @param integer $code
     * @param \Throwable|null $previous
     * @return \Exception
     */
    private static function buildException(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $class = new ReflectionClass(static::class);
        $exClass = $class->getConstant('EXCEPTIONCLASS');
        if ($exClass === false) {
            $exClass = Exception::class;
        }
        return new $exClass($message, $code, $previous);
    }
}

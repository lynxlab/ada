<?php

namespace Lynxlab\ADA\Module\EventDispatcher;

use ReflectionClass;

trait ADAEventTrait
{
    public static function getConstants()
    {
        // "static::class" here does the magic
        $reflectionClass = new ReflectionClass(static::class);
        return $reflectionClass->getConstants();
    }
}

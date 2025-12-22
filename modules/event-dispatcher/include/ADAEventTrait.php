<?php

namespace Lynxlab\ADA\Module\EventDispatcher;

use ReflectionClass;

trait ADAEventTrait
{
    private $eventName;

    public function __construct($subject = null, array $arguments = [], $eventName = null)
    {
        parent::__construct($subject, $arguments);
        $this->eventName = $eventName;
    }

    public static function getConstants()
    {
        // "static::class" here does the magic
        $reflectionClass = new ReflectionClass(static::class);
        return $reflectionClass->getConstants();
    }

    /**
     * Get the value of eventName
     */
    public function getEventName()
    {
        return $this->eventName;
    }
}

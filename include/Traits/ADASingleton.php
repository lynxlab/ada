<?php

/**
 * ADASingleton class
 *
 *
 * @package
 * @author      Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        ada_error
 * @version     0.1
 * @see         https://refactoring.guru/design-patterns/singleton/php/example#example-1
 */

namespace Lynxlab\ADA\Main\Traits;

use Exception;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\DebugBar\ADADebugBar;

/**
 * If you need to support several types of Singletons in your app, you can
 * define the basic features of the Singleton in a base class, while moving the
 * actual business logic (like logging) to subclasses.
 */
trait ADASingleton
{
    /**
     * The actual singleton's instance almost always resides inside a static
     * field. In this case, the static field is an array, where each subclass of
     * the Singleton stores its own instance.
     */
    private static $instances = [];

    /**
     * Singleton's constructor should not be public. However, it can't be
     * private either if we want to allow subclassing.
     */
    protected function __construct(mixed ...$params)
    {
    }

    /**
     * Cloning and unserialization are not permitted for singletons.
     */
    final protected function __clone()
    {
    }

    final public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * The method you use to get the Singleton's instance.
     *
     * @param mixed $parms parameteres to be passed to the constructor
     *
     * @return self
     */
    final public static function getInstance(mixed ...$params)
    {
        $subclass = static::class;
        if (!isset(static::$instances[$subclass])) {
            // Note that here we use the "static" keyword instead of the actual
            // class name. In this context, the "static" keyword means "the name
            // of the current class". That detail is important because when the
            // method is called on the subclass, we want an instance of that
            // subclass to be created here.
            if ($subclass !== ADADebugBar::class && ModuleLoaderHelper::isLoaded('debugbar')) {
                $debugstr = sprintf("Build singleton new %s() with", $subclass);
                if (empty($params)) {
                    $debugstr .= 'out params';
                } else {
                    $debugstr .= sprintf(
                        " params: %s",
                        (is_scalar($params) ? (string) $params : print_r($params, true))
                    );
                }
                if (ModuleLoaderHelper::isLoaded('debugbar')) {
                    ADADebugBar::addMessage($debugstr);
                }
            }
            static::$instances[$subclass] = new $subclass(...$params);
        }
        return static::$instances[$subclass];
    }

    /**
     * Checks if the class is already an instantiaded singleton.
     *
     * @return boolean
     */
    final public static function hasInstance()
    {
        return in_array(static::class, static::$instances);
    }

    /**
     * Overrides an intance with the passed one
     *
     * @param mixed $instance
     *
     * @return self
     */
    private static function overrideInstance(mixed $instance)
    {
        static::$instances[static::class] = $instance;
        return static::$instances[static::class];
    }
}

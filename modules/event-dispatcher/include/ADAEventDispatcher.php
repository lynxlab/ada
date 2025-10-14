<?php

/**
 * @package     event-dispatcher module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

declare(strict_types=1);

namespace Lynxlab\ADA\Module\EventDispatcher;

use Exception;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Traits\ADASingleton;
use Lynxlab\ADA\Module\DebugBar\ADADebugBar;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAMethodSubscriberInterface;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * ADAEventDispatcher Class
 */
class ADAEventDispatcher extends EventDispatcher implements EventDispatcherInterface
{
    use ADASingleton {
        getInstance as traitGetInstance;
    }

    /**
     * Set to true to have getInstance to return a TraceableEventDispatcher
     * with getCalledListeners and getNotCalledListeners methods
     */
    public const TRACEABLE = false;

    /**
     * Separator to build prefixed event Names
     */
    public const PREFIX_SEPARATOR = '::';

    /**
     * Returns a Singleton instance of this class.
     *
     * @return ADAEventDispatcher
     */
    public static function getInstance(): EventDispatcherInterface
    {
        static $instance;
        if (null === $instance) {
            $instance = static::traitGetInstance();
            if ((ModuleLoaderHelper::isLoaded('debugbar') && ADADebugBar::getInstance()->hasCollector('dispatcher')) || self::TRACEABLE) {
                $instance = static::overrideInstance(new TraceableEventDispatcher($instance, new Stopwatch()));
            }
        }
        return $instance;
    }

    /**
     * builds an event and dispatch it using passed subject and arguments
     *
     * @param array $eventData Associative array to build the event. MUST have 'eventClass' and 'eventName' keys
     * @param mixed $subject   Subject passed to the dispatcher
     * @param array $arguments Arguments passed to the dispatcher
     * @return \Symfony\Component\EventDispatcher\GenericEvent as returned by the dispatch method
     */
    public static function buildEventAndDispatch(array $eventData = [], $subject = null, array $arguments = [])
    {
        $eventsNS = 'Events';
        $eventName = null;
        if (array_key_exists('eventClass', $eventData)) {
            if (array_key_exists('eventName', $eventData)) {
                if (class_exists($eventData['eventClass'])) {
                    $classname = $eventData['eventClass'];
                } else {
                    $classname = __NAMESPACE__ . '\\' . $eventsNS . '\\' . $eventData['eventClass'];
                }
                if (class_exists($classname)) {
                    if (in_array($eventData['eventName'], $classname::getConstants())) {
                        $eventName = $eventData['eventName'];
                    } else {
                        $constantname = $classname . '::' . $eventData['eventName'];
                        if (defined($constantname)) {
                            $eventName = constant($constantname);
                        }
                    }
                    if (!is_null($eventName)) {
                        $dispatchArr = [];
                        $event = new $classname($subject, $arguments);
                        $eventPrefix = array_key_exists('eventPrefix', $eventData) ? trim($eventData['eventPrefix']) . self::PREFIX_SEPARATOR : '';
                        $listeners = self::getInstance()->getListeners();
                        $dbt = array_unique(array_map(
                            fn($el) => $el['class'] . '::' . $el['function'],
                            array_filter(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), fn($el) => array_key_exists('class', $el))
                        ));
                        $dbt[] = basename($_SERVER['SCRIPT_NAME']);
                        foreach ($dbt as $prefix) {
                            if (array_key_exists($prefix . self::PREFIX_SEPARATOR . $eventName, $listeners)) {
                                // ADD Event prefixed by class::method or by SCRIPT_NAME
                                $dispatchArr[] = $prefix . self::PREFIX_SEPARATOR . $eventName;
                            }
                        }
                        if (!empty($eventPrefix)) {
                            // ADD Event prefixed by $eventPrefix (can be a custom sting, such as script full path)
                            $dispatchArr[] = $eventPrefix . $eventName;
                        }
                        // ADD Event name without perfix
                        $dispatchArr[] = $eventName;

                        foreach (array_unique($dispatchArr) as $dispatchName) {
                            if (!$event->isPropagationStopped()) {
                                $event = self::getInstance()->dispatch($event, $dispatchName);
                            }
                        }
                        return $event;
                    } else {
                        throw new ADAEventException(sprintf("Event constant %s is not defined", $eventData['eventName']), ADAEventException::EVENTNAMENOTFOUND);
                    }
                } else {
                    throw new ADAEventException(sprintf("Class %s not found", $eventData['eventClass']), ADAEventException::EVENTCLASSNOTFOUND);
                }
            } else {
                throw new ADAEventException("Must pass an Event name", ADAEventException::NOEVENTNAME);
            }
        } else {
            throw new ADAEventException("Must pass an Events class", ADAEventException::NOEVENTCLASS);
        }
    }

    /**
     * {@inheritdoc}
     *
     * eventName can be a regexp and will dispatch all events that matches
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {

        // check if $eventName is a regexp
        set_error_handler(function () {}, E_WARNING);
        $isRegularExpression = preg_match($eventName, "") !== false;
        restore_error_handler();
        if ($isRegularExpression) {
            foreach ($this->getListeners() as $anEvent) {
                if (preg_match($eventName, $anEvent[1])) {
                    $event = parent::dispatch($event, $anEvent[1]);
                }
            }
            return $event;
        }
        return parent::dispatch($event, $eventName);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $prefixedListeners = [];
        if ($subscriber instanceof ADAMethodSubscriberInterface) {
            $prefixedListeners += $subscriber::getSubscribedMethods();
        }
        if ($subscriber instanceof ADAScriptSubscriberInterface) {
            $prefixedListeners += $subscriber::getSubscribedScripts();
        }
        if (count($prefixedListeners) > 0) {
            foreach ($prefixedListeners as $methodName => $methodParams) {
                foreach ($methodParams as $eventName => $params) {
                    $eventName = $methodName . self::PREFIX_SEPARATOR . $eventName;
                    if (is_string($params)) {
                        $this->addListener($eventName, [$subscriber, $params]);
                    } elseif (is_string($params[0])) {
                        $this->addListener($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
                    } else {
                        foreach ($params as $listener) {
                            $this->addListener($eventName, [$subscriber, $listener[0]], $listener[1] ?? 0);
                        }
                    }
                }
            }
        }
        parent::addSubscriber($subscriber);
    }

    /**
     * return getCalledListeners if TRACEABLE is true or throws an exception
     *
     * @return mixed
     */
    public function getCalledListeners()
    {
        return $this->callParentIfExists('getCalledListeners');
    }

    /**
     * return getNotCalledListeners if TRACEABLE is true or throws an exception
     *
     * @return mixed
     */
    public function getNotCalledListeners()
    {
        return $this->callParentIfExists('getNotCalledListeners');
    }

    /**
     * If $method exists in parent class, call it and return its return value
     * else throw an Excecption
     *
     * @param string $method
     * @return mixed
     */
    private function callParentIfExists($method)
    {
        foreach (class_parents($this) as $parent) {
            if (method_exists($parent, $method)) {
                return call_user_func([$parent, $method]);
            }
        }
        throw new Exception(sprintf('The required method %s does not exist for %s', $method, static::class));
    }

    /**
     * Adds all the events subscribers found in the subscribers directory
     *
     * @return void
     */
    public static function addAllSubscribers()
    {
        $fileext = '.php';
        $subscribersNS = 'Subscribers';
        $fullNS = __NAMESPACE__ . '\\' . $subscribersNS . '\\';
        $dispatcher = self::getInstance();
        foreach (glob(__DIR__ . '/' . strtolower($subscribersNS) . '/*' . $fileext) as $filename) {
            if (is_readable($filename)) {
                $classname = $fullNS . rtrim(basename($filename), $fileext);
                if (class_exists($classname)) {
                    $reflected = new ReflectionClass($classname);
                    if ($reflected->isInstantiable()) {
                        $dispatcher->addSubscriber(new $classname());
                    }
                }
            }
        }
    }
}

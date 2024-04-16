<?php

/**
 * @package     event-dispatcher module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\EventDispatcher\Events;

use Lynxlab\ADA\Module\EventDispatcher\ADAEventTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * CoreEvent class
 */
final class CoreEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'adacore';

    /**
     * The PAGEPRERENDER event occurs before the page is rendered by the ARE::render
     *
     * This event allows you to add, remove or replace render data
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PAGEPRERENDER = self::NAMESPACE . '.page.prerender';

    /**
     * The AMAPDOPREGETALL event occurs before the AMAPDOWrapper::getAll runs its query
     *
     * This event allows you manipulate the query being executed
     *
     * @CoreEvent
     *
     * @var string
     */
    public const AMAPDOPREGETALL = self::NAMESPACE . '.amapdo.pregetall';

    /**
     * The AMAPDOPOSTGETALL event occurs after the AMAPDOWrapper::getAll is run
     *
     * This event allows you to manipulate the retunred results array
     *
     * @CoreEvent
     *
     * @var string
     */
    public const AMAPDOPOSTGETALL = self::NAMESPACE . '.amapdo.postgetall';
}

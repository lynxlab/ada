<?php

/**
 * @package     event-dispatcher module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\EventDispatcher\Events;

use Lynxlab\ADA\Module\EventDispatcher\ADAEventTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * SubscriptionEvent class
 */
final class SubscriptionEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'subscripiton';

    /**
     * The PRESUBSCRIBE event occurs before the user is subscried to an instance
     *
     * This event allows you to add, remove or replace course data
     *
     * @SubscriptionEvent
     *
     * @var string
     */
    public const PRESUBSCRIBE = self::NAMESPACE . '.presubscribe';

    /**
     * The POSTSUBSCRIBE event occurs after the user is subscried to an instance
     *
     * @SubscriptionEvent
     *
     * @var string
     */
    public const POSTSUBSCRIBE = self::NAMESPACE . '.postsubscribe';

    /**
     * The PREUNSUBSCRIBE event occurs before the user is unsubscried to an instance
     *
     * This event allows you to add, remove or replace course data
     *
     * @SubscriptionEvent
     *
     * @var string
     */
    public const PREUNSUBSCRIBE = self::NAMESPACE . '.preunsubscribe';

    /**
     * The POSTUNSUBSCRIBE event occurs after the user is unsubscried to an instance
     *
     * @SubscriptionEvent
     *
     * @var string
     */
    public const POSTUNSUBSCRIBE = self::NAMESPACE . '.postunsubscribe';
}

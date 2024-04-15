<?php

use Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent;

// Trigger: ClassWithNameSpace. The class NodeEvent was declared with namespace Lynxlab\ADA\Module\EventDispatcher\Events. //

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
 * NodeEvent class
 */
final class NodeEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'node';

    /**
     * The PRESAVE event occurs before the node is created (i.e. saved)
     *
     * This event allows you to add, remove or replace data
     *
     * @GenericEvent
     *
     * @var string
     */
    public const PRESAVE = self::NAMESPACE . '.presave';

    /**
     * The POSTSAVE event occurs after the node is created (i.e. saved)
     *
     * This event allows you to add actions after the node has been saved.
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTSAVE = self::NAMESPACE . '.postsave';

    /**
     * The POSTADDREDIRECT event occurs after the node is created (i.e. saved)
     * and the redirect header has been sent
     *
     * This event allows you to add actions after the node has been created,
     * you may close the connection with the browser and do some "background" task
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTADDREDIRECT = self::NAMESPACE . 'add.postredirect';
}

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
 * ForumEvent class
 */
final class ForumEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'forum';

    /**
     * The NOTEPRESAVE event occurs before the a forum note (aka post) is created (i.e. saved)
     *
     * This event allows you to add, remove or replace data
     *
     * @GenericEvent
     *
     * @var string
     */
    public const NOTEPRESAVE = self::NAMESPACE . '.note.presave';

    /**
     * The NOTEPOSTSAVE event occurs after the forum note (aka post) is created (i.e. saved)
     *
     * This event allows you to add actions after the node has been saved.
     *
     * @GenericEvent
     *
     * @var string
     */
    public const NOTEPOSTSAVE = self::NAMESPACE . '.note.postsave';

    /**
     * The INDEXACTIONINIT event occurs before the first action is inserted into the default actions container
     *
     * This event allows you to add custom actions buttons to the beginning of the default buttons container
     *
     * @GenericEvent
     *
     * @var string
     */
    public const INDEXACTIONINIT = self::NAMESPACE . '.index.action.init';

    /**
     * The INDEXACTIONDONE event occurs after the action buttons for the forum index have been generated
     *
     * This event allows you to add custom actions buttons to the end of the default buttons container
     *
     * @GenericEvent
     *
     * @var string
     */
    public const INDEXACTIONDONE = self::NAMESPACE . '.index.action.done';
}

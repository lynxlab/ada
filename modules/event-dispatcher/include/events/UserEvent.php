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
 * UserEvent class
 */
final class UserEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'user';

    /**
     * The PRESAVE event occurs before the user is created (i.e. saved)
     *
     * This event allows you to add, remove or replace data
     *
     * @GenericEvent
     *
     * @var string
     */
    public const PRESAVE = self::NAMESPACE . '.presave';

    /**
     * The POSTSAVE event occurs after the user is created (i.e. saved)
     *
     * This event allows you to add actions after the user has been saved.
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTSAVE = self::NAMESPACE . '.postsave';

    /**
     * Dispatched in ADAUser::getClassForLinkedTable
     * so that modules may define their own classes for the method
     *
     * @GenericEvent
     *
     * @var string
     */
    public const GETCLASSFORLINKEDTABLE = self::NAMESPACE . 'getclassforlinkedtable';

    /**
     * Dispatched in ADAUser::getClassForLinkedTable
     * so that modules may define their own classes for the method
     *
     * @GenericEvent
     *
     * @var string
     */
    public const GETFORMCLASSFORLINKEDTABLE = self::NAMESPACE . 'getformclassforlinkedtable';
}

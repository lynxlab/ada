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
 * CourseInstanceEvent class
 */
final class CourseInstanceEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = 'courseinstance';

    /**
     * The PRESAVE event occurs before the course is saved in the DB
     *
     * This event allows you to add, remove or replace course data
     *
     * @CourseInstanceEvent
     *
     * @var string
     */
    public const PRESAVE = self::NAMESPACE . '.presave';

    /**
     * The POSTSAVE event occurs after the course is saved in the DB
     *
     * @CourseInstanceEvent
     *
     * @var string
     */
    public const POSTSAVE = self::NAMESPACE . '.postsave';
}

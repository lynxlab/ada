<?php

/**
 * @package
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\Module\EventDispatcher\ADAEventTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * ModuleTestEvent class
 */
final class ModuleTestEvent extends GenericEvent
{
    use ADAEventTrait;

    /**
     * event own namespace
     */
    public const NAMESPACE = MODULES_TEST_NAME;

    /**
     * The PRESAVEANSWER event occurs before every answer is saved
     *
     * @GenericEvent
     *
     * @var string
     */
    public const PRESAVEANSWER = self::NAMESPACE . '.presaveanswer';

    /**
     * The POSTSAVEANSWER event occurs after every answer is saved
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTSAVEANSWER = self::NAMESPACE . '.postsaveanswer';

    /**
     * The PRESAVETEST event occurs before the test is submitted by the student
     *
     * @GenericEvent
     *
     * @var string
     */
    public const PRESAVETEST = self::NAMESPACE . '.presavetest';

    /**
     * The POSTSAVETEST event occurs after the test is submitted by the student
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTSAVETEST = self::NAMESPACE . '.postsavetest';

    /**
     * The POSTRENDERENDTEST event occurs before the RootTest::renderEndTest method returns the html
     *
     * @GenericEvent
     *
     * @var string
     */
    public const POSTRENDERENDTEST = self::NAMESPACE . '.postrenderendtest';
}

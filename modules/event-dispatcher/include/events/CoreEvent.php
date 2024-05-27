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

    /**
     * The HELPERINITEXTRACT event occurs before ViewBaseHelper::extract
     * sets the $GLOBALS array keys.
     *
     * This event allows you to manipulate the retunred results array
     *
     * @CoreEvent
     *
     * @var string
     */
    public const HELPERINITEXTRACT = self::NAMESPACE . '.helperinit.extract';

    /**
     * The HTMLOUTPUT event occurs before the html is sent to the browser
     *
     * This event allows you to add, remove or replace header, body and footer html
     *
     * @CoreEvent
     *
     * @var string
     */
    public const HTMLOUTPUT = self::NAMESPACE . '.page.htmloutput';

    /**
     * The AMADBPRECONNECT event occurs before a database gets connected
     *
     * This event allows you to add, remove or replace the connection dsn
     *
     * @CoreEvent
     *
     * @var string
     */
    public const AMADBPRECONNECT = self::NAMESPACE . '.amadb.preconnect';


    /**
     * The AMADBPOSTCONNECT event occurs after a database gets successfully connected
     *
     * This event allows you to add, remove or replace the connection dsn and connection object itself
     *
     * @CoreEvent
     *
     * @var string
     */
    public const AMADBPOSTCONNECT = self::NAMESPACE . '.amadb.postconnect';

    /**
     * The PREMODULEINIT event occurs before module_init includes all its files and call its methods
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PREMODULEINIT = self::NAMESPACE . '.moduleinit.pre';

    /**
     * The POSTMODULEINIT event occurs when module_init has done all of its stuff
     *
     * @CoreEvent
     *
     * @var string
     */
    public const POSTMODULEINIT = self::NAMESPACE . '.moduleinit.post';
}

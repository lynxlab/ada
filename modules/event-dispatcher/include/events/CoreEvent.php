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

    /**
     * The PREFILLINTEMPLATE event occurs before ARE:render fills in the template fields.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PREFILLINTEMPLATE = self::NAMESPACE . '.fillintemplate.pre';

    /**
     * The POSTFILLINTEMPLATE event occurs after ARE:render has filled in the template fields.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const POSTFILLINTEMPLATE = self::NAMESPACE . '.fillintemplate.post';

    /**
     * The PREPREPAREANDEXECUTE event occurs before the AbstractAMADataHandler::prepareAndExecute
     * method prepares and executes the query.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PREPREPAREANDEXECUTE = self::NAMESPACE . '.prepareandexecute.pre';

    /**
     * The POSTPREPAREANDEXECUTE event occurs after the AbstractAMADataHandler::prepareAndExecute
     * method prepares and executes the query, just before returning.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const POSTPREPAREANDEXECUTE = self::NAMESPACE . '.prepareandexecute.post';

    /**
     * The PREFETCH event occurs before the AbstractAMADataHandler::getRowPrepared
     * method fetches the results to be returned.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PREFETCH = self::NAMESPACE . '.fetch.pre';

    /**
     * The POSTFETCH event occurs after the AbstractAMADataHandler::getRowPrepared
     * method fetches the results to be returned, just before returning.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const POSTFETCH = self::NAMESPACE . '.fetch.post';

    /**
     * The PREFETCHALL event occurs before the AbstractAMADataHandler::getAllPrepared
     * method fetches the results to be returned.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const PREFETCHALL = self::NAMESPACE . '.fetchall.pre';

    /**
     * The POSTFETCHALL event occurs after the AbstractAMADataHandler::getAllPrepared
     * method fetches the results to be returned, just before returning.
     *
     * @CoreEvent
     *
     * @var string
     */
    public const POSTFETCHALL = self::NAMESPACE . '.fetchall.post';
}

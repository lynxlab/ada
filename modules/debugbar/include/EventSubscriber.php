<?php

/**
 * @package     debugbar module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\DebugBar;

use DebugBar\DataCollector\PDO\TraceablePDO;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * Undocumented variable
     *
     * @var ADADebugBar
     */
    private $debugbar;
    private $debugBarRender;

    public static function getSubscribedEvents()
    {
        return [
            CoreEvent::AMADBPOSTCONNECT => 'addPDOCollector',
            CoreEvent::PREMODULEINIT => 'onPreModuleInit',
            CoreEvent::POSTMODULEINIT => 'onPostModuleInit',
            CoreEvent::PAGEPRERENDER => 'addDebugBar',
        ];
    }

    public function __construct()
    {
        $this->debugbar = ADADebugBar::getInstance();
        $this->debugBarRender = $this->debugbar->getJavascriptRenderer()
            ->setBaseUrl(MODULES_DEBUGBAR_HTTP . '/vendor/maximebf/debugbar/src/DebugBar/Resources')
            ->setEnableJqueryNoConflict(false)->setIncludeVendors(true);
        if ($this->debugbar->hasCollector('time')) {
            $this->debugbar['time']->startMeasure('render');
        }
    }

    /**
     * adds the script and css tags to include the debugbar components.
     * adds dispatcher and ARE collectors content.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function addDebugBar(CoreEvent $event)
    {
        $data = $event->getArguments();
        [$cssFiles, $jsFiles] = $this->debugBarRender->getAssets();

        foreach (
            [
            'JS_filename' => $jsFiles,
            'CSS_filename' => $cssFiles,
            ] as $assetKey => $assets
        ) {
            if (!array_key_exists($assetKey, $data['layout_dataAr'] ?? [])) {
                $data['layout_dataAr'][$assetKey] = [];
            }
            $data['layout_dataAr'][$assetKey] = array_merge(
                $data['layout_dataAr'][$assetKey],
                // filter out debugbar own jquery
                array_filter($assets, fn ($el) => !str_contains(strtolower($el), 'jquery'))
            );
        }

        $debugbarCode = str_ireplace(
            sprintf(
                "%s.ajaxHandler.bindToXHR();",
                $this->debugBarRender->getVariableName()
            ),
            sprintf(
                "%s.ajaxHandler.bindToJquery(\$j);\n",
                $this->debugBarRender->getVariableName()
            ),
            $this->debugBarRender->render()
        );
        $data['content_dataAr']['adadebugbar'] = $debugbarCode;

        if ($this->debugbar->hasCollector('ARE')) {
            $this->debugbar['ARE']->setData($data);
        }

        if ($this->debugbar->hasCollector('dispatcher')) {
            $this->addDispatcherMsg();
        }

        $event->setArguments($data);
    }

    /**
     * adds the PDOCollectors to the debugbar
     *
     * @param CoreEvent $event
     * @return void
     */
    public function addPDOCollector(CoreEvent $event)
    {
        $data = $event->getArguments();
        // parse the dsn to get a dbname
        $matched = [];
        preg_match('/^([a-z]+):\/\/(\S*):(\S*)@(\S*)\/(\S*)$/', $data['dsn'], $matched);
        [, $dbtype, $username, $password, $dbhost, $dbname] = $matched;
        $this->debugbar->getPdoCollector()->addConnection(new TraceablePDO($data['db']->connectionObject()), $dbname);
    }

    /**
     * does pre-module init stuff: starts a measure timer
     * and fix buffer and shutdown function is request is detected as ajax
     *
     * @param CoreEvent $event
     * @return void
     */
    public function onPreModuleInit(CoreEvent $event)
    {
        if ($this->debugbar->hasCollector('time')) {
            $this->debugbar['time']->startMeasure('module-init');
        }
        $data = $event->getArguments();
        if (array_key_exists('isAjax', $data) && $data['isAjax']) {
            \ob_start();
            \register_shutdown_function([$this, 'flushHeaders']);
        }
    }

    /**
     * does post-module init stuff: stops the measure timer
     *
     * @param CoreEvent $event
     * @return void
     */
    public function onPostModuleInit(CoreEvent $event)
    {
        if ($this->debugbar->hasCollector('time')) {
            $this->debugbar['time']->stopMeasure('module-init');
        }
    }

    /**
     * if an ajax call is detected, tell the debugbar to flush the headers
     *
     * @return void
     */
    public function flushHeaders()
    {
        $this->debugbar->sendDataInHeaders(true);
    }

    /**
     * adds the dispatcher content
     *
     * @return void
     */
    private function addDispatcherMsg()
    {
        if ($this->debugbar->hasCollector('dispatcher')) {
            $this->debugbar['dispatcher']->setData([
                'not-called' => ADAEventDispatcher::getInstance()->getNotCalledListeners(),
                'called' => ADAEventDispatcher::getInstance()->getCalledListeners(),
            ]);
        }
    }
}

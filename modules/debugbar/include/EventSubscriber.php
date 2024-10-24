<?php

/**
 * @package     debugbar module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\DebugBar;

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
            CoreEvent::PAGEPRERENDER => 'addDebugBarAssets',
            CoreEvent::PREFILLINTEMPLATE => 'addDebugBar',
        ];
    }

    public function __construct()
    {
        $this->debugbar = ADADebugBar::getInstance();
        $this->debugBarRender = $this->debugbar->getJavascriptRenderer()
            ->setBaseUrl(MODULES_DEBUGBAR_HTTP . '/vendor/maximebf/debugbar/src/DebugBar/Resources')
            ->setEnableJqueryNoConflict(false)->setIncludeVendors(true)
            ->setOpenHandlerUrl(MODULES_DEBUGBAR_HTTP . '/ajax/open.php');
        if ($this->debugbar->hasCollector('time')) {
            $this->debugbar['time']->startMeasure('render');
        }
    }

    /**
     * adds the script and css tags to include the debugbar components.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function addDebugBarAssets(CoreEvent $event)
    {
        $data = $event->getArguments();
        if ((($_SESSION['sess_id_user'] ?? 0) > 0) && ($data['renderer'] ?? ARE_HTML_RENDER) == ARE_HTML_RENDER) {
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
        }
        $event->setArguments($data);
    }

    /**
     * adds debugbar code and dispatcher and ARE collectors content.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function addDebugBar(CoreEvent $event)
    {
        /**
         * Output the debugbar only for logged users
         */
        if (($_SESSION['sess_id_user'] ?? 0) > 0) {
            $data = $event->getArguments();

            if ($this->debugbar->hasCollector('ARE')) {
                $this->debugbar['ARE']->setData(reset($data));
            }

            if ($this->debugbar->hasCollector('dispatcher')) {
                $this->addDispatcherMsg();
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
            $data['dataHa']['adadebugbar'] = $debugbarCode;
            $event->setArguments($data);
        }
    }

    /**
     * adds the PDOCollectors to the debugbar
     *
     * @param CoreEvent $event
     * @return void
     */
    public function addPDOCollector(CoreEvent $event)
    {
        if ($this->debugbar->getPdoCollector()) {
            $data = $event->getArguments();
            // parse the dsn to get a dbname
            $matched = [];
            preg_match('/^([a-z]+):\/\/(\S*):(\S*)@(\S*)\/(\S*)$/', $data['dsn'], $matched);
            [, $dbtype, $username, $password, $dbhost, $dbname] = $matched;
            $num = count(array_filter(
                array_keys($this->debugbar->getPdoCollector()->getConnections()),
                fn ($el) => str_starts_with($el, $dbname)
            ));
            $this->debugbar->getPdoCollector()->addConnection($data['db']->connectionObject(), $dbname . '#' . $num);
        }
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
    }

    /**
     * does post-module init stuff: stops the measure timer
     *
     * @param CoreEvent $event
     * @return void
     */
    public function onPostModuleInit(CoreEvent $event)
    {
        $this->debugbar->setStorage(
            ADADebugBar::buildStorage(
                ADA_UPLOAD_PATH . '/tmp/' . MODULES_DEBUGBAR_NAME . '/' . ($_SESSION['sess_id_user'] ?? 0)
            )
        );
        if ($this->debugbar->hasCollector('time')) {
            $this->debugbar['time']->stopMeasure('module-init');
        }
        $data = $event->getArguments();
        if (array_key_exists('isAjax', $data) && $data['isAjax']) {
            $this->debugbar->sendDataInHeaders(true);
        }
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

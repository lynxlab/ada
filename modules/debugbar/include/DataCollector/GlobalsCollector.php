<?php

/**
 * @package     debugbar module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\DebugBar\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class GlobalsCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @return array
     */
    public function collect()
    {
        $vars = array_filter(
            array_keys($GLOBALS),
            fn ($el) => !in_array($el, [
                '_GET',
                '_POST',
                '_SESSION',
                '_COOKIE',
                '_REQUEST',
                '__composer_autoload_files',
                'content_dataAr'])
        );
        $data = [];

        foreach ($vars as $var) {
            if (isset($GLOBALS[$var])) {
                if ($this->isHtmlVarDumperUsed()) {
                    $data[$var] = $this->getVarDumper()->renderVar($GLOBALS[$var]);
                } else {
                    $data[$var] = $this->getDataFormatter()->formatVar($GLOBALS[$var]);
                }
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'globals';
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return [
            "globals" => [
                "icon" => "tags",
                "widget" => $widget,
                "map" => "globals",
                "default" => "{}",
            ],
        ];
    }
}

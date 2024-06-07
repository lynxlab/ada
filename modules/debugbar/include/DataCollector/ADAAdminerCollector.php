<?php

namespace Lynxlab\ADA\Module\DebugBar\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Lynxlab\ADA\Module\DebugBar\ADAAdminerHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class ADAAdminerCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * {@inheritDoc}
     */
    public function getAssets()
    {
        return [
            'base_path' => MODULES_DEBUGBAR_PATH,
            'js' => 'js/AdminerIndicator.js',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'adaadminer';
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        if (ADAAdminerHelper::hasAdminer()) {
            $clients = array_map(fn ($el) => [
                'value' => MODULES_DEBUGBAR_HTTP . '/adaadminer.php?client=' . $el,
                'label' => $el,
            ], ADAAdminerHelper::getADAClients());

            array_unshift($clients, [
                'value' => null,
                'label' => translateFN('Adminer'),
            ]);

            return [
                'menu' => $clients,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return [
            "menu" => [
                "indicator" => "PhpDebugBar.Widgets.AdminerIndicator",
                "map" => "adaadminer.menu",
                "default" => '[]',
            ],
        ];
    }
}

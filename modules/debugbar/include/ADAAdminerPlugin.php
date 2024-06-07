<?php

namespace Lynxlab\ADA\Module\DebugBar;

use AdminerPlugin;

require_once realpath(__DIR__) . '/../adminer/plugins/plugin.php';

class ADAAdminerPlugin extends AdminerPlugin
{
    private $client;

    public function __construct($plugins, $client)
    {
        $this->client = $client;
        parent::__construct($plugins);
    }

    public function head()
    {
        ?>
        <style>
            p.logout {
                display: none;
            }
        </style>
        <?php
        return true;
    }

    public function name()
    {
        return 'ADA Adminer';
    }

    public function databases($flush = true)
    {
        return array_filter(
            parent::databases($flush),
            fn ($el) => in_array(
                $el,
                ADAAdminerHelper::getDBNames()
            )
        );
    }

    public function credentials()
    {
        return ADAAdminerHelper::getCredentials($this->client);
    }

    public function login($login, $password)
    {
        // autologin to adminer
        return true;
    }

    public function headers()
    {
        // allow usage within iframe
        header("X-Frame-Options: SameOrigin");
    }
}

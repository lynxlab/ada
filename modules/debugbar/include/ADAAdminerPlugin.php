<?php

namespace Lynxlab\ADA\Module\DebugBar;

class ADAAdminerPlugin extends \AdminerPlugin
{

    private $client;

    public function __construct($plugins, $client)
    {
        $this->client = $client;
        parent::__construct($plugins);
    }

    function head()
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

    function name()
    {
        return 'ADA Adminer';
    }

    function databases($flush = true)
    {
        return array_filter(
            parent::databases($flush),
            fn ($el) => in_array(
                $el,
                ADAAdminerHelper::getDBNames()
            )
        );
    }

    function credentials()
    {
        return ADAAdminerHelper::getCredentials($this->client);
    }

    function login($login, $password)
    {
        // autologin to adminer
        return true;
    }

    function headers()
    {
        // allow usage within iframe
        header("X-Frame-Options: SameOrigin");
    }
}

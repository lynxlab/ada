<?php

namespace Adminer;

use Lynxlab\ADA\Module\DebugBar\ADAAdminerHelper;

class ADAAdminerPlugin extends Adminer
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function head($Ab = null)
    {
        echo "<style>
            p.logout {
                display: none;
            }
        </style>";
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

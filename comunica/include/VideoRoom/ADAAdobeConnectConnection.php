<?php

namespace Lynxlab\ADA\Comunica\VideoRoom;

use AdobeConnect\Connection;

class ADAAdobeConnectConnection extends Connection
{
    /**
     * @return bool     true if is already logged in / false if is not logged in
     */
    public function getLoggedIn()
    {
        if ($this->loggedIn) {
            return true;
        }

        return false;
    }

    public function unsetCookie()
    {
        unset($_COOKIE[$this->config->getCookieName()]);
        unset($this->cookie);
    }

    public function getConnectionCookie()
    {
        return $this->getCookie();
    }
}

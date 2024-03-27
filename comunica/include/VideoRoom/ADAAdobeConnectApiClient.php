<?php

namespace Lynxlab\ADA\Comunica\VideoRoom;

use AdobeConnect\ApiClient;

class ADAAdobeConnectApiClient extends ApiClient {

    /**
     * Get logged in.
     *
     * @return bool
     */
    public function getLoggedIn()
    {
        return $this->getConnection()->getLoggedIn();
    }

    public function unsetCookie() {
        return $this->getConnection()->unsetCookie();
    }

    public function getConnection() {
        return $this->connection;
    }
}

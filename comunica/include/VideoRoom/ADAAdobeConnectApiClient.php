<?php

use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;

use Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectConnection;

use Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectApiClient;

// Trigger: ClassWithNameSpace. The class ADAAdobeConnectApiClient was declared with namespace Lynxlab\ADA\Comunica\VideoRoom. //

namespace Lynxlab\ADA\Comunica\VideoRoom;

use AdobeConnect\ApiClient;

class ADAAdobeConnectApiClient extends ApiClient
{
    /**
     * Get logged in.
     *
     * @return bool
     */
    public function getLoggedIn()
    {
        return $this->getConnection()->getLoggedIn();
    }

    public function unsetCookie()
    {
        return $this->getConnection()->unsetCookie();
    }

    /**
     * Undocumented function
     *
     * @return \Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}

<?php

/**
 * CleanQueryString.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Middleware;

use Lynxlab\ADA\API\Controllers\TokenController;
use Lynxlab\ADA\API\OAuth2Storage\ADA as ADAOauth2Storage;
use OAuth2\Request as OAuth2Request;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server as OAuth2Server;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to clean the query string from
 * unwanted and unneeded data
 *
 * @author giorgio
 */
class OAuth2Auth implements MiddlewareInterface
{
    /**
     * OAuth2 server object
     *
     * @var \OAuth2\Server
     */
    private $server;

    /**
     *
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Class constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $dsn      = ADA_COMMON_DB_TYPE . ':dbname=' . ADA_COMMON_DB_NAME . ';host=' . ADA_COMMON_DB_HOST;
        $username = ADA_COMMON_DB_USER;
        $password = ADA_COMMON_DB_PASS;

        $storage = new ADAOauth2Storage(['dsn' => $dsn, 'username' => $username, 'password' => $password]);
        $this->server = new OAuth2Server($storage);
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle a request for an OAuth2.0 Access Token and send the response to the client.
     * Will set the 'authUserID' attribute to the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $oauth2request = OAuth2Request::createFromGlobals();
        $oauth2response = new OAuth2Response();
        if (!$this->server->verifyResourceRequest($oauth2request, $oauth2response)) {
            return TokenController::convertOAuth2Response(
                $oauth2response,
                $this->responseFactory->createResponse()
            );
        } else {
            $data = $this->server->getAccessTokenData($oauth2request, new OAuth2Response());
            $request = $request->withAttribute('authUserID', (int) ($data['user_id'] ?? 0));
            $response = $handler->handle($request);
            return $response->withHeader('X-ADA-auth-userid', $request->getAttribute('authUserID'));
        }
    }
}

<?php

/**
 * token.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Controllers;

use InvalidArgumentException;
use LogicException;
use Lynxlab\ADA\API\OAuth2Storage\ADA as ADAOauth2Storage;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Request as OAuth2Request;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server as OAuth2Server;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReflectionClass;
use RuntimeException;

class TokenController
{
    /**
     * The appplication container
     *
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Verify passed credentials and generate an access token
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getAccessToken(Request $request, Response $response, array $args): Response
    {
        // https://bshaffer.github.io/oauth2-server-php-docs/cookbook/

        $dsn      = ADA_COMMON_DB_TYPE . ':dbname=' . ADA_COMMON_DB_NAME . ';host=' . ADA_COMMON_DB_HOST;
        $username = ADA_COMMON_DB_USER;
        $password = ADA_COMMON_DB_PASS;

        $storage = new ADAOauth2Storage(['dsn' => $dsn, 'username' => $username, 'password' => $password]);
        $server = new OAuth2Server($storage);

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $server->addGrantType(new ClientCredentials($storage));

        return static::convertOAuth2Response(
            $server->handleTokenRequest(OAuth2Request::createFromGlobals()),
            $response
        );
    }

    /**
     * Converts an OAuth2Response to Response.
     *
     * @param OAuth2Response $oauth2response
     * @param Response $response
     * @return Response
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function convertOAuth2Response(OAuth2Response $oauth2response, Response $response): Response
    {
        $oauth2Arr = static::OAuth2ResponseToArray($oauth2response);
        foreach ($oauth2Arr as $respKey => $respVal) {
            if ($respKey == 'statusCode') {
                $response = $response->withStatus((int) $respVal, $oauth2Arr['statusText'] ?? '');
            } elseif ($respKey == 'parameters') {
                $response = $response->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode($respVal));
            } elseif ($respKey == 'httpHeaders') {
                foreach ($respVal as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }
            }
        }
        return $response;
    }

    /**
     * Converts an OAuth2 response to array.
     *
     * @param \OAuth2\Response $obj
     * @return array
     */
    private static function OAuth2ResponseToArray(OAuth2Response $obj): array
    {
        $reflect = new ReflectionClass($obj);
        $props = $reflect->getProperties();
        $array = [];
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $array[$prop->getName()] = $prop->getValue($obj);
            $prop->setAccessible(false);
        }
        return $array;
    }
}

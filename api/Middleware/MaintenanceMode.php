<?php

/**
 * MaintenanceMode.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to check if ADA is in maintenance mode
 *
 * @author giorgio
 */
class MaintenanceMode implements MiddlewareInterface
{
    /**
     *
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Cleans the querystring from unwanted parameters.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('foo', 'bar');
        if (defined('MAINTENANCE_MODE') && true === MAINTENANCE_MODE) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode([
                'error' => 'ADA is in mantenaince mode, request cannot be processed.',
            ]));
            return $response;
        }
        return $handler->handle($request);
    }
}

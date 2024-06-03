<?php

/**
 * ADAHeaders.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to add custom ADA Headers to the response.
 *
 * @author giorgio
 */
class ADAHeaders implements MiddlewareInterface
{
    /**
     * Undocumented variable
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Add ADA custom headers to the response
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Add custom ADA headers.
        $response = $response->withHeader('X-ADA-version', ADA_VERSION);
        if ($this->container->has('usedversion')) {
            $response = $response->withHeader('X-ADA-apiVersion', $this->container->get('usedversion'));
        }

        return $response;
    }
}

<?php

/**
 * ResolveLatestVersion.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Middleware;

use DI\Container;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware that will set the response status to 400
 * if the requested format parameter is not supported
 *
 * @author giorgio
 */
class ResolveLatestVersion implements MiddlewareInterface
{
    private $latestVersion = null;

    private $container = null;

    private $responseFactory;

    /**
     * Class constructor.
     *
     * @param \DI\Container $container
     */
    public function __construct(Container $container, ResponseFactoryInterface $responseFactory)
    {
        $this->container = $container;
        $this->responseFactory = $responseFactory;
        $this->latestVersion = $this->container->get('latestversion');
    }

    /**
     * If format is not supported sets response status to 400 and body to 'Unsupported Output Format'.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = strtolower(str_ireplace($this->container->get('basepath') . '/', '', $request->getRequestTarget()));
        if (!str_starts_with($target, 'token')) {
            $params = $request->getQueryParams();
            if (str_starts_with($target, 'latest/')) {
                // target STARTS WITH 'latest', replace it with latest version
                $target = str_replace('latest', $this->latestVersion, $request->getRequestTarget());

                $uri = $request->getUri();
                $uri = $uri->withPath(str_replace('latest', $this->latestVersion, $uri->getPath()))
                    ->withQuery(str_replace('v=latest', 'v=' . $this->latestVersion, $uri->getQuery()));

                $params['v'] = $this->latestVersion;

                $request = $request->withRequestTarget($target)->withUri($uri, true)->withQueryParams($params);
                $this->container->set('usedversion', $params['v']);
            } elseif (1 === preg_match("/^v\d+\//i", $target)) {
                /**
                 * target DOES START WITH 'v' FOLLOWED BY 1 OR MORE DIGITS AND A SLASH.
                 * Check if requested version is supported and set usedversion in the container.
                 */
                [$reqV, $latestV] = array_map(fn ($el) => ltrim($el, 'v'), [$params['v'], $this->latestVersion]);
                if (version_compare($reqV, $latestV, '<=')) {
                    $this->container->set('usedversion', $params['v']);
                } else {
                    $response = $this->responseFactory->createResponse();
                    $response = $response->withStatus(StatusCodeInterface::STATUS_VERSION_NOT_SUPPORTED);
                    $response->getBody()->write("Unsupported version: " . $params['v']);
                    return $response;
                }
            } elseif (0 === preg_match("/^v\d+\//i", $target)) {
                // target DOES NOT START WITH 'v' FOLLOWED BY 1 OR MORE DIGITS AND A SLASH, redirect to 'latest'.
                return $this->responseFactory->createResponse()
                    ->withHeader(
                        'Location',
                        $this->container->get('basepath') . '/latest/' . $target
                    )
                    ->withStatus(StatusCodeInterface::STATUS_TEMPORARY_REDIRECT);
            }
        }

        return $handler->handle($request);
    }
}

<?php

/**
 * FormatSupported.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerInterface;
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
class FormatSupported implements MiddlewareInterface
{
    private $formats = [];
    private $defaultFormat  = 'json';

    /**
     *
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * Class constructor.
     *
     * @param ContainerInterface $container
     * @param ResponseFactoryInterface $responseFactory
     * @param array $extraFormats
     */
    public function __construct(ContainerInterface $container, ResponseFactoryInterface $responseFactory, array $extraFormats = [])
    {
        $this->formats = array_merge($container->get('supportedFormats') ?? [], $extraFormats);
        $this->defaultFormat = $container->get('defaultFormat') ?? 'json';
        $this->responseFactory = $responseFactory;
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
        $format = $request->getQueryParams()['format'] ?? $this->defaultFormat;
        if (!in_array($format, $this->formats)) {
            $response = $this->responseFactory->createResponse();
            $response = $response->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST, "Unsupported output format: $format");
            $response->getBody()->write("Unsupported output format: $format");
            return $response;
        }

        return $handler->handle($request);
    }
}

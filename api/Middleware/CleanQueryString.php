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
class CleanQueryString implements MiddlewareInterface
{
    private $parametersToRemove = null;

    public function __construct(array $parametersToRemove = ['format', 'v', 'XDEBUG_SESSION_START'])
    {
        if (is_array($parametersToRemove)) {
            $this->parametersToRemove = $parametersToRemove;
        } else {
            $this->parametersToRemove = [$parametersToRemove];
        }
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
        $params = $request->getQueryParams();
        if (!is_null($this->parametersToRemove) && !empty($this->parametersToRemove)) {
            foreach ($this->parametersToRemove as $parameter) {
                if (strlen($parameter) > 0 && isset($params[$parameter])) {
                    // unset unwanted parameter
                    unset($params[$parameter]);
                }
            }
        }
        $request = $request->withQueryParams($params);
        return $handler->handle($request);
    }
}

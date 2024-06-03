<?php

/**
 * PhpView.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Views;

use Psr\Http\Message\ResponseInterface as Response;

class Php extends AbstractApiView
{
    /**
     * Renderers output data
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param mixed $data
     * @param string $endpoint
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function render(Response $response, mixed $data = null, string $endpint = null): Response
    {
        $response = $response->withHeader('Content-Type', 'text/php');

        $response->getBody()->write(
            (string)serialize(
                $data
            )
        );

        return $response;
    }
}

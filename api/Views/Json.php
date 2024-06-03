<?php

/**
 * JsonView.php
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

final class Json extends AbstractApiView
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
    public function render(Response $response, mixed $data = null, string $endpoint = null): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(
            (string)json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );

        return $response;
    }
}

<?php

/**
 * AdaApiInterface.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Controllers\Common;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AdaApiInterface, all controllers must implement this
 *
 * @author giorgio
 */
interface AdaApiInterface
{
    public function get(Request $request, Response &$response, array $args): Response|array;
    public function post(Request $request, Response &$response, array $args): Response|array;
    public function put(Request $request, Response &$response, array $args): Response|array;
    public function delete(Request $request, Response &$response, array $args): Response|array;
}

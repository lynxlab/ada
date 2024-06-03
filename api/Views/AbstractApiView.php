<?php

/**
 * AbstractApiView.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Views;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractApiView
{
    private $container = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function render(Response $response, mixed $data = null, string $endpoint = null): Response
    {
        return $response;
    }

    /**
     * Get the value of apiVersion
     */
    public function getApiVersion()
    {
        return $this->container->has('usedversion') ? $this->container->get('usedversion') : $this->container->get('latestversion');
    }

    /**
     * Get the value of basePath
     */
    public function getBasepath()
    {
        return $this->container->has('basepath') ? $this->container->get('basepath') : '';
    }

    /**
     * Get the value of container
     */
    public function getContainer()
    {
        return $this->container;
    }
}

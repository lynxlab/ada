<?php

/**
 * Rss2View.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Views;

use FeedWriter\RSS2 as FeedWriterRSS2;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

final class Rss2 extends Rss
{
    /**
     * Class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->setFeedObject(new FeedWriterRSS2());
    }

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
        $this->getFeedObject()->setLink(HTTP_ROOT_DIR . $this->getBasepath() . $this->getApiVersion() . '/' . $endpoint . '.rss2');
        return parent::render($response, $data, $endpoint);
    }
}

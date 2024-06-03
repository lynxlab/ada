<?php

/**
 * RssView.php
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

abstract class Rss extends AbstractApiView
{
    /**
     * The FeedWriter object.
     *
     * @var \FeedWriter\Feed
     */
    protected $feedObject;

    protected static function buildArray(array $data): array
    {
        return array_map(fn ($element) => [
            'title'       => $element['name'] ?? '',
            'link'        => $element['link'] ?? HTTP_ROOT_DIR,
            'description' => $element['description'] ?? '',
        ], $data);
    }

    public function render(Response $response, mixed $data = null, string $endpoint = null): Response
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        $rssArray = self::buildArray($data);

        $this->feedObject->setTitle('Courses Available on ' . PORTAL_NAME);
        $this->feedObject->setChannelElement('language', 'en-us');
        $this->feedObject->setChannelElement('pubDate', date(DATE_RSS, time()));

        foreach ($rssArray as $rssElement) {
            $newItem = $this->feedObject->createNewItem();
            $newItem->addElementArray($rssElement);
            $this->feedObject->addItem($newItem);
        }

        $response = $response->withHeader('Content-Type', $this->getFeedObject()->getMIMEType());
        $response->getBody()->write($this->getFeedObject()->generateFeed());

        return $response;
    }

    /**
     * Get the value of feedObject
     */
    public function getFeedObject()
    {
        return $this->feedObject;
    }

    /**
     * Set the value of feedObject
     */
    public function setFeedObject($feedObject): self
    {
        $this->feedObject = $feedObject;

        return $this;
    }
}

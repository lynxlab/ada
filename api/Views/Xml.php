<?php

/**
 * XmlView.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Views;

use Lynxlab\ADA\Main\ArrayToXML\ArrayToXML;
use Psr\Http\Message\ResponseInterface as Response;

class Xml extends AbstractApiView
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

        /**
         * if rootName is a plural, e.g. "users" than the element name
         * is going to be singular, e.g. "user"
         * else the element name is going to be the root name and the
         * root name shall have a _info appended
         */

        $rootName = $endpoint ?? '';
        if (str_ends_with($rootName, 's')) {
            $elementName = substr($rootName, 0, strlen($rootName) - 1);
        } else {
            $elementName = $rootName;
            $rootName .= '_info';
        }

        /**
         * If the first element of data['output'] is an array than build an array
         * of array (to nicely nest the produced XML)
         * else it's enough to nest one level
         */

        if (is_object($data)) {
            $data = (array) $data;
        }

        if (is_array(reset($data))) {
            foreach ($data as $element) {
                $outArr[$elementName][] = $element;
            }
        } else {
            $outArr[$elementName] = $data;
        }

        $response = $response->withHeader('Content-Type', 'application/xml');

        $response->getBody()->write(
            ArrayToXML::toXml($outArr, $rootName)
        );

        return $response;
    }
}

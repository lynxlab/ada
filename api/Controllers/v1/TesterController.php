<?php

/**
 * TesterController.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2014, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Controllers\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lynxlab\ADA\API\Controllers\Common\AbstractController;
use Lynxlab\ADA\API\Controllers\Common\AdaApiInterface;
use Lynxlab\ADA\API\Controllers\Common\APIException;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Tester controller for handling /testers API endpoint
 *
 * @author giorgio
 */
class TesterController extends AbstractController implements AdaApiInterface
{
    /**
     * GET method.
     *
     * Must be called with empty array parameter and shall
     * return all the tester that an authenticated user calling
     * the API is subscribed to.
     *
     * (non-PHPdoc)
     * @see \AdaApi\AdaApiInterface::get()
     */
    public function get(Request $request, Response &$response, array $params): array
    {
        if (empty($params)) {
            // This GLOBAL is needed by the MultiPort
            $GLOBALS['common_dh'] = $this->common_dh;
            $testers = MultiPort::getTestersPointersAndIds();

            if (!AMADB::isError($testers)) {
                // need to map $testers to id and name pairs
                if (!MULTIPROVIDER && (is_null($this->authUserID) || !is_array($this->authUserTesters))) {
                    throw new APIException('No Auth User or Testers Found', StatusCodeInterface::STATUS_NOT_FOUND);
                } else {
                    foreach ($testers as $testername => $testerid) {
                        if (MULTIPROVIDER || in_array($testername, $this->authUserTesters)) {
                            $retArray[] = ['id' => $testerid, 'name' => $testername];
                        }
                    }
                    if (isset($retArray) && count($retArray) > 0) {
                        return $retArray;
                    } else {
                        throw new APIException('No Tester Found', StatusCodeInterface::STATUS_NOT_FOUND);
                    }
                }
            } else {
                throw new APIException('No Tester Found', StatusCodeInterface::STATUS_NOT_FOUND);
            }
        } else {
            throw new APIException('Wrong Parameters', StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    public function post(Request $request, Response &$response, array $args): Response
    {
        return $response;
    }

    public function put(Request $request, Response &$response, array $args): Response
    {
        return $response;
    }

    public function delete(Request $request, Response &$response, array $args): Response
    {
        return $response;
    }
}

<?php

/**
 * SubscriptionController.php
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
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Switcher\Subscription;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Subscription controller for handling /subscriptions API endpoint
 *
 * @author giorgio
 */
class SubscriptionController extends AbstractController implements AdaApiInterface
{
    public function get(Request $request, Response &$response, array $params): Response
    {
        return $response;
    }

    /**
     * POST method.
     *
     * Subscribes a student to a course instance.
     * The params array must have the id_course_instance and
     * username parameters properly set.
     *
     * (non-PHPdoc)
     * @see \AdaApi\AdaApiInterface::post()
     */
    public function post(Request $request, Response &$response, array $args): array
    {
        /**
         * Check if header says it's json
         */
        if (strcmp($request->getHeaderLine('Content-Type'), 'application/json') === 0) {

            /**
             *  SLIM has converted the body to an array alreay
             */
            $subscriptionArr = $request->getParsedBody();
        } elseif (!empty($params) && is_array($params)) {

            /**
             * Assume we've been passed an array
             */
            $subscriptionArr = $params;
        } else {
            throw new APIException('Wrong Parameters', StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        /**
         * Check if passed username and id_course_instance are OK
         */
        if (
            isset($subscriptionArr['username']) && DataValidator::validateUsername($subscriptionArr['username']) &&
            isset($subscriptionArr['id_course_instance']) && DataValidator::isUinteger($subscriptionArr['id_course_instance'])
        ) {
            /**
             * This GLOBAL is needed by the MultiPort and Translator class
             */
            $GLOBALS['common_dh'] = $this->common_dh;

            /*
             * This GLOBAL is needed by almost everyone
             */
            $GLOBALS['dh'] = new AMADataHandler(MultiPort::getDSN($this->authUserTesters[0]));
            $dh = $GLOBALS['dh'];

            $canSubscribeUser = false;
            $courseInstanceObj = new CourseInstance(intval(trim($subscriptionArr['id_course_instance'])));

            if ($courseInstanceObj instanceof  CourseInstance && $courseInstanceObj->isFull()) {
                $startStudentLevel = $courseInstanceObj->start_level_student;

                $subscriberObj = MultiPort::findUserByUsername($subscriptionArr['username']);
                if ($subscriberObj instanceof ADAUser) {
                    $result = $dh->studentCanSubscribeToCourseInstanceXX($subscriberObj->getId(), $courseInstanceObj->getId());
                    if (!AMADataHandler::isError($result) && $result !== false) {
                        $canSubscribeUser = true;
                    }

                    if ($canSubscribeUser) {
                        $s = new Subscription($subscriberObj->getId(), $courseInstanceObj->getId(), 0, $startStudentLevel);
                        $s->setSubscriptionStatus(ADA_STATUS_SUBSCRIBED);
                        Subscription::addSubscription($s);

                        /**
                         * Subscribed successfully
                         */
                        $saveResults = [
                            'status' => 'SUCCESS',
                            'message' => 'User successfully subscribed to course instance',
                        ];
                    } else {

                        /**
                         * An error occoured
                         */
                        $saveResults = [
                            'status' => 'FAILURE',
                            'message' => 'Cannot complete request, perhaps user is subscribed already?',
                        ];
                        $response = $response->withStatus(StatusCodeInterface::STATUS_CONFLICT);
                    }
                } else {
                    throw new APIException('No User Found', StatusCodeInterface::STATUS_NOT_FOUND);
                }
            } else {
                throw new APIException('Course Instance Not Found', StatusCodeInterface::STATUS_NOT_FOUND);
            }
        } else {
            throw new APIException('Wrong Parameters', StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        /**
         * Final check: if all OK return the data else throw the exception
         */
        if (is_array($saveResults)) {
            return $saveResults;
        } else {
            throw new APIException('Unkonwn error in subscription post method', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        }
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

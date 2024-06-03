<?php

/**
 * InfoController.php
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
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Info controller for handling /info API endpoint
 *
 * @author giorgio
 */
class InfoController extends AbstractController implements AdaApiInterface
{
    /**
     * GET method.
     *
     * (non-PHPdoc)
     * @see \Lynxlab\ADA\API\Controllers\Common\AdaApiInterface::get()
     */
    public function get(Request $request, Response $response, array $params): array
    {
        /**
         * Get all published courses:
         * - for all providers if it's MULTIPROVIDER and
         *   a provider has not been passed
         * - for the passed provider if it's MULTIPROVIDER and
         *   a provider has been passed
         * - for the selected provider if it's NOT MULTIPROVIDER
         *   and a provider has been selected by 3d level domain
         */
        if (MULTIPROVIDER) {
            if (count($params) === 1 && isset($params['provider']) && strlen($params['provider']) > 0) {
                // a tester has been passed
                $userProvider = $params['provider'];
            } elseif (count($params) > 1) {
                throw new APIException('Wrong Parameters: please pass the provider only', 400);
            }
        } else {
            if (empty($params)) {
                if (isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
                    $userProvider = $GLOBALS['user_provider'];
                } else {
                    throw new APIException('Server error: provider is not set', 500);
                }
            } else {
                throw new APIException('Wrong Parameters', 400);
            }
        }

        if (isset($userProvider)) {
            $userProviderInfo = $this->common_dh->getTesterInfoFromPointer($userProvider);
            $user_provider_id = (!AMADB::isError($userProviderInfo)) ? $userProviderInfo[0] : null;
        } else {
            // this means to get courses on all testers
            $user_provider_id = null;
        }

        if (!MULTIPROVIDER && is_null($user_provider_id)) {
            throw new APIException('Selected provider ' . $userProvider . ' is not found in the DB', 404);
        }

        $publishedServices = $this->common_dh->getPublishedCourses($user_provider_id);

        /**
         * following code reflects info.php ada file
         */
        foreach ($publishedServices as $service) {
            $serviceId = $service['id_servizio'];
            $coursesAr = $this->common_dh->getCoursesForService($serviceId);
            if (!AMADB::isError($coursesAr)) {
                $currentTesterId = 0;
                $currentTester = '';
                $tester_dh = null;
                foreach ($coursesAr as $courseData) {
                    $courseId = $courseData['id_corso'];
                    $flagCourseHasInstance = false;
                    if ($courseId != PUBLIC_COURSE_ID_FOR_NEWS) {
                        $newTesterId = $courseData['id_tester'];
                        if ($newTesterId != $currentTesterId) { // stesso corso su altro tester ?
                            $testerInfoAr = $this->common_dh->getTesterInfoFromId($newTesterId, AMA_FETCH_ASSOC);
                            if (!AMADB::isError($testerInfoAr)) {
                                $tester = $testerInfoAr['puntatore'];
                                $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                                $currentTesterId = $newTesterId;
                                $course_dataHa = $tester_dh->getCourse($courseId);
                                $instancesAr = $tester_dh->courseInstanceSubscribeableGetList([
                                    'data_inizio_previsto',
                                    'durata',
                                    'data_fine',
                                    'title',
                                ], $courseId);
                                if (is_array($instancesAr) && count($instancesAr) > 0) {
                                    $flagCourseHasInstance = true;
                                }
                            }
                        }
                        if ($flagCourseHasInstance) {
                            $moreInfoLink = HTTP_ROOT_DIR . "/info.php?op=course_info&id=" . $serviceId;
                        } else {
                            $moreInfoLink = null;
                        }
                        // giorgio 13/ago/2013 if it's not the news course, add it to the displayed results
                        if (defined('PUBLIC_COURSE_ID_FOR_NEWS') && PUBLIC_COURSE_ID_FOR_NEWS != $courseData['id_corso']) {
                            $returnArray[] = [
                                'name' => $service['nome'],
                                'description' => $service['descrizione'],
                                'provider' => ['pointer' => $testerInfoAr['puntatore'], 'name' => $testerInfoAr['nome']],
                            ];
                            if (!is_null($moreInfoLink)) {
                                $returnArray[count($returnArray) - 1]['link'] = $moreInfoLink;
                            }
                        }
                    }
                }
            }
        }

        if (count($returnArray)) {
            return $returnArray;
        } else {
            throw new APIException('No courses found', StatusCodeInterface::STATUS_NOT_FOUND);
        }
    }

    public function post(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    public function put(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        return $response;
    }
}

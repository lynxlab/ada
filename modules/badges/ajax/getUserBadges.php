<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\BadgesActions;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(BadgesActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$badges = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    $withRewardLabel = true;

    if (in_array($userObj->getType(), [ AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR ])) {
        /**
         * Switcher and Tutor can view all course's badges by not passing a userId
         */
        if (isset($params['userId'])) {
            $userId = intval($params['userId']);
        } else {
            $userId = null;
            $withRewardLabel = false;
        }
    } else {
        $userId = $userObj->getId();
    }

    foreach ($userObj->getTesters() as $provider) {
        $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($provider));

        /**
         * filter course instance that are associated to a level of service having:
         *
         * - nonzero value in isPublic, so that all instances of public courses will not be shown here
         * - zero value in IsPublic and the service level in the $GLOBALS['userHiddenServiceTypes'] array, to hide autosubscription instances
         */
        if (is_null($userId) && isset($params['courseInstanceId'])) {
            $courseInstances = $bdh->getInstanceWithCourse($params['courseInstanceId']);
        } else {
            $courseInstances = $bdh->getCourseInstancesForThisStudent($userId, true);
        }
        if (AMADB::isError($courseInstances)) {
            $courseInstances = [];
        }
        $courseInstances = array_filter($courseInstances, function ($courseInstance) {
            if (is_null($courseInstance['tipo_servizio'])) {
                $courseInstance['tipo_servizio'] = DEFAULT_SERVICE_TYPE;
            }
            $actualServiceType = !is_null($courseInstance['istanza_tipo_servizio']) ? $courseInstance['istanza_tipo_servizio'] : $courseInstance['tipo_servizio'];
            if (intval($_SESSION['service_level_info'][$actualServiceType]['isPublic']) !== 0) {
                $filter = false;
            } elseif (in_array($actualServiceType, $GLOBALS['userHiddenServiceTypes'])) {
                $filter = false;
            }
            return ($filter ?? true);
        });

        // filter the instances array according to params
        $courseInstances = array_filter(
            $courseInstances,
            function ($instance) use ($params) {
                if (isset($params['courseId']) && isset($params['courseInstanceId'])) {
                    return (intval($params['courseId']) === intval($instance['id_corso']) &&
                            intval($params['courseInstanceId']) === intval($instance['id_istanza_corso']));
                } elseif (isset($params['courseId'])) {
                    return intval($params['courseId']) === intval($instance['id_corso']);
                } elseif (isset($params['courseInstanceId'])) {
                    return intval($params['courseInstanceId']) === intval($instance['id_istanza_corso']);
                }
                return true;
            }
        );
        // useful $courseInstances keys are: id_corso, id_istanza_corso, titolo (course name), title (course instance name)

        foreach ($courseInstances as $instance) {
            $courseId = $instance['id_corso'];
            $courseInstanceId = $instance['id_istanza_corso'];
            // load all the badges for this course
            $courseBadges = $bdh->findBy('CourseBadge', ['id_corso' => $courseId]);

            if (!AMADB::isError($courseBadges) && is_array($courseBadges) && count($courseBadges) > 0) {
                if (!array_key_exists($courseId, $badges)) {
                    $badges[$courseId] = [
                        'course' => [
                            'id' => $courseId,
                            'name' => $instance['titolo'],
                            'withRewardLabel' => $withRewardLabel,
                        ],
                        'courseInstances' => [],
                    ];
                }

                $badges[$courseId]['courseInstances'][$courseInstanceId] = [
                    'id' => $courseInstanceId,
                    'name' => $instance['title'],
                ];

                $tmpBadges = [];
                /**
                 * @var \Lynxlab\ADA\Module\Badges\CourseBadge $cb
                 */
                foreach ($courseBadges as $cb) {
                    $badge = $bdh->findBy('Badge', [ 'uuid' => $cb->getBadgeUuid() ]);
                    $issuedOn = null;
                    if (!AMADB::isError($badge) && is_array($badge) && count($badge) === 1) {
                        /**
                         * @var \Lynxlab\ADA\Module\Badges $badge
                         */
                        $badge = reset($badge);
                        if (!is_null($userId)) {
                            $reward = $bdh->findBy('RewardedBadge', [
                                'badge_uuid' => $badge->getUuid(),
                                'id_utente'  => $userId,
                                'id_corso'   => $courseId,
                                'id_istanza_corso' => $courseInstanceId,
                                'approved' => 1,
                            ]);
                            if (!AMADB::isError($reward) && is_array($reward) && count($reward) === 1) {
                                /**
                                 * @var \Lynxlab\ADA\Module\Badges\RewardedBadge $reward
                                 */
                                $reward = reset($reward);
                                $issuedOn = $reward->getIssuedOn();
                            }
                        }
                    }
                    $tmpBadges[] = $badge->toArray() + [ 'imageurl' => $badge->getImageUrl(), 'issuedOn' => $issuedOn ];
                }
                /**
                 * the tmpBadges array now holds all the badges of the course each having
                 * - the badge imageurl
                 * - the issuedOn field set to null if the student does not has rewarded the badge
                 *
                 * now sort the badges array by badge name asc
                 */
                usort($tmpBadges, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
                $badges[$courseId]['courseInstances'][$courseInstanceId]['badges'] = $tmpBadges;
            }
        }
    }
}

if (count($badges) <= 0) {
    header(' ', true, 404);
    $badges = [];
}
header('Content-Type: application/json');
die(json_encode($badges));

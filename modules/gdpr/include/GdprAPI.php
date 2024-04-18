<?php

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use Lynxlab\ADA\Module\GDPR\GdprRequest;
use Lynxlab\ADA\Module\GDPR\GdprUser;
use Lynxlab\ADA\Module\GDPR\GdprUserType;
use Lynxlab\ADA\Module\Impersonate\Utils;

/**
 * class for managing Gdpr API to be used by external modules
 *
 * @author giorgio
 */

class GdprAPI
{
    /**
     * @var AMAGdprDataHandler
     */
    private $dh;

    /**
     * constructor
     */
    public function __construct($tester = null)
    {
        if (isset($GLOBALS['dh']) && $GLOBALS['dh'] instanceof AMAGdprDataHandler) {
            $this->dh = $GLOBALS['dh'];
        } else {
            if (is_null($tester)) {
                if (array_key_exists('sess_selected_tester', $_SESSION)) {
                    $tester = $_SESSION['sess_selected_tester'];
                } elseif (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
                    $tester = $GLOBALS['user_provider'];
                }
            }
            $this->dh = AMAGdprDataHandler::instance(MultiPort::getDSN($tester));
        }
        $this->dh->setObjectClassesFromRequest();
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        $this->dh->disconnect();
    }

    /**
     * Gets all the GdprUserType' objects
     * @return array
     */
    public function getGdprUserTypes()
    {
        return $this->dh->findAll('GdprUserType');
    }

    /**
     * Gets the GdprUserTypes marked as 'none'
     *
     * @return array
     */
    public function getGdprNoneUserTypes()
    {
        $noneTypes = [GdprUserType::NONE];
        return array_filter($this->getGdprUserTypes(), fn ($el) => in_array($el->getId(), $noneTypes));
    }

    /**
     * Loads a GdprUser from the passed user
     *
     * @param integer|\ADALoggableUser $userID
     * @return GdprUser
     */
    public function getGdprUserByID($userID)
    {
        if ($userID instanceof ADALoggableUser) {
            $userID = $userID->getId();
        } elseif (is_numeric($userID)) {
            $userID = intval($userID);
        } else {
            $userID = -1;
        }
        $res = $this->dh->findBy('GdprUser', ['id_utente' => $userID]);
        return reset($res);
    }

    /**
     * Checks if a user is of the passed GdprUserType
     *
     * @param integer|\ADALoggableUser $user
     * @param integer|array $gdprUserTypes array of GdprUserType ids
     * @return boolean
     */
    public function isGdprUserType($user, $gdprUserTypes)
    {
        if ($user instanceof ADALoggableUser) {
            $user = $user->getId();
        }
        if (!is_array($gdprUserTypes)) {
            $gdprUserTypes = [$gdprUserTypes];
        }
        $result = array_filter(
            $this->dh->findBy('GdprUser', ['id_utente' => intval($user)]),
            fn (GdprUser $el) => $el->hasTypes($gdprUserTypes, $this)
        );
        return (count($result) > 0);
    }

    /**
     * Saves a GdprUser object
     *
     * @param GdprUser $gdprUser
     */
    public function saveGdprUser(GdprUser $gdprUser)
    {
        return $this->dh->saveGdprUser($gdprUser->toArray());
    }

    /**
     * Saves a Gdpr Request
     *
     * @param array $data
     * @return \Lynxlab\ADA\Module\GDPR\GdprRequest
     */
    public function saveRequest($data)
    {
        return $this->dh->saveRequest($data);
    }

    /**
     * Closes a request
     *
     * @param string|GdprRequest $request the uuid of the request or a GdprRequest instance
     * @param number $closedBy id of the user closing the request. null to get it from session user
     */
    public function closeRequest($request, $closedBy = null)
    {
        $this->dh->closeRequest($request, $closedBy);
    }

    /**
     * Confirms a request
     *
     * @param string|GdprRequest $request the uuid of the request or a GdprRequest instance
     */
    public function confirmRequest($request)
    {
        $this->dh->confirmRequest($request);
    }

    /**
     * Saves a GdprPolicy
     * @param array $data
     * @return \Lynxlab\ADA\Module\GDPR\GdprPolicy|mixed
     */
    public function savePolicy($data)
    {
        return $this->dh->savePolicy($data);
    }

    /**
     * Gets the array of the published policies objects,
     * either mandatory or not
     *
     * @return array
     */
    public function getPublishedPolicies()
    {
        return $this->dh->getPublishedPolicies();
    }

    /**
     * Gets the array of the mandatory policies objects
     *
     * @return array
     */
    public function getMandatoryPolicies()
    {
        return $this->dh->getMandatoryPolicies();
    }

    /**
     * Gets the array of the policies accepted by the user
     *
     * @param integer $userID
     * @return array
     */
    public function getUserAcceptedPolicies($userID)
    {
        return $this->dh->getUserAcceptedPolicies($userID);
    }

    /**
     * Gets the array of the policies accepted and rejected by the user
     *
     * @param integer $userID
     * @return array
     */
    public function getUserPolicies($userID)
    {
        return $this->dh->getUserPolicies($userID);
    }

    /**
     * Saves the policies accepted by the user
     *
     * @param integer $data
     * @return \stdClass
     */
    public function saveUserPolicies($data)
    {
        return $this->dh->saveUserPolicies($data, $this->getPublishedPolicies(), $this->getUserPolicies($data['userId']));
    }

    /**
     * Checks if the passed users have accepted all the mandatory policies
     * the returned array will have the following keys:
     * - accepted: true or false
     * - details: if accepted is true: null
     *            if accepted is false: current (holding current policy) and user (holding the accepted privacy version)
     *            if accepted is false and no user accepted version: null
     *
     * @param array $userIDs
     * @return array
     */
    public function checkMandatoryPoliciesForUserArray(array $userIDs)
    {
        $retArr = [];
        $policies = $this->getMandatoryPolicies();
        $userPolicies = $this->dh->getUserPolicies($userIDs);
        foreach ($userIDs as $userID) {
            if (array_key_exists($userID, $userPolicies)) {
                $retArr[$userID] = [];
                $accepted = true;
                /** @var GdprPolicy $policy */
                foreach ($policies as $policy) {
                    // user MUST accept all mandatory policies!!
                    $accepted = $accepted && self::ckeckAcceptedPolicy($policy, $userPolicies[$userID]);
                    if (false === $accepted) {
                        $retArr[$userID] = [
                            'accepted' => false,
                            'details' => [
                                'current' => $policy,
                                'user' => $userPolicies[$userID][$policy->getPolicyContentId()],
                            ],
                        ];
                        // break out of the loop at the first not accepted policy
                        break;
                    }
                }
                if ($accepted) {
                    $retArr[$userID] = [ 'accepted' => $accepted, 'details' => null ];
                }
            } else {
                $retArr[$userID] = [ 'accepted' => false, 'details' => null ];
            }
        }
        return $retArr;
    }

    /**
     * Checks if the passed user has accepted all the mandatory policies
     *
     * @param \ADALoggableUser $userObj
     * @return boolean
     */
    public function checkMandatoryPoliciesForUser(ADALoggableUser $userObj)
    {
        if ($userObj->getType() == AMA_TYPE_ADMIN) {
            /**
             * the ADMIN is not required to accept all mandatory policies
             */
            return true;
        } elseif (defined('MODULES_IMPERSONATE') && MODULES_IMPERSONATE && Utils::isImpersonating()) {
            /**
             * if impersonate module is active and logged user is impersonating another user
             * no policy must be accepted
             */
            return true;
        } else {
            /**
             * other types of users must be checked
             */
            $policies = $this->getMandatoryPolicies();
            if (count($policies) > 0) {
                $okToLogin = true;
                $userPolicies = $this->getUserAcceptedPolicies($userObj->getId());
                /** @var GdprPolicy $policy */
                foreach ($policies as $policy) {
                    // user MUST accept all mandatory policies!!
                    $okToLogin = $okToLogin && self::ckeckAcceptedPolicy($policy, $userPolicies);
                }
                return $okToLogin;
            } else {
                /**
                 * if no mandatory policies, the user is OK
                 */
                return true;
            }
        }
    }

    /**
     * checks if the passed policy is accepted by the user
     *
     * @param GdprPolicy $policy
     * @param array $userPolicies
     * @return boolean
     */
    private static function ckeckAcceptedPolicy(GdprPolicy $policy, array $userPolicies)
    {
        return (array_key_exists($policy->getPolicyContentId(), $userPolicies) &&
        intval($userPolicies[$policy->getPolicyContentId()]['acceptedVersion']) >= $policy->getVersion());
    }

    /**
     * Calls the datahandler findBy method
     *
     * @param string $className
     * @param array $whereArr
     * @param array $orderByArr
     * @param AbstractAMADataHandler $dbToUse
     * @return array
     */
    public function findBy($className, array $whereArr = null, array $orderByArr = null, AbstractAMADataHandler $dbToUse = null)
    {
        return $this->dh->findBy($className, $whereArr, $orderByArr, $dbToUse);
    }

    /**
     * Calls the datahandler findAll method
     *
     * @param string $className
     * @param array $orderBy
     * @param AbstractAMADataHandler $dbToUse
     * @return array
     */
    public function findAll($className, array $orderBy = null, AbstractAMADataHandler $dbToUse = null)
    {
        return $this->dh->findAll($className, $orderBy, $dbToUse);
    }

    /**
     * Calls the datahandler getObjectClasses method
     *
     * @return array|string[]
     */
    public function getObjectClasses()
    {
        return $this->dh->getObjectClasses();
    }

    /**
     * Calls the datahandler setObjectClasses method
     *
     * @param array $objectClasses
     * @return \Lynxlab\ADA\Module\GDPR\GdprAPI
     */
    public function setObjectClasses(array $objectClasses)
    {
        $this->dh->setObjectClasses($objectClasses);
        return $this;
    }

    /**
     * Builds a GdprUser from the passed ADALoggableUser
     *
     * @param \ADALoggableUser $user
     * @return \Lynxlab\ADA\Module\GDPR\GdprUser
     */
    public static function createGdprUserFromADALoggable(ADALoggableUser $user)
    {
        return new GdprUser(['id_utente' => $user->getId()]);
    }
}

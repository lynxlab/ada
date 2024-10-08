<?php

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

/**
 * Class for handling module permissions based on user profiles.
 *
 * @author giorgio
 */
class GdprActions
{
    /**
     * global actions, not performed on the single request
     *
     * @var integer
     */
    public const ACCESS_ALL_REQUESTS = 1;
    public const FORCE_CLOSE_REQUEST = 2;
    public const LIST_POLICIES = 7;
    public const EDIT_POLICY = 8;

    /**
     * request actions, performed on the request depending on its type
     *
     * @var integer
     */
    public const REQUEST_TYPE_ACCESS = 3;
    public const REQUEST_TYPE_EDIT = 4;
    public const REQUEST_TYPE_ONHOLD = 5;
    public const REQUEST_TYPE_DELETE = 6;
    public const REQUEST_TYPE_OPPOSITION = 9;

    protected static $CANDOARR = null;

    /**
     * gets the canDo array
     *
     * @return array
     */
    protected static function getCanDoArr()
    {
        return [
            self::ACCESS_ALL_REQUESTS => function () {
                $gdprUser = (new GdprAPI())->getGdprUserByID($_SESSION['sess_userObj']);
                if ($gdprUser instanceof GdprUser) {
                    return $gdprUser->hasTypes(GdprUserType::MANAGER);
                } else {
                    return false;
                }
            },
            self::FORCE_CLOSE_REQUEST => fn () =>
                // use the same logic as ACCESS_ALL_REQUESTS action
                call_user_func(self::getCanDoArr()[self::ACCESS_ALL_REQUESTS]),
            self::LIST_POLICIES => fn () =>
                // use the same logic as ACCESS_ALL_REQUESTS action
                call_user_func(self::getCanDoArr()[self::ACCESS_ALL_REQUESTS]),
            self::EDIT_POLICY => fn () =>
                // use the same logic as ACCESS_ALL_REQUESTS action
                call_user_func(self::getCanDoArr()[self::ACCESS_ALL_REQUESTS]),
            self::REQUEST_TYPE_ACCESS => function ($object = null, $userType = null) {
                // first check if the session user is a GdprUser as well
                $gdprUser = (new GdprAPI())->getGdprUserByID($_SESSION['sess_userObj']);
                if ($gdprUser instanceof GdprUser) {
                    // if it is, use module gdpr logic
                    return $gdprUser->hasTypes(GdprUserType::MANAGER);
                } else {
                    // only GdprRequests object are supported
                    if ($object instanceof GdprRequest) {
                        return $object->getGeneratedBy() == $_SESSION['sess_userObj']->getId();
                    }
                }
                // deny the action by default
                return false;
            },
            self::REQUEST_TYPE_EDIT => fn ($object = null, $userType = null) =>
                // use the same logic as REQUEST_TYPE_ACCESS action
                call_user_func_array(self::getCanDoArr()[self::REQUEST_TYPE_ACCESS], [$object, $userType]),
            self::REQUEST_TYPE_ONHOLD => fn ($object = null, $userType = null) =>
                // use the same logic as REQUEST_TYPE_ACCESS action
                call_user_func_array(self::getCanDoArr()[self::REQUEST_TYPE_ACCESS], [$object, $userType]),
            self::REQUEST_TYPE_DELETE => fn ($object = null, $userType = null) =>
                // use the same logic as REQUEST_TYPE_ACCESS action
                call_user_func_array(self::getCanDoArr()[self::REQUEST_TYPE_ACCESS], [$object, $userType]),
            self::REQUEST_TYPE_OPPOSITION => fn ($object = null, $userType = null) =>
                // use the same logic as REQUEST_TYPE_ACCESS action
                call_user_func_array(self::getCanDoArr()[self::REQUEST_TYPE_ACCESS], [$object, $userType]),
        ];
    }

    /**
     * Gets the constant value whose name is the passed string, if it exists, else returns null
     *
     * @param string $stringConstant
     * @return NULL|mixed
     */
    public static function getConstantFromString($stringConstant)
    {
        return defined(self::class . '::' . $stringConstant) ? constant(self::class . '::' . $stringConstant) : null;
    }

    /**
     * Checks if a user has the rights to to an action on the optional passed object.
     * If the action is an array the method will return true if the userType can do at least one
     * of the actions in the group
     *
     * @param int|array $actionID
     * @param unknown $object the object you are checking if the user has permission to do the action
     * @param int $userType, if null it will be set to the session user type
     * @return boolean
     */
    public static function canDo($actionID, $object = null, $userType = null)
    {
        if (is_null(self::$CANDOARR)) {
            self::$CANDOARR = self::getCanDoArr();
        }
        if (is_null($userType) && array_key_exists('sess_userObj', $_SESSION)) {
            $userType = $_SESSION['sess_userObj']->getType();
        }
        if (is_null($userType) || intval($userType) <= 0) {
            return false;
        }
        if (is_array($actionID)) {
            foreach ($actionID as $anAction) {
                if (self::canDo($anAction, $object, $userType)) {
                    return true;
                }
            }
            return false;
        } else {
            if (array_key_exists($actionID, self::$CANDOARR)) {
                if (is_callable(self::$CANDOARR[$actionID])) {
                    return call_user_func_array(self::$CANDOARR[$actionID], [$object, $userType]);
                } else {
                    return in_array(intval($userType), self::$CANDOARR[$actionID]);
                }
            }
            return false;
        }
    }

    /**
     * Gets the $allowedUsersAr and $neededObjAr that must be defined for module_init checks
     * typical usage is: list($allowedUsersAr, $neededObjAr) = array_values(GdprActions::getAllowedAndNeededAr());
     *
     * @param string $fileName php script to get arrays for. defaults to null, and $_SERVER['SCRIPT_FILENAME'] is used
     * @return array[]|string[][][] array with keys 'allowedUsers' and 'neededObjects' as arrays
     */
    public static function getAllowedAndNeededAr($fileName = null)
    {
        $retArr = [
            'allowedUsers' => [],
            'neededObjects' => [],
        ];
        if (is_null($fileName)) {
            $fileName = realpath($_SERVER['SCRIPT_FILENAME']);
        }
        $fileName = trim(str_replace(MODULES_GDPR_PATH, '', $fileName), '/');
        if (strlen($fileName) > 0) {
            // admin, coordinator, author and editor have access to everything by default
            $retArr['neededObjects'] = [
                AMA_TYPE_ADMIN => ['layout'],
                AMA_TYPE_SWITCHER => ['layout'],
                AMA_TYPE_TUTOR => ['layout'],
                AMA_TYPE_SUPERTUTOR => ['layout'],
                AMA_TYPE_AUTHOR => ['layout'],
                AMA_TYPE_STUDENT => ['layout'],
            ];
            switch ($fileName) {
                // separate index.php from default, prevents too many redirect error
                case 'index.php':
                    $retArr['neededObjects'] = [
                        AMA_TYPE_ADMIN => ['layout'],
                        AMA_TYPE_SWITCHER => ['layout'],
                        AMA_TYPE_TUTOR => ['layout'],
                        AMA_TYPE_SUPERTUTOR => ['layout'],
                        AMA_TYPE_AUTHOR => ['layout'],
                        AMA_TYPE_STUDENT => ['layout'],
                    ];
                    break;

                case 'ajax/lookupRequest.php':
                case 'lookuprequest.php':
                    $retArr['neededObjects'] = [
                        AMA_TYPE_VISITOR => ['layout'],
                    ];
                    break;

                case GdprPolicy::ACCEPTPOLICIESPAGE:
                case 'ajax/saveUserPolicies.php':
                case 'ajax/saveRequest.php':
                case 'ajax/getCaptcha.php':
                    $retArr['neededObjects'] += [
                        AMA_TYPE_VISITOR => ['layout'],
                    ];
                    break;
            }
        }
        // if no allowedUsers specified, use the neededObjects keys
        if (count($retArr['allowedUsers']) <= 0) {
            $retArr['allowedUsers'] = array_keys($retArr['neededObjects']);
        }
        return $retArr;
    }
}

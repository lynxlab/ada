<?php

/**
 * AUTHOR FUNCTIONS
 *
 * @package
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Helper;

/**
 * Service helper class
 */
class ServiceHelper extends ViewBaseHelper
{
    /**
     * Builds array keys for the author directory scripts
     *
     * @param array $neededObjAr
     *
     * @return array
     */
    public static function init(array $neededObjAr = [])
    {
        if (count(self::$helperData) === 0) {
            self::$helperData = parent::init($neededObjAr);
            self::$helperData = array_merge(
                self::$helperData,
                [
                    'user_level' => ADA_MAX_USER_LEVEL,
                    'user_score' => '',
                    'user_status' => '',
                    'user_uname' => self::$helperData['userObj']->getUserName(),
                    'user_surname' => self::$helperData['userObj']->getLastName(),
                    'user_mail' => self::$helperData['userObj']->getEmail(),
                    'user_messages' => self::getUserMessages(self::$helperData['userObj']),
                    'user_agenda' => self::getUserAgenda(self::$helperData['userObj']),
                ],
                self::buildGlobals()
            );
            self::extract();
        }
        return self::getHelperData();
    }
}

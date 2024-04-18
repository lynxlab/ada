<?php

/**
 * FORMMAIL MODULE.
 *
 * @package        formmail module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           formmail
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\FormMail;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;

class AMAFormmailDataHandler extends AMADataHandler
{
    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public static $PREFIX = 'module_formmail_';

    public function saveFormMailHistory($userID, $helpTypeID, $subject, $msgbody, $attachmentsStr, $selfSent, $sentOK)
    {
        $sql = 'INSERT INTO `' . self::$PREFIX . 'history` (`id_utente`,`' . self::$PREFIX . 'helptype_id`, ' .
        '`subject`,`msgbody`,`attachments`,`selfSent`,`sentOK`,`sentTimestamp`) VALUES (?,?,?,?,?,?,?,?);';

        $res = $GLOBALS['dh']->queryPrepared($sql, [$userID, $helpTypeID, $subject, $msgbody, $attachmentsStr, $selfSent, $sentOK, AMADataHandler::dateToTs('now')]);

        if (AMADB::isError($res)) {
            $err = new AMAError(AMA_ERR_ADD);
            return $err;
        }

        return true;
    }

    public function getHelpTypes($user_type)
    {
        $sql = 'SELECT * FROM `' . self::$PREFIX . 'helptype` WHERE `user_type` =? ORDER BY `description` ASC';
        return $GLOBALS['dh']->getAllPrepared($sql, $user_type, AMA_FETCH_ASSOC);
    }

    /**
     * Returns an instance of AMAFormmailDataHandler.
     *
     * @param  string $dsn - optional, a valid data source name
     * @return self an instance of AMAFormmailDataHandler
     */
    public static function instance($dsn = null)
    {
        return parent::instance($dsn);
    }
}

<?php

/**
 * @package     forked-paths module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\ForkedPaths;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsHistory;

class AMAForkedPathsDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_forkedpaths_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\ForkedPaths\\';

    private const EXCEPTIONCLASS = ForkedPathsException::class;

    /**
     * Save a forkedpathhistory object from the passed array
     *
     * @param array $saveData
     * @return \Lynxlab\ADA\Module\ForkedPaths\ForkedPathsHistory
     */
    public function saveForkedPathHistory($saveData)
    {

        $historyObj = new ForkedPathsHistory($saveData);
        $historyObj->setSaveTS($this->dateToTs('now'))->setSessionId(session_id());

        $fields = $historyObj->toArray();
        $result = $this->executeCriticalPrepared($this->sqlInsert($historyObj::TABLE, $fields), array_values($fields));

        if (AMADB::isError($result)) {
            throw new ForkedPathsException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }

        return $historyObj;
    }

    /**
     * calls and sets the parent instance method, and if !MULTIPROVIDER
     * checks if module_gdpr_policy_content table is in the provider db.
     *
     * If found, use the provider DB else use the common
     *
     * @param string $dsn
     */
    /*
    static function instance ($dsn = null) {
        if (!MULTIPROVIDER && is_null($dsn)) $dsn = \MultiPort::getDSN($GLOBALS['user_provider']);
        $theInstance = parent::instance($dsn);

        if (is_null(self::$policiesDB)) {
            self::$policiesDB = AMACommonDataHandler::getInstance();
            if (!MULTIPROVIDER && !is_null($dsn)) {
                // must check if passed $dsn has the module login tables
                // execute this dummy query, if result is not an error table is there
                $sql = 'SELECT NULL FROM `'.GdprPolicy::TABLE.'`';
                // must use AMADataHandler because we are not able to
                // query AMALoginDataHandelr in this method!
                $ok = AMADataHandler::instance($dsn)->getOnePrepared($sql);
                if (!AMADB::isError($ok)) self::$policiesDB = $theInstance;
            }
        }
        return $theInstance;
    }
    */
}

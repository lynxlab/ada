<?php

/**
 * @package     etherpad module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\EtherpadIntegration;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Module\EtherpadIntegration\HashKey;

class AMAEtherpadDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_etherpad_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\EtherpadIntegration\\';

    private const EXCEPTIONCLASS =  EtherpadException::class;

    /**
     * saves an etherpad session in the local db
     *
     * @param array $saveData
     * @return bool|EtherpadExeception
     */
    public function saveSession($saveData)
    {
        return $this->insertIntoTable($saveData, Session::class);
    }

    /**
     * saves an etherpad pad in the local db (id only, no content)
     *
     * @param array $saveData
     * @return bool|EtherpadExeception
     */
    public function savePad($saveData)
    {
        return $this->insertIntoTable($saveData, Pads::class);
    }

    /**
     * saves an etherpad group to ada instance mapping
     *
     * @param array $saveData
     * @return bool|EtherpadExeception
     */
    public function saveGroupMapping($saveData)
    {
        return $this->insertIntoTable($saveData, Groups::class);
    }

    /**
     * saves an etherpad author to ada user mapping
     *
     * @param array $saveData
     * @return bool|EtherpadExeception
     */
    public function saveAuthorMapping($saveData)
    {
        return $this->insertIntoTable($saveData, Authors::class);
    }

    /**
     * saves the key used to hash the data sent to etherpad
     *
     * @param array $saveData
     * @return bool|EtherpadExeception
     */
    public function saveHashKey($saveData)
    {
        $this->beginTransaction();
        if (array_key_exists('isActive', $saveData) && (bool)$saveData['isActive'] === true) {
            // ensure that inserted key is the only one with isActive true
            $this->queryPrepared('UPDATE `' . self::PREFIX . HashKey::TABLE . '`SET `isActive`=?', [0]);
        }

        $result = $this->executeCriticalPrepared(
            $this->sqlInsert(
                HashKey::TABLE,
                $saveData
            ),
            array_values($saveData)
        );

        if (!AMADB::isError($result)) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return new EtherpadException($result->getMessage());
        }
    }
}

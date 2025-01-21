<?php

/**
 * @package     maxtries module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\MaxTries;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\AMA\Traits\WithTransactions;

class AMAMaxTriesDataHandler extends AMADataHandler
{
    use WithInstance;
    use WithTransactions;

    /**
     * module's own data tables prefix.
     *
     * @var string
     */
    public const PREFIX = 'module_maxtries_';

    /**
     * module's managed tables.
     */
    private $managedTables = [
        self::PREFIX . 'history_nodi' => [
            'table' => 'history_nodi',
            'user' => 'id_utente_studente',
            'instance' => 'id_istanza_corso',
        ],
        self::PREFIX . 'history_esercizi' => [
            'table' => 'history_esercizi',
            'user' => 'ID_UTENTE_STUDENTE',
            'instance' => 'ID_ISTANZA_CORSO',
        ],
        self::PREFIX . 'log_classi' => [
            'table' => 'log_classi',
            'user' => 'id_user',
            'instance' => 'id_istanza_corso',
        ],
        self::PREFIX . 'log_videochat' => [
            'table' => 'log_videochat',
            'user' => 'id_user',
            'instance' => 'id_istanza_corso',
        ],
        self::PREFIX . 'history_test' => [
            'table' => 'module_test_history_test',
            'user' => 'id_utente',
            'instance' => 'id_istanza_corso',
        ],
        self::PREFIX . 'history_answer' => [
            'table' => 'module_test_history_answer',
            'user' => 'id_utente',
            'instance' => 'id_istanza_corso',
        ],
    ];

    public function getTriesCount($userId, $instanceId)
    {
        $count = $this->getOnePrepared(
            "SELECT `count` FROM `" . self::PREFIX . "count` WHERE `id_utente_studente` = :userId AND `id_istanza_corso` = :instanceId",
            [
                'userId' => $userId,
                'instanceId' => $instanceId,
            ]
        );
        if ($count === false) {
            if ($this->updateTriesCount($userId, $instanceId, null)) {
                $count = 0;
            }
        }
        return (int)$count;
    }

    public function updateTriesCount($userId, $instanceId, $count)
    {
        if ($count == null) {
            $sql = "INSERT INTO `" . self::PREFIX . "count` (`id_utente_studente`, `id_istanza_corso`, `count`, `lastupdate`) " .
            "VALUES (:userId, :instanceId, :count, :lastupdate)";
            $count = 0;
        } else {
            $sql = "UPDATE `" . self::PREFIX . "count` SET `count` = :count, `lastupdate` = :lastupdate " .
            "WHERE `id_utente_studente` = :userId AND `id_istanza_corso` = :instanceId";
        }
        $res = $this->executeCriticalPrepared(
            $sql,
            [
                'userId' => $userId,
                'instanceId' => $instanceId,
                'count' => $count,
                'lastupdate' => AMADataHandler::dateToTs("now"),
            ]
        );
        return !AMADB::isError($res);
    }

    public function backupUserLog($userId, $instanceId, $trycount = 0, $excludeArr = [])
    {
        $this->beginTransaction();
        $res = false;
        foreach ($this->getManagedTables() as $table => $sourceTable) {
            $res = $this->backupTable($table, $sourceTable, $userId, $instanceId, $trycount, $excludeArr);
            if (AMADB::isError($res)) {
                $this->rollBack();
                break;
            }
        }
        $retval = false;
        if ($res !== false && !AMADB::isError($res)) {
            $this->commit();
            $retval = true;
        }
        return $retval;
    }

    private function backupTable($table, $sourceTable, $userId, $instanceId, $trycount = 0, $excludeArr = [])
    {
        $where = " WHERE `" . $sourceTable['table'] . "`.`" . $sourceTable['user'] . "` = :userId AND " .
        "`" . $sourceTable['table'] . "`.`" . $sourceTable['instance'] . "` = :instanceId";
        if (!empty($excludeArr) && isset($excludeArr[$sourceTable['table']])) {
            foreach ($excludeArr[$sourceTable['table']] as $field => $ids) {
                $where .= " AND `" . $sourceTable['table'] . "`.`" . $field . "` NOT IN (" .
                implode(',', $ids) . ")";
            }
        }
        $from = " FROM `" . $sourceTable['table'] . "` " . $where;
        $count = $this->getOnePrepared(
            "SELECT COUNT(*) FROM `" . $sourceTable['table'] . "` " . $where,
            [
                'userId' => $userId,
                'instanceId' => $instanceId,
            ]
        );
        if (!AMADB::isError($count) && $count > 0) {
            $res = $this->executeCriticalPrepared(
                "INSERT INTO `" . $table . "` SELECT *, $trycount, NOW() " . $from,
                [
                    'userId' => $userId,
                    'instanceId' => $instanceId,
                ]
            );
            if (!AMADB::isError($res)) {
                $res = $this->executeCriticalPrepared(
                    "DELETE " . $from,
                    [
                        'userId' => $userId,
                        'instanceId' => $instanceId,
                    ]
                );
            }
        } else {
            $res = true;
        }
        return $res;
    }

    /**
     * Get module's managed tables array.
     *
     * @return array
     */
    public function getManagedTables()
    {
        return $this->managedTables;
    }
}

<?php

/**
 * @package     notifications module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Notifications;

use Exception;
use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Module\Notifications\EmailQueueItem;
use Lynxlab\ADA\Module\Notifications\Notification;
use ReflectionClass;
use ReflectionProperty;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMANotificationsDataHandler extends AMADataHandler
{
    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_notifications_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\Notifications\\';

    /**
     * insert or update a Notification
     *
     * @param array $saveData Notification object, as an array
     *
     * @return boolean|NotificationException
     */
    public function saveNotification($saveData)
    {
        $isUpate = array_key_exists('notificationId', $saveData) && !is_null($saveData['notificationId']);
        $saveData['lastEditTS'] = date('Y-m-d H:i:s');
        $this->beginTransaction();
        if ($isUpate) {
            $saveData['isActive'] = !$saveData['isActive'];
            $whereArr = ['notificationId' => $saveData['notificationId']];
            unset($saveData['notificationId']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    Notification::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['notificationId'] = $whereArr['notificationId'];
        } else {
            $saveData['isActive'] = true;
            $saveData['creationTS'] = date('Y-m-d H:i:s');
            $saveData['userId'] = $_SESSION['sess_userObj']->getId();
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    Notification::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        }

        if (!AMADB::isError($result)) {
            if (!$isUpate) {
                $saveData['notificationId'] = intval($this->getConnection()->lastInsertID());
            }
            $this->commit();
            return $saveData;
        } else {
            $this->rollBack();
            return new NotificationException($result->getMessage());
        }
    }

    /**
     * insert or update an email queue item
     *
     * @param array $saveData EmailQueueItem object, as an array
     *
     * @return boolean|NotificationException
     */
    public function saveEmailQueueItem($saveData)
    {
        $isUpate = array_key_exists('id', $saveData) && !is_null($saveData['id']);
        $this->beginTransaction();
        if ($isUpate) {
            $whereArr = ['id' => $saveData['id']];
            unset($saveData['id']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    EmailQueueItem::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['id'] = $whereArr['id'];
        } else {
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    EmailQueueItem::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        }

        if (!AMADB::isError($result)) {
            if (!$isUpate) {
                $saveData['id'] = intval($this->getConnection()->lastInsertID());
            }
            $this->commit();
            return $saveData;
        } else {
            $this->rollBack();
            return new NotificationException($result->getMessage());
        }
    }

    /**
     * insert some EmailQueueItems at once
     *
     * @param array $saveData array of EmailQueueItem objects, each one as an array
     *
     * @return boolean|NotificationException
     */
    public function multiSaveEmailQueueItems($insertData)
    {
        try {
            if (!$this->beginTransaction()) {
                throw new NotificationException(translateFN('Errore avvio transazione DB'));
            }
            $result = $this->queryPrepared(
                $this->insertMultiRow(
                    $insertData,
                    EmailQueueItem::TABLE
                ),
                array_values($insertData)
            );
            if (AMADB::isError($result)) {
                throw new NotificationException($result->getMessage());
            } else {
                $this->commit();
                return true;
            }
        } catch (Exception $e) {
            $this->rollBack();
            return $e;
        }
    }

    /**
     * loads an array of objects of the passed className with matching where values
     * and ordered using the passed values by performing a select query on the DB
     *
     * @param string $className to use a class from your namespace, this string must start with "\"
     * @param array $whereArr
     * @param array $orderByArr
     * @param AbstractAMADataHandler $dbToUse object used to run the queries. If null, use 'this'
     * @throws NotificationException
     * @return array
     */
    public function findBy($className, array $whereArr = null, array $orderByArr = null, AbstractAMADataHandler $dbToUse = null)
    {
        if (
            stripos($className, '\\') !== 0 &&
            stripos($className, self::MODELNAMESPACE) !== 0
        ) {
            $className = self::MODELNAMESPACE . $className;
        }
        $reflection = new ReflectionClass($className);
        $properties =  array_map(
            fn ($el) => $el->getName(),
            array_filter(
                $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC),
                fn ($refEl) => $className === $refEl->getDeclaringClass()->getName()
            )
        );

        // get object properties to be loaded as a kind of join
        $joined = $className::loadJoined();
        // and remove them from the query, they will be loaded afterwards
        $properties = array_diff($properties, array_keys($joined));
        // check for customField class const and explode matching propertiy array
        $properties = $className::explodeArrayProperties($properties);

        $sql = sprintf("SELECT %s FROM `%s`", implode(',', array_map(fn ($el) => "`$el`", $properties)), $className::TABLE)
            . $this->buildWhereClause($whereArr, $properties) . $this->buildOrderBy($orderByArr, $properties);

        if (is_null($dbToUse)) {
            $dbToUse = $this;
        }

        $result = $dbToUse->getAllPrepared($sql, (!is_null($whereArr) && count($whereArr) > 0) ? array_values($whereArr) : [], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            throw new NotificationException($result->getMessage(), (int) $result->getCode());
        } else {
            $retArr = array_map(fn ($el) => new $className($el, $dbToUse), $result);
            // load properties from $joined array
            foreach ($retArr as $retObj) {
                foreach ($joined as $joinKey => $joinData) {
                    if (array_key_exists('idproperty', $joinData)) {
                        // this is a 1:1 relation, load the linked object using object property
                        $retObj->{$retObj::ADDERPREFIX . ucfirst($joinKey)}(
                            $retObj->{$retObj::GETTERPREFIX . ucfirst($joinData['idproperty'])}(),
                            $dbToUse
                        );
                    } elseif (array_key_exists('reltable', $joinData)) {
                        if (!is_array($joinData['key'])) {
                            $joinData['key'] = [
                                'name' => $joinData['key'],
                                'getter' => (new Convert($retObj::GETTERPREFIX . ucfirst($joinData['key'])))->toCamel(),
                            ];
                        }
                        $joinSelFields = '';
                        if (array_key_exists('relproperties', $joinData) && is_array($joinData['relproperties']) && count($joinData['relproperties']) > 0) {
                            $joinSelFields = ',`' . implode('`,`', $joinData['relproperties']) . '`';
                        }
                        // this is a 1:n relation, load the linked objects querying the relation table
                        $sql = sprintf("SELECT `%s`%s FROM `%s` WHERE `%s`=?", $joinData['extkey'], $joinSelFields, $joinData['reltable'], $joinData['key']['name']);
                        $joinRes = $dbToUse->getAllPrepared($sql, [$retObj->{$joinData['key']['getter']}()], AMA_FETCH_ASSOC);
                        if (array_key_exists('callback', $joinData)) {
                            if (is_callable($joinData['callback'])) {
                                $joinRes = $joinData['callback']($joinRes);
                            } elseif (method_exists($retObj, $joinData['callback'])) {
                                $joinRes = $retObj->{$joinData['callback']}($joinRes);
                            }
                        }
                        $method = new Convert($retObj::SETTERPREFIX . ucfirst($joinKey));
                        $retObj->{$method->toCamel()}($joinRes);
                    }
                }
            }
            return $retArr;
        }
    }

    /**
     * loads an array holding all of the passed className objects, possibly ordered.
     * Actually it's an alias for findBy($className, null, $orderby)
     *
     * @param string $className
     * @param array $orderBy
     * @param AbstractAMADataHandler $dbToUse object used to run the queries. If null, use 'this'
     * @return array
     */
    public function findAll($className, array $orderBy = null, AbstractAMADataHandler $dbToUse = null)
    {
        return $this->findBy($className, null, $orderBy, $dbToUse);
    }

    /**
     * loads the first one of the array of objects of the passed className with matching where values
     * and ordered using the passed values by performing a select query on the DB
     *
     * @param string $className to use a class from your namespace, this string must start with "\"
     * @param array $whereArr
     * @param array $orderByArr
     * @param AbstractAMADataHandler $dbToUse object used to run the queries. If null, use 'this'
     * @throws NotificationException
     * @return object
     */
    public function findOneBy($className, array $whereArr = null, array $orderByArr = null, AbstractAMADataHandler $dbToUse = null)
    {
        $retval = $this->findBy($className, $whereArr, $orderByArr, $dbToUse);
        if (is_array($retval) && count($retval) > 0) {
            $retval = reset($retval);
        } else {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Returns an instance of AMANotificationsDataHandler.
     *
     * @param  string $dsn - optional, a valid data source name
     * @return self an instance of AMANotificationsDataHandler
     */
    public static function instance($dsn = null)
    {
        return parent::instance($dsn);
    }

    /**
     * Builds an sql update query as a string
     *
     * @param string $table
     * @param array $fields
     * @param string $whereField
     * @return string
     */
    private function sqlUpdate($table, array $fields, $whereField)
    {
        if (is_array($whereField)) {
            return sprintf(
                "UPDATE `%s` SET %s %s;",
                $table,
                implode(',', array_map(fn ($el) => "`$el`=?", $fields)),
                $this->buildWhereClause($whereField, array_keys($whereField))
            );
        } else {
            return sprintf(
                "UPDATE `%s` SET %s WHERE `%s`=?;",
                $table,
                implode(',', array_map(fn ($el) => "`$el`=?", $fields)),
                $whereField
            );
        }
    }

    /**
     * Builds an sql insert into query as a string
     *
     * @param string $table
     * @param array $fields
     * @return string
     */
    private function sqlInsert($table, array $fields)
    {
        return sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s);",
            $table,
            implode(',', array_map(fn ($el) => "`$el`", array_keys($fields))),
            implode(',', array_map(fn ($el) => "?", array_keys($fields)))
        );
    }

    private function insertMultiRow(&$valuesArray = [], $tableName = null, $subPrefix = '')
    {

        if (is_array($valuesArray) && count($valuesArray) > 0 && !is_null($tableName)) {
            // 0. init the query
            if (strlen($subPrefix) > 0) {
                $tableName = $subPrefix . '_' . $tableName;
            }

            $sql = 'INSERT INTO `' . $tableName . '` ';
            // 1. get the keys of the passed array
            $fields = array_keys(reset($valuesArray));
            // 2. build the placeholders string
            $flCount = count($fields);
            $lCount = ($flCount ? $flCount - 1 : 0);
            $questionMarks = sprintf("?%s", str_repeat(",?", $lCount));

            $arCount = count($valuesArray);
            $rCount = ($arCount ? $arCount - 1 : 0);
            $criteria = sprintf("(" . $questionMarks . ")%s", str_repeat(",(" . $questionMarks . ")", $rCount));
            // 3. build the fields list in sql
            $sql .= '(`' . implode('`,`', $fields) . '`)';
            // 4. append the placeholders
            $sql .= ' VALUES ' . $criteria;
            $toSave = [];
            foreach ($valuesArray as $v) {
                $toSave = array_merge($toSave, array_values($v));
            }
            $valuesArray = $toSave;
            return $sql;
        }
    }

    /**
     * Builds an sql delete query as a string
     *
     * @param string $table
     * @param array $whereArr
     * @return string
     */
    private function sqlDelete($table, &$whereArr)
    {
        return sprintf(
            "DELETE FROM `%s`",
            $table
        ) . $this->buildWhereClause($whereArr, array_keys($whereArr)) . ';';
    }

    /**
     * Builds an sql where clause
     *
     * @param array $whereArr
     * @param array $properties
     * @return string
     */
    private function buildWhereClause(&$whereArr, $properties)
    {
        $sql  = '';
        $newWhere = [];
        if (!is_null($whereArr) && count($whereArr) > 0) {
            $invalidProperties = array_diff(array_keys($whereArr), $properties);
            if (count($invalidProperties) > 0) {
                throw new NotificationException(translateFN('Proprietà WHERE non valide: ') . implode(', ', $invalidProperties));
            } else {
                $sql .= ' WHERE ';
                $sql .= implode(' AND ', array_map(function ($el) use (&$newWhere, $whereArr) {
                    if (is_null($whereArr[$el])) {
                        unset($whereArr[$el]);
                        return "`$el` IS NULL";
                    } else {
                        if (is_array($whereArr[$el])) {
                            $retStr = '';
                            if (array_key_exists('op', $whereArr[$el]) && array_key_exists('value', $whereArr[$el])) {
                                $whereArr[$el] = [$whereArr[$el]];
                            }
                            foreach ($whereArr[$el] as $opArr) {
                                if (strlen($retStr) > 0) {
                                    $retStr = $retStr . ' AND ';
                                }
                                $retStr .= "`$el` " . $opArr['op'] . ' ' . $opArr['value'];
                            }
                            unset($whereArr[$el]);
                            return '(' . $retStr . ')';
                        } elseif (is_numeric($whereArr[$el])) {
                            $op = '=';
                        } else {
                            $op = ' LIKE ';
                            $whereArr[$el] = '%' . $whereArr[$el] . '%';
                        }
                        $newWhere[$el] = $whereArr[$el];
                        return "`$el`$op?";
                    }
                }, array_keys($whereArr)));
            }
        }
        $whereArr = $newWhere;
        return $sql;
    }

    /**
     * Builds an sql orderby clause
     *
     * @param array $orderByArr
     * @param array $properties
     * @return string
     */
    private function buildOrderBy(&$orderByArr, $properties)
    {
        $sql = '';
        if (!is_null($orderByArr) && count($orderByArr) > 0) {
            $invalidProperties = array_diff(array_keys($orderByArr), $properties);
            if (count($invalidProperties) > 0) {
                throw new NotificationException(translateFN('Proprietà ORDER BY non valide: ') . implode(', ', $invalidProperties));
            } else {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', array_map(function ($el) use ($orderByArr) {
                    if (in_array($orderByArr[$el], ['ASC', 'DESC'])) {
                        return "`$el` " . $orderByArr[$el];
                    } else {
                        throw new NotificationException(sprintf(translateFN("ORDER BY non valido %s per %s"), $orderByArr[$el], $el));
                    }
                }, array_keys($orderByArr)));
            }
        }
        return $sql;
    }

    /**
     * PDO::beginTransaction wrapper
     *
     * @return bool
     */
    protected function beginTransaction()
    {
        return $this->getConnection()->connectionObject()->beginTransaction();
    }

    /**
     * PDO::rollBack wrapper
     *
     * @return bool
     */
    protected function rollBack()
    {
        return $this->getConnection()->connectionObject()->rollBack();
    }

    /**
     * PDO::commit wrapper
     *
     * @return bool
     */
    protected function commit()
    {
        return $this->getConnection()->connectionObject()->commit();
    }
}

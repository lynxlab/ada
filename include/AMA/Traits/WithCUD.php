<?php

/**
 * WithCUD trait
 *
 * use this trait when you need a datahandler with
 * methods: sqlInsert, sqlUpdate and sqlDelete.
 *
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\Traits;

use Lynxlab\ADA\Main\AMA\AMADB;
use ReflectionClass;

trait WithCUD
{
    use WithWhereClause;
    use WithExceptionClass;
    use WithTransactions;

    /**
     * Builds an sql update query as a string
     *
     * @param string $table
     * @param array $fields
     * @param array $whereArr
     * @return string
     */
    private function sqlUpdate($table, array $fields, &$whereArr)
    {
        return sprintf(
            "UPDATE `%s` SET %s %s;",
            $table,
            implode(',', array_map(fn ($el) => "`$el`=?", $fields)),
            static::buildWhereClause($whereArr, array_keys($whereArr))
        );
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
     * insert passed data into the classname own table
     *
     * @param array $saveData
     * @param string $className
     * @return bool|EtherpadException
     */
    private function insertIntoTable($saveData, $className)
    {
        $this->beginTransaction();

        $reflection = new ReflectionClass(static::class);
        $modelnamespace = $reflection->getConstant('MODELNAMESPACE');

        if (false === stripos($className, (string) $modelnamespace)) {
            $className = $modelnamespace . $className;
        }

        if (property_exists($className, 'creationDate') && !array_key_exists('creationDate', $saveData)) {
            $saveData['creationDate'] = date('Y-m-d H:i:s');
        }

        $result = $this->executeCriticalPrepared(
            $this->sqlInsert(
                constant($className . '::TABLE'),
                $saveData
            ),
            array_values($saveData)
        );

        if (!AMADB::isError($result)) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return static::buildException($result->getMessage());
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
        ) . static::buildWhereClause($whereArr, array_keys($whereArr)) . ';';
    }
}

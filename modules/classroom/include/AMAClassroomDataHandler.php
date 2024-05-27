<?php

/**
 * CLASSROOM MODULE.
 *
 * @package         classroom module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classroom;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;

class AMAClassroomDataHandler extends AMADataHandler
{
    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public static $PREFIX = 'module_classroom_';

    /**
     * gets rows in the venues table
     *
     * @param number $id if null gets all rows
     *
     * @return AMAError|array
     *
     * @access public
     */
    public function classroomGetVenue($id)
    {
        return $this->getRecord($id, 'venues', 'id_venue');
    }

    /**
     * gets all the venues, it's an alias for
     * classroomGetVenue(null)
     *
     * @return AMAError|array
     *
     * @access public
     */
    public function classroomGetAllVenues()
    {
        return $this->classroomGetVenue(null);
    }

    /**
     * save venue data either in insert or update
     *
     * @param array $data data to be saved
     *
     * @return AMAError|number inserted or updated id
     *
     * @access public
     */
    public function classroomSaveVenue($data)
    {

        $fields = [
            'name', 'addressline1', 'addressline2', 'contact_name',
            'contact_phone', 'contact_email', 'map_url',
        ];
        $primaryKey = 'id_venue';

        return $this->saveRecord('venues', $fields, $primaryKey, $data);
    }

    /**
     * deletes the venue having the passed id
     *
     * @param number $id_venue
     */
    public function classroomDeleteVenue($id_venue)
    {
        $sql = 'DELETE FROM `' . self::$PREFIX . 'venues` WHERE `id_venue`=?';
        // TODO: delete classroom
        return $this->executeCriticalPrepared($sql, $id_venue);
    }

    /**
     * gets rows in the classroom table
     *
     * @param number $id if null gets all rows
     * @param number $id_venue id of venue to get classrooms for
     *
     * @return AMAError|array
     *
     * @access public
     */
    public function classroomGetClassroom($id, $id_venue = null)
    {
        $tablesData = [
            self::$PREFIX . 'classrooms' =>
            [
                'fields' => [
                    'id_classroom', 'id_venue', 'name', 'seats', 'computers',
                    'internet', 'wifi', 'projector', 'mobility_impaired',
                    'hourly_rate',
                ],
                'join_field' => 'id_venue',
            ],
            self::$PREFIX . 'venues' =>
            [
                'fields' => ['name'],
                'aliasfields' => ['venue_name'],
                'join_field' => 'id_venue',
            ],
        ];

        if (!is_null($id)) {
            $whereClause  = 'id_classroom=' . $id;
        } else {
            $whereClause = null;
        }

        if (!is_null($id_venue)) {
            if (!is_null($whereClause)) {
                $whereClause .=  ' AND ';
            } else {
                $whereClause = '';
            }

            $whereClause .= self::$PREFIX . 'venues.id_venue=' . $id_venue;
        }

        $res = $this->getJoined($tablesData, $whereClause);

        if (AMADB::isError($res)) {
            return $res;
        } elseif (count($res) === 1 && !is_null($whereClause)) {
            return reset($res);
        } else {
            return $res;
        }
    }

    /**
     * gets all the classrooms, it's an alias for
     * classroomGetClassroom(null)
     *
     * @return AMAError|array
     *
     * @access public
     */
    public function classroomGetAllClassrooms()
    {
        return $this->classroomGetClassroom(null);
    }

    /**
     * save classroom data either in insert or update
     *
     * @param array $data data to be saved
     *
     * @return AMAError|number inserted or updated id
     *
     * @access public
     */
    public function classroomSaveClassroom($data)
    {
        $fields = [
            'id_venue', 'name', 'seats', 'computers', 'internet',
            'wifi', 'projector', 'mobility_impaired', 'hourly_rate',
        ];
        $primaryKey = 'id_classroom';

        if (!isset($data['id_venue']) || intval($data['id_venue']) <= 0) {
            // venue id was not passed, must save a new venue and use that one
            $tmpID = $this->classroomSaveVenue(['name' => $data['venue_name']]);
            if (AMADB::isError($tmpID)) {
                return $tmpID;
            } else {
                $data['id_venue'] = intval($tmpID);
            }
        }

        if (isset($data[$primaryKey]) && intval($data[$primaryKey]) > 0) {
            // set all facilities to 0 so only the passed ones shall be set
            $sql = 'UPDATE `' . self::$PREFIX . 'classrooms` SET internet=0, wifi=0,' .
                'projector=0, mobility_impaired=0 WHERE ' . $primaryKey . '=?';
            $this->executeCriticalPrepared($sql, $data[$primaryKey]);
        }

        return $this->saveRecord('classrooms', $fields, $primaryKey, $data);
    }

    /**
     * deletes the classroom having the passed id
     *
     * @param number $id_classroom
     */
    public function classroomDeleteClassroom($id_classroom)
    {
        $sql = 'DELETE FROM `' . self::$PREFIX . 'classrooms` WHERE `id_classroom`=?';
        // TODO: delete calendar (?)
        return $this->executeCriticalPrepared($sql, $id_classroom);
    }

    /**
     * Performs the serach for the autocomplete form fields
     *
     * @param string $tableName  the table to be searched
     * @param string $fieldName  the field to be searched
     * @param string $term       the search term
     * @param string $primaryKey the table primaryKey to get the ids if needed, defaults to null
     *
     * @return NULL|array
     *
     * @access public
     */
    public function doSearchForAutocomplete($tableName, $fieldName, $term, $primaryKey = null)
    {
        $retArray = null;

        $sql = 'SELECT `' . $fieldName . '` ';
        if (!is_null($primaryKey) && strlen($primaryKey) > 0) {
            $sql .= ', `' . $primaryKey . '` ';
        }

        $sql .= 'FROM `' . self::$PREFIX . $tableName . '` WHERE `' . $fieldName . "` LIKE ?";

        $result = $this->getAllPrepared($sql, ['%' . $term . '%'], AMA_FETCH_ASSOC);

        if (!AMADB::isError($result)) {
            $count = -1;
            foreach ($result as $res) {
                $retArray[++$count]['label'] = $res[$fieldName];
                if (!is_null($primaryKey) && strlen($primaryKey) > 0) {
                    $retArray[$count]['value'] = $res[$primaryKey];
                }
            }
        }
        return $retArray;
    }

    /**
     * gets the join result of two (or more) tables
     *
     * @param array $tablesData tables parameters to use, e.g.
     *      array (
                'classrooms'=>  // table name is the array key
                    array(
                        'fields'=>array('id_classroom','name','seats','computers'), // fields to select
                        'join_field'=>'id_venue' // field to join on
                    ),
                'venues'=> // table name is the array key
                    array(
                        'fields'=>array('name'), // fields to select
                        'aliasfields'=>array('venuename'), // name shall be selected as venuename
                        'join_field'=>'id_venue' // field to join on
                    )
        )
     * @param string $whereClause
     * @param string $orderBY
     *
     * @return mixed
     *
     * @access public
     */
    public function getJoined($tablesData, $whereClause = null, $orderBY = null)
    {
        $sql = 'SELECT ';
        $from = ' FROM ';
        $joinON = ' ON ';

        // just some logic to build the join sql string
        reset($tablesData);
        while ($tableFields = current($tablesData)) {
            $tableName = key($tablesData);
            $from .= '`' . $tableName . '`';
            reset($tableFields);
            foreach ($tableFields as $type => $data) {
                if ($type === 'fields') {
                    while ($field = current($tableFields[$type])) {
                        $index = key($tableFields[$type]);
                        $sql .= '`' . $tableName . '`.`' . $field . '`';
                        if (isset($tablesData[$tableName]['aliasfields'][$index])) {
                            $sql .= ' AS `' . $tablesData[$tableName]['aliasfields'][$index] . '`';
                        }
                        if (next($tableFields[$type]) !== false) {
                            $sql .= ', ';
                        }
                    }
                } elseif ($type === 'join_field') {
                    $joinON .= '`' . $tableName . '`.`' . $tableFields[$type] . '`';
                }
            }
            if (($nextTable = next($tablesData)) !== false) {
                if (count($nextTable['fields']) > 0) {
                    $sql  .= ', ';
                }
                $from .= ' JOIN ';
                $joinON .= ' = ';
            }
        }
        $sql .= $from . $joinON;
        if (!is_null($whereClause)) {
            $sql .= ' WHERE 1 AND ' . $whereClause;
        }
        if (!is_null($orderBY)) {
            $sql .= ' ORDER BY ' . $orderBY;
        }

        $res = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);

        // if an error is detected, an error is generated and reported
        if (AMADB::isError($res)) {
            return $this->generateError(AMA_ERR_GET, __FUNCTION__, $res);
        } elseif (count($res) <= 0 || $res === false) {
            return $this->generateError(AMA_ERR_NOT_FOUND, __FUNCTION__, $res);
        } else {
            return $res;
        }
    }

    /**
     * gets records from the DB
     *
     * @param number $id if null gets all rows
     * @param string $tableName name of the table
     * @param string $primaryKey name of the table's own primary key
     *
     * @return AMAError|array
     *
     * @access private
     */
    private function getRecord($id, $tableName, $primaryKey)
    {
        $sql = 'SELECT * FROM `' . self::$PREFIX . $tableName . '`';
        if (!is_null($id)) {
            $sql .= ' WHERE `' . $primaryKey . '`=?';
        }
        $sql .= ' ORDER BY `' . $primaryKey . '` ASC';

        if (!is_null($id)) {
            $res = $this->getRowPrepared($sql, $id, AMA_FETCH_ASSOC);
        } else {
            $res = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
        }

        // if an error is detected, an error is generated and reported
        if (AMADB::isError($res)) {
            return $this->generateError(AMA_ERR_GET, __FUNCTION__, $res);
        } elseif (count($res) <= 0 || $res === false) {
            return $this->generateError(AMA_ERR_NOT_FOUND, __FUNCTION__, $res);
        } else {
            return $res;
        }
    }

    /**
     * saves a record to the DB
     *
     * @param string $what name of the table where to insert/update without PREFIX
     * @param array $fields fields to insert/update
     * @param string $primaryKey name of the table's own primary key
     * @param array  $data assoc array of data to be saved
     *
     * @return AMAError|number inserted or updated id
     *
     * @access private
     */
    private function saveRecord($what, $fields, $primaryKey, $data)
    {
        $isInsert = false;

        // unset invalid $data array keys
        foreach ($data as $key => $val) {
            if (!in_array($key, $fields) && $key != $primaryKey) {
                unset($data[$key]);
            }
        }

        // unset data that are not a field
        foreach ($fields as $key => $val) {
            if (!isset($data[$val]) || strlen($data[$val]) <= 0) {
                unset($data[$val]);
            }
        }

        if (!isset($data[$primaryKey]) || $data[$primaryKey] == 0 || strlen($data[$primaryKey]) <= 0) {
            // it's an insert
            if (isset($data[$primaryKey])) {
                unset($data[$primaryKey]);
            }

            $sql = 'INSERT INTO `' . self::$PREFIX . $what . '` (';
            $sql .= implode(',', array_keys($data));
            $sql .= ') VALUES (' . $this->buildQuestionMarksString(count(array_keys($data)));
            $sql .= ')';

            $params = array_values($data);
            $errorCode = AMA_ERR_ADD;
            $isInsert = true;
        } else {
            $primaryKeyVal = $data[$primaryKey];
            unset($data[$primaryKey]);

            // it's an update
            foreach (array_keys($data) as $field) {
                $setValues[] = '`' . $field . '` = ?';
            }
            if (isset($setValues) && is_array($setValues) && count($setValues) > 0) {
                $sql = 'UPDATE `' . self::$PREFIX . $what . '` SET ';
                $sql .= implode(',', $setValues);
                $sql .= ' WHERE `' . $primaryKey . '`= ?';

                $params = array_merge(array_values($data), [$primaryKeyVal]);
            }
            $errorCode = AMA_ERR_UPDATE;
        }

        $res = $this->queryPrepared($sql, $params);

        // if an error is detected, an error is generated and reported
        if (AMADB::isError($res)) {
            return $this->generateError($errorCode, __FUNCTION__, $res);
        } else {
            return ($isInsert) ? $this->getConnection()->lastInsertID() : $primaryKeyVal;
        }
    }

    /**
     * build the question mark string for an insert into row:
     * if number==1 returns '?'
     * else if number==n returns '?,?,....?' n times
     *
     * @param int $count how many fields are needed
     *
     * @return string the generated string
     *
     * @access private
     */
    private function buildQuestionMarksString($count)
    {
        return sprintf("?%s", str_repeat(",?", ($count ? $count - 1 : 0)));
    }

    private function generateError($errorCode, $functionName, $res)
    {
        $errStr = $this->errorMessage(new AMAError($errorCode)) . " in " . $functionName;
        if (AMADB::isError($res)) {
            $errStr .= ":" . AMA_SEP . $res->getMessage();
        }
        return new AMAError($errorCode, $errStr);
    }
}

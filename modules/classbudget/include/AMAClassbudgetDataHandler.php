<?php

/**
 * CLASSBUDGET MODULE.
 *
 * @package        classbudget module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2015, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classbudget
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use DateTimeImmutable;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;

class AMAClassbudgetDataHandler extends AMADataHandler
{
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public static $PREFIX = 'module_classbudget_';

    /**
     * Saves a budget row for the course instance
     *
     * @param array $data array of data to be saved, according to db table fields
     *
     * @return AMA_Error|number inserted or updated id
     *
     * @access public
     */
    public function saveBudgetCourseInstance($data)
    {
        $fields = array_keys($data);
        $primaryKey = 'budget_instance_id';
        return $this->saveRecord('budget_instance', $fields, $primaryKey, $data);
    }

    /**
     * Gets a budget row for a course instance
     *
     * @param number $course_instance_id the instance id to load row for
     *
     * @return AMA_Error|number inserted or updated id
     *
     * @access public
     */
    public function getBudgetCourseInstanceByInstanceID($id_course_instance)
    {
        return $this->getRecord($id_course_instance, 'budget_instance', 'id_istanza_corso');
    }

    /**
     * Deletes a budget row for a course instance
     *
     * @param number $course_instance_id the instance id to delete row for
     *
     * @return AMA_Error|number of affected rows
     *
     * @access public
     */
    public function deleteBudgetCourseInstanceByInstanceID($id_course_instance)
    {
        $sql = 'DELETE FROM `' . self::$PREFIX . 'budget_instance` WHERE `id_istanza_corso`=?';
        return $this->executeCriticalPrepared($sql, $id_course_instance);
    }

    /**
     * Gets needed data for the Tutor Costs HTML-table
     *
     * @param number $id_course_instance
     *
     * @return mixed
     *
     * @access public
     */
    public function getTutorCostForInstance($id_course_instance)
    {
        $sql = 'SELECT SUM((CAL.end-CAL.start)) as `totaltime`, CAL.`id_utente_tutor` AS `id_tutor`, ' .
            'USER.`nome` as `name`, USER.`cognome` AS `lastname`, TUTORS.`tariffa` AS `default_rate`, ' .
            'TUTORCOST.`hourly_rate` AS `cost_rate`, TUTORCOST.`cost_tutor_id` ' .
            'FROM `module_classagenda_calendars` AS CAL ' .
            'JOIN `tutor` AS TUTORS ON CAL.`id_utente_tutor` = TUTORS.`id_utente_tutor` ' .
            'LEFT JOIN `module_classbudget_cost_tutor` AS TUTORCOST ON CAL.`id_utente_tutor` = TUTORCOST.`id_tutor` ' .
            'JOIN `utente` AS USER ON USER.`id_utente`= TUTORS.`id_utente_tutor` ' .
            'WHERE CAL.`id_istanza_corso` = ? ' .
            'GROUP BY (CAL.`id_utente_tutor`)';

        $res = $this->getAllPrepared($sql, $id_course_instance, AMA_FETCH_ASSOC);

        /**
         * if the number of returned rows is less than all rows in
         * the module_classbudget_cost_tutor table for the passed
         * instance, then delete the non returned rows, they're not needed
         * anymore and must be wiped off from the database.
         */
        if (!AMADB::isError($res) && is_array($res) && count($res) > 0) {
            $this->cleanCostTable($id_course_instance, 'tutor', count($res), $res);
        }
        return $res;
    }

    /**
     * Gets the costs for a list of events
     *
     * @param array $ids event ids
     * @return array
     */
    public function getCostsForEvents(array $ids = [])
    {
        $dateFormat = str_replace('%', '', ADA_DATE_FORMAT) . ' - H:i';
        $tutors = array_map(
            fn ($el) => $el + [
                'duration' => $el['end'] - $el['start'],
                'startformatted' => (new DateTimeImmutable())->setTimestamp($el['start'])->format($dateFormat),
                'endformatted' => (new DateTimeImmutable())->setTimestamp($el['end'])->format($dateFormat),
            ],
            $this->getTutorsCostsForEvents($ids)
        );
        $classrooms = array_map(
            fn ($el) => $el + [
                'duration' => $el['end'] - $el['start'],
                'startformatted' => (new DateTimeImmutable())->setTimestamp($el['start'])->format($dateFormat),
                'endformatted' => (new DateTimeImmutable())->setTimestamp($el['end'])->format($dateFormat),
            ],
            $this->getClassroomsCostsForEvents($ids)
        );
        $eventids = array_unique(
            array_merge(
                array_column($tutors, 'id_event'),
                array_column($classrooms, 'id_event')
            )
        );
        $instances = [];
        foreach (array_merge($tutors, $classrooms) as $item) {
            if (!isset($instances[$item['id_istanza_corso']])) {
                $instances[$item['id_istanza_corso']] = [
                    'id' => $item['id_istanza_corso'],
                    'title' => $item['instancetitle'],
                    'course' => [
                        'id' => $item['id_corso'],
                        'title' => $item['coursetitle'],
                        'code' => $item['coursename'],
                    ],
                ];
            }
        }
        $retval = [
            'instances' => [],
            'events' => [],
        ];
        $GLOBALS['dh'] = AMAClassbudgetDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        foreach (array_keys($instances) as $instanceid) {
            $costItem = new CostitemBudgetManagement($instanceid);
            $costItem->run();
            $retval['instances'][$instanceid] = [
                $instances[$instanceid] + [
                    'costs' => array_values(array_map(
                        fn ($el) => $el + [
                            'total' => $el['totalqty'] * $el['unitprice'],
                        ],
                        $costItem->dataCostsArr,
                    )),
                    'events' => array_unique(
                        array_merge(
                            array_column(
                                array_filter($tutors, fn ($x) => $x['id_istanza_corso'] == $instanceid),
                                'id_event'
                            ),
                            array_column(
                                array_filter($classrooms, fn ($x) => $x['id_istanza_corso'] == $instanceid),
                                'id_event'
                            )
                        )
                    ),
                ],
            ];
        }

        foreach (
            [
                'tutors' => $tutors,
                'classrooms' => $classrooms,
            ] as $element => $items
        ) {
            foreach ($items as $key => $item) {
                unset(${$element}[$key]['id_corso']);
                unset(${$element}[$key]['coursetitle']);
                unset(${$element}[$key]['coursename']);
                unset(${$element}[$key]['instancetitle']);
            }
        }

        foreach ($eventids as $eventid) {
            $retval['events'][$eventid] = [
                'tutor' => array_values(array_filter($tutors, fn ($x) => $x['id_event'] == $eventid)),
                'classroom' => array_values(array_filter($classrooms, fn ($x) => $x['id_event'] == $eventid)),
            ];
        }
        return $retval;
    }

    /**
     * Gets the tutors costs for a list of events
     *
     * @param array $ids event ids
     * @return array
     */
    public function getTutorsCostsForEvents(array $ids = [])
    {
        if (ModuleLoaderHelper::isLoaded('CLASSAGENDA') && count($ids) > 0) {
            $sql = 'SELECT ' . AMAClassagendaDataHandler::$PREFIX . 'calendars_id as id_event, ' .
                'C.`id_corso`, IC.`id_istanza_corso`, C.`titolo` AS coursetitle, C.`nome` AS coursename, ' .
                'IC.`title` AS instancetitle, CAL.`id_utente_tutor` AS `id_tutor`, USER.`nome` as `name`, ' .
                'USER.`cognome` AS `lastname`, TUTORS.`tariffa` AS `default_rate`, ' .
                'CAL.`start`, CAL.`end`, ' .
                'TUTORCOST.`hourly_rate` AS `cost_rate` FROM `module_classagenda_calendars` AS CAL ' .
                'LEFT JOIN `istanza_corso` AS IC ON IC.`id_istanza_corso` = CAL.id_istanza_corso ' .
                'LEFT JOIN `modello_corso` AS C ON C.`id_corso` = IC.`id_corso` ' .
                'JOIN `tutor` AS TUTORS ON CAL.`id_utente_tutor` = TUTORS.`id_utente_tutor` ' .
                'LEFT JOIN `module_classbudget_cost_tutor` AS TUTORCOST ON CAL.`id_utente_tutor` = TUTORCOST.`id_tutor` ' .
                'JOIN `utente` AS USER ON USER.`id_utente`= TUTORS.`id_utente_tutor` ' .
                'WHERE CAL.`module_classagenda_calendars_id` IN (' . implode(',', array_fill(0, count($ids), '?'))  . ');';
            $tutorCosts = $this->getAllPrepared($sql, $ids, AMA_FETCH_ASSOC);
            if (!AMADB::isError($tutorCosts)) {
                return $tutorCosts;
            }
        }
        return [];
    }

    /**
     * Gets the classrooms costs for a list of events
     *
     * @param array $ids event ids
     * @return array
     */
    public function getClassroomsCostsForEvents(array $ids = [])
    {
        if (ModuleLoaderHelper::isLoaded('CLASSROOM') && count($ids) > 0) {
            $sql = 'SELECT ' . AMAClassagendaDataHandler::$PREFIX . 'calendars_id as id_event, ' .
                'C.`id_corso`, IC.`id_istanza_corso`, C.`titolo` AS coursetitle, C.`nome` AS coursename, ' .
                'IC.`title` AS instancetitle, CAL.`id_classroom`, ' .
                'VENUES.`name` as `venuename`, ROOMS.`name` AS `roomname`, ROOMS.`hourly_rate` AS `default_rate`, ' .
                'CAL.`start`, CAL.`end`, ' .
                'ROOMCOST.`hourly_rate` AS `cost_rate`, ROOMCOST.`cost_classroom_id` ' .
                'FROM `module_classagenda_calendars` AS CAL ' .
                'LEFT JOIN `istanza_corso` AS IC ON IC.`id_istanza_corso` = CAL.id_istanza_corso ' .
                'LEFT JOIN `modello_corso` AS C ON C.`id_corso` = IC.`id_corso` ' .
                'JOIN `module_classroom_classrooms` AS ROOMS ON CAL.`id_classroom` = ROOMS.`id_classroom` ' .
                'LEFT JOIN `module_classbudget_cost_classroom` AS ROOMCOST ON CAL.`id_classroom` = ROOMCOST.`id_classroom` ' .
                'JOIN `module_classroom_venues` AS VENUES ON VENUES.`id_venue`= ROOMS.`id_venue` ' .
                'WHERE CAL.`module_classagenda_calendars_id` IN (' . implode(',', array_fill(0, count($ids), '?'))  . ');';
            $classCosts = $this->getAllPrepared($sql, $ids, AMA_FETCH_ASSOC);
            if (!AMADB::isError($classCosts)) {
                return $classCosts;
            }
        }
        return [];
    }

    /**
     * Gets needed data for the Classroom Costs HTML-table
     *
     * @param number $id_course_instance
     *
     * @return mixed
     *
     * @access public
     */
    public function getClassroomCostForInstance($id_course_instance)
    {
        $sql = 'SELECT SUM((CAL.end-CAL.start)) as `totaltime`, CAL.`id_classroom`, ' .
            'VENUES.`name` as `venuename`, ROOMS.`name` AS `roomname`, ROOMS.`hourly_rate` AS `default_rate`, ' .
            'ROOMCOST.`hourly_rate` AS `cost_rate`, ROOMCOST.`cost_classroom_id` ' .
            'FROM `module_classagenda_calendars` AS CAL ' .
            'JOIN `module_classroom_classrooms` AS ROOMS ON CAL.`id_classroom` = ROOMS.`id_classroom` ' .
            'LEFT JOIN `module_classbudget_cost_classroom` AS ROOMCOST ON CAL.`id_classroom` = ROOMCOST.`id_classroom` ' .
            'JOIN `module_classroom_venues` AS VENUES ON VENUES.`id_venue`= ROOMS.`id_venue` ' .
            'WHERE CAL.`id_istanza_corso` = ? ' .
            'GROUP BY (CAL.`id_classroom`)';

        $res = $this->getAllPrepared($sql, $id_course_instance, AMA_FETCH_ASSOC);

        /**
         * if the number of returned rows is less than all rows in
         * the module_classbudget_cost_classroom table for the passed
         * instance, then delete the non returned rows, they're not needed
         * anymore and must be wiped off from the database.
         */
        if (!AMADB::isError($res) && is_array($res) && count($res) > 0) {
            $this->cleanCostTable($id_course_instance, 'classroom', count($res), $res);
        }
        return $res;
    }

    /**
     * Gets needed data for the Item Costs HTML-table
     *
     * @param number $id_course_instance
     *
     * @return mixed
     *
     * @access public
     */
    public function getItemCostForInstance($id_course_instance)
    {
        $sql = 'SELECT * FROM `' . self::$PREFIX . 'cost_item` WHERE `id_istanza_corso`=?';
        return $this->getAllPrepared($sql, $id_course_instance, AMA_FETCH_ASSOC);
    }

    /**
     * saves the costs data for tables named self::PREFIX_cost_*
     *
     * @param array $data
     * @param string $type type of record to be saved. Can be 'classroom', 'tutor', 'item'
     *
     * @return AMA_Error|array of saved ids
     *
     * @access public
     */
    public function saveCosts($data, $type)
    {
        if (is_array($data) && count($data) > 0) {
            $savedIDs = [];
            foreach ($data as $index => $element) {
                /**
                 * prepare the array keys to be saved
                 */
                if (isset($element['id_type'])) {
                    $element['id_' . $type] = $element['id_type'];
                    unset($element['id_type']);
                }

                if (isset($element['cost_type_id'])) {
                    $element['cost_' . $type . '_id'] = $element['cost_type_id'];
                    unset($element['cost_type_id']);
                }
                /**
                 * actually save
                 */
                $res = $this->saveRecord('cost_' . $type, array_keys($element), 'cost_' . $type . '_id', $element);
                if (AMADB::isError($res)) {
                    break;
                } else {
                    $savedIDs[] = $res;
                }
            }

            if (AMADB::isError($res)) {
                // delete inserted ids and return an error
                $sql = 'DELETE FROM `' . self::$PREFIX . 'cost_' . $type . '` WHERE `cost_' . $type . '_id` IN (' .
                    implode(',', $savedIDs) . ')';
                $this->executeCritical($sql);
                return $this->generateError(AMA_ERROR, __FUNCTION__, $res);
            } else {
                return $savedIDs;
            }
        }
        return null;
    }

    /**
     * gets a cost item record
     *
     * @param number $course_instance_id the instance id to load row for
     *
     * @return AMA_Error|number inserted or updated id
     *
     * @access public
     */
    public function getCostItem($cost_item_id)
    {
        return $this->getRecord($cost_item_id, 'cost_item', 'cost_item_id');
    }

    /**
     * deletes a cost item record
     *
     * @param number $course_instance_id the instance id to delete row for
     *
     * @return AMA_Error|number of affected rows
     *
     * @access public
     */
    public function deleteCostItem($cost_item_id)
    {
        return $this->executeCriticalPrepared('DELETE FROM `' . self::$PREFIX . 'cost_item` WHERE `cost_item_id`=?', $cost_item_id);
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
     * cleans up cost table
     *
     * @param number $id_course_instance
     * @param string $type which table to clean. Can be 'classroom', 'tutor'
     * @param number $recordcount number of records returned by the joined query
     * @param array $res rows passed by the caller
     *
     * @access private
     */
    private function cleanCostTable($id_course_instance, $type, $recordcount, $res = [])
    {
        $sql = 'SELECT COUNT(`cost_' . $type . '_id`) ' .
            'FROM `' . self::$PREFIX . 'cost_' . $type . '` WHERE `id_istanza_corso`=?';
        $numRes = $this->getOnePrepared($sql, $id_course_instance);
        if (AMADB::isError($numRes)) {
            $numtablerows = 0;
        } else {
            $numtablerows = (int) $numRes;
        }

        if ($numtablerows > $recordcount) {
            $toDelArray = [];
            foreach ($res as $aRow) {
                $toDelArray[] = (int) $aRow['cost_' . $type . '_id'];
            }
            if (count($toDelArray) > 0) {
                $sql = 'DELETE FROM `' . self::$PREFIX . 'cost_' . $type . '` ' .
                    ' WHERE `cost_' . $type . '_id` NOT IN (' . implode(',', $toDelArray) . ')';
                $this->executeCritical($sql);
            }
        }
    }
    /**
     * gets records from the DB
     *
     * @param number $id if null gets all rows
     * @param string $tableName name of the table
     * @param string $primaryKey name of the table's own primary key
     *
     * @return AMA_Error|array
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
        } elseif (empty($res) || $res === false) {
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
     * @return AMA_Error|number inserted or updated id
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

            foreach (array_keys($data) as $field) {
                $insertValues[] = '`' . $field . '`';
            }

            $sql = 'INSERT INTO `' . self::$PREFIX . $what . '` (';
            $sql .= implode(',', $insertValues);
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

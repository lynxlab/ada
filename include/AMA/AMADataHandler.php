<?php

/**
 * AMADataHandler class
 *
 * This is the new implementation of the AMADataHandler class that
 * the whole ADA system is used to work with.
 *
 * The new implementation shall basically manage any extra required
 * field that a user may have beside the 'standard ones'. Theese are
 * usually stored in the tables named: 'autore', 'studente', 'tutor'
 * depending upon user's role.
 *
 * PLS NOTE:
 * For the customizations, you must implement all the stuff you need here,
 * keeping in mind that the parent it's always there to help you, kiddy!
 *
 *
 * @package     model
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2013, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        AMADataHandler
 * @version     0.1
 * @see         AMATesterDataHandler
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMATesterDataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;

/**
 * AMADataHandler.
 *
 */
class AMADataHandler extends AMATesterDataHandler
{
    /**
     * AMADataHandler constructor is inherited from AMATesterDataHandler
     * for time being there's no need to implement a new one.
     */

    /**
     * sets student datas into the student table in the proper tester.
     * Do no call parent function here, since we do not want to save
     * standard user (student) data (they're personal data and are saved separately)
     *
     * NOTE:
     * This is usally called by the save_*.php file in the ajax directory
     * via a call to the MultiPort.
     *
     * @param number id_student id of the student whose datas are to be updated
     * @param array datas to be saved
     *
     * @return id of saved student on success, AMAError on ErrorException
     * @access public
     *
     * @see AMATesterDataHandler::set_student()
     */
    public function setStudent($id_student, $user_dataAr, $extraTableName = false, $userObj = null, &$idFromPublicTester = null)
    {
        /*
         * if we're not saving extra fields, just call the parent
         * BUT: if we're saving extra fields, we do not call the parent because we want
         * extra fields to be saved by themselves!!
         */
        //      $retval = false;
        //      if (!$extraTableName) $retval = parent::set_student($id_student, $user_dataAr);
        $retval = parent::setStudent($id_student, $user_dataAr);
        if ($extraTableName) {
            if ($extraTableName == ADAUser::getExtraTableName()) {
                // aka Extra, stored in ADAUser::getExtraTableKeyProperty() table
                // usually is the 'studente' table
                $user_id_sql =  'SELECT ' . ADAUser::getExtraTableKeyProperty() .
                ' FROM ' . $extraTableName . ' WHERE ' . ADAUser::getExtraTableKeyProperty() . '=?';

                $user_id = $this->getOnePrepared($user_id_sql, [$id_student]);

                // if it's an error return it right away
                if (AMADB::isError($user_id)) {
                    $retval = $user_id;
                } else {
                    // get ExtraFields array
                    $extraFields = $userObj->getExtraFields();
                    if (!AMADB::isError($extraFields) && is_array($extraFields) && count($extraFields) > 0) {
                        // if $user_id not found, build an insert into else an update
                        if ($user_id === false) {
                            $saveQry = "INSERT INTO " . $extraTableName . " ( " . ADAUser::getExtraTableKeyProperty() . ", ";
                            $saveQry .= implode(", ", $extraFields);
                            $saveQry .= ") VALUES (" . $id_student . str_repeat(",?", count($extraFields)) . ")" ;
                        } else {
                            $saveQry = "UPDATE " . $extraTableName . " SET ";
                            foreach ($extraFields as $num => $field) {
                                $saveQry .= $field . "=?";
                                if ($num < count($extraFields) - 1) {
                                    $saveQry .= ", ";
                                }
                            }
                            $saveQry .= " WHERE " . ADAUser::getExtraTableKeyProperty() . "=" . $id_student;
                        }

                        // build valuesAr with extraFields only
                        foreach ($extraFields as $field) {
                            if (isset($user_dataAr[$field])) {
                                // check if it's a date and convert it to timestamp
                                if (stripos($field, "date") !== false) {
                                    $valuesAr[] = $this->dateToTs($user_dataAr[$field]);
                                } else {
                                    $valuesAr[] = $user_dataAr[$field];
                                }
                            } else {
                                $valuesAr[] = null;
                            }
                        }
                        $result = $this->queryPrepared($saveQry, $valuesAr);
                        if (AMADB::isError($result)) {
                            $retval = $result;
                        } else {
                            $retval = true;
                        }
                    }
                }
            } elseif (in_array($extraTableName, ADAUser::getLinkedTables())) { // stored in tableprefix_$extraTableName
                $extraTableClass = ADAUser::getClassForLinkedTable($extraTableName);
                $uniqueField = $extraTableClass::getKeyProperty();

                $tblPrefix = ADAUser::getTablesPrefix();

                $fieldList = $extraTableClass::getFields();

                // search for the unique field int the fieldList array
                $pos = array_search($uniqueField, $fieldList, true);
                // if found unset it since it doesn't need to be saved
                if ($pos !== false) {
                    unset($fieldList[$pos]);
                }

                $rowsToSave = $user_dataAr[$extraTableName];

                foreach ($rowsToSave as $rowToSave) {
                    // if row element is not to be saved, continue to next element
                    if (isset($rowToSave['_isSaved']) && $rowToSave['_isSaved'] == 1) {
                        continue;
                    }

                    if ($rowToSave[$uniqueField] > 0) {
                        $saveQry  = "UPDATE " . $tblPrefix . $extraTableName . " SET ";
                        foreach ($fieldList as $num => $field) {
                            $saveQry .= $field . "=?";
                            if ($num < count($fieldList)) {
                                $saveQry .= ", ";
                            }
                        }
                        $saveQry .= " WHERE " . $uniqueField . "=" . $rowToSave[$uniqueField];
                        $nextID = $rowToSave[$uniqueField];
                    } else {
                        // if using the public tester and the idFromPublicTester is not set or lt 0
                        if (MultiPort::getDSN(ADA_PUBLIC_TESTER) === $this->dsn &&  intval($idFromPublicTester) <= 0) {
                            // retrieve the id for the next insert
                            $nextIDQry = "SELECT MAX(" . $uniqueField . ") FROM " . $tblPrefix . $extraTableName;
                            $nextID = $this->getOnePrepared($nextIDQry);
                            $nextID = (is_null($nextID)) ? 1 : ++$nextID;
                            $idFromPublicTester = $nextID;
                        } else {
                            $nextID = $idFromPublicTester;
                        }
                        $saveQry  = "INSERT INTO " . $tblPrefix . $extraTableName . "( " . $uniqueField . ", ";
                        $saveQry .= implode(", ", $fieldList) . " ) VALUES ( " . $nextID;
                        $saveQry .= str_repeat(" ,?", count($fieldList)) . " )";
                    }

                    unset($rowToSave[$uniqueField]);
                    // prepare the array to be passed to the query
                    $valuesArr = [];
                    foreach ($fieldList as $field) {
                        if (isset($rowToSave[$field])) {
                            // check if it's a date and convert it to timestamp
                            if (stripos($field, "date") !== false) {
                                $valuesArr[] = $this->dateToTs($rowToSave[$field]);
                            } else {
                                $valuesArr[] = $rowToSave[$field];
                            }
                        } else {
                            $valuesArr[] = null;
                        }
                    }

                    $result = $this->queryPrepared($saveQry, $valuesArr);
                    if (AMADB::isError($result)) {
                        $retval = $result;
                    } else {
                        if (is_null($nextID) || intval($nextID) === 0) {
                            /**
                             * If could not geta nextID from the ADA_PUBLIC_TESTER
                             * read it as the lastInsertID and set it as the $idFromPublicTester
                             */
                            $nextID = $this->lastInsertID();
                            if (is_null($idFromPublicTester) || intval($idFromPublicTester) === 0) {
                                $idFromPublicTester = $nextID;
                            }
                        }
                        $retval = $nextID;
                    }
                }
            }
        }
        return $retval; // return insertedId on success, else the erorr
    }

    /**
     * loads and prepares all extra fields to be put in the
     * object via the setExtra method called in the multiport
     *
     * NOTE: this MUST be implemented if user class hasExtra is true.
     * can be empty or removed (no, it won't be called) if hasExtra is false.
     *
     * @param int $userId
     * @return array extra user data stored in the object
     *
     * @access public
     */
    public function getExtraData(ADAUser $userObj)
    {
        /**
         * get extras from table ADAUser::getExtraTableKeyProperty()
         */
        $selQry = "SELECT " . implode(", ", $userObj->getExtraFields()) .
                  " FROM " . ADAUser::getExtraTableName() .
                  " WHERE " . ADAUser::getExtraTableKeyProperty() . "=?";
        $returnArr = $this->getRowPrepared($selQry, [$userObj->getId()], AMA_FETCH_ASSOC);

        /**
         * load data form tables that have a 1:n relationship with studente table.
         *
         *  $tablseToLoad is the array of tables to be loaded. WITHOUT PREFIX.
         *  $tablesPrefix is the prefix of the table in the DB.
         *
         *  the foreach loop does the magic
         */

        $tablesToLoad = ADAUser::getLinkedTables();
        $tablesPrefix = ADAUser::getTablesPrefix();

        if (!is_null($tablesToLoad)) {
            foreach ($tablesToLoad as $table) {
                $class = ADAUser::getClassForLinkedTable($table);
                if (!empty($table) && class_exists($class)) {
                    $selQry = "SELECT " . implode(", ", $class::getFields()) .
                    " FROM " . $tablesPrefix . $table . " WHERE " . $class::getForeignKeyProperty() . "=?" .
                    " ORDER BY " . $class::getKeyProperty() . " ASC";

                    $extraArr = $this->getAllPrepared($selQry, [$userObj->getId()], AMA_FETCH_ASSOC);
                    if (!AMADB::isError($extraArr) && is_array($extraArr) && count($extraArr) > 0) {
                        foreach ($extraArr as $extraKey => $extraElement) {
                            foreach ($extraElement as $key => $val) {
                                if (stripos($key, "date") !== false) {
                                    $extraArr[$extraKey][$key] = Utilities::ts2dFN($val);
                                }
                            }
                        }
                    }
                    if (!empty($extraArr)) {
                        $returnArr[$table] = $extraArr;
                    }
                }
            }
        }
        return $returnArr;
    }

    /**
     * remove_user_extraRow
     *
     * deletes a row from user extra datas
     *
     * @param int $user_id
     * @param int $extraTableId
     * @param string $extraTable
     * @return query result, either a PDOStatement or PDOException object
     *
     * @access public
     */
    public function removeUserExtraRow($user_id, $extraTableId, $extraTable)
    {
        $tablesPrefix = ADAUser::getTablesPrefix();
        $extraTableClass = ADAUser::getClassForLinkedTable($extraTable);

        $delQry = "DELETE FROM " . $tablesPrefix . $extraTable .
        " WHERE " . $extraTableClass::getForeignKeyProperty() . "=? AND " . $extraTableClass::getKeyProperty() . "=?";

        $result = $this->queryPrepared($delQry, [$user_id, $extraTableId]);

        return $result;
    }

    /**
     * Returns an instance of AMADataHandler.
     *
     * @param  string $dsn - optional, a valid data source name
     * @return self an instance of AMADataHandler
     */
    public static function instance($dsn = null)
    {
        return parent::instance($dsn);
    }

    public function disconnect()
    {
        parent::disconnect();
        self::$instance = null;
    }
}

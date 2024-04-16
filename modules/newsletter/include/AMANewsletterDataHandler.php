<?php

/**
 * NEWSLETTER MODULE.
 *
 * @package     newsletter module
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            newsletter
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Newsletter;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;

use function Lynxlab\ADA\Main\Utilities\ts2dFN;

class AMANewsletterDataHandler extends AMADataHandler
{
    public const MODULES_NEWSLETTER_HISTORY_STATUS_UNDEFINED = 0;
    public const MODULES_NEWSLETTER_HISTORY_STATUS_SENDING = 1;
    public const MODULES_NEWSLETTER_HISTORY_STATUS_SENT = 2;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public static $PREFIX = 'module_newsletter_';

    /**
     * true form debug mode, will echo out the queries
     *
     * @var bool
     */
    private static $DEBUG = false;


    /**
     * Gets all the fields of the given table in an array, EXCLUDING the id field
     *
     * @param string $tablename table name to retreive fields list
     * @param bool $backTick true if fields name must be backtrick enquoted
     * @return array|AMAError on error, array on success
     *
     * @access private
     */
    private function getFieldsList($tablename, $backTick = true)
    {
        $db = & $this->getConnection();
        if (AMADB::isError($db)) {
            return $db;
        }

        $fields = [];

        $sql = "SHOW COLUMNS FROM " . self::$PREFIX . $tablename . " WHERE field NOT LIKE 'id'";
        $res = $db->getAll($sql, [], AMA_FETCH_ORDERED);

        if (!AMADB::isError($res)) {
            // row index 0 is 'Field' field
            foreach ($res as $row) {
                $fields[] = (($backTick) ? '`' : '') . $row[0] . (($backTick) ? '`' : '');
            }
        }
        return $fields;
    }

    /**
     * Executes a getAll or getOne call to the DB depending on the passed param
     *
     * @param bool $countOnly true if it's executing a count only query
     * @param string $sql the query to be executed
     * @return AMAError on error, result of query execution on success
     *
     * @access private
     */
    private function getOneOrGetAll($countOnly, $sql)
    {
        $db = & $this->getConnection();
        if (AMADB::isError($db)) {
            return $db;
        }

        if ($countOnly) {
            $retval = $db->getOne($sql);
        } else {
            $retval =  $db->getAll($sql);
        }

        if ($countOnly && AMADB::isError($retval)) {
            return -1;
        } else {
            return $retval;
        }
    }

    /**
     * Gets a list of authors filtered with specified criteria
     *
     * @param string $id_course course id agains which must filter
     * @param bool $countOnly true to only count authors
     * @return AMAError on error, result of query execution on success
     *
     * @access private
     */
    private function getAuthorsFiltered($id_course = null, $countOnly = false)
    {
        $selectField = 'DISTINCT(`UT`.`id_utente`)';
        if ($countOnly) {
            $selectField =  'COUNT(' . $selectField . ')';
        } else {
            $selectField .= ',`UT`.`e_mail`';
        }

        $sql =  'SELECT ' . $selectField;
        $sql .= ' FROM `utente` AS `UT`';
        if (!is_null($id_course)) {
            $sql .= ' JOIN `modello_corso` AS `MC` ON `MC`.`id_utente_autore` = `UT`.`id_utente`';
        }
        $sql .= ' WHERE `UT`.`tipo` =' . AMA_TYPE_AUTHOR;
        if (!is_null($id_course)) {
            $sql .= ' AND `MC`.`id_corso` = ' . intval($id_course);
        }

        if (self::$DEBUG) {
            print_r($sql . "\n");
        }

        return $this->getOneOrGetAll($countOnly, $sql);
    }

    /**
     * Gets a list of tutors filtered with specified criteria
     *
     * @param string $id_course course id agains which must filter
     * @param string $id_instance instance id agains which must filter
     * @param bool $countOnly true to only count tutors
     * @return AMAError on error, result of query execution on success
     *
     * @access private
     */
    private function getTutorsFiltered($id_course = null, $id_instance = null, $countOnly = false)
    {
        $selectField = 'DISTINCT(`UT`.`id_utente`)';
        if ($countOnly) {
            $selectField =  'COUNT(' . $selectField . ')';
        } else {
            $selectField .= ',`UT`.`e_mail`';
        }

        $sql =  'SELECT ' . $selectField;
        $sql .= ' FROM `utente` AS `UT`';
        if (!is_null($id_course)) {
            // $id_instance being not null, implies $id_course being not null
            $sql .= ' JOIN `tutor_studenti` AS `TS` ON `UT`.`id_utente`=`TS`.`id_utente_tutor`';
            $sql .= ' JOIN `istanza_corso` AS `IST` ON `IST`.`id_istanza_corso`=`TS`.`id_istanza_corso`';
            $sql .= ' JOIN `modello_corso` AS `MC` ON `MC`.`id_corso` = `IST`.`id_corso`';
        }
        $sql .= ' WHERE `UT`.`tipo`=' . AMA_TYPE_TUTOR;
        if (!is_null($id_course)) {
            $sql .= ' AND `MC`.`id_corso` = ' . intval($id_course);
        }
        if (!is_null($id_instance)) {
            $sql .= ' AND `IST`.`id_istanza_corso` = ' . intval($id_instance);
        }

        if (self::$DEBUG) {
            print_r($sql . "\n");
        }

        return $this->getOneOrGetAll($countOnly, $sql);
    }

    /**
     * Gets a list of switchers filtered with specified criteria
     *
     * @param bool $countOnly true to only count switchers
     * @return  AMAError on error, result of query execution on success
     *
     * @access private
     */
    private function getSwitchersFiltered($countOnly = false)
    {
        $selectField = 'DISTINCT(`UT`.`id_utente`)';
        if ($countOnly) {
            $selectField =  'COUNT(' . $selectField . ')';
        } else {
            $selectField .= ',`UT`.`e_mail`';
        }

        $sql =  'SELECT ' . $selectField;
        $sql .= ' FROM `utente` AS `UT` WHERE `tipo` =' . AMA_TYPE_SWITCHER;

        if (self::$DEBUG) {
            print_r($sql . "\n");
        }

        return $this->getOneOrGetAll($countOnly, $sql);
    }

    /**
     * Gets a list of students filtered with specified criteria
     *
     * @param string $id_course course id agains which must filter
     * @param string string $id_instance instance id agains which must filter
     * @param string $userCourseStatus status of students in the course agains which must filter
     * @param string $userPlatformStatus status of students in the platform agains which must filter
     * @param bool $countOnly true to only count students
     * @return AMAError on error, result of query execution on success
     *
     * @access private
     */
    private function getStudentsFiltered($id_course = null, $id_instance = null, $userCourseStatus = null, $userPlatformStatus = null, $countOnly = false)
    {
        $selectField = 'DISTINCT(`UT`.`id_utente`)';
        if ($countOnly) {
            $selectField =  'COUNT(' . $selectField . ')';
        } else {
            $selectField .= ',`UT`.`e_mail`';
        }

        $sql =  'SELECT ' . $selectField;
        $sql .= ' FROM `utente` AS `UT`';

        if (is_null($userCourseStatus) && is_null($id_course) && is_null($id_instance)) {
            $sql .= ' WHERE `UT`.`tipo`=' . AMA_TYPE_STUDENT;
            if (!is_null($userPlatformStatus)) {
                $sql .= ' AND `UT`.`stato`=' . $userPlatformStatus;
            }
        } else {
            /*
             * main query is like this (with some added fields that might be useful
             *

               SELECT `UT`.id_utente, `UT`.username,`UT`.e_mail, `UT`.`stato`,`MC`.`id_corso`, `MC`.`nome`,`MC`.`titolo`,`IST`.id_istanza_corso, `IST`.title,`ISCR`.status
               FROM `utente` AS `UT`, `iscrizioni` AS `ISCR`
               JOIN `istanza_corso` AS `IST` ON `ISCR`.`id_istanza_corso` = `IST`.`id_istanza_corso`
               JOIN `modello_corso` AS `MC` ON `MC`.`id_corso` = `IST`.`id_corso`
               WHERE `UT`.`id_utente` = `ISCR`.`id_utente_studente`

             */
            $sql .= ', `iscrizioni` AS `ISCR`';
            $sql .= ' JOIN `istanza_corso` AS `IST` ON `ISCR`.`id_istanza_corso` = `IST`.`id_istanza_corso`';
            $sql .= ' JOIN `modello_corso` AS `MC` ON `MC`.`id_corso` = `IST`.`id_corso`';
            $sql .= ' WHERE `UT`.`tipo`=' . AMA_TYPE_STUDENT;
            $sql .= ' AND `UT`.`id_utente` = `ISCR`.`id_utente_studente`';

            if (!is_null($id_course)) {
                $sql .= ' AND `MC`.`id_corso` = ' . intval($id_course);
            }
            if (!is_null($id_instance)) {
                $sql .= ' AND `IST`.`id_istanza_corso` = ' . intval($id_instance);
            }
            if (!is_null($userPlatformStatus)) {
                $sql .= ' AND `UT`.`stato`=' . $userPlatformStatus;
            }
            if (!is_null($userCourseStatus)) {
                $sql .= ' AND `ISCR`.`status`=' . $userCourseStatus;
            }
        }

        if (self::$DEBUG) {
            print_r($sql . "\n");
        }

        return $this->getOneOrGetAll($countOnly, $sql);
    }

    /**
     * Builds all filter values from the passed array
     *
     * @param array $arrayValues values from which to build the filter
     * @return array the representation of the generated filter
     *
     * @access public
     */
    public function buildFilterFromArray($arrayValues = [])
    {
        if (isset($arrayValues['userType']) && intval($arrayValues['userType']) > 0) {
            $filter['userType'] = $arrayValues['userType'];
        } else {
            $filter['userType'] = null;
        }

        if (isset($arrayValues['idCourse']) && intval($arrayValues['idCourse']) > 0) {
            $filter['idCourse'] = $arrayValues['idCourse'];
        } else {
            $filter['idCourse'] = null;
        }

        if (isset($arrayValues['idInstance']) && intval($arrayValues['idInstance']) > 0) {
            $filter['idInstance'] = $arrayValues['idInstance'];
        } else {
            $filter['idInstance'] = null;
        }

        if (isset($arrayValues['userCourseStatus']) && intval($arrayValues['userCourseStatus']) > -1) {
            $filter['userCourseStatus'] = $arrayValues['userCourseStatus'];
        } else {
            $filter['userCourseStatus'] = null;
        }

        if (isset($arrayValues['userPlatformStatus']) && intval($arrayValues['userPlatformStatus']) > -1) {
            $filter['userPlatformStatus'] = $arrayValues['userPlatformStatus'];
        } else {
            $filter['userPlatformStatus'] = null;
        }

        return $filter;
    }

    /**
     * Gets a list of users filtered with specified criteria
     *
     * @param array $filterValues values to filter
     * @param bool $countOnly true to only count users
     * @return <number, array> count of the filtered users or array containing, id and email of filtered users
     *
     * @access public
     */
    public function getUsersFiltered($filterValues = [], $countOnly = true)
    {
        // prepare vars depending on passed array values

        extract($this->buildFilterFromArray($filterValues));

        $retval    = ($countOnly) ? 0 : [];
        $authors   = ($countOnly) ? 0 : [];
        $tutors    = ($countOnly) ? 0 : [];
        $switchers = ($countOnly) ? 0 : [];
        $students  = ($countOnly) ? 0 : [];

        // start with authors
        if ($userType == AMA_TYPE_AUTHOR || $userType == 9999) {
            $authors =  $this->getAuthorsFiltered($idCourse, $countOnly);
        }
        // second tutors
        if ($userType == AMA_TYPE_TUTOR || $userType == 9999) {
            $tutors = $this->getTutorsFiltered($idCourse, $idInstance, $countOnly);
        }
        // third switchers
        if ($userType == AMA_TYPE_SWITCHER || $userType == 9999) {
            $switchers = $this->getSwitchersFiltered($countOnly);
        }
        // last students
        if ($userType == AMA_TYPE_STUDENT || $userType == 9999) {
            $students = $this->getStudentsFiltered($idCourse, $idInstance, $userCourseStatus, $userPlatformStatus, $countOnly);
        }

        if ($countOnly) {
            $retval = intval($authors) + intval($tutors) + intval($switchers) + intval($students);
        } else {
            $retval = array_merge($authors, $tutors, $switchers, $students);
        }

        return $retval;
    }

    /**
     * Saves a row in the newsletter history
     *
     * @param int $id_newsletter the id of the newsletter
     * @param array $filterArray the filter used to send the newsletter out
     * @param int $count users count
     * @param int $status status of the sending process as defined in module config.inc.php
     * @return AMAError on error, inserted row id on success
     *
     * @access public
     */
    public function saveNewsletterHistory($id_newsletter, $filterArray, $count, $status)
    {
        $values[0] = $id_newsletter;
        $values[1] = json_encode($filterArray);
        $values[2] = $this->dateToTs("now");
        $values[3] = $count;
        $values[4] = $status;

        $sql = 'INSERT INTO `' . self::$PREFIX . 'history`  (' . implode(',', $this->getFieldsList('history')) . ') VALUES (?,?,?,?,?)';

        $db = & $this->getConnection();
        if (AMADB::isError($db)) {
            return $db;
        }

        $result = $this->queryPrepared($sql, $values);

        if (AMADB::isError($result)) {
            return new $result();
        }
        return $db->lastInsertID();
    }

    /**
     * Gets the newsletter details
     *
     * @param int $id the id pf the newsletter
     * @return  AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function getNewsletter($id)
    {
        $sql = 'SELECT * FROM `' . self::$PREFIX . 'newsletters` WHERE id=?';

        $retval = $this->getRowPrepared($sql, $id, AMA_FETCH_ASSOC);

        if (!AMADB::isError($retval) && $retval !== false) {
            $retval['date'] = ts2dFN($retval['date']);
        }

        return $retval;
    }

    /**
     * Performs newsletter duplication
     *
     * @param int $id_newsletter the id of the newsletter to be duplicated
     * @return  AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function duplicateNewsletter($id_newsletter)
    {
        $fieldsArr = $this->getFieldsList('newsletters');

        $sql = 'INSERT INTO `' . self::$PREFIX . 'newsletters` (' . implode(',', $fieldsArr) . ') SELECT ' . implode(',', $fieldsArr) . ' FROM `' . self::$PREFIX . 'newsletters` WHERE id=?';

        $retval =  $this->executeCriticalPrepared($sql, $id_newsletter);

        return $retval;
    }

    /**
     * Performs newsletter deletion, together with history
     *
     * @param unknown $id_newsletter the id of the newsletter to be deleted
     * @return  AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function deleteNewsletter($id_newsletter)
    {
        $sql = 'DELETE FROM `' . self::$PREFIX . 'history` WHERE id_newsletter=?';
        $retval = $this->executeCriticalPrepared($sql, $id_newsletter);

        /**
         *  error checking and handling must be don by the caller
         *  anyway, I don't care if I have delete nothing with the query
         *  above, this means that the newsletter has no history
         */

        $sql = 'DELETE FROM `' . self::$PREFIX . 'newsletters` WHERE id=?';
        $retval = $this->executeCriticalPrepared($sql, $id_newsletter);

        return $retval;
    }

    /**
     * Checks if the given newsletter is in sending status
     *
     * @param int $id_newsletter the id of the newsletter to check
     * @return boolean true if it's in sending status
     *
     * @access public
     */
    public function isSending($id_newsletter)
    {
        $sql = 'SELECT COUNT(`id`) FROM `' . self::$PREFIX . 'history` WHERE id_newsletter=? AND status=' . self::MODULES_NEWSLETTER_HISTORY_STATUS_SENDING;

        return (intval($this->getOnePrepared($sql, $id_newsletter)) > 0);
    }

    /**
     * Gets newsletter history details
     *
     * @param int $id_newsletter the id of the newsletter
     * @param boolean $statusSent true if must return a value only if newsletter is in sending status
     * @return AMAError on error, result of query execution on success
     */
    public function getNewsletterHistory($id_newsletter, $statusSent = false)
    {
        $sql = 'SELECT * FROM `' . self::$PREFIX . 'history` WHERE id_newsletter=?';
        if ($statusSent) {
            $sql .= ' AND `status`=' . self::MODULES_NEWSLETTER_HISTORY_STATUS_SENT;
        }

        $retval = $this->getAllPrepared($sql, $id_newsletter, AMA_FETCH_ASSOC);

        if (!AMADB::isError($retval) && $retval !== false) {
            for ($i = 0; $i < count($retval); $i++) {
                $retval[$i]['datesent'] = ts2dFN($retval[$i]['datesent']);
            }
        }
        return $retval;
    }

    /**
     * Sets the status in the history details of a newsletter
     *
     * @param int $history_id the history id of the newsletter
     * @param int $newstatus status to be set, as defined in module config.inc.php
     * @return AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function setHistoryStatus($history_id, $newstatus)
    {
        $sql = 'UPDATE `' . self::$PREFIX . 'history` SET `status`=? WHERE `id`=?';
        return $this->executeCriticalPrepared($sql, [$newstatus, $history_id]);
    }

    /**
     * Gets the newsletters list
     *
     * @param array $fields the array of the fields to get
     * @param boolean $idOrdered true if must order by insertion id asc
     * @return AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function getNewsletters($fields = [], $idOrdered = true)
    {
        $sql = 'SELECT ';

        if (empty($fields)) {
            $sql .= '*';
        } else {
            $sql .= implode(',', $fields);
        }

        $sql .= ' FROM `' . self::$PREFIX . 'newsletters`';
        if ($idOrdered) {
            $sql .= ' ORDER BY `id` DESC';
        }

        return $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
    }

    /**
     * Saves a newsletter, either in insert or update mode
     *
     * @param array $newsletterHa contains the datas to be saved
     * @return AMAError on error, result of query execution on success
     *
     * @access public
     */
    public function saveNewsletter($newsletterHa)
    {

        if (intval($newsletterHa['id']) <= 0) {
            $sql = 'INSERT INTO `' . self::$PREFIX . 'newsletters` (' . implode(',', $this->getFieldsList('newsletters')) . ') VALUES ( ?, ?, ?, ?, ?, ?)';
            unset($newsletterHa['id']);
        } else {
            $sql = 'UPDATE `' . self::$PREFIX . 'newsletters` SET ' . implode('=?,', $this->getFieldsList('newsletters')) . '=? WHERE id=?';
        }
        return $this->queryPrepared($sql, array_values($newsletterHa));
    }
}

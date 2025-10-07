<?php

/**
 *
 * PDO wrapper
 *
 *
 *
 * PHP version >= 5.0
 *
 * @package     db
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        AMAPDOWrapper
 * @version     0.2
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Main\Logger\ADALogger;
use PDO;
use PDOException;
use PDOStatement;

/**
 * AMAPDOWrapper maps calls to PEAR::DB or PEAR:MDBS methods to PDO methods.
 */
class AMAPDOWrapper
{
    /**
     * @var AMAPDO|PDOException $connection_object - it will contain an
     * AMAPDO connection object, or a PDOException object on error.
     */
    private $connection_object = null;

    /**
     * connection_object getter
     *
     * @return AMAPDO Object or PDOException on error
     * @access public
     */
    public function connectionObject()
    {
        return $this->connection_object;
    }

    /**
     * Constructor for class AMADB_PDO_wrapper
     * Establishes a connection by calling connect
     * This is kept for compatibility reasons, could have moved connect code here.
     *
     * @param string $dsn the string "data source name" as requested by PEAR and that will be translated to PDO in the connect method
     * @param string $options
     *
     * @access public
     */
    public function __construct($dsn, $options = false)
    {
        $this->connect($dsn, $options);
        $this->setupConnection();
    }

    /**
     * Translates the passed dsn into the PDO dialect and creates
     * the AMAPDO object, thus estabilishong a db connection.
     *
     * Stores the object in $this->connection_object.
     *
     * On error it sets the connection_object the raised PDOException.
     *
     * @param mixed $dsn the string "data source name"
     * @param array $options an associative array of options names and their values
     *
     * @access private
     */
    private function connect($dsn, $options = false)
    {
        /**
         * @author giorgio 24/mag/2013
         *
         * Need to reverse the dsn string to get back separate values again.
         * This is done in the regexp.
         * Note list building where first matched item is not used because it's
         * going to be the whole matched string (aka $dsn).
         *
         * I could use defined constants here, but then what's the sense of passing
         * a dsn string??
         *
         */
        $matched = [];
        preg_match('/^([a-z]+):\/\/(\S*):(\S*)@(\S*)\/(\S*)$/', $dsn, $matched);
        [, $dbtype, $username, $password, $dbhost, $dbname] = $matched;

        /**
         * Ok, let's make the connection by instatiatiating the object
         */
        try {
            $this->connection_object = new AMAPDO(
                $dbtype,
                $dbhost,
                $dbname,
                $username,
                $password,
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                    PDO::ATTR_DEFAULT_FETCH_MODE => AMA_FETCH_DEFAULT,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
        } catch (PDOException $e) {
            $this->connection_object = self::handleException($e);
        }
    }

    /**
     * method to setup the connection, executes whatever is needed to
     * have a properly working db connection
     */
    private function setupConnection()
    {
        /*
         * remove unwanted sql modes for compatibility with AMA code
         */
        $stmt = $this->connection_object->prepare('SELECT @@SESSION.sql_mode;');
        if ($stmt->execute()) {
            $modes = [];
            $removeModes = ['STRICT_', 'ONLY_FULL_GROUP_BY'];
            foreach ($stmt->fetchAll() as $modeArrs) {
                foreach ($modeArrs as $modeStr) {
                    $modes = array_filter(explode(',', $modeStr), function ($mode) use ($modes, $removeModes) {
                        if (!in_array($mode, $modes)) {
                            foreach ($removeModes as $removeMode) {
                                if (str_contains($mode, $removeMode)) {
                                    return false;
                                }
                            }
                        }
                        return true;
                    });
                }
            }
            if (count($modes) > 0) {
                $this->connection_object->exec('SET SESSION sql_mode = \'' . implode(',', $modes) . '\';');
            }
        }
    }

    /**
     * It looks like there's no disconnect method on PDO, setting the pointer to null should do the trick
     *
     * @param bool $force to force disconnection even if the connection is opened persistently
     * @return bool true on success, false if not connected
     *
     * @access public
     */
    public function disconnect($force = true)
    {
        if ($this->connection_object instanceof AMAPDO) {
            $this->connection_object = null;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns number of affected rows on passed statement.
     * Returns 0 if invalid or no statement passed in.
     *
     * @param PDOStatement $stmt the execute statement to count rows for. defaults to null
     * @return integer value with the number of rows, 0 on failure.
     *
     * @access public
     */
    public function affectedRows($stmt = null)
    {
        if ($stmt instanceof PDOStatement) {
            return $stmt->rowCount();
        }
        return 0;
    }

    /**
     * Calls exec method.
     *
     * @param string $query - the SQL query.
     * @return number of affected rows on success, PDOException error on failure.
     *
     * @access public
     */
    public function &exec($query)
    {
        try {
            $retval = $this->connection_object->exec($query);
            if (!is_bool($retval)) {
                return $retval;
            } else {
                return 0;
            }
        } catch (PDOException $e) {
            $retval = self::handleException($e);
            return $retval;
        }
    }

    /**
     * Calls columnCount method.
     *
     * @param PDOStatement $stmt the execute statement to count columns for. defaults to null
     * @return integer value with the number of columns, 0 on failure.
     *
     * @access public
     */
    public function numCols($stmt = null)
    {
        if ($stmt instanceof PDOStatement) {
            return $stmt->columnCount();
        } else {
            return 0;
        }
    }

    /**
     * Prepares the passed query
     * Don't know how to handle all the parameters that MDB2 has!
     *
     * @param string $query the query to be prepared
     * @param string $types
     * @param string $result_types
     * @param array  $lobs
     * @return PDOStatement
     *
     * @access public
     */
    public function &prepare($query, $types = null, $result_types = null, $lobs = [])
    {
        $stmt = $this->connection_object->prepare($query);
        return $stmt; // ,$types,$result_types,$lobs);
    }

    /**
     * Calls execute method on passed PDOStatement with passed values
     *
     * @param PDOStatement $stmt
     * @param array $values params to be bounded to the query
     * @return PDOStatement the executed statement from which yuo're going to fetch or false on failure
     *
     * @access public
     */
    public function &execute($stmt, $values)
    {
        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        if ($stmt instanceof PDOStatement) {
            $success = $stmt->execute($values);
            if ($success === true) {
                return $stmt;
            }
        }
        return false;
    }

    /**
     * Calls AMAPDO free method.
     *
     * @return bool true on success
     *
     * @access public
     */
    public function free()
    {
        return $this->connection_object->free();
    }

    /**
     * Gets the dsn of the current database connection
     *
     * @param string $type  array or string. kept for MDB2 calls compatibility, always array is used. defaults to array
     * @param string $hidepw wether to hide the password or not. kept for MDB2 calls compatibility, always false is used. defaults to false
     * @return string the dsn as a string
     *
     * @access public
     */
    public function getDSN($type = 'array', $hidepw = false)
    {

        $arrayDSN = $this->connection_object->getDSN($type, $hidepw);
        $stringDSN = $arrayDSN['phptype'] . '://' . $arrayDSN['username'] . ':' . $arrayDSN['password'] .
            '@' . $arrayDSN['hostspec'] . '/' . $arrayDSN['database'];

        //    print_r($arrayDSN);
        return $stringDSN;
    }

    /**
     * Calls lastInsertId
     *
     * @param  string $table not used
     * @param  string $field not used
     * @return string representing the row ID of the last row that was inserted into the database or a PDOException if not supported by the DBMS
     */
    public function lastInsertID($table = null, $field = null)
    {
        try {
            $retval = $this->connection_object->lastInsertID();
            return $retval;
        } catch (PDOException $e) {
            return self::handleException($e);
        }
    }

    /**
     * Method for handling thrown exceptions all in the same way.
     * For time being, simply logs the exception and returns it.
     *
     * @author giorgio 31/mag/2013
     *
     * @param  PDOException $e the PDOException to be handled
     * @return PDOException the passed PDOException
     *
     * @access private
     */
    private static function handleException(PDOException $e)
    {
        /**
         * Probably log the error somewhere and return it in the connection_object itself
         */
        ADALogger::logDb("[PDOException] : " . $e->getFile() . ":" . $e->getLine() . " - " . $e->getMessage());
        return $e;
    }
}

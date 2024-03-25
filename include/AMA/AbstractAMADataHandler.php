<?php

/**
 * AMA_DataHandler implements a class to handle complex DB read/write operations
 * for the ADA project.
 *
 * @access public
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Main\Logger\ADALogger;
use Lynxlab\ADA\Main\Stack\RBStack;
use PDO;
use PDOException;
use PDOStatement;

abstract class Abstract_AMA_DataHandler
{
    /**
     * database connection string
     * @var unknown_type
     */
    protected $dsn;

    /**
     * database connection object
     *
     * @var unknown_type
     */
    protected $db;

    /**
     * rollback stack
     *
     * @var unknown_type
     */
    protected $rbStack;


    //protected static $instance = null;

    /**
     * function AMA_DataHandler
     *
     * @param $db_type
     * @param $db_name
     * @param $db_user
     * @param $db_password
     * @param $db_host
     * @return unknown_type
     */
    public function __construct($dsn = null)
    {
        if ($dsn === null) {
            //      $this->dsn = ADA_DB_TYPE.'://'.ADA_DB_USER.':'.ADA_DB_PASS.'@'.
            //                   ADA_DB_HOST.'/'.ADA_DB_NAME;
            $this->dsn = ADA_DEFAULT_TESTER_DB_TYPE . '://' . ADA_DEFAULT_TESTER_DB_USER
                . ':' . ADA_DEFAULT_TESTER_DB_PASS . '@' . ADA_DEFAULT_TESTER_DB_HOST
                . '/' . ADA_DEFAULT_TESTER_DB_NAME;
        } else {
            $this->dsn = $dsn;
        }

        $this->db = AMA_DB_NOT_CONNECTED;
        $this->rbStack = new RBStack();
    }

    /**
     * Return the API version
     *
     * @return the API version number as a string
     */
    public function apiVersion()
    {
        return "0.4.1";
    }

    /**
     * function getConnection
     *
     * Used to handle database connection.
     * Calls AMA_DB::connect() method and returns a reference to
     * the AMA_DB connection object created.
     * If $this->db already stores a connection object, then simply
     * return a reference to it.
     *
     * @return mixed $db - an AMA_DB connection object on success,
     *                  an AMA_Error object on failure.
     */
    protected function &getConnection()
    {

        if ($this->db === AMA_DB_NOT_CONNECTED) {
            //            ADALogger::log_db('Creating a new database connection '. $this->dsn);
            $db = &AMA_DB::connect($this->dsn);
            if (AMA_DB::isError($db)) {
                $retval = new AMA_Error(AMA_ERR_DB_CONNECTION);
                return $retval;
            }
            $this->db = &$db;
        } else {
            //           ADALogger::log_db('Db già connesso '. $this->dsn . ' ' .$this->db->getDSN());
            if ($this->dsn !== $this->db->getDSN()) {
                ADALogger::log_db('dsn diverso chiusura DB ' . $this->dsn . ' ' . $this->db->getDSN());
                // Close existing datababse connection
                if (is_object($this->db) && method_exists($this->db, 'disconnect')) {
                    ADALogger::log_db('Closing open connection to database ' .  $this->db->getDSN());
                    $this->db->disconnect();
                }
                // Open a new database connection
                $db = &AMA_DB::connect($this->dsn);
                if (AMA_DB::isError($db)) {
                    return new AMA_Error(AMA_ERR_DB_CONNECTION);
                }
                $this->db = &$db;
            }
        }
        return $this->db;
    }

    /**
     * function executeCritical
     *
     * Execute a query and return the number of affected rows (>0) or an AMA_Error
     *
     * @param string $query the INSER, UPDATE or DELETE sql query
     * @return mixed int number of affected rows or AMA_Error object
     */
    protected function executeCritical($query)
    {
        // use the first 6 chars in $query (corresponding to INSERT, UPDATE, DELETE)
        // to choose wich ama error eventually raise

        $keyword = strtolower(substr($query, 0, 6));
        switch ($keyword) {
            case 'insert':
                $ERROR = AMA_ERR_ADD;
                break;

            case 'update':
                $ERROR = AMA_ERR_UPDATE;
                break;

            case 'delete':
                $ERROR = AMA_ERR_REMOVE;
                break;
        }
        // based on selected DB Abstraction Layer, execute the right code
        // to perform a query and obtain affected rows
        switch (DB_ABS_LAYER) {
            case PDO_DB:
            default:
                $res = $this->DB_execute_critical($query);
                break;
                /**
                 * Pls handle other databases connection here by adding more cases
                 */
        }
        // $res is the number of affected rows or an error
        // if $res is an error, return an AMA Error with error message as
        // additional debug info
        if (AMA_DB::isError($res)) {
            // get debug info (this works from php 4.3.0)
            $deb_bac = debug_backtrace();
            // create debuginfo
            $error_msg = "while in {$deb_bac[1]['function']} in file {$deb_bac[1]['file']} on line {$deb_bac[1]['line']} " . $res->getMessage();
            // create a new AMA error with error code $ERROR and additional debug info $error_msg
            return new AMA_Error($ERROR, $error_msg);
        }
        // if $res is not an error, it's the number of rows affected by $query
        if ($res == 0) {
            // get debug info
            $deb_bac = debug_backtrace();
            // create debuginfo referring to the function that called executeCritical
            $error_msg = "while in {$deb_bac[1]['function']} in file {$deb_bac[1]['file']} on line {$deb_bac[1]['line']}: unknown error!";
            // create a new AMA error with error code $ERROR and additional debug info $error_msg
            return new AMA_Error($ERROR, $error_msg);
        }
        // if $res > 0, query succeeded. we return number of affected rows.
        return $res;
    }

    /**
     * Executes a query and returns the number of affected rows
     *
     * @param string $query
     * @return mixed number of affected rows or an error
     */
    protected function DB_execute_critical($query)
    {

        //ADALogger::log_db('Call to DB_execute_critical');
        // connect to db if not connected
        $db = &$this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        // execute query, and if there's an error return it
        $res = $db->exec($query);
        if (AMA_DB::isError($res)) {
            return $res;
        }
        // if $res is not an error, return the number of affected rows
        // return $db->affectedRows();
        return $res;
    }

    /**
     *  Functions for SQL string handling
     */

    /**
     * Prepares a string to be submitted to a SQL parser
     *
     * @access private
     *
     * @param $s the string to be prepared
     *
     * @return the prepared string
     */
    public function sql_prepared($s)
    {
        $s =  addslashes($s);
        return "'$s'";
    }

    /**
     * Removes backslashes from prepared string (not so useful, uh?)
     *
     * @access private
     *
     * @param $s the string to be transformed
     *
     * @return the transformed string
     */
    public function sql_deprepared($s)
    {
        // function used to remove backslashes
        // and other stuff like \', ...
        $s = stripslashes($s);
        return $s;
    }

    /**
     * Assigns a NULL value to a DB field if the value is not properly defined
     *
     * @access private
     *
     * @param $s the value to be checked
     *
     * @return the value or "NULL"
     */
    protected function or_null($s)
    {
        if (!$s || $s == "''") {
            return "NULL";
        } else {
            return $s;
        }
    }

    /**
     * Assigns a ZERO value to a DB field if the value is not properly defined
     *
     * @access private
     *
     * @param $s the value to be checked
     *
     * @return the value or ZERO (0)
     */
    protected function or_zero($s)
    {
        if (!isset($s) || $s == "''" || $s == "") {
            return "0";
        } else {
            return $s;
        }
    }

    /**
     * Converts a timestamp to a date of the format specified as a string
     *
     * @access public
     *
     * @param $timestamp the timestamp
     * @param $format the format used to convert the timestamp (optional, default = ADA_DATE_FORMAT)
     *
     * @return the string representing the timestamp as a date, according to the format
     */
    public static function ts_to_date($timestamp, $format = ADA_DATE_FORMAT)
    {
        if ($timestamp == "") {
            return "";
        }
        return strftime($format, (float)$timestamp);
    }

    /**
     * Converts a a date of the format specified as a string to an integer timestamp
     *
     * @access public
     *
     * @param $date the date string
     * @param $time the time string (format hh:mm:ss, defaults to null)
     *
     * @return the timestamp as an integer
     */
    public static function date_to_ts($date, $time = null)
    {
        if ($date == "NULL") {
            return $date;
        }

        if ($date == "now") {
            return time();
        }

        // $date_ar = split ('[\\/.-]', $date);
        $date_ar = preg_split('/[\\/.-]/', $date);
        if (count($date_ar) < 3) {
            return 0;
        }

        // $format_ar = split ('[/.-]',ADA_DATE_FORMAT);
        $format_ar = preg_split('/[\\/.-]/', ADA_DATE_FORMAT);
        if ($format_ar[0] == "%d") {
            $giorno = (int)$date_ar[0];
            $mese = (int)$date_ar[1];
        } else {
            $giorno = (int)$date_ar[1];
            $mese = (int)$date_ar[0];
        }

        $anno = (int)$date_ar[2];

        if (!is_null($time)) {
            [$ora, $minuti, $secondi] = explode(':', $time);
        } else {
            $ora = 0;
            $minuti = 0;
            $secondi = 0;
        }

        $unix_date = mktime($ora, $minuti, $secondi, $mese, $giorno, $anno);

        return $unix_date;
        //return strtotime($date);
    }

    /**
     * calculates a new timestamp by adding $number_of_days days to the
     * given timestamp or, if it is not given, to the current time stamp.
     *
     * @param
     * @param
     * @return
     */
    public function add_number_of_days($number_of_days, $timestamp = null)
    {

        if (!is_null($timestamp)) {
            return $timestamp + $number_of_days * AMA_SECONDS_IN_A_DAY;
        } else {
            return time() + $number_of_days * AMA_SECONDS_IN_A_DAY;
        }
    }

    /**
     *  Functions for error handling
     */

    /**
     * Tell whether a result code from an AMA method is an error
     *
     * @access public
     *
     * @param $value int result code
     *
     * @return bool whether $value is an error
     */
    public static function isError($value)
    {
        return (is_object($value) && AMA_DB::isError($value));
        //         ($value instanceof  AMA_Error)
        // (get_class($value) == 'AMA_Error' || is_subclass_of($value, 'PEAR_Error')));
    }

    /**
     * Return a textual error message for an AMA error object
     *
     * @access public
     *
     * @param $value error object
     *
     * @return string error message, or translateFN("unknown") if the error code was
     * not recognized
     */
    // now included in AMA_error !
    public function errorMessage($error)
    {

        if (AMA_DataHandler::isError($error)) {
            return $error->errorMessage();
        }

        return '';
    }

    /**
     *  Functions for transactions handling
     */

    /**
     * Begin a transaction
     *
     * @access private
     *
     */
    protected function begin_transaction()
    {
        // if the rollback stack is not empty, then set up a marker
        if (!$this->rbStack->isEmpty()) {
            $this->rbStack->insert_marker();
        }
    }

    /**
     * Add an instruction to the rollback stack
     *
     * @access private
     *
     */
    protected function rs_add()
    {

        // nuber of arguments
        $numargs = func_num_args();

        // generate an error if less than two arguments
        if ($numargs < 2) {
            return new AMA_Error(AMA_ERR_TOO_FEW_ARGS);
        }
        // get all the arguments as an array
        $arg_list = func_get_args();

        // build the element as an hash

        // the name is the first argument
        $element_ha['name'] = $arg_list[0];

        // record the number of arguments (all but the name)
        $element_ha['n_params'] = $numargs - 1;

        // all other parameters goes into params_i keys
        for ($i = 1; $i < $numargs; $i++) {
            $element_ha["params_$i"] = $arg_list[$i];
        }

        // put the element onto the stack
        $this->rbStack->push($element_ha);
    }

    /**
     * Do the rollback.
     * Actually performs the rollback, executing all the instructions in the stack
     * (up to the last marker in the markers stack).
     * If something goes wrong, an error is returned.
     *
     * @access private
     *
     * @return a string containing a message
     *
     */
    protected function rollback()
    {
        $err_msg = '';

        // get last marker
        $marker = $this->rbStack->remove_marker();

        ADALogger::log_db("entered _rollback (size: " . $this->rbStack->get_size() . ", marker: $marker)");

        // loop on the stack untill the last marker is reached
        while ($this->rbStack->get_size() > $marker) {
            // get the element from the rollback stack
            $element_ha = $this->rbStack->pop();

            // build the string to call the function (using eval)
            $function_name = $element_ha['name'];
            $last_param = $element_ha['n_params'];

            // the result will be assigned to $res
            $e_str  = "\$res = ";

            // the function name and opening parenthesis
            $e_str .= '$this->' . $function_name . "(";

            // add the parameters
            if ($last_param) {
                // all but last parameters, separated by commas
                for ($i = 0; $i < $last_param - 1; $i++) {
                    $e_str .= $element_ha["params_$i"] . ",";
                }
                // last parameters
                $e_str .= $element_ha["params_$last_param"];
            }

            // closing parenthesis
            $e_str .= ")";

            // and closing instruction
            $e_str .= ";";

            // evaluate the function

            ADALogger::log_db("_rollback calls: $e_str");
            eval($e_str);

            // add to error message if the instruction in the stack fails, somehow
            if (AMA_DataHandler::isError($res)) {
                $err_msg .= AMA_SEP . "error in function $function_name (" . $res->getMessage() . ")";
            }
        }

        // return error message
        if ($err_msg) {
            $err_msg = AMA_ROLLBACK_NOT_SUCCESSFUL . AMA_SEP . $err_msg;
        } else {
            $err_msg = AMA_ROLLBACK_SUCCESSFUL;
        }

        return $err_msg;
    }

    /**
     * Do the commit.
     * Delete the rollback stack
     *
     * @access private
     *
     */
    protected function commit()
    {
        // get last marker
        $marker = $this->rbStack->remove_marker();

        // loop on the stack untill the last marker is reached
        while ($this->rbStack->get_size() > $marker) {
            // delete the rollback stack statement
            // by assigning it to a dummy variable
            $a = $this->rbStack->pop();
        }
    }

    /**
     * Execution of prepared statements
     */

    /**
     * Prepares and executes a query.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @return object $resultObj - the result object as returned by the AMA_DB layer
     *
     * @access private
     */
    private function prepareAndExecute($sql, $values = [])
    {
        $db = &$this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        /**
         * qui potrebbe esserci il codice che verifica se la query $sql ha gia' uno
         * statement precompilato nell'array statico $statements degli statement precompilati
         * che potrebbe essere mantenuto anche qui (anche se forse e' meglio averlo
         * come attributo della classe).
         */

        /**
         * let's check if $sql has alreay been prepared, and let's do it if it's not.
         */
        if (!$sql instanceof PDOStatement) {
            $stmt = $db->prepare($sql);
        } else {
            $stmt = $sql;
        }

        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        try {
            $resultObj = $stmt->execute($values);
            if ($resultObj) {
                return $stmt;
            } else {
                return new AMA_Error();
            }
        } catch (PDOException $e) {
            return $e;
        }
        /**
         * sempre nell'ottica del caching a livello di esecuzione dello script degli
         * statement precompilati, questo $stmt->free() devo toglierlo ed
         * implementare il __destruct() per AMA e li fare il free di tutti gli statement
         * presenti nell'array $statements.
         */
    }

    /**
     * This is the prepared version of the AMA_DB getRow() method.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @param  int    $fetchmode - optional, indicates how to retrieve the results.
     * @return mixed  array when no fetchmode is specified or AMA_FETCH_ASSOC is specified,
     *                object when AMA_FETCH_OBJECT is specified.
     */
    protected function getRowPrepared($sql, $values = [], $fetchmode = null)
    {
        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        $resultObj = $this->prepareAndExecute($sql, $values);

        if (AMA_DB::isError($resultObj)) {
            return $resultObj;
        }

        $resultAr = $resultObj->fetch($fetchmode);
        $resultObj->closeCursor();
        return $resultAr;
    }

    /**
     * This is the prepared version of the AMA_DB getAll() method.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @param  int    $fetchmode - optional, indicates how to retrieve the results.
     * @return mixed  array when no fetchmode is specified or AMA_FETCH_ASSOC is specified,
     *                object when AMA_FETCH_OBJECT is specified.
     */
    protected function getAllPrepared($sql, $values = [], $fetchmode = null, $col = null)
    {
        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        $resultObj = $this->prepareAndExecute($sql, $values);

        if (AMA_DB::isError($resultObj)) {
            return $resultObj;
        }

        if (is_null($col)) {
            $resultAr = $resultObj->fetchAll($fetchmode);
        } elseif (is_numeric($col) && intval($col) >= 0) {
            $resultAr = $resultObj->fetchAll($fetchmode, intval($col));
        }

        $resultObj->closeCursor();
        return $resultAr;
    }

    /**
     * This is the prepared version of the AMA_DB getOne() method.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @param  int    $fetchmode - optional, indicates how to retrieve the results.
     * @return mixed  array when no fetchmode is specified or AMA_FETCH_ASSOC is specified,
     *                object when AMA_FETCH_OBJECT is specified.
     */
    protected function getOnePrepared($sql, $values = [])
    {
        return self::getRowPrepared($sql, $values, PDO::FETCH_COLUMN);
    }

    /**
     * This is the prepared version of the AMA_DB getCol() method.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @param  int    $fetchmode - optional, indicates how to retrieve the results.
     * @return mixed  array when no fetchmode is specified or AMA_FETCH_ASSOC is specified,
     *                object when AMA_FETCH_OBJECT is specified.
     */
    protected function getColPrepared($sql, $values = [])
    {
        return self::getAllPrepared($sql, $values, PDO::FETCH_COLUMN, 0);
    }

    /**
     * This is the prepared version of the AMA_DB query() method.
     *
     * @param  string $sql       - the sql query with placeholders
     * @param  array  $values    - the values to bind with the prepared statement
     * @param  int    $fetchmode - optional, indicates how to retrieve the results.
     * @return mixed  array when no fetchmode is specified or AMA_FETCH_ASSOC is specified,
     *                object when AMA_FETCH_OBJECT is specified.
     */
    protected function queryPrepared($sql, $values = [], $fetchmode = null)
    {
        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        $resultObj = $this->prepareAndExecute($sql, $values);

        if (!AMA_DB::isError($resultObj) && $resultObj === AMA_DB_OK) {
            return true;
        }

        return $resultObj;
    }

    /**
     * This is the prepared version of the ama_pear_mdb2_wrapper exec() method.
     *
     * @param $stmt
     * @param $values
     * @return number of affected rows on success, MDB2 error on failure
     */
    protected function execPrepared($stmt, $values = [])
    {
        $db = &$this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        /**
         * if $values is a scalar, let's transform it into a one-element array
         */
        if (!is_array($values)) {
            $values =  [$values];
        }

        try {
            $resultObj = $stmt->execute($values);
            if ($resultObj) {
                return $db->affectedRows($stmt);
            } else {
                return new AMA_Error();
            }
        } catch (PDOException $e) {
            return $e;
        }
    }

    /**
     * This is the prepared version of this class executeCritical() method.
     *
     * @param $sql
     * @param $values
     * @return unknown_type
     */
    protected function executeCriticalPrepared($sql, $values = [])
    {

        $keyword = strtolower(substr($sql, 0, 6));
        switch ($keyword) {
            case 'insert':
                $ERROR = AMA_ERR_ADD;
                break;

            case 'update':
                $ERROR = AMA_ERR_UPDATE;
                break;

            case 'delete':
                $ERROR = AMA_ERR_REMOVE;
                break;
        }
        // based on selected DB Abstraction Layer, execute the right code
        // to perform a query and obtain affected rows
        switch (DB_ABS_LAYER) {
            case PDO_DB:
            default:
                $res = $this->DB_execute_critical_prepared($sql, $values);
                break;
                /**
                 * Pls handle other databases connection here by adding more cases
                 */
        }
        // $res is the number of affected rows or an error
        // if $res is an error, return an AMA Error with error message as
        // additional debug info
        if (AMA_DB::isError($res)) {
            // get debug info (this works from php 4.3.0)
            $deb_bac = debug_backtrace();
            // create debuginfo
            $error_msg = "while in {$deb_bac[1]['function']} in file {$deb_bac[1]['file']} on line {$deb_bac[1]['line']} " . $res->getMessage();
            // create a new AMA error with error code $ERROR and additional debug info $error_msg
            return new AMA_Error($ERROR, $error_msg);
        }
        // if $res is not an error, it's the number of rows affected by $query
        if ($res == 0) {
            // get debug info
            $deb_bac = debug_backtrace();
            // create debuginfo referring to the function that called executeCritical
            $error_msg = "while in {$deb_bac[1]['function']} in file {$deb_bac[1]['file']} on line {$deb_bac[1]['line']}: unknown error!";
            // create a new AMA error with error code $ERROR and additional debug info $error_msg
            return new AMA_Error($ERROR, $error_msg);
        }
        // if $res > 0, query succeeded. we return number of affected rows.
        return $res;
    }

    /**
     * This is the prepared version of this class' DB_execute_critical() method.
     * Executes a query and returns the number of affected rows
     *
     * @param string $query
     * @return mixed number of affected rows or an error
     */
    protected function DB_execute_critical_prepared($sql, $values = [])
    {

        //ADALogger::log_db('Call to DB_execute_critical_prepared');

        // connect to db if not connected
        $db = &$this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        /**
         * qui potrebbe esserci il codice che verifica se la query $sql ha gia' uno
         * statement precompilato nell'array statico $statements degli statement precompilati
         * cmantenuto anche qui come attributo della classe.
         */
        $stmt = $db->prepare($sql);

        // execute query, and if there's an error return it
        $result = $this->queryPrepared($stmt, $values);
        if (AMA_DB::isError($result)) {
            return $result;
        }
        /**
         * sempre nell'ottica del caching a livello di esecuzione dello script degli
         * statement precompilati, questo $stmt->free() devo toglierlo ed
         * implementare il __destruct() per AMA e li fare il free di tutti gli statement
         * presenti nell'array $statements.
         */
        // if $res is not an error, return the number of affected rows
        return $db->affectedRows($stmt);
    }

    /**
     * When no references exist to this object, disconnect from database if connected.
     *
     * @return unknown_type
     */
    public function __destruct()
    {
        // FIXME: verificare se e' ok chiudere cosi' una connessione al database.

        //ADALogger::log_db('Call to Abstract_AMA_DataHandler destructor');
        if (is_object($this->db) && method_exists($this->db, 'disconnect')) {
            //ADALogger::log_db('Closing open connection to database');
            $this->disconnect();
        }
    }

    public function disconnect()
    {
        //ADALogger::log_db('Call to disconnect');
        if (is_object($this->db) && method_exists($this->db, 'disconnect')) {
            //ADALogger::log_db('Closing open connection to database');
            $this->db->disconnect();
            $this->db = AMA_DB_NOT_CONNECTED;
        }
    }
}

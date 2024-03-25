<?php

/**
 *
 * Common
 *
 * @access public
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Main\Logger\ADALogger;

use function Lynxlab\ADA\Main\Utilities\ts2dFN;

class AMA_Common_DataHandler extends Abstract_AMA_DataHandler
{
    protected static $instance = null;
    /**
     *
     * @param  string $dsn - a valid data source name
     * @return an instance of AMA_Common_DataHandler
     */
    public function __construct($dsn = null)
    {
        $common_db_dsn = ADA_COMMON_DB_TYPE . '://' . ADA_COMMON_DB_USER . ':'
                . ADA_COMMON_DB_PASS . '@' . ADA_COMMON_DB_HOST . '/'
                . ADA_COMMON_DB_NAME;
        parent::__construct($common_db_dsn);
    }

    /**
     * Returns an instance of AMA_Common_DataHandler.
     *
     * @param  string $dsn - optional, a valid data source name
     * @return an instance of AMA_Common_DataHandler
     */
    public static function instance($dsn = null)
    {

        //ADALogger::log_db('AMA_Common_DataHandler: get instance for main db connection');
        $callerClassName = get_called_class();
        if (!is_null(self::$instance) && get_class(self::$instance) !== $callerClassName) {
            self::$instance = null;
        }

        if (self::$instance == null) {
            //ADALogger::log_db('AMA_Common_DataHandler: creating a new instance of AMA_Common_DataHandler');
            self::$instance = new $callerClassName();
        }
        return self::$instance;
    }

    /**
     * Methods accessing table `utente`
     */
    // MARK: Methods accessing table `utente`

    /**
     * Checks if exists a user with the given username and password.
     *
     * @param  string $username
     * @param  string $password
     * @return mixed
     */
    public function check_identity($username, $password)
    {

        $sql = 'SELECT U.id_utente, U.tipo, U.nome, U.cognome FROM utente U ';
        $sql_params = [$username, sha1($password)];

        /**
         * @author giorgio 05/mag/2014 16:32:07
         *
         * if not in a multiprovider environment, must
         * match the user in the selected provider
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $testerAr = $this->get_tester_info_from_pointer($GLOBALS['user_provider']);
            $sql .= ',utente_tester UT WHERE ' .
                    'U.id_utente = UT.id_utente AND U.username=? AND U.password=? AND UT.id_tester=?';
            array_push($sql_params, $testerAr[0]);
        } else {
            $sql .= 'WHERE U.username=? AND U.password=?';
        }

        $resultHa = $this->getRowPrepared($sql, $sql_params, AMA_FETCH_ASSOC);

        if (!is_array($resultHa) || empty($resultHa)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }
        return $resultHa;
    }

    /**
     *
     * @param $user_dataAr
     * @return unknown_type
     */
    public function add_user($user_dataAr = [], $mustcheck = true)
    {

        /*
         * Before inserting a row, check if a user with this username already exists
         */
        if ($mustcheck) {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
            $user_id = $this->getOnePrepared($user_id_sql, [$user_dataAr['username']]);
            if (AMA_DB::isError($user_id)) {
                return $user_id;
            } elseif ($user_id) {
                return new AMA_Error(AMA_ERR_UNIQUE_KEY);
            }
        }

        $add_user_sql = 'INSERT INTO utente(nome,cognome,tipo,e_mail,username,password,layout,
                               indirizzo,citta,provincia,nazione,codice_fiscale,birthdate,sesso,
                               telefono,stato,lingua,timezone,avatar,birthcity,birthprovince)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

        $values = [
                $user_dataAr['nome'],
                $user_dataAr['cognome'],
                $user_dataAr['tipo'],
                $user_dataAr['e_mail'],
                $user_dataAr['username'],
                //sha1($user_dataAr['password']),
                $user_dataAr['password'], // sha1 encoded
                $user_dataAr['layout'],
                $user_dataAr['indirizzo'],
                $user_dataAr['citta'],
                $user_dataAr['provincia'],
                $user_dataAr['nazione'],
                $user_dataAr['codice_fiscale'],
                AMA_Common_DataHandler::date_to_ts($user_dataAr['birthdate']),
                $user_dataAr['sesso'],
                $user_dataAr['telefono'],
//                $this->or_null($user_dataAr['indirizzo']),
//                $this->or_null($user_dataAr['citta']),
//                $this->or_null($user_dataAr['provincia']),
//                $this->or_null($user_dataAr['nazione']),
//                $this->or_null($user_dataAr['codice_fiscale']),
//                $this->or_zero($user_dataAr['birthdate']),
//                $this->or_null($user_dataAr['sesso']),
//                $this->or_null($user_dataAr['telefono']),
                $user_dataAr['stato'],
                $user_dataAr['lingua'],
                $user_dataAr['timezone'],
                $user_dataAr['avatar'],
                $user_dataAr['birthcity'],
                $user_dataAr['birthprovince'],

        ];

        /*
     * Adds the user
        */
        $result = $this->executeCriticalPrepared($add_user_sql, $values);
        if (AMA_DB::isError($result)) {
            return $result;
        }
        /*
         * Return the user id of the inserted user
         */
        if (!MULTIPROVIDER) {
            /**
             * If it's not multiprovider there's no other way
             * of getting the ID but a call to lastInsertID
             */
            $user_id = $this->getConnection()->lastInsertID();
        } else {
            $user_id = $this->find_user_from_username($user_dataAr['username']);
        }

        /*
    $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
    $user_id = $this->getOnePrepared($user_id_sql, $user_dataAr['username']);
    if (AMA_DB::isError($user_id)) {
      return new AMA_Error(AMA_ERR_GET);
    }
        */
        return $user_id;
    }

    /**
     * Return the user id of the user with username = $username
     *
     * @param string $username
     * @return AMA_Error|number
     */
    public function find_user_from_username($username)
    {
        /**
         * @author giorgio 05/mag/2014 15:44:34
         *
         * if not in a multiprovider environment, must
         * match the user in the selected provider
         *
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $testerAr = $this->get_tester_info_from_pointer($GLOBALS['user_provider']);
            $user_id_sql = 'SELECT U.id_utente FROM utente U, utente_tester UT WHERE ' .
                           'U.id_utente = UT.id_utente AND id_tester=? AND username=?';
            $sql_params =  [$testerAr[0], $username];
        } else {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
            $sql_params = $username;
        }
        $user_id = $this->getOnePrepared($user_id_sql, $sql_params);
        if (AMA_DB::isError($user_id) || $user_id == null) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $user_id;
    }

    /**
     * Return the user id of the user with email = $e_mail
     *
     * @param string $email
     * @return AMA_Error|number
     */
    public function find_user_from_email($email)
    {
        /**
         * @author giorgio 05/mag/2014 16:29:39
         *
         * if not in a multiprovider environment, must
         * match the user in the selected provider
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $testerAr = $this->get_tester_info_from_pointer($GLOBALS['user_provider']);
            $user_id_sql = 'SELECT U.id_utente FROM utente U, utente_tester UT WHERE ' .
                    'U.id_utente = UT.id_utente AND id_tester=? AND e_mail=?';
            $sql_params =  [$email, $testerAr[0]];
        } else {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE e_mail=?';
            $sql_params = $email;
        }
        $user_id = $this->getOnePrepared($user_id_sql, $sql_params);
        if (AMA_DB::isError($user_id) || $user_id == null) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $user_id;
    }
    /**
     *
     * @param $user_dataAr
     * @return unknown_type
     */
    public function add_user_to_tester($user_id, $tester_id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql = 'INSERT INTO utente_tester VALUES(' . $user_id . ',' . $tester_id . ')';
        $result = $db->query($sql);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }
        return true;
    }

    /* Get password for a user
   *
   * @access private
   *
   * @param $id the user's id
   *
   * @return an array containing all the informations about a user
   *        res_ha['password']
    */


    public function get_user_pwd($id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        // get a row from table UTENTE
        $query = "select password from utente where id_utente=$id";
        $res_ar =  $db->getOne($query, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($res_ar)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }
        return $res_ar;
    }

    /**
     * Get all informations about a user
     *
     * @access private
     *
     * @param $id the user's id
     *
     * @return an array containing all the informations about a user
     *        res_ha['nome']
     *        res_ha['cognome']
     *        res_ha['tipo']
     *        res_ha['e-mail']
     *        res_ha['telefono']
     *        res_ha['username']
     *        res_ha['password']
     */

    //private function get_user_info($id) {
    public function get_user_info($id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        // get a row from table UTENTE
        $query = "select nome, cognome, tipo, e_mail AS email, telefono, username, layout, " .
                "indirizzo, citta, provincia, nazione, codice_fiscale, birthdate, sesso, " .
                "telefono, stato, lingua, timezone, cap, matricola, avatar, birthcity, birthprovince from utente where id_utente=$id";
        $res_ar =  $db->getRow($query, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($res_ar)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        $res_ar['id'] = $id;
        if (isset($res_ar['birthdate'])) {
            $res_ar['birthdate'] = ts2dFN($res_ar['birthdate']);
        }
        return $res_ar;
    }

    // FIXME: forse deve essere pubblico
    /**
     *
     * @param $id_user
     * @param $id_course_instance
     * @return unknown_type
     */
    private function get_student_level($id_user, $id_course_instance)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        if (empty($id_course_instance)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        // get a row from table iscrizioni
        // FIXME: usare getOne al posto di getRow
        $res_ar =  $db->getRow("select livello from iscrizioni where id_utente_studente=$id_user and  id_istanza_corso=$id_course_instance");
        if (AMA_DB::isError($res_ar)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $res_ar[0];
    }

    /**
     * Get type of a user
     *
     * @access public
     *
     * @param $id the user's id
     *
     * @return an INT (1,2,3,4) or Error
     */
    public function get_user_type($id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $result =  $db->getOne("select tipo from utente where id_utente=$id");
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (empty($result)) { //OR is_object($res_ar)){
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * Get status of a user
     *
     * @access public
     *
     * @param $id the user's id
     *
     * @return an INT (1,2,3,4) or Error
     */
    public function get_user_status($id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        $query = "select stato from utente where id_utente=$id";
        $result =  $db->getOne($query);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (empty($result)) { //OR is_object($res_ar)){
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * Get all informations about an author
     *
     * @access public
     *
     * @param $id the author's id
     *
     * @return an array containing all the informations about an author's
     *        res_ha['nome']
     *        res_ha['cognome']
     *        res_ha['e-mail']
     *        res_ha['telefono']
     *        res_ha['username']
     *        res_ha['password']
     *        res_ha['tariffa']
     *        res_ha['profilo']
     *        res_ha['layout']
     *
     */
    public function get_author($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->get_user_info($id);
        if (AMA_Common_DataHandler::isError($get_user_result)) {
            // $get_user_result is an AMA_Error object
            return $get_user_result;
        }

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        // get a row from table AUTORE
        // FIXME: dobbiamo sapere a quale tester e' associato per ottenere il  suo profilo.

        //    $get_author_sql = "SELECT tariffa, profilo FROM autore WHERE id_utente_autore=$id";
        //    $get_author_result = $db->getRow($get_author_sql, NULL, AMA_FETCH_ASSOC);
        //    if (AMA_DB::isError($get_author_result)) {
        //      return new AMA_Error(AMA_ERR_GET);
        //    }
        //    if(!$get_author_result) {
        /* inconsistency found! a message should be logged */
        //      return new AMA_Error(AMA_ERR_INCONSISTENT_DATA);
        //    }

        //    return array_merge($get_user_result, $get_author_result);
        return $get_user_result;
    }

    /**
     * Get all informations about student
     *
     * @access public
     *
     * @param $id the student's id
     *
     * @return an array containing all the informations about an administrator
     *        res_ha['nome']
     *        res_ha['cognome']
     *        res_ha['e-mail']
     *        res_ha['telefono']
     *        res_ha['username']
     *        res_ha['password']
     *        res_ha['tariffa']
     *        res_ha['profilo']
     *
     */
    public function get_student($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->get_user_info($id);
        if (AMA_Common_DataHandler::isError($get_user_result)) {
            // $get_user_result is an AMA_Error object
            return $get_user_result;
        }
        // get_student($id) originally did not return the user id as a result,
        unset($get_user_result['id']);

        return $get_user_result;
    }

    public function get_user($id)
    {
        return $this->get_student($id);
    }
    /**
     * Get all informations about tutor
     *
     * @access public
     *
     * @param $id the tutor's id
     *
     * @return an array containing all the informations about a tutor
     *        res_ha['nome']
     *        res_ha['cognome']
     *        res_ha['e-mail']
     *        res_ha['telefono']
     *        res_ha['username']
     *        res_ha['password']
     *        res_ha['tariffa']
     *        res_ha['profilo']
     *        res_ha['layout']
     *
     *        an AMA_Error object on failure
     *
     */
    public function get_tutor($id)
    {

        // get a row from table UTENTE
        $get_user_result = $this->get_user_info($id);
        if (AMA_Common_DataHandler::isError($get_user_result)) {
            // $get_user_result is an AMA_Error object
            return $get_user_result;
        }
        // get_tutor($id) originally did not return the user id as a result,
        unset($get_user_result['id']);

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        return $get_user_result;
    }

    /**
     * Updates informations related to a user
     *
     * @access public
     *
     * @param $id the user id
     *        $admin_ar the informations. empty fields are not updated
     *
     * @return an error if something goes wrong, true on success
     *
     */
    public function set_user($id, $user_ha)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        // verify that the record exists and store old values for rollback
        $user_id_sql =  'SELECT id_utente FROM utente WHERE id_utente=?';
        $user_id = $this->getOnePrepared($user_id_sql, [$id]);
        if (AMA_DB::isError($user_id)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (is_null($user_id)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        // backup old values
        $old_values_ha = $this->get_user($id);

        // verify unique constraint once updated
        /*
 * Nome e cognome non determinano univocamente un utente.
 * E' possibile avere piu' di un utente con lo stesso nome e cognome.
        */

        //    $new_nome    = $user_ha['nome'];
        //    $new_cognome = $user_ha['cognome'];
        //    $old_nome    = $old_values_ha['nome'];
        //    $old_cognome = $old_values_ha['cognome'];
        //
        //
        //    if ($new_nome != $old_nome || $new_cognome != $old_cognome){
        //
        //      //$existing_user_id_sql = 'SELECT id_utente FROM utente WHERE nome=? AND cognome=?';
        //      $existing_user_id_sql = 'SELECT id_utente FROM utente WHERE nome=? AND cognome=?';
        //      $result = $this->getOnePrepared($existing_user_id_sql, array($new_nome, $new_cognome));
        //      if(AMA_DB::isError($result)) {
        //        return new AMA_Error(AMA_ERR_GET);
        //      }
        //      if($result) {
        //        return new AMA_Error(AMA_ERR_UNIQUE_KEY);
        //      }
        //    }

        $where = ' WHERE id_utente=?';
        if (empty($user_ha['password'])) {
            $update_user_sql = 'UPDATE utente SET nome=?, cognome=?, e_mail=?, telefono=?, layout=?, '
                    . 'indirizzo=?, citta=?, provincia=?, nazione=?, codice_fiscale=?, birthdate=?, sesso=?, '
                    . 'telefono=?, stato=?, lingua=?, timezone=?, cap=?, matricola=?, avatar=?, birthcity=?, birthprovince=?';

            $valuesAr = [
                    $user_ha['nome'],
                    $user_ha['cognome'],
                    $user_ha['e_mail'],  // FIXME: VERIFICARE BENE
                    $user_ha['telefono'],
                    $user_ha['layout'],
                    $user_ha['indirizzo'],
                    $user_ha['citta'],
                    $user_ha['provincia'],
                    $user_ha['nazione'],
                    $user_ha['codice_fiscale'],
                    AMA_Common_DataHandler::date_to_ts($user_ha['birthdate']),
                    $user_ha['sesso'],
                    $user_ha['telefono'],
                    $user_ha['stato'],
                    $user_ha['lingua'],
                    $user_ha['timezone'],
                    $user_ha['cap'],
                    $user_ha['matricola'],
                    $user_ha['avatar'],
                    $user_ha['birthcity'],
                    $user_ha['birthprovince'],
            ];
        } else {
            $update_user_sql = 'UPDATE utente SET nome=?, cognome=?, e_mail=?, password=?, telefono=?, layout=?, '
                    . 'indirizzo=?, citta=?, provincia=?, nazione=?, codice_fiscale=?, birthdate=?, sesso=?, '
                    . 'telefono=?, stato=?, lingua=?, timezone=?, cap=?, matricola=?, avatar=?, birthcity=?, birthprovince=?';

            $valuesAr = [
                    $user_ha['nome'],
                    $user_ha['cognome'],
                    $user_ha['e_mail'],  // FIXME: VERIFICARE BENE
                    $user_ha['password'], //sha1 encoded
                    $user_ha['telefono'],
                    $user_ha['layout'],
                    $user_ha['indirizzo'],
                    $user_ha['citta'],
                    $user_ha['provincia'],
                    $user_ha['nazione'],
                    $user_ha['codice_fiscale'],
                    AMA_Common_DataHandler::date_to_ts($user_ha['birthdate']),
                    $user_ha['sesso'],
                    $user_ha['telefono'],
                    $user_ha['stato'],
                    $user_ha['lingua'],
                    $user_ha['timezone'],
                    $user_ha['cap'],
                    $user_ha['matricola'],
                    $user_ha['avatar'],
                    $user_ha['birthcity'],
                    $user_ha['birthprovince'],
            ];
        }
        /**
         * UPDATE USERNAME ONLY IF MODULES_GDPR
         */
        if (defined('MODULES_GDPR') && MODULES_GDPR === true && array_key_exists('username', $user_ha) && strlen($user_ha['username']) > 0 && $user_ha['username'] !== $old_values_ha['username']) {
            $update_user_sql .= ',username=?';
            $valuesAr[] = $user_ha['username'];
        }

        $update_user_sql .= $where;
        $valuesAr[] = $id;

        $result = $this->queryPrepared($update_user_sql, $valuesAr);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_UPDATE);
        }
        return true;
    }


    /**
     * Updates status  of a student (NOT ANY USER)
     *
     * @access public
     *
     * @param $id the student's id
     *        $status the new status
     *
     * @return  if something goes wrong, new status on success
     *
     */
    public function set_user_status($userid, $status)
    {
        $student_ha = [];
        $student_ha['stato'] = $status;
        $usertype = $this->get_user_type($userid);
        if ((is_numeric($status)) and ($usertype == AMA_TYPE_STUDENT)) {
            $result =  $this->set_student($userid, $student_ha);
            if ($this->isError($result)) {
                return $result;
            } else {
                $new_status = $this->get_user_status($userid);
                return $new_status;
            }
        } else {
            return new AMA_Error(AMA_ERR_UPDATE);
        }
    }

    /**
     * Get those users ids verifying the given criterium
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono
     *
     * @param  $usertype the type of users
     *
     * @param  $clause the clause string which will be added to the select
     *
     * @param  $order the ordering filter
     *
     * @return a nested array containing the list, or an AMA_Error object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function find_users_list($field_list_ar, $clause = '', $usertype = AMA_TYPE_STUDENT, $order = 'cognome')
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }

        // handle null clause, too
        if ($clause) {
            $clause = ' where ' . $clause;
        }

        if ($clause == '') {
            $query = "select id_utente$more_fields from utente where tipo=" . $usertype . " order by $order";
        } else {
            $query = "SELECT id_utente$more_fields from utente $clause and tipo=" . $usertype . "  order by $order";
        }

        // do the query
        $users_ar =  $db->getAll($query);
        if (AMA_DB::isError($users_ar)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $users_ar;
    }

    /**
     * Methods accessing table `tester`
     */
    // MARK: Methods accessing table `tester`


    public function get_testers_for_user($id_user)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT T.puntatore FROM utente_tester AS U, tester AS T "
                . "WHERE U.id_utente = $id_user AND T.id_tester = U.id_tester";

        $testers_result = $db->getCol($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function get_testers_for_username($username)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT T.puntatore FROM utente AS U, utente_tester AS UT, tester AS T "
                . "WHERE U.username = '$username' AND UT.id_utente= U.id_utente AND T.id_tester = UT.id_tester";

        $testers_result = $db->getCol($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function get_all_testers($field_data_Ar = [])
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        if (!empty($field_data_Ar)) {
            $fields = implode(', ', $field_data_Ar);
            $fields .= ', ';
        } else {
            $fields = '';
        }

        $testers_sql = 'SELECT ' . $fields . ' puntatore FROM tester WHERE 1';
        $testers_result = $db->getAll($testers_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function get_tester_info_from_id($id_tester, $fetchmode = null)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT id_tester,nome,ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore,descrizione,iban FROM tester "
                . "WHERE id_tester = ?";

        $testers_result = $db->getRow($testers_sql, $id_tester, $fetchmode);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function get_tester_info_from_id_course($id_course)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $tester_sql = "SELECT T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.citta,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore,T.descrizione,T.iban "
                . "FROM tester AS T, servizio_tester AS ST WHERE ST.id_corso=$id_course AND T.id_tester=ST.id_tester";

        $tester_resultAr = $db->getRow($tester_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($tester_resultAr)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (is_null($tester_resultAr)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $tester_resultAr;
    }

    public function get_tester_info_from_service($id_service)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $tester_sql = "SELECT T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore,T.descrizione,T.iban "
                . "FROM tester AS T, servizio_tester AS ST WHERE ST.id_servizio=$id_service AND T.id_tester=ST.id_tester";

        $tester_resultAr = $db->getRow($tester_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($tester_resultAr)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        if (is_null($tester_resultAr)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $tester_resultAr;
    }


    public function get_tester_info_from_pointer($tester)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT id_tester,nome,ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore,descrizione,iban FROM tester "
                . "WHERE puntatore = '$tester'";

        $testers_result = $db->getRow($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function add_tester($tester_dataAr = [])
    {

        $tester_sql = 'INSERT INTO tester(nome, ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore,descrizione,iban) '
                . 'VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';

        $valuesAr = [
                $tester_dataAr['tester_name'],
                $tester_dataAr['tester_rs'],
                $tester_dataAr['tester_address'],
                $tester_dataAr['tester_city'],
                $tester_dataAr['tester_province'],
                $tester_dataAr['tester_country'],
                $tester_dataAr['tester_phone'],
                $tester_dataAr['tester_email'],
                $tester_dataAr['tester_resp'],
                $tester_dataAr['tester_pointer'],
                $tester_dataAr['tester_desc'],
                (array_key_exists('tester_iban', $tester_dataAr) && strlen(trim($tester_dataAr['tester_iban'])) > 0) ? trim($tester_dataAr['tester_iban']) : null,
        ];

        $result = $this->queryPrepared($tester_sql, $valuesAr);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }

        return $this->getConnection()->lastInsertID();
    }

    public function set_tester($tester_id, $tester_dataAr = [])
    {

        $tester_sql = 'UPDATE tester SET nome=?, ragione_sociale=?,indirizzo=?,citta=?,provincia=?,nazione=?,telefono=?,e_mail=?,responsabile=?,puntatore=?,descrizione=?,iban=? WHERE id_tester=?';

        $valuesAr = [
                $tester_dataAr['tester_name'],
                $tester_dataAr['tester_rs'],
                $tester_dataAr['tester_address'],
                $tester_dataAr['tester_city'],
                $tester_dataAr['tester_province'],
                $tester_dataAr['tester_country'],
                $tester_dataAr['tester_phone'],
                $tester_dataAr['tester_email'],
                $tester_dataAr['tester_resp'],
                $tester_dataAr['tester_pointer'],
                $tester_dataAr['tester_desc'],
                (array_key_exists('tester_iban', $tester_dataAr) && strlen(trim($tester_dataAr['tester_iban'])) > 0) ? trim($tester_dataAr['tester_iban']) : null,
                $tester_id,
        ];

        $result = $this->queryPrepared($tester_sql, $valuesAr);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_UPDATE);
        }

        return true; // FIXME: deve restituire l'id del tester appena aggiunto
    }

    /**
     * Methods accessing table `servizio_tester`
     */
    // MARK: Methods accessing table `servizio_tester`
    /**
     * Get the tester where the given service is provided
     *
     * @access public
     *
     * @param $id_service the service's id
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_tester_for_service($id_service)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT id_tester FROM servizio_tester "
                . "WHERE id_servizio = $id_service";

        $testers_result = $db->getCol($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    /**
     * Get informations about service provided by a given tester
     *
     * @access public
     *
     * @param $id_tester the tester's id
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_services_for_tester($id_tester)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT id_servizio FROM servizio_tester "
                . "WHERE id_tester = $id_tester";

        $testers_result = $db->getCol($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    /**
     * Get informations about services and relative tester by a given tester id
     *
     * @access public
     *
     * @param $id_tester the tester's id or array of ids or empty array / false for not apply restriction
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_services_tester_info($id_tester = [])
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        if (!is_array($id_tester)) {
            $id_tester = [$id_tester];
        }

        $sql = "SELECT
					t.`id_tester`, t.`nome` as nome_tester, t.`ragione_sociale`, t.`puntatore`,
					s.*,
					st.`id_corso`
				FROM `tester` t
				JOIN `servizio_tester` st ON (st.`id_tester` = t.`id_tester`)
				JOIN `servizio` s ON (s.`id_servizio` = st.`id_servizio`)";

        if (!empty($id_tester)) {
            $sql .= " WHERE t.`id_tester` IN (" . implode(',', $id_tester) . ")";
        }
        $sql .= " ORDER BY t.`nome` ASC, s.`nome` ASC";

        $res = $db->getAll($sql, null, AMA_FETCH_ASSOC);
        if (self::isError($res)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $res;
    }

    /**
     * Get informations about service implementation (=course) provided by a given tester
     *
     * @access public
     *
     * @param $id_tester the tester's id
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_courses_for_tester($id_tester)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $testers_sql = "SELECT id_corso FROM servizio_tester "
                . "WHERE id_tester = $id_tester";

        $testers_result = $db->getCol($testers_sql);
        if (self::isError($testers_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $testers_result;
    }

    /**
     * gets the max id of a course in the whole ADA system
     *
     * Mainly used in course creation (
     *
     * @author giorgio
     *
     * @return AMA_Error | integer
     * @access public
     */
    public function get_course_max_id()
    {
        $sql = "SELECT MAX(id_corso) FROM servizio_tester";
        $max_id = $this->getOnePrepared($sql);

        if (AMA_DB::isError($max_id)) {
            $retval = new AMA_Error(AMA_ERR_GET);
        } else {
            $retval = $max_id;
        }

        return $retval;
    }

    /**
     * Get informations about service
     *
     * @access public
     *
     * @param $id_service the service's id
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_service_info($id_servizio)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $service_sql = "SELECT id_servizio, nome, descrizione, livello, durata_servizio, min_incontri, max_incontri, durata_max_incontro  FROM servizio "
                . "WHERE id_servizio = $id_servizio";

        $service_result = $db->getRow($service_sql);
        if (self::isError($service_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $service_result;
    }

    /**
     * Get informations about service
     *
     * @access public
     *
     * @param $id_service the service's id
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_info_for_tester_services($id_tester)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $services_sql = "SELECT S.id_servizio, S.nome, S.descrizione, S.livello, S.durata_servizio, S.min_incontri, S.max_incontri, S.durata_max_incontro,"
                . " ST.id_corso FROM servizio AS S, servizio_tester AS ST WHERE ST.id_tester=$id_tester AND S.id_servizio=ST.id_servizio";

        $services_result = $db->getAll($services_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($services_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $services_result;
    }

    /*
   * Get level of all services
   *
   * @access public
   *
   * @ return an array: id_service, id_level
   *
   * @return an error if something goes wrong
   *
    */
    public function get_service_levels()
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $service_sql = "SELECT id_servizio, livello  FROM servizio ";
        $service_result = $db->getAll($service_sql);
        if (self::isError($service_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $service_result;
    }


    /*
   * Get all services
   *
   * @access public
   *
   * @ return an array: id_service, service name, id_course , id_provider, provider name, id_departement, departement name, state
   *
   * @return an error if something goes wrong
   *
    */
    public function get_services($orderByAr = null, $clause = null)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }


        $orderByFields = '';
        if ($orderByAr != null) {
            $orderByFields = 'ORDER BY ';
            foreach ($orderByAr as $field) {
                $orderByFields .= "$field, ";
            }
            $orderByFields = substr($orderByFields, 0, strlen($orderByFields) - 2);
        }
        if ($clause == null) {
            $clause = ' AND s.livello > 1 ';
        } else {
            $clause = " AND $clause ";
        }

        /* //query in provincia table
     $service_sql = "SELECT st.id_servizio, s.nome,  s.livello, st.id_corso, st.id_tester, t.nome, st.id_provincia, p.provincia, p.stato " .
    "FROM servizio_tester AS st, provincia AS p, servizio AS s, tester AS t ".
    "WHERE  st.id_servizio = s.id_servizio AND st.id_tester = t.id_tester AND st.id_provincia = p.id_pro".
    "ORDER BY s.livello";

        */
        /*
    // query without geographical data
     $service_sql = "SELECT st.id_servizio, s.nome, s.livello, st.id_corso, st.id_tester, t.nome FROM servizio_tester AS st,  servizio AS s, tester AS t ".
    "WHERE  st.id_servizio = s.id_servizio AND st.id_tester = t.id_tester ORDER BY s.livello";
        */
        // query only  in tester table
        $service_sql = "SELECT st.id_servizio, s.nome, s.livello, st.id_corso, st.id_tester, t.nome, t.provincia, t.nazione
     FROM servizio_tester AS st,  servizio AS s, tester AS t
     WHERE  st.id_servizio = s.id_servizio AND st.id_tester = t.id_tester $clause $orderByFields";
        //echo $service_sql;
        $service_result = $db->getAll($service_sql);
        if (self::isError($service_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $service_result;
    }

    /*
   * Get implementors (= courses) for all services
   *
   * @access public
   *
   * @ return an array: id_service, id_course
   *
   * @return an error if something goes wrong
   *
    */
    public function get_service_implementors()
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $service_sql = "SELECT id_servizio, id_corso FROM servizio_tester ";
        $service_result = $db->getAll($service_sql);
        if (self::isError($service_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $service_result;
    }


    /**
     * Get service implementors (courses) for a service and a tester (optional)
     *
     * @access public
     *
     * @param $id_service the service's id
     * @param $id_tester  the tester's id (optional)
     *
     * @return an error if something goes wrong
     *
     */
    public function get_courses_for_service($id_service, $id_tester = null)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $courses_sql = "SELECT id_tester, id_corso FROM servizio_tester "
                . "WHERE id_servizio = $id_service";
        if ($id_tester != null) {
            $courses_sql .= " AND id_tester = $id_tester";
        }

        $courses_sql .= ' GROUP BY id_tester';

        $courses_result = $db->getAll($courses_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($courses_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $courses_result;
    }



    /**
     * Get informations about a service
     *
     * @access public
     *
     * @param $id_course
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_service_info_from_course($id_course)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        // FIXME:sistemare query
        $service_sql = "SELECT S.id_servizio, S.nome, S.descrizione, S.livello, S.durata_servizio, S.min_incontri, S.max_incontri, S.durata_max_incontro FROM servizio AS S, "
                . "  servizio_tester as ST "
                . "WHERE ST.id_corso = $id_course "
                . " AND S.id_servizio = ST.id_servizio";
        //. " AND ST.id_servizio = S.id_servizio";

        $service_result = $db->getRow($service_sql);
        if (self::isError($service_result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $service_result;
    }

    /**
     * Get informations about the course service type
     *
     * @access public
     *
     * @param $id_course
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_service_type_info_from_course($id_course)
    {

        $sql = "SELECT STYPE.* FROM `service_type` as STYPE, " .
               "servizio_tester as ST, servizio as S " .
               "WHERE ST.id_corso=? " .
               "AND S.id_servizio = ST.id_servizio AND S.livello = STYPE.`livello_servizio`";

        $result = $this->getRowPrepared($sql, $id_course, AMA_FETCH_ASSOC);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }

    public function add_service($service_dataAr = [])
    {

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $service_sql = 'INSERT INTO servizio(nome, descrizione, livello, durata_servizio, min_incontri, max_incontri, durata_max_incontro) VALUES(?,?,?,?,?,?,?)';
        $valuesAr = [
                $service_dataAr['service_name'],
                $service_dataAr['service_description'],
                $service_dataAr['service_level'],
                $service_dataAr['service_duration'],
                $service_dataAr['service_min_meetings'],
                $service_dataAr['service_max_meetings'],
                $service_dataAr['service_meeting_duration'],
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }

        return $db->lastInsertID();
    }


    public function delete_service($id_service)
    {
        $service_sql = 'DELETE FROM servizio WHERE id_servizio=?';
        $valuesAr = [
            $id_service,
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_REMOVE);
        }
        return true;
    }

    public function set_service($id_service, $service_dataAr = [])
    {

        $service_sql = 'UPDATE servizio SET nome=?, descrizione=?, livello=?, durata_servizio=?, min_incontri=?, max_incontri=?, durata_max_incontro=? WHERE id_servizio=?';
        $valuesAr = [
                $service_dataAr['service_name'],
                $service_dataAr['service_description'],
                $service_dataAr['service_level'],
                $service_dataAr['service_duration'],
                $service_dataAr['service_min_meetings'],
                $service_dataAr['service_max_meetings'],
                $service_dataAr['service_meeting_duration'],
                $id_service,
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_UPDATE);
        }

        return true;
    }

    public function link_service_to_course($id_tester, $id_service, $id_course)
    {
        $service_sql = 'INSERT INTO servizio_tester(id_tester, id_servizio, id_corso) VALUES(?,?,?)';
        $valuesAr = [
            $id_tester,
            $id_service,
            $id_course,
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }

        return true;
    }

    public function unlink_service_from_course($id_service, $id_course)
    {
        $sql = 'DELETE FROM servizio_tester WHERE id_servizio=? AND id_corso=?';
        $valuesAr = [
            $id_service,
            $id_course,
        ];

        $result = $this->queryPrepared($sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_REMOVE);
        }
        return true;
    }


    /**
     * giorgio 13/ago/2013
     * added id_tester parameter that is passed if it's not a multiprovider environment
     */
    public function get_published_courses($id_tester = null)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $courses_sql = 'SELECT S.id_servizio, S.nome, S.descrizione, S.durata_servizio FROM servizio AS S ' .
                'JOIN `service_type` AS STYPE ON STYPE.`livello_servizio`=S.`livello` AND STYPE.`hiddenFromInfo`!=1 ' .
                'JOIN `servizio_tester` AS ST ON ST.`id_servizio`=S.`id_servizio`';
        if (!is_null($id_tester) && intval($id_tester) > 0) {
            $courses_sql .= ' WHERE id_tester=' . intval($id_tester);
        }

        $result = $db->getAll($courses_sql, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }




    /**
     * Get users' list of a given type
     *
     * @access public
     *
     * @param $user_type
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function get_users_by_type($user_type = [], $retrieve_extended_data = false)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $type = implode(',', $user_type);
        if ($retrieve_extended_data) {
            $sql = "SELECT nome, cognome, tipo, username FROM utente WHERE tipo IN ($type)";
        } else {
            $sql = "SELECT tipo, username FROM utente WHERE tipo IN ($type)";
        }

        $result = $db->getAll($sql, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }

    public function get_number_of_users_with_status($user_idsAr = [], $status = -1)
    {
        if (count($user_idsAr) == 0) {
            return 0;
        }
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $user_ids = implode(',', $user_idsAr);
        $sql = 'SELECT count(id_utente) FROM utente WHERE id_utente IN(' . $user_ids . ')
    		AND stato=' . $status;
        $result = $db->getOne($sql);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }
    /**
     * Methods accessing table `token`
     */
    // MARK: Methods accessing table `token`

    /**
     *
     * @param  $token_dataAr an associative array with the following key set:
     *  'token'
     *  'user_id'
     *  'request_timestamp'
     *  'expiration_timestamp'
     *  'action'
     *  'valid'
     * @return unknown_type
     */
    public function add_token($token_dataAr = [])
    {
        $token_sql = 'INSERT INTO token(token, id_utente, timestamp_richiesta, azione, valido) VALUES(?,?,?,?,?)';
        $valuesAr = [
                $token_dataAr['token'],
                $token_dataAr['id_utente'],
                $token_dataAr['timestamp_richiesta'],
                $token_dataAr['azione'],
                $token_dataAr['valido'],
        ];

        $result = $this->queryPrepared($token_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }

        return true;
    }

    /**
     *
     * @param $token
     * @param $user_id
     * @param $action
     * @return unknown_type
     */
    public function get_token($token, $user_id, $action)
    {
        $db  = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql = "SELECT token, id_utente, timestamp_richiesta, azione, valido FROM token WHERE token='$token' AND id_utente=$user_id AND azione=$action";

        $result = $db->getRow($sql, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($result) || !is_array($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }

    public function update_token($token_dataAr = [])
    {
        $db  = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $valido = $token_dataAr['valido'];
        $token  = $token_dataAr['token'];

        $sql = "UPDATE token SET valido=$valido WHERE token='$token'";

        $result = $db->query($sql);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * Methods accessing table `messaggi`
     *
     * @see MessageDataHandler.inc.php
     */
    // MARK: Methods accessing table `messaggi`

    /**
     * Methods accessing table `messaggi_sistema`
     */
    // MARK: Methods accessing table `messaggi_sistema`

    /**
     * function find_message_translation
     *
     * @param string $message_text  - ADA system message string to be translated
     * @param string $language_code - ISO 639-1 language code (e.g. 'it' for 'italian')
     * @return mixed - An AMA_Error object if something went wrong or a string.
     */
    public function find_message_translation($message_text, $language_code)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        $table_name = $this->get_translation_table_name_for_language_code($language_code);

        if (AMA_DB::isError($table_name)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        $sql_message = $this->sql_prepared($message_text);
        /*
     * Check if the given message is already in table messaggi_sistema
        */
        $sql_message_id = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=$sql_message";

        $result = $db->getRow($sql_message_id);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        /*
     * If the given message is not in table messaggi_sistema, add it.
        */
        if ($result ==  null) {
            $insert = $this->add_translation_message($sql_message);
            if (AMA_DB::isError($insert)) {
                return new AMA_Error(AMA_ERR_ADD);
            }
            /*
       * For this message there aren't translations at this moment, so return the original message
            */
            return $message_text;
        }
        /*
     * If the message was in table messaggi_sistema, search for a message translation in the given
     * user language
        */

        $message_id = $result[0];
        $result = $this->select_message_text($table_name, $message_id);

        /*
     * If a translation in the given language is not found, return the original message
        */
        if (AMA_DB::isError($result) or $result ==  null) {
            return $message_text;
        }

        /*
     * If a messagge translation is found with an empty string, return the original message
        */
        // vito, 2 marzo 2009
        //      $translated_message = $result[0];
        $translated_message = $result['testo_messaggio'];
        if (empty($translated_message)) {
            return $message_text;
        }

        return $translated_message;
    }

    /**
     * function select_message_text by ID
     * @param $table_name
     * @param $message_id
     * @return unknown_type
     */
    public function select_message_text($table_name, $message_id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql_translated_message = "SELECT testo_messaggio FROM $table_name WHERE id_messaggio=$message_id";
        $result = $db->getRow($sql_translated_message, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        return $result;
    }


    /**
     * function find_translation_for_message
     *
     * @param string  $message_text
     * @param string  $language_code
     * @param integer $limit_results_number_to
     * @return mixed - An AMA_Error object if there were errors, an array of string otherwise
     */
    public function find_translation_for_message($message_text, $language_code, $limit_results_number_to)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $last_char = strlen($message_text);
        /*
         * Check if the user has specified an exact query (e.g. '"some text"')
         */
        if ($message_text[0] == '"' && $message_text[$last_char - 1] == '"') {
            $sql_prepared_text = $this->sql_prepared(trim($message_text, '"'));
            $sql_for_where     = "testo_messaggio=$sql_prepared_text";
        } elseif ($message_text[1] == '"' && $message_text[$last_char] == '"') {
            $sql_prepared_text = $this->sql_prepared(trim($message_text, '\"'));
            $sql_for_where     = "testo_messaggio=$sql_prepared_text";
        } else {
            /*
             * The user entered some search tokens (e.g. 'some text')
             */
            $sql_for_where = "";
            $token = strtok($message_text, ' ');
            $sql_prepared_text = $this->sql_prepared("%$token%");
            $sql_for_where .= "testo_messaggio LIKE $sql_prepared_text ";
            while (($token = strtok(' ')) !== false) {
                $sql_prepared_text = $this->sql_prepared("%$token%");
                $sql_for_where .= "AND testo_messaggio LIKE $sql_prepared_text ";
            }
            if ($limit_results_number_to != null || $limit_results_number_to != "") {
                $sql_for_where .= " LIMIT $limit_results_number_to";
            }
        }

        $table_name = $this->get_translation_table_name_for_language_code($language_code);
        if (AMA_DB::isError($table_name)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        $sql_translated_message = "SELECT id_messaggio,testo_messaggio
                                   FROM $table_name
                                  WHERE $sql_for_where";

        $result = $db->getAll($sql_translated_message, null, AMA_FETCH_ASSOC);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        return $result;
    }


    /**
     * function get_all_system_messages: used to obtain all the messages stored
     * in table 'messaggi_sistema'.
     *
     * @return mixed - An AMA_Error object if there were errors, an array of strings otherwise.
     */
    public function get_all_system_messages()
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql_get_messages = "SELECT testo_messaggio FROM messaggi_sistema";
        $result = $db->getAll($sql_get_messages, null, AMA_FETCH_ASSOC);

        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * function add_translation_message: used to add a message into table messaggi_sistema
     * and into the translation related tables (messaggi_it, messaggi_en, ...) to ensure that,
     * given a message, its id points to the corresponding translated message in all of the
     * translation related tables.
     *
     * @access private
     * @param  string $sql_prepared_message - a message already prepared by calling $this->sql_prepare
     * @return true if the message was successfully inserted, an AMA_DB error otherwise
     */
    private function add_translation_message($sql_prepared_message)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        /**
         * Insert this message in table messaggi_sistema
         */
        $sql_insert_message    = "INSERT INTO messaggi_sistema(testo_messaggio) VALUES($sql_prepared_message)";

        $result = $db->query($sql_insert_message);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_ADD);
        }

        /**
         * Get tablename suffixes for each language supported in the user interface message
         * translation and use each suffix to construct the table name to use for message insertion
         */
        $sql_select_translation_tables_suffixes = "SELECT identificatore_tabella FROM lingue";
        $suffixes = $db->getCol($sql_select_translation_tables_suffixes);
        if (AMA_DB::isError($suffixes)) {
            return new AMA_Error(AMA_ERR_GET);
        }
        // ottenere l'id per il messaggio appena inserito

        $sql_id_message    = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=$sql_prepared_message";
        $id_message = $db->getOne($sql_id_message);
        if (AMA_DB::isError($id_message)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        foreach ($suffixes as $table_suffix) {
            $table_name = 'messaggi_' . $table_suffix;
            /**
             * Insert the messagge in the translation table named $table_name
             */
            $sql_insert_message_in_translation_table = "INSERT INTO $table_name(id_messaggio,testo_messaggio) VALUES($id_message,$sql_prepared_message)";
            $result = $db->query($sql_insert_message_in_translation_table);
            /**
             * If an error occurs while adding the message into this table, then add an empty string, since
             * we don't want to loose identifier one-to-one mapping between this table and table messaggi_sistema
             */
            if (AMA_DB::isError($result)) {
                ADALogger::log_db("Error encountered while adding message $sql_prepared_message into table $table_name");
                $sql_insert_in_case_of_error = "INSERT INTO $table_name(testo_messaggio) VALUES('')";
                $result = $db->query($sql_insert_in_case_of_error);
            }
        }

        return true;
    }

    /*
   * vito, 20 ottobre 2008: necessaria ad aggiornare il testo di un messaggio di
   * sistema per un dato language code
    */
    /**
     * function update_message_translation_for_language_code
     *
     * @param integer $message_id    - the identifier of the message in table 'messaggi_sistema'
     * @param string  $message_text  - the text for the translated message
     * @param string  $language_code - the language code that identifies the translation
     * @return mixed  - An AMA_Error object if there were errors, true otherwise
     */
    public function update_message_translation_for_language_code($message_id, $message_text, $language_code)
    {

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $table_name = $this->get_translation_table_name_for_language_code($language_code);
        if (AMA_DB::isError($table_name)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        $sql_prepared_message_text = $this->sql_prepared($message_text);
        $sql_update_message_text = "UPDATE $table_name SET testo_messaggio=$sql_prepared_message_text WHERE id_messaggio=$message_id";
        $result = $db->query($sql_update_message_text);

        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * function update_message_translation_for_language_code_given_this_text
     *
     * @param string $message_text     - the existing string in the translation
     * @param string $new_message_text - the new string
     * @param string $language_code    - ISO 639-1 code which identifies the translation
     * @return mixed - AMA_Error object if there were errors, the number of affected rows otherwise
     */
    public function update_message_translation_for_language_code_given_this_text($message_text, $new_message_text, $language_code)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $table_name = $this->get_translation_table_name_for_language_code($language_code);
        if (AMA_DB::isError($table_name)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        $sql_prepared_message_text = $this->sql_prepared($message_text);
        $sql_prepared_new_message_text = $this->sql_prepared($new_message_text);
        /*
     * Check if the given message is already in table messaggi_sistema
        */
        $sql_message_id = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=$sql_prepared_message_text";

        $result = $db->getRow($sql_message_id);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        $message_id = $result[0];
        // FIXME: verificare il valore restituito se il messaggio dato non esiste nella tabella.

        $sql_update_message_text = "UPDATE $table_name SET testo_messaggio=$sql_prepared_new_message_text WHERE id_messaggio=$message_id";

        $result = $this->executeCritical($sql_update_message_text);

        if (AMA_DB::isError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * function add_translated_message: used to add a message translate into translation
     * tables (messaggi_it, messaggi_en, ...)
     *
     * @access public
     * @param  string $sql_prepared_message - a message already prepared by calling $this->sql_prepare
     * @return true if the message was successfully inserted, an AMA_DB error otherwise
     */
    public function add_translated_message($sql_prepared_message, $id, $suffix)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $table_name = 'messaggi_' . $suffix;
        /**
         * Insert the messagge in the translation table named $table_name
         */
        $sql_insert_message_in_translation_table = "INSERT INTO $table_name(id_messaggio, testo_messaggio) VALUES($id, $sql_prepared_message)";
        $result = $db->query($sql_insert_message_in_translation_table);
        /**
         * If an error occurs while adding the message into this table, then add an empty string, since
         * we don't want to loose identifier one-to-one mapping between this table and table messaggi_sistema
         */
        if (AMA_DB::isError($result)) {
            ADALogger::log_db("Error encountered while adding message $sql_prepared_message into table $table_name");
            return $result;
        }

        return true;
    }

    /**
     * function delete_all_messages: used to delete all messages from translation
     * tables (messaggi_it, messaggi_en, ...) and from system messagges (messaggi_sistema)
     *
     * @access public
     * @param  string $suffix - (it, is, es)
     * @return true if the table was emptied, an AMA_DB error otherwise
     */
    public function delete_all_messages($suffix)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $table_name = 'messaggi_' . $suffix;
        /**
         * delete messagges from the translation table named $table_name
         */
        $sql_delete_messages_from_translation_table = "delete from $table_name";
        $result = $db->query($sql_delete_messages_from_translation_table);
        /**
         * If an error occurs while deleting all the messages from this table
         */
        if (AMA_DB::isError($result)) {
            ADALogger::log_db("Error encountered while deleting messages from table $table_name");
            return $result;
        }
        $sql = "ALTER TABLE $table_name AUTO_INCREMENT = 0";
        $result = $db->query($sql);
        if (AMA_DB::isError($result)) {
            ADALogger::log_db("Error encountered while deleting messages from table $table_name");
            return $result;
        }


        return true;
    }
    /**
     * Methods accessing table `lingue`
     */
    // MARK: Methods accessing table `lingue`
    /**
     * function find_languages: used to get the language names for all of the language
     * supported in the user interface message translation
     *
     * @return mixed - An AMA_Error object if there were errors, an array of string otherwise
     */
    public function find_languages()
    {

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql_select_languages = "SELECT id_lingua,nome_lingua,codice_lingua FROM lingue";
        $result = $db->getAll($sql_select_languages, null, AMA_FETCH_ASSOC);

        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * return the identificatore_tabella of language given a language id
     * added on 17/lug/2013 for course exporting, feel free to use it!
     *
     * @author giorgio
     *
     */
    public function find_language_table_identifier_by_langauge_id($language_id)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql_select_languages = "SELECT identificatore_tabella FROM lingue WHERE id_lingua=" . $language_id;
        $result = $db->getOne($sql_select_languages, null, AMA_FETCH_ASSOC);

        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * return the id_lingua of language given a identificatore_tabella
     * added on 17/lug/2013 for course exporting, feel free to use it!
     *
     * @author giorgio
     *
     */
    public function find_language_id_by_langauge_table_identifier($table_identifier)
    {
        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }

        $sql_select_languages = "SELECT id_lingua FROM lingue WHERE identificatore_tabella='" . $table_identifier . "'";
        $result = $db->getOne($sql_select_languages, null, AMA_FETCH_ASSOC);

        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMA_Error(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * function get_translation_table_name_for_language_code(): used to obtain the name of
     * the table in which to store a translated message given the language code for the translation.
     *
     * @param  string $language_code - the ISO 639-1 language code (e.g. 'it' for 'italian')
     * @return string $table_name    - a string containing the table name for the given language code
     */
    private function get_translation_table_name_for_language_code($language_code)
    {

        $db = & $this->getConnection();
        if (AMA_DB::isError($db)) {
            return $db;
        }
        // = AMA_DB_MDB2_wrapper
        $translation_tables_default_prefix = 'messaggi_';

        $sql_translation_table_suffix_for_language_code = "SELECT identificatore_tabella FROM lingue WHERE codice_lingua='$language_code'";

        $result = $db->getRow($sql_translation_table_suffix_for_language_code);
        if (AMA_DB::isError($result)) {
            return new AMA_Error(AMA_ERR_GET);
        }

        /*
     * build table name
        */

        $table_suffix = $result[0];
        $table_name = $translation_tables_default_prefix . $table_suffix;

        return $table_name;
    }

    /**
     * In a non multiprovider environment, returns the tester pointer associated to the
     * passed 3rd level domain (i.e. an internet domain name up until the first dot)
     * If the 3rd level is not found or the column in the tester table does not exists,
     * will return $thirdleveldomain
     *
     * @param string $thirdleveldomain
     * @return string|null
     */
    public function getPointerFromThirdLevel($thirdleveldomain = null)
    {
        if (!MULTIPROVIDER && strlen($thirdleveldomain) > 0) {
            $query = "SELECT `puntatore` FROM `tester` WHERE `3rdleveldomain`=?";
            $res = $this->getOnePrepared($query, [ $thirdleveldomain ]);
            if ($res !== false && !AMA_DB::isError($res) && strlen($res) > 0) {
                return $res;
            }
        }
        return $thirdleveldomain;
    }

    /**
     * (non-PHPdoc)
     * @see include/Abstract_AMA_DataHandler#__destruct()
     */
    public function __destruct()
    {
        parent::__destruct();
    }
}

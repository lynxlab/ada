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

use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Logger\ADALogger;
use Lynxlab\ADA\Main\Traits\ADASingleton;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;

class AMACommonDataHandler extends AbstractAMADataHandler
{
    use ADASingleton;

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
     * @return self an instance of AMA_Common_DataHandler
     */
    public static function instance($dsn = null)
    {

        //ADALogger::logDb('AMA_Common_DataHandler: get instance for main db connection');
        $callerClassName = static::class;
        if (!is_null(self::$instance) && self::$instance::class !== $callerClassName) {
            self::$instance = null;
        }

        if (self::$instance == null) {
            //ADALogger::logDb('AMA_Common_DataHandler: creating a new instance of AMA_Common_DataHandler');
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
    public function checkIdentity($username, $password)
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
            $testerAr = $this->getTesterInfoFromPointer($GLOBALS['user_provider']);
            $sql .= ',utente_tester UT WHERE ' .
                'U.id_utente = UT.id_utente AND U.username=? AND U.password=? AND UT.id_tester=?';
            array_push($sql_params, $testerAr[0]);
        } else {
            $sql .= 'WHERE U.username=? AND U.password=?';
        }

        $resultHa = $this->getRowPrepared($sql, $sql_params, AMA_FETCH_ASSOC);

        if (!is_array($resultHa) || empty($resultHa)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        return $resultHa;
    }

    /**
     *
     * @param $user_dataAr
     * @return unknown_type
     */
    public function addUser($user_dataAr = [], $mustcheck = true)
    {

        /*
         * Before inserting a row, check if a user with this username already exists
         */
        if ($mustcheck) {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
            $user_id = $this->getOnePrepared($user_id_sql, [$user_dataAr['username']]);
            if (AMADB::isError($user_id)) {
                return $user_id;
            } elseif ($user_id) {
                return new AMAError(AMA_ERR_UNIQUE_KEY);
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
            AMACommonDataHandler::dateToTs($user_dataAr['birthdate']),
            $user_dataAr['sesso'],
            $user_dataAr['telefono'],
            //                $this->orNull($user_dataAr['indirizzo']),
            //                $this->orNull($user_dataAr['citta']),
            //                $this->orNull($user_dataAr['provincia']),
            //                $this->orNull($user_dataAr['nazione']),
            //                $this->orNull($user_dataAr['codice_fiscale']),
            //                $this->orZero($user_dataAr['birthdate']),
            //                $this->orNull($user_dataAr['sesso']),
            //                $this->orNull($user_dataAr['telefono']),
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
        if (AMADB::isError($result)) {
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
            $user_id = $this->lastInsertID();
        } else {
            $user_id = $this->findUserFromUsername($user_dataAr['username']);
        }

        /*
    $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
    $user_id = $this->getOnePrepared($user_id_sql, $user_dataAr['username']);
    if (AMADB::isError($user_id)) {
      return new AMAError(AMA_ERR_GET);
    }
        */
        return $user_id;
    }

    /**
     * Return the user id of the user with username = $username
     *
     * @param string $username
     * @return AMAError|number
     */
    public function findUserFromUsername($username)
    {
        /**
         * @author giorgio 05/mag/2014 15:44:34
         *
         * if not in a multiprovider environment, must
         * match the user in the selected provider
         *
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $testerAr = $this->getTesterInfoFromPointer($GLOBALS['user_provider']);
            $user_id_sql = 'SELECT U.id_utente FROM utente U, utente_tester UT WHERE ' .
                'U.id_utente = UT.id_utente AND id_tester=? AND username=?';
            $sql_params =  [$testerAr[0], $username];
        } else {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
            $sql_params = $username;
        }
        $user_id = $this->getOnePrepared($user_id_sql, $sql_params);
        if (AMADB::isError($user_id) || $user_id == null) {
            return new AMAError(AMA_ERR_GET);
        }

        return $user_id;
    }

    /**
     * Return the user id of the user with email = $e_mail
     *
     * @param string $email
     * @return AMAError|number
     */
    public function findUserFromEmail($email)
    {
        /**
         * @author giorgio 05/mag/2014 16:29:39
         *
         * if not in a multiprovider environment, must
         * match the user in the selected provider
         */
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $testerAr = $this->getTesterInfoFromPointer($GLOBALS['user_provider']);
            $user_id_sql = 'SELECT U.id_utente FROM utente U, utente_tester UT WHERE ' .
                'U.id_utente = UT.id_utente AND id_tester=? AND e_mail=?';
            $sql_params =  [$email, $testerAr[0]];
        } else {
            $user_id_sql = 'SELECT id_utente FROM utente WHERE e_mail=?';
            $sql_params = $email;
        }
        $user_id = $this->getOnePrepared($user_id_sql, $sql_params);
        if (AMADB::isError($user_id) || $user_id == null) {
            return new AMAError(AMA_ERR_GET);
        }

        return $user_id;
    }
    /**
     *
     * @param $user_dataAr
     * @return unknown_type
     */
    public function addUserToTester($user_id, $tester_id)
    {
        $sql = 'INSERT INTO utente_tester VALUES (?, ?)';
        $result = $this->executeCriticalPrepared($sql, [$user_id, $tester_id]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
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


    public function getUserPwd($id)
    {
        // get a row from table UTENTE
        $query = "select password from utente where id_utente=?";
        $res_ar =  $this->getOnePrepared($query, [$id], AMA_FETCH_ASSOC);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function getUserInfo($id)
    {
        // get a row from table UTENTE
        $query = "select nome, cognome, tipo, e_mail AS email, telefono, username, layout, " .
            "indirizzo, citta, provincia, nazione, codice_fiscale, birthdate, sesso, " .
            "telefono, stato, lingua, timezone, cap, matricola, avatar, birthcity, birthprovince from utente where id_utente=?";
        $res_ar =  $this->getRowPrepared($query, [$id], AMA_FETCH_ASSOC);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $res_ar['id'] = $id;
        if (isset($res_ar['birthdate'])) {
            $res_ar['birthdate'] = Utilities::ts2dFN($res_ar['birthdate']);
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
    private function getStudentLevel($id_user, $id_course_instance)
    {
        if (empty($id_course_instance)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // get a row from table iscrizioni
        // FIXME: usare getOne al posto di getRow
        $res_ar =  $this->getRowPrepared(
            "select livello from iscrizioni where id_utente_studente=? and  id_istanza_corso=?",
            [$id_user, $id_course_instance]
        );
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function getUserType($id)
    {
        $result =  $this->getOnePrepared("select tipo from utente where id_utente=?", [$id]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($result)) { //OR is_object($res_ar)){
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function getUserStatus($id)
    {
        $query = "select stato from utente where id_utente=?";
        $result =  $this->getOnePrepared($query, [$id]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($result)) { //OR is_object($res_ar)){
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function getAuthor($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->getUserInfo($id);
        if (AMACommonDataHandler::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            return $get_user_result;
        }

        // get a row from table AUTORE
        // FIXME: dobbiamo sapere a quale tester e' associato per ottenere il  suo profilo.

        //    $get_author_sql = "SELECT tariffa, profilo FROM autore WHERE id_utente_autore=$id";
        //    $get_author_result = $db->getRow($get_author_sql, NULL, AMA_FETCH_ASSOC);
        //    if (AMADB::isError($get_author_result)) {
        //      return new AMAError(AMA_ERR_GET);
        //    }
        //    if(!$get_author_result) {
        /* inconsistency found! a message should be logged */
        //      return new AMAError(AMA_ERR_INCONSISTENT_DATA);
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
    public function getStudent($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->getUserInfo($id);
        if (AMACommonDataHandler::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            return $get_user_result;
        }
        // get_student($id) originally did not return the user id as a result,
        unset($get_user_result['id']);

        return $get_user_result;
    }

    public function getUser($id)
    {
        return $this->getStudent($id);
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
     *        an AMAError object on failure
     *
     */
    public function getTutor($id)
    {

        // get a row from table UTENTE
        $get_user_result = $this->getUserInfo($id);
        if (AMACommonDataHandler::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            return $get_user_result;
        }
        // get_tutor($id) originally did not return the user id as a result,
        unset($get_user_result['id']);

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
    public function setUser($id, $user_ha)
    {
        // verify that the record exists and store old values for rollback
        $user_id_sql =  'SELECT id_utente FROM utente WHERE id_utente=?';
        $user_id = $this->getOnePrepared($user_id_sql, [$id]);
        if (AMADB::isError($user_id)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (is_null($user_id)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // backup old values
        $old_values_ha = $this->getUser($id);

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
        //      if(AMADB::isError($result)) {
        //        return new AMAError(AMA_ERR_GET);
        //      }
        //      if($result) {
        //        return new AMAError(AMA_ERR_UNIQUE_KEY);
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
                AMACommonDataHandler::dateToTs($user_ha['birthdate']),
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
                AMACommonDataHandler::dateToTs($user_ha['birthdate']),
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
        if (ModuleLoaderHelper::isLoaded('GDPR') === true && array_key_exists('username', $user_ha) && strlen($user_ha['username']) > 0 && $user_ha['username'] !== $old_values_ha['username']) {
            $update_user_sql .= ',username=?';
            $valuesAr[] = $user_ha['username'];
        }

        $update_user_sql .= $where;
        $valuesAr[] = $id;

        $result = $this->queryPrepared($update_user_sql, $valuesAr);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
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
     * @return int|AMAError if something goes wrong, new status on success
     *
     */
    public function setUserStatus($userid, $status)
    {
        $usertype = $this->getUserType($userid);
        if ((is_numeric($status)) and ($usertype == AMA_TYPE_STUDENT)) {
            $userObj = MultiPort::findUser($userid);
            if ($userObj instanceof ADALoggableUser) {
                $userObj->setStatus($status);
                $result = MultiPort::setUser($userObj, [], true);
                if (static::isError($result)) {
                    return $result;
                } else {
                    $new_status = $this->getUserStatus($userid);
                    return $new_status;
                }
            } else {
                return new AMAError(AMA_ERR_NOT_FOUND);
            }
        } else {
            return new AMAError(AMA_ERR_UPDATE);
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
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function findUsersList($field_list_ar, $clause = '', $usertype = AMA_TYPE_STUDENT, $order = 'cognome')
    {
        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }

        // handle null clause, too
        if ($clause) {
            $clause = ' where ' . $clause;
        }

        if ($clause == '') {
            $query = "select id_utente$more_fields from utente where tipo=?" . " order by $order";
        } else {
            $query = "SELECT id_utente$more_fields from utente $clause and tipo=?" . "  order by $order";
        }

        // do the query
        $users_ar =  $this->getAllPrepared($query, [$usertype]);
        if (AMADB::isError($users_ar)) {
            return new AMAError(AMA_ERR_GET);
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


    public function getTestersForUser($id_user)
    {
        $testers_sql = "SELECT T.puntatore FROM utente_tester AS U, tester AS T "
            . "WHERE U.id_utente = ? AND T.id_tester = U.id_tester";

        $testers_result = $this->getColPrepared($testers_sql, [$id_user]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function getTestersForUsername($username)
    {
        $testers_sql = "SELECT T.puntatore FROM utente AS U, utente_tester AS UT, tester AS T "
            . "WHERE U.username = ? AND UT.id_utente= U.id_utente AND T.id_tester = UT.id_tester";

        $testers_result = $this->getColPrepared($testers_sql, [$username]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function getAllTesters($field_data_Ar = [])
    {
        if (!empty($field_data_Ar)) {
            $fields = implode(', ', $field_data_Ar);
            $fields .= ', ';
        } else {
            $fields = '';
        }

        $testers_sql = 'SELECT ' . $fields . ' puntatore FROM tester WHERE 1';
        $testers_result = $this->getAllPrepared($testers_sql, null, AMA_FETCH_ASSOC);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function getTesterInfoFromId($id_tester, $fetchmode = null)
    {
        $testers_sql = "SELECT id_tester,nome,ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore,descrizione,iban FROM tester "
            . "WHERE id_tester = ?";

        $testers_result = $this->getRowPrepared($testers_sql, $id_tester, $fetchmode);
        if (false === $testers_result || self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function getTesterInfoFromIdCourse($id_course)
    {
        $tester_sql = "SELECT T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.citta,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore,T.descrizione,T.iban "
            . "FROM tester AS T, servizio_tester AS ST WHERE ST.id_corso= ? AND T.id_tester=ST.id_tester";

        $tester_resultAr = $this->getRowPrepared($tester_sql, [$id_course], AMA_FETCH_ASSOC);
        if (self::isError($tester_resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (is_null($tester_resultAr)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        return $tester_resultAr;
    }

    public function getTesterInfoFromService($id_service)
    {
        $tester_sql = "SELECT T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore,T.descrizione,T.iban "
            . "FROM tester AS T, servizio_tester AS ST WHERE ST.id_servizio= ? AND T.id_tester=ST.id_tester";

        $tester_resultAr = $this->getRowPrepared($tester_sql, [$id_service], AMA_FETCH_ASSOC);
        if (self::isError($tester_resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (is_null($tester_resultAr)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        return $tester_resultAr;
    }


    public function getTesterInfoFromPointer($tester)
    {
        $testers_sql = "SELECT id_tester,nome,ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore,descrizione,iban FROM tester "
            . "WHERE puntatore = ?";

        $testers_result = $this->getRowPrepared($testers_sql, [$tester]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $testers_result;
    }

    public function addTester($tester_dataAr = [])
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
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
        }

        return $this->lastInsertID();
    }

    public function setTester($tester_id, $tester_dataAr = [])
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
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
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
    public function getTesterForService($id_service)
    {
        $testers_sql = "SELECT id_tester FROM servizio_tester "
            . "WHERE id_servizio = ?";

        $testers_result = $this->getColPrepared($testers_sql, [$id_service]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServicesForTester($id_tester)
    {
        $testers_sql = "SELECT id_servizio FROM servizio_tester "
            . "WHERE id_tester = ?";

        $testers_result = $this->getColPrepared($testers_sql, [$id_tester]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServicesTesterInfo($id_tester = [])
    {
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
            $sql .= " WHERE t.`id_tester` IN (" . join(',', array_fill(0, count($id_tester), '?')) . ")";
        }
        $sql .= " ORDER BY t.`nome` ASC, s.`nome` ASC";

        $res = $this->getAllPrepared($sql, $id_tester, AMA_FETCH_ASSOC);
        if (self::isError($res)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getCoursesForTester($id_tester)
    {
        $testers_sql = "SELECT id_corso FROM servizio_tester "
            . "WHERE id_tester = ?";

        $testers_result = $this->getColPrepared($testers_sql, [$id_tester]);
        if (self::isError($testers_result)) {
            return new AMAError(AMA_ERR_GET);
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
     * @return AMAError | integer
     * @access public
     */
    public function getCourseMaxId()
    {
        $sql = "SELECT MAX(id_corso) FROM servizio_tester";
        $max_id = $this->getOnePrepared($sql);

        if (AMADB::isError($max_id)) {
            $retval = new AMAError(AMA_ERR_GET);
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
    public function getServiceInfo($id_servizio)
    {
        $service_sql = "SELECT id_servizio, nome, descrizione, livello, durata_servizio, min_incontri, max_incontri, durata_max_incontro  FROM servizio "
            . "WHERE id_servizio = ?";

        $service_result = $this->getRowPrepared($service_sql, [$id_servizio]);
        if (self::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getInfoForTesterServices($id_tester)
    {
        $services_sql = "SELECT S.id_servizio, S.nome, S.descrizione, S.livello, S.durata_servizio, S.min_incontri, S.max_incontri, S.durata_max_incontro,"
            . " ST.id_corso FROM servizio AS S, servizio_tester AS ST WHERE ST.id_tester=? AND S.id_servizio=ST.id_servizio";

        $services_result = $this->getAllPrepared($services_sql, [$id_tester], AMA_FETCH_ASSOC);
        if (self::isError($services_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServiceLevels()
    {
        $service_sql = "SELECT id_servizio, livello  FROM servizio ";
        $service_result = $this->getAllPrepared($service_sql);
        if (self::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServices($orderByAr = null, $clause = null)
    {
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
        $service_result = $this->getAllPrepared($service_sql);
        if (self::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServiceImplementors()
    {
        $service_sql = "SELECT id_servizio, id_corso FROM servizio_tester ";
        $service_result = $this->getAllPrepared($service_sql);
        if (self::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getCoursesForService($id_service, $id_tester = null)
    {
        $params = [
            $id_service,
        ];

        $courses_sql = "SELECT id_tester, id_corso FROM servizio_tester "
            . "WHERE id_servizio = ?";
        if ($id_tester != null) {
            $courses_sql .= " AND id_tester = ?";
            $params[] = $id_tester;
        }

        $courses_sql .= ' GROUP BY id_tester';

        $courses_result = $this->getAllPrepared($courses_sql, $params, AMA_FETCH_ASSOC);
        if (self::isError($courses_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServiceInfoFromCourse($id_course)
    {
        // FIXME:sistemare query
        $service_sql = "SELECT S.id_servizio, S.nome, S.descrizione, S.livello, S.durata_servizio, S.min_incontri, S.max_incontri, S.durata_max_incontro FROM servizio AS S, "
            . "  servizio_tester as ST "
            . "WHERE ST.id_corso = ? "
            . " AND S.id_servizio = ST.id_servizio";
        //. " AND ST.id_servizio = S.id_servizio";

        $service_result = $this->getRowPrepared($service_sql, [$id_course]);
        if (self::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getServiceTypeInfoFromCourse($id_course)
    {

        $sql = "SELECT STYPE.* FROM `service_type` as STYPE, " .
            "servizio_tester as ST, servizio as S " .
            "WHERE ST.id_corso=? " .
            "AND S.id_servizio = ST.id_servizio AND S.livello = STYPE.`livello_servizio`";

        $result = $this->getRowPrepared($sql, $id_course, AMA_FETCH_ASSOC);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function addService($service_dataAr = [])
    {
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
            return new AMAError(AMA_ERR_ADD);
        }

        return $this->lastInsertID();
    }


    public function deleteService($id_service)
    {
        $service_sql = 'DELETE FROM servizio WHERE id_servizio=?';
        $valuesAr = [
            $id_service,
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }
        return true;
    }

    public function setService($id_service, $service_dataAr = [])
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
            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
    }

    public function linkServiceToCourse($id_tester, $id_service, $id_course)
    {
        $service_sql = 'INSERT INTO servizio_tester(id_tester, id_servizio, id_corso) VALUES(?,?,?)';
        $valuesAr = [
            $id_tester,
            $id_service,
            $id_course,
        ];

        $result = $this->queryPrepared($service_sql, $valuesAr);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
        }

        return true;
    }

    public function unlinkServiceFromCourse($id_service, $id_course)
    {
        $sql = 'DELETE FROM servizio_tester WHERE id_servizio=? AND id_corso=?';
        $valuesAr = [
            $id_service,
            $id_course,
        ];

        $result = $this->queryPrepared($sql, $valuesAr);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }
        return true;
    }


    /**
     * giorgio 13/ago/2013
     * added id_tester parameter that is passed if it's not a multiprovider environment
     */
    public function getPublishedCourses($id_tester = null)
    {
        $params = [];

        $courses_sql = 'SELECT S.id_servizio, S.nome, S.descrizione, S.durata_servizio FROM servizio AS S ' .
            'JOIN `service_type` AS STYPE ON STYPE.`livello_servizio`=S.`livello` AND STYPE.`hiddenFromInfo`!=1 ' .
            'JOIN `servizio_tester` AS ST ON ST.`id_servizio`=S.`id_servizio`';
        if (!is_null($id_tester) && intval($id_tester) > 0) {
            $courses_sql .= ' WHERE id_tester= ?';
            $params[] = intval($id_tester);
        }

        $result = $this->getAllPrepared($courses_sql, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function getUsersByType($user_type = [], $retrieve_extended_data = false)
    {
        $type = join(',', array_fill(0, count($user_type), '?'));
        if ($retrieve_extended_data) {
            $sql = "SELECT nome, cognome, tipo, username FROM utente WHERE tipo IN ($type)";
        } else {
            $sql = "SELECT tipo, username FROM utente WHERE tipo IN ($type)";
        }

        $result = $this->getAllPrepared($sql, $user_type, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getNumberOfUsersWithStatus($user_idsAr = [], $status = -1)
    {
        if (count($user_idsAr) == 0) {
            return 0;
        }
        $user_ids = join(',', array_fill(0, count($user_idsAr), '?'));
        $sql = 'SELECT count(id_utente) FROM utente WHERE id_utente IN(' . $user_ids . ')
    		AND stato= ?';
        $result = $this->getOnePrepared($sql, array_merge($user_ids, [$status]));
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
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
    public function addToken($token_dataAr = [])
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
            return new AMAError(AMA_ERR_ADD);
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
    public function getToken($token, $user_id, $action)
    {
        $sql = "SELECT token, id_utente, timestamp_richiesta, azione, valido FROM token WHERE token=? AND id_utente=? AND azione=?";

        $result = $this->getRowPrepared($sql, [$token, $user_id, $action], AMA_FETCH_ASSOC);
        if (AMADB::isError($result) || !is_array($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function updateToken($token_dataAr = [])
    {
        $valido = $token_dataAr['valido'];
        $token  = $token_dataAr['token'];

        $sql = "UPDATE token SET valido=? WHERE token=?";

        $result = $this->queryPrepared($sql, [$valido, $token]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
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
     * @return mixed - An AMAError object if something went wrong or a string.
     */
    public function findMessageTranslation($message_text, $language_code)
    {
        $table_name = $this->getTranslationTableNameForLanguageCode($language_code);

        if (AMADB::isError($table_name)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        /*
     * Check if the given message is already in table messaggi_sistema
        */
        $sql_message_id = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=?";

        $result = $this->getRowPrepared($sql_message_id, [$message_text]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        /*
     * If the given message is not in table messaggi_sistema, add it.
        */
        if ($result ==  null) {
            $insert = $this->addTranslationMessage($message_text);
            if (AMADB::isError($insert)) {
                return new AMAError(AMA_ERR_ADD);
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
        $result = $this->selectMessageText($table_name, $message_id);

        /*
     * If a translation in the given language is not found, return the original message
        */
        if (AMADB::isError($result) or $result ==  null) {
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
    public function selectMessageText($table_name, $message_id)
    {
        $sql_translated_message = "SELECT testo_messaggio FROM $table_name WHERE id_messaggio=?";
        $result = $this->getRowPrepared($sql_translated_message, [$message_id], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }


    /**
     * function find_translation_for_message
     *
     * @param string  $message_text
     * @param string  $language_code
     * @param integer $limit_results_number_to
     * @return mixed - An AMAError object if there were errors, an array of string otherwise
     */
    public function findTranslationForMessage($message_text, $language_code, $limit_results_number_to)
    {
        $last_char = strlen($message_text);
        /*
         * Check if the user has specified an exact query (e.g. '"some text"')
         */
        if ($message_text[0] == '"' && $message_text[$last_char - 1] == '"') {
            $sql_prepared_text = $this->sqlPrepared(trim($message_text, '"'));
            $sql_for_where     = "testo_messaggio=$sql_prepared_text";
        } elseif ($message_text[1] == '"' && $message_text[$last_char] == '"') {
            $sql_prepared_text = $this->sqlPrepared(trim($message_text, '\"'));
            $sql_for_where     = "testo_messaggio=$sql_prepared_text";
        } else {
            /*
             * The user entered some search tokens (e.g. 'some text')
             */
            $sql_for_where = "";
            $token = strtok($message_text, ' ');
            $sql_prepared_text = $this->sqlPrepared("%$token%");
            $sql_for_where .= "testo_messaggio LIKE $sql_prepared_text ";
            while (($token = strtok(' ')) !== false) {
                $sql_prepared_text = $this->sqlPrepared("%$token%");
                $sql_for_where .= "AND testo_messaggio LIKE $sql_prepared_text ";
            }
            if ($limit_results_number_to != null || $limit_results_number_to != "") {
                $sql_for_where .= " LIMIT $limit_results_number_to";
            }
        }

        $table_name = $this->getTranslationTableNameForLanguageCode($language_code);
        if (AMADB::isError($table_name)) {
            return new AMAError(AMA_ERR_GET);
        }

        $sql_translated_message = "SELECT id_messaggio,testo_messaggio
                                   FROM $table_name
                                  WHERE $sql_for_where";

        $result = $this->getAllPrepared($sql_translated_message, null, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }


    /**
     * function get_all_system_messages: used to obtain all the messages stored
     * in table 'messaggi_sistema'.
     *
     * @return mixed - An AMAError object if there were errors, an array of strings otherwise.
     */
    public function getAllSystemMessages()
    {
        $sql_get_messages = "SELECT testo_messaggio FROM messaggi_sistema";
        $result = $this->getAllPrepared($sql_get_messages, null, AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
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
     * @return true if the message was successfully inserted, an AMADB error otherwise
     */
    private function addTranslationMessage($sql_prepared_message)
    {
        /**
         * Insert this message in table messaggi_sistema
         */
        $sql_insert_message    = "INSERT INTO messaggi_sistema(testo_messaggio) VALUES(?)";

        $result = $this->queryPrepared($sql_insert_message, [$sql_prepared_message]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
        }

        /**
         * Get tablename suffixes for each language supported in the user interface message
         * translation and use each suffix to construct the table name to use for message insertion
         */
        $sql_select_translation_tables_suffixes = "SELECT identificatore_tabella FROM lingue";
        $suffixes = $this->getColPrepared($sql_select_translation_tables_suffixes);
        if (AMADB::isError($suffixes)) {
            return new AMAError(AMA_ERR_GET);
        }
        // ottenere l'id per il messaggio appena inserito

        $sql_id_message    = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=$sql_prepared_message";
        $id_message = $this->getOnePrepared($sql_id_message);
        if (AMADB::isError($id_message)) {
            return new AMAError(AMA_ERR_GET);
        }

        foreach ($suffixes as $table_suffix) {
            $table_name = 'messaggi_' . $table_suffix;
            /**
             * Insert the messagge in the translation table named $table_name
             */
            $sql_insert_message_in_translation_table = "INSERT INTO $table_name(id_messaggio,testo_messaggio) VALUES($id_message,$sql_prepared_message)";
            $result = $this->queryPrepared($sql_insert_message_in_translation_table);
            /**
             * If an error occurs while adding the message into this table, then add an empty string, since
             * we don't want to loose identifier one-to-one mapping between this table and table messaggi_sistema
             */
            if (AMADB::isError($result)) {
                ADALogger::logDb("Error encountered while adding message $sql_prepared_message into table $table_name");
                $sql_insert_in_case_of_error = "INSERT INTO $table_name(testo_messaggio) VALUES('')";
                $result = $this->queryPrepared($sql_insert_in_case_of_error);
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
     * @return mixed  - An AMAError object if there were errors, true otherwise
     */
    public function updateMessageTranslationForLanguageCode($message_id, $message_text, $language_code)
    {
        $table_name = $this->getTranslationTableNameForLanguageCode($language_code);
        if (AMADB::isError($table_name)) {
            return new AMAError(AMA_ERR_GET);
        }

        $sql_update_message_text = "UPDATE $table_name SET testo_messaggio=? WHERE id_messaggio=?";
        $result = $this->queryPrepared($sql_update_message_text, [$message_text, $message_id]);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * function update_message_translation_for_language_code_given_this_text
     *
     * @param string $message_text     - the existing string in the translation
     * @param string $new_message_text - the new string
     * @param string $language_code    - ISO 639-1 code which identifies the translation
     * @return mixed - AMAError object if there were errors, the number of affected rows otherwise
     */
    public function updateMessageTranslationForLanguageCodeGivenThisText($message_text, $new_message_text, $language_code)
    {
        $table_name = $this->getTranslationTableNameForLanguageCode($language_code);
        if (AMADB::isError($table_name)) {
            return new AMAError(AMA_ERR_GET);
        }

        /*
     * Check if the given message is already in table messaggi_sistema
        */
        $sql_message_id = "SELECT id_messaggio FROM messaggi_sistema WHERE testo_messaggio=?";

        $result = $this->getRowPrepared($sql_message_id, [$message_text]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        $message_id = $result[0];
        // FIXME: verificare il valore restituito se il messaggio dato non esiste nella tabella.

        $sql_update_message_text = "UPDATE $table_name SET testo_messaggio=? WHERE id_messaggio=?";
        $result = $this->queryPrepared($sql_update_message_text, [$new_message_text, $message_id]);

        if (AMADB::isError($result)) {
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
     * @return true if the message was successfully inserted, an AMADB error otherwise
     */
    public function addTranslatedMessage($sql_prepared_message, $id, $suffix)
    {
        $table_name = 'messaggi_' . $suffix;
        /**
         * Insert the messagge in the translation table named $table_name
         */
        $sql_insert_message_in_translation_table = "INSERT INTO $table_name(id_messaggio, testo_messaggio) VALUES(?,?)";
        $result = $this->queryPrepared($sql_insert_message_in_translation_table, [$id, $sql_prepared_message]);
        /**
         * If an error occurs while adding the message into this table, then add an empty string, since
         * we don't want to loose identifier one-to-one mapping between this table and table messaggi_sistema
         */
        if (AMADB::isError($result)) {
            ADALogger::logDb("Error encountered while adding message $sql_prepared_message into table $table_name");
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
     * @return true if the table was emptied, an AMADB error otherwise
     */
    public function deleteAllMessages($suffix)
    {
        $table_name = 'messaggi_' . $suffix;
        /**
         * delete messagges from the translation table named $table_name
         */
        $sql_delete_messages_from_translation_table = "delete from $table_name";
        $result = $this->queryPrepared($sql_delete_messages_from_translation_table);
        /**
         * If an error occurs while deleting all the messages from this table
         */
        if (AMADB::isError($result)) {
            ADALogger::logDb("Error encountered while deleting messages from table $table_name");
            return $result;
        }
        $sql = "ALTER TABLE $table_name AUTO_INCREMENT = 0";
        $result = $this->queryPrepared($sql);
        if (AMADB::isError($result)) {
            ADALogger::logDb("Error encountered while deleting messages from table $table_name");
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
     * @return mixed - An AMAError object if there were errors, an array of string otherwise
     */
    public function findLanguages()
    {
        $sql_select_languages = "SELECT id_lingua,nome_lingua,codice_lingua FROM lingue";
        $result = $this->getAllPrepared($sql_select_languages, null, AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function findLanguageTableIdentifierByLangaugeId($language_id)
    {
        $sql_select_languages = "SELECT identificatore_tabella FROM lingue WHERE id_lingua=?";
        $result = $this->getOnePrepared($sql_select_languages, [$language_id], AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    public function findLanguageIdByLangaugeTableIdentifier($table_identifier)
    {
        $sql_select_languages = "SELECT id_lingua FROM lingue WHERE identificatore_tabella=?";
        $result = $this->getOnePrepared($sql_select_languages, [$table_identifier], AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (empty($result)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
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
    private function getTranslationTableNameForLanguageCode($language_code)
    {
        // = AMA_DB_MDB2_wrapper
        $translation_tables_default_prefix = 'messaggi_';

        $sql_translation_table_suffix_for_language_code = "SELECT identificatore_tabella FROM lingue WHERE codice_lingua=?";

        $result = $this->getRowPrepared($sql_translation_table_suffix_for_language_code, [$language_code]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
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
            $res = $this->getOnePrepared($query, [$thirdleveldomain]);
            if ($res !== false && !AMADB::isError($res) && strlen($res) > 0) {
                return $res;
            }
        }
        return $thirdleveldomain;
    }

    /**
     * (non-PHPdoc)
     * @see include/AbstractAMADataHandler#__destruct()
     */
    public function __destruct()
    {
        parent::__destruct();
    }
}

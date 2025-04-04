<?php

/**
 *
 * Tester
 *
 * @access public
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Main\AMA;

use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Logger\ADALogger;
use Lynxlab\ADA\Main\Menu;
use Lynxlab\ADA\Main\Traits\ADASingleton;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\CourseEvent;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsNode;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class AMATesterDataHandler extends AbstractAMADataHandler
{
    use ADASingleton;

    protected static $instance = null;
    /**
     * Contains the data source name used to create this instance of AMADataHandler
     * @var string
     */
    protected static $tester_dsn = null;

    /**
     *
     * @param  string $dsn - a valid data source name
     * @return an instance of AMADataHandler
     */
    public function __construct($dsn = null)
    {
        //ADALogger::logDb('AMADataHandler constructor');
        parent::__construct($dsn);
    }

    /**
     * (non-PHPdoc)
     * @see include/AbstractAMADataHandler#__destruct()
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    public static function instance($dsn = null)
    {
        if (static::hasInstance()) {
            $instance = static::getInstance($dsn);
        } else {
            $instance = static::getInstance($dsn);
            $instance->setDSN($dsn);
        }
        return $instance;
    }

    /**
     * Returns an instance of AMADataHandler.
     *
     * @param  string $dsn - optional, a valid data source name
     * @return self an instance of AMADataHandler
     */
    public static function OLDinstance($dsn = null)
    {
        $callerClassName = static::class;
        if (!is_null(self::$instance) && self::$instance::class !== $callerClassName) {
            self::$instance = null;
        }

        if (self::$instance === null) {
            self::$instance = new $callerClassName($dsn);
        } else {
            self::$instance->setDSN($dsn);
        }
        //return null;
        return self::$instance;
    }

    public function setDSN($dsn = null)
    {
        $this->dsn = $dsn;
    }
    /**
     * Methods accessing database
     */

    /**
     * Methods accessing table `amministratore_corsi`
     */
    // MARK: Methods accessing table `amministratore_corsi`
    // FIXME: currently we have no methods accessing this table.

    /**
     * Methods accessing table `amministratore_sistema`
     *
     * There aren't methods accessing only this table. Queries on this table
     * are performed by methods add_admin, remove_admin, get_admins_list.
     */
    // MARK: Methods accessing table `amministratore_sistema`

    /**
     * Methods accessing table `autore`
     */
    // MARK: Methods accessing table `autore`
    /**
     * Add an author to the DB
     *
     * @access public
     *
     * @param $author_ha an associative array containing all the author's data
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *         the author id on success
     */
    public function addAuthor($author_ha)
    {
        /*
     * $author_ha is an associative array with the following keys set:
     * id_utente, nome, cognome, email, username, password, telefono, layout, tariffa, profilo
        */
        /*
     * Add user data in table utenti
        */
        $result = $this->addUser($author_ha);
        if (AMADB::isError($result)) {
            // $result is an AMAError object
            return $result; //new AMAError(AMA_ERR_ADD);
        }

        $add_author_sql = 'INSERT INTO autore(id_utente_autore, tariffa, profilo) VALUES(?,?,?)';

        $add_author_values = [
            $author_ha['id_utente'],
            $this->orZero(array_key_exists('tariffa', $author_ha) ? $author_ha['tariffa'] : null),
            $this->orNull(array_key_exists('profilo', $author_ha) ? $author_ha['profilo'] : null),
        ];

        $result = $this->executeCriticalPrepared($add_author_sql, $add_author_values);
        if (AMADB::isError($result)) {
            // try manual rollback in case problems arise
            $delete_user_sql = 'DELETE FROM utente WHERE username=?';
            $delete_result   = $this->executeCriticalPrepared($delete_user_sql, [$author_ha['username']]);
            if (AMADB::isError($delete_result)) {
                return $delete_result;
            }
            /*
       * user data has been successfully removed from table utente, return only
       * the error obtained when adding user data to table autore.
            */
            return $result;
        }

        /*
     * the author data has been successfully added to tables utente and autore,
     * return the user id assigned to this user.
        */
        return $author_ha['id_utente'];
    }

    /**
     * Remove an author from the DB
     *
     * @access public
     *
     * @param $id the unique id of the author
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *         true on success
     */
    public function removeAuthor($id)
    {
        $valuesAr = [$id];

        // referential integrity checks
        $id_course_sql = 'SELECT id_corso FROM modello_corso WHERE id_utente_autore=?';
        $result = $this->getOnePrepared($id_course_sql, $valuesAr);
        if (AMADB::isError($result)) {
        } elseif ($result) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        $id_node_sql = 'SELECT id_nodo FROM nodo WHERE id_utente=?';
        $result = $this->getOnePrepared($id_node_sql, $valuesAr);
        if (AMADB::isError($result)) {
        } elseif ($result) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        $id_link_sql = 'SELECT id_link FROM link WHERE id_utente=?';
        $result = $this->getOnePrepared($id_link_sql, $valuesAr);
        if (AMADB::isError($result)) {
        } elseif ($result) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        /*
     * Referential integrity checks are OK, delete the author from tables
     * autore and utente.
        */

        $delete_author_sql = 'DELETE FROM autore WHERE id_utente_autore=?';
        $result = $this->executeCriticalPrepared($delete_author_sql, $valuesAr);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }

        $delete_user_sql = 'DELETE FROM utente WHERE id_utente=?';
        $result = $this->executeCriticalPrepared($delete_user_sql, $valuesAr);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }
        /*
     * Author's data was successfully deleted from tables autore and utente.
        */
        return true;
    }

    /**
     * Get a list of authors' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password,
     *        telefono, profilo, tariffa
     *
     * @return array|AMAError a nested array containing the list, or an AMAError object or a
     * DB_Error object if something goes wrong
     *
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_authors_list
     */
    public function &getAuthorsList($field_list_ar)
    {
        return $this->findAuthorsList($field_list_ar);
    }

    /**
     * Get a list of authors' ids from the DB
     *
     * @access public
     *
     * @return an array containing the list, or an AMAError object or a DB_Error
     * object if something goes wrong
     *
     * @see find_authors_list, get_authors_list
     */
    public function &getAuthorsIds()
    {
        return $this->getAuthorsList([]);
    }

    /**
     * Get those authors' ids verifying the given criterium on the tarif fiels
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @param  clause the clause string which will be added to the select
     *
     * @return array|AMAError a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &findAuthorsList($field_list_ar, $clause = '')
    {
        // FIXME: the queries performef by this method aren't prepared.

        // build comma separated string out of $field_list_ar array
        $more_fields = '';
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }
        // add an 'and' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'and ' . $clause;
        }
        // do the query
        $authors_ar =  $this->getAllPrepared("select id_utente$more_fields from utente, autore where id_utente=id_utente_autore $clause");

        if (AMADB::isError($authors_ar)) {
            //return $authors_ar;
            return new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $authors_ar;
    }

    /**
     * Get all informations about an author
     *
     * @access public
     *
     * @param $id the author's id
     *
     * @return an array containing all the informations about an author
     *
     */
    public function getAuthor($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->getUserInfo($id);
        if (self::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            return $get_user_result;
        }

        // get a row from table AUTORE
        $get_author_sql = "SELECT tariffa, profilo FROM autore WHERE id_utente_autore=?";
        $get_author_result = $this->getRowPrepared($get_author_sql, [$id], AMA_FETCH_ASSOC);
        if (AMADB::isError($get_author_result)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (!$get_author_result) {
            /* inconsistency found! a message should be logged */
            return new AMAError(AMA_ERR_INCONSISTENT_DATA);
        }
        return array_merge($get_user_result, $get_author_result);
    }

    /**
     * Updates informations related to an author
     *
     * @access public
     *
     * @param $id the author's id
     *        $author_ar the informations. empty fields are not updated
     *
     * @return an error if something goes wrong, true on success
     *
     */
    public function setAuthor($id, $author_ha)
    {
        // backup old values
        $old_values_ha = $this->getAuthor($id);

        $result = $this->setUser($id, $author_ha);
        if (self::isError($result)) {
            // $result is an AMAError object
            return $result;
        }

        $update_author_sql = 'UPDATE autore SET tariffa=?, profilo=? WHERE id_utente_autore=?';
        $valuesAr = [
            $author_ha['tariffa'] ?? null,
            $author_ha['profilo'] ?? null,
            $id,
        ];
        $result = $this->queryPrepared($update_author_sql, $valuesAr);

        if (AMADB::isError($result)) {
            $valuesAr = [
                $old_values_ha['nome'],
                $old_values_ha['cognome'],
                $old_values_ha['email'],
                $old_values_ha['telefono'],
                $old_values_ha['password'],
                $old_values_ha['layout'],
                $old_values_ha['indirizzo'],
                $old_values_ha['citta'],
                $old_values_ha['provincia'],
                $old_values_ha['nazione'],
                $old_values_ha['codice_fiscale'],
                AMACommonDataHandler::dateToTs($old_values_ha['birthdate']),
                $old_values_ha['sesso'],
                $old_values_ha['stato'],
                $old_values_ha['lingua'],
                $old_values_ha['timezone'],
                $old_values_ha['cap'],
                $old_values_ha['matricola'],
                $old_values_ha['avatar'],
                $old_values_ha['birthcity'],
                $old_values_ha['birthprovince'],
                $id,
            ];

            $update_user_sql = 'UPDATE utente SET nome=?, cognome=?, e_mail=?, telefono=?, password=?, layout=?, '
                . 'indirizzo=?, citta=?, provincia=?, nazione=?, codice_fiscale=?, birthdate=?, sesso=?, '
                . 'stato=?, lingua=?, timezone=?, cap=?, matricola=?, avatar=?, birthcity=?, birthprovince=? WHERE id_utente=?';

            $result = $this->queryPrepared($update_user_sql, $valuesAr);
            // qui andrebbe differenziato il tipo di errore
            if (AMADB::isError($result)) {
                return new AMAError(AMA_ERR_UPDATE);
            }

            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
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
        if (is_null($user_id) || $user_id === false) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

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
                $this->dateToTs($user_ha['birthdate']),
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
                . 'telefono=?,stato=?, lingua=?, timezone=?, cap=?, matricola=?, avatar=?, birthcity=?, birthprovince=?';

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
                $this->dateToTs($user_ha['birthdate']),
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
        if (ModuleLoaderHelper::isLoaded('GDPR') === true && array_key_exists('username', $user_ha) && strlen($user_ha['username']) > 0) {
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
     * Methods accessing table `bookmark`
     */
    // MARK: Methods accessing table `bookmark`

    /**
     * Add an item  to table bookmark
     * The date of the adding is set automatically.
     * It is assumed that the IDs have already been checked by the caller
     * The ordering field is automatically filled by the add_bookmark() method
     *
     * access:
     *  public
     *
     * parameters:
     * @param $student_id   the id of the student
     * @param $course_id    the id of the instance of course the student is navigating
     * @param $node_id      the node to be registered in the history
     * @param $description  a textual description of the bookmark
     * @param $ordering     integer if specified, then insert the bookmark with a given ordering
     *
     * @return the id of the added bookmark on success, an AMAError object on failure
     */
    public function addBookmark($node_id, $student_id, $instance_id, $date, $description, $ordering = "")
    {
        // get the present date-time as timestamp
        $date = $this->dateToTs("now");

        // if ordering is not specified or not an integer, then calculate it
        if (empty($ordering) || !is_int($ordering)) {
            // get last ordering value from the bookmarks
            // of a student in a class
            $sql = "select ordering from bookmark" .
                " where id_utente_studente=? and id_istanza_corso=?" .
                " order by ordering desc;";

            $ordering =  $this->getOnePrepared($sql, [$student_id, $instance_id]);
            if (AMADB::isError($ordering)) {
                return new AMAError(AMA_ERR_GET);
            }

            // if no record is found, then set ordering to zero
            // (so that incrementing it will bring it to one)
            // FIXME: siamo sicuri che getOne resituisca zero se non trova il record?
            // dovrebbe restituire null
            if ($ordering == 0) {
                $ordering = 0;
            }

            // increment ordering
            $ordering++;
        }

        // find duplicates

        /*  $out_fields_ar = array('id_nodo','descrizione');
     $clause = "descrizione = $description";
     $already_exists= $this->doFind_bookmarks_list($out_fields_ar, $clause);
        */
        $sql = "select id_nodo from bookmark" .
            " where descrizione=? and id_utente_studente=? and id_istanza_corso=?";

        $already_exists =  $this->getOnePrepared($sql, [$description, $student_id, $instance_id]);
        if (AMADB::isError($already_exists)) {
            return new AMAError(AMA_ERR_GET);
        }

        if ($already_exists) {
            return new AMAError(AMA_ERR_UNIQUE_KEY);
        }

        // add a row into table bookmark
        $sql =  "insert into bookmark (id_utente_studente, id_istanza_corso, id_nodo, data, descrizione, ordering)";
        $sql .= " values (?, ?, ?, ?, ?, ?)";

        $res = $this->queryPrepared($sql, [$student_id, $instance_id, $node_id, $date, $description, $ordering]);

        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_ADD);
        }

        $sql = "select id_bookmark from bookmark" .
            " where id_nodo=? and descrizione=? and id_utente_studente=? and id_istanza_corso=?";

        $new_bookmark =   $this->getRowPrepared($sql, [$node_id, $description, $student_id, $instance_id]);
        if (AMADB::isError($new_bookmark)) {
            return new AMAError(AMA_ERR_GET);
        }
        return  $new_bookmark[0];
    }

    /**
     * Get all informations related to a given bookmark.
     *
     * @access public
     *
     * @param $bookmark_id
     *
     * @return an hash with the fields
     *               the keys are:
     * node_id       - the id of the bookmarked node
     * student_id    - the id of the student who bookmarked the node
     * course_id     - the id of the instance of the course  the student is following
     * date          - the date of the bookmark's insertion (as ADA_DATE_FORMAT)
     * description   - the description of the ordering
     * ordering      - the ordering value
     *
     * @return an array on success, an AMAError object on failure.
     */
    public function getBookmarkInfo($bookmark_id)
    {
        // get a row from table bookmark
        $sql  = "select id_nodo, id_utente_studente, id_istanza_corso, data, descrizione, ordering ";
        $sql .= " from bookmark where id_bookmark=?";
        $res_ar =  $this->getRowPrepared($sql, [$bookmark_id]);

        //    if (AMADB::isError($res_ar)) {
        //      return new AMAError(AMA_ERR_GET);
        //    }
        if (AMADB::isError($res_ar) || !$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        $res_ha['node_id']      = $res_ar[0];
        $res_ha['student_id']   = $res_ar[1];
        $res_ha['course_id']    = $res_ar[2];
        $res_ha['date']         = self::tsToDate($res_ar[3]);
        $res_ha['description']  = $res_ar[4];
        $res_ha['ordering']     = $res_ar[5];

        return $res_ha;
    }

    /**
     * Get bookmarks which satisfy a given clause
     * Only the fields specified in the $out_fields_ar parameter are inserted
     * in the result set.
     * This function is meant to be used by the public find_bookmarks_list()
     *
     * @access private
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     * @return array on success, a bi-dimensional array containing these fields:
     *
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     *      on failure, an AMAError object
     */
    private function &doFindBookmarksList($out_fields_ar, $clause = '')
    {
        $more_fields = '';
        // build comma separated string out of $field_list_ar array
        if (!empty($out_fields_ar) and is_array($out_fields_ar) and count($out_fields_ar)) {
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        }
        // add a 'where' on top of the clause
        // handle null clause, too

        $sql = "select id_bookmark";
        if ($more_fields) {
            $sql .= $more_fields;
        }
        $sql .= " from bookmark ";
        if ($clause) {
            $sql .= 'where ' . $clause;
        }
        // do the query
        $res_ar =  $this->getAllPrepared($sql);

        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        // return nested array
        return $res_ar;
    }

    /**
     * Get bookmarks
     * Returns all the bookmarks without filtering. Only the fields specified
     * in the $out_fields_ar parameter are inserted in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @return a bi-dimensional array containing the fields as specified
     *
     * @see _find_bookmarks_list
     *
     */
    public function &getBookmarksList($out_fields_ar)
    {
        return $this->doFindBookmarksList($out_fields_ar);
    }

    /**
     * Get bookmarks for a given student, course instance or node.
     * Returns all the history informations filtering on students, courses or both.
     * If a parameter has the value '', then it is not filtered.
     * Only the fields specified in the $out_fields_ar parameter are inserted
     * in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     * @return a bi-dimensional array containing the fields as specified.
     *
     * @see _find_bookmarks_list
     *
     */
    public function &findBookmarksList($out_fields_ar, $student_id = 0, $course_instance_id = 0, $node_id = '')
    {
        // build the clause
        $clause = '';

        if ($student_id) {
            $clause .= "id_utente_studente =" . $this->sqlPrepared($student_id);
        }
        if ($course_instance_id) {
            if ($clause) {
                $clause .= ' and ';
            }
            $clause .= "id_istanza_corso =" . $this->sqlPrepared($course_instance_id);
        }

        if ($node_id) {
            if ($clause) {
                $clause .= ' and ';
            }

            $clause .= "id_nodo =" . $this->sqlPrepared($node_id);
        }
        // invokes the private method to get all the records
        return $this->doFindBookmarksList($out_fields_ar, $clause);
    }

    /**
     * Updates informations related to a bookmark
     * only the description and ordering can be updated
     * the date is also changed, but automatically
     *
     * access:
     *  private
     *
     *
     * @param $id              - the bookmark's id
     * @param $new_description - the new description string
     * @param $new_ordering    - the new ordering number
     *
     *
     * @return true on success, an AMAError object on failure
     *
     * @see
     *  set_bookmark_description()
     *  swap_bookmarks()
     */
    private function setBookmark($id, $new_description, $new_ordering)
    {
        // get the present date-time as timestamp
        $date = $this->dateToTs("now");
        $values = [];

        // build the description change
        // leave it blank if it is not required
        if ($new_description) {
            $description_update = "descrizione=?, ";
            $values[] = $new_description;
        } else {
            $description_update = "";
        }
        // build the ordering change
        // leave it blank if it is not required
        if ($new_ordering) {
            $ordering_update = "ordering=?, ";
            $values[] = $new_ordering;
        } else {
            $ordering_update = "";
        }
        // verify that the record exists and store old values for rollback
        $res_id =  $this->getRowPrepared("select id_bookmark from bookmark where id_bookmark=?", [$id]);
        //    if (AMADB::isError($res_id)) {
        //    return $res_id;
        if (AMADB::isError($res_id) || $res_id == 0) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $values[] = $date;
        $values[] = $id;

        // update the rows in the tables
        $sql  = "update bookmark set " .
            $description_update .
            $ordering_update .
            " data=? " .
            " where id_bookmark=?";

        $res = $this->queryPrepared($sql, $values);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * Updates a bookmark's description
     *
     * access:
     *  public
     *
     * parameters:
     *  $id              - the bookmark's id
     *  $new_description - the new description string
     *
     * return:
     *  an error if something goes wrong
     *
     * see also:
     *  set_bookmark()
     */
    public function setBookmarkDescription($id, $descr)
    {
        // invoke private _set_bookmark method to do the job
        if (AMADB::isError($this->setBookmark($id, $descr, ""))) {
            return new AMAError(AMA_ERR_UPDATE);
        }
    }

    /**
     * Swap positions between bookmark entries
     * (do it inside a transaction!)
     *
     * @param $id1  - the first bookmark entry
     * @param $id2  - the second bookmark entry
     *
     * @return true on success, an AMAError object on failure
     *
     * @see  set_bookmark()
     */
    public function swapBookmarks($id1, $id2)
    {
        // do not check DB connection,
        // since it uses methods which do

        // get ordering for first record
        $res_ha = $this->getBookmarkInfo($id1);
        if (AMADataHandler::isError($res_ha)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        $ordering1 = $res_ha['ordering'];

        // get ordering for second record
        $res_ha = $this->getBookmarkInfo($id2);
        if (AMADataHandler::isError($res_ha)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        $ordering2 = $res_ha['ordering'];

        // begin the transaction
        $this->beginTransaction();

        if (AMADB::isError($this->setBookmark($id1, "", $ordering2))) {
            return new AMAError(AMA_ERR_UPDATE);
        }

        $this->rsAdd("_set_bookmark", $id1, "", $ordering1);

        if (AMADB::isError($this->setBookmark($id2, "", $ordering1))) {
            $this->rollback();
            return new AMAError(AMA_ERR_UPDATE);
        }
        $this->commit();

        return true;
    }

    /**
     * Remove a bookmark
     * all subsequent orderings are decreased by one
     * this is performed inside a transaction
     *
     * @access public
     *
     * @param id the id of the action to be removed
     * @return true on success, an AMAError object on failure
     */
    public function removeBookmark($id)
    {
        // get data of record to remove (for rollback)
        $res_ha = $this->getBookmarkInfo($id);
        if (AMADataHandler::isError($res_ha)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        $ordering = $res_ha['ordering'];

        // get a list of ids having ordering greater than
        // the record to be removed
        $res_ar = $this->doFindBookmarksList("ordering", "ordering>$ordering");

        // begin complex removal operations

        // start a transaction
        $this->beginTransaction();

        // removal query
        $sql = "delete from bookmark where id_bookmark=?";
        $res = $this->executeCriticalPrepared($sql, [$id]);

        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_REMOVE);
        }
        // insert restoring action into rollback segment
        // for the remove operation


        $this->rsAdd(
            "add_bookmark",
            $res_ha['node_id'],
            $res_ha['student_id'],
            $res_ha['course_id'],
            $res_ha['description'],
            $res_ha['ordering']
        );

        // update ordering loop
        $n = count($res_ar);
        for ($i = 0; $i < $n; $i++) {
            // decrease ordering value

            $res = @$this->setBookmark($res_ar[$i][0], "", $res_ar[$i][1] - 1);
            if (AMADB::isError($res)) {
                // rollback in case of error
                $this->rollback();
                return new AMAError(AMA_ERR_REMOVE);
            }


            // insert restoring action into rollback segment
            // for the ordering update operation
            @$this->rsAdd(
                "_set_bookmark",
                $res_ar[$i][0],
                "",
                $res_ar[$i][1]
            );
        }

        // final success
        $this->commit();

        return true;
    }

    /**
     * Methods accessing table `chatroom`
     * @see ChatRoom.inc.php
     */
    // MARK: Methods accessing table `chatroom`

    /**
     * Methods accessing table `clienti`
     *
     * Currently we have no methods for table clienti
     */
    // MARK: Methods accessing table `clienti`

    /**
     * Methods accessing table `destinatari_messaggi`
     * @see MessageDataHandler.inc.php
     */
    // MARK: Methods accessing table `destinatari_messaggi`


    /**
     * Methods accessing table `history_nodi`
     */
    // MARK: Methods accessing table `history_nodi`


    /**
     * Add an item  to table history_nodi
     * Useful during the navigation. The date of the visit is computed automatically.
     *
     * @access public
     *
     * @param $student_id   the id of the student
     * @param $course_id    the id of the instance of course the student is navigating
     * @param $node_id      the node to be registered in the history
     *
     * @return true on success, an AMAError object on failure.
     */
    public function addNodeHistory($student_id, $course_id, $node_id, $remote_address, $installation_path, $access_from, $isAjax = false)
    {
        // get session id
        $session_id = session_id();

        // visiting date ...
        $visit_date = $this->dateToTs("now");

        // exit date ... :)
        $exit_date = $visit_date;

        // update field exit_date in table history_nodi
        $sql  = "select id_history,id_nodo from history_nodi where session_id=? AND `data_visita`=`data_uscita` ORDER BY id_history DESC";
        $res_ar =  $this->getRowPrepared($sql, [$session_id], AMA_FETCH_ASSOC);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (isset($res_ar['id_history'])) {
            $last_id_history = $res_ar['id_history'];
        }
        if (isset($res_ar['id_nodo'])) {
            $last_id_nodo = $res_ar['id_nodo'];
        }
        if (!$isAjax && isset($last_id_nodo) && $last_id_nodo == $node_id) {
            return true;
        }

        if (isset($last_id_history)) {
            $sql = "update history_nodi set data_uscita=? where id_history=?;";
            $res = $this->queryPrepared($sql, [$visit_date, $last_id_history]);
            if (AMADB::isError($res)) {
                return new AMAError(AMA_ERR_UPDATE);
            }
        }

        // if visiting a node...
        if (isset($node_id) && !$isAjax) {
            // add a row into table history_nodi
            $sql =  "insert into history_nodi (id_utente_studente, id_istanza_corso, id_nodo, data_visita, data_uscita, session_id, remote_address, installation_path, access_from)";
            $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?);";
            $res = $this->queryPrepared($sql, [$student_id, $course_id, $node_id, $visit_date, $exit_date, $session_id, $remote_address, $installation_path, $access_from]);
            if (AMADB::isError($res)) {
                return new AMAError(AMA_ERR_ADD);
            }
            // update field N_CONTATTI in table nodo

            $sql  = "select n_contatti from nodo where id_nodo=?";
            $res_ar =  $this->getRowPrepared($sql, [$node_id]);
            if (AMADB::isError($res_ar)) {
                return new AMAError(AMA_ERR_GET);
            }

            $visitCount = $res_ar[0];
            $visitCount++;
            $sql = "update nodo set n_contatti=? where id_nodo=?;";
            $res = $this->queryPrepared($sql, [$visitCount, $node_id]);
            if (AMADB::isError($res)) {
                return new AMAError(AMA_ERR_UPDATE);
            }
        }

        return true;
    }

    /**
     * Get all informations related to a given nodes history row.
     *
     * @access public
     *
     * @param $nodes_history_id
     *
     * @return on success, an hash with the fields
     *         the keys are:
     * node_id            - the id of the bookmarked node
     * student_id         - the id of the student
     * course_id          - the id of the instance of the course  the student is following
     * visit_date         - the moment of the visit
     * exit_date          - the moment the user left the node (?)
     * session_id         - session_id at the moment of the visit
     *
     *      on failure, an AMAError object
     */
    public function getNodesHistoryInfo($nodes_history_id)
    {
        // get a row from table history_nodi
        $sql  = "select id_nodo, id_utente_studente, id_istanza_corso, data_visita, data_uscita, session_id ";
        $sql .= "from history_nodi where id_history=?";
        $res_ar =  $this->getRowPrepared($sql, [$nodes_history_id]);
        //    if (AMADB::isError($res_ar))
        //    return $res_ar;

        if (AMADB::isError($res_ar) || !$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $res_ha['node_id']      = $res_ar[0];
        $res_ha['student_id']   = $res_ar[1];
        $res_ha['course_id']    = $res_ar[2];
        $res_ha['visit_date']   = self::tsToDate($res_ar[3]);
        $res_ha['exit_date']    = self::tsToDate($res_ar[4]);
        $res_ha['session_id']   = $res_ar[5];
        $res_ha['time_spent']   = $res_ar[4] - $res_ar[3];


        return $res_ha;
    }

    /**
     * Get nodes history informations which satisfy a given clause
     * Only the fields specifiedin the $out_fields_ar parameter are inserted
     * in the result set.
     * This function is meant to be used by the public get_nodes_history_list()
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     * @param $return_as_associative return an associative array
     *
     * @return on success, a bi-dimensional array containing these fields
     *
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     *           on failure, an AMAError object
     *
     */
    public function &doFindNodesHistoryList($out_fields_ar, $clause = '', $return_as_associative = false)
    {
        // build comma separated string out of $field_list_ar array
        if (count($out_fields_ar)) {
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        }
        // add a 'where' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'where ' . $clause;
        }
        // do the query
        $sql = "select id_history$more_fields from history_nodi $clause order by id_history";
        if ($return_as_associative) {
            $res_ar = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
        } else {
            $res_ar =  $this->getAllPrepared($sql);
        }

        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $res_ar;
    }

    /**
     * Get nodes history informations.
     * Returns all the history informations without filtering. Only the fields specified
     * in the $out_fields_ar parameter are inserted in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @return a bi-dimensional array containing the fields as specified
     *
     * @see
     *
     */
    public function &getNodesHistoryList($out_fields_ar)
    {
        return $this->doFindNodesHistoryList($out_fields_ar);
    }

    /**
     * Get nodes history informations for a given student, course instance or both
     * Returns all the history informations filtering on students, courses or both.
     * If a parameter has the value '', then it is not filtered.
     * Only the fields specified
     * in the $out_fields_ar parameter are inserted in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $student_id
     * @param $course_instance_id
     * @param $node_id
     *
     * @return a bi-dimensional array containing the fields as specified.
     *
     * @see
     *
     */
    public function &findNodesHistoryList($out_fields_ar, $student_id = 0, $course_instance_id = 0, $node_id = '')
    {
        // build the clause
        $clause = '';

        if ($student_id) {
            $clause .= "id_utente_studente = $student_id";
        }
        if ($course_instance_id) {
            if ($clause) {
                $clause .= ' and ';
            }
            $clause .= "id_istanza_corso = $course_instance_id";
        }

        if ($node_id) {
            $node_id = $this->sqlPrepared($node_id);
            if ($clause) {
                $clause .= ' and ';
            }
            $clause .= "id_nodo = $node_id";
        }

        /* modified 6/7/01 steve: redundant with code in _find_nodes_history_list
     if ($clause)
     $clause = ' where '.$clause;
        */

        // invokes the private method to get all the records
        return $this->doFindNodesHistoryList($out_fields_ar, $clause);
    }

    /**
     * Return student subscribed course instance
     *
     * @access public
     *
     * @param $id_user pass a single/array student id or use "false" to retrieve all student
     *
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array('tutor id'=>array('course_instance', 'course_instance', 'course_instance'));
     */

    public function getStudentsSubscribedCourseInstance($id_user = false, $presubscription = false, $both = false)
    {
        if ($both) {
            $status_Ar = [ADA_STATUS_PRESUBSCRIBED, ADA_STATUS_SUBSCRIBED, ADA_STATUS_REMOVED, ADA_STATUS_VISITOR, ADA_STATUS_TERMINATED];
        } elseif ($presubscription) {
            $status_Ar = [ADA_STATUS_PRESUBSCRIBED];
        } else {
            $status_Ar = [ADA_STATUS_SUBSCRIBED, ADA_STATUS_REMOVED, ADA_STATUS_VISITOR, ADA_STATUS_TERMINATED];
        }

        $sql = "SELECT
					i.`id_utente_studente`,
					c.`id_corso`, c.`titolo`, c.`id_utente_autore`,
					ic.`id_istanza_corso`, ic.`title`
				FROM `iscrizioni` i
				JOIN `istanza_corso` ic ON (ic.`id_istanza_corso`=i.`id_istanza_corso`)
				JOIN `modello_corso` c ON (c.`id_corso`=ic.`id_corso`)
				WHERE i.`status` IN (" . (implode(',', $status_Ar)) . ")";

        if (is_array($id_user) and !empty($id_user)) {
            $sql .= " AND i.`id_utente_studente` IN (" . implode(',', $id_user) . ")";
        } elseif ($id_user) {
            $sql .= " AND i.`id_utente_studente` = " . $id_user;
        }

        $result = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        } else {
            $array = [];
            foreach ($result as $k => $v) {
                $id = $v['id_utente_studente'];
                unset($v['id_utente_studente']);
                $array[$id][] = $v;
            }
            unset($result);
            return $array;
        }
    }

    /***
     * get_students_for_course_instances
     *
     * @param array $id_course_instances
     * @return mixed - an AMADB Error if something goes wrong or an associative array on success
     *
     */

    public function getStudentsForCourseInstance($id_course_instance, $all = false)
    {
        $status_Ar = [ADA_STATUS_SUBSCRIBED, ADA_STATUS_REMOVED, ADA_STATUS_VISITOR, ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED, ADA_STATUS_TERMINATED];
        if ($all !== false) {
            $status_Ar[] = ADA_STATUS_PRESUBSCRIBED;
        }

        $sql = 'SELECT U.*, I.status,I.data_iscrizione,I.laststatusupdate';

        if (ModuleLoaderHelper::isLoaded('CODEMAN')) {
            $sql = $sql . ', I.codice';
        }

        $sql = $sql . ' FROM utente AS U, iscrizioni AS I '
            . ' WHERE I.id_istanza_corso=?'
            . ' AND I.status IN (' . join(',', array_fill(0, count($status_Ar), '?')) . ')'
            . ' AND U.id_utente = I.id_utente_studente';

        $result = $this->getAllPrepared($sql, array_merge([$id_course_instance], $status_Ar), AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /***
     * get_unique_students_for_course_instances
     * used to fetch an associative array contains users having subscribe same course instance
     *
     * @param array $id_course_instances
     * @return mixed - an AMADB Error if something goes wrong or an associative array on success
     *
     * graffio 31/01/2011
     *
     */

    public function getUniqueStudentsForCourseInstances($id_course_instances = [])
    {
        $sql = 'SELECT U.id_utente, U.username, U.tipo, U.nome, U.cognome
                FROM utente AS U
                JOIN
                (SELECT DISTINCT
                    id_utente_studente
                    FROM iscrizioni
                 WHERE
                    id_istanza_corso IN (' . join(',', array_fill(0, count($id_course_instances), '?')) . ')) AS I ON (U.id_utente = I.id_utente_studente)
                 ORDER BY U.cognome ASC';


        $result = $this->getAllPrepared($sql, $id_course_instances, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /***
     *  get_students_report
     *  The function get the report of a class of students
     *  Used to fetch Table with all info requested in $requestedColumn
     *
     * @param int $id_instance
     * @param int $id_course
     * @param array $requestedColumn
     * @param array $indexWeights
     *
     * note: the ORDER of elements in this array change the ORDER of result columns
     *
     * All fields available:
     * "history"  - count of visits
     * "last_access"  - last access
     * "exercises_test"
     * "exercises_survey"
     * "added_notes"
     * "read_notes"
     * "message_count_out"
     * "message_count_in"
     * "chat"
     * "bookmarks"
     * "index"  - calculate a function  ("history" + "message_count_out" +"message_count_in"+ "chat" + "bookmarks" + "read_notes" + "added_notes" )
     * "level"
     *
     * example of $requestedColumn:
     * $columns= array(
                    REPORT_COLUMN_HISTORY => 'history',
                    REPORT_COLUMN_LAST_ACCESS => 'last_access',
                    REPORT_COLUMN_EXERCISES_TEST => 'exercises_test',
                    REPORT_COLUMN_EXERCISES_SURVEY => 'exercises_survey',
                    REPORT_COLUMN_ADDED_NOTES => 'added_notes',
                    REPORT_COLUMN_READ_NOTES   => 'read_notes',
                    REPORT_COLUMN_MESSAGE_COUNT_IN  => 'message_count_in',
                    REPORT_COLUMN_MESSAGE_COUNT_OUT  => 'message_count_out',
                    REPORT_COLUMN_CHAT  => 'chat',
                    REPORT_COLUMN_BOOKMARKS  => 'bookmarks',
                    REPORT_COLUMN_INDEX  => 'index',
                    REPORT_COLUMN_LEVEL  => 'level',
                    REPORT_COLUMN_LEVEL_PLUS  => 'level_plus',
                    REPORT_COLUMN_LEVEL_LESS  => 'level_less'  );
     *
     * Federico 17/11/2019
     */
    public function getStudentsReport($id_instance, $id_course, $requestedColumn, $indexWeights = [])
    {
        // the fields id and student return always
        $select = "SELECT  utente.id_utente AS id, CONCAT(utente.nome ,'::',utente.cognome) AS student ";
        $from = "
            FROM  (
                (Select * from iscrizioni WHERE id_istanza_corso = $id_instance and status <> " . ADA_STATUS_VISITOR . " ) as iscrizioni
            LEFT OUTER JOIN
                        utente
            ON iscrizioni.id_utente_studente = utente.id_utente
            ";

        /*
             $allPossibleFields has this shape:
             [
                column1 => ["select" => "text to insert in select", "from" => "text to insert in from"]
                column2 => ["select" => "text to insert in select2", "from" => "text to insert in from2"]
                .......
             ]

        */
        $allPossibleFields = [
            "history" => [
                "select" => "IFNULL(getterVisite.visite_totali,0) AS history",
                "from" => "
                        LEFT OUTER JOIN
                        (SELECT innerVisite.id_utente_studente, SUM(innerVisite.numero_visite) visite_totali
                                FROM nodo AS N
                                    LEFT JOIN
                                        (SELECT id_nodo, count(id_nodo) AS numero_visite, id_utente_studente FROM history_nodi WHERE id_istanza_corso=$id_instance GROUP BY id_nodo,id_utente_studente) AS innerVisite
                                    ON (N.id_nodo=innerVisite.id_nodo)
                                WHERE N.id_nodo LIKE '{$id_course}_%' AND N.tipo IN (" . ADA_LEAF_TYPE . ", " . ADA_GROUP_TYPE . ")
                            GROUP BY innerVisite.id_utente_studente) as getterVisite
                        ON utente.id_utente = getterVisite.id_utente_studente
                        ",
            ],
            "last_access" => [
                "select" =>   "IFNULL(FROM_UNIXTIME(ultimavisita.recente, '%d/%m/%Y'),'-') AS last_access",
                "from" => " LEFT OUTER JOIN
                            (SELECT id_utente_studente,MAX(data_uscita) AS recente from history_nodi as h LEFT OUTER JOIN nodo as n ON ( h.id_nodo = n.id_nodo) where  id_istanza_corso = $id_instance group by id_utente_studente) as ultimavisita
                                ON utente.id_utente = ultimavisita.id_utente_studente
                                ",
            ],

            "added_notes" => [
                "select" => "IFNULL(notes_write.note,0) AS added_notes",
                "from" => "
                        LEFT OUTER JOIN
                            (SELECT id_utente, COUNT(*) as note from nodo where tipo = " . ADA_NOTE_TYPE . " AND id_istanza = $id_instance GROUP BY id_utente) as notes_write
                        ON utente.id_utente = notes_write.id_utente
                        ",
            ],
            "read_notes" => [
                "select" => "IFNULL( notes_read.note,0) AS read_notes",
                "from" => "
                        LEFT OUTER JOIN
                            (SELECT id_utente_studente, count(*) as note from history_nodi as h LEFT OUTER JOIN nodo as n ON ( h.id_nodo = n.id_nodo) where tipo IN (" . ADA_NOTE_TYPE . ") and  id_istanza = $id_instance group by id_utente_studente) as notes_read
                        ON utente.id_utente = notes_read.id_utente_studente
                        ",
            ],
            "message_count_out" => [
                "select" => " IFNULL( lst_msgs_inv.msgsi,0) AS message_count_out",
                "from" =>   "
                        LEFT OUTER JOIN
                            (SELECT id_mittente, SUM(CASE WHEN messaggi.tipo = '" . ADA_MSG_SIMPLE . "' THEN 1 ELSE 0 END) as msgsi from messaggi group by id_mittente ) as lst_msgs_inv
                        ON utente.id_utente = lst_msgs_inv.id_mittente
                        ",
            ],
            "message_count_in" => [
                "select" => "IFNULL( lst_msgs_ric.msgsr,0) AS message_count_in",
                "from" =>    "
                        LEFT OUTER JOIN
                            (SELECT id_utente, count(*) as msgsr from destinatari_messaggi, messaggi WHERE messaggi.id_messaggio = destinatari_messaggi.id_messaggio AND tipo = '" . ADA_MSG_SIMPLE . "' AND deleted='N' group by id_utente) as lst_msgs_ric
                        ON utente.id_utente = lst_msgs_ric.id_utente
                        ",
            ],
            "chat" => [
                "select" => " IFNULL( lst_chat.chat,0) AS chat",
                "from" =>     "
                        LEFT OUTER JOIN
                        (SELECT id_mittente, SUM(CASE WHEN messaggi.tipo = '" . ADA_MSG_CHAT . "' THEN 1 ELSE 0 END) as chat from chatroom, messaggi where id_istanza_corso = $id_instance and messaggi.id_group=chatroom.id_chatroom GROUP BY id_mittente) as lst_chat
                        ON utente.id_utente = lst_chat.id_mittente
                        ",
            ],
            "bookmarks" => [
                "select" => " IFNULL(lst_bkmrs.bkmrs,0) AS bookmarks",
                "from" =>      "
                        LEFT OUTER JOIN
                            (SELECT id_utente_studente, count(*) as bkmrs from bookmark  WHERE id_istanza_corso = $id_instance group by id_utente_studente) as lst_bkmrs
                        ON utente.id_utente = lst_bkmrs.id_utente_studente
                        ",
            ],
            "index" => [
                "select" => function () use ($requestedColumn, $indexWeights) {
                    $select = [];
                    $fields = [
                        REPORT_COLUMN_HISTORY => 'IFNULL(getterVisite.visite_totali,0)',
                        REPORT_COLUMN_EXERCISES_TEST => 'IFNULL(punteggio.puntitest,0)',
                        REPORT_COLUMN_EXERCISES_SURVEY => 'IFNULL(punteggio.puntisondaggi,0)',
                        REPORT_COLUMN_ADDED_NOTES => 'IFNULL(notes_write.note,0)',
                        REPORT_COLUMN_READ_NOTES => 'IFNULL(notes_read.note,0)',
                        REPORT_COLUMN_MESSAGE_COUNT_IN => 'IFNULL(lst_msgs_ric.msgsr,0)',
                        REPORT_COLUMN_MESSAGE_COUNT_OUT => 'IFNULL(lst_msgs_inv.msgsi,0)',
                        REPORT_COLUMN_CHAT => 'IFNULL(lst_chat.chat,0)',
                        REPORT_COLUMN_BOOKMARKS => 'IFNULL(lst_bkmrs.bkmrs,0)',
                    ];
                    foreach ($fields as $colIndex => $field) {
                        if (array_key_exists($colIndex, $requestedColumn)) {
                            array_push(
                                $select,
                                (isset($indexWeights[$colIndex]) && !is_null($indexWeights[$colIndex]) ? $indexWeights[$colIndex] . ' * ' : '') .
                                    '(' . $field . ')'
                            );
                        }
                    }
                    if (count($select) > 0) {
                        return "(" . implode(' + ', $select) . ") as 'index' ";
                    }
                    return '';
                },
                "from" => "",
            ],
            "level" => [
                "select" => " livello AS 'level'",
                "from" => "",
            ],
            "status" => [
                "select" => " status AS 'status'",
                "from" => "",
            ],

            "test" => [
                "select" => " CONCAT(IFNULL(punteggio.puntitest,0),' su ',IFNULL(lst_max.maxtest,0)) as exercises_test,
                             CONCAT(IFNULL(punteggio.puntisondaggi,0),' su ',IFNULL(lst_max.maxsondaggi,0)) as exercises_survey",
                "from" => "
                    LEFT OUTER JOIN
                        (SELECT
                            ht.`id_utente`,
                            SUM(CASE WHEN LEFT(CAST(t.`tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_TEST . "'   THEN ht.`punteggio_realizzato` ELSE 0 END) as puntitest,
                            SUM(CASE WHEN LEFT(CAST(t.`tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_SURVEY . "' THEN ht.`punteggio_realizzato` ELSE 0 END) as puntisondaggi
                        FROM
                            `" . AMATestDataHandler::$PREFIX . "history_test` ht JOIN `" . AMATestDataHandler::$PREFIX . "nodes` t ON (t.`id_nodo` = ht.`id_nodo`)
                        LEFT OUTER JOIN
                            (SELECT id_nodo, max(punteggio_realizzato) as mass from " . AMATestDataHandler::$PREFIX . "history_test where id_istanza_corso = $id_instance group by id_nodo) innerPunteggio
                                ON (innerPunteggio.id_nodo = t.id_nodo) WHERE   ht.`id_istanza_corso` = $id_instance AND ( ht.`consegnato` = 1 OR ht.`tempo_scaduto` = 1 )
                            GROUP BY ht.`id_utente`) as punteggio
                        ON utente.id_utente = punteggio.id_utente
                        LEFT OUTER JOIN
                            (select id_utente,

                                SUM(CASE
                                    WHEN LEFT(CAST(innerMax.`test_tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_TEST . "' and
                                        (RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_MULTIPLE_CHECK_TEST_TYPE . "' or
                                        RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_LIKERT_TEST_TYPE . "' or
                                        RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_CLOZE_TEST_TYPE . "')
                                    THEN innerMax.sum_punti
                                    WHEN LEFT(CAST(innerMax.`test_tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_TEST . "' and
                                        (RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_STANDARD_TEST_TYPE . "')
                                    THEN innerMax.max_punti
                                    WHEN LEFT(CAST(uu.`tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_TEST . "'
                                    THEN  innerMax.max_punti_domanda
                                    ELSE 0
                                    END) as maxtest,
                                SUM(CASE
                                    WHEN LEFT(CAST(innerMax.`test_tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_SURVEY . "' and
                                        (RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_MULTIPLE_CHECK_TEST_TYPE . "' or
                                        RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_LIKERT_TEST_TYPE . "' or
                                        RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_CLOZE_TEST_TYPE . "')
                                    THEN innerMax.sum_punti
                                    WHEN LEFT(CAST(innerMax.`test_tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_SURVEY . "' and
                                        (RIGHT(LEFT(CAST(innerMax.`tipo` AS CHAR(20)), 2),1)='" . ADA_STANDARD_TEST_TYPE . "')
                                    THEN innerMax.max_punti
                                    WHEN LEFT(CAST(innerMax.`test_tipo` AS CHAR(20)), 1) = '" . ADA_TYPE_SURVEY . "'
                                    THEN innerMax.max_punti_domanda
                                    ELSE 0
                                    END) as maxsondaggi
                            FROM
                                (SELECT t.`tipo`, ht.`id_utente`, ht.`domande`, ht.`punteggio_realizzato`
                                 FROM `" . AMATestDataHandler::$PREFIX . "history_test` ht
                                 JOIN `" . AMATestDataHandler::$PREFIX . "nodes` t ON (t.`id_nodo` = ht.`id_nodo`)
                                 WHERE ht.`id_corso` = $id_course AND ht.`id_istanza_corso` = $id_instance AND ( ht.`consegnato` = 1 OR ht.`tempo_scaduto` = 1 )) as uu,
                                 (SELECT  t.`tipo` as test_tipo, q.`id_nodo`,q.`tipo`, q.`correttezza` as max_punti_domanda, SUM(a.`correttezza`) as sum_punti,MAX(a.`correttezza`) as max_punti
                                  FROM `" . AMATestDataHandler::$PREFIX . "nodes` q
                                  JOIN `" . AMATestDataHandler::$PREFIX . "nodes` a ON (a.`id_nodo_parent` = q.`id_nodo`)
                                  JOIN `" . AMATestDataHandler::$PREFIX . "nodes` t ON (t.`id_nodo` = q.`id_nodo_radice`)
                                  -- WHERE a.id_corso = $id_course OR t.`id_nodo` IN (SELECT `id_test` FROM `" . AMATestDataHandler::$PREFIX . "course_survey` WHERE `id_corso`=$id_course)
                                  GROUP BY t.`tipo`, q.`id_nodo`, q.`tipo`) as innerMax
                            WHERE  (uu.`domande` like CONCAT ('%\"',innerMax.id_nodo,'\"%'))
                            GROUP by uu.id_utente) as lst_max
                            ON (utente.id_utente = lst_max.`id_utente` )",
            ],
        ];

        $test_already_added = false;
        // Build final query
        foreach ($requestedColumn as $column) {
            if ($column == 'exercises_test' || $column == 'exercises_survey') {
                if ($test_already_added == false) {
                    $select .= "," . $allPossibleFields['test']["select"];
                    $from .=  $allPossibleFields['test']["from"];
                    $test_already_added = true;
                }
            } else {
                if (isset($allPossibleFields[$column])) {
                    if (is_callable($allPossibleFields[$column]["select"])) {
                        $select .= "," . call_user_func($allPossibleFields[$column]["select"]);
                    } else {
                        $select .= "," . $allPossibleFields[$column]["select"];
                    }
                    if (is_callable($allPossibleFields[$column]["from"])) {
                        $from .= call_user_func($allPossibleFields[$column]["from"]);
                    } else {
                        $from .=  $allPossibleFields[$column]["from"];
                    }
                }
            }
        }

        $result = $this->getAllPrepared($select . $from . ")", null, AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getPresubscribedStudentsForCourseInstance($id_course_instance)
    {
        $sql = 'SELECT U.*, I.status,I.data_iscrizione,I.laststatusupdate';

        if (ModuleLoaderHelper::isLoaded('CODEMAN')) {
            $sql = $sql . ', I.codice';
        }

        $sql = $sql . ' FROM utente AS U, iscrizioni AS I '
            . ' WHERE I.id_istanza_corso=?'
            . ' AND I.status=?'
            . ' AND U.id_utente = I.id_utente_studente';

        $result = $this->getAllPrepared($sql, [$id_course_instance, ADA_STATUS_PRESUBSCRIBED], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * get_student_visits_for_course_instance
     * Used to fetch an associative array containing informations about
     * user activity in a course instance.
     *
     * @param integer $id_student
     * @param integer $id_course
     * @param integer $id_course_instance
     * @return mixed - an AMADB Error if something goes wrong or an associative array on success
     */
    public function getStudentVisitsForCourseInstance($id_student, $id_course, $id_course_instance)
    {
        $sql_root_node = "SELECT N.id_nodo, N.nome, N.tipo, H.visite AS numero_visite
      FROM (SELECT id_nodo, count(data_visita) AS VISITE FROM history_nodi
      WHERE id_istanza_corso=$id_course_instance AND id_utente_studente=? AND id_nodo=? GROUP BY id_nodo)
	 	                      AS H LEFT JOIN nodo AS N ON (N.id_nodo=H.id_nodo) WHERE N.id_istanza IN (NULL, 0, ?)";
        $result_root_node = $this->getRowPrepared($sql_root_node, [$id_student, $id_course . '_0', $id_course_instance], AMA_FETCH_ASSOC);

        if (AMADB::isError($result_root_node)) {
            return new AMAError(AMA_ERR_GET);
        }

        $nodes_id = [ADA_LEAF_TYPE, ADA_GROUP_TYPE, ADA_NOTE_TYPE];

        //$sql = "SELECT N.nome, H.id_nodo, count(H.id_nodo) AS visite FROM history_nodi AS H LEFT JOIN nodo AS N ON (N.id_nodo=H.id_nodo) WHERE H.id_utente_studente=$id_student AND H.id_istanza_corso=$id_course_instance GROUP BY H.id_nodo ORDER BY visite DESC";
        $sql = "SELECT N.id_nodo, N.nome, N.tipo, visite.numero_visite
      FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent=N2.id_nodo)
      LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
      WHERE id_istanza_corso=? AND id_utente_studente=?
      GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)
      WHERE N.id_nodo LIKE ? AND N.id_istanza IN (NULL, 0, ?) AND N.tipo IN (" . implode(',', $nodes_id) . ") AND N2.tipo IN(" . implode(',', $nodes_id) . ")
	             ORDER BY visite.numero_visite DESC";
        $result = $this->getAllPrepared($sql, [$id_course_instance, $id_student, $id_course . "\_%", $id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result_root_node)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (false !== $result_root_node) {
            array_push($result, $result_root_node);
        }

        /**
         * @author giorgio 16/mag/2013
         *
         * just pushing the root_node will result in an array with root node always at last position,
         * regardless of numer_visite (i.e. visit count). Let's sort the whole array so that the root
         * node will be properly positioned as well.
         */
        usort($result, fn ($a, $b) => $b['numero_visite'] <=> $a['numero_visite']);

        return $result;
    }

    /**
     * get_student_visit_time
     * Used to fetch data about student visit time in a course instance.
     *
     * @param array|string|int $id_student id of a student, or an array of ids.
     * @param int $id_course_instance
     * @return mixed - an AMADB Error if something goes wrong or an associative array on success
     */
    public function getStudentVisitTime($id_student, $id_course_instance)
    {
        if (is_array($id_student)) {
            $sql = "SELECT id_nodo, data_visita, data_uscita, session_id, id_utente_studente " .
            "FROM history_nodi WHERE id_utente_studente IN (" . implode(',', $id_student) . ") AND id_istanza_corso=$id_course_instance ORDER BY session_id,data_uscita ASC";
        } else {
            $sql = "SELECT id_nodo, data_visita, data_uscita, session_id " .
            "FROM history_nodi WHERE id_utente_studente=? AND id_istanza_corso=? ORDER BY session_id,data_uscita ASC";
        }

        $result = $this->getAllPrepared($sql, [$id_student, $id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * get_last_visited_nodes_in_period
     * Used to get last visited nodes for a student in a time period
     *
     * @param int $id_student
     * @param int $id_course_instance
     * @param int $period
     * @return - an AMADB Error if something goes wrong or an associative array on success
     */
    public function getLastVisitedNodesInPeriod($id_student, $id_course_instance, $period)
    {
        $sql = "SELECT H.id_nodo, N.nome, N.tipo, H.data_visita, H.data_uscita
      FROM history_nodi AS H LEFT JOIN nodo AS N ON (N.id_nodo=H.id_nodo)
      WHERE H.id_utente_studente=?
      AND H.id_istanza_corso=?
      AND H.data_visita >= ?
      ORDER BY H.data_uscita DESC, H.data_visita DESC";
        $result = $this->getAllPrepared($sql, [$id_student, $id_course_instance, $period], AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * get_last_visited_nodes
     * Used to get last visited $num_visits nodes for a student
     *
     * @param int $id_student
     * @param int $id_course_instance
     * @param int $num_visits
     * @return - an AMADB Error if something goes wrong or an associative array on success
     */
    public function getLastVisitedNodes($id_student, $id_course_instance, $num_visits)
    {
        $sql = "SELECT H.id_nodo, N.nome, N.tipo, H.data_visita, H.data_uscita
      FROM history_nodi AS H LEFT JOIN nodo AS N ON (N.id_nodo=H.id_nodo)
      WHERE H.id_utente_studente=?
      AND H.id_istanza_corso=?
      ORDER BY H.data_uscita DESC, H.data_visita DESC LIMIT " . (int) $num_visits;
        $result = $this->getAllPrepared($sql, [$id_student, $id_course_instance], AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Methods accessing table `iscrizioni`
     */
    // MARK: Methods accessing table `iscrizioni`
    public function courseInstanceSubscribedStudentsCount($id_istanza_corso)
    {
        $sql = 'SELECT count(id_utente_studente) FROM iscrizioni WHERE id_istanza_corso=? AND (status=? OR status=?)';
        $values = [
            $id_istanza_corso,
            ADA_STATUS_SUBSCRIBED,
            ADA_STATUS_TERMINATED,
        ];

        $result = $this->getOnePrepared($sql, $values);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * pre-subscribe a student
     *
     * @access public
     *
     * @param $id_studente - student id
     * @param $id_corso    - course instance id
     * @param $livello     - level of subscription (0=beginner, 1=intermediate, 2=advanced)
     *
     * @return bool|AMAError true on success, an AMAError object if something goes wrong
     */
    public function courseInstanceStudentPresubscribeAdd($id_istanza_corso, $id_studente, $livello = 0)
    {
        // verify key uniqueness (index)
        $sql = "select id_istanza_corso from iscrizioni where id_istanza_corso=? and id_utente_studente=?";
        $id =  $this->getOnePrepared($sql, [$id_istanza_corso, $id_studente]);

        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }

        if ($id) {
            return new AMAError(AMA_ERR_UNIQUE_KEY);
        }
        $data_iscrizione = time();
        // insert a row into table iscrizioni
        $sql1 =  "insert into iscrizioni (id_utente_studente, id_istanza_corso, livello, status,data_iscrizione,laststatusupdate)";
        $sql1 .= " values (?, ?, ?, ?, ?, ?);";
        $res = $this->queryPrepared($sql1, [$id_studente, $id_istanza_corso, $livello, ADA_STATUS_PRESUBSCRIBED, $data_iscrizione, $data_iscrizione]);
        // FIXME: usare executeCritical?
        if (AMADB::isError($res)) { // || $db->affectedRows()==0)
            return new AMAError(AMA_ERR_ADD);
        }
        return true;
    }

    /**
     * Add a whole lot of students pre-subscriptions
     *
     * @access public
     *
     * @param $id_course_instance      the unique id of the course  instance
     *
     * @param $studenti_ar    the array containing the ids of the students to be added
     *
     * @return the number of students successfully added
     *
     */
    public function courseInstanceStudentsPresubscribeAdd($id_course_instance, $studenti_ar)
    {
        $successfully_added = 0;

        for ($i = 0; $i < count($studenti_ar); $i++) {
            $res = $this->courseInstanceStudentPresubscribeAdd($id_course_instance, $studenti_ar[$i]);
            if (!AMADataHandler::isError($res)) {
                $successfully_added++;
            }
        }

        return $successfully_added;
    }

    /**
     * Remove a whole lot of students pre-subscriptions
     *  The record is removed from table iscrizioni.
     * @access public
     *
     * @param $id_course_instance      the unique id of the course  instance
     *
     * @param $studenti_ar    the array containing the ids of the students to be removed
     *
     * @return the number of students successfully removed
     *
     */
    public function courseInstanceStudentsPresubscribeRemove($id_course_instance, $studenti_ar)
    {
        $successfully_removed = 0;

        for ($i = 0; $i < count($studenti_ar); $i++) {
            $res = $this->courseInstanceStudentPresubscribeRemove($id_course_instance, $studenti_ar[$i]);
            if (!AMADataHandler::isError($res)) {
                $successfully_removed++;
            }
        }

        return $successfully_removed;
    }

    /**
     * Removes all the subscriptions to a given course instance
     *
     * @param integer $id_course_instance
     * @return true on success, an AMAError object on failure
     */
    public function courseInstanceStudentsSubscriptionsRemoveAll($id_course_instance)
    {
        $sql = "delete from iscrizioni where id_istanza_corso=?";
        $result = $this->queryPrepared($sql, [$id_course_instance]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }

        return true;
    }

    /**
     * Remove a student pre-subscription
     * The record is removed from table iscrizioni.
     *
     * @access public
     *
     * @param $id_studente   the id of the student
     * @param $id_corso      the unique id of the course  instance
     *
     * @return an Error object if something goes wrong, true on success
     *
     */
    public function courseInstanceStudentPresubscribeRemove($id_istanza_corso, $id_studente)
    {
        $sql = "delete from iscrizioni where id_utente_studente=? and id_istanza_corso=?";
        $res = $this->queryPrepared($sql, [$id_studente, $id_istanza_corso]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_REMOVE);
        }

        $sql = 'SELECT count(id_utente_studente) FROM iscrizioni'
            . " WHERE id_istanza_corso=?"
            . ' AND status IN (' . ADA_STATUS_SUBSCRIBED . ',' . ADA_STATUS_TERMINATED . ')';

        $res = $this->getOnePrepared($sql, [$id_istanza_corso]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $res;
    }

    /**
     * Set the level of students for instance course
     *
     * @access public
     *
     * @param $id_course_instance      the unique id of the course  instance
     * @param $studenti_ar             Student id array
     * @param $level                   the student levet to update
     *
     *
     * @return true on success, an AMAError object on failure
     *
     */
    public function setStudentLevel($id_course_instance, $studenti_ar, $level)
    {
        $params = [];
        $n = count($studenti_ar);
        if ($n > 0) {
            $sql = "update iscrizioni set livello=? where id_istanza_corso=?";
            $params[] = $level;
            $params[] = $id_course_instance;
        } else {
            return 0;
        }

        for ($i = 0; $i < $n; $i++) {
            $studente = $studenti_ar[$i];
            $sql .= " and id_utente_studente=? ";
            $params[] = $studente;
        }

        // update the records
        $affected_rows = $this->queryPrepared($sql, $params);
        if (AMADB::isError($affected_rows)) {
            return $affected_rows;
        }

        return $affected_rows;
    }

    /**
     * Return the subscription status of all the students in a given cource instances
     *
     * @access public
     *
     * @param $id_course_instance     the unique id of the course instance
     *
     *
     * @return   on success, an array of hash containing the subscription statuses for all
     *           subscribed students
     *           For each element, infos are organized this way:
     *               KEY                  VALUE
     *           - id_studente           the id of the student
     *           - id_istanza_corso      the id of the course instance
     *           - livello               the level of the course
     *           - status                the actual status of subscription
     *
     *           on failure, an AMAError object
     *
     */
    public function courseInstanceStudentsPresubscribeGetList($id_course_instance, $status = "")
    {
        $sql_clause = "select * from iscrizioni where id_istanza_corso=?";
        $params = [$id_course_instance];

        if ($status == "") {
            $clause = "";  // 1 OR 2
        } elseif ($status == ADA_STATUS_SUBSCRIBED) {
            $clause = ' and (status IN (' . ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED . ',' .
                ADA_STATUS_SUBSCRIBED . ',' . ADA_STATUS_TERMINATED . '))';
        } else {
            $clause = " and status =?";
            $params[] = $status;
        }
        $sql_clause .= $clause;
        // do the query
        $students_ar =  $this->getAllPrepared($sql_clause, $params);
        if (AMADB::isError($students_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        $n = count($students_ar);
        if ($n > 0) {
            foreach ($students_ar as $key => $value) {
                $res_ar[$key]['id_utente_studente'] = $value[0];
                // $res_ar[$key]['istanza_corso'] = $value[1];
                $res_ar[$key]['livello'] = $value[2];
                $res_ar[$key]['status'] = $value[3];
                //   echo    $value[0]." ". $value[2]." ". $value[3]."<br>";
            }
            /*  modificato il 9/08/2001
       for($i=0; $i<$n; $i++){
       $res_ar[$i]['id_studente'] = $students_ar[$i][0];
       $res_ar[$i]['livello'] = $students_ar[$i][2];
       $res_ar[$i]['status'] = $students_ar[$i][3];
       }
            */
            return $res_ar;
        }

        return 0;
    }

    /**
     *
     * @param $id_student
     * @param $id_course_instance
     * @return unknown_type
     */
    public function studentCanSubscribeToCourseInstance($id_student, $id_course_instance)
    {
        $already_subscribed_sql = "SELECT id_utente_studente FROM iscrizioni
                               WHERE id_utente_studente = ?
                               AND id_istanza_corso = ?";

        $result = $this->getRowPrepared($already_subscribed_sql, [$id_student, $id_course_instance]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!is_array($result)) {
            $students_subscribed_sql = "SELECT count(id_utente_studente) FROM iscrizioni
      							WHERE id_istanza_corso=?";
            // TODO:verificare modifica apportata, passiamo da getCol a getOne
            $students_subscribed = $this->getOnePrepared($students_subscribed_sql, [$id_course_instance]);
            if (AMADB::isError($students_subscribed)) {
                return new AMAError(AMA_ERR_GET);
            }

            return $students_subscribed;
        }

        return false;
    }

    /**
     * Return the subscription status of a student
     *
     * @access public
     *
     * @param $id_student     the unique id of the student
     *
     *
     * @return   an array of hash containing the course_instances
     *           For each element, infos are organized this way:
     *               KEY                  VALUE
     *           - id_istanza_corso      the id of the course instance
     *           - status                the actual status of subscription
     *
     */
    public function courseInstanceStudentPresubscribeGetStatus($id_student)
    {
        // do the query
        $students_ar =  $this->getAllPrepared("select * from iscrizioni where id_utente_studente=?", [$id_student]);
        if (AMADB::isError($students_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        $n = count($students_ar);
        if ($n > 0) {
            foreach ($students_ar as $key => $value) {
                //   $res_ar[$key]['id_utente_studente'] = $value[0];
                $res_ar[$key]['istanza_corso'] = $value[1];
                //  $res_ar[$key]['livello'] = $value[2];
                $res_ar[$key]['status'] = $value[3];
                //   echo    $value[0]." ". $value[2]." ". $value[3]."<br>";
            }
            return $res_ar;
        }
        return 0;
    }

    /**
     * Subscribe a set of students to the course instance.
     * i.e: Set the status of all the students to 2 (definitevly subscribed)
     *
     * @access public
     *
     * @param $id_course_instance       the unique id of the course  instance
     *
     * @param $studenti_ar    the array containing the ids of the students to be removed
     *
     * @return the number of students successfully subscribed
     *
     */
    public function courseInstanceStudentsSubscribe($id_course_instance, $studenti_ar, $status = 2)
    {
        $student_subscribed = 0;
        foreach ($studenti_ar as $student) {
            $res = $this->courseInstanceStudentSubscribe($id_course_instance, $student, $status);
            // FIXME: verificare se bisogna ritornare errore o lasciare continuare l'iscrizione degli altri utenti
            if (AMADataHandler::isError($res)) {
                return $res;
            }
            $student_subscribed++;
        }
        return  $student_subscribed;
    }

    /**
     *
     * @param $id_course_instance
     * @param $student
     * @param $status
     * @param $user_level if null than this field is not updated
     * @return unknown_type
     */
    public function courseInstanceStudentSubscribe($id_course_instance, $student, $status = 2, $user_level = 1, $lastupdateTS = null)
    {
        if (is_null($lastupdateTS)) {
            $lastupdateTS = time();
        }
        $values = [
            'status' => $status,
            'laststatusupdate' => $lastupdateTS,
            'id_istanza_corso' => $id_course_instance,
            'id_utente_studente' => $student,
        ];
        $sql = "update iscrizioni set status=:status, laststatusupdate=:laststatusupdate";
        if (!is_null($user_level)) {
            $sql .= ", livello=:livello";
            $values['livello'] = $user_level;
        }
        $sql .= " where id_istanza_corso=:id_istanza_corso and id_utente_studente=:id_utente_studente";
        $res = $this->queryPrepared($sql, $values);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * Unsubscribe a set of students from an instance course.
     * i.e.: set the status of the students back to 1.
     * (to effectively remove the students from table iscrizioni, use
     * course_instance_students_presubscribe_remove)
     *
     * @access public
     *
     * @param $id_corso      the unique id of the course  instance
     *
     * @param $studenti_ar    the array containing the ids of the students to be removed
     *
     * @return the number of students successfully 'removed'
     *
     */
    public function courseInstanceStudentsUnsubscribe($id_corso, $studenti_ar)
    {
        $params = [];
        $n = count($studenti_ar);
        if ($n > 0) {
            $lastupdateTS = time();
            $sql = "update iscrizioni set status=1, laststatusupdate=? where id_istanza_corso=? ";
            $params[] = $lastupdateTS;
            $params[] = $id_corso;
        } else {
            return 0;
        }

        for ($i = 0; $i < $n; $i++) {
            $studente = $studenti_ar[$i];
            $sql .= " and id_utente_studente=? ";
            $params[] = $studente;
        }
        $affected_rows = $this->queryPrepared($sql, $params);
        if (AMADB::isError($affected_rows)) {
            return $affected_rows;
        }
        return $affected_rows;
    }

    /**
     * Check if a student is subscribed to a course  and return the type of subscription
     *
     * @access public
     *
     * @param $id_studente student id
     * @param $id_corso    course model id
     *
     * @return an hash containing the following info
     *  istanza_id - id of the instance course the student is subscribed to
     *  istanza_ha - the course instance the student is subscribed to (a hash)
     *  tipo       - type of subscription
     *               0 - no subscription
     *               1 - presubscription
     *               2 - subscription
     *  livello    - the level of the course
     */
    // vito, 2 apr 2009
    //function &get_subscription($id_studente, $id_corso)
    public function &getSubscription($id_studente, $id_istanza_corso)
    {
        // vito, 2 apr 2009
        $sql =  "select ic.id_istanza_corso, ic.data_inizio, ic.durata, ic.data_inizio_previsto, isc.livello, isc.status ";
        $sql .= " from istanza_corso as ic,  iscrizioni as isc ";
        $sql .= " where isc.id_utente_studente=? ";
        $sql .= " and isc.id_istanza_corso=? ";
        $sql .= " and ic.id_istanza_corso=?";
        //$sql .= " and isc.id_istanza_corso=ic.id_istanza_corso";

        $res_ar =  $this->getRowPrepared($sql, [$id_studente, $id_istanza_corso, $id_istanza_corso]);
        if (AMADB::isError($res_ar)) {
            $err = new AMAError(AMA_ERR_GET);
            return $err;
        }
        if (is_array($res_ar)) {
            $ret_ha['istanza_id'] = $res_ar[0];
            $ret_ha['istanza_ha']['data_inizio'] = $res_ar[1];
            $ret_ha['istanza_ha']['durata'] = $res_ar[2];
            $ret_ha['istanza_ha']['data_inizio_previsto'] = $res_ar[3];
            $ret_ha['livello'] = $res_ar[4];
            $ret_ha['tipo'] = $res_ar[5];
            return $ret_ha;
        }
        // vito, 7 luglio 2009, se non è un array allora non ho ottenuto i dati che
        // mi servivano e restituisco un errore
        $err = new AMAError(AMA_ERR_NOT_FOUND);
        return $err;
    }

    public function getCourseInstancesForThisStudent($id_student, $extra_fields = false)
    {
        $sql = 'SELECT C.id_corso, C.titolo, C.crediti, IC.id_istanza_corso,'
            . ' IC.data_inizio, IC.durata, IC.data_inizio_previsto, IC.data_fine, I.status';
        if ($extra_fields) {
            $sql .= ' ,IC.title,I.data_iscrizione,IC.duration_subscription, C.tipo_servizio, IC.self_instruction, IC.tipo_servizio as `istanza_tipo_servizio`';
        }
        $sql .= ' FROM modello_corso AS C, istanza_corso AS IC, iscrizioni AS I'
            . ' WHERE I.id_utente_studente=?'
            . ' AND IC.id_istanza_corso = I.id_istanza_corso'
            . ' AND C.id_corso = IC.id_corso';
        $valuesAr = [
            $id_student,
        ];

        $result = $this->getAllPrepared($sql, $valuesAr, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    public function getCourseInstancesActiveForThisStudent($id_student)
    {
        $currentTime = time();
        $sql = 'SELECT C.id_corso, C.titolo, IC.id_istanza_corso, IC.self_instruction,'
            . ' IC.data_inizio, IC.durata, IC.data_inizio_previsto, IC.data_fine, I.status, C.crediti,'
            . ' I.data_iscrizione, IC.duration_subscription, C.tipo_servizio'
            . ' FROM modello_corso AS C, istanza_corso AS IC, iscrizioni AS I'
            . ' WHERE I.id_utente_studente=?'
            . ' AND IC.id_istanza_corso = I.id_istanza_corso'
            . ' AND C.id_corso = IC.id_corso'
            . ' AND IC.data_fine > ?'
            . ' AND IC.data_fine > 0';
        $valuesAr = [
            $id_student,
            $currentTime,
        ];

        $result = $this->getAllPrepared($sql, $valuesAr, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    public function getIdCourseInstancesForThisStudent($id_student)
    {
        $sql = 'SELECT id_istanza_corso'
            . ' FROM iscrizioni'
            . ' WHERE id_utente_studente=?';
        $valuesAr = [
            $id_student,
        ];

        $result = $this->getColPrepared($sql, $valuesAr); //,AMA_FETCH_ASSOC);
        //        $result = $this->getAll($sql) ;
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    // vito, 2 apr 2009
    public function &getCourseInstanceForThisStudentAndCourseModel($id_student, $id_course, $getAll = false)
    {
        $sql  =  "select ic.id_istanza_corso, ic.data_inizio, ic.durata, ic.data_inizio_previsto, isc.livello, isc.status  ";
        $sql .= " from istanza_corso as ic,  iscrizioni as isc ";
        $sql .= " where ic.id_corso=? ";
        $sql .= " and isc.id_istanza_corso=ic.id_istanza_corso";
        $sql .= " and isc.id_utente_studente=? ";

        if ($getAll === false) {
            $result = $this->getRowPrepared($sql, [$id_course, $id_student]);
        } else {
            $result = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
        }

        if (AMADB::isError($result)) {
            $result = new AMAError(AMA_ERR_GET);
        }

        if (!is_array($result)) {
            $result = new AMAError(AMA_ERR_NOT_FOUND);
        }

        if (!AMADB::isError($result) && $getAll === false) {
            $ret_ha['istanza_id'] = $result[0];
            $ret_ha['istanza_ha']['data_inizio'] = $result[1];
            $ret_ha['istanza_ha']['durata'] = $result[2];
            $ret_ha['istanza_ha']['data_inizio_previsto'] = $result[3];
            $ret_ha['livello'] = $result[4];
            $ret_ha['tipo'] = $result[5];
            return $ret_ha;
        }
        return $result;
    }

    /**
     * Methods accessing table `istanza_corso`
     */
    // MARK: Methods accessing table `istanza_corso`

    /**
     * Detect if a course has instances or not
     *
     * @access public
     *
     * @param $model_id the unique id of the course model
     *
     * @return true if it has instances, false otherwise
     *         an error if something get wrong
     */
    public function courseHasInstances($model_id)
    {
        ADALogger::logDb("entered course_has_instances (model_id: $model_id)");

        $get_instances_count_sql = 'SELECT COUNT(id_istanza_corso) FROM istanza_corso WHERE id_corso=?';

        $instances_count = $this->getOnePrepared($get_instances_count_sql, [$model_id]);
        if (AMADB::isError($instances_count)) {
            ADALogger::logDb('Error obtaining instances count for course model : ' . $model_id . '.' . $instances_count->message);
            return new AMAError(AMA_ERR_GET); // era AMA_ERR
        }

        if ($instances_count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Add an istance of a course to the table istanza_corso
     * An instance of a course is created by the administrator to make the course
     * available to the students for subscriptions.
     * Only the field data_inizio_previsto is filled at this time.
     * A course is said to be published when an instance of it exist.
     *
     * A class is formed when the fields data_inizio and durata are also filled.
     * At this moment the instance is said to be instituted.
     *
     * This method can be invoked _automatically_ by a script while creating a new class
     * in case the students are too many for a single class.
     *
     *
     * @access public
     *
     * @param $id_corso    - course model the instance originates from
     * @param $istanza_ha  - variables of the instance
     *
     *  data_inizio           - starting date
     *  durata                - duration (in days)
     *  data_inizio_previsto  - supposed starting date
     *  id_layout         - tpl+css
     *
     * @return an AMAError object if something goes wrong, true on success
     */
    public function courseInstanceAdd($id_corso, $istanza_ha)
    {
        // prepare values
        $data_inizio = $this->orZero($istanza_ha['data_inizio'] ?? '');
        $durata = $this->orZero($istanza_ha['durata'] ?? '');
        $data_inizio_previsto = $this->orZero($istanza_ha['data_inizio_previsto'] ?? '');
        $id_layout = $this->orZero($istanza_ha['id_layout'] ?? '');
        $self_instruction = $istanza_ha['self_instruction'];
        $self_registration = $istanza_ha['self_registration'];
        $price = $this->orZero($istanza_ha['price']);
        $title = trim($istanza_ha['title']);
        $duration_subscription = $this->orZero($istanza_ha['duration_subscription']);
        $start_level_student = $this->orZero($istanza_ha['start_level_student']);
        $open_subscription = $istanza_ha['open_subscription'];
        $duration_hours = $this->orZero($istanza_ha['duration_hours']);
        $tipo_servizio = $this->orNull($istanza_ha['service_level']);

        // check value of supposed starting date (cannot be empty)
        if (empty($data_inizio_previsto)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in course_instance_add " .
                AMA_SEP . ": empty supposed starting date");
        }

        // vito, 17 apr 2009, set the end date of this course instance
        $data_fine = 0;
        if (empty($data_inizio)) {
            $data_fine = $this->addNumberOfDays($durata, $data_inizio_previsto);
        } else {
            $data_fine = $this->addNumberOfDays($durata, $data_inizio);
        }
        /**
         * giorgio 13/01/2021: force data_fine to have time set to 23:59:59
         */
        $data_fine = strtotime('tomorrow midnight', $data_fine) - 1;

        // check if corso exists
        $sql  = "select id_corso from modello_corso where id_corso=?";
        $res = $this->getOnePrepared($sql, [$id_corso]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (!$res) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in course_instance_add " .
                AMA_SEP . ": the course model ($id_corso) does not exist!");
        }

        // add the record
        // vito, 17 apr 2009, added data_fine
        $sql  = "insert into istanza_corso (id_corso, data_inizio, durata, " .
            "data_inizio_previsto,id_layout,data_fine, price, self_instruction, " .
            "self_registration, title, duration_subscription, start_level_student, " .
            "open_subscription, duration_hours, tipo_servizio)";
        $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $res = $this->executeCriticalPrepared($sql, [
            $id_corso,
            $data_inizio,
            $durata,
            $data_inizio_previsto,
            $id_layout,
            $data_fine,
            $price,
            $self_instruction,
            $self_registration,
            $title,
            $duration_subscription,
            $start_level_student,
            $open_subscription,
            $duration_hours,
            $tipo_servizio,
        ]);
        if (AMADB::isError($res)) {
            return $res;
        }
        return $this->lastInsertID();
    }

    /**
     * Remove a course from the DB
     *
     * @access public
     *
     * @param $id the unique id of the course
     *
     * @return an AMAError object or a DB_Error object if something goes wrong,
     *      true on success
     *
     * @note referential integrity is checked against table iscrizioni
     */
    public function courseInstanceRemove($id_istanza)
    {
        ADALogger::logDb("entered course_instance_remove (id_istanza:$id_istanza)");

        // referential integrity checks
        $ri_id = $this->getOnePrepared("select id_utente_studente from iscrizioni where id_istanza_corso=?", [$id_istanza]);
        if ($ri_id) {
            ADALogger::logDb("got at least one student (uid: $ri_id) still subscribed to this instance, blocking removal");
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        $ri_id = $this->getOnePrepared("select id_utente_tutor from tutor_studenti where id_istanza_corso=?", [$id_istanza]);
        if ($ri_id) {
            ADALogger::logDb("got at least one tutor (uid: $ri_id) still assigned to this instance, blocking removal");
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        // get id of course model
        $model_id = $this->getOnePrepared("select id_corso from istanza_corso where id_istanza_corso=?", [$id_istanza]);
        if (AMADB::isError($model_id)) {
            ADALogger::logDb("error detected: " . $model_id->message);
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("model is $model_id");

        $sql = "delete from istanza_corso where id_istanza_corso=?";
        ADALogger::logDb("deleting instance: $sql");

        $res = $this->executeCriticalPrepared($sql, [$id_istanza]);
        if (AMADB::isError($res)) {
            return $res;
        }

        // retrieve subscribed students
        $uids = $this->getColPrepared("select id_utente_studente from iscrizioni where id_istanza_corso=?", [$id_istanza]);

        if (AMADB::isError($uids)) {
            ADALogger::logDb("error detected: " . $uids->getMessage());
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("got " . count($uids) . " users");

        // loop
        foreach ($uids as $uid) {
            // delete all notes authored by uid
            $id_node_prefix = $model_id . "\_%";
            $sql = "delete from nodo where id_nodo like ? and tipo=? and id_utente=?";
            ADALogger::logDb("removing all notes authored by user $uid: $sql");
            $res = $this->queryPrepared($sql, [$id_node_prefix, ADA_NOTE_TYPE, $uid]);
            if (AMADB::isError($res)) {
                ADALogger::logDb("error detected: " . $res->message);
                return new AMAError(AMA_ERR_REMOVE);
            }
            ADALogger::logDb("deleted!");
        }

        ADALogger::logDb("course instance successfully removed");
        return true;
    }

    /**
     * Get all informations about the users subscribed the course instances
     *
     * @access public
     *
     * @param $id the course's id
     *
     * @return an array containing all the informations about users
     *   corso                - course model the instance is originated from
     *
     */
    public function courseUsersInstanceGet($id)
    {
        ADALogger::logDb("course_users_instance_get (id_corso:$id)");

        $sql = 'SELECT distinct U.id_utente, U.nome, U.cognome, U.username, U.codice_fiscale,IC.id_corso, I.id_utente_studente, I.id_istanza_corso, IC.data_inizio, I.status FROM
        iscrizioni AS I, istanza_corso AS IC, utente AS U
        WHERE IC.id_corso = ? AND I.id_istanza_corso = IC.id_istanza_corso AND U.id_utente = I.id_utente_studente order by U.cognome';
        $result = $this->getAllPrepared($sql, [$id], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Get all informations about a course instance
     *
     * @access public
     *
     * @param $id the course's id
     *
     * @return an array containing all the informations about a course
     *   corso                - course model the instance is originated from
     *   data_inizio          - starting date
     *   durata               - duration of the course (in days)
     *   data_inizio_previsto - supposed starting date
     *
     */
    // vito,20 apr 2009, added argument $with_end_date.
    public function courseInstanceGet($id, $with_end_date = false)
    {
        // get a row from table istanza_corso
        $sql = "select id_corso, data_inizio, durata, data_inizio_previsto, id_layout, data_fine, status, " .
            "price, self_instruction, self_registration, title, duration_subscription, start_level_student, " .
            "open_subscription, duration_hours, tipo_servizio as `service_level` " .
            "from istanza_corso where id_istanza_corso=?";
        $result = $this->getRowPrepared($sql, [$id], AMA_FETCH_ASSOC);
        //        print_r($result);

        //        $res_ar =  $db->getRow("select id_corso, data_inizio, durata, data_inizio_previsto, id_layout, data_fine, status, " .
        //                               "price, self_istruction, self_registration, title, duration_subscription, start_level_student from istanza_corso where id_istanza_corso=$id");
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!is_array($result)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        /*
                // queste andrebbero trasformate in interi e date (devo fare le funzioni di conversione da stringa)
                $res_ha['id_corso']              = $res_ar[0];
                $res_ha['data_inizio']           = $res_ar[1];
                $res_ha['durata']                = $res_ar[2];
                $res_ha['data_inizio_previsto']  = $res_ar[3];
                $res_ha['id_layout']             = $res_ar[4];
                if ($with_end_date) {
                    $res_ha['data_fine'] = $res_ar[5];
                }
                $res_ha['status']                 = $res_ar[6];
                return $res_ha;
         *
         */
        return $result;
    }

    /**
     * Get informations about a course instance status
     *
     * @access public
     *
     * @param $id the course's id
     *
     * @return an integer
     *   0         private
     *   1         reserved
     *   2         public
     *
     */
    public function courseInstanceStatusGet($id)
    {
        // get a row from table istanza_corso
        $res_ar =  $this->getRowPrepared("select id_corso, status from istanza_corso where id_istanza_corso=?", [$id]);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!is_array($res_ar)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $res = $res_ar[1];
        return $res;
    }

    /**
     * Find those course instances verifying the given criterium
     *
     * @access public
     *
     * @param  $field_list_ar an array containing the desired fields' names
     *         possible values are: ID_CORSO, DATA_INIZIO, DURATA, DATA_INIZIO_PREVISTO,
     *         The value of field ID_ISTANZA_CORSO is always returned
     *
     * @param  $clause the clause string which will be added to the select
     *
     * @return array nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &courseInstanceFindList($field_list_ar, $clause = '')
    {
        $more_fields = '';

        // build comma separated string out of $field_list_ar array
        if (is_array($field_list_ar) && count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }

        // handle null clause, too
        if ($clause) {
            $clause = 'where ' . $clause;
        }

        // do the query
        //echo "select id_istanza_corso$more_fields from istanza_corso $clause";
        $query = "select id_istanza_corso$more_fields from istanza_corso $clause";
        $courses_ar =  $this->getAllPrepared($query, null, AMA_FETCH_BOTH);
        if (AMADB::isError($courses_ar)) {
            $courses_ar = new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array
        //
        return $courses_ar;
    }

    /**
     * get a list of all courses instances originated from a given course model
     * if the $id_course is not given, then all the instances of all the courses are returned
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param $id_course the course model id
     *
     * @return array|AMAError a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &courseInstanceGetList($field_list_ar, $id_corso = '')
    {
        if ($id_corso) {
            return $this->courseInstanceFindList($field_list_ar, "id_corso=$id_corso");
        } else {
            return $this->courseInstanceFindList($field_list_ar);
        }
    }

    /**
     * get a list of all published courses instances
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param $id_course the course model id
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &courseInstancePublishedGetList($field_list_ar)
    {
        return $this->courseInstanceFindList($field_list_ar, "data_inizio_previsto is not null and data_inizio is null and durata is null");
    }

    public function courseInstanceSubscribeableGetList($field_list_ar, $courseId)
    {
        $today_date = Utilities::todayDateFN();
        $timestamp = AMADataHandler::dateToTs($today_date);
        //        $timestamp = time();
        //        return $this->course_instance_find_list($field_list_ar, "id_corso=$courseId AND self_registration=1 AND data_inizio=0 AND data_inizio_previsto >= $timestamp and durata > 0  ORDER BY data_inizio_previsto ASC");
        return $this->courseInstanceFindList($field_list_ar, "id_corso=$courseId AND self_registration=1 AND open_subscription=1 ORDER BY data_inizio_previsto DESC");
    }

    /**
     * get a list of all started courses instances
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param $id_course the course model id
     *
     * @return array a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &courseInstanceStartedGetList($field_list_ar, $id_corso = '')
    {
        if (strlen($id_corso) <= 0) {
            return $this->courseInstanceFindList($field_list_ar, "data_inizio is not null and durata is not null");
        } else {
            return $this->courseInstanceFindList($field_list_ar, "id_corso=$id_corso AND data_inizio is not null and durata is not null");
        }
    }

    /**
     * Updates informations related to a course instance
     *
     * @access public
     *
     * @param $id the course's id
     *
     * @param $istanza_ha the hash containing the updating info (empty fields are not updated)
     *
     *  data_inizio           - starting date
     *  durata                - duration (in days)
     *  data_inizio_previsto  - supposed starting date
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     */
    public function courseInstanceSet($id, $istanza_ha)
    {
        // prepare values
        $data_inizio = $this->orNull($istanza_ha['data_inizio']);
        $durata = $this->orZero($istanza_ha['durata']);
        $data_inizio_previsto = $this->orZero($istanza_ha['data_inizio_previsto']);
        $self_instruction = $istanza_ha['self_instruction'];
        $self_registration = $istanza_ha['self_registration'];
        $price = $this->orZero($istanza_ha['price']);
        $title = trim($istanza_ha['title']);
        $duration_subscription = $this->orZero($istanza_ha['duration_subscription']);
        $start_level_student = $this->orZero($istanza_ha['start_level_student']);
        $open_subscription = $istanza_ha['open_subscription'];
        $duration_hours = $this->orZero($istanza_ha['duration_hours']);
        $tipo_servizio = $this->orNull($istanza_ha['service_level']);


        // check value of supposed starting date (cannot be empty)
        if (empty($data_inizio_previsto)) {
            return new AMAError($this->errorMessage(AMA_ERR_UPDATE) . " in course_instance_set " .
                AMA_SEP . ": empty supposed starting date");
        }

        $data_fine = 0;
        if ($data_inizio == "NULL") {
            $data_fine = $this->addNumberOfDays($durata, $data_inizio_previsto);
        } else {
            $data_fine = $this->addNumberOfDays($durata, $data_inizio);
        }
        /**
         * giorgio 13/01/2021: force data_fine to have time set to 23:59:59
         */
        $data_fine = strtotime('tomorrow midnight', $data_fine) - 1;

        // verify that the record exists
        $res_id =  $this->getRowPrepared("select id_istanza_corso from istanza_corso where id_istanza_corso=?", [$id]);
        if (AMADB::isError($res_id)) {
            return new AMAError(AMA_ERR_GET);
        }

        if ($res_id == 0) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $sql  = "update istanza_corso set data_inizio=?, durata=?, data_inizio_previsto=?, ";
        $sql .= "data_fine=?, self_instruction=?, title=?, self_registration=?, ";
        $sql .= "price=?, duration_subscription=?, start_level_student=?, open_subscription=?, ";
        $sql .= "duration_hours=?, tipo_servizio=? where id_istanza_corso=?";
        $res = $this->queryPrepared($sql, [
            $data_inizio,
            $durata,
            $data_inizio_previsto,
            $data_fine,
            $self_instruction,
            $title,
            $self_registration,
            $price,
            $duration_subscription,
            $start_level_student,
            $open_subscription,
            $duration_hours,
            $tipo_servizio,
            $id,
        ]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_UPDATE);
        } else {
            if (intval($data_inizio) > 0) {
                $this->updateStudentsSubscriptionAfterCourseInstanceSet($id, intval($duration_subscription));
            }
        }

        return true;
    }

    /**
     * update students subscription status in the passed instance to
     * either SUBSCRIBED or TERMINATED as appropriate, checking if
     * $duration_subscription + student subscription date is in the past or not
     *
     * @param number $instance_id
     * @param number $duration_subscription
     *
     * @author giorgio 02/apr/2015
     */
    private function updateStudentsSubscriptionAfterCourseInstanceSet($instance_id, $duration_subscription)
    {
        $subscriptions = Subscription::findSubscriptionsToClassRoom($instance_id);
        if (!AMADB::isError($subscriptions) && is_array($subscriptions) && count($subscriptions) > 0) {
            foreach ($subscriptions as $subscription) {
                $updateSubscription = false;
                $subscritionEndDate = $this->addNumberOfDays($duration_subscription, intval($subscription->getSubscriptionDate()));
                /**
                 * giorgio 13/01/2021: force subscritionEndDate to have time set to 23:59:59
                 */
                $subscritionEndDate = strtotime('tomorrow midnight', $subscritionEndDate) - 1;
                // never update status if it's completed
                if ($subscription->getSubscriptionStatus() != ADA_STATUS_COMPLETED) {
                    if (
                        $subscription->getSubscriptionStatus() == ADA_STATUS_SUBSCRIBED &&
                        $subscritionEndDate <= time()
                    ) {
                        $subscription->setSubscriptionStatus(ADA_STATUS_TERMINATED);
                        $updateSubscription = true;
                    } elseif (
                        $subscription->getSubscriptionStatus() == ADA_STATUS_TERMINATED &&
                        $subscritionEndDate > time()
                    ) {
                        $subscription->setSubscriptionStatus(ADA_STATUS_SUBSCRIBED);
                        $updateSubscription = true;
                    }
                }

                if ($updateSubscription) {
                    $subscription->setStartStudentLevel(null); // null means no level update
                    subscription::updateSubscription($subscription);
                }
            }
        }
    }

    /**
     * get_course_id_for_course_instance
     *
     * @param int $id_course_instance
     * @return mixed - an AMADB Error or a course id
     */
    public function getCourseIdForCourseInstance($id_course_instance)
    {
        $sql = "SELECT id_corso FROM istanza_corso WHERE id_istanza_corso=?";

        $result = $this->getRowPrepared($sql, [$id_course_instance]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result[0];
    }

    /**
     * get_course_info_for_course_instance
     *
     * @param int $id_course_instance
     * @return mixed - an AMADB Error or a course id
     */
    public function getCourseInfoForCourseInstance($id_course_instance)
    {
        $sql = "SELECT concat_ws(' ',U.nome,U.cognome),MC.*,U.id_utente FROM modello_corso as MC, utente as U WHERE U.id_utente = MC.id_utente_autore AND MC.id_corso = (select id_corso from istanza_corso WHERE id_istanza_corso=?)";
        //        $sql = "SELECT MC.* FROM modello_corso as MC.id_corso = (select id_corso from istanza_corso WHERE id_istanza_corso=$id_course_instance)";
        $result = $this->getRowPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Get course max level (based on max level of his nodes)
     * returns constant ADA_MAX_USER_LEVEL if max level is zero or null
     *
     * @param int $id_course_instance
     * @return int
     *
     */
    public function getCourseMaxLevel($id_course_instance)
    {
        $sql = "SELECT MAX(n.`livello`) as max_level FROM `nodo` as n WHERE n.`id_nodo` LIKE ?";

        $result = $this->getOnePrepared($sql, [intval($id_course_instance) . "\_%"]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        } else {
            if (is_null($result)) {
                $result = ADA_MAX_USER_LEVEL;
            }
        }

        return $result;
    }

    /**
     * function get_course_instances_student_can_subscribe_to:
     *
     * used to retrieve data about course instances the student can subscribe to.
     *
     * @param  int $id_student - the id of the student
     * @return mixed - an array of course instances or an AMADB error.
     */
    public function getCourseInstancesStudentCanSubscribeTo($id_student)
    {
        $current_timestamp = time();

        $sql = "SELECT IC.id_istanza_corso, IC.id_corso, IC.data_inizio, IC.durata, IC.data_inizio_previsto
  			  FROM istanza_corso as IC
             WHERE IC.data_inizio_previsto > :current_timestamp
              AND IC.id_istanza_corso NOT IN
              	(SELECT id_istanza_corso
                   FROM iscrizioni
                  WHERE id_utente_studente=:id_student
                    AND (data_inizio_previsto > :current_timestamp
                         OR (data_inizio_previsto < :current_timestamp AND data_fine > :current_timestamp)))";

        $result = $this->getAllPrepared($sql, compact('id_student', 'current_timestamp')); //, null, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Methods accessing table `link`
     */
    // MARK: Methods accessing table `link`

    /**
     * Add a link to table link
     *
     * @access public
     *
     * @param $link_ha an associative array containing all the link's data
     *                 the structure is as follows:
     * id_nodo           - the id of the node this link lives in
     * id_nodo_to        - the id of the node this link points to
     * pos_x0, pos_y0,   - the four coordinates of the link's position in the map
     * pos_x1, pos_y1
     * id_utente         - user id of the author
     * tipo              - type of link
     *                     0 - internal
     *                     1 - external (with respect to the node ID_NODE)
     * data_creazione    - date of creation of the link
     * stile             - style of the link
     *                     0 - continuo
     *                     1 - tratteggiato
     *                     2 - tratto-punto
     *                     3 - puntinato
     * significato        - meaning of the link (????)
     * azione             - the action following a click on this link
     *                     0 - jump
     *                     1 - popup
     *                     2 - open application
     *
     * @return an AMAError object or a DB_Error object if something goes wrong,
     *      true on success
     *
     */
    public function addLink($link_ha)
    {
        // prepare variables for insertion
        //Utilities::mydebug(__LINE__,__FILE__,$link_ha);
        $id_nodo =  DataValidator::validateNodeId($link_ha['id_nodo']);
        $id_nodo_to =  DataValidator::validateNodeId($link_ha['id_nodo_to']);
        $id_utente =  DataValidator::isUinteger($link_ha['id_utente']);
        $tipo =  $this->orZero($link_ha['tipo']);
        $data_creazione = $this->dateToTs($link_ha['data_creazione']);
        $stile = $this->orZero($link_ha['stile']);
        $significato = DataValidator::validateString($link_ha['significato']);
        $azione =  $this->orZero($link_ha['azione']);
        $pos_ar = $link_ha['posizione'];

        // check values
        if (empty($id_nodo)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_link " .
                AMA_SEP . ": no node specified");
        }

        if (empty($id_nodo_to)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_link " .
                AMA_SEP . ": empty destination node");
        }

        if (empty($pos_ar)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_link " .
                AMA_SEP . ": empty position");
        }

        if (empty($id_utente)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_link " .
                AMA_SEP . ": undefined author");
        }

        // data regarding the node's position
        $pos_x0 = $pos_ar[0];
        $pos_y0 = $pos_ar[1];
        $pos_x1 = $pos_ar[2];
        $pos_y1 = $pos_ar[3];

        if ($id = $this->doGetIdPosition($pos_ar)) {
            // if a position is found in the posizione table, the use it
            $id_posizione = $id;
        } else {
            // add row to table "posizione"
            if (AMADataHandler::isError($res = $this->doAddPosition($pos_ar))) {
                return new AMAError($res->getMessage());
            } else {
                // get id of position just added
                $id_posizione = $this->doGetIdPosition($pos_ar);
            }
        }

        // insert a row into table link
        $sql  = "insert into link (id_link,id_utente,id_nodo,id_nodo_to,id_posizione,tipo,data_creazione,stile,significato,azione)";
        $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        //   Utilities::mydebug(__LINE__,__FILE__,$sql);
        $res = $this->executeCriticalPrepared($sql, [
            '',
            $id_utente,
            $id_nodo,
            $id_nodo_to,
            $id_posizione,
            $tipo,
            $data_creazione,
            $stile,
            $significato,
            $azione,
        ]);

        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            $err = $this->errorMessage(AMA_ERR_ADD) . " while in add_link " .
                AMA_SEP .  ": " . $res->getMessage();
            return new AMAError($err);
        }

        return true;
    }

    /**
     * Remove a link from table link
     *
     * @access public
     * @param $link_id
     *
     * @return true on success, ana AMAError object on failure
     */
    public function removeLink($link_id)
    {
        $sql = "delete from link where id_link=?";
        $result = $this->executeCriticalPrepared($sql, [$link_id]);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }

        return true;
    }

    /**
     * Get link id starting from the starting node and the target node
     *
     *
     * @param $node     - id of the starting node
     * @param $node_to  - id of the targeted node
     *
     * @return the id of the link or a null value
     *
     */
    public function getLinkId($sqlnode_id, $sqlnode_to_id)
    {
        ADALogger::logDb("entered get_link_id (node_id: $sqlnode_id, node_to_id: $sqlnode_to_id)");

        // vito, 21 luglio 2008
        $id =  $this->getOnePrepared("select id_link from link where id_nodo=? and id_nodo_to=?", [$sqlnode_id, $sqlnode_to_id]);
        //$id =  $db->getOne("select id_link from link where id_nodo='$sqlnode_id' and id_nodo_to='$sqlnode_to_id'");
        if (AMADataHandler::isError($id)) {
            // vito, $db e' l'oggetto di connessione, l'errore e' in $id
            //                 return $db;
            return new AMAError(AMA_ERR_GET);
        }

        return $id;
    }

    /**
     * Get link info
     * get all informations about a link.
     *
     * @param $link_id the id of the node to query
     *
     * @return an hash containing the following values:
     * id_nodo           - the id of the node this link lives in
     * id_nodo_to        - the id of the node this link points to
     * autore            - hash with author's data
     * posizione         - 4 elements array containing the position
     * tipo              - type of link
     *                     0 - internal
     *                     1 - external (with respect to the node ID_NODE)
     * data_creazione    - date of creation of the link
     * stile             - style of the link
     *                     0 - continuo
     *                     1 - tratteggiato
     *                     2 - tratto-punto
     *                     3 - puntinato
     * significato        - meaning of the link (????)
     * azione             - the action following a click on this link
     *                     0 - jump
     *                     1 - popup
     *                     2 - open application
     *
     */
    public function getLinkInfo($link_id)
    {
        // get a row from table link
        $sql  = "select id_nodo, id_utente, id_posizione, ";
        $sql .= "id_nodo_to, tipo, data_creazione, stile, significato, azione";
        $sql .= " from link where id_link=?";
        $res_ar =  $this->getRowPrepared($sql, [$link_id]);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // author is a hash
        $author_id = $res_ar[1];
        $author_ha = $this->getAuthor($author_id);

        // position is a four elements array
        $pos_id = $res_ar[2];
        $pos_ar = $this->getPosition($pos_id);

        $res_ha['id_nodo']        = $res_ar[0];
        $res_ha['autore']         = $author_ha;
        $res_ha['posizione']      = $pos_ar;
        $res_ha['id_nodo_to']     = $res_ar[3];
        $res_ha['tipo']           = $res_ar[4];
        $res_ha['data_creazione'] = $res_ar[5];
        $res_ha['stile']          = $res_ar[6];
        $res_ha['significato']    = $res_ar[7];
        $res_ha['azione']         = $res_ar[8];

        return $res_ha;
    }

    /**
     * add all links in the array links_ar (within a transaction)
     *
     * @access private
     *
     * @param
     *  - links_ar bi-dimensional array containing a series of links
     *  - $node_id id of the node the links start from
     *
     */
    private function addLinks($links_ar, $node_id)
    {
        ADALogger::logDb("entered add_links(");
        ADALogger::logDb("starting a transaction");

        $this->beginTransaction();

        $n = count($links_ar);
        ADALogger::logDb("got $n links to add");
        //Utilities::mydebug(__LINE__,__FILE__,$links_ar);

        for ($i = 1; $i <= $n; $i++) {   // links start with 1 !  steve 23/10/01
            $link_ha['id_nodo'] = $links_ar[$i]['id_nodo'];
            if (empty($link_ha['id_nodo'])) {
                $link_ha['id_nodo'] = $node_id;
            }
            $link_ha['id_nodo_to'] = $links_ar[$i]['id_nodo_to'];
            $link_ha['posizione'] = $links_ar[$i]['posizione'];
            $link_ha['id_utente'] = $links_ar[$i]['id_utente'];
            $link_ha['tipo'] = $links_ar[$i]['tipo'];
            $link_ha['data_creazione'] = $links_ar[$i]['data_creazione'];
            $link_ha['stile'] = $links_ar[$i]['stile'];
            $link_ha['significato'] = $links_ar[$i]['significato'];
            $link_ha['azione'] = $links_ar[$i]['azione'];
            //Utilities::mydebug(__LINE__,__FILE__,$link_ha);

            ADALogger::logDb("trying to add link $i");
            if (AMADataHandler::isError($res = $this->addLink($link_ha))) {
                // does the rollback
                $err  = $res->getMessage() . AMA_SEP . $this->rollback();
                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                $link_id = $this->getLinkId(
                    $link_ha['id_nodo'],
                    $link_ha['id_nodo_to']
                );
                ADALogger::logDb("done ($link_id), adding instruction to rbs");
                $this->rsAdd("remove_link", $link_id);
            }
        }

        ADALogger::logDb("committing links insertion");
        $this->commit();

        return true;
    }

    /**
     * remove all links connected to node $id_node
     *
     * @access private
     *
     */
    private function delLinks($sqlnode_id)
    {
        ADALogger::logDb("enteres del_links (sqlnode_id: $sqlnode_id)");

        $sql = "delete from link where id_nodo=?";
        $result = $this->queryPrepared($sql, [$sqlnode_id]);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }

        return true;
    }

    /**
     * remove extended node $id_node
     *
     * @access private
     *
     */
    private function delExtendedNode($sqlnode_id)
    {
        $sql = "delete from extended_node where id_node=?";
        ADALogger::logDb("cleaning extended node: $sql");
        $res = $this->executeCriticalPrepared($sql, [$sqlnode_id]);
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->message . " detected, aborting");
            return new AMAError(AMA_ERR_REMOVE);
        }
        return true;
    }

    /**
     * Methods accessing table `log_classi`
     */
    // MARK: Methods accessing table `log_classi`
    /**
     * Add an item  to table log_classi
     *
     *
     * @access public
     *
     *
     * @param $course_id    the id of the instance of course the student is navigating
     * @param $class_report      the report to be inserted
     *
     *
     */
    public function addClassReport($course_id, $course_instance_id, $student_data)
    {
        $user_id = $student_data['id'];
        $date = $student_data['date'];
        $visits = $this->orZero($student_data['visits']);
        $exercises = $this->orZero($student_data['exercises']);
        $score = $this->orZero($student_data['score']);
        $msg_out = $this->orZero($student_data['msg_out']);
        $msg_in = $this->orZero($student_data['msg_in']);
        $added_notes = $this->orZero($student_data['added_notes']);
        $read_notes = $this->orZero($student_data['read_notes']);
        $chat = $this->orZero($student_data['chat']);
        $bookmarks = $this->orZero($student_data['bookmarks']);
        $index_att = $student_data['index'];
        $status = $this->orZero($student_data['status']);
        $level = $student_data['level'];
        $last_access = $this->orZero($student_data['last_access']);

        if (MODULES_TEST) {
            $exercises_test = $this->orZero($student_data['exercises_test']);
            $score_test = $this->orZero($student_data['score_test']);
            $exercises_survey = $this->orZero($student_data['exercises_survey']);
            $score_survey = $this->orZero($student_data['score_survey']);
        }

        //      print_r($student_data);

        $sql = "SELECT `id_user`,`id_istanza_corso`,`data`,`id_log` from log_classi WHERE `id_istanza_corso` = ? AND `data` = ? AND `id_user` = ?";

        $res = $this->getRowPrepared($sql, [$course_instance_id, $date, $user_id], AMA_FETCH_ASSOC);
        $params = compact(
            'visits',
            'score',
            'exercises',
            'msg_out',
            'msg_in',
            'added_notes',
            'read_notes',
            'chat',
            'bookmarks',
            'index_att',
            'level',
            'last_access',
            'status'
        );

        if (!AMADB::isError($res) && !empty($res)) {
            $id_log = $res['id_log'];
            //data  already written, make an update
            $sql = "update log_classi set visite=:visits, punti=:score, esercizi=:exercises " .
                ", msg_out=:msg_out, msg_in=:msg_in, notes_out=:added_notes " .
                ", notes_in=:read_notes, chat=:chat, bookmarks=:bookmarks " .
                ", indice_att=:index_att, level=:level, last_access=:last_access, subscription_status=:status" .
                " where id_log=:id_log";
            $params['id_log'] = $id_log;
        } else {
            // add a row into table log_classi
            $sql =  "insert into log_classi (id_user,id_corso, id_istanza_corso, data, visite, punti,esercizi, msg_out,msg_in,notes_out,notes_in,chat,bookmarks,indice_att,level,last_access,subscription_status)";
            $sql .= " values (:user_id, :course_id, :course_instance_id, :date, :visits, ";
            $sql .= ":score, :exercises, :msg_out, :msg_in, :added_notes, :read_notes, :chat, :bookmarks, :index_att, :level, :last_access, :status);";
            //echo $sql;
            $params = array_merge(
                $params,
                compact(
                    'user_id',
                    'course_id',
                    'course_instance_id',
                    'date',
                )
            );
        }

        $res = $this->queryPrepared($sql, $params);
        // global $debug;$debug=1;Utilities::mydebug(__LINE__,__FILE__,$res); $debug=0;
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) .
                " while in add_class_report");
        } else {
            if (MODULES_TEST) {
                if (!isset($id_log)) {
                    $id_log = $this->lastInsertID();
                }
                $sql = 'update log_classi set `exercises_test`=?, `score_test`=?, `exercises_survey`=?, `score_survey`=? where `id_log`=?';
                $res = $this->queryPrepared($sql, [$exercises_test, $score_test, $exercises_survey, $score_survey, $id_log]);
                if (AMADB::isError($res)) {
                    return new AMAError($this->errorMessage(AMA_ERR_UPDATE) .
                        " while in add_class_report");
                }
            }
        }

        return true;
    }

    /**
     * Get a set of report data for a single day from table log_classi
     *
     *
     * @access public
     *
     *
     * @param $course_id    the course id
     * @param $course_instance_id     the id of the instance of that course
     * @param $date    the day (0000-00-00)
     *
     */
    public function getClassReport($course_id, $course_instance_id, $date)
    {
        /**
         * @author giorgio 27/ott/2014
         *
         * if we've been passed a null date, get the latest
         * availeble report for the passed course_instance_id
         */
        if (is_null($date)) {
            $sql = "SELECT MAX(data) FROM log_classi WHERE id_istanza_corso=?";
            $res = $this->getOnePrepared($sql, [$course_instance_id]);
            $date = (!AMADB::isError($res) && strlen($res) > 0) ? $res : time();
        }

        $sql = "SELECT L.*, U.nome, U.cognome "
            . "FROM (SELECT * from `log_classi` WHERE `id_istanza_corso`=? AND `data`=?) AS L "
            . "LEFT JOIN utente AS U ON (L.id_user=U.id_utente)";

        $res = $this->getAllPrepared($sql, [$course_instance_id, $date], AMA_FETCH_ASSOC);

        //global $debug;$debug=1;Utilities::mydebug(__LINE__,__FILE__,$res); $debug=0;
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_GET) . " while in get_class_report");
        }

        foreach ($res as $res_item) {
            $id_log = $res_item['id_log'];
            $student_data[$id_log]['id_stud'] = $res_item['id_user'];
            // vito, 27 mar 2009
            $student_data[$id_log]['student'] = $res_item['nome'] . ' ' . $res_item['cognome'];
            $student_data[$id_log]['nome'] = $res_item['nome'];
            $student_data[$id_log]['cognome'] = $res_item['cognome'];

            //        $student_data[$id_log]['id_course_instance'] = $res_item[2];
            //        $student_data[$id_log]['id_course'] = $res_item[3];

            $student_data[$id_log]['visits'] = $res_item['visite'];
            $student_data[$id_log]['date'] = $res_item['last_access'];
            //$student_data[$id_log]['visits'] = $res_item[5];
            $student_data[$id_log]['score'] = $res_item['punti'];
            $student_data[$id_log]['exercises'] = $res_item['esercizi'];
            $student_data[$id_log]['notes_out'] = $res_item['notes_out'];
            $student_data[$id_log]['notes_in'] = $res_item['notes_in'];
            $student_data[$id_log]['msg_in'] = $res_item['msg_in'];
            $student_data[$id_log]['msg_out'] = $res_item['msg_out'];
            $student_data[$id_log]['chat'] = $res_item['chat'];
            $student_data[$id_log]['bookmarks'] = $res_item['bookmarks'];
            $student_data[$id_log]['indice_att'] = $res_item['indice_att'];
            $student_data[$id_log]['level'] = $res_item['level'];
            $student_data[$id_log]['status'] = $res_item['subscription_status'];
            if (MODULES_TEST) {
                $student_data[$id_log]['exercises_test'] = $res_item['exercises_test'];
                $student_data[$id_log]['score_test'] = $res_item['score_test'];
                $student_data[$id_log]['exercises_survey'] = $res_item['exercises_survey'];
                $student_data[$id_log]['score_survey'] = $res_item['score_survey'];
            }
        }
        /**
         * @author giorgio 27/ott/2014
         *
         * added report generation date
         */
        if (isset($student_data)) {
            $student_data['report_generation_date'] = $date;
            return $student_data;
        } else {
            return null;
        }
    }

    /**
     * Get  all items of report data for a single student from table log_classi
     *
     *
     * @access public
     *
     *
     * @param $course_id    the course id
     * @param $course_instance_id     the id of the instance of that course
     *@param $student_id    the student  id
     *
     */
    public function getStudentReport($course_id, $course_instance_id, $student_id)
    {
        $sql = "SELECT * from log_classi WHERE id_istanza_corso  = ? AND id_user = ?";

        $res = $this->getAllPrepared($sql, [$course_instance_id, $student_id]);
        // global $debug;$debug=1;Utilities::mydebug(__LINE__,__FILE__,$res); $debug=0;
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_GET) . " while in get_student_report");
        }

        foreach ($res as $res_item) {
            $id_log = $res_item[0];
            //$student_data[$id_log]['id_stud'] = $res_item[1];
            $student_data[$id_log]['id_course_instance'] = $res_item[2];
            //$student_data[$id_log]['id_course'] = $res_item[3];
            $student_data[$id_log]['date'] = $res_item[4];
            $student_data[$id_log]['visits'] = $res_item[5];
            $student_data[$id_log]['score'] = $res_item[6];
            $student_data[$id_log]['exercises'] = $res_item[7];
            $student_data[$id_log]['msg_in'] = $res_item[9];
            $student_data[$id_log]['msg_out'] = $res_item[9];
            $student_data[$id_log]['notes_in'] = $res_item[10];
            $student_data[$id_log]['notes_out'] = $res_item[11];
            $student_data[$id_log]['chat'] = $res_item[11];
            $student_data[$id_log]['bookmarks'] = $res_item[12];
            $student_data[$id_log]['indice_att'] = $res_item[13];
            $student_data[$id_log]['level'] = $res_item[14];
        }
        return $student_data;
    }

    /**
     *
     * @param $student_id
     * @param $course_instance_id
     * @param $clause
     * @param $out_fields_ar
     * @return unknown_type
     */
    public function findStudentReport($student_id, $course_instance_id, $clause, $out_fields_ar)
    {
        // build comma separated string out of $field_list_ar array
        if (count($out_fields_ar)) {
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        }
        // add a 'where' on top of the clause
        // handle null clause, too
        $top_clause = " where id_istanza_corso  =  $course_instance_id AND id_user = $student_id";
        if ($clause) {
            $top_clause .= "AND $clause";
        }
        $sql = "select id_log,id_istanza_corso,id_user$more_fields from log_classi $top_clause order by id_log";
        // do the query
        $res =  $this->getAllPrepared($sql);

        // global $debug;$debug=1;Utilities::mydebug(__LINE__,__FILE__,$res); $debug=0;
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_GET) . " while in find_student_report");
        }

        return $res;
    }

    /**
     * Methods accessing table `modello_corso`
     */
    // MARK: Methods accessing table `modello_corso`

    /**
     * Add a course to the DB
     *
     * @access public
     *
     * @param $course_ha an associative array containing all the course's data
     *
     * @return an AMAError object or a DB_Error object if something goes wrong,
     *          or the id of new course if it is ok
     *
     */
    public function addCourse($course_ha)
    {
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CourseEvent::class,
                    'eventName' => CourseEvent::PRESAVE,
                ],
                $course_ha,
                ['isUpdate' => false]
            );
            foreach ($event->getArguments() as $key => $val) {
                if (array_key_exists($key, $course_ha)) {
                    $course_ha[$key] = $val;
                }
            }
        }

        // prepare values
        // *(dovrei aggiungere le funzioni per le date)*
        $nome = DataValidator::validateString($course_ha['nome']);
        $titolo = DataValidator::validateString($course_ha['titolo']);
        $descr = $this->orNull($course_ha['descr']);
        $d_create = $this->dateToTs($this->orNull($course_ha['d_create']));

        /**
         * @author giorgio 15/lug/2013
         * nobody remembers how come this lines of code sets the published date
         * value to either creation or publication date.
         * Anyway, today it was decided to comment 'em out and set the published date
         * to the passed one.
         */

        //         if (isset($course_ha['d_create'])) {
        //             $dateToTs = $course_ha['d_create'];
        //         }
        //         else if(isset($course_ha['d_publish'])) {
        //             $dateToTs = $course_ha['d_publish'];
        //         }
        //         else {
        //             $dateToTs = null;
        //         }

        //         $d_publish = $this->dateToTs($this->orNull($dateToTs));
        /**
         * @author giorgio 15/lug/2013
         * this line was added
         */
        $d_publish = $this->dateToTs($this->orNull($course_ha['d_publish']));

        $id_autore = $this->orZero($course_ha['id_autore']);
        $id_nodo_iniziale = $this->orZero(DataValidator::validateNodeId($course_ha['id_nodo_toc']));
        $id_nodo_toc = $this->orZero(DataValidator::validateNodeId($course_ha['id_nodo_iniziale']));
        $media_path = DataValidator::validateString($course_ha['media_path']);
        if (false == $media_path) {
            $media_path = null;
        }

        $static_mode = $this->orZero($course_ha['static_mode']);
        $id_lingua = DataValidator::isUinteger($course_ha['id_lingua']);
        $crediti =  $this->orZero($course_ha['crediti']);
        $duration_hours = $this->orZero($course_ha['duration_hours']);
        $service_type = $this->orNull($course_ha['service_level']);

        // verify key uniqueness (index)
        $id =  $this->getOnePrepared("select id_corso from modello_corso where nome = ?", [$nome]);
        if ($id) {
            return new AMAError(AMA_ERR_UNIQUE_KEY);
        }

        // insert a row into table modello_corso
        $sql1 =  "insert into modello_corso (id_corso, nome, titolo, id_utente_autore, descrizione, data_creazione, data_pubblicazione, id_nodo_toc, id_nodo_iniziale, media_path, static_mode, id_lingua, crediti, duration_hours,tipo_servizio)";
        $sql1 .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $res = $this->executeCriticalPrepared($sql1, [
            /**
             * @author giorgio 03/lug/2013
             *
             * call to common dh to get the new id_corso for the course to be inserted
             */
            (AMACommonDataHandler::getInstance()->getCourseMaxId() + 1),
            $nome,
            $titolo,
            $id_autore,
            $descr,
            $d_create,
            $d_publish,
            $id_nodo_toc,
            $id_nodo_iniziale,
            $media_path,
            $static_mode,
            $id_lingua,
            $crediti,
            $duration_hours,
            $service_type,
        ]);

        if (AMADB::isError($res)) {
            return $res;
        }

        $id =  $this->getOnePrepared("select id_corso from modello_corso where nome = ?", [$nome]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CourseEvent::class,
                    'eventName' => CourseEvent::POSTSAVE,
                ],
                array_merge(['id' => $id], $course_ha),
                ['isUpdate' => false]
            );
        }
        return $id;
    }

    /**
     * Remove a course model from the DB
     *
     * @access public
     *
     * @param $id the unique id of the course
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *         true on success
     *
     * @note referential integrity is checked against tables
     *  tutor_corso, amministratore_corsi and nodo
     * it must be impossible to remove a course model
     *  if a tutor or an administrator is still assigned to it
     * it must be impossible to remove a course model if nodes relating to this course
     *  still are in the DB
     */
    public function removeCourseModel($id)
    {
        ADALogger::logDb("entered remove_course_model (id:$id)");

        $ri_id = $this->getOnePrepared("select id_utente_amministratore from amministratore_corsi where id_corso=?", [$id]);
        if ($ri_id) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        $id_node_prefix = $id . "\_";
        $ri_id = $this->getOnePrepared("select id_nodo from nodo where id_nodo LIKE ?", [$id_node_prefix . "%"]);

        if ($ri_id) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        // do the removal
        $sql = "delete from modello_corso where id_corso=?";
        $res = $this->executeCriticalPrepared($sql, [$id]);
        $res = true;
        if (AMADB::isError($res)) {
            return $res;
        }

        ADALogger::logDb("course model successfully removed");

        return true;
    }

    /**
     * Remove a course content from the DB
     * This means all nodes are removed, that have their ID_NODO field
     * starting with the ID_CORSO parameter
     * This function can be useful both in eliminating a whole course from the system,
     * and in removing the content if some errors occur during the upload phase
     *
     * @access public
     *
     * @param $id the unique id of the course the content relates to
     *
     * @return
     *  - true if everything is OK
     *  - an AMA_ERR_NOT_FOUND if no records to remove were found
     *  - another ERROR if something goes wrong
     *
     * @note referential integrity isn't checked since only nodes are removed
     */
    public function removeCourseContent($id)
    {
        ADALogger::logDb("entered remove_course_content (id:$id)");

        // get all nodes relating to this course
        $id_node_prefix = $id . "\_";

        $ids = $this->getColPrepared("select id_nodo from nodo where id_nodo LIKE ?", [$id_node_prefix . '%']);
        ADALogger::logDb("getting all nodes related to this course " . count($ids));
        if (empty($ids)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $n_ids = count($ids);
        ADALogger::logDb("got " . count($ids) . " records");

        // start a loop to remove the nodes
        for ($i = 0; $i < $n_ids; $i++) {
            $res = $this->removeNode($ids[$i]);
            // FIXME: resituire subito l'errore?
            if (AMADataHandler::isError($res)) {
                return $res;
            }
        }
        return true;
    }

    /**
     * Remove a whole course from the DB (content, instances and model)
     * This is done by calling the three functions:
     *  - remove_course_content
     *  - course_instance_remove on all instance related to the model
     *  - remove_course_model
     *
     * @access public
     *
     * @param $id the unique id of the course to be zapped away
     *
     * @return
     *  - nothing if everything is OK
     *  - an AMA_ERR_NOT_FOUND if no such course were found
     *  - another ERROR if something goes wrong with any of the functions called
     *
     * @note this is a kind of 'macro', so referential integrity is not checked
     * (it is checked inside functions);
     */
    public function removeCourse($id)
    {
        ADALogger::logDb("entered remove_course");
        ADALogger::logDb("id: $id");

        // remove content
        // return only if error is different from AMA_ERR_NOT_FOUND
        ADALogger::logDb("trying to remove course content");
        $res = $this->removeCourseContent($id);

        if (AMADataHandler::isError($res) && $res->code != AMA_ERR_NOT_FOUND) {
            return $res;
        }

        ADALogger::logDb("content successfully removed");
        // find all instances related to this model
        ADALogger::logDb("getting instances related to this model -$id-");

        $ids = $this->getColPrepared("select id_istanza_corso from istanza_corso where id_corso=?", [$id]);
        if (!AMADataHandler::isError($ids)) {
            $n_ids = count($ids);
            ADALogger::logDb("got $n_ids records");
            // start a loop to remove the instances (may be a void loop)
            for ($i = 0; $i < $n_ids; $i++) {
                ADALogger::logDb("trying to remove course instance " . $ids[$i]);
                $res = $this->courseInstanceRemove($ids[$i]);
                if (AMADataHandler::isError($res)) {
                    return $res;
                }
                ADALogger::logDb("instance " . $ids[$i] . " successfully removed");
            }
        }
        // FIXME: else restituire un errore?

        // remove the course model
        ADALogger::logDb("trying to remove course model " . $id);
        $res = $this->removeCourseModel($id);
        if (AMADataHandler::isError($res)) {
            return $res;
        }
        ADALogger::logDb("course model $id successfully removed");

        return true;
    }

    /**
     * Get a list of courses' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_courses_list
     */
    public function &getCoursesList($field_list_ar)
    {
        return $this->findCoursesList($field_list_ar);
    }

    /**
     * Get a list of courses' ids from the DB
     *
     * @access public
     *
     * @return an array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     *
     * @see find_courses_list, get_courses_list
     */
    public function &getCoursesIds()
    {
        return $this->getCoursesList([]);
    }

    /**
     * Get courses where a keyword is in one of the fields specified
     *
     * @access public
     *
     * @param  $out_fields_ar an array containing the desired fields' names
     *         possible values are: nome, titolo, id_utente_autore, descrizione,
     *         data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param  $key the keyword or sentence to look for (a string)
     *
     * @param  $search_fields_ar array of fields where the key must be looked for
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_courses_list
     */
    public function &findCoursesListByKey($out_fields_ar, $key, $search_fields_ar)
    {
        $clause = '';
        $n = count($search_fields_ar);
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1) {
                $or = " OR ";
            } else {
                $or = "";
            }
            $clause .= $search_fields_ar[$i] . " LIKE '%" . $key . "%' " . $or;
        }

        return $this->findCoursesList($out_fields_ar, $clause);
    }

    /**
     * Find those courses verifying the given criterium
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param  clause the clause string which will be added to the select
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &findCoursesList($field_list_ar, $clause = '')
    {
        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }
        // add an 'and' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'where ' . $clause;
        }
        $query = "select id_corso$more_fields from modello_corso $clause";
        // do the query
        $courses_ar =  $this->getAllPrepared($query, null, AMA_FETCH_BOTH);

        if (AMADB::isError($courses_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        //
        // return nested array in the form
        //
        if (!$courses_ar) {
            $retval = new AMAError(AMA_ERR_NOT_FOUND);
            return $retval;
        }
        if (!is_array($courses_ar)) {
            $retval = new AMAError(AMA_ERR_INCONSISTENT_DATA);
            return $retval;
        }
        return $courses_ar;
    }

    /**
     * Among the courses available for subscription,
     * get those where a keyword is in one of the fields specified
     *
     * @access public
     *
     * @param  $out_fields_ar an array containing the desired fields' names
     *         possible values are: nome, titolo, id_utente_autore, descrizione,
     *         data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param  $key the keyword or sentence to look for (a string)
     *
     * @param  $search_fields_ar array of fields where the key must be looked for
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_courses_list
     */
    public function &findSubCoursesListByKey($out_fields_ar, $key, $search_fields_ar)
    {
        $clause = "";
        $n = count($search_fields_ar);
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1) {
                $or = " OR ";
            } else {
                $or = "";
            }
            $clause .= $search_fields_ar[$i] . " LIKE '%" . $key . "%' " . $or;
        }

        return $this->findSubCoursesList($out_fields_ar, $clause);
    }

    /**
     * Among the courses available for subscription,
     * get those where a criterium is satisfied
     *
     * @access public
     *
     * @param  $out_fields_ar an array containing the desired fields' names
     *         possible values are: nome, titolo, id_utente_autore, descrizione,
     *         data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @param  $clause the criterium (as a where clause, without where)
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_courses_list
     */
    public function &findSubCoursesList($out_fields_ar, $clause = "")
    {
        $complete_clause = "data_pubblicazione IS NOT NULL";

        if (isset($clause) && $clause != "") {
            $complete_clause .= " AND ($clause)";
        }
        return $this->findCoursesList($out_fields_ar, $complete_clause);
    }

    /**
     * Among the courses available for subscription,
     * get a list of courses' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, titolo, id_utente_autore, descrizione,
     *        data_creazione, data_pubblicazione, media_path, (id_nodo_iniziale), (id_nodo_toc)
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_courses_list
     */
    public function &getSubCoursesList($field_list_ar)
    {
        return $this->findSubCoursesList($field_list_ar);
    }

    /**
     * Get all informations about a course
     *
     * @access public
     *
     * @param $id the course's id
     *
     * @return an array containing all the informations about a course
     *        res_ha['nome']
     *        res_ha['titolo']
     *        res_ha['id_autore']
     *        res_ha['id_layout']
     *        res_ha['descr']
     *        res_ha['d_create']
     *        res_ha['d_publish']
     *        res_ha['id_nodo_iniziale']
     *        res_ha['id_nodo_toc']
     *        res_ha['media_path']
     *
     */
    public function getCourse($id)
    {
        // get a row from table MODELLO_CORSO
        $res_ar =  $this->getRowPrepared("select nome, titolo, id_utente_autore, id_layout, descrizione, data_creazione, data_pubblicazione, id_nodo_iniziale, id_nodo_toc, media_path,static_mode, id_lingua, crediti, duration_hours,tipo_servizio from modello_corso where id_corso=?", [$id]);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if ((!$res_ar) or (!is_array($res_ar))) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // queste andrebbero trasformate in interi e date (devo fare le funzioni di conversione da stringa)
        $res_ha['nome']                  = $res_ar[0];
        $res_ha['titolo']                = $res_ar[1];
        $res_ha['id_autore']             = $res_ar[2];
        $res_ha['id_layout']             = $res_ar[3];
        $res_ha['descr']                 = $res_ar[4];
        $res_ha['d_create']              = self::tsToDate($res_ar[5]);
        $res_ha['d_publish']             = self::tsToDate($res_ar[6]);
        $res_ha['id_nodo_iniziale']      = $res_ar[7];
        $res_ha['id_nodo_toc']           = $res_ar[8];
        $res_ha['media_path']            = $res_ar[9];
        $res_ha['static_mode']           = $res_ar[10];
        $res_ha['id_lingua']             = $res_ar[11];
        $res_ha['crediti']               = $res_ar[12];
        $res_ha['duration_hours']        = $res_ar[13];
        $res_ha['service_level']         = $res_ar[14];

        return $res_ha;
    }

    /**
     * Updates informations related to a course
     *
     * @access public
     *
     * @param $id the course's id
     *        $course_ar the informations. empty fields are not updated
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     */
    public function setCourse($id, $course_ha)
    {
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CourseEvent::class,
                    'eventName' => CourseEvent::PRESAVE,
                ],
                array_merge(['id' => $id], $course_ha),
                ['isUpdate' => true]
            );
            foreach ($event->getArguments() as $key => $val) {
                if (array_key_exists($key, $course_ha)) {
                    $course_ha[$key] = $val;
                }
            }
        }

        // prepare values
        // (dovrei aggiungere le funzioni per le date)
        $nome = DataValidator::validateString($course_ha['nome']);
        $titolo = DataValidator::validateString($course_ha['titolo']);
        $descr = $this->orNull(DataValidator::validateString($course_ha['descr']));
        $d_create = $this->dateToTs($this->orNull($course_ha['d_create']));
        $d_publish = $this->dateToTs($this->orNull($course_ha['d_publish']));
        $id_autore = $this->orZero($course_ha['id_autore']);
        $id_layout = $this->orZero($course_ha['id_layout'] ?? '');
        $id_lingua = $this->orZero($course_ha['id_lingua']);
        $crediti = $this->orZero($course_ha['crediti']);
        $duration_hours = $this->orZero($course_ha['duration_hours']);
        $service_type = $this->orNull($course_ha['service_level']);

        if (empty($course_ha['id_nodo_iniziale'])) {
            //$id_nodo_iniziale = $id."_0";      dovrebbe essere una stringa !!!
            $id_nodo_iniziale = "0";
        }

        if (empty($course_ha['id_nodo_toc'])) {
            //$id_nodo_toc = $id."_0";           dovrebbe essere una stringa !!!
            $id_nodo_toc = "0";
        }
        /* fine modifica */

        $media_path = DataValidator::validateString($course_ha['media_path']);
        if (false == $media_path) {
            $media_path = null;
        }
        $res_id = 0;

        // verify that the record exists and store old values for rollback
        $res_id =  $this->getRowPrepared("select id_corso from modello_corso where id_corso=?", [$id]);
        if (AMADB::isError($res_id)) {
            return new AMAError(AMA_ERR_GET);
        }
        if ($res_id == 0) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // backup old values
        $old_values_ha = $this->getCourse($id);
        // verify unique constraint once updated
        $new_nome = $course_ha['nome'];
        $old_nome = $old_values_ha['nome'];
        if ($new_nome != $old_nome) {
            $res_id = $this->getOnePrepared("select id_corso from modello_corso where nome=?", [$nome]);
            if (AMADB::isError($res_id)) {
                return new AMAError(AMA_ERR_GET);
            }
            if ($res_id) {
                return new AMAError(AMA_ERR_UNIQUE_KEY);
            }
        }

        // update the rows in the tables
        $sql1  = "update modello_corso set nome=?, titolo=?, descrizione=?, data_creazione=?, data_pubblicazione=?, id_utente_autore=?, id_nodo_toc=?, id_nodo_iniziale=?, media_path=?, id_layout=?, id_lingua=?, crediti=?, duration_hours=?,tipo_servizio=? where id_corso=?";
        $res = $this->queryPrepared($sql1, [
            $nome,
            $titolo,
            $descr,
            $d_create,
            $d_publish,
            $id_autore,
            $id_nodo_toc,
            $id_nodo_iniziale,
            $media_path,
            $id_layout,
            $id_lingua,
            $crediti,
            $duration_hours,
            $service_type,
            $id,
        ]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_UPDATE);
        }
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CourseEvent::class,
                    'eventName' => CourseEvent::POSTSAVE,
                ],
                array_merge(['id' => $id], $course_ha),
                ['isUpdate' => true]
            );
        }
        return true;
    }

    /*
     * Get course type
     *
     * @access public
     *
     * @ return course_type
     *
     * @return an error if something goes wrong
     *
     */
    public function getCourseType($id_course)
    {
        $sql = "SELECT tipo_servizio FROM modello_corso where id_corso=?";
        $result = $this->getOnePrepared($sql, [$id_course]);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Methods accessing table `nodo`
     */
    // MARK: Methods accessing table `nodo`

    /**
     * Verify node existence
     *
     *
     * @access public
     *
     * @param $node_id
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *
     */
    public function nodeExists($node_id)
    {
        $sql = 'SELECT id_nodo FROM nodo WHERE id_nodo=?';
        $values = [
            $node_id,
        ];
        $result = $this->getOnePrepared($sql, $values);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Returns the id of the child having order $child_order if it exists and if
     * it is not a note or a private note.
     *
     * @param string $parent_node_id
     * @param integer $child_order
     * @param string operator comparison operator to be used in ordine clause. Can be one of: >, <, =, !=, >=, <=. Defaluts to =
     * @return string the id of the child, or an AMAError
     */
    public function childExists($parent_node_id, $child_order, $user_level = ADA_MAX_USER_LEVEL, $operator = '=')
    {
        $allowedOperators =  [
            '>' => ['sortorder' => 'ASC'],
            '=' => ['sortorder' => 'ASC'],
            '>=' => ['sortorder' => 'ASC'],
            '!=' => ['sortorder' => 'ASC'],
            '<' => ['sortorder' => 'DESC'],
            '<=' => ['sortorder' => 'DESC'],
        ];

        if (array_key_exists($operator, $allowedOperators)) {
            $sql = 'SELECT id_nodo FROM nodo WHERE livello <= ? AND id_nodo_parent=? AND ordine' . $operator . '? AND tipo NOT IN (?, ?) ORDER BY ordine ' . $allowedOperators[$operator]['sortorder'];
            $values = [
                $user_level,
                $parent_node_id,
                $child_order,
                ADA_NOTE_TYPE,
                ADA_PRIVATE_NOTE_TYPE,
            ];
            $result = $this->getOnePrepared($sql, $values);
            if (AMADB::isError($result)) {
                return new AMAError(AMA_ERR_GET);
            }
            return $result;
        } else {
            return new AMAError(AMA_ERR_WRONG_ARGUMENTS);
        }
    }

    /**
     * Returns the id of the last child of the given node if it exists and if
     * it is not a note or a private note.
     *
     * @param string $parent_node_id
     * @param integere $child_order
     * @return string the id of the child, or an AMAError
     */
    public function lastChildExists($parent_node_id, $user_level = ADA_MAX_USER_LEVEL)
    {
        $sql = 'SELECT id_nodo FROM nodo WHERE livello <= ? AND id_nodo_parent = ?  AND tipo NOT IN (?, ?) ORDER BY ordine DESC LIMIT 1';
        $values = [
            $user_level,
            $parent_node_id,
            ADA_NOTE_TYPE,
            ADA_PRIVATE_NOTE_TYPE,
        ];
        $result = $this->getOnePrepared($sql, $values);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Undocumented function
     *
     * @param integer $id_course
     * @param string $id_toc
     * @param integer $depth
     * @return int
     */
    public function getMaxIdFN($id_course = 1, $id_toc = '', $depth = 1)
    {
        // return the max id_node of the course
        $id_node_max = $this->doGetMaxIdFN($id_course, $id_toc, $depth);
        // vito, 15/07/2009
        if (AMADataHandler::isError($id_node_max)) {
            /*
             * Return a ADAError object with delayedErrorHandling set to TRUE.
             */
            return new ADAError(
                $id_node_max,
                translateFN('Errore in lettura max id'),
                'get_max_idFN',
                null,
                null,
                null,
                true
            );
        }
        return $id_node_max;
    }

    /**
     * Get last node for a course
     *
     *
     * @access public
     *
     * @param $id_course, $id_toc, $depth
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *
     */
    public function doGetMaxIdFN($id_course, $id_toc, $depth)
    {
        $out_fields_ar = ['nome'];
        $key = $id_course . "\_";
        $id_node_max = "";
        $clause = "ID_NODO LIKE '$key%'";
        $nodes = $this->doFindNodesList($out_fields_ar, $clause);
        if (AMADB::isError($nodes)) {
            return $nodes;
        }

        foreach ($nodes as $single_node) {
            $id_node = $single_node[0];
            $id_temp = substr($id_node, 2); // get only the part of node
            $id_temp_ar = explode("_", $id_node);
            $id_temp = $id_temp_ar[1];
            if ($id_temp > $id_node_max) {
                $id_node_max = $id_temp;
            }
        }
        // additional control to ensure that nobody has inserted new node
        // recursive function
        $newNodeId = $id_course . "_" . (intval($id_node_max) + 1);
        $clause = "ID_NODO = '$newNodeId'";
        $nodes = $this->doFindNodesList($out_fields_ar, $clause);
        if (AMADB::isError($nodes) || count($nodes) == 0) {
            $id_node_max = $id_course . "_" . $id_node_max;
            return $id_node_max;
        } else {
            return $this->doGetMaxIdFN($id_course, $id_toc, $depth);
        }
    }

    /**
     * Add the node extension (only for ADA_WORD_LEAF_TYPE and ADA_WORD_GROUP_TYPE)
     * only add the node extension.
     * This function is called from the public add_node function.
     *
     * @access private
     *
     * @param $node_ha an associative array containing all the node's data (see add_node public function)
     *
     * @return an AMAError object if something goes wrong,
     *         true on success
     *
     * @see add_node()
     */
    protected function addExtensionNode($node_ha)
    {
        ADALogger::logDb("entered add_extension_node");

        // FIXME: l'id del nodo dovrebbe venire ottenuto qui e non passato nell'array $node_ha
        $id_node = DataValidator::validateNodeId($node_ha['id']);
        $hyphenation = DataValidator::validateString($node_ha['hyphenation']);
        $grammar = DataValidator::validateString($node_ha['grammar']);
        $semantic = DataValidator::validateString($node_ha['semantic']);
        $notes = DataValidator::validateString($node_ha['notes']);
        $examples = DataValidator::validateString($node_ha['examples']);
        $language = DataValidator::validateString($node_ha['lingua']);

        $sql  = "insert into extended_node (id_node, hyphenation, grammar, semantic, notes, examples, language)";
        $sql .= " values (?, ?, ?, ?, ?, ?, ?)";
        ADALogger::logDb("trying inserting the extended_node: $sql");

        $res = $this->executeCriticalPrepared($sql, [
            $id_node,
            $hyphenation,
            $grammar,
            $semantic,
            $notes,
            $examples,
            $language,
        ]);
        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->getMessage());
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " while in add_extension_node." .
                AMA_SEP . ": " . $res->getMessage());
        }
        ADALogger::logDb("extended_node inserted");
        return true;
    }

    /**
     * Add a node
     * only add a node. Leaves out position, author and course.
     * This function is called from the public add_node function.
     *
     * @access private
     *
     * @param $node_ha an associative array containing all the node's data (see public function)
     *
     * @return an AMAError object if something goes wrong,
     *         true on success
     *
     * @see add_node()
     */
    // FIXME: probabiltmente dovrà diventare pubblico.
    protected function doAddNode($node_ha)
    {
        ADALogger::logDb("entered doAdd_node");

        // FIXME: l'id del nodo dovrebbe venire ottenuto qui e non passato nell'array $node_ha
        // Fixed by Graffio 08/11/2011
        $id_author = $node_ha['id_node_author'];
        $name = $this->orNull($node_ha['name'] ?? null);
        /**
         * ForkedPaths title must be set before the title
         */
        if (ModuleLoaderHelper::isLoaded('FORKEDPATHS') && isset($node_ha['is_forkedpaths']) && $node_ha['is_forkedpaths'] == 1) {
            $node_ha['title'] = ForkedPathsNode::addMagicKeywordToTitle($node_ha['title']);
        }
        $title = $this->orNull($node_ha['title'] ?? null);

        $text = $node_ha['text'] ?? null;
        $type = $this->orZero($node_ha['type'] ?? null);
        $creation_date = $this->dateToTs($this->orNull($node_ha['creation_date'] ?? ''));
        $parent_id = $this->orNull($node_ha['parent_id'] ?? null);
        $order = $this->orNull($node_ha['order'] ?? null);
        $level = $this->orZero($node_ha['level'] ?? null);
        $version = $this->orZero($node_ha['version'] ?? null);
        $n_contacts = $this->orZero($node_ha['n_contacts'] ?? null);
        $icon = $this->orNull($node_ha['icon'] ?? null);

        // modified 7/7/01 ste
        // $color = $this->orZero($node_ha['color']);
        $bgcolor = $this->orNull($node_ha['bgcolor'] ?? null);
        $color = $this->orNull($node_ha['color'] ?? null);
        // end
        $correctness = $this->orZero($node_ha['correctness'] ?? null);
        $copyright = $this->orZero($node_ha['copyright'] ?? null);
        // added 6/7/01 ste
        $id_position = $node_ha['id_position'] ?? null;
        $lingua = $this->orZero($node_ha['lingua'] ?? null);
        $pubblicato = $this->orZero($node_ha['pubblicato'] ?? null);
        // end
        // added 24/7/02 ste
        //  $family = $this->dateToTs($this->orNull($node_ha['family']));
        // end

        // added  2/4/03
        if (array_key_exists('id_instance', $node_ha)) {
            $id_instance = $this->orNull($node_ha['id_instance']);
        } else {
            $id_instance = "";
        }
        //end
        /******
         * graffio 08/11/2012
         * get the last id of the course.
         * If new course the first node of a course MUST be idCourse_0
         */
        if (isset($node_ha['id_course']) and ($node_ha['parent_id'] == null || $node_ha['parent_id'] == '')) {
            $new_node_id = $node_ha['id_course'] . '_' . '0';
        } else {
            $parentId = $node_ha['parent_id'];
            //             $regExp = '#^([1-9][0-9]+)_#';
            // giorgio 09/mag/2013
            // fixed bug in regexp, it mached two digits only and gived back no match for the first 9 courses!
            $regExp = '#^([1-9][0-9]*)_#';
            preg_match($regExp, $parentId, $stringFound);
            if (count($stringFound) > 0) {
                $idCourse = $stringFound[1];
                $last_node = $this->getMaxIdFN($idCourse);
                $tempAr = explode("_", $last_node);
                $newId = intval($tempAr[1]) + 1;
                $new_node_id = $idCourse . "_" . $newId;
            }
        }
        $id_node = $new_node_id;

        /***************************/
        // verify key uniqueness on nodo
        // Modifica di Graffio del 03/12/01
        // Se il nodo c'e' gia va avanti
        // E' corretto?????
        /***************************/
        /*
        $sql = "select id_nodo from nodo where id_nodo = $id_node";
        ADALogger::logDb("Query: $sql");
        $id =  $db->getOne($sql);
        if(AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("Query result: $id");

        if (!empty($id)) {
            return new AMAError($this->errorMessage(AMA_ERR_UNIQUE_KEY) . " while in doAdd_node.");
        }
         *
         */
        /***************************/
        /*  + family
     $sql  = "insert into nodo (id_nodo, id_utente,id_posizione, nome, titolo, testo, tipo, data_creazione, id_nodo_parent, ordine, livello, versione, n_contatti, icona, colore_didascalia, colore_sfondo, correttezza, copyright, family)";
     $sql .= " values ($id_node,  $id_author, $id_position, $name, $title, $text, $type, $creation_date, $parent_id, $order, $level, $version, $n_contacts, $icon, $color, $bgcolor, $correctness, $copyright, $family)";
        */
        // insert a row into table nodo
        $sql  = "insert into nodo (id_nodo, id_utente,id_posizione, nome, titolo, testo, tipo, data_creazione, id_nodo_parent, ordine, livello, versione, n_contatti, icona, colore_didascalia, colore_sfondo, correttezza, copyright, lingua, pubblicato, id_istanza)";
        $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        ADALogger::logDb("trying inserting the node: $sql");

        $res = $this->executeCriticalPrepared($sql, [
            $id_node,
            $id_author,
            $id_position,
            $name,
            $title,
            $text,
            $type,
            $creation_date,
            $parent_id,
            $order,
            $level,
            $version,
            $n_contacts,
            $icon,
            $color,
            $bgcolor,
            $correctness,
            $copyright,
            $lingua,
            $pubblicato,
            $id_instance,
        ]);
        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " while in doAdd_node." .
                AMA_SEP . ": " . $res->getMessage());
        }

        //return true;
        return $new_node_id;
    }

    /**
     * Edit a node
     *  edit type, title, name and text (title, name and text are compulsory)
     *
     * @access public
     *
     * @param $node_ha an associative array containing all the node's data (see public function)
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     */
    public function doEditNode($node_ha)
    {
        ADALogger::logDb("entered doEdit_node");

        $id_node = $node_ha['id'];
        $name = $this->orNull($node_ha['name']);
        /**
         * ForkedPaths title must be set before the title
         */
        if (ModuleLoaderHelper::isLoaded('FORKEDPATHS') && isset($node_ha['is_forkedpaths']) && $node_ha['is_forkedpaths'] == 1) {
            $node_ha['title'] = ForkedPathsNode::addMagicKeywordToTitle($node_ha['title']);
        }
        $title = $this->orNull($node_ha['title'] ?? '');

        $text = $node_ha['text'];
        //if (isset($node_ha['type'])) {
        $type = $this->orZero($node_ha['type']);
        //}
        //if (isset($node_ha['id_instance'])) {
        $id_instance = $this->orNull($node_ha['id_instance'] ?? '');
        //}
        $parent_id = $this->orNull($node_ha['parent_id']);

        $order = $this->orZero($node_ha['order']);
        $level = $this->orZero($node_ha['level'] ?? '');
        $version = $this->orZero($node_ha['version'] ?? '');
        $icon = $this->orNull($node_ha['icon'] ?? '');
        $correctness = $this->orZero($node_ha['correctness'] ?? '');

        /*
     * vito, 23 jan 2009
     * check if node position was given
        */
        if (
            isset($node_ha['pos_x0']) && is_numeric($node_ha['pos_x0']) &&
            isset($node_ha['pos_x1']) && is_numeric($node_ha['pos_x1']) &&
            isset($node_ha['pos_y0']) && is_numeric($node_ha['pos_y0']) &&
            isset($node_ha['pos_y1']) && is_numeric($node_ha['pos_y1'])
        ) {
            $position_ar = [$node_ha['pos_x0'], $node_ha['pos_y0'], $node_ha['pos_x1'], $node_ha['pos_y1']];
            $position_id = $this->doGetIdPosition($position_ar);
            if (AMADB::isError($position_id)) {
                return $position_id;
            }

            if ($position_id == -1) {
                // if position not found
                $res = $this->doAddPosition($position_ar);
                if (AMADB::isError($position_ar)) {
                    return new AMAError($res);
                } else {
                    $id = $this->doGetIdPosition($position_ar);
                }
            } else {
                $id = $position_id;
            }

            $update_node_position_sql = 'id_posizione=' . $id . ',';
        } else {
            $update_node_position_sql = '';
        }

        $sql = "select id_nodo from nodo where id_nodo = ?";
        ADALogger::logDb("Query: $sql");
        $id =  $this->getOnePrepared($sql, [$id_node]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("Query result: $id");
        if (empty($id)) {
            return new AMAError($this->errorMessage(AMA_ERR_NOT_FOUND) . " while in doEdit_node.");
        }
        // edit a row into table nodo
        $sql  = "update nodo set $update_node_position_sql nome = ?, titolo = ?, ordine = ?, testo = ?, livello = ?, versione = ?, correttezza = ?, id_nodo_parent = ?, icona= ?, tipo = ?";

        $params = [
            $name,
            $title,
            $order,
            $text,
            $level,
            $version,
            $correctness,
            $parent_id,
            $icon,
            $type,
        ];

        if (isset($id_instance)) {
            $sql  .= ", id_istanza = ?";     // promoting nodes
            $params[] = $id_instance;
        }

        // @author giorgio 26/apr/2013
        // force data_creazione to now if appropriate form checkbox is checked
        if (isset($node_ha['forcecreationupdate']) && intval($node_ha['forcecreationupdate']) === 1) {
            $sql .= ', data_creazione = ?';
            $params[] = $this->dateToTs('now');
        }

        $sql  .= " where id_nodo = ?";
        $params[] = $id_node;

        ADALogger::logDb("trying updating the node: $sql");

        $res = $this->queryPrepared($sql, $params);
        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            ADALogger::logDb("error while updating node id $id_node result:" . $res->getMessage());
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " while in doEdit_node." .
                AMA_SEP . ": " . $res->getMessage());
        } else {
            ADALogger::logDb("updating node id $id_node successful;");
        }
        /*
     * Check if parent node type is ADA_GROUP_TYPE. if not change it.
        */

        /*
     * vito, 28 nov 2008
     * The root node of a course has parent_id == 'NULL' (string)
     * So, if current node isn't a root node, makes sense to check
     * for its parent node attributes.
        */

        if ($node_ha['parent_id'] != null && $node_ha['parent_id'] !== 'NULL') {
            $parent_node_ha = $this->getNodeInfo($node_ha['parent_id']);
            if (AMADB::isError($parent_node_ha)) {
                return $parent_node_ha;
            }

            if ($parent_node_ha['type'] == ADA_LEAF_TYPE) {
                $result = $this->changeNodeType($node_ha['parent_id'], ADA_GROUP_TYPE);
                if (AMADB::isError($result)) {
                    return $result;
                }
            } elseif ($parent_node_ha['type'] == ADA_LEAF_WORD_TYPE) {
                $result = $this->changeNodeType($node_ha['parent_id'], ADA_GROUP_WORD_TYPE);
                if (AMADB::isError($result)) {
                    return $result;
                }
            }
        }
        // update row to table "extended_node"
        if ($node_ha['type'] == ADA_LEAF_WORD_TYPE or $node_ha['type'] == ADA_GROUP_WORD_TYPE) {
            $res = $this->editExtensionNode($node_ha);
            if (AMADB::isError($res)) {
                $err = $this->errorMessage(AMA_ERR_ADD) . "while in doEdit_node($id_node)" .
                    AMA_SEP . $res->getMessage();
                ADALogger::logDb($err);
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                ADALogger::logDb("extended_node update to db");
            }
        }
        return true;
    }

    /**
     * edit the node extension (only for ADA_WORD_LEAF_TYPE and ADA_WORD_GROUP_TYPE)
     * only update the node extension.
     * This function is called from the public doEdit_node function.
     *
     * @access private
     *
     * @param $node_ha an associative array containing all the node's data (see add_node public function)
     *
     * @return an AMAError object if something goes wrong,
     *         true on success
     *
     * @see doEdit_node()
     */
    private function editExtensionNode($node_ha)
    {
        ADALogger::logDb("entered add_extension_node");

        // FIXME: l'id del nodo dovrebbe venire ottenuto qui e non passato nell'array $node_ha
        $id_node = $node_ha['id'];
        $hyphenation = $node_ha['hyphenation'];
        $grammar = $node_ha['grammar'];
        $semantic = $node_ha['semantic'];
        $notes = $node_ha['notes'];
        $examples = $node_ha['examples'];
        $language = $node_ha['lingua'];

        $sql  = "update extended_node set hyphenation = ?, grammar = ?, semantic = ?, notes = ?, examples = ?, language = ?";
        $sql  .= " where id_node = ?";
        ADALogger::logDb("trying updating the extended_node: $sql");
        $res = $this->queryPrepared($sql, [
            $hyphenation,
            $grammar,
            $semantic,
            $notes,
            $examples,
            $language,
            $id_node,
        ]);
        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            //            var_dump($res);
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " while in _edit_extension_node." .
                AMA_SEP . ": " . $res->getMessage());
        }

        return true;
    }

    /**
     * Remove a node.
     * Only remove a row from table "nodo". Leaves out position, author and the rest.
     * All aspects of referential integrity is handled in the public method remove_node().
     *
     * @access private
     *
     * @param $id the id of the node to be removed
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     * @see remove_node()
     */
    // FIXME: forse deve essere pubblico
    private function doRemoveNode($sqlnode_id)
    {
        ADALogger::logDb("entered doRemove_node (id:$sqlnode_id)");

        $sql = "delete from nodo where id_nodo=?";
        ADALogger::logDb("trying query: $sql");

        $res = $this->executeCriticalPrepared($sql, [$sqlnode_id]);
        if (AMADB::isError($res)) {
            return $res;
        }

        return true;
    }

    /**
     * Add a node
     * Add a node, its position, author and everything into the DB.
     * Transactions are handled (with care).
     *
     * @access public
     *
     * @param $node_ha an associative array containing all the node's data
     *                 the structure is as follows:
     * id                - the unique id of the node.
     *                     made out of the unique id inside promenade concatenated to the course_id
     * id_node_author    - the id of the user who created the node
     * pos_x0            - starting x coordinate in the map
     * pos_y0            - starting y coordinate in the map
     * pos_x1            - final x coordinate in the map
     * pos_y1            - final y coordinate in the map
     * name              - the name of the node (what's a name for a node, anyway?)
     * title             - the title of the node
     * text              - the content of the node, i.e. the real stuff
     * type              - the type of node
     *                       0 - Simple node,
     *                       1 - Group of nodes,
     *                       2 - Note,
     *                       3 - Multiple Answer,
     *                       4 - Free Answer,
     *                       5 - Single answer with check,
     *                       6 - Multiple choice (or closing answers)
     *                      99 - History Separators
     * creation_date     - the date of creation of the node (the format is specified in ADA_DATE_FORMAT)
     * parent_id         - the id of this node's parent (same format as the main id)
     * order             - the order relative to the group
     * level             - the level at which the node is visible in the course (0 - 3)
     * version           - version of the node (not used yet)
     * contacts          - number of contacts that the node has received
     * icon              - name of the graphical file containing the icon of the node
     *                     the path is built out of the modello_corso.media_path db field or
     *                     from applicazion configuration parameters
     * bgcolor           - the background color of this node
     * color             - the color of the caption (uh?)
     * correctness       - if the node is of type 3 or 4 (answers), give the correctness
     *                     (0-10 or 0-100 or 0-whateverYouLike) of the answer, else it must be NULL
     * copyright         - boolean (0, 1) if a copyright is held by the author on this node (useful for node modification)
     *
     * links_ar          - array of links associated to the node
     *                     each element will be an associative array link_ha of this form:
     *                     id_author             - unique id of the author of the link (student or author)
     *                     id_to_node            - the node the link points to
     *                     array(x0, y0, x1, y1) - coordinates
     *                     type                  - type of the link (don't know)
     *                     creation_date         - creation date (format is ADA_DATE_FORMAT)
     *                     style                 - graphical style in the map
     *                                             (0 - Continue, 1 - Dotted line, 2 - Small dots, ...)
     *                     meaning               - ???
     *                     action                                 - what to do on click
     *                                             (0 - Jump, 1 - popup, 2 - open application)
     *
     * resources_ar      - array of external resources associated to this node
     *                     each element will be an associative array resource_ha of this form:
     *                     file_name - the file name (unique)
     *                     type      - the type of resources (picture, audio, video, ...)
     *                     copyright - if a copyright exists on the resource
     *
     * actions_ar        - array of actions associated to this node
     *                     each element will be an associative array acition_ha of this form:
     *                       type - the type of action
     *                     text - the text of the action (unique)
     *
     * @param $author_id  - the id of the author of the node (the original author)
     *                     can be a student or an author
     *
     * @return an AMAError object if something goes wrong,
     *      true on success
     *
     */
    public function addNode($node_ha)
    {
        ADALogger::logDb("entered add_node");

        // data regarding the node's position
        $pos_x0 = ($node_ha['pos_x0']);
        $pos_y0 = ($node_ha['pos_y0']);
        $pos_x1 = ($node_ha['pos_x1']);
        $pos_y1 = ($node_ha['pos_y1']);

        // an array with the four coordinates will be useful
        $pos_ar = [$pos_x0, $pos_y0, $pos_x1, $pos_y1];

        // check that the author is not an administrator
        $user_ha = $this->getUserInfo($node_ha['id_node_author']);
        if (AMADB::isError($user_ha)) {
            return $user_ha;
        }
        $type = $user_ha['tipo'];

        ADALogger::logDb("looking for user type ... got $type");

        if ($type == AMA_TYPE_ADMIN) {
            return new AMAError(AMA_ERR_WRONG_USER_TYPE);
        }

        // check if the position already exists in the table
        ADALogger::logDb("checking if position ($pos_x0, $pos_y0)($pos_x1, $pos_y1) already exists ... ");
        $id = $this->doGetIdPosition($pos_ar);
        // FIXME: restituire l'errore?
        //vito, 23 jan 2009
        //if (!is_object($id)) {
        if (!AMADB::isError($id) && $id != -1) {
            ADALogger::logDb("it seems it does ($id)");
            $id_position = $id;
        } else {
            ADALogger::logDb("it didn't exist, inserting ...");
            // add row to table "posizione"
            $res = $this->doAddPosition($pos_ar);
            if (AMADB::isError($res)) {
                return $res;
            }
            $id_position = $this->doGetIdPosition($pos_ar);
            if (AMADB::isError($id_position)) {
                return $id_position;
            }

            ADALogger::logDb("done ($id_position)");
        }

        $node_ha['id_position'] = $id_position;
        // begin the transaction
        ADALogger::logDb("beginning node insertion transaction");
        $this->beginTransaction();

        // add row to table "nodo"
        $res = $this->doAddNode($node_ha);
        if (AMADB::isError($res)) {
            $err = $this->errorMessage(AMA_ERR_ADD) . "while in add_node(" . $node_ha['id'] . ")" .
                AMA_SEP . $res->getMessage();

            ADALogger::logDb("$err detected");
            return new AMAError($err);
        } else {
            $node_id = $res;
            $node_ha['id'] = $node_id;
            // add instruction to rollback segment
            ADALogger::logDb("node added to db, adding instruction to rbs");
            $this->rsAdd("doRemove_node", $node_id);
        }

        /*
     * if exists a parent node for this node, check if it has type ADA_LEAF_TYPE
     * and change it in ADA_GROUP_TYPE
        */
        if (isset($node_ha['parent_id']) && ($node_ha['parent_id'] != "")) {
            $parent_node_ha = $this->getNodeInfo($node_ha['parent_id']);
            // vito, 23 mar 2009.
            //            if ( AMADB::isError($parent_node_ha) )
            //            {
            //              return new AMAError(AMA_ERR_GET);
            //            }
            if (!AMADB::isError($parent_node_ha)) {
                if ($parent_node_ha['type'] == ADA_LEAF_TYPE) {
                    $result = $this->changeNodeType($node_ha['parent_id'], ADA_GROUP_TYPE);
                    if (AMADB::isError($parent_node_ha)) {
                        return $result;
                    }
                } elseif ($parent_node_ha['type'] == ADA_LEAF_WORD_TYPE) {
                    $result = $this->changeNodeType($node_ha['parent_id'], ADA_GROUP_WORD_TYPE);
                    if (AMADB::isError($parent_node_ha)) {
                        return $result;
                    }
                }
            }
        }
        ADALogger::logDb("resources added to db, committing node insertion");
        // add rows to table "LINK"
        // checking if the caller really wants to add some
        if (in_array('links_ar', array_keys($node_ha))) {
            // get the links' infoz
            $link_ar =  $node_ha['links_ar'];

            // add them to the DB
            $res = $this->addLinks($link_ar, $node_id);
            // FIXME: è corretto questo if?
            if (AMADB::isError($res)) {
                $err = $this->errorMessage(AMA_ERR_ADD) . "while in add_node($node_id)" .
                    AMA_SEP . $res->getMessage();

                $this->rollback();

                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                ADALogger::logDb("links added to db, adding instruction to rbs");
                $this->rsAdd("del_links", $node_id);
            }
        }


        // add row to table "extended_node"
        // checking if the caller really wants to add some
        if ($node_ha['type'] == ADA_LEAF_WORD_TYPE or $node_ha['type'] == ADA_GROUP_WORD_TYPE) {
            $res = $this->addExtensionNode($node_ha);
            if (AMADB::isError($res)) {
                $err = $this->errorMessage(AMA_ERR_ADD) . "while in add_node($node_id)" .
                    AMA_SEP . $res->getMessage();
                $this->rollback();
                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                ADALogger::logDb("links added to db, adding instruction to rbs");
                $this->rsAdd("del_extended_node", $node_id);
            }
        }
        // add rows to table "RISORSA_ESTERNA"
        // checking if the caller really wants to add some
        if (in_array('resources_ar', array_keys($node_ha))) {
            // get the resources' infoz
            $resources_ar = $node_ha['resources_ar'];
            // add them to the DB
            $res = $this->addMedia($resources_ar, $node_id);
            if (AMADataHandler::isError($res)) {
                $err = $this->errorMessage(AMA_ERR_ADD) . "while in add_node($node_id)" .
                    AMA_SEP . $res->getMessage();
                $this->rollback();
                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            }
            ADALogger::logDb("resources added to db, committing node insertion");
        }


        // everything was ok, so the commit can be issued
        $this->commit();
        return $node_id;
        //        return true;
    }

    /**
     * Set the node position
     *
     * @access public
     * @param $node_ha an associative array containing all the node's data
     * @return an AMAError object if something goes wrong, true on success
     *
     */
    public function setNodePosition($node_ha)
    {
        /*
         * vito, 23 jan 2009
         * check if node position was given
         */
        $id_node = $node_ha['id'];
        if (
            isset($node_ha['pos_x0']) && is_numeric($node_ha['pos_x0']) &&
            isset($node_ha['pos_x1']) && is_numeric($node_ha['pos_x1']) &&
            isset($node_ha['pos_y0']) && is_numeric($node_ha['pos_y0']) &&
            isset($node_ha['pos_y1']) && is_numeric($node_ha['pos_y1'])
        ) {
            $position_ar = [$node_ha['pos_x0'], $node_ha['pos_y0'], $node_ha['pos_x1'], $node_ha['pos_y1']];
            $position_id = $this->doGetIdPosition($position_ar);
            if (AMADB::isError($position_id)) {
                return $position_id;
            }

            if ($position_id == -1) {
                // if position not found
                $res = $this->doAddPosition($position_ar);
                if (AMADB::isError($position_ar)) {
                    return new AMAError($res);
                } else {
                    $id = $this->doGetIdPosition($position_ar);
                }
            } else {
                $id = $position_id;
            }

            $update_node_position_sql = 'id_posizione=' . $id;
        } else {
            $update_node_position_sql = '';
        }

        $sql = "select id_nodo from nodo where id_nodo = ?";
        ADALogger::logDb("Query: $sql");
        $id =  $this->getOnePrepared($sql, [$id_node]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("Query result: $id");
        if (empty($id)) {
            return new AMAError($this->errorMessage(AMA_ERR_NOT_FOUND) . " while in doEdit_node.");
        }
        // edit a row into table nodo
        $sql  = "update nodo set " . $update_node_position_sql;
        $sql  .= " where id_nodo = ?";
        ADALogger::logDb("trying updating the node position: $sql");

        $res = $this->queryPrepared($sql, [
            $id_node,
        ]);
        // if an error is detected, an error is created and reported
        if (AMADB::isError($res)) {
            ADALogger::logDb("error while updating node position, id $id_node result:" . $res->getMessage());
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " while in doEdit_node." .
                AMA_SEP . ": " . $res->getMessage());
        } else {
            ADALogger::logDb("updating node position, id $id_node successful;");
        }
        return true;
    }

    /**
     *
     * @param $node_id
     * @param $type
     * @return true  on success, false if node type was not changed, an AMAError
     *         object on failure
     */
    public function changeNodeType($node_id, $type)
    {
        $sql = "UPDATE nodo SET tipo=? WHERE id_nodo=?";

        $result = $this->queryPrepared($sql, [
            $type,
            $node_id,
        ]);
        if (AMADB::isError($result)) {
            return $result;
        }

        if ($result == 1) {
            return true;
        }
        return false;
    }

    /**
     * Updates the given node text.     *
     *
     * @param string $node_id The id of the node
     * @param string $text The new text for the node
     * @return AMAError
     */
    public function setNodeText($node_id, $text)
    {
        $sql = 'UPDATE nodo SET testo=? WHERE id_nodo=?';
        $values = [
            $text,
            $node_id,
        ];
        $result = $this->queryPrepared($sql, $values);
        if (AMADataHandler::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
        }
        return true;
    }

    /**
     * Remove a node and all informations related to it (position, links, resources and actions)
     * Also remove all history and bookmarks related to the node.
     * Transactions are not handled since no referential integrity must be checked.
     *
     * Nodes cannot be removed if a reference to them is found in table bookmark. (?)
     * Once a node is removed, all records refering to it in tables risorse_nodi and azioni_nodi
     * must be removed, too.
     *
     * @access public
     *
     * @param $node_id id of the node to be removed
     *
     * @return bool|AMAError an error if something goes wrong, true on success
     *
     */
    public function removeNode($node_id)
    {
        ADALogger::logDb("entered remove_node(node_id:$node_id)");

        /*
     * remove resources
        */
        $risorse_ar = $this->getColPrepared("select id_risorsa_ext from risorse_nodi where id_nodo=?", [$node_id]);
        if (AMADB::isError($risorse_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("removing " . count($risorse_ar) . " resources");
        if (count($risorse_ar)) {
            // delete all references to $node_id in risorse_nodi
            $res_risorse = $this->delMedia($risorse_ar, $node_id);
            if (AMADataHandler::isError($res_risorse)) {
                $err = $this->errorMessage(AMA_ERR_REMOVE) . "while in remove_node($node_id)" .
                    AMA_SEP .  $res_risorse->getMessage();

                ADALogger::logDb("error: $err");
                return new AMAError($err);
            }
            ADALogger::logDb("resources successfully removed");
        }

        /*
     * remove links
        */
        $links_ar = $this->getAllPrepared("select * from link where id_nodo=?", [$node_id]);
        if (AMADB::isError($links_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        ADALogger::logDb("removing " . count($links_ar) . " links");
        if (count($links_ar)) {
            // delete all references to $node_id in link
            $res_links = $this->delLinks($node_id);
            if (AMADataHandler::isError($res_links)) {
                $err = $this->errorMessage(AMA_ERR_REMOVE) .  "while in remove_node($node_id)" .
                    AMA_SEP . $res_links->getMessage();

                ADALogger::logDb("error: $err");
                return new AMAError($err);
            }
            ADALogger::logDb("links successfully removed");
        }

        /*
         * history cleaning
         */
        $sql = "delete from history_nodi where id_nodo=?";
        ADALogger::logDb("cleaning history_nodi: $sql");
        $res = $this->queryPrepared($sql, [$node_id]);
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->getMessage() . " detected, aborting");
            return new AMAError(AMA_ERR_REMOVE);
        }

        /*
         *  exercises history cleaning
         */
        $sql = "delete from history_esercizi where id_nodo=?";
        ADALogger::logDb("cleaning history_esercizi: $sql");
        $res = $this->queryPrepared($sql, [$node_id]);
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->getMessage() . " detected, aborting");
            return $res;
        }

        /*
         * bookmarks cleaning
         */
        $sql = "delete from bookmark where id_nodo=?";
        ADALogger::logDb("cleaning bookmark: $sql");
        $res = $this->queryPrepared($sql, [$node_id]);
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->getMessage() . " detected, aborting");
            return new AMAError(AMA_ERR_REMOVE);
        }
        //        ADALogger::logDb("cleaning successfully terminated");

        /*
         * extension node cleaning
         */
        $sql = "delete from extended_node where id_node=?";
        ADALogger::logDb("cleaning extended node: $sql");
        $res = $this->queryPrepared($sql, [$node_id]);
        if (AMADB::isError($res)) {
            ADALogger::logDb($res->getMessage() . " detected, aborting");
            return new AMAError(AMA_ERR_REMOVE);
        }
        ADALogger::logDb("cleaning successfully terminated");

        /*
         * node removal
         */
        $res_node = $this->doRemoveNode($node_id);
        if (AMADataHandler::isError($res_node)) {
            $err = $this->errorMessage(AMA_ERR_REMOVE) . "while in remove_node($node_id)" .
                AMA_SEP . $res_node->getMessage();

            ADALogger::logDb("error: $err");
            return new AMAError($err);
        }
        ADALogger::logDb("node $node_id successfully removed");

        return true;
    }

    /**
     * Remove a node and all its children recursively
     * The method calls recursively the remove_node method
     *
     * @access public
     *
     * @param $node_id id of the node to be removed
     *
     * @return an error if something goes wrong
     *
     */
    public function recursivedoRemoveNode($node_id)
    {
        ADALogger::logDb("entered remove_node(node_id:$node_id)");

        // retrieve children's ids
        $ids_ar = $this->getColPrepared("select id_nodo from nodo where id_nodo_parent=?", [$node_id]);
        if (AMADB::isError($ids_ar)) {
            ADALogger::logDb($ids_ar->getMessage() . " detected, aborting recursive removal");
            return new AMAError(AMA_ERR_GET);
        }

        // children removal loop
        foreach ($ids_ar as $id) {
            $res = $this->removeNode($node_id);
            if (AMADataHandler::isError($res)) {
                ADALogger::logDb($res->getMessage() . " detected, aborting recursive removal");
                return $res;
            }
        }

        return true;
    }


    /**
     * function get_nodes:
     * used to obtain nodes data.
     *
     * @param array $ids_nodes array that contains ids of desidered notes
     * @return array
     */
    public function getNodes($ids_nodes)
    {
        if (!is_array($ids_nodes)) {
            $ids_nodes = [$ids_nodes];
        }

        $sql = "SELECT * FROM nodo WHERE `id_nodo` IN (" . join(',', array_fill(0, count($ids_nodes), '?')) . ")";

        $tmp = $this->getAllPrepared($sql, $ids_nodes, AMA_FETCH_ASSOC);
        if (AMADataHandler::isError($tmp)) {
            return $tmp;
        } else {
            $result = [];
            foreach ($tmp as $k => $v) {
                $result[$v['id_nodo']] = $v;
            }
            return $result;
        }
    }

    /**
     * Get all the informations related to a node, except for links, resources and actions
     *
     * @access public
     *
     * @param $node_id id of the node
     *
     * @return an hash with all information about the node
     *         the keys to access the informations are:
     * author             - the author (hash: see get_author)
     * position           - the position (array: (x0, y0, x1, y1))
     * name               - the name of the node (what's a name for a node, anyway?)
     * title              - the title of the node
     * text               - the content of the node, i.e. the real stuff
     * type               - the type of node
     *                      (0 - Page, 1 - Group, 2 - Note, 3 - Multiple Answer,
     *                       4 - Free Answer, 5 - History separators (?))
     * creation_date      - the date of creation of the node (the format is specified in ADA_DATE_FORMAT)
     * parent_id          - the id of this node's parent (same format as the main id)
     * order              - the order relative to the group
     * level              - the level at which the node is visible in the course (0 - 3)
     * version            - version of the node (not used yet)
     * contacts           - number of contacts that the node has received
     * icon               - name of the graphical file containing the icon of the node
     *                      the path is built out of the modello_corso.media_path db field or
     *                      from applicazion configuration parameters
     * bgcolor            - background color of this node
     * color              - the color of the caption (uh?)
     * correctness        - if the node is of type 3 or 4 (answers), give the correctness
     *                      (0-10 or 0-100 or 0-whateverYouLike) of the answer, else it must be NULL
     * copyright          - boolean (0, 1) if a copyright is held by the author on this node
     *                      (useful in the future for node modification)
     *
     *         values related to links, resources and actions are not returned
     *         you have to call the proper functions
     *         Author is returned as a hash (see get_author).
     *         Position is returned as a four elements array (see get_position)
     *
     * @see get_author
     * @see get_position
     *
     */
    public function getNodeInfo($node_id)
    {
        // get a row from table nodo
        $sql  = "select id_utente, id_posizione, nome, titolo, testo, tipo, ";
        $sql .= "data_creazione, id_nodo_parent, ordine, ";
        $sql .= "livello, versione, n_contatti, icona, colore_didascalia, colore_sfondo,";
        $sql .= "correttezza, copyright, id_istanza, lingua, pubblicato";
        $sql .= " from nodo where id_nodo=?";
        $res_ar =  $this->getRowPrepared($sql, array_map(fn ($el) => trim($el, "'"), [$node_id]));
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }

        //if ((!$res_ar) OR (is_Object($res_ar))){
        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        // author is a hash

        $author_id = $res_ar[0];
        $author_ha = $this->getUserInfo($author_id);
        if (AMADB::isError($author_ha)) {
            // shall we stop action if author is not ok?
            //  return $author_ha;
            $author_ha = "";
        } else {
            if ($author_ha['tipo'] == AMA_TYPE_AUTHOR) {
                $author_ha = $this->getAuthor($author_id);  // more info on author than on students
                if (AMADB::isError($author_ha)) {
                    // shall we stop action if author is not ok?
                    // return $author_ha;
                    $author_ha = "";
                }
            }
        } // author
        // position is a four elements array
        $pos_id = $res_ar[1];
        $pos_ar = $this->getPosition($pos_id);

        if (AMADB::isError($pos_ar)) {
            // shall we stop action if position is not ok?
            //return $pos_ar;
            $pos_ar = "";
        }

        $res_ha['author']      = $author_ha;
        $res_ha['position']    = $pos_ar;
        $res_ha['name']        = $res_ar[2];
        $res_ha['title']       = $res_ar[3];
        $res_ha['text']        = $res_ar[4];
        $res_ha['type']        = $res_ar[5];
        $res_ha['creation_date']    = self::tsToDate($res_ar[6]);
        $res_ha['parent_id']   = $res_ar[7];
        $res_ha['ordine']      = $res_ar[8];
        $res_ha['order']       = $res_ar[8];
        $res_ha['level']       = $res_ar[9];
        $res_ha['version']     = $res_ar[10];
        $res_ha['contacts']    = $res_ar[11];
        $res_ha['icon']        = $res_ar[12];
        $res_ha['color']       = $res_ar[13];
        $res_ha['bgcolor']     = $res_ar[14];
        $res_ha['correctness'] = $res_ar[15];
        $res_ha['copyright']   = $res_ar[16];
        $res_ha['instance']    = $res_ar[17];
        $res_ha['language']    = $res_ar[18];
        $res_ha['published']   = $res_ar[19];


        return $res_ha;
    }

    /**
     * Get nodes where a keyword is in one of the fields specified
     *
     * @access public
     *
     * @param  $out_fields_ar an array containing the desired fields' names
     *         possible values are: nome, titolo, testo
     *
     * @param  $key the keyword or sentence to look for (a string)
     *
     * @param  $search_fields_ar array of fields where the key must be looked for
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see doFind_nodes_list
     */
    public function &findNodesListByKey($out_fields_ar, $key, $search_fields_ar)
    {
        $clause = '';
        $n = count($search_fields_ar);
        for ($i = 0; $i < $n; $i++) {
            if ($i < $n - 1) {
                $or = " OR ";
            } else {
                $or = "";
            }
            $clause .= $search_fields_ar[$i] . " LIKE '%" . $key . "%' " . $or;
        }

        return $this->doFindNodesList($out_fields_ar, $clause);
    }

    /**
     * Get nodes informations which satisfy a given clause
     * Only the fields specifiedin the $out_fields_ar parameter are inserted
     * in the result set.
     * This function is meant to be used by the public find_nodes_list_by_key()
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     * @return array|AMAError a bi-dimensional array containing these fields
     *
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     */
    public function &doFindNodesList($out_fields_ar, $clause = '')
    {
        // build comma separated string out of $field_list_ar array
        if (count($out_fields_ar)) {
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        }

        // add a 'where' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'where ' . $clause;
        }
        // do the query
        $res_ar =  $this->getAllPrepared("select id_nodo$more_fields from nodo $clause");
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get nodes informations in a course which satisfy a given clause
     * Only the fields specifiedin the $out_fields_ar parameter are inserted
     * in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     * @param $course_id
     *
     * @return a bi-dimensional array containing these fields
     *
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     */
    public function &findCourseNodesList($out_fields_ar, $clause = '', $course_id = '-1')
    {
        // build comma separated string out of $field_list_ar array
        if (is_array($out_fields_ar) && count($out_fields_ar)) {
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        } else {
            $more_fields = $out_fields_ar ?? '';
        }

        // add an 'and' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'and ' . $clause;
        }
        // vito, 16 giugno 2009
        $course_id .= "\_"; // $course_id.="\_"; ?

        // do the query
        $sqlquery = "select id_nodo$more_fields from nodo where id_nodo LIKE ? $clause";
        $res_ar =  $this->getAllPrepared($sqlquery, [$course_id . '%']);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get the children of a given node.
     *
     * @access public
     *
     * @param $node_id the id of the father
     *
     * @return an associative array of ids containing all the id's of the children of a given node
     *
     * @see get_node_info
     *
     */
    public function &getNodeChildrenInfo($node_id, $id_course_instance = "", $order = "ordine")
    {
        $params = [
            $node_id,
        ];
        if ($id_course_instance != "") {
            $sql  = "select id_nodo,ordine,nome,tipo,livello from nodo where id_nodo_parent=? AND id_istanza=? ORDER BY $order ASC";
            $params[] = $id_course_instance;
        } else {
            $sql  = "select id_nodo,ordine,nome,tipo,livello from nodo where id_nodo_parent=? ORDER BY $order ASC";
        }
        $res_ar = $this->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
        //$res_ar =  $db->getCol($sql);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        // return an error in case of an empty recordset
        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get the children of a given node.
     *
     * @access public
     *
     * @param $node_id the id of the father
     *
     * @return array|AMAError an array of ids containing all the id's of the children of a given node
     *
     * @see get_node_info
     *
     */
    public function &getNodeChildren($node_id, $id_course_instance = "")
    {
        $params = [
            $node_id,
        ];
        if ($id_course_instance != "") {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent=? AND id_istanza=? ORDER BY ordine ASC";
            $params[] = $id_course_instance;
        } else {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent=? ORDER BY ordine ASC";
        }
        $res_ar =  $this->getColPrepared($sql, $params);
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // return an error in case of an empty recordset
        if (!$res_ar) {
            $retval = new AMAError(AMA_ERR_NOT_FOUND);
            return $retval;
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get the childrens of a given node.
     *
     * @access public
     *
     * @param $node_id the id of the father
     *
     * @return an array of all data containing of the children of a given node
     *
     * @see get_node_info
     *
     */
    public function &getNodeChildrenComplete($node_id, $id_course_instance = "")
    {
        $params = [
            $node_id,
        ];
        if ($id_course_instance != "") {
            $sql  = "select * from nodo where id_nodo_parent=? AND id_istanza=? ORDER BY ordine ASC";
            $params[] = $id_course_instance;
        } else {
            $sql  = "select * from nodo where id_nodo_parent=? ORDER BY ordine ASC";
        }
        $res_ar =  $this->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // return an error in case of an empty recordset
        if (!$res_ar) {
            $retval = new AMAError(AMA_ERR_NOT_FOUND);
            return $retval;
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get all external links associated to the given node.
     * A list of ids is returned in an array. Each id can be used as a
     * parameter for the function get_link_info, to retrieve all
     * about the link.
     *
     * @access public
     *
     * @param $node_id the node
     *
     * @return an array of link ids
     *
     * @see get_link_info
     *
     */
    public function &getNodeLinks($node_id)
    {
        // do the select
        $sql  = "select id_link from link where id_nodo=?";
        $res_ar =  $this->getColPrepared($sql, [$node_id]);
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        // return an error in case of an empty recordset
        if (!$res_ar) {
            $retval = new AMAError(AMA_ERR_NOT_FOUND);
            return $retval;
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get all external resources associated to the given node
     *
     * @access public
     *
     * @param $nod_id the node
     *
     * @param $mediatype the type of media to return.
     *                   The default empty value meaning all types are returned.
     *
     * @return an array of hashes, similar to those used in add_node
     *
     * @see add_node
     *
     */
    public function &getNodeResources($node_id, $mediatype = "")
    {
        // do the select
        $sql  = "select id_risorsa_ext from risorse_nodi where id_nodo=?";
        $res_ar =  $this->getColPrepared($sql, [$node_id]);
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        // return an error in case of an empty recordset
        if (!$res_ar) {
            $retval = new AMAError(AMA_ERR_NOT_FOUND);
            return $retval;
        }
        // return nested array
        return $res_ar;
    }

    /**
     * Get all actions associated with the given node
     *
     *
     * @access public
     *
     * @param $nod_id the node
     *
     * @return an array of hashes, similar to those used in add_node
     *
     * @see add_node
     *
     */
    public function &getNodeActions($node_id)
    {
        // do the select
        $sql  = "select id_azione from azioni_nodi where id_nodo=?";
        $res_ar =  $this->getColPrepared($sql, [$node_id]);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        // return an error in case of an empty recordset
        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        return $res_ar;
    }

    /**
     *
     * Get all node and group of a course (doesn't return node type NOTE and WORD)
     *
     * @param $id_course
     * @param $required_info
     * @param $order_by_name
     * @param $id_course_instance
     * @param $id_student
     * @return unknown_type
     */
    public function getCourseData($id_course, $required_info = null, $order_by_name = false, $id_course_instance = null, $id_student = null, $user_level = null)
    {
        if ($order_by_name) {
            $ORDER = "N.nome ASC";
        } else {
            $ORDER = "N.id_nodo_parent, N.ordine ASC";
        }

        $params = [];

        switch ($required_info) {
            case 1: // get NODE n_contatti field, author has no instance
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.n_contatti AS numero_visite, N.icona, N.livello, N.ordine, N.titolo as keywords
                FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo  OR N2.id_nodo_parent = 'NULL' OR N2.id_nodo_parent IS NULL)";
                break;

            case 2:
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.icona, visite.numero_visite
                FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo OR N2.id_nodo_parent = 'NULL' OR N2.id_nodo_parent IS NULL)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                break;

            case 3:
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.livello, N.icona, visite.numero_visite, N.titolo
                FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo OR N2.id_nodo_parent = 'NULL' OR N2.id_nodo_parent IS NULL)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso AND id_utente_studente=:id_utente_studente
                GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                $params['id_utente_studente'] = $id_student;
                break;

            case null:
            default:
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.icona, N.n_contatti AS numero_visite
                FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo OR N2.id_nodo_parent = 'NULL' OR N2.id_nodo_parent IS NULL)";
                break;
        }

        $sql .= " WHERE N.id_nodo LIKE :node_like AND N2.id_nodo LIKE :node_like" .
            " AND N.tipo NOT IN (" . ADA_NOTE_TYPE . "," . ADA_PRIVATE_NOTE_TYPE . ") AND N.tipo NOT IN (" . ADA_LEAF_WORD_TYPE . "," .
            ADA_GROUP_WORD_TYPE . ") AND N2.tipo in (" . ADA_LEAF_TYPE . "," . ADA_GROUP_TYPE . ") GROUP BY N.id_nodo ORDER BY " . $ORDER;

        $params['node_like'] = $id_course . "\_%";

        $result = $this->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     *
     * Get all node and group of a course (doesn't return node type NOTE and WORD)
     *
     * @param $id_course
     * @param $required_info
     * @param $order_by_name
     * @param $id_course_instance
     * @param $id_student
     * @return unknown_type
     */
    public function getGlossaryData($id_course, $required_info = null, $order_by_name = false, $id_course_instance = null, $id_student = null)
    {
        if ($order_by_name) {
            $ORDER = "N.nome ASC";
        } else {
            $ORDER = "N.id_nodo_parent, N.nome ASC, N.ordine ASC";
        }

        $params = [];

        switch ($required_info) {
            case 1: // get NODE n_contatti field
                $sql = " SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.n_contatti, N.icona FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)";
                break;

            case 2:
                $sql = " SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.icona, visite.numero_visite FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                break;

            case 3:
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.livello, N.icona, visite.numero_visite FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso AND id_utente_studente=:id_utente_studente
                GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                $params['id_utente_studente'] = $id_student;
                break;

            case null:
            default:
                $sql = "SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.icona FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)";
                break;
        }

        $sql .= "WHERE N.id_nodo LIKE :node_like AND N2.id_nodo LIKE :node_like" .
            " AND N.tipo NOT IN (" . ADA_NOTE_TYPE . "," . ADA_PRIVATE_NOTE_TYPE . ") AND N.tipo NOT IN (" . ADA_LEAF_TYPE . "," . ADA_GROUP_TYPE . ") AND N.tipo in (" . ADA_LEAF_WORD_TYPE . "," . ADA_GROUP_WORD_TYPE . ") ORDER BY " . $ORDER;

        $params['node_like'] = $id_course . "\_%";

        $result = $this->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     *
     * @param $id_course
     * @param $id_course_instance
     * @param $required_info
     * @param $order_by_name
     * @param $id_student
     * @return unknown_type
     */
    public function getForumData($id_course, $id_course_instance, $required_info = null, $order_by_name = false, $id_student = null)
    {
        if ($order_by_name) {
            $ORDER = "N.nome ASC";
        } else {
            $ORDER = "N.id_nodo_parent ASC";
        }

        $params = [];

        switch ($required_info) {
            case 3:
                $sql = " SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.livello, N.icona, visite.numero_visite FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso AND id_utente_studente=:id_utente_studente
                GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                $params['id_utente_studente'] = $id_student;
                break;

            case null:
            case 2:
            default:
                $sql = " SELECT N.id_nodo, N.nome, N.tipo, N.id_nodo_parent, N.icona, visite.numero_visite FROM nodo AS N LEFT JOIN nodo AS N2 ON (N.id_nodo_parent = N2.id_nodo)
                LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi
                WHERE id_istanza_corso=:id_istanza_corso GROUP BY id_nodo) AS visite ON (N.id_nodo=visite.id_nodo)";
                $params['id_istanza_corso'] = $id_course_instance;
                break;
        }

        $sql .= "WHERE N.id_nodo LIKE :node_like AND N2.id_nodo LIKE :node_like" .
            " AND N.tipo  IN (" . ADA_NOTE_TYPE . "," . ADA_PRIVATE_NOTE_TYPE . ") " .
            " AND N2.tipo in (" . ADA_LEAF_TYPE . "," . ADA_GROUP_TYPE . "," . ADA_NOTE_TYPE . "," . ADA_PRIVATE_NOTE_TYPE . ") ORDER BY " . $ORDER;

        $params['node_like'] = $id_course . "\_%";

        $result = $this->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Methods accessing table `posizione`
     */
    // MARK: Methods accessing table `posizione`

    /**
     * Add a position to table posizione
     *
     * @access private
     *
     * @param pos_ar - the four elements array containing the x0, y0, x1, y1 coordinates
     *
     * @return true|AMAError on success, an AMAError object on failure
     */
    protected function doAddPosition($pos_ar)
    {
        ADALogger::logDb("entered doAdd_position (" . serialize($pos_ar) . ")");

        // extract coordinates from array
        [$x0, $y0, $x1, $y1] = $pos_ar;

        $id =  $this->getOnePrepared("select id_posizione from posizione where x0=? AND y0=? AND x1=? AND y1=?", [$x0, $y0, $x1, $y1]);
        if ($id) {
            return new AMAError(AMA_ERR_UNIQUE_KEY);
        }

        // add a row into table posizione
        $sql =  "insert into posizione (x0, y0, x1, y1) values (?, ?, ?, ?);";
        ADALogger::logDb("inserting with query: $sql");
        $res = $this->queryPrepared($sql, [$x0, $y0, $x1, $y1]);
        if (AMADB::isError($res)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) .
                " while in doAdd_position");
        }
        return true;
    }

    /**
     * Remove a position to table posizione [private]
     * A position is removed only if no node is still using it
     *
     * @access private
     *
     * @param id - the id of the position to remove
     *
     * @return true on success, ana AMAError object on failure
     */
    private function removePosition($id)
    {
        // check referential integrity with table nodo
        $ri_id = $this->getOnePrepared("select id_nodo from nodo where id_posizione=?", [$id]);
        if ($ri_id) {
            return new AMAError($this->errorMessage(AMA_ERR_REF_INT_KEY) .
                " while in remove_position($id)");
        }
        $sql = "delete from posizione where id_posizione=?";

        $res = $this->executeCriticalPrepared($sql, [$id]);
        if (AMADB::isError($res)) {
            return $res;
        }

        return true;
    }

    /**
     * Get a position's id from the coordinates array
     *
     * @access protected
     *
     * @param pos_ar - the four elements array containing the x0, y0, x1, y1 coordinates
     *
     * @return the id if it's found existsing, -1 otherwise, ana AMAError object on failure
     *
     *  @author giorgio 16/lug/2013
     *  modified access to proteced (was private) because this method is needed in the
     *  import/export course module own datahandler
     */
    protected function doGetIdPosition($pos_ar)
    {
        // simple names for the coordinates
        [$x0, $y0, $x1, $y1] = $pos_ar;

        // look for the position
        $id =  $this->getOnePrepared("select id_posizione from posizione where x0=? and y0=? and x1=? and y1=?", [$x0, $y0, $x1, $y1]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }

        if ($id) {
            return $id;
        } else {
            return -1;
        }
    }

    /**
     * Get a position array out of table posizione
     * the array (x0, y0, x1, y1) corresponding to the given $id is returned
     *
     * @ access private
     *
     * @param $id id of the position to extract
     *
     * @return an array of four elements on success, ana AMAError object on failure
     *
     */
    protected function getPosition($id)
    {
        $result =  $this->getRowPrepared("select x0, y0, x1, y1 from posizione where id_posizione=?", [$id], AMA_FETCH_DEFAULT);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Methods accessing table `risorsa_esterna`, `risorse_nodi`
     */
    // MARK: Methods accessing table `risorsa_esterna`, `risorse_nodi`

    /**
     * Add a record to table risorsa_esterna
     *
     * @access public
     *
     * @param $res_ha hash containing the information to be added
     * nome_file  - name of the file (path is specified as a config param or using corso.media_path)
     * type of the external resource
     *              0 -  Image (jpeg, png)
     *              1 -  Sound (wav, mp3, midi, au, ra)
     *              2 -  Video (real, quicktime, avi, mpeg)
     *              3 -  Doc (Excel, Word, Rtf, txt, pdf)
     *              4 -  link esterno (URL)
     *
     *
     *
     * copyright  - if the resource has a copytight or not (boolean)
     *
     * @param bool forceDuplicate true to force duplicate filename insertion. defaults to false
     * @return
     *  - the id of the resource just inserted on success
     *  - an Error on failure
     */
    public function addRisorsaEsterna($res_ha, $forceDuplicate = false)
    {
        ADALogger::logDb("entered add_risorsa_esterna");

        $nome_file = $res_ha['nome_file'] ?? '';
        $tipo = $res_ha['tipo'] ?? '';
        $copyright = $this->orZero($res_ha['copyright']);
        $id_nodo = $res_ha['id_nodo'] ?? '';
        $keywords = $res_ha['keywords'] ?? '';
        $titolo = $res_ha['titolo'] ?? '';
        $pubblicato = $this->orZero($res_ha['pubblicato'] ?? null);
        $descrizione = $res_ha['descrizione'] ?? '';
        $lingua = $this->orZero($res_ha['lingua'] ?? null);

        // vito, 19 luglio 2008
        $id_utente = $this->orZero($res_ha['id_utente']);

        ADALogger::logDb("nome: $nome_file");
        ADALogger::logDb("tipo: $tipo");
        ADALogger::logDb("copyright: $copyright");
        ADALogger::logDb("id_utente: $id_utente");

        // check values
        if (empty($nome_file)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_risorsa_esterna " .
                AMA_SEP .  ": empty file name");
        }

        if ($tipo < 0 || $tipo > POSSIBLE_TYPE) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_risorsa_esterna " .
                AMA_SEP . ": undefined type");
        }

        // gets the ids of all the resources having the same names
        // as the one that has to be inserted before the insertion
        $sql = "select id_risorsa_ext from risorsa_esterna where nome_file=?";
        ADALogger::logDb("getting resources: $sql");
        $id = $this->getOnePrepared($sql, [$nome_file]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($id) || $forceDuplicate) {
            // insert a row into table risorsa_esterna
            $sql  = "insert into risorsa_esterna (nome_file, tipo, copyright,id_utente, keywords, titolo, descrizione, pubblicato, lingua)";
            $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            ADALogger::logDb("inserting: $sql");

            $res = $this->executeCriticalPrepared($sql, [
                $nome_file,
                $tipo,
                $copyright,
                $id_utente,
                $keywords,
                $titolo,
                $descrizione,
                $pubblicato,
                $lingua,
            ]);
            if (AMADB::isError($res)) {
                return $res;
            }

            // preleva l'id della risorsa appena inserita
            $sql = "select id_risorsa_ext from risorsa_esterna where nome_file=?";
            if ($forceDuplicate) {
                $sql .= ' ORDER BY id_risorsa_ext DESC';
            }
            ADALogger::logDb("getting resources: $sql");
            $id = $this->getOnePrepared($sql, [$nome_file]);
            if (AMADB::isError($id)) {
                return new AMAError(AMA_ERR_GET);
            }
            // crea relazione tra il nodo e la risorsa esterna
            $res1 = $this->addRisorseNodi($id_nodo, $id);
            if (AMADB::isError($res1)) {
                return new AMAError($this->errorMessage(AMA_ERR_ADD) .  " while in risorse_nodi" .
                    AMA_SEP . ": " . $res1->getMessage());
            }
        } else {
            // return minus id if the resource was already there (a dirty trick!)
            $id = -1 * (int) $id;
        }

        ADALogger::logDb("returning: " . $id);
        return $id;
    }

    /**
     *
     * @param $res_ha
     * @return unknown_type
     */
    public function addOnlyInRisorsaEsterna($res_ha)
    {
        $nome_file = $res_ha['nome_file'] ?? '';
        $titolo = $res_ha['titolo'] ?? '';
        $tipo      = $res_ha['tipo'] ?? null;
        $copyright = $this->orZero($res_ha['copyright'] ?? '');
        $id_utente = $this->orZero($res_ha['id_utente'] ?? '');
        $keywords = $res_ha['keywords'] ?? '';
        $pubblicato = $this->orZero($res_ha['pubblicato'] ?? '');
        $descrizione = $res_ha['descrizione'] ?? '';
        $lingua = $res_ha['lingua'] ?? '';

        // check values
        if (empty($nome_file)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) .  " in add_only_in_risorsa_esterna " .
                AMA_SEP . ": empty file name");
        }

        if ($tipo < 0 || $tipo > POSSIBLE_TYPE) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) . " in add_only_in_risorsa_esterna " .
                AMA_SEP . ": undefined type");
        }

        // gets the ids of all the resources having the same names and the same owner
        // as the one that has to be inserted before the insertion
        $sql = "select id_risorsa_ext from risorsa_esterna where nome_file = ? and id_utente = ?";
        $id = $this->getOnePrepared($sql, [
            $nome_file,
            $id_utente,
        ]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (empty($id)) {
            // insert a row into table risorsa_esterna
            $sql  = "insert into risorsa_esterna (nome_file, tipo, copyright, id_utente, keywords, titolo, descrizione, pubblicato, lingua)";
            $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $res = $this->executeCriticalPrepared($sql, [
                $nome_file,
                $tipo,
                $copyright,
                $id_utente,
                $keywords,
                $titolo,
                $descrizione,
                $pubblicato,
                $lingua,
            ]);
            if (AMADB::isError($res)) {
                return $res;
            }
        } else {
            $id = -1 * (int) $id;
        }
        return $id;
    }

    /**
     * Remove a record from risorsa_esterna
     * referential integrity is checked against risorse_nodi
     * this must have no records related to the external resource to remove
     *
     * @access public
     *
     * @param res_id the id of the external resource to be removed
     */

    public function removeRisorsaEsterna($res_id)
    {
        ADALogger::logDb("entering remove_risorsa_esterna (res_id:$res_id)");

        $ri_id = $this->getOnePrepared("select id_nodo from risorse_nodi where id_risorsa_ext=?", [$res_id]);
        if (AMADB::isError($ri_id)) {
            return new AMAError(AMA_ERR_GET);
        }

        ADALogger::logDb("got: " . (is_array($ri_id) ? count($ri_id) : 0) . " records in risorse_nodi still referring to resource $res_id");
        if (empty($ri_id)) {
            $sql = "delete from risorsa_esterna where id_risorsa_ext=?";
            ADALogger::logDb("deleting record: $sql");
            $res = $this->queryPrepared($sql, [$res_id]);
            if (AMADB::isError($res)) {
                return new AMAError(AMA_ERR_REMOVE);
            }
            return $res;
        }
        // if there was at least one reference to $res_id into risorse_nodi
        // return without doing anything
        return 0;
    }

    /**
     * Get external resource info starting from the file name and the Id_node
     *
     *
     * @param $file_name - file name of the resource
     * @param $id_node - the id of the current node
     *
     * @return the array containes the onfo about the resource or null or an error value
     *
     */
    public function getRisorsaEsternaInfoFromFilename($filename, $id_node)
    {
        $sql = "select RE.id_risorsa_ext,RE.nome_file,RE.tipo,RE.copyright, RE.id_utente, RE.keywords,RE.titolo, RE.descrizione, RE.pubblicato,RE.lingua, RN.id_nodo from risorsa_esterna as RE, risorse_nodi as RN where RE.nome_file = ? and RE.id_risorsa_ext = RN.id_risorsa_ext and RN.id_nodo = ?";

        $resourceInfoAr =  $this->getRowPrepared($sql, [$filename, $id_node], AMA_FETCH_ASSOC);
        //        $resourceInfoAr =  $db->getRow("select id_risorsa_ext, nome_file, tipo, copyright, id_utente, keywords, titolo, descrizione, pubblicato, lingua from risorsa_esterna where nome_file=$sqlfilename");
        if (AMADB::isError($resourceInfoAr)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $resourceInfoAr;
    }

    /**
     * Get external resource id starting from the file name
     *
     *
     * @param $file_name - file name of the resource
     *
     * @return the id of the resource or null or an error value
     *
     */
    public function getRisorsaEsternaId($filename)
    {
        $id =  $this->getOnePrepared("select id_risorsa_ext from risorsa_esterna where nome_file=?", [$filename]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $id;
    }

    /**
     * Get external resource id starting from the file name
     *
     *
     * @param $file_name - substring of file name of the resource o
     *
     * @return an array of ids of the resource or null or an error value
     *
     */
    public function getRisorsaEsternaIds($filename)
    {
        $sqlquery = "select id_risorsa_ext from risorsa_esterna where nome_file LIKE ?";
        $idAr =  $this->getColPrepared($sqlquery, ['%' . $filename . '%']);
        if (AMADB::isError($idAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $idAr;
    }

    /**
     * Get a node id starting from external resource id
     *
     *
     * @param $res_id - idof the resource
     *
     * @return the id of the node or null or an error value
     *
     */
    public function getNodoRisorsaEsternaId($res_id)
    {
        $id =  $this->getOnePrepared("select id_nodo  from risorse_nodi where id_risorsa_ext = ?", [$res_id]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $id;
    }

    /**
     * Get the extended node data starting from node id
     *
     *
     * @param $node_id - id of node
     *
     * @return all data of the extended node or null or an error value
     *
     */
    public function getExtendedNode($node_id)
    {
        $sql = "select *  from extended_node where id_node = ?";
        $extended_nodeHA =  $this->getRowPrepared($sql, [$node_id], AMA_FETCH_ASSOC);
        if (AMADB::isError($extended_nodeHA)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $extended_nodeHA;
    }

    /**
     *
     * @param $search_text
     * @param $course_id
     * @param $user_level
     * @return unknown_type
     */
    public function findMediaInCourse($search_text, $course_id, $user_level = null)
    {
        $sql = "SELECT  RE.nome_file, RE.tipo, N.id_nodo, N.id_utente, N.nome, N.titolo
                FROM risorsa_esterna AS RE, risorse_nodi AS RN, nodo AS N
                WHERE RE.nome_file like ?
                AND RN.id_risorsa_ext = RE.id_risorsa_ext
                AND RN.id_nodo LIKE ?
                AND N.id_nodo = RN.id_nodo";
        if ($user_level != null && is_numeric($user_level)) {
            $sql .= " AND N.livello <= $user_level";
        }

        $result = $this->getAllPrepared($sql, [
            "%" . $search_text . "%",
            $course_id . "\_%",
        ], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Get all info from the record of risorsa_esterna_info identified by $res_id
     *
     * @param $res_id the id of the record to query
     *
     * @return an hash containing the following values:
     * nome_file         - the id of the node this link lives in
     * tipo              - type of resource
     *                     0 -  Image (jpeg, png)
     *                     1 -  Sound (wav, mp3, midi, au, ra)
     *                     2 -  Video (real, quicktime, avi, mpeg)
     *                     3 -  Doc (Excel, Word, Rtf, txt, pdf)
     * copyright         - if the resource has a copyright or not
     *                     0 - no
     *                     1 - yes
     * id_utente        - the id of the user that added this media
     * keywords         - the keywords of the media (separated by coma)
     * titolo           - title of the media
     * descrizione      - description of the media
     * pubblicato       - published or not (0 = not published. 1 = published)
     * lingua           - the language (numeric value). Contain the id_lingua to point to common.lingue table
     *
     */
    public function getRisorsaEsternaInfo($res_id)
    {
        $sql = 'SELECT nome_file, tipo, copyright, id_utente, keywords, titolo, descrizione, pubblicato, lingua'
            . ' FROM risorsa_esterna WHERE id_risorsa_ext=?';
        $values = [$res_id];

        $result = $this->getRowPrepared($sql, $values, AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (!$result) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /**
     * Get all info from the record of risorsa_esterna_info identified by $filename AND $author_id
     *
     * @param $res_id the id of the record to query
     *
     * @return an hash containing the following values:
     * nome_file         - the id of the node this link lives in
     * tipo              - type of resource
     *                     0 -  Image (jpeg, png)
     *                     1 -  Sound (wav, mp3, midi, au, ra)
     *                     2 -  Video (real, quicktime, avi, mpeg)
     *                     3 -  Doc (Excel, Word, Rtf, txt, pdf)
     * copyright         - if the resource has a copyright or not
     *                     0 - no
     *                     1 - yes
     * id_utente        - the id of the user that added this media
     * keywords         - the keywords of the media (separated by coma)
     * titolo           - title of the media
     * descrizione      - description of the media
     * pubblicato       - published or not (0 = not published. 1 = published)
     * lingua           - the language (numeric value). Contain the id_lingua to point to common.lingue table
     *
     */
    public function getRisorsaEsternaInfoAutore($filename, $author_id)
    {
        $sql = 'SELECT id_risorsa_ext, nome_file, tipo, copyright, id_utente, keywords, titolo, descrizione, pubblicato, lingua'
            . ' FROM risorsa_esterna WHERE nome_file=? AND id_utente=?';
        $values = [$filename, $author_id];

        $result = $this->getRowPrepared($sql, $values, AMA_FETCH_ASSOC);

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (!$result) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        return $result;
    }

    /*
     * function set_risorsa_esterna, used to update the media info
     * @param int $id_risorsa
     * @param array $media
     */

    public function setRisorsaEsterna($id_risorsa, $media)
    {
        $update_risorsa_sql = 'UPDATE risorsa_esterna SET copyright=?, keywords=?, titolo=?, tipo=?,'
            . 'descrizione=?, pubblicato=?, lingua=? WHERE id_risorsa_ext=?';
        $valuesAr = [
            $media['copyright'],
            $media['keywords'],
            $media['titolo'],
            $media['tipo'],
            $media['descrizione'],
            $media['pubblicato'],
            $media['lingua'],
            $id_risorsa,
        ];
        $result = $this->queryPrepared($update_risorsa_sql, $valuesAr);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
        }
        return true;
    }

    /**
     * function get_risorse_autore, used to get info about author's media in
     * table risorsa_esterna filtered by media type in $media.
     *
     * @param int $author_id
     * @param array $media
     * @return array
     */
    public function getRisorseAutore($author_id, $media = [])
    {
        if (count($media) > 0) {
            $get_media = "";
            while (count($media) > 1) {
                $media_type = array_shift($media);
                $get_media .= "$media_type,";
            }
            if (count($media) == 1) {
                $media_type = array_shift($media);
                $get_media .= "$media_type";
            }
            $sql = "SELECT nome_file, tipo FROM risorsa_esterna WHERE id_utente=? AND tipo IN(" . $get_media . ")";
        } else {
            $sql = "SELECT nome_file, tipo FROM risorsa_esterna WHERE id_utente=?";
        }
        $result = $this->getAllPrepared($sql, [$author_id], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Add a record to table risorse_nodi
     *
     * @access public
     *
     * @param $node_id id of the node to be added
     * @param $res_id  id of the resource to be added
     *
     * @return true|AMAError on success, an AMAError object on failure
     */
    public function addRisorseNodi($sqlnode_id, $res_id)
    {
        ADALogger::logDb("entered add_risorse_nodi (node_id: $sqlnode_id, res_id: $res_id)");

        if ($sqlnode_id == "''") {
            ADALogger::logDb("passed node id is empty, returning true right away");
            return true;
        }

        // Modified 29/11/01 by Graffio
        // check if the already exists in the table
        ADALogger::logDb("checking if resource $res_id and node $sqlnode_id already exists in table risorse_nodi ... ");
        $sql_temp = "select id_nodo from risorse_nodi where id_nodo=? and id_risorsa_ext=?";
        $id = $this->getOnePrepared($sql_temp, [$sqlnode_id, $res_id]);

        if (AMADB::isError($id)) {
            ADALogger::logDb("Error while checking resource in risorse_nodi in query $sql_temp)");
            return new AMAError(AMA_ERR_GET);
        }

        if ($id) {
            ADALogger::logDb("it seems it does ($id)");
        } else {
            $sql = "insert into risorse_nodi (ID_NODO, ID_RISORSA_EXT) values (?, ?)";
            ADALogger::logDb("inserting using query: $sql");
            $res = $this->executeCriticalPrepared($sql, [$sqlnode_id, $res_id]);
            if (AMADB::isError($res)) {
                return new AMAError(AMA_ERR_ADD);
            }
        }
        return true;
    }

    /**
     * Delete one or more rows from table risorse_nodi
     *
     * @access private
     *
     * @param $node_id id of the node to be removed
     *                 removes all record of that node if $res_id is null
     * @param $res_id  id of the resource to be removed
     *                 a resource cannot be removed if
     *
     * @return a db query result object
     */
    public function delRisorseNodi($sqlnode_id, $res_id = '')
    {
        ADALogger::logDb("entering del_risorse_nodi (sqlnode_id: $sqlnode_id, res_id: $res_id)");
        $params = [
            $sqlnode_id,
        ];
        $sql = "delete from risorse_nodi where id_nodo=?";

        if ($res_id != '') {
            $sql .= " and id_risorsa_ext = ?";
            $params[] = $res_id;
        }
        ADALogger::logDb("deleting record: $sql");

        $result = $this->queryPrepared($sql, $params);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_REMOVE);
        }
        return true;
    }

    /**
     * Insert multiple records into table risorse_nodi
     *
     * @access private
     *
     * @param $risorse_ar array containing the records as resulting from db->getAll
     *
     * @return a db query result object
     */
    private function restoreRisorseNodi($risorse_ar)
    {
        for ($i = 0; $i < count($risorse_ar); $i++) {
            $node_id = $risorse_ar[$i][0];
            $res_id = $risorse_ar[$i][1];
            $result = $this->addRisorseNodi($node_id, $res_id);
            if (AMADB::isError($result)) {
                // FIXME: restituire l'errore o lasciare proseguire?
            }
        }
        // FIXME: per poter restituire true qui non devono essersi verificati errori
        return true;
    }

    /**
     * Insert multiple resources into tables risorsa_esterna and risorse_nodi
     * (within a transaction)
     *
     * @access private
     *
     * @param $risorse_ar array containing the infos
     *        each element has the same structure as that of the hash
     *        passed to add_risorsa_esterna
     *
     * @return an AMAError object if something goes wrong, true on success
     */
    private function addMedia($risorse_ar, $sqlnode_id)
    {
        ADALogger::logDb("entered add_media");
        $n = count($risorse_ar);

        ADALogger::logDb("got $n resources to add");
        if ($n > 0) {
            ADALogger::logDb("starting a transaction");
            $this->beginTransaction();

            for ($i = 1; $i <= $n; $i++) {
                $res_ha = $risorse_ar[$i];
                ADALogger::logDb("adding resource $i to risorsa esterna");

                $res_id = $this->addRisorsaEsterna($res_ha);
                if (AMADB::isError($res_id)) {
                    // does the rollback
                    $err  = $res_id->getMessage() . AMA_SEP . $this->rollback();
                    ADALogger::logDb("$err detected, rollbacking");
                    return new AMAError($err);
                } else {
                    // add instruction to rollback segment only if a new resource was inserted
                    if ($res_id > 0) {
                        ADALogger::logDb("done ($res_id), adding instruction to rbs");
                        $this->rsAdd("remove_risorsa_esterna", $res_id);
                    } else {
                        // revert $res_id to positive for future needs
                        $res_id *= -1;
                    }
                }

                ADALogger::logDb("adding resource $i to risorse_nodi");
                $res = $this->addRisorseNodi($sqlnode_id, $res_id);

                if (AMADB::isError($res)) {
                    // does the rollback
                    $err  = $res->getMessage() . AMA_SEP . $this->rollback();
                    ADALogger::logDb("$err detected, rollbacking");
                    return new AMAError($err);
                } else {
                    // add instruction to rollback segment
                    ADALogger::logDb("done, adding instruction to rbs");
                    $this->rsAdd("del_risorse_nodi", $sqlnode_id, $res_id);
                }
            }
            ADALogger::logDb("committing resources insertion");
            $this->commit(); // FIXME: e' il posto giusto per $this->commit?
        }
        return true;
    }

    /**
     * Remove all records related to an external resource from the tables
     * (within a transaction)
     *
     * @access private
     *
     * @param $risorse_ar array containing the ids of the records to remove
     *
     * @return a db query result object
     */
    private function delMedia($risorse_ar, $sqlnode_id)
    {
        ADALogger::logDb("entered del_media");
        $this->beginTransaction();
        $n = count($risorse_ar);

        ADALogger::logDb("got $n resources to remove");

        for ($i = 1; $i <= $n; $i++) {
            $res_id = $risorse_ar[$i - 1];
            $res = $this->delRisorseNodi($sqlnode_id, $res_id);
            if (AMADataHandler::isError($res)) {
                // does the rollback
                $err  = $res->getMessage() . AMA_SEP . $this->rollback();
                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                ADALogger::logDb("removing from risorse_nodi done ($sqlnode_id, $res_id), adding instruction to rbs");
                $this->rsAdd("add_risorse_nodi", $sqlnode_id, $res_id);
            }

            $res = $this->removeRisorsaEsterna($res_id);
            if (AMADataHandler::isError($res)) {
                // does the rollback
                $err  = $res->getMessage() . AMA_SEP . $this->rollback();
                ADALogger::logDb("$err detected, rollbacking");
                return new AMAError($err);
            } else {
                // add instruction to rollback segment
                $res_ha = $this->getRisorsaEsternaInfo($res_id);
                ADALogger::logDb("removing from risorsa_esterna done ($res_id), adding instruction to rbs");
                $this->rsAdd("add_risorsa_esterna", $res_ha);
            }
        }

        ADALogger::logDb("committing the removal of resources");
        $this->commit();
        return true;
    }

    /**
     * Methods accessing table `sessione_eguidance`
     */
    // MARK: Methods accessing table `sessione_eguidance`
    public function addEguidanceSessionData($eguidance_dataAr = [])
    {
        $sql = 'INSERT INTO sessione_eguidance(id_utente,id_tutor,id_istanza_corso,event_token,data_ora,tipo_eguidance,ud_1,ud_2,ud_3,ud_comments,'
            . 'pc_1,pc_2,pc_3,pc_4,pc_5,pc_6,pc_comments,ba_1,ba_2,ba_3,ba_4,ba_comments,'
            . 't_1,t_2,t_3,t_4,t_comments,pe_1,pe_2,pe_3,pe_comments,ci_1,ci_2,ci_3,ci_4, ci_comments,'
            . 'm_1,m_2,m_comments,other_comments) '
            . 'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $dataAr = [
            $eguidance_dataAr['id_utente'],
            $eguidance_dataAr['id_tutor'],
            $eguidance_dataAr['id_istanza_corso'],
            $eguidance_dataAr['event_token'],
            time(),
            $eguidance_dataAr['type_of_guidance'],
            $eguidance_dataAr['ud_1'],
            $eguidance_dataAr['ud_2'],
            $eguidance_dataAr['ud_3'],
            $eguidance_dataAr['ud_comments'],
            $eguidance_dataAr['pc_1'],
            $eguidance_dataAr['pc_2'],
            $eguidance_dataAr['pc_3'],
            $eguidance_dataAr['pc_4'],
            $eguidance_dataAr['pc_5'],
            $eguidance_dataAr['pc_6'],
            $eguidance_dataAr['pc_comments'],
            $eguidance_dataAr['ba_1'],
            $eguidance_dataAr['ba_2'],
            $eguidance_dataAr['ba_3'],
            $eguidance_dataAr['ba_4'],
            $eguidance_dataAr['ba_comments'],
            $eguidance_dataAr['t_1'],
            $eguidance_dataAr['t_2'],
            $eguidance_dataAr['t_3'],
            $eguidance_dataAr['t_4'],
            $eguidance_dataAr['t_comments'],
            $eguidance_dataAr['pe_1'],
            $eguidance_dataAr['pe_2'],
            $eguidance_dataAr['pe_3'],
            $eguidance_dataAr['pe_comments'],
            $eguidance_dataAr['ci_1'],
            $eguidance_dataAr['ci_2'],
            $eguidance_dataAr['ci_3'],
            $eguidance_dataAr['ci_4'],
            $eguidance_dataAr['ci_comments'],
            $eguidance_dataAr['m_1'],
            $eguidance_dataAr['m_2'],
            $eguidance_dataAr['m_comments'],
            $eguidance_dataAr['other_comments'],
        ];

        $result = $this->queryPrepared($sql, $dataAr);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
        }

        return true;
    }

    public function updateEguidanceSessionData($eguidance_dataAr = [])
    {
        $sql = 'UPDATE sessione_eguidance '
            . 'SET tipo_eguidance = ?,ud_1 = ?,ud_2 = ?,ud_3 = ?,ud_comments = ?,'
            . 'pc_1 = ?,pc_2 = ?,pc_3 = ?,pc_4 = ?,pc_5 = ?,pc_6 = ?,pc_comments = ?,ba_1 = ?,ba_2 = ?,ba_3 = ?,ba_4 = ?,ba_comments = ?,'
            . 't_1 = ?,t_2 = ?,t_3 = ?,t_4 = ?,t_comments = ?,pe_1 = ?,pe_2 = ?,pe_3 = ?,pe_comments = ?,ci_1 = ?,ci_2 = ?,ci_3 = ?,ci_4 = ?, ci_comments = ?,'
            . 'm_1 = ?,m_2 = ?,m_comments = ?,other_comments = ? '
            . 'WHERE id = ?';

        $dataAr = [
            $eguidance_dataAr['type_of_guidance'],
            $eguidance_dataAr['ud_1'],
            $eguidance_dataAr['ud_2'],
            $eguidance_dataAr['ud_3'],
            $eguidance_dataAr['ud_comments'],
            $eguidance_dataAr['pc_1'],
            $eguidance_dataAr['pc_2'],
            $eguidance_dataAr['pc_3'],
            $eguidance_dataAr['pc_4'],
            $eguidance_dataAr['pc_5'],
            $eguidance_dataAr['pc_6'],
            $eguidance_dataAr['pc_comments'],
            $eguidance_dataAr['ba_1'],
            $eguidance_dataAr['ba_2'],
            $eguidance_dataAr['ba_3'],
            $eguidance_dataAr['ba_4'],
            $eguidance_dataAr['ba_comments'],
            $eguidance_dataAr['t_1'],
            $eguidance_dataAr['t_2'],
            $eguidance_dataAr['t_3'],
            $eguidance_dataAr['t_4'],
            $eguidance_dataAr['t_comments'],
            $eguidance_dataAr['pe_1'],
            $eguidance_dataAr['pe_2'],
            $eguidance_dataAr['pe_3'],
            $eguidance_dataAr['pe_comments'],
            $eguidance_dataAr['ci_1'],
            $eguidance_dataAr['ci_2'],
            $eguidance_dataAr['ci_3'],
            $eguidance_dataAr['ci_4'],
            $eguidance_dataAr['ci_comments'],
            $eguidance_dataAr['m_1'],
            $eguidance_dataAr['m_2'],
            $eguidance_dataAr['m_comments'],
            $eguidance_dataAr['other_comments'],
            $eguidance_dataAr['id_eguidance_session'],
        ];

        $result = $this->queryPrepared($sql, $dataAr);
        if (self::isError($result)) {
            return new AMAError(AMA_ERR_ADD);
        }

        return true;
    }

    public function getEguidanceSessionWithEventToken($event_token)
    {
        $sql = 'SELECT id, id_utente,id_tutor,data_ora,tipo_eguidance,ud_1,ud_2,ud_3,ud_comments,'
            . 'pc_1,pc_2,pc_3,pc_4,pc_5,pc_6,pc_comments,ba_1,ba_2,ba_3,ba_4,ba_comments,'
            . 't_1,t_2,t_3,t_4,t_comments,pe_1,pe_2,pe_3,pe_comments,ci_1,ci_2,ci_3,ci_4, ci_comments,'
            . 'm_1,m_2,m_comments,other_comments '
            . "FROM sessione_eguidance WHERE event_token = ?";

        $result = $this->getRowPrepared($sql, [$event_token], AMA_FETCH_ASSOC);
        if (AMADB::isError($result) || !is_array($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getLastEguidanceSession($id_course_instance)
    {
        $limit_clause = 'LIMIT 1';
        return $this->getEguidanceSessions($id_course_instance, $limit_clause);
    }

    public function getEguidanceSessions($id_course_instance, $limit_clause = '')
    {
        $sql = 'SELECT id_utente,id_tutor,event_token,data_ora,tipo_eguidance,ud_1,ud_2,ud_3,ud_comments,'
            . 'pc_1,pc_2,pc_3,pc_4,pc_5,pc_6,pc_comments,ba_1,ba_2,ba_3,ba_4,ba_comments,'
            . 't_1,t_2,t_3,t_4,t_comments,pe_1,pe_2,pe_3,pe_comments,ci_1,ci_2,ci_3,ci_4, ci_comments,'
            . 'm_1,m_2,m_comments,other_comments '
            . 'FROM sessione_eguidance WHERE id_istanza_corso = ?'
            . ' ORDER BY id DESC';

        if ($limit_clause != '') {
            $sql .= ' ' . $limit_clause;
            $result = $this->getRowPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        } else {
            $result = $this->getAllPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        }

        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getEguidanceSessionDates($id_course_instance)
    {
        $sql = 'SELECT id, data_ora FROM sessione_eguidance WHERE id_istanza_corso = ?';

        $result = $this->getAllPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getEguidanceSession($id_course_instance, $row)
    {
        $sql = 'SELECT id_utente,id_tutor,data_ora,tipo_eguidance,ud_1,ud_2,ud_3,ud_comments,'
            . 'pc_1,pc_2,pc_3,pc_4,pc_5,pc_6,pc_comments,ba_1,ba_2,ba_3,ba_4,ba_comments,'
            . 't_1,t_2,t_3,t_4,t_comments,pe_1,pe_2,pe_3,pe_comments,ci_1,ci_2,ci_3,ci_4, ci_comments,'
            . 'm_1,m_2,m_comments,other_comments '
            . 'FROM sessione_eguidance WHERE id_istanza_corso = ?'
            . ' LIMIT ' . $row . ',1';
        $result = $this->getRowPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Methods accessing table `studente`
     */
    // MARK: Methods accessing table `studente`

    /**
     * Add a student to the DB
     *
     * @access public
     *
     * @param $student_ar an array containing all the student's data
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *
     */
    public function addStudent($student_ar)
    {
        /**
         * Add user data in table utenti
         */
        $result = $this->addUser($student_ar);
        if (self::isError($result)) {
            // $result is an AMAError object
            return $result;
        }

        // insert a row into table studente
        $sql  = "insert into studente (id_utente_studente) values (?)";
        $res = $this->executeCriticalPrepared($sql, [$student_ar['id_utente']]);
        if (AMADB::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
        return $student_ar['id_utente']; // return the id of inserted student.
    }

    /**
     * Remove a student from the DB
     *
     * @access public
     *
     * @param $id the unique id of the student
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     * @note the referential integrity with iscrizioni is checked
     */
    public function removeStudent($id)
    {
        // referential integrity checks
        $ri_id = $this->getOnePrepared("select id_utente_studente from iscrizioni where id_utente_studente=?", [$id]);
        if ($ri_id) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }
        $ri_id = $this->getOnePrepared("select id_nodo from nodo where id_utente=?", [$id]);
        if ($ri_id) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }
        $ri_id = $this->getOnePrepared("select id_link from link where id_utente=?", [$id]);
        if ($ri_id) {
            return new AMAError(AMA_ERR_REF_INT_KEY);
        }

        $sql = "delete from studente where id_utente_studente=?";
        $res = $this->executeCriticalPrepared($sql, [$id]);
        if (AMADB::isError($res)) {
            // $res is an AMAError object
            return $res;
        }

        $sql = "delete from utente where id_utente=?";
        $res = $this->executeCriticalPrepared($sql, [$id]);
        if (AMADB::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
        return true;
    }

    /**
     * Get a list of students' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     */
    public function &getStudentsList($field_list_ar)
    {
        $more_fields = '';
        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }
        // do the query
        $students_ar =  $this->getAllPrepared("select id_utente$more_fields from utente, studente where  tipo=" . AMA_TYPE_STUDENT . " and id_utente=id_utente_studente");
        if (AMADB::isError($students_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $students_ar;
    }

    /**
     * Get those students ids verifying the given criterium
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono
     *
     * @param  clause the clause string which will be added to the select
     *
     * @return array a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &findStudentsList($field_list_ar, $clause = '', $order = 'cognome')
    {
        // build comma separated string out of $field_list_ar array
        if (is_array($field_list_ar) && count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }

        // handle null clause, too
        if ($clause) {
            $clause = ' where ' . $clause;
        }
        // do the query
        if ($clause == '') {
            $students_ar =  $this->getAllPrepared("select id_utente$more_fields from utente, studente where tipo=" . AMA_TYPE_STUDENT . " and id_utente=id_utente_studente order by $order");
        } else {
            $students_ar =  $this->getAllPrepared("select id_utente$more_fields from utente, studente $clause and tipo=" . AMA_TYPE_STUDENT . " and id_utente=id_utente_studente order by $order");
        }

        if (AMADB::isError($students_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $students_ar;
    }

    /**
     * Get a list of students' ids from the DB
     *
     * @access public
     *
     * @return an array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     *
     * @see get_students_list
     */
    public function &getStudentsIds()
    {
        return $this->getStudentsList([]);
    }

    public function getUser($id)
    {
        return $this->getStudent($id);
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
        if (self::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            return $get_user_result;
        }
        // get_student($id) originally did not return the user id as a result,
        unset($get_user_result['id']);

        return $get_user_result;
    }

    /**
     * Updates informations related to a student
     *
     * @access public
     *
     * @param $id the student's id
     *        $admin_ar the informations. empty fields are not updated
     *
     * @return an error if something goes wrong, true on success
     *
     */
    public function setStudent($id, $student_ha)
    {
        return $this->setUser($id, $student_ha);
    }

    /**
     * Methods accessing table `template`
     */
    // MARK: Methods accessing table `template`

    /**
     * Get the template used by a given type of node and by a given type of user.
     * Looks into the "template" table and returns the text of the template.
     *
     *
     * @access public
     *
     * @param $node_type the type of the node (see add_node)
     *
     * @param $user_type the type of user (see add_user)
     *
     * @return the text of the template, if found
     *
     * @see add_node, add_user
     *
     */
    public function getTemplate($node_type, $user_type)
    {
        // get a row from table template
        $sql  = "select testo from template where tipo_pagina=? and profilo_utente=?";
        // FIXME:chiamare getOne al posto di getRow
        $res_ar =  $this->getRowPrepared($sql, [$node_type, $user_type]);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // returns the text
        return $res_ar[0];
    }

    /**
     * Methods accessing table `tutor`
     */
    // MARK: Methods accessing table `tutor`

    /**
     * Add a tutor to the DB
     *
     * @access public
     *
     * @param $tutor_ar an array containing all the tutor's data
     *
     * @return an AMAError object or a DB_Error object if something goes wrong
     *
     */
    public function addTutor($tutor_ha)
    {
        /*
         * Add user data in table utenti
         */
        $result = $this->addUser($tutor_ha);
        if (self::isError($result)) {
            // $result is an AMAError object
            return $result;
        }
        $add_tutor_sql = 'INSERT INTO tutor(id_utente_tutor, tariffa, profilo) VALUES(?,?,?)';

        $add_tutor_values = [
            $tutor_ha['id_utente'],
            $this->orZero($tutor_ha['tariffa']),
            $this->orNull($tutor_ha['profilo']),
        ];

        $result = $this->executeCriticalPrepared($add_tutor_sql, $add_tutor_values);
        if (AMADB::isError($result)) {
            // try manual rollback in case problems arise
            $delete_user_sql = 'DELETE FROM utente WHERE username=?';
            $delete_result   = $this->executeCriticalPrepared($delete_user_sql, [$tutor_ha['username']]);
            if (AMADB::isError($delete_result)) {
                return $delete_result;
            }
            /*
       * user data has been successfully removed from table utente, return only
       * the error obtained when adding user data to table autore.
            */
            return $result;
        }

        // return the tutor id
        return $tutor_ha['id_utente'];
    }

    /**
     * Remove a tutor from the DB
     *
     * @access public
     *
     * @param $id the unique id of the tutor
     *
     * @return an AMAError object if something goes wrong, true on success
     *
     */
    public function removeTutor($id)
    {
        $sql = "delete from tutor where id_utente_tutor=?";
        ADALogger::logDb($sql);
        $res = $this->executeCriticalPrepared($sql, [$id]);
        if (AMADB::isError($res)) {
            // $res is ana AMAError object
            return $res;
        }

        $sql = "delete from utente where id_utente=?";
        $res = $this->executeCritical($sql, [$id]);
        if (AMADB::isError($res)) {
            // $res is ana AMAError object
            return $res;
        }
        return true;
    }

    /**
     * Get a list of tutor' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_tutors_list
     */
    public function &getTutorsList($field_list_ar)
    {
        return $this->findTutorsList($field_list_ar, '', false);
    }

    /**
     * Get a list of super tutor' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_tutors_list
     */
    public function &getSupertutorsList($field_list_ar)
    {
        return $this->findTutorsList($field_list_ar, '', true);
    }

    /**
     * Get a list of tutors' ids from the DB
     *
     * @access public
     *
     * @return an array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     *
     * @see find_authors_list, get_authors_list
     */
    public function &getTutorsIds()
    {
        return $this->getTutorsList([]);
    }

    /**
     * Get those tutors' ids verifying the given criterium on the tarif fiels
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @param  clause the clause string which will be added to the select
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &findTutorsList($field_list_ar, $clause = '', $supertutors = false)
    {
        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }
        // handle null clause, too
        if ($clause) {
            $clause = ' AND ' . $clause;
        }

        // do the query
        $sql_query = "select id_utente$more_fields from utente, tutor where  tipo=" .
            ($supertutors ? AMA_TYPE_SUPERTUTOR : AMA_TYPE_TUTOR) . " and id_utente=id_utente_tutor$clause";
        $tutors_ar =  $this->getAllPrepared($sql_query, [], AMA_FETCH_DEFAULT);
        if (AMADB::isError($tutors_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $tutors_ar;
    }

    /**
     * Return tutor assigned course instance
     *
     * @access public
     *
     * @param $id_tutor pass a single/array tutor id or use "false" to retrieve all tutors
     * @param $id_course if passed as int, select only instances of the passed course id
     * @param $isSuper true if the tutor is a supertutor
     *
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array('tutor id'=>array('course_instance', 'course_instance', 'course_instance'));
     */
    public function &getTutorsAssignedCourseInstance($id_tutor = false, $id_course = false, $isSuper = false)
    {
        // do the query
        $sql = "SELECT " .
            (($isSuper) ? $id_tutor . " AS `id_utente_tutor`" : "ts.`id_utente_tutor`") . "," .
            "c.`id_corso`, c.`titolo`, c.`id_utente_autore`,
					i.`id_istanza_corso`, i.`title`,i.`data_inizio_previsto`,i.`data_fine`,i.`duration_hours`,
                                        i.`durata`,i.`self_instruction`,i.`data_inizio`
				FROM " .
            (($isSuper) ? "" : "`tutor_studenti` ts JOIN ") .
            "`istanza_corso` i " .
            (($isSuper) ? "" : "ON (i.`id_istanza_corso`=ts.`id_istanza_corso`)") .
            " JOIN `modello_corso` c ON (c.`id_corso`=i.`id_corso`)";

        if (!$isSuper) {
            if (is_array($id_tutor) and !empty($id_tutor)) {
                $sql .= " WHERE id_utente_tutor IN (" . implode(',', $id_tutor) . ")";
            } elseif ($id_tutor) {
                $sql .= " WHERE id_utente_tutor = " . $id_tutor;
            }
        }

        if (is_numeric($id_course) && intval($id_course) > 0) {
            if (stristr($sql, 'where') !== false) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }

            $sql .= 'c.`id_corso`=' . intval($id_course);
        }

        $tutors_ar =  $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);

        if (AMADB::isError($tutors_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        } else {
            $tutors = [];
            foreach ($tutors_ar as $k => $v) {
                $id = $v['id_utente_tutor'];
                unset($v['id_utente_tutor']);
                $tutors[$id][] = $v;
            }
            unset($tutors_ar);

            return $tutors;
        }
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

        // get a row from table TUTOR
        $get_tutor_sql = "select tariffa, profilo from tutor where id_utente_tutor=?";
        $get_tutor_result = $this->getRowPrepared($get_tutor_sql, [$id], AMA_FETCH_ASSOC);
        if (AMADB::isError($get_tutor_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!is_array($get_tutor_result)) {
            /* inconsistency found! a message should be logged */
            return new AMAError(AMA_ERR_INCONSISTENT_DATA);
        }
        return array_merge($get_user_result, $get_tutor_result);
    }

    /**
     * Updates informations related to a tutor
     *
     * @access public
     *
     * @param $id the tutor's id
     *        $tutor_ar the informations. empty fields are not updated
     *
     * @return an error if something goes wrong
     *
     */
    public function setTutor($id, $tutor_ha)
    {
        // backup old values
        $old_values_ha = $this->getTutor($id);

        $result = $this->setUser($id, $tutor_ha);
        if (self::isError($result)) {
            // $result is an AMAError object
            return $result;
        }

        $update_tutor_sql = 'UPDATE tutor SET tariffa=?, profilo=? WHERE id_utente_tutor=?';
        $valuesAr = [
            $this->orZero($tutor_ha['tariffa']),
            $tutor_ha['profilo'],
            $id,
        ];
        $result = $this->queryPrepared($update_tutor_sql, $valuesAr);
        if (AMADB::isError($result)) {
            $valuesAr = [
                $old_values_ha['nome'],
                $old_values_ha['cognome'],
                $old_values_ha['email'],
                $old_values_ha['telefono'],
                $old_values_ha['password'],
                $old_values_ha['layout'],
                $old_values_ha['indirizzo'],
                $old_values_ha['citta'],
                $old_values_ha['provincia'],
                $old_values_ha['nazione'],
                $old_values_ha['codice_fiscale'],
                AMACommonDataHandler::dateToTs($old_values_ha['birthdate']),
                $old_values_ha['sesso'],
                $old_values_ha['stato'],
                $old_values_ha['lingua'],
                $old_values_ha['timezone'],
                $old_values_ha['cap'],
                $old_values_ha['matricola'],
                $old_values_ha['avatar'],
                $old_values_ha['birthcity'],
                $old_values_ha['birthprovince'],
                $id,
            ];
            $update_user_sql = 'UPDATE utente SET nome=?, cognome=?, e_mail=?, telefono=?, password=?, layout=?, '
                . 'indirizzo=?, citta=?, provincia=?, nazione=?, codice_fiscale=?, birthdate=?, sesso=?, '
                . 'stato=?, lingua=?,timezone=?,cap=?,matricola=?,avatar=?, birthcity=?, birthprovince=? WHERE id_utente=?';

            $result = $this->queryPrepared($update_user_sql, $valuesAr);
            // qui andrebbe differenziato il tipo di errore
            if (AMADB::isError($result)) {
                return new AMAError(AMA_ERR_UPDATE);
            }

            return new AMAError(AMA_ERR_UPDATE);
        }

        return true;
    }

    /**
     * Methods accessing table `tutor_studenti`
     */
    // MARK: Methods accessing table `tutor_studenti`

    /**
     * assign a tutor to the course_instance
     *
     * @access public
     *
     * @param $id_tutor    - tutor id
     * @param $id_corso    - course instance id
     *
     * @return an AMAError object if something goes wrong, true on success
     */
    public function courseInstanceTutorSubscribe($id_course_instance, $id_tutor)
    {
        // verify key uniqueness (index)
        $sql = "select id_istanza_corso from tutor_studenti where id_istanza_corso=? and id_utente_tutor=?";
        $id =  $this->getOnePrepared($sql, [$id_course_instance, $id_tutor]);
        if (AMADB::isError($id)) {
            return new AMAError(AMA_ERR_GET);
        }
        if ($id) {
            return new AMAError($this->errorMessage(AMA_ERR_UNIQUE_KEY) .
                " in course_instance_tutor_subscribe ");
        }

        // insert a row into table iscrizioni
        $sql =  "insert into tutor_studenti (id_utente_tutor, id_istanza_corso) values (?, ?);";
        $res = $this->executeCriticalPrepared($sql, [$id_tutor, $id_course_instance]);
        if (AMADB::isError($res)) {
            // $res is ana AMAError object
            return $res;
        }

        return true;
    }

    /**
     * De-assign a tutor from the course.
     *
     * @param int $id_course_instance  the unique id of the course instance.
     * @param int $id_tutor            the id of the tutor.
     * @return boolean|AMAError an AMAError object if something goes wrong, true on success.
     *
     */
    public function courseInstanceTutorUnsubscribe($id_course_instance, $id_tutor)
    {
        return $this->courseInstanceTutorsUnsubscribe($id_course_instance, $id_tutor);
    }

    /**
     * De-assign all the tutors from the course instance
     *
     * @param int $id_course_instance the unique id of the course instance.
     * @param int|null $id_tutor ID of the tutor to de-assing, or null for all tutors.
     * @return boolean|AMAError true on success, an AMAError object on faulure.
     */
    public function courseInstanceTutorsUnsubscribe($id_course_instance, $id_tutor = null)
    {
        $params = [
            $id_course_instance,
        ];

        $sql = "delete from tutor_studenti where id_istanza_corso=?";
        if (!empty($id_tutor) && is_numeric($id_tutor)) {
            $sql .= " and id_utente_tutor=?";
            $params[] = $id_tutor;
        }
        $result = $this->queryPrepared($sql, $params);
        if (AMADB::isError($result)) {
            // $result is an AMAError object
            return $result;
        }
        return true;
    }

    /**
     * get the tutor(s) of the course_instance
     *
     * @access public
     *
     * @param $id_tutor    - tutor id
     * @param $id_instance    - course instance id
     * @param $number    - mode: a single tutor  or array
     *
     * @return array|AMAError an error if something goes wrong, an array if $number >=1, an integer else
     */
    public function courseInstanceTutorGet($id_instance, $number = 1)
    {
        // select row(s) into table tutor_studenti
        $sql =  "select id_utente_tutor from tutor_studenti where id_istanza_corso=?";
        if ($number == 1) {
            $res =  $this->getRowPrepared($sql, [$id_instance]);
        } else {
            $res =  $this->getAllPrepared($sql, [$id_instance]);
        }
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_GET);
        }

        if ((!empty($res)) && (!AMADataHandler::isError($res))) {
            if ($number == 1) {
                $id_utente_tutor = $res[0];
                return $id_utente_tutor;
            } else {
                $tutorAr = [];
                foreach ($res as $tutor) {
                    $id_utente_tutor = $tutor[0];
                    $tutorAr[] = $id_utente_tutor;
                }
                return $tutorAr;
            }
        }

        // no tutor found
        return false;
    }

    /**
     * get the tutor(s) complete informations of the course_instance
     *
     * @access public
     *
     * @param $id_tutor    - tutor id
     * @param $id_instance    - course instance id
     * @param $number    - mode: a single tutor  or array
     *
     * @return array|AMAError an error if something goes wrong, an array if $number >=1, an integer else
     */
    public function courseInstanceTutorInfoGet($id_instance, $number = 0)
    {
        $sql =  "select TS.id_utente_tutor, U.nome, U.cognome, U.username, U.e_mail from tutor_studenti AS TS, utente AS U where id_istanza_corso=? AND TS.id_utente_tutor=U.id_utente";
        if ($number == 1) {
            $res =  $this->getRowPrepared($sql, [$id_instance]);
        } else {
            $res =  $this->getAllPrepared($sql, [$id_instance], AMA_FETCH_ASSOC);
        }
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $res;
        /*
        if ((!empty($res)) && (!AMADataHandler::isError($res))){
        if ($number==1){
          $id_utente_tutor = $res[0];
          return $id_utente_tutor;
        }
        else {
          $tutorAr = array();
          foreach ($res as $tutor) {
            $id_utente_tutor = $tutor[0];
            $tutorAr[] = $id_utente_tutor;
          }
          return $tutorAr;
        }
        }
         *
         */

        // no tutor found
        return false;
    }

    /**
     * get the course_instance of the tutor
     *
     * @access public
     *
     * @param $id_tutor    - tutor id
     * @param $isSuper     - true if tutor is a supertutor
     *
     * @return
     */
    public function courseTutorInstanceGet($id_tutor, $isSuper = false)
    {
        // select row into table tuto_studenti
        if (!$isSuper) {
            $sql =  "select id_istanza_corso,id_utente_tutor from tutor_studenti where id_utente_tutor=?";
        } else {
            $sql =  "select id_istanza_corso, ? AS id_utente_tutor FROM istanza_corso";
        }
        $res =  $this->getAllPrepared($sql, [$id_tutor]);
        if (AMADB::isError($res)) {
            return new AMAError(AMA_ERR_GET);
        }

        if (!empty($res)) {
            return $res;
        }
        // no instance found
        return false;
    }

    public function countActiveCourseInstances($timestamp)
    {
        // select row into table tuto_studenti
        $sql =  "SELECT COUNT(id_istanza_corso) FROM istanza_corso WHERE data_inizio < :timestamp AND data_fine > :timestamp";
        $result =  $this->getOnePrepared($sql, ['timestamp' => $timestamp]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Get notes' count for a given given user and course instance in the selected provider
     *
     * @access public
     *
     * @param courseInstanceId
     *
     * @param userId
     *
     *
     * @return int of notes on success, an AMAError object on failure
     */
    public function countNewNotesInCourseInstances($courseInstanceId, $userId)
    {
        // select row into table nodo
        $sql =  "SELECT COUNT(n.`id_nodo`)
    	FROM `nodo` n
    	JOIN
    	(
    	SELECT `data_visita`
    	FROM `history_nodi`
    	WHERE `id_utente_studente` = ?
    	ORDER BY `data_visita` DESC
    	LIMIT 1
    	) h ON (h.`data_visita` < n.`data_creazione`)";

        $sql .= " WHERE n.`tipo` = ? AND n.`id_istanza` = ?";

        $result =  $this->getOnePrepared($sql, [$userId, ADA_NOTE_TYPE, $courseInstanceId]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * gets the list of all nodes that have been visited and are to be removed from the
     * user whatsnew array
     *
     * @param  $userObj user to perform the action for
     *
     */
    public function getUpdatesNodes($userObj, $pointer)
    {
        $todel = [];
        $node_id_string = '';
        $whatsnew = $userObj->getwhatsnew();

        // build the list of id_nodo stored in session var
        foreach ($whatsnew[$pointer] as $num => $item) {
            $node_id_string .= "'" . $item['id_nodo'] . "'";
            if ($num < count($whatsnew[$pointer]) - 1) {
                $node_id_string .= ',';
            }
        }
        /**
         * if there are no new nodes stored in session for this provider, break out of the loop
         * and skip to the next provider
         */
        if ($node_id_string !== '') {
            /**
             * let's execute the query that will return the list of the id_nodo to be
             * removed from the session whatsnew array
             */

            $sql = "SELECT DISTINCT(B.id_nodo)
                    FROM nodo B LEFT JOIN history_nodi A ON A.id_nodo = B.id_nodo
                    WHERE B.id_nodo IN(" . $node_id_string . ")
                    AND A.`id_utente_studente`=? AND NOT(data_creazione>=data_visita OR ISNULL(data_visita))";
            $todel = $this->getAllPrepared($sql, [$userObj->getId()]);
        }
        return $todel;
    }

    /**
     * Get nodes' count for a given given user the selected provider
     *
     * @access public
     *
     * @param userId user id to get new nodes for
     * @param maxNodes : maximum number of nodes to get, gets all nodes if zero or not passed
     *
     * @author giorgio 29/apr/2013
     *
     * @return assoc array containing id_nodo, id_istazna and nome of the matched new nodes
     *
     * on success, an AMAError object on failure
     */
    public function getNewNodes($userId, $maxNodes = 3)
    {
        $nodeTypesArray =  [ADA_LEAF_TYPE, ADA_GROUP_TYPE];

        $instancesArray = $this->getCourseInstancesActiveForThisStudent($userId);
        $result = [];

        if (!AMADB::isError($instancesArray) && is_array($instancesArray) && count($instancesArray) > 0) {
            foreach ($instancesArray as $instance) {
                // check if course instance has been visited
                $temp = $this->getLastVisitedNodes($userId, $instance['id_istanza_corso'], 1);
                $hasbeenvisited = !empty($temp);

                if ($hasbeenvisited) {
                    $last_time_visited_class = $temp[0]['data_uscita'];
                    // get student level
                    $studentlevel = $this->getStudentLevel($userId, $instance['id_istanza_corso']);
                    /**
                     * new nodes are:
                     * 1. nodes the user has never visited
                     * 2. ndoes with data creazione > of the maximum data_visita for that node
                     *
                     *     so:
                     */

                    $sql = 'SELECT id_nodo, ID_ISTANZA, nome from nodo where data_creazione >= ?' .
                        ' AND id_nodo LIKE ? AND livello <=?' .
                        ' AND tipo IN (' . implode(", ", $nodeTypesArray) . ') ORDER BY data_creazione DESC LIMIT ' . $maxNodes;

                    $tmpresults = $this->getAllPrepared($sql, [$last_time_visited_class, $instance['id_corso'] . '\_%', $studentlevel], AMA_FETCH_ASSOC);

                    if (!empty($tmpresults)) {
                        foreach ($tmpresults as $tempresult) {
                            array_push($result, $tempresult);
                        }
                    }


                    // return ($db->getAll($sql, null, AMA_FETCH_ASSOC ));

                    // 1. get nodes user has never visited

                    //                  $sql = "SELECT DISTINCT(B.`id_nodo`) AS `id_nodo` , B.`id_istanza`, B.`nome`
                    //                          FROM `nodo` B LEFT JOIN `history_nodi` A ON A.`id_nodo` = B.`id_nodo`
                    //                          WHERE B.`tipo` IN (". implode (", ", $nodeTypesArray) .")
                    //                          AND ISNULL (`data_visita`)";
                    //                  // $sql .= " AND (A.`id_utente_studente` =".$userId ." OR ISNULL(A.`id_utente_studente`))";
                    //                  $sql .= " AND B.`id_nodo` LIKE '".$instance[id_corso]."_%'";
                    //                  $sql .= " AND B.`livello`<=" . $studentlevel;
                    //                  $sql .= " ORDER BY `data_creazione` DESC";

                    //                  $nevervisitednodes = $db->getAll($sql, null, AMA_FETCH_ASSOC );

                    //                  print_r ($nevervisitednodes);

                    //                  $sql= "SELECT HN.id_nodo AS 'id_nodo', HN.data_visita AS 'max_data_visita'
                    //                      FROM history_nodi HN
                    //                      INNER JOIN (
                    //                          SELECT id_nodo, MAX( data_visita ) AS maxdatetime
                    //                          FROM history_nodi
                    //                          GROUP BY id_nodo
                    //                          )   GROUPEDHN ON HN.id_nodo = GROUPEDHN.id_nodo
                    //                      AND HN.data_visita = GROUPEDHN.maxdatetime
                    //                      AND HN.`id_nodo` LIKE '".$instance[id_corso]."_%'
                    //                      AND HN.id_utente_studente =".$userId."
                    //                      ORDER BY id_nodo ASC";

                    //                  $sql  = "SELECT `id_nodo`, `data_visita` AS `max_data_visita` FROM `history_nodi`";
                    //                  $sql .= " WHERE `id_utente_studente`=".$userId;
                    //                  $sql .= " AND `id_nodo` LIKE '".$instance[id_corso]."_%'";
                    //                  $sql .= " GROUP BY `id_nodo` HAVING MAX(`data_visita`) ";
                    //                  $sql .= " ORDER BY max_data_visita DESC";

                    //                  $maximumdatas = $db->getAll($sql, null, AMA_FETCH_ASSOC );

                    //                  print_r($nevervisitednodes);

                    //                  $othernewnodes = array();
                    //                  foreach ($maximumdatas as $maxdatafornode)
                    //                  {
                    //                      $nodeId = $maxdatafornode['id_nodo'];
                    //                      $maxData = $maxdatafornode['max_data_visita'];

                    // //                       print_r ("$nodeId - $maxData\r\n<br>");

                    //                      // execute the query to get new nodes
                    //                      $sql ="SELECT DISTINCT(B.`id_nodo`) AS `id_nodo` , B.`id_istanza`, B.`nome`
                    //                      FROM `nodo` B LEFT JOIN `history_nodi` A ON A.`id_nodo` = B.`id_nodo`
                    //                      WHERE B.`tipo` IN (". implode (", ", $nodeTypesArray) .")";
                    //                      $sql .= " AND `data_creazione`>" . $maxData  ;
                    //                      $sql .= " AND A.`id_utente_studente` =".$userId;
                    //                      $sql .= " AND B.`id_nodo` = '".$nodeId."'";
                    //                      $sql .= " AND B.`livello`<=" . $studentlevel;

                    // //                       print_r("<hr/>".$sql."<hr/>");


                    //                      $tempresults = $db->getAll($sql, null, AMA_FETCH_ASSOC );
                    //                      if (!empty($tempresults)) foreach ($tempresults as $tempresult)  array_push ($othernewnodes,$tempresult);

                    //                          print_r ($othernewnodes);
                    //                  }
                    //                  die();

                    //                  $retarray = array_merge ($nevervisitednodes, $othernewnodes);
                } // if hasbeenvisited
            } // foreach instancesarray
        }

        return $result;


        //          // get id_corso of courses for which user has subscribed
        //          $sql = "SELECT DISTINCT(B.`id_corso`)
        //                  FROM `iscrizioni` A, `istanza_corso` B
        //                  WHERE A.`id_utente_studente` =$userId
        //                  AND A.`id_istanza_corso` = B.`id_istanza_corso`";

        //          $subscribedId = $db->getAll ($sql);

        //          $regexp = '';
        //          foreach ($subscribedId as $num=>$a)
        //          {
        //              foreach ( $a as $val )
        //              {
        //                  $regexp .= $val."_";
        //              }
        //              if ($num < count($subscribedId)-1) $regexp .= "|";
        //          }

        //          // regexp now contains a regular expression with all course_id user is subscriped to..
        //          // e.g 102_|107_ this will be used to compare agains id_nodo in nodo table.

        //          // get course id, if none found return 0
        // //           if ($courseId = $this->get_course_id_for_course_instance($courseInstanceId))
        // //           {
        //              // select row into table nodo
        //              $sql =  "SELECT DISTINCT(n.`id_nodo`) AS `id_nodo` , n.`id_istanza`, n.`nome`
        //              FROM `nodo` n
        //              JOIN
        //              (
        //              SELECT `data_visita`
        //              FROM `history_nodi`
        //              WHERE `id_utente_studente` = $userId
        //              ORDER BY `data_visita` DESC
        //              LIMIT 1
        //              ) h ON (h.`data_visita` < n.`data_creazione`)";

        //              $sql .= " WHERE n.`tipo` IN (". implode(",", $nodeTypesArray) .")";

        //              if ($regexp !== '') $sql .= " AND n.`id_nodo` REGEXP '$regexp'";

        //              $sql .= " ORDER BY n.`data_creazione` DESC";



        //              // new query
        // //               $sql ="SELECT DISTINCT(B.`id_nodo`) AS `id_nodo` , B.`id_istanza`, B.`nome`
        // //               FROM `nodo` B LEFT JOIN `history_nodi` A ON A.`id_nodo` = B.`id_nodo`
        // //               WHERE B.`tipo` IN (". implode (", ", $nodeTypesArray) .")
        // //               AND (`data_creazione`>=`data_visita` OR ISNULL(`data_visita`))";

        // //               if ($regexp!== '') $sql .= " AND B.`id_nodo` REGEXP '". $regexp ."'";

        // //               $sql .= " ORDER BY `data_creazione` DESC";


        //              // $sql .= " AND n.`id_nodo` LIKE '".$courseId."_%'";
        //              if ($maxNodes > 0) $sql .= " LIMIT ".$maxNodes;

        //              $result =  $db->getAll($sql, null, AMA_FETCH_ASSOC );

        //              if(AMADB::isError($result)) {
        //              return new AMAError(AMA_ERR_GET);
        //              }
        // //           }
        // //           else  $result=null;
        //          return $result;
    }

    public function getRegisteredStudentsWithoutTutor()
    {
        // select row into table tuto_studenti
        //$sql =  "SELECT COUNT(id_istanza_corso) FROM istanza_corso WHERE data_inizio < $timestamp AND data_fine > $timestamp";
        $sql = 'SELECT U.nome, U.cognome, U.tipo, U.username
                FROM utente AS U, iscrizioni AS I, istanza_corso AS IC
                WHERE U.tipo = ? AND U.stato = ?
                AND I.id_utente_studente = U.id_utente
                AND IC.id_istanza_corso = I.id_istanza_corso
                AND IC.data_inizio = ?';

        $result =  $this->getAllPrepared($sql, [AMA_TYPE_STUDENT, ADA_STATUS_REGISTERED, 0], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Methods accessing table `utente`
     */
    // MARK: Methods accessing table `utente`

    /**
     *
     * @param $user_dataAr
     * @return unknown_type
     */
    public function addUser($user_dataAr = [])
    {
        /*
     * Before inserting a row, check if a user with this username already exists
        */
        $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
        $user_id = $this->getOnePrepared($user_id_sql, [$user_dataAr['username']]);
        if (AMADB::isError($user_id)) {
            return $user_id;
        } elseif ($user_id) {
            return new AMAError(AMA_ERR_UNIQUE_KEY);
        }

        $add_user_sql = 'INSERT INTO utente(id_utente,nome,cognome,tipo,e_mail,username,password,layout,
                               indirizzo,citta,provincia,nazione,codice_fiscale,birthdate,sesso,
                               telefono,stato,lingua,timezone,cap,matricola,avatar,birthcity,birthprovince)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

        $values = [
            $user_dataAr['id_utente'],
            $user_dataAr['nome'],
            $user_dataAr['cognome'],
            $user_dataAr['tipo'],
            $user_dataAr['e_mail'],
            $user_dataAr['username'],
            //sha1($user_dataAr['password']),
            $user_dataAr['password'], // sha1 encoded
            $user_dataAr['layout'],
            $this->orNull($user_dataAr['indirizzo']),
            $this->orNull($user_dataAr['citta']),
            $this->orNull($user_dataAr['provincia']),
            $this->orNull($user_dataAr['nazione']),
            $this->orNull($user_dataAr['codice_fiscale']),
            $this->orZero($this->dateToTs($user_dataAr['birthdate'])),
            $this->orNull($user_dataAr['sesso']),
            $this->orNull($user_dataAr['telefono']),
            $user_dataAr['stato'],
            $user_dataAr['lingua'],
            $user_dataAr['timezone'],
            $user_dataAr['cap'],
            $user_dataAr['matricola'],
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

        //    /*
        //     * Return the user id of the inserted user
        //     */
        //    $user_id_sql = 'SELECT id_utente FROM utente WHERE username=?';
        //    $user_id = $this->getOnePrepared($user_id_sql, $user_dataAr['username']);
        //    if (AMADB::isError($user_id)) {
        //      return new AMAError(AMA_ERR_GET);
        //    }
        //
        //    return $user_id;
        return true;
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
    // vito 7/9/09
    //private function get_user_info($id) {
    public function getUserInfo($id)
    {
        // get a row from table UTENTE
        $query = "select nome, cognome, tipo, e_mail AS email, telefono, username, layout, " .
            "indirizzo, citta, provincia, nazione, codice_fiscale, birthdate, sesso, " .
            "telefono, stato, lingua, timezone, cap, matricola, avatar, birthcity, birthprovince  from utente where id_utente=?";
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
    public function getStudentLevel($id_user, $id_course_instance)
    {
        if (empty($id_course_instance)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // get a row from table iscrizioni
        // FIXME: usare getOne al posto di getRow
        $res_ar =  $this->getRowPrepared("select livello from iscrizioni where id_utente_studente=? and  id_istanza_corso=?", [$id_user, $id_course_instance]);
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
     * Methods accessing table `utente_chatroom`
     * @see ChatRoom.inc.php
     */
    // MARK: Methods accessing table `utente_chatroom`

    /**
     * Methods accessing table `utente_chatroom_log`
     * @see ChatRoom.inc.php
     */
    // MARK: Methods accessing table `utente_chatroom_log`

    /**
     * Methods accessing table `utente_log`
     * @see
     */
    // MARK: Methods accessing table `utente_log`

    /**
     * Methods accessing table `utente_messaggio_log`
     * @see
     */
    // MARK: Methods accessing table `utente_messaggio_log`


    /**
     * Methods accessing table `openmeetings_room`
     */
    // MARK: Methods accessing table `openmeetings_room`
    /**
     *
     * @param $videoroom_dataAr
     * @return bool|\PDOException|\Lynxlab\ADA\Main\AMA\AMAError|\Lynxlab\ADA\Main\ADAError
     */
    public function addVideoroom($videoroom_dataAr = [])
    {
        $add_room_sql = 'INSERT INTO openmeetings_room(id_room,id_istanza_corso,id_tutor,
    				           tipo_videochat, descrizione_videochat, tempo_avvio, tempo_fine)
                 VALUES(?,?,?,?,?,?,?)';

        $values = [
            $videoroom_dataAr['id_room'],
            $videoroom_dataAr['id_istanza_corso'],
            $videoroom_dataAr['id_tutor'],
            $videoroom_dataAr['tipo_videochat'],
            $videoroom_dataAr['descrizione_videochat'],
            $videoroom_dataAr['tempo_avvio'],
            $videoroom_dataAr['tempo_fine'],
        ];
        /*
     * Adds the room
        */
        $result = $this->executeCriticalPrepared($add_room_sql, $values);
        if (AMADB::isError($result)) {
            return $result;
        }
        return true;
    }


    /**
     * Get all informations about a videoroom
     *
     * @access public
     *
     * @param $id_istanza_corso the id instance course
     *
     * @return an array containing all the informations about a videoroom
     *        res_ar['id']
     *        res_ar['id_room']
     *        res_ar['id_istanza_corso']
     *        res_ar['id_tutor']
     *        res_ar['tipo_videochat']
     *        res_ar['descrizione_videochat']
     *        res_ar['tempo_avvio']
     *        res_ar['tempo_fine']
     */

    public function getVideoroomInfo($id_course_instance, $ora_attuale = null, $more_query = null)
    {
        $params = [
            'id_istanza_corso' => $id_course_instance,
        ];

        // get a row from table OPENMEETINGS_ROOM
        $query = "select id, id_room, id_istanza_corso, id_tutor, tipo_videochat, descrizione_videochat, tempo_avvio, tempo_fine
             from openmeetings_room where id_istanza_corso=:id_istanza_corso";
        if ($ora_attuale != null) {
            $where_more = " and tempo_avvio<=:ora_attuale and :ora_attuale<=tempo_fine";
            $query .= $where_more;
            $params['ora_attuale'] = $ora_attuale;
        }
        if ($more_query != null) {
            $query .= ' ' . $more_query;
        }
        $res_ar =  $this->getRowPrepared($query, $params, AMA_FETCH_ASSOC);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        if (empty($res_ar) or is_object($res_ar)) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }
        /*
    $res_ha['id']         = $id;
    $res_ha['nome']       = $res_ar[0];
    $res_ha['cognome']    = $res_ar[1];
    $res_ha['tipo']       = $res_ar[2];
    $res_ha['email']      = $res_ar[3];
    $res_ha['telefono']   = $res_ar[4];
    $res_ha['username']   = $res_ar[5];
    $res_ha['template_family']   = $res_ar[6];

    return $res_ha;
        */
        return $res_ar;
    }

    /**
     *
     * @param $id_romm
     * @return unknown_type
     */
    public function deleteVideoroom($id_room)
    {
        $sql = "DELETE FROM openmeetings_room WHERE id_room = ?";

        $res = $this->queryPrepared($sql, $id_room);
        if (AMADB::isError($res)) {
            // $res is ana AMAError object
            return $res;
        }
        return true;
    }

    public function logVideoroom($logData)
    {
        $sql = '';
        $values = [];
        $sid = session_id();
        $sid = strlen($sid) > 0 ? $sid : null;
        // always execute an EVENT_EXIT
        $sql = "UPDATE `log_videochat` SET `uscita`=? WHERE `id_room` = ? AND `id_istanza_corso`=? AND `uscita` IS NULL AND `id_user` = ?";
        $values = [
            $this->dateToTs('now'),
            $logData['id_room'],
            $logData['id_istanza_corso'],
            $logData['id_user'],
        ];
        if (!is_null($sid)) {
            $sql .= " AND `sessionID` = ? ";
            $values[] = $sid;
        } else {
            $sql .= ' ORDER BY `id_log` DESC LIMIT 1';
        }
        if ($logData['event'] == VideoRoom::EVENT_ENTER) {
            // run the prepared exit query
            $res = $this->queryPrepared($sql, $values);
            if (AMADB::isError($res)) {
                // $res is ana AMAError object
                return $res;
            }
            // prepare the enter query
            $sql = "INSERT INTO `log_videochat` (`id_user`, `is_tutor`, `id_room`, `id_istanza_corso`, `entrata`, `sessionID`) VALUES (?, ?, ?, ?, ?, ?)";
            $values = [
                $logData['id_user'],
                (int) $logData['is_tutor'],
                $logData['id_room'],
                $logData['id_istanza_corso'],
                $this->dateToTs('now'),
                $sid,
            ];
        }

        if (count($values) > 0 && strlen($sql) > 0) {
            $res = $this->queryPrepared($sql, $values);
            if (AMADB::isError($res)) {
                // $res is ana AMAError object
                return $res;
            }
        }
        return true;
    }

    public function getLogVideoroom($id_instance, $id_room = null, $id_user = null)
    {
        $retArr = [];
        $retArrIdx = 0;
        $roomCache = [];
        // 00. get min entrata and max uscita
        $sql = 'SELECT `id_room`, MIN(`entrata`) AS `entrata`, MAX(`uscita`) AS `uscita` FROM `log_videochat` WHERE `id_istanza_corso`=?';
        $values = [$id_instance];
        if (!is_null($id_room)) {
            $sql .= ' AND `log_videochat`.`id_room`=?';
            $values[] = $id_room;
        }
        $sql .= ' GROUP BY `log_videochat`.`id_room`';
        $showChatRooms = $this->getAllPrepared($sql, $values, AMA_FETCH_ASSOC);
        if (!AMADB::isError($showChatRooms) && is_array($showChatRooms) && count($showChatRooms) > 0) {
            foreach ($showChatRooms as $showChatRoom) {
                if (!array_key_exists($showChatRoom['id_room'], $roomCache)) {
                    $roomCache[$showChatRoom['id_room']] = $this->getVideoroomInfo($id_instance, null, ' AND `id_room`=' . $showChatRoom['id_room']);
                    // add tutor max uscita for the room
                    $sql = 'SELECT MAX(`uscita`) AS `uscita` FROM `log_videochat` WHERE `is_tutor` = 1 AND`id_istanza_corso`=? AND `id_room`=?';
                    $sql .= ' GROUP BY `log_videochat`.`id_room`';
                    $values = [$id_instance, (int) $showChatRoom['id_room']];
                    $tutorExit = $this->getOnePrepared($sql, $values);
                    $roomCache[$showChatRoom['id_room']]['lastTutorExit'] = $tutorExit;
                }
                if (!AMADB::isError($roomCache[$showChatRoom['id_room']])) {
                    // 01. load room details in the returned array
                    $retArr[$retArrIdx] = [
                        'details' => $roomCache[$showChatRoom['id_room']],
                        'users' => [],
                    ];
                    // user tutor enter and exit time as room start/end times
                    $retArr[$retArrIdx]['details']['inizio'] = $showChatRoom['entrata'];
                    $retArr[$retArrIdx]['details']['fine'] = $showChatRoom['uscita'];
                    // 02. load log events and push it to the users subarray
                    $sql = 'SELECT `log_videochat`.*,`utente`.`nome` AS `nome`, `utente`.`cognome` AS `cognome` ' .
                        'FROM `log_videochat` JOIN `utente` ON ' .
                        '`log_videochat`.`id_user`=`utente`.`id_utente` ' .
                        'WHERE `id_istanza_corso`=? AND `id_room`=? AND ((`entrata`>=? AND `uscita`<=?) OR (`entrata`>=? AND `uscita` IS NULL))';
                    $values = [
                        $id_instance, $showChatRoom['id_room'], $showChatRoom['entrata'], $showChatRoom['uscita'], $showChatRoom['entrata'],
                    ];
                    if (!is_null($id_user)) {
                        $sql .= ' AND `log_videochat`.`id_user`=?';
                        $values[] = $id_user;
                    }
                    if (!is_null($id_room)) {
                        $sql .= ' AND `log_videochat`.`id_room`=?';
                        $values[] = $id_room;
                    }
                    $logEvents = $this->getAllPrepared($sql, $values, AMA_FETCH_ASSOC);

                    if (!AMADB::isError($logEvents) && is_array($logEvents) && count($logEvents) > 0) {
                        foreach ($logEvents as $logEvent) {
                            if (!array_key_exists($logEvent['id_user'], $retArr[$retArrIdx]['users'])) {
                                $retArr[$retArrIdx]['users'][$logEvent['id_user']] = [
                                    'id' => $logEvent['id_user'],
                                    'nome' => $logEvent['nome'],
                                    'cognome' => $logEvent['cognome'],
                                    'isTutor' => (1 === intval($logEvent['is_tutor'])),
                                    'events' => [],
                                ];
                            }
                            array_push(
                                $retArr[$retArrIdx]['users'][$logEvent['id_user']]['events'],
                                [
                                    'entrata' => $logEvent['entrata'],
                                    'uscita' => $logEvent['uscita'],
                                ]
                            );
                        }
                        $retArr[$retArrIdx]['users'] = array_values($retArr[$retArrIdx]['users']);
                    }
                    $retArrIdx++;
                }
            }
        }
        return $retArr;
    }

    public function getTesterServicesNotStarted()
    {
        $sql = 'SELECT U1.id_utente, U1.nome, U1.cognome, MC.titolo, I.status,
                   IC.id_istanza_corso, IC.id_corso, IC.data_inizio_previsto AS data_richiesta
              FROM utente AS U1, modello_corso AS MC, iscrizioni AS I, istanza_corso AS IC
             WHERE IC.data_inizio = ? AND MC.id_corso = IC.id_corso
               AND I.id_istanza_corso = IC.id_istanza_corso
               AND U1.id_utente = I.id_utente_studente
               AND U1.stato=? ORDER BY IC.id_istanza_corso DESC';

        $resultAr = $this->getAllPrepared($sql, [0, ADA_STATUS_REGISTERED], AMA_FETCH_ASSOC);
        if (AMADB::isError($resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $resultAr;
    }

    public function getTesterServicesStarted()
    {
        $sql = 'SELECT U1.id_utente, U1.nome, U1.cognome, MC.titolo, I.status,
                   U2.id_utente AS id_tutor, U2.nome AS nome_t, U2.cognome AS cognome_t, U2.username AS username_t,
                   IC.id_istanza_corso, IC.id_corso, IC.data_inizio_previsto AS data_richiesta
              FROM utente AS U1, utente AS U2, modello_corso AS MC, iscrizioni AS I, istanza_corso AS IC, tutor_studenti AS TS
             WHERE IC.data_inizio > 0 AND MC.id_corso = IC.id_corso
               AND I.id_istanza_corso = IC.id_istanza_corso
               AND U1.id_utente = I.id_utente_studente
               AND TS.id_istanza_corso = IC.id_istanza_corso
               AND U2.id_utente = TS.id_utente_tutor
               ORDER BY IC.id_istanza_corso DESC';

        $resultAr = $this->getAllPrepared($sql, null, AMA_FETCH_ASSOC);
        if (AMADB::isError($resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $resultAr;
    }

    /*
     * Get type of all services
     *
     * @access public
     *
     * @ return an array: id_type_service, type_service(=service_level), name_service,description, custom fields
     *
     * @return an error if something goes wrong
     *
     */
    public function getServiceType($id_user = null)
    {
        $service_sql = "SELECT id_tipo_servizio, livello_servizio,nome_servizio,descrizione_servizio,custom_1,custom_2,custom_3,hiddenFromInfo,isPublic  FROM service_type";
        $common_dh = AMACommonDataHandler::getInstance();

        /* if isset $id_user it means that the admin is asking data for log_report.php, and he have to take data from common db */
        if (isset($id_user)) {
            $db = [$common_dh];
        } else {
            $db = [$this, $common_dh];
        }

        foreach ($db as $dbToUse) {
            $service_result = $dbToUse->getAllPrepared($service_sql, null, AMA_FETCH_ASSOC);
            if (!AMADB::isError($service_result) && $service_result !== false && count($service_result) > 0) {
                break;
            }
        }

        if (AMADB::isError($service_result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $service_result;
    }

    public function getNumberOfTutoredUsers($id_tutor)
    {
        $sql = 'SELECT count(id_istanza_corso) FROM tutor_studenti WHERE id_utente_tutor=?';

        $result = $this->getOnePrepared($sql, [$id_tutor]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    public function getTutoredUserIds($id_tutor)
    {
        $sql = "SELECT distinct(I.id_utente_studente)
              FROM tutor_studenti AS TS, iscrizioni AS I
             WHERE id_utente_tutor=?
               AND I.id_istanza_corso=TS.id_istanza_corso";

        $result = $this->getColPrepared($sql, [$id_tutor]);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    public function getListOfTutoredUsers($id_tutor)
    {
        $sql = 'SELECT U1.id_utente, U1.username, U1.nome, U1.cognome, U1.tipo, MC.titolo, I.status,
                   IC.id_corso, IC.id_istanza_corso, IC.data_inizio, IC.durata, IC.data_fine
          FROM utente AS U1, modello_corso AS MC, iscrizioni AS I, istanza_corso AS IC, tutor_studenti AS TS
         WHERE TS.id_utente_tutor =?
           AND IC.id_istanza_corso = TS.id_istanza_corso
           AND MC.id_corso = IC.id_corso
           AND I.id_istanza_corso = IC.id_istanza_corso
           AND U1.id_utente = I.id_utente_studente
           ';
        $resultAr = $this->getAllPrepared($sql, [$id_tutor], AMA_FETCH_ASSOC);
        if (AMADB::isError($resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $resultAr;
    }

    public function getListOfTutoredUniqueUsers($id_tutor)
    {
        $sql = 'SELECT U1.id_utente, U1.username, U1.nome, U1.cognome, U1.tipo
                FROM utente AS U1
                JOIN
                (SELECT DISTINCT I.id_utente_studente FROM
                iscrizioni AS I, istanza_corso AS IC, tutor_studenti AS TS
                WHERE TS.id_utente_tutor = ?
                    AND IC.id_istanza_corso = TS.id_istanza_corso
                    AND I.id_istanza_corso = IC.id_istanza_corso)
                AS U2 ON (U1.id_utente = U2.id_utente_studente)
                ';
        $resultAr = $this->getAllPrepared($sql, [$id_tutor], AMA_FETCH_ASSOC);
        if (AMADB::isError($resultAr)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $resultAr;
    }

    public function getUsersByType($user_type = [], $retrieve_extended_data = false)
    {
        $type = join(',', array_fill(0, count($user_type ?? []), '?'));
        if ($retrieve_extended_data) {
            $sql = "SELECT id_utente, nome, cognome, tipo, username, e_mail FROM utente WHERE tipo IN ($type) ORDER BY cognome ASC";
        } else {
            $sql = "SELECT tipo, username FROM utente WHERE tipo IN ($type)";
        }

        $result = $this->getAllPrepared($sql, $user_type ?? [], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getUsersByTypeFromPositionToPosition($user_type = [], $start = 0, $count = null)
    {
        $type = join(',', array_fill(0, count($user_type ?? []), '?'));
        $sql = "SELECT id_utente, nome, cognome, e_mail, username, tipo FROM utente WHERE tipo IN ($type) LIMIT $start";
        if (!is_null($count)) {
            $sql .= ",$count";
        }
        $result = $this->getAllPrepared($sql, $user_type ?? [], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function countUsersByType($user_type = [])
    {
        $type = join(',', array_fill(0, count($user_type ?? []), '?'));
        $sql = "SELECT COUNT(id_utente) FROM utente WHERE tipo IN ($type)";
        $result = $this->getOnePrepared($sql, $user_type ?? []);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function getTutorsForStudent($id_student)
    {
        $sql = "SELECT U.tipo, U.username, U.nome, U.cognome, U.avatar FROM utente AS U, iscrizioni AS I, tutor_studenti AS T
                WHERE I.id_utente_studente=? AND T.id_istanza_corso = I.id_istanza_corso
                AND U.id_utente = T.id_utente_tutor";
        $result = $this->getAllPrepared($sql, [$id_student], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Get a list of admins' fields from the DB
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     * @see find_admins_list
     */
    public function &getAdminsList($field_list_ar = [])
    {
        return $this->findAdminsList($field_list_ar);
    }

    /**
     * Get a list of admins' ids from the DB
     *
     * @access public
     *
     * @return an array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     *
     * @see find_admins_list, get_admins_list
     */
    public function &getAdminsIds()
    {
        return $this->getAdminsList();
    }
    /**
     * Get those admins' ids verifying the given criterium
     *
     * @access public
     *
     * @param $field_list_ar an array containing the desired fields' names
     *        possible values are: nome, cognome, e-mail, username, password, telefono, profilo, tariffa
     *
     * @param  clause the clause string which will be added to the select
     *
     * @return a nested array containing the list, or an AMAError object or a DB_Error object if something goes wrong
     * The form of the nested array is:
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     */
    public function &findAdminsList($field_list_ar = [], $clause = '')
    {
        // build comma separated string out of $field_list_ar array
        if (count($field_list_ar)) {
            $more_fields = ', ' . implode(', ', $field_list_ar);
        }
        // handle null clause, too
        if ($clause) {
            $clause = ' AND ' . $clause;
        }

        // do the query
        $sql_query = "select id_utente$more_fields from utente where  tipo=" . AMA_TYPE_ADMIN . " $clause";
        $admins_ar =  $this->getAllPrepared($sql_query);
        if (AMADB::isError($admins_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        //
        // return nested array in the form
        //
        return $admins_ar;
    }

    public function getAdmin($id)
    {
        // get a row from table UTENTE
        $get_user_result = $this->getUserInfo($id);
        if (AMACommonDataHandler::isError($get_user_result)) {
            // $get_user_result is an AMAError object
            //?
        }
        return $get_user_result;
    }


    /** Sara -14/01/2015
     * get some log data for a given tester
     * @return  $res_ar array
     */
    public function testerLogReport($tester = 'default', $Services_TypeAr = null)
    {
        if (defined('CONFIG_LOG_REPORT') && CONFIG_LOG_REPORT && is_array($GLOBALS['LogReport_Array']) && count($GLOBALS['LogReport_Array'])) {
            $res_ar = [];
            $sql = [];
            if (isset($Services_TypeAr)) {
                $Services_Type = $Services_TypeAr;
            } elseif (isset($_SESSION['service_level'])) {
                $Services_Type = $_SESSION['service_level'];
            }
            foreach ($GLOBALS['LogReport_Array'] as $key => $value) {
                /* if a case fails or a query return error, the corresponding column will not appear in log report table */
                switch ($key) {
                    case 'final_users':
                        $sql[$key] = "SELECT COUNT(`id_utente`) `tipo` FROM `utente` WHERE `tipo` = " . AMA_TYPE_STUDENT;
                        break;
                    case 'user_subscribed':
                        $sql[$key] = "SELECT COUNT(DISTINCT(`id_utente_studente`))  FROM `iscrizioni` WHERE `status` IN (" . ADA_STATUS_SUBSCRIBED . "," . ADA_STATUS_TERMINATED . ")";
                        break;
                    case 'course':
                        $sql[$key] = "SELECT COUNT(`id_corso`) FROM `modello_corso`";
                        break;
                    case 'service_level':
                        if (isset($Services_Type)) {
                            foreach ($Services_Type as $keyService_level => $value) {
                                $sql['course_' . $keyService_level] = "SELECT COUNT(`id_corso`) FROM `modello_corso` where `tipo_servizio`=$keyService_level";
                            }
                        }
                        break;
                    case 'sessions_started':
                        $sql[$key] = "SELECT COUNT(`id_istanza_corso`) FROM `istanza_corso` WHERE `data_inizio` > 0 AND `data_fine` >" . time();
                        break;
                    case 'student_subscribedStatus_sessStarted':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status` IN (" . ADA_STATUS_SUBSCRIBED . "," . ADA_STATUS_TERMINATED . ") AND ic.`data_inizio` > 0 AND ic.`data_fine` >" . time();
                        break;
                    case 'student_CompletedStatus_sessStarted':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status`= " . ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED . " AND ic.`data_inizio` > 0 AND ic.`data_fine` >" . time();
                        break;
                    case 'sessions_closed':
                        $sql[$key] = "SELECT COUNT(`id_istanza_corso`) FROM `istanza_corso` WHERE `data_fine` <= " . time();
                        break;
                    case 'student_subscribedStatus_sessEnd':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status` IN(" . ADA_STATUS_SUBSCRIBED . "," . ADA_STATUS_TERMINATED . ") AND ic.`data_inizio` > 0 AND ic.`data_fine` <=" . time();
                        break;
                    case 'student_CompletedStatus_sessionEnd':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status`= " . ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED . " AND ic.`data_inizio` > 0 AND ic.`data_fine` <=" . time();
                        break;
                    case 'tot_student_subscribedStatus':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic  WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status` IN (" . ADA_STATUS_SUBSCRIBED . ',' . ADA_STATUS_TERMINATED . ') AND ic.`data_inizio` > 0';
                        break;
                    case 'tot_student_CompletedStatus':
                        $sql[$key] = "SELECT COUNT(`id_utente_studente`) FROM `iscrizioni` AS i,`istanza_corso` AS ic  WHERE i.`id_istanza_corso`= ic.`id_istanza_corso` AND i.`status`=" . ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED . ' AND ic.`data_inizio` > 0';
                        break;
                    case 'tot_Session':
                        $sql[$key] = "SELECT COUNT(`id_istanza_corso`) FROM `istanza_corso`";
                        break;
                    case 'visits':
                        $sql[$key] = "SELECT COUNT(`id_history`) FROM `history_nodi` AS hn JOIN `studente` AS st ON hn.id_utente_studente = st.id_utente_studente";
                        break;
                    case 'system_messages':
                        $sql[$key] = "SELECT COUNT(`id_messaggio`) FROM `messaggi` WHERE `tipo` = '" . ADA_MSG_SIMPLE . "'";
                        break;
                    case 'chatrooms':
                        $sql[$key] = "SELECT COUNT(`id_chatroom`) FROM `chatroom`";
                        break;
                    case 'videochatrooms':
                        $sql[$key] = "SELECT COUNT(`id`) FROM `openmeetings_room`";
                        break;
                        /* Return array of this method must have this key otherwise the corresponding columns will not appear in log-report table */
                    case 'student_CompletedStatus_sessStarted_Rate':
                    case 'student_CompletedStatus_sessionEnd_Rate':
                    case 'tot_student_CompletedStatus_Rate':
                        $sql[$key] = "SELECT -1";
                        break;
                }
            }
        }

        $res_ar['provider'] = $tester;
        foreach ($sql as $type => $query) {
            $res =  $this->getOnePrepared($query);
            if (!AMADataHandler::isError($res)) {
                $res_ar[$type] = $res;
            }
        }
        return $res_ar;
    }

    /**
     * Methods accessing table `history_esercizi`
     */
    // MARK: Methods accessing table `history_esercizi`

    /**
     * Add an item  to table history_esercizi
     * Useful during the navigation. The date of the visit is computed automatically.
     *
     * @access public
     *
     * @param $student_id   the id of the student
     * @param $course_id    the id of the instance of course the student is navigating
     * @param $node_id      the node to be registered in the history
     * @param $answer       the answer in case of free answer (a text)
     * @param $remark       a remark to send to the tutor, together with the answer
     * @param $points       the points the tutor assign to the answer (filled by tutor)
     * @param $correction   a textual correction of the free answer (filled by tutor)
     * @param $ripetibile   0 = the student cannot repeat the exercise
     *                      1 = the student can repeat the exercise
     * @param $attach       the file name of attach
     *
     */
    public function addExHistory($student_id, $course_instance_id, $node_id, $answer = '', $remark = '', $points = 0, $correction = '', $ripetibile = 0, $attach = '')
    {
        $sql = 'INSERT INTO history_esercizi (id_utente_studente, id_istanza_corso, id_nodo, data_visita, data_uscita,'
            . ' risposta_libera, commento, punteggio, correzione_risposta_libera, ripetibile, allegato)'
            . ' VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $valuesAr = [
            $student_id,
            $course_instance_id,
            $node_id,
            time(),
            time(),
            $answer,
            $remark,
            $points,
            $correction,
            $ripetibile,
            $attach,
        ];

        $result = $this->queryPrepared($sql, $valuesAr);
        if (AMADB::isError($result)) {
            return new AMAError($this->errorMessage(AMA_ERR_ADD) .
                ' while in add_ex_history');
        }

        return true;
    }

    /**
     * Get all informations related to a given exercises history row.
     *
     * @access public
     *
     * @param $ex_history_id
     *
     * @return an hash with the fields
     *         the keys are:
     * node_id            - the id of the bookmarked node
     * student_id         - the id of the student
     * course_id          - the id of the instance of the course  the student is following
     * visit_date         - the moment of the visit
     * exit_date          - the moment the user left the node (?)
     * answer             - the free answer
     * remark             - a comment
     * points             - points assigned
     * correction         - a correction to a free answer
     * ripetibile         - 0 = the student cannot repeat the exercise
     * $attach            - the file name of attach
     *
     */
    public function getExHistoryInfo($ex_history_id)
    {
        // get a row from table history_nodi
        $sql  = "select id_history_ex, id_nodo, id_utente_studente, id_istanza_corso, data_visita, data_uscita,";
        $sql .= "risposta_libera, commento, punteggio, correzione_risposta_libera, ripetibile, allegato";
        $sql .= " from history_esercizi where id_history_ex=?";
        $res_ar =  $this->getRowPrepared($sql, [$ex_history_id]);
        if (AMADB::isError($res_ar)) {
            return $res_ar;
        }

        if (!$res_ar) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        $res_ha['exe_id']       = $res_ar[0];
        $res_ha['node_id']      = $res_ar[1];
        $res_ha['student_id']   = $res_ar[2];
        $res_ha['course_id']    = $res_ar[3];
        $res_ha['visit_date']   = self::tsToDate($res_ar[4]);
        $res_ha['exit_date']    = self::tsToDate($res_ar[5]);
        $res_ha['answer']       = $res_ar[6];
        $res_ha['remark']       = $res_ar[7];
        $res_ha['points']       = $res_ar[8];
        $res_ha['correction']   = $res_ar[9];
        $res_ha['ripetibile']   = $res_ar[10];
        $res_ha['allegato']     = $res_ar[11];


        /*
       global $debug; $debug=1;
       Utilities::mydebug(__LINE__,__FILE__,$res_ar);
       $debug=0;
        */



        return $res_ha;
    }

    /**
     * Get exercises history informations which satisfy a given clause
     * Only the fields specifiedin the $out_fields_ar parameter are inserted
     * in the result set.
     * This function is meant to be used by the public find_nodes_history_list()
     *
     * @access private
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $clause
     *
     *     array(array(ID1, 'field_1_1', 'field_1_2'),
     *           array(ID2, 'field_2_1', 'field_2_2'),
     *           array(ID3, 'field_3_1', 'field_3_2'))
     *
     */
    public function &doFindExHistoryList($out_fields_ar, $clause)
    {
        $more_fields = "";
        // build comma separated string out of $field_list_ar array
        if (count($out_fields_ar)) {
            foreach ($out_fields_ar as $k => &$v) {
                $v = 'he.`' . $v . '`';
            }
            $more_fields = ', ' . implode(', ', $out_fields_ar);
        }

        // add a 'where' on top of the clause
        // handle null clause, too
        if ($clause) {
            $clause = 'where ' . $clause;
        }

        // do the query
        $sql = "SELECT n.`nome`, n.`titolo`, n.`id_nodo_parent`,
						he.`id_history_ex`$more_fields
				FROM `history_esercizi` he
				JOIN `nodo` n ON (n.`id_nodo` = he.`ID_NODO`)
				$clause
				ORDER BY he.`id_history_ex` DESC";

        $res_ar =  $this->getAllPrepared($sql);
        if (AMADB::isError($res_ar)) {
            return $res_ar;
        }

        //
        // return nested array
        //
        return $res_ar;
    }

    public function &findExerciseHistoryForCourseInstance($exercise_id, $course_instance_id)
    {
        $sql = "SELECT id_history_ex, id_utente_studente FROM history_esercizi WHERE id_nodo=? AND id_istanza_corso=?";
        $result = $this->getAllPrepared($sql, [$exercise_id, $course_instance_id], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    public function findExerciseHistoryForUser($exercise_id, $course_instance_id, $user_id)
    {
        $sql = 'SELECT * FROM history_esercizi WHERE id_utente_studente = ? AND id_nodo = ? AND id_istanza_corso = ?';
        $values = [
            $user_id,
            $exercise_id,
            $course_instance_id,
        ];

        $result = $this->getRowPrepared($sql, $values, AMA_FETCH_ASSOC);
        if (AMADataHandler::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Get exercises history informations.
     * Returns all the history informations without filtering. Only the fields specified
     * in the $out_fields_ar parameter are inserted in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @return a bi-dimensional array containing the fields as specified
     *
     * @see
     *
     */
    public function &getExHistoryList($out_fields_ar)
    {
        return $this->doFindExHistoryList($out_fields_ar, '');
    }

    /**
     * Get exercises history informations for a given student, course instance or both
     * Returns all the history informations filtering on students, courses or both.
     * If a parameter has the value '*', then it is not filtered.
     * Only the fields specified
     * in the $out_fields_ar parameter are inserted in the result set.
     *
     * @access public
     *
     * @param $out_fields_ar array of the fields returned
     *
     * @param $student_id
     * @param $course_instance_id
     * @param $node_id
     *
     * @return a bi-dimensional array containing the fields as specified.
     *
     * @see
     *
     */
    public function &findExHistoryList($out_fields_ar, $student_id = 0, $course_instance_id = 0, $node_id = '')
    {
        // build the clause
        $clause = '';

        if ($student_id) {
            $student_id_prep = $this->sqlPrepared($student_id);
        }
        $clause .= "id_utente_studente = $student_id_prep";



        if ($course_instance_id) {
            if ($clause) {
                $clause .= ' and ';
            }

            $course_istance_id_prep = $this->sqlPrepared($course_instance_id);
            $clause .= "id_istanza_corso = $course_istance_id_prep";
        }


        if ($node_id) {
            if ($clause) {
                $clause .= ' and ';
            }

            $node_id_prep = $this->sqlPrepared($node_id);
            $clause .= "n.id_nodo = $node_id_prep";
        }

        // if ($clause)
        //         $clause = ' where '.$clause;


        // invokes the private method to get all the records
        return $this->doFindExHistoryList($out_fields_ar, $clause);
    }

    public function getExReport($id_node, $id_course_instance)
    {
        $sql = "SELECT risposta_libera, punteggio, count(risposta_libera) AS risposte
   	 	          FROM history_esercizi
   	 	         WHERE id_istanza_corso = ?
   	 	           AND id_nodo = ? GROUP BY risposta_libera";
        $result = $this->getAllPrepared($sql, [$id_course_instance, $id_node], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }

        return $result;
    }

    /**
     * Update informations of a record in the history_ex table
     *
     * @access public
     *
     * @param $id            - the id of the history_ex voice to modify
     * @param $history_ex_ha - the informations in a hash with keys:
     *                         commento, punteggio, correzione_risposta_libera,da_ripetere
     *
     *
     * @return an error if something goes wrong
     *
     */
    public function setExHistory($id, $history_ex_ha)
    {
        // verify that the record exists and store old values for rollback
        $res_id =  $this->getRowPrepared("select id_history_ex from history_esercizi where id_history_ex=?", [$id]);

        if (AMADB::isError($res_id)) {
            return $res_id;
        }
        if ($res_id == 0) {
            return new AMAError(AMA_ERR_NOT_FOUND);
        }

        // get old values
        $old_ha = $this->getExHistoryInfo($id);
        if (AMADB::isError($old_ha)) {
            //
        }

        if (isset($history_ex_ha['risposta_libera'])) {
            $risposta_libera = $history_ex_ha['risposta_libera'];
        } else {
            $risposta_libera = $old_ha['answer'];
        }

        if (isset($history_ex_ha['commento'])) {
            $commento = $history_ex_ha['commento'];
        } else {
            $commento = $old_ha['remark'];
        }

        if (isset($history_ex_ha['punteggio'])) {
            $punteggio = $history_ex_ha['punteggio'];
        } else {
            $punteggio = $old_ha['points'];
        }

        if (isset($history_ex_ha['correzione'])) {
            $correzione = $history_ex_ha['correzione'];
        } else {
            $correzione = $old_ha['correction'];
        }

        if (isset($history_ex_ha['da_ripetere'])) {
            $ripetibile = $history_ex_ha['da_ripetere'];
        } else {
            $ripetibile = $old_ha['ripetibile'];
        }

        if (isset($history_ex_ha['allegato'])) {
            $allegato = $history_ex_ha['allegato'];
        } else {
            $allegato = $old_ha['allegato'];
        }

        $values = [
            $risposta_libera,
            $commento,
            $punteggio,
            $correzione,
            $ripetibile,
            $allegato,
            $id,
        ];


        $sql = 'UPDATE history_esercizi'
            . ' SET risposta_libera = ?, commento = ?, punteggio = ?, correzione_risposta_libera = ?, ripetibile = ?, allegato = ?'
            . ' WHERE id_history_ex = ?';

        $result = $this->queryPrepared($sql, $values);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_UPDATE);
        }
        return true;
    }

    /*
     * Exercise functions
     */

    // vito
    public function getExerciseType($id_node)
    {
        $sql = "SELECT tipo FROM nodo WHERE id_nodo=?";
        $result = $this->getRowPrepared($sql, [$id_node], AMA_FETCH_ASSOC);
        return $result;
    }

    public function getExerciseAnswers($id_node)
    {
        $sql = "SELECT id_nodo, nome, titolo, testo, tipo, ordine, correttezza FROM nodo WHERE id_nodo_parent=?";
        $result = $this->getAllPrepared($sql, [$id_node], AMA_FETCH_ASSOC);
        return $result;
    }

    public function getExerciseAnswer($id_node)
    {
        $sql = "SELECT id_nodo, nome, titolo, testo, tipo, ordine, correttezza FROM nodo WHERE id_nodo=?";
        $result = $this->getRowPrepared($sql, [$id_node], AMA_FETCH_ASSOC);
        return $result;
    }

    public function getOtherExercises($id_nodo_parent, $ordine, $user)
    {
        $sql = "SELECT N.id_nodo, H.ripetibile FROM nodo AS N LEFT JOIN history_esercizi AS H ON (N.id_nodo=H.id_nodo) WHERE N.id_nodo_parent=? AND N.ordine>?";
        $result = $this->getAllPrepared($sql, [$id_nodo_parent, $ordine], AMA_FETCH_ASSOC);
        return $result;
    }

    public function getOrdineMaxVal($id_nodo)
    {
        $sql = "SELECT MAX(ordine) AS ordine FROM nodo WHERE id_nodo_parent=?";
        $result = $this->getRowPrepared($sql, [$id_nodo], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return $result;
        }
        return ($result['ordine'] == "") ? 0 : $result['ordine'];
    }

    public function raiseStudentLevel($id_student, $id_course_instance, $increment)
    {
        $sql = "UPDATE iscrizioni SET livello=livello+? WHERE id_utente_studente=? AND id_istanza_corso=?";
        $result = $this->queryPrepared($sql, [$increment, $id_student, $id_course_instance]);
        return $result;
    }

    /*
     * nuova classe esercizi
     */
    public function getExercise($id_node)
    {
        $sql = "SELECT id_nodo, id_utente, nome, titolo, testo, tipo, ordine, id_nodo_parent, livello, correttezza
      FROM nodo
      WHERE id_nodo=:id_node OR id_nodo_parent=:id_node";
        $result = $this->getAllPrepared($sql, ['id_node' => $id_node], AMA_FETCH_ASSOC);
        return $result;
    }

    public function getStudentAnswer($id_answer)
    {
        $sql = "SELECT id_history_ex, id_utente_studente, id_nodo, id_istanza_corso, data_visita, data_uscita, risposta_libera,
      commento, punteggio, correzione_risposta_libera, ripetibile, allegato
      FROM history_esercizi
      WHERE id_history_ex=?";
        $result = $this->getAllPrepared($sql, [$id_answer], AMA_FETCH_ASSOC);
        return $result[0]; //vito, 1 dic 2008.
    }

    /**
     * function get_notes_for_this_course_instance:
     * used to obtain data about public notes in the selected course instance,
     * private notes added by the selected user id, optionally ordered by creation
     * date and optionally showing notes visit count.
     *
     * @param integer $id_course_instance - the selected course instance into which search for notes
     * @param integer $id_user
     * @param boolean $order_by_date
     * @param boolean $show_visits
     * @return array
     */
    public function getNotesForThisCourseInstance($id_course_instance, $id_user, $order_by_date = false, $show_visits = false)
    {
        /**
         * @var $order_by_date_sql: if $order_by_date is set to true, then order notes by their
         * creation date in ascending order.
         */
        if ($order_by_date) {
            $order_by_sql = "ORDER BY N.data_creazione DESC";
        } else {
            $order_by_sql = "ORDER BY N.nome ASC";
        }

        /**
         * based on show visits value, obtain info about notes with visits count for each note or not.
         */
        switch ($show_visits) {
            // get note visits in this course instance
            case true:
                $sql = "
				SELECT
					N.id_nodo, N.id_utente, N.nome AS nome_nodo, N.titolo, N.testo, N.tipo, N.id_nodo_parent, N.data_creazione,
					U.username, U.nome, U.cognome, U.avatar
				FROM nodo N
				LEFT JOIN (SELECT id_nodo, count(id_nodo) AS numero_visite FROM history_nodi WHERE id_istanza_corso=:id_istanza_corso GROUP BY id_nodo) AS V ON (N.id_nodo=V.id_nodo)
				LEFT JOIN utente AS U ON (U.id_utente=N.id_utente)
				WHERE N.id_istanza=:id_istanza_corso AND (N.tipo = " . ADA_NOTE_TYPE . " OR (N.tipo=" . ADA_PRIVATE_NOTE_TYPE . " AND N.id_utente=:id_user)) " .
                    $order_by_sql;
                break;
                // do not get note visits
            case false:
            default:
                $sql = "
				SELECT
					N.id_nodo, N.id_utente, N.nome AS nome_nodo, N.titolo, N.testo, N.tipo, N.id_nodo_parent, N.data_creazione,
					U.username, U.nome, U.cognome, U.avatar
				FROM nodo N
				LEFT JOIN utente AS U ON (U.id_utente=N.id_utente)
				WHERE N.id_istanza=:id_istanza_corso AND (N.tipo = " . ADA_NOTE_TYPE . " OR (N.tipo=" . ADA_PRIVATE_NOTE_TYPE . " AND N.id_utente=:id_user)) " .
                    $order_by_sql;
                break;
        }

        $result = $this->getAllPrepared($sql, [
            'id_istanza_corso' => $id_course_instance,
            'id_user' => $id_user,
        ], AMA_FETCH_ASSOC);
        return $result;
    }

    /**
     * @author giorgio 27/ago/2014
     *
     * gets a menu tree_id from the provider database, if not found
     * tries the common database and if still a menu tree_id is not
     * found for the given script, module and user_type tries the default
     *
     * @param string $module (module for the menu. e.g. browsing, comunica, modules/test)
     * @param string $script (script for the menu, derived from the URL)
     * @param string $user_type AMA_USER_TYPE
     * @param number $self_instruction non zero if course is in self instruction mode
     * @param boolean $get_all set it to true to get also disabled elements. Defaults to false
     *
     * @return boolean false | array (
     *                          'tree_id' the menu tree id to be used
     *                          'isVertical' non zero if this is a vertical menu
     *                          'dbToUse' the DataHandler where the menu was found
     *                         )
     *
     * @access public
     */
    public function getMenutreeId($module, $script, $user_type, $self_instruction = 0)
    {

        $default_module = 'main';    // module name to be used as a default value
        $default_script = 'default'; // script name to be used as a default value
        $menu_found = false;
        $retVal = [];

        // get the query string as an array
        $queryStringArr = (strlen($_SERVER['QUERY_STRING']) > 0) ? explode('&', $_SERVER['QUERY_STRING']) : [];

        $sql = 'SELECT tree_id, script, isVertical, linked_tree_id FROM menu_page WHERE module=? AND script LIKE ? AND user_type=? AND self_instruction=?';

        $common_dh = AMACommonDataHandler::getInstance();

        /**
         * Rules used to look for a menu:
         * - try passed module/script  in current provider, and if nothing is found
         * - try passed module/script  in common  provider, and if nothing is found
         * - try passed module/default in current provider, and if nothing is found
         * - try passed module/default in common  provider, and if nothing is found
         * - try main/default          in current provider, and if nothing is found
         * - try main/default          in common  provider, and if nothing is found
         * - give up.
         */

        foreach ([$module, $default_module] as $nummodule => $currentModule) {
            foreach ([$script, $default_script] as $numscript => $currentScript) {
                // skip main module/passed script as per above rules
                if ($nummodule == 1 && $numscript == 0) {
                    continue;
                }
                $params =  [$currentModule, $currentScript . '%', $user_type, $self_instruction];
                foreach ([$this, $common_dh] as $dbToUse) {
                    $candidates = $dbToUse->getAllPrepared($sql, $params, AMA_FETCH_ASSOC);
                    if (!AMADB::isError($candidates) && $candidates !== false && count($candidates) > 0) {
                        $bestScore = 0;
                        $bestNumOfMatchedParams = 0;
                        /**
                         * main loop to look for a menu to return
                         */
                        foreach ($candidates as $menuCandidate) {
                            /**
                             * search if there's a query string and
                             * load it in the mneuCandidate array
                             */
                            $querypos = strpos($menuCandidate['script'], '?');
                            if ($querypos !== false) {
                                $menuCandidate['queryString'] = substr($menuCandidate['script'], $querypos + 1);
                            } else {
                                $menuCandidate['queryString'] = null;
                            }

                            if (is_null($menuCandidate['queryString'])) {
                                /**
                                 * if menu candidate has no query string, it's the menu
                                 * only if it's the only candidate or the url had no query string
                                 */
                                if (count($candidates) === 1 || count($queryStringArr) === 0) {
                                    $menu_found = true;
                                } else {
                                    /**
                                     * save the menu as a default for this script,
                                     * to be returned if nothing more appropriate is found
                                     */
                                    if ($bestScore <= 0) {
                                        $bestMatch = $menuCandidate;
                                    }
                                }
                            } else {
                                /**
                                 * if menu candidate has a query string the menu is
                                 * the one with the highest number of matching params
                                 */

                                // make the array of the menuCandidate query string
                                $menuCandidateArr = explode('&', $menuCandidate['queryString']);
                                // find matched parameters array
                                $matchedParams = array_intersect($menuCandidateArr, $queryStringArr);
                                if (count($matchedParams) > 0) {
                                    $score = count($matchedParams) / count($menuCandidateArr);

                                    /**
                                     * if current candidate has a score higher than the bestScore or
                                     * if it has an equal score but with more mathched parameters,
                                     * than it is the new best match
                                     */

                                    if (
                                        $score > $bestScore ||
                                        ($score == $bestScore && count($matchedParams) > $bestNumOfMatchedParams)
                                    ) {
                                        $bestScore = $score;
                                        $bestNumOfMatchedParams = count($matchedParams);
                                        $bestMatch = $menuCandidate;
                                    }
                                }
                            }
                            if ($menu_found) {
                                break;
                            }
                        }
                        /**
                         * if nothing is found BUT there's a bestMatch, use it as the menu
                         */
                        if (!$menu_found && isset($bestMatch)) {
                            $menu_found = true;
                            $menuCandidate = $bestMatch;
                        }
                        if ($menu_found) {
                            break;
                        }
                    }
                }
                if ($menu_found) {
                    break;
                }
            }
            if ($menu_found) {
                break;
            }
        }
        // if no menu has been found return false right away!
        if ($menu_found === true) {
            $retVal['tree_id'] = $menuCandidate['tree_id'];
            $retVal['isVertical'] = $menuCandidate['isVertical'];
            $retVal['dbToUse'] = $dbToUse;
            // if is a linked tree, set the actual tree_id to the linked one
            if (!is_null($menuCandidate['linked_tree_id'])) {
                $retVal['tree_id'] = $menuCandidate['linked_tree_id'];
                $retVal['linked_from'] = $menuCandidate['tree_id'];
            }
        } else {
            $retVal = false;
        }

        return $retVal;
    }

    /**
     * @author giorgio 27/ago/2014
     *
     * gets the left and right submenu trees
     *
     * @param number $tree_id the id of the menu tree to load
     * @param AMADataHandler $dbToUse the data handler to be used, either Common or Tester
     * @param boolean $get_all set it to true to get also disabled elements.
     *
     * @return array associative, with 'left' and 'right' keys for each submenu tree
     *
     * @access public
     */
    public function getMenuChildren($tree_id, $dbToUse, $get_all = false)
    {
        $retVal = [];
        // get all the first level items, first left and then right side
        foreach ([0 => 'left', 1 => 'right'] as $sideIndex => $sideString) {
            $sql = 'SELECT MI.*, MT.extraClass AS menuExtraClass FROM `menu_items` AS MI JOIN `menu_tree` AS MT ON ' .
                'MI.item_id=MT.item_id WHERE MT.tree_id=? AND MT.parent_id=0 AND MI.groupRight=?';
            if (!$get_all) {
                $sql .= ' AND MI.enabledON!="' . Menu::NEVER_ENABLED . '"';
            }
            $sql .= ' ORDER BY MI.order ASC';

            $res = $dbToUse->getAllPrepared($sql, [$tree_id, $sideIndex], AMA_FETCH_ASSOC);

            if (!AMADB::isError($res) && count($res) > 0) {
                foreach ($res as $count => $element) {
                    $res[$count]['children'] = $this->getMenuChildrenRecursive($element['item_id'], $dbToUse, $get_all, $tree_id);
                }
                $retVal[$sideString] = $res;
            } else {
                $retVal[$sideString] = null;
            }
        }
        return $retVal;
    }

    /**
     * @author giorgio 19/ago/2014
     *
     * recursively gets all the children of a given menu item
     *
     * @param number $tree_id the id of the menu tree to load
     * @param number $parent_id the id of the parent to get children for
     * @param AMADataHandler $dbToUse the data handler to be used, either Common or Tester
     * @param boolean $get_all set it to true to get also disabled elements.
     *
     * @return array of found children or null if no children found
     *
     * @access private
     */
    private function getMenuChildrenRecursive($parent_id, $dbToUse, $get_all, $tree_id = 0)
    {

        $sql = 'SELECT MI.*, MT.extraClass AS menuExtraClass FROM `menu_items` AS MI JOIN `menu_tree` AS MT ON ' .
            'MI.item_id=MT.item_id WHERE MT.tree_id=? AND MT.parent_id=?';
        if (!$get_all) {
            $sql .= ' AND MI.enabledON!="' . Menu::NEVER_ENABLED . '"';
        }
        $sql .= ' ORDER BY MI.order ASC';

        $res = $dbToUse->getAllPrepared($sql, [$tree_id, $parent_id], AMA_FETCH_ASSOC);

        if (AMADB::isError($res) || count($res) <= 0 || $res === false) {
            return null;
        } else {
            foreach ($res as $count => $element) {
                $res[$count]['children'] = $this->getMenuChildrenRecursive($element['item_id'], $dbToUse, $get_all, $tree_id);
            }
            return $res;
        }
    }
}

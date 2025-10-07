<?php

/**
 * Spool
 *
 * @package
 * @author      Guglielmo Celata <guglielmo@celata.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Comunica\Spools;

use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;

/**
 * Spool extends the AMADataHandler, to communicate with the DB,
 * and implements the API to access data regarding messages.
 * Some functions are implemented in the original Spool, while some others
 * in the derivated classes.
 *
 * The class hierarchy is the following:
 *
 *                  AMADataHandler
 *                          ^
 *                          |
 *                        Spool
 *                          ^
 *                          |
 *         ---------------------------------
 *         ^                ^              ^
 *         |                |              |
 *    SimpleSpool       AgendaSpool     ChatSpool
 *
 *
 * @access public
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */
class Spool extends AbstractAMADataHandler
{
    public $ntc;       // characteristic time for non-read messages
    public $rtc;       // characteristic time for read messages
    public $type;      // type of messages
    public $user_id;   // user of the spool
    public $cleaned;   // is hygenic to clean only once in a session
    public $id_chatroom;

    public function __construct($user_id = "", $dsn = null)
    {

        // logger("entered Spool constructor - user_id=$user_id", 3);
        $this->user_id = $user_id;
        //AMADataHandler::AMADataHandler();
        parent::__construct($dsn);
    }


    /**
     * add a message to the spool by writing record in the
     * "messaggi" and "destinatari_messaggi" tables in a transational way
     *
     * @access  public
     *
     * @param   $message_ha        - message data as an hash with keys:
     *                               ID, data_ora, titolo, priorita, testo
     * @param   $recipients_ids_ar - list of recipients ids
     *
     * @return  mixed|AMAError an AMAError object if something goes wrong
     *
     */
    public function addMessage($message_ha, $recipients_ids_ar, $check_on_uniqueness = true)
    {

        // 28/12/01 Graffio
        // Modifica per differenziare il trattamento delle date
        // che provengono da send_event e sendMessage
        // Andra' corretto in send_event
        if ($message_ha['data_ora'] == "now") {
            $timestamp = $this->dateToTs($message_ha['data_ora']);
        } else {
            $timestamp = $message_ha['data_ora'];
        }
        // Fine modifica

        $title     = $this->orNull($message_ha['titolo'] ?? '');
        $id_group  = $this->orZero($message_ha['id_group'] ?? '');
        $priority  = $this->orZero($message_ha['priorita'] ?? '');
        $body      = $this->orNull($message_ha['testo']);
        $type      = $this->type;
        $sender_id = $this->user_id;
        $flags     = $this->orZero($message_ha['flags'] ?? '');


        /*
        $sql = "select id_messaggio from messaggi ".
                            " where data_ora=".$timestamp.
                            "   and tipo=".$type.
                            "   and id_group=".$id_group.
                            "   and titolo=".$title.
                            "   and id_mittente=".$sender_id;
        */
        if ($check_on_uniqueness) {
            $sql = 'SELECT id_messaggio FROM messaggi WHERE data_ora=? AND tipo=? AND id_group=? AND titolo=? AND id_mittente=?';


            // verify key uniqueness
            //$id =  $db->getOne($sql);
            $id = parent::getOnePrepared($sql, [$timestamp, $type, $id_group, $title, $sender_id]);

            if (AMADataHandler::isError($id)) {
                $retval = new AMAError(AMA_ERR_GET);
                return $retval;
            }
            if ($id) {
                $retval = new AMAError(AMA_ERR_UNIQUE_KEY);
                return $retval;
            }
        }

        // insert a row into table messaggi
        /*
        $sql =  "insert into messaggi (data_ora, tipo, id_group, titolo, id_mittente, priorita, testo)";
        $sql .= " values ($timestamp, $type, $id_group, $title, $sender_id, $priority, $body);";
        */

        $sql = 'INSERT INTO messaggi(data_ora,tipo,id_group,titolo,id_mittente,priorita,testo,flags) VALUES(?,?,?,?,?,?,?,?)';

        //$res = parent::executeCritical( $sql );
        $res = parent::executeCriticalPrepared($sql, [$timestamp, $type, $id_group, $title, $sender_id, $priority, $body, $flags]);
        if (AMADB::isError($res)) {
            // $res is an AMAError object
            return $res;
        }

        // get the id of the last inserted message

        // Modified 19/01/2005, Stamatios Filippis
        // we check the message type,if it is a generic chatroom message
        // we stop here the procedure, since we do not need to access the "destinatari_messaggi" table
        // and we return the id of the last insert message
        //case of public chat, once we get the id and quit the rest of the function
        if ($this->type == ADA_MSG_CHAT) {
            /*
            $sql = "select id_messaggio from messaggi ".
                            " where data_ora=".$timestamp.
                            "   and tipo=".$type.
            //     "   and titolo=".$title.
                            "   and id_group=".$id_group.
                            "   and id_mittente=".$sender_id;
            */
            $sql = 'SELECT id_messaggio FROM messaggi WHERE data_ora=? AND tipo=? AND id_group=? AND id_mittente=?';
            //$id = $db->getOne($sql);
            $id = parent::getOnePrepared($sql, [$timestamp, $type, $id_group, $sender_id]);
            if (AMADB::isError($id) || !$id) {
                $retval = new AMAError(AMA_ERR_NOT_FOUND);
                return $retval;
            }

            return $id;
            //end ADA_MSG_CHAT
        } elseif ($this->type == ADA_MSG_PRV_CHAT) {
            //case of private chat message, we get the id and we go on.
            //we have to access the "destinatari_messaggi" table
            /* $sql = "select id_messaggio from messaggi ".
                            " where data_ora=".$timestamp.
                            "   and tipo=".$type.
            //                        "   and titolo=".$title.
                            "   and id_group=".$id_group.
                            "   and id_mittente=".$sender_id;
            */
            $sql = 'SELECT id_messaggio FROM messaggi WHERE data_ora=? AND tipo=? AND id_group=? AND id_mittente=?';
            //$id = $db->getOne($sql);
            $id = parent::getOnePrepared($sql, [$timestamp, $type, $id_group, $sender_id]);
            if (AMADB::isError($id) || !$id) {
                $retval = new AMAError(AMA_ERR_NOT_FOUND);
                return $retval;
            }
            //end ADA_MSG_PRV_CHAT
        } else {
            /*$sql = "select id_messaggio from messaggi ".
                            " where data_ora=".$timestamp.
                            "   and tipo=".$type.
                            "   and titolo=".$title.
            //                        "   and id_group=".$id_group.
                            "   and id_mittente=".$sender_id;
            // logger("performing query: $sql", 4);
             */
            $sql = 'SELECT id_messaggio FROM messaggi WHERE data_ora=? AND tipo=? AND titolo=? AND id_mittente=?';
            //$id = $db->getOne($sql);
            $id = parent::getOnePrepared($sql, [$timestamp, $type, $title, $sender_id]);

            if (AMADB::isError($id) || !$id) {
                // logger("query failed", 4);
                $retval = new AMAError(AMA_ERR_NOT_FOUND);
                return $retval;
            }
        } // end type control

        // start the transaction
        $this->beginTransaction();

        // push instruction to remove the record into rollback segment
        $this->rsAdd("_remove_message", $id);


        // insert references of the message related to all recipients
        // into the 'destinatari_messaggi' table
        foreach ($recipients_ids_ar as $rid) {
            // add message to 'destinatari_messaggi' table
            $sql = "insert into destinatari_messaggi (id_messaggio, id_utente) " .
                "values (?, ?)";

            // logger("performing query: $sql", 4);
            $res = $this->queryPrepared($sql, [$id, $rid]);
            if (AMADB::isError($res)) {
                // logger("query failed", 4);

                // rollback in case of error
                $this->rollback();
                $retval = new AMAError(AMA_ERR_ADD);
                return $retval;
            }
            // logger("query succeeded", 4);

            // insert instruction into rollback segment
            $this->rsAdd("_clean_message", $id, $rid);
        }

        // final success
        $this->commit();
        return $id;
    }

    /**
     * log a message by writing record in the
     * "utente_messaggio_log" tables
     *
     * @access  public
     *
     * @param   $message_ha        - message data as an hash with keys:
     *                               tempo, mittente, id_corso, id_istanza_corso, titolo, testo, lingua
     * @param   $recipients_ids_ar - list of recipients ids
     *
     * @return  mixed|AMAError an AMAError object if something goes wrong
     *
     */
    public function logMessage($message_ha, $recipients_ids_ar)
    {

        // logger("entered Spool::log_message", 3);

        // prepare data to be inserted

        // 28/12/01 Graffio
        // Modifica per differenziare il trattamento delle date
        // che provengono da send_event e sendMessage
        // Andra' corretto in send_event
        if ($message_ha['data_ora'] == "now") {
            $timestamp = $this->dateToTs($message_ha['data_ora']);
        } else {
            $timestamp = $message_ha['data_ora'];
        }
        // Fine modifica
        /*
        * vito 4 feb 2009
        */
        //       $id_course = $message_ha['id_course'];
        //       $id_course_instance = $message_ha['id_course_instance'];
        if (!isset($message_ha['id_course']) || empty($message_ha['id_course'])) {
            $id_course = 0;
        } else {
            $id_course = $message_ha['id_course'];
        }
        if (!isset($message_ha['id_course_instance']) || empty($message_ha['id_course_instance'])) {
            $id_course_instance = 0;
        } else {
            $id_course_instance = $message_ha['id_course_instance'];
        }


        if (empty($message_ha['language'])) {
            $language = ADA_DEFAULT_LANGUAGE;
        } else {
            $language = $message_ha['language'];
        }

        $title = $this->orNull($message_ha['titolo']);
        // vito 19 gennaio 2009
        //       $text = sqlPrepared($message_ha['testo']);
        $text = $message_ha['testo'];

        $type = "'" . $this->type . "'";


        $sender_id = $this->user_id;
        if ($this->type == ADA_MSG_CHAT) {
            $recipient_ids = "";
        } else {
            $recipient_ids = implode(",", $recipients_ids_ar);
        } //end ADA_MSG_CHAT

        $status = "1"; // 0: non initialized; 1: logged in DB; 2: logged in DB and removed from message tables; 3 logged to file

        $flags = $this->orZero($message_ha['flags']);

        // MARK: preparare query
        // insert a row into table utente_messaggio_log
        $sql =  "insert into utente_messaggio_log (tempo, id_mittente, testo, tipo, status, titolo,  id_istanza_corso, id_corso, lingua, id_riceventi, flags)";
        $sql .= " values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $values = [
            $timestamp,
            $sender_id,
            $text,
            $type,
            $status,
            $title,
            $id_course_instance,
            $id_course,
            $language,
            $recipient_ids,
            $flags,
        ];
        // logger("performing query: $sql", 4);

        /*
         $res = $db->query($sql);
         if (AMADB::isError($res) || $db->affectedRows()==0){
         // logger("query failed", 4);
         return new AMAError(AMA_ERR_ADD);
         }// logger("query succeeded", 4);
         */
        $res = parent::executeCriticalPrepared($sql, [$values]);
        if (AMADB::isError($res)) {
            // $res is an AMAError object
        }
        return $res;
    }

    /**
     * get all the messages sent by user and logged verifying the given clause
     * the list of fields specified is returned
     * records are sorted by the given order
     *
     * @access  public
     *
     * @param   $fields_list_ar - a list of fields to return
     * @param   $clause         - a clause to filter records
     *                            (records are always filtered for user and type)
     * @param   $ordering       - the order
     *
     * @return  a reference to a hash, if more than one fields are required
     *           res_ha[ID_MESSAGGIO] contains an array with all the values
     *          a reference to a linear array if only one field is required
     *          an AMAError object if something goes wrong
     *
     */
    public function &findLoggedMessages($fields_list = "", $clause = "", $ordering = "")
    {

        /* logger("entered Spool::find_logged_messages - ".
         "[fields_list=".serialize($fields_list).
         ", clause=$clause, ordering=$ordering]", 3);
         */

        $user_id = $this->user_id;
        $type = $this->type;

        //prepare fields list
        if ($fields_list != "") {
            $fields = "id_messaggio, " . implode(",", $fields_list);
        } else {
            $fields = "id_messaggio";
        }
        // logger("fields_list: $fields", 4);

        // set where clause
        $basic_clause = "id_mittente='$user_id' and tipo='$type' ";
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }

        // set ordering instruction
        if ($ordering != "") {
            $ordering = "order by $ordering";
        }

        $sql = "select $fields from utente_messaggio_log " .
            " where $clause $ordering";
        // logger("performing query: $sql", 4);

        //   if ($fields_list != ""){

        // bidimensional array
        //       $res_ar = $db->getAssoc($sql);

        //   } else {

        // linear array
        $res_ar = $this->getColPrepared($sql);

        //  }

        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // logger("query succeeded", 4);

        return $res_ar;
    }


    /**
     * get all the messages verifying the given clause
     * the list of fields specified is returned
     * records are sorted by the given order
     *
     * @access  public
     *
     * @param   $fields_list_ar - a list of fields to return
     * @param   $clause         - a clause to filter records
     *                            (records are always filtered for user and type)
     * @param   $ordering       - the order
     *
     * @return  array|AMAError a reference to a hash, if more than one fields are required
     *           res_ha[ID_MESSAGGIO] contains an array with all the values
     *          a reference to a linear array if only one field is required
     *          an AMAError object if something goes wrong
     *
     */
    public function &findMessages($fields_list = "", $clause = "", $ordering = "")
    {

        $user_id = $this->user_id;
        $type    = $this->type;

        if ($fields_list != "") {
            $fields = "messaggi.id_messaggio, " . implode(",", $fields_list);
        } else {
            $fields = "messaggi.id_messaggio";
        }

        // Modified by Stamatios Filippis 27-01-05
        $tables = "messaggi";

        /*
         *     ChatSpool find_messages method overwrites this method
         */
        //    // check the type of the message
        //    if ($type == ADA_MSG_CHAT)// we have to access only the "messaggi" table
        //    {
        //      // getting the time that users joined the chatroom
        //      $id_chatroom=$this->id_chatroom;
        //      $et= "select tempo_entrata from utente_chatroom where id_utente=$user_id and id_chatroom=$id_chatroom";
        //      $entrance_time = $db->getOne($et);
        //      $basic_clause = "tipo='$type' and data_ora>=$entrance_time";
        //    }
        //    elseif ($type == ADA_MSG_PRV_CHAT)
        //    {
        //            $tables .=", destinatari_messaggi ";
        //            // getting the time that users joined the chatroom
        //            $id_chatroom=$this->id_chatroom;
        //            $et= "select tempo_entrata from utente_chatroom where id_utente=$user_id and id_chatroom=$id_chatroom";
        //            $entrance_time = $db->getOne($et);
        //
        //            $basic_clause = "id_utente='$user_id' and tipo='$type' and data_ora>=$entrance_time " .
        //                          " and messaggi.id_messaggio=destinatari_messaggi.id_messaggio";
        //    }
        //    else // all other cases
        //    {

        if (is_array($fields_list) && in_array('utente.username', $fields_list)) {
            $tables .= ", destinatari_messaggi AS DM, utente ";

            $basic_clause = "DM.id_utente=$user_id "
                . "AND messaggi.tipo='$type' AND messaggi.id_messaggio=DM.id_messaggio "
                . "AND utente.id_utente=messaggi.id_mittente";
        } else {
            $tables .= ", destinatari_messaggi AS DM";

            $basic_clause = "DM.id_utente=$user_id "
                . "AND messaggi.tipo='$type' AND messaggi.id_messaggio=DM.id_messaggio";
        }

        //    }
        // set where clause
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }
        // set ordering instruction
        if ($ordering != "") {
            $ordering = "order by $ordering";
        }

        $sql = "select $fields from $tables where $clause $ordering";
        // logger("performing query: $sql", 4);
        if ($fields_list != "") {
            // bidimensional array
            $dbar = $this->getAllPrepared($sql);
            if (AMADB::isError($dbar)) {
                $res_ar = $dbar;
            } else {
                $res_ar = [];
                foreach ($dbar as $dbrow) {
                    // remove id_messaggio and its numeric key. (2 items)
                    $res_ar[$dbrow['id_messaggio']] = array_slice($dbrow, 2);
                }
            }
        } else {
            // linear array
            $res_ar = $this->getColPrepared($sql);
        }

        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // logger("query succeeded", 4);

        return $res_ar;
    }

    public function &findChatMessages($fields_list = "", $clause = "", $ordering = "")
    {

        /* logger("entered Spool::find_chat_messages - ".
         "[fields_list=".serialize($fields_list).
         ", clause=$clause, ordering=$ordering]", 3);
         */
        $type = $this->type;
        $id_group = $this->id_chatroom;

        //prepare fields list
        if ($fields_list != "") {
            $fields = "messaggi.id_mittente, messaggi.testo, messaggi.data_ora, " . $fields_list;
        } else {
            $fields = "messaggi.id_mittente, messaggi.testo, messaggi.data_ora";
        }
        // logger("fields_list: $fields", 4);

        // Modified by Stamatios Filippis 27-01-05
        $tables = "messaggi";

        $basic_clause = "tipo='$type' and id_group=$id_group";
        // set where clause
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }
        // set ordering instruction
        if ($ordering != "") {
            $ordering = "order by $ordering";
        }
        $sql = "select $fields from $tables where $clause $ordering";
        //echo $sql;

        // logger("performing query: $sql", 4);

        $res_ar = $this->getAllPrepared($sql);

        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // logger("query succeeded", 4);

        return $res_ar;
    }


    /**
     * get all the messages sent by user verifying the given clause
     * the list of fields specified is returned
     * records are sorted by the given order
     *
     * @access  public
     *
     * @param   $fields_list_ar - a list of fields to return
     * @param   $clause         - a clause to filter records
     *                            (records are always filtered for user and type)
     * @param   $ordering       - the order
     *
     * @return  a reference to a hash, if more than one fields are required
     *           res_ha[ID_MESSAGGIO] contains an array with all the values
     *          a reference to a linear array if only one field is required
     *          an AMAError object if something goes wrong
     *
     */
    public function &findSentMessages($fields_list = "", $clause = "", $ordering = "")
    {

        /* logger("entered Spool::find_sent_messages - ".
         "[fields_list=".serialize($fields_list).
         ", clause=$clause, ordering=$ordering]", 3);
         */

        $user_id = $this->user_id;
        $type = $this->type;

        //prepare fields list
        if ($fields_list != "") {
            // prepend every element of $field_list with an 'M.' string
            array_map(fn ($val) => "M." . $val, $fields_list);
            $fields = "M.id_messaggio, " . implode(",", $fields_list);
        } else {
            $fields = "M.id_messaggio";
        }
        // logger("fields_list: $fields", 4);

        // set where clause
        $basic_clause = "M.id_mittente='$user_id' and M.tipo='$type' ";
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }
        // set ordering instruction
        if ($ordering != "") {
            $ordering = "order by $ordering";
        }

        // giorgio, new query to get recipient id, name and last name
        $sql = "SELECT $fields , DM.id_utente AS id_destinatatrio, " .
            "U.nome AS nome_destinatario, U.cognome AS cognome_destinatario " .
            "FROM  `messaggi` M,  `destinatari_messaggi` DM,  `utente` U " .
            "WHERE $clause AND M.id_messaggio = DM.id_messaggio " .
            "AND DM.id_utente = U.id_utente $ordering";

        //     $sql = "select $fields from messaggi ".
        //                " where $clause $ordering";
        // logger("performing query: $sql", 4);

        //   if ($fields_list != ""){

        // bidimensional array
        //       $res_ar = $db->getAssoc($sql);

        //   } else {

        // linear array
        if ($fields_list != "") {
            $res_ar = $this->getAllPrepared($sql, [], AMA_FETCH_ASSOC);
        } else {
            $res_ar = $this->getColPrepared($sql);
        }
        //  }

        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        // logger("query succeeded", 4);

        return $res_ar;
    }

    /**
     * get a list of all users data in the utente table
     * which verifies a given clause
     *
     * @access  public
     *
     * @param   $id - the id of the message
     *
     * @return  a refrerence to a 2 elements array,
     *           the first element is a hash with data of the message
     *           the second element is an array of recipients' ids
     *          an AMAError object if something goes wrong
     *
     */
    public function &getMessageInfo($id)
    {

        // logger("entered Spool::get_message_info - [id=$id]", 3);

        // get info about message
        $sql = "select id_messaggio, data_ora, tipo, titolo, id_mittente, priorita, testo,flags from messaggi " .
            " where id_messaggio=?";

        // logger("performing query: $sql", 4);
        $res_ar = $this->getRowPrepared($sql, [$id]);
        if (AMADB::isError($res_ar) || !is_array($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // logger("query succeeded", 4);

        $msg_ha['id_messaggio'] = $res_ar[0];
        $msg_ha['data_ora']     = $res_ar[1];
        $msg_ha['tipo']         = $res_ar[2];
        $msg_ha['titolo']       = $res_ar[3];
        $msg_ha['id_mittente']  = $res_ar[4];
        $msg_ha['priorita']     = $res_ar[5];
        $msg_ha['testo']        = $res_ar[6];
        $msg_ha['flags']        = $res_ar[7];

        // get recipients ids
        $sql = "select id_utente from destinatari_messaggi " .
            " where id_messaggio=?";
        // logger("performing query: $sql", 4);
        $res_ar = $this->getAllPrepared($sql, [$id]);
        if (AMADB::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        // logger("query succeeded", 4);

        $recipients_ids = [];
        foreach ($res_ar as $res_el) {
            $recipients_ids[] = $res_el[0];
        }

        // return the two elements as an array reference
        $retval = [$msg_ha, $recipients_ids];
        return $retval;
    }

    /**
     * set all messages to the specified value
     * if the value passed is incorrect, nothing is done
     *
     * @access  public
     *
     * @param   $msgs_ar - array of messages id to change
     * @param   $value   - new status ('R' or 'N')
     *
     * @return  void|AMAError an AMAError object if something goes wrong
     *
     */
    public function setMessages($msgs_ar, $value)
    {

        // logger("entered Spool::set_messages - ".
        //        "msgs_ar=".serialize($msgs_ar)." value=$value", 3);

        // convert values to timestamps and
        // get inverse values for rollback
        if ($value == 'R') {
            $inverse_value = 'N';
        } elseif ($value == 'N') {
            $inverse_value = 'R';
        }

        if (strstr("RN", (string) $value)) {
            // begin a transaction
            $this->beginTransaction();

            foreach ($msgs_ar as $msg_id) {
                // update message
                $res = $this->setMessage($msg_id, "read", $value);
                if (AMADataHandler::isError($res)) {
                    $this->rollback();
                    $retval = new AMAError(AMA_ERR_UPDATE);
                    return $retval;
                }

                // add instruction to rollback segment
                $this->rsAdd("_set_message", $msg_id, "read", $inverse_value);
            } // end foreach


            $this->commit();
        } // end if
    }

    /**
     * set status of a message (read or deleted) to new value
     *
     * @access  private
     *
     * @param   $msg_id     - id of the message
     * @param   $field      - the name of the field to modify
     *                        can be 'read' or 'deleted'
     * @param   $value      - new status
     *                        'R' | 'N' for read
     *                        'Y' | 'N' for deleted
     *
     * @return  an AMAError object if something goes wrong
     *
     */
    public function setMessage($msg_id, $field, $value)
    {

        // logger("entered Spool::_set_message - ".
        //        "[msg_id=$msg_id, field=$field, value=$value]", 3);

        switch (strtolower($field)) {
            case 'read':
                $field_name = "read_timestamp";
                if ($value == 'R') {
                    $value = $this->dateToTs("now");
                } elseif ($value == 'N') {
                    $value = 0;
                }
                break;

            case 'deleted':
                $field_name = "deleted";
                break;

            default:
                $retval = new AMAError(AMA_ERR_WRONG_ARGUMENTS);
                return $retval;
        }

        // update message
        $user_id = $this->user_id;
        $sql = "update destinatari_messaggi" .
            " set $field_name=? where id_messaggio=? and id_utente=?";
        // logger("performing query: $sql", 4);


        $res =  $this->queryPrepared($sql, [$value, $msg_id, $user_id]);

        if (AMADB::isError($res)) {
            $retval = new AMAError(AMA_ERR_UPDATE);
            return $retval;
        }
        // logger("query succeeded", 4);

        // vito, 19 gennaio 2009, se tutto ok, restituisce TRUE
        return true;
    }


    /**
     * remove a series of messages from the spool
     * this is done by setting the field deleted of table destinatari_messaggi to 'Y'
     * messages are logged into utente_messaggio_log table
     *
     * @access public
     *
     *
     * @param   $msgs_ar - array of messages id to change
     *
     * @return  void|AMAError an AMAError object if something goes wrong
     *
     */
    public function removeMessages($msgs_ar)
    {
        // logger("entered Spool::_remove_messages - ".
        //       "[msgs_ar=".serialize($msgs_ar)."]", 3);

        // loop for all messages
        foreach ($msgs_ar as $msg_id) {
            // remove message from user's spool means
            // the field deleted is set to 'Y'
            $res = $this->setMessage($msg_id, "deleted", "Y");

            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }
            // 5 dec 2008
            $msg_Ha = $this->getMessageInfo($msg_id);

            //vito 19 gennaio 2009
            //       $res = $this->log_message($msg_Ha);

            $res = $this->logMessage($msg_Ha[0], $msg_Ha[1]);

            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_ADD);
                return $retval;
            }
        }
    }

    protected function removeMessage($id)
    {
        //FIXME: richiamare remove_messages?
    }

    /**
     * remove permanently a series of messages from the spool,
     * then check that no other user are currently referring to
     * these messages and in case remove the message from the
     * messaggi table
     *
     * @access private
     *
     * @param   $user_id - id of the owner of the spool
     * @param   $msgs_ar - array of messages id to change
     *
     * @return  void|AMAError an AMAError object if something goes wrong
     *
     */
    protected function cleanMessages($msgs_ar)
    {

        // logger("entered Spool::_clean_messages - ".
        //       "[msgs_ar=".serialize($msgs_ar)."]", 3);

        foreach ($msgs_ar as $msg_id) {
            // remove message from user's spool
            $res = $this->cleanMessage($msg_id, $this->user_id);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }

            // check if message is referenced by any other user
            $sql = "select count(*) from destinatari_messaggi where id_messaggio=?";
            // logger("performing query: $sql", 4);
            $n_refs =  $this->getOnePrepared($sql, [$msg_id]);
            if (AMADB::isError($n_refs)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }

            // logger("query returned: $n_refs", 4);

            // if it is not, then remove the message from the 'messaggi' table
            if ($n_refs == 0) {
                $res =  $this->cleanMessage($msg_id);
                if (AMADataHandler::isError($res)) {
                    $retval = new AMAError(AMA_ERR_REMOVE);
                    return $retval;
                }
            }
        }
    }


    /**
     * remove permanently a message record from 'messaggi' table
     * or from 'destinatari_messaggi'
     * used in the rollback operations and in _clean_messages
     *
     * @access  private
     *
     * @param   $id  - id of the message to remove
     * @param   $rid - the recipient id
     *                 if the parameter is passed and not null, then
     *                 a row is removed from 'destinatari_messaggi' table
     *
     * @return  void|AMAError an AMAError object if something goes wrong
     *
     */
    protected function cleanMessage($id, $rid = 0)
    {

        // logger("entered Spool::_remove_message", 3);

        if ($rid == 0) {
            // remove a row from table messaggi
            $sql =  "delete from messaggi where id_messaggio=$id";
            // logger("performing query: $sql", 4);
            /*
            $res = $db->query($sql);
            //           if (AMADB::isError($res) || $db->affectedRows()==0)  ??

            if (AMADB::isError($res) || $db->numCols()==0)
            return new AMAError(AMA_ERR_REMOVE);
            */
            $res = $this->executeCriticalPrepared($sql, [$id]);
            if (AMADB::isError($res)) {
                // $res is an AMAError object
                return $res;
            }
            // logger("query succeeded", 4);
        } else {
            // remove a row from table destinatari_messaggi
            $sql =  "delete from destinatari_messaggi " .
                " where id_messaggio=? and id_utente=?";
            // logger("performing query: $sql", 4);
            /*
            $res = $db->query($sql);
            if (AMADB::isError($res) || $db->affectedRows()==0)
            return new AMAError(AMA_ERR_REMOVE);
            */
            $res = $this->executeCriticalPrepared($sql, [$id, $rid]);
            if (AMADB::isError($res)) {
                // $res is an AMAError object
                return $res;
            }

            // logger("query succeeded", 4);
        }
    }
}

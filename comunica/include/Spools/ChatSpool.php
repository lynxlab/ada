<?php

use Lynxlab\ADA\Main\AMA\AMAError;

use Lynxlab\ADA\Main\AMA\AMADB;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use Lynxlab\ADA\Comunica\Spools\Spool;

use Lynxlab\ADA\Comunica\Spools\SimpleSpool;

use Lynxlab\ADA\Comunica\Spools\ChatSpool;

// Trigger: ClassWithNameSpace. The class ChatSpool was declared with namespace Lynxlab\ADA\Comunica\Spools. //

/**
 * ChatSpool extends Spool and implements some peculiarities
 * related to the Chat sentence.
 *
 *
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Comunica\Spools;

class ChatSpool extends Spool
{
    public $id_chatroom;

    /**
     * SimpleSpool constructor
     *
     * @access  public
     *
     * @param   $user_id - the user of the spool
     *
     */
    public function __construct($user_id, $type, $id_chatroom = "", $dsn = null)
    {
        // logger("entered ChatSpool constructor", 3);
        $this->ntc = $GLOBALS['ChatSpool_ntc'];
        $this->rtc = $GLOBALS['ChatSpool_rtc'];
        $this->type = $type;

        if (!isset($id_chatroom) || empty($id_chatroom)) {
            $this->id_chatroom = $GLOBALS['id_chatroom'] ?? null;
        } else {
            $this->id_chatroom = $id_chatroom;
        }
        parent::__construct($user_id, $dsn);
    }

    /**
     * first, the cleaning mechanism is invoked, then
     * add a message to the spool by invoking the parent's method
     *
     * @access  public
     *
     * @param   $message_ha        - message data as an hash
     * @param   $recipients_ids_ar - list of recipients ids
     *
     * @return  an AMAError object if something goes wrong
     *
     * (non-PHPdoc)
     * @see Spool::add_message()
     *
     * @author giorgio 20/ott/2014
     *
     * added $check_on_uniqueness parameters to make the
     * definition compatible with Spool::add_message()
     *
     */
    public function addMessage($message_ha, $recipients_ids_ar = [], $check_on_uniqueness = false)
    {
        $this->clean();
        /*
         * Call parent add_message with no checks on message uniqueness
         */
        return parent::addMessage($message_ha, $recipients_ids_ar, $check_on_uniqueness);
    }


    /**
     * get a list of all users data in the utente table
     * which verifies a given clause
     *
     * @access  public
     *
     * @param   $fields_list_ar - a list of fields to return
     * @param   $clause         - a clause to filter records
     *
     * @return  a refrerence to a 2-dim array,
     *           each row will have id_utente in the 0 element
     *           and the fields specified in the list in the others
     *          an AMAError object if something goes wrong
     *
     */
    public function clean()
    {
        // logger("entered ChatSpool::clean", 3);
    }


    public function &findMessages($fields_list = "", $clause = "", $ordering = "")
    {
        // cleaning (don't bother on errors)
        $this->clean();

        // vito, 26 settembre 2008
        // check if fields_list is a numeric value: in this case, it's a time interval
        // TODO: se e' un valore numerico, allora deve essere l'id dell'ultimo messaggio
        // ricevuto
        if (is_numeric($fields_list)) {
            return $this->newFindMessages($fields_list);
        }

        $id_chatroom = $this->id_chatroom;

        /*
         $user_id= $this->user_id;
         // getting the time that users joined the chatroom
         $et = "select tempo_entrata from utente_chatroom where id_utente=$user_id and id_chatroom=$id_chatroom";
         $entrance_time = $db->getOne($et);

         print_r($et);
         print_r($entrance_time);
         */

        // in this spool are retrieved all the messages of the chatroom where the user
        // takes part and are inserted after his entrance into the chatroom.

        $basic_clause = "id_group=$id_chatroom";
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }
        // call the parent's find_messages (without clean)
        $res = parent::findMessages($fields_list, $clause, $ordering);
        //    if (AMADataHandler::isError($res)) {
        //      // $res is an AMAError
        //      return $res;
        //    }
        // $res can be an AMAError object or the messages list
        return $res;
    }

    private function &newFindMessages($last_read_message_id)
    {

        $id_group = $this->id_chatroom;
        $type     = $this->type;
        $user_id  = $this->user_id;

        $db = &parent::getConnection();
        if (AMADB::isError($db)) {
            return $db;
        }

        if ($last_read_message_id == 0) {
            $message_id_sql = '';
        } else {
            $message_id_sql = ' AND id_messaggio > ' . $last_read_message_id;
        }

        if ($type == ADA_MSG_CHAT) {
            $sql  = "SELECT CONCAT(U.nome, \" \",U.cognome) AS `nome`, M.id_messaggio, M.data_ora, M.tipo, M.testo, M.id_mittente as `id_mittente`
                       FROM  (SELECT id_messaggio, data_ora, tipo, id_mittente, testo FROM messaggi
                               WHERE id_group=$id_group $message_id_sql AND tipo='$type') AS M
                             LEFT JOIN utente AS U ON (U.id_utente = M.id_mittente)";
        } elseif ($type == ADA_MSG_PRV_CHAT) {
            $user = $this->user_id;

            $sql = "SELECT CONCAT(U.nome, \" \",U.cognome) AS `nome`, M.id_messaggio, M.data_ora, M.tipo, M.testo, M.id_mittente as `id_mittente`
                  FROM (SELECT id_messaggio, data_ora, tipo, id_mittente, testo FROM messaggi
                             WHERE id_group=$id_group $message_id_sql AND tipo='$type') AS M
                           LEFT JOIN utente AS U ON (U.id_utente = M.id_mittente)
                           LEFT JOIN
                           (SELECT id_messaggio FROM destinatari_messaggi WHERE id_utente=$user_id) AS PM
                           ON (M.id_messaggio=PM.id_messaggio)
            ";
        }

        $result = $db->getAll($sql, null, AMA_FETCH_ASSOC);
        if (AMADataHandler::isError($result)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }
        return $result;
    }
}

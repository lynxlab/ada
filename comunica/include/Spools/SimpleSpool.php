<?php

use Lynxlab\ADA\Main\AMA\AMAError;

use Lynxlab\ADA\Main\AMA\AMADB;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use Lynxlab\ADA\Comunica\Spools\Spool;

use Lynxlab\ADA\Comunica\Spools\SimpleSpool;

// Trigger: ClassWithNameSpace. The class SimpleSpool was declared with namespace Lynxlab\ADA\Comunica\Spools. //

/**
 * SimpleSpool extends Spool and implements some peculiarities
 * related to the Simple message.
 *
 *
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Comunica\Spools;

class SimpleSpool extends Spool
{
    /**
     * SimpleSpool constructor
     *
     * @access  public
     *
     * @param   $user_id - the user of the spool
     *
     */
    public function __construct($user_id, $dsn = null)
    {
        // logger("entered SimpleSpool constructor", 3);

        $this->ntc = $GLOBALS['SimpleSpool_ntc'];
        $this->rtc = $GLOBALS['SimpleSpool_rtc'];
        $this->type = ADA_MSG_SIMPLE;

        //Spool::Spool($user_id);
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
    public function addMessage($message_ha, $recipients_ids_ar, $check_on_uniqueness = false)
    {

        // logger("entered SimpleSpool::add_message", 3);

        $this->clean();

        $res = parent::addMessage($message_ha, $recipients_ids_ar, $check_on_uniqueness);
        if (AMADataHandler::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
    }


    /**
     * first invoke the cleaning mechanism,
     * then get all the messages verifying the given clause
     * by invoking the parent's find_messages method
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
    public function &findMessages($fields_list = "", $clause = "", $ordering = "")
    {

        /* logger("entered SimpleSpool::find_messages - ".
         "[fields_list=".serialize($fields_list).
         ", clause=$clause, ordering=$ordering]", 3);
         */
        // cleaning (don't bother on errors)
        $this->clean();
        // in this spool only messages marked as non deleted are retrieved
        $basic_clause = "deleted='N'";
        if ($clause == "") {
            $clause = $basic_clause;
        } else {
            $clause .= " and $basic_clause";
        }
        // call the parent's find_messages (without clean)
        $res = parent::findMessages($fields_list, $clause, $ordering);

        //    if (AMADataHandler::isError($res)) {
        //      // $res is an AMAError object
        //      return $res;
        //    }
        // $res can ba an AMAError object or the messages found
        return $res;
    }

    /**
     * invoke the cleaning mechanism
     * three different messages are removed from the tables:
     *  deleted messages (immediately)
     *  read messages (after rtc seconds)
     *  non read messages (after ntc seconds)
     *
     * @access  public
     *
     * @return  an AMAError object if something goes wrong
     *          it is recommended that the error is not treated
     *
     */
    public function clean()
    {

        $simpleCleaned = $GLOBALS['simpleCleaned'];

        // logger("entered SimpleSpool::clean", 3);

        // make sure cleaning is only done once per session
        if ($simpleCleaned) {
            return;
        }

        $db = &parent::getConnection();
        if (AMADB::isError($db)) {
            return $db;
        }


        // setting some variables
        $user_id = $this->user_id;
        $type    = $this->type;
        $rtc     = $this->rtc;
        $ntc     = $this->ntc;
        $now     = $this->dateToTs("now");


        // removing deleted_messages
        /*
        $sql = "select messaggi.id_messaggio from messaggi, destinatari_messaggi ".
        " where id_utente=$user_id and tipo='$type' and ".
        "       messaggi.id_messaggio=destinatari_messaggi.id_messaggio and ".
        "       deleted='Y'";
        */
        // logger("SimpleSpool::clean: removing deleted", 2);
        $res_ar = parent::findMessages("", "deleted='Y'");
        if (AMADataHandler::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        if (count($res_ar)) {
            // FIXME: self::_clean_messages al posto della riga di sotto
            $res = parent::cleanMessages($res_ar);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }
        }

        // removing non read messages
        /*
        $sql = "select messaggi.id_messaggio from messaggi, destinatari_messaggi ".
        " where id_utente=$user_id and tipo='$type' and ".
        "       messaggi.id_messaggio=destinatari_messaggi.id_messaggio and ".
        "       read_timestamp = 0 and data_ora < $now-$ntc";
        */
        // logger("SimpleSpool::clean: removing not read", 2);
        $res_ar = parent::findMessages("", "read_timestamp=0 and data_ora<$now-$ntc");
        if (AMADataHandler::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        if (count($res_ar)) {
            // FIXME: self::_clean_messages al posto della riga di sotto
            $res = parent::cleanMessages($res_ar);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }
        }


        // removing read messages
        /*
        $sql = "select messaggi.id_messaggio from messaggi, destinatari_messaggi ".
        " where id_utente=$user_id and tipo='$type' and ".
        "       messaggi.id_messaggio=destinatari_messaggi.id_messaggio and ".
        "       read_timestamp > 0 and read_timestamp < $now-$rtc";
        */
        // logger("SimpleSpool::clean: removing read", 2);
        $res_ar = parent::findMessages("", "read_timestamp>0 and read_timestamp<$now-$rtc");
        if (AMADataHandler::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_GET);
            return $retval;
        }

        if (count($res_ar)) {
            // FIXME: self::_clean_messages al posto della riga di sotto
            $res = parent::cleanMessages($res_ar);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
                return $retval;
            }
        }

        // done with cleaning for this session
        $simpleCleaned = true;
    }
}

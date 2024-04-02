<?php

/**
 * AgendaSpool extends Spool and implements some peculiarities
 * related to the Agenda event.
 *
 *
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */

namespace Lynxlab\ADA\Comunica\Spools;

class AgendaSpool extends Spool
{
    /**
     * AgendaSpool constructor
     *
     * @access  public
     *
     * @param   $user_id - the user of the spool
     *
     */
    public function __construct($user_id, $dsn = null)
    {
        // logger("entered AgendaSpool constructor", 3);

        $this->ntc = $GLOBALS['AgendaSpool_ntc'];
        $this->rtc = $GLOBALS['AgendaSpool_rtc'];
        $this->type = ADA_MSG_AGENDA;

        //Spool::Spool($user_id);
        parent::__construct($user_id, $dsn);
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
     *          an AMA_Error object if something goes wrong
     *
     */
    public function &find_messages($fields_list = "", $clause = "", $ordering = "")
    {

        /* logger("entered AgendaSpool::find_messages - ".
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
        $res = parent::find_messages($fields_list, $clause, $ordering);
        //    if (AMA_DataHandler::isError($res)) {
        //      // $res is an AMA_Error object
        //      return $res;
        //    }
        // $res can be an AMA_Error object or the messages list
        return $res;
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
     * @return  an AMA_Error object if something goes wrong
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
    public function add_message($message_ha, $recipients_ids_ar, $check_on_uniqueness = false)
    {

        // logger("entered AgendaSpool::add_message", 3);
        $this->clean();

        return parent::add_message($message_ha, $recipients_ids_ar, $check_on_uniqueness);
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
     */
    public function clean()
    {

        // logger("entered AgendaSpool::clean", 3);
    }
}

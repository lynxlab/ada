<?php

/**
 * MessageHandler, list messages
 *
 * @package
 * @author      Guglielmo Celata <guglielmo@celata.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Comunica\DataHandler;

use Lynxlab\ADA\Comunica\DataHandler\UserDataHandler;
use Lynxlab\ADA\Comunica\Spools\AgendaSpool;
use Lynxlab\ADA\Comunica\Spools\ChatSpool;
use Lynxlab\ADA\Comunica\Spools\Mailer;
use Lynxlab\ADA\Comunica\Spools\SimpleSpool;
use Lynxlab\ADA\Comunica\Spools\Spool;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\Node\Node;

/**
 * MessageHandler implements the API which a script developer can use to
 * implement interfaces regarding the ADA communication module
 * The class uses spool objects and the mailer internally
 *
 * @access public
 *
 * @author Guglielmo Celata <guglielmo@celata.com>
 */
class MessageHandler
{
    private $dsn;
    private static $instance = null;

    /**
     *
     * @param string $dsn - a valid data source name
     */
    public function __construct($dsn = null)
    {
        $this->dsn = $dsn;
    }

    /**
     * function instance
     *
     * @param string $dsn - a valid data source name
     * @return MessageHandler instance
     */
    public static function instance($dsn = null)
    {
        if (self::$instance == null) {
            self::$instance = new MessageHandler($dsn);
        } else {
            self::$instance->setDSN($dsn);
        }
        return self::$instance;
    }

    private function setDSN($dsn = null)
    {
        $this->dsn = $dsn;
    }

    /**
     * send a message of a given type to one or more recipients
     *
     * @access public
     *
     * @param   $message_ha - contains all data of message to send
     *                        this also means the recipients list
     *                        the parameter is an hash whose keys are:
     *                        data_ora,
     *                        tipo,
     *                        titolo,
     *                        mittente*,
     *                        destinatari**,
     *                        priorita,
     *                        testo
     *
     *                        *mittente is a username
     *                       **destinatari is a CVS list of usernames
     *
     * @return    an AMAError object if something goes wrong
     *
     **/
    public function sendMessage($message_ha)
    {
        $root_dir = $GLOBALS['root_dir'];

        // logger("MessageHandler::send_message entered", 2);

        // create instance of UserDataHandler
        //vito, 26 settembre 2008
        //$udh = new UserDataHandler();
        $udh = UserDataHandler::instance($this->dsn);

        // exctract type
        $type = $message_ha['tipo'];

        // extract sender name
        $sender = $message_ha['mittente'];

        // Modified by Stamatios Filippis. 19/01/2005
        // we have the interest to extract and define the recipients field in all cases
        // except the case that the message is a common chat message.in that case we do not need
        // the recipients field since we do not have to access the destinatari_messaggi table
        if ($type != ADA_MSG_CHAT) {
            // extract recipient_list
            if (is_array($message_ha['destinatari'])) {
                $recipients_ar = $message_ha['destinatari'];
            } else {
                $recipients_ar = explode(",", $message_ha['destinatari']);
            }

            foreach ($recipients_ar as $recipient) {
                $new_recipients_ar[] = "'" . trim($recipient) . "'";
            }
            // vito 14 gennaio 2009 aggiunto > 1
            if (count($new_recipients_ar) > 1) {
                $recipients_list = implode(",", $new_recipients_ar);
                $clause = "username in ($recipients_list)";
            } else {
                $recipient_list = $new_recipients_ar[0];
                // vito 14 gennaio 2009, rimossi ' ' intorno a $recipient_list
                $clause = "username = $recipient_list";
            } // logger("recipients_list: $recipients_list", 4);

            // get the list of e-mail addresses of the recipients
            $res_ar = $udh->findUsersList(["e_mail"], $clause);

            // echo "array: " . (!is_array($res_ar)) . "!";
            if (AMADataHandler::isError($res_ar) or (!is_array($res_ar))) {
                // echo "array: " . (!is_array($res_ar));
                $retval = new AMAError(AMA_ERR_SEND_MSG);
                return $retval;
            }

            // transform bidim array into linear array of ids and emails
            // logger("recipients:", 4);
            foreach ($res_ar as $user) {
                $recipients_ids_ar[] = $user[0];
                $recipients_emails_ar[] = $user[1];
                // logger("id: ".$user[0]." email: ".$user[1], 4);
            }
        } // end of exctracting recipients

        // get the sender's ID and email address
        $res_ar = $udh->findUsersList(["e_mail"], "username='$sender'");

        if (AMADataHandler::isError($res_ar) || (!is_array($res_ar)) || count($res_ar) <= 0) {
            $retval = new AMAError(AMA_ERR_SEND_MSG);
            return $retval;
        }

        $sender_id = $res_ar[0][0];
        $sender_email = $res_ar[0][1];


        // handle the mail case in a different way
        // we want to use SimpleSpool even if user selected e-mail type

        if ($type == ADA_MSG_MAIL || $type == ADA_MSG_MAIL_ONLY) {
            // delegate sending the e-mail to the mailer
            $mailer = new Mailer();
            $res = $mailer->sendMail($message_ha, $sender_email, $recipients_emails_ar);

            if (AMADataHandler::isError($res)) {
                //  return $res; ???
                $retval = new AMAError(AMA_ERR_SEND_MSG);
                return $retval;
            }
            if ($type == ADA_MSG_MAIL_ONLY) {
                return $res;
            } else {
                // continue...
                $spool = new SimpleSpool($sender_id, $this->dsn);
            }
        } else {
            switch ($type) {
                case ADA_MSG_SIMPLE:
                    // select the appropriate spool
                    $spool = new SimpleSpool($sender_id, $this->dsn);
                    break;
                case ADA_MSG_AGENDA:
                    // select the appropriate spool
                    $spool = new AgendaSpool($sender_id, $this->dsn);
                    break;

                    // Modified by Stamatios Filippis. 19/01/2005
                case ADA_MSG_CHAT:
                    // select the appropriate spool
                    $spool = new ChatSpool($sender_id, $type, null, $this->dsn);
                    break;

                    // Modified by Stamatios Filippis. 19/01/2005
                case ADA_MSG_PRV_CHAT:
                    // select the appropriate spool
                    $spool = new ChatSpool($sender_id, $type, null, $this->dsn);
                    break;
            } // end of switch
        }

        // Modified by Stamatios Filippis. 19/01/2005
        // Common chat message, no need to access destinatari_messaggi table.
        if ($type == ADA_MSG_CHAT) {
            // add the message to the spool
            $res = $spool->addMessage($message_ha);
            if (AMADataHandler::isError($res)) {
                return $res;
                // return new AMAError(AMA_ERR_SEND_MSG);
            } // logger("Message successfully sent", 2);
            return $res;
            // end public chat message
        } else {
            // all other cases
            // we have to verify that the field $recipients it is not empty
            if (!isset($recipients_ids_ar)) {
                $retval = new AMAError(AMA_ERR_SEND_MSG);
                return $retval;
            }
            // add the message to the spool
            // e se non è inizializzato?
            $res = $spool->addMessage($message_ha, $recipients_ids_ar);

            if (AMADataHandler::isError($res)) {
                //             echo "spooler error (type $type)";
                return $res;
                //return new AMAError(AMA_ERR_SEND_MSG);
            } // logger("Message successfully sent", 2);
            return $res;
        }
    }

    /**
     * get all messages from the spool of a given user
     * it is possible to specify the list of fields
     * if not specified, all the fields are returned
     * only those messages not deleted are returned
     *
     * @access public
     *
     * @param   $user_id     - id of the user of the spool
     * @param   $type        - type of the spool
     * @param   $fields_list - list of fields to get  (as an array, or "" or "*")
     * @param   $ordering    - ordering clause (without where)
     *
     * @return  array|AMAError a list of messages as an array of hashes
     *          keys are among these possible values:
     *          id_messaggio, data_ora, tipo, titolo, id_mittente, priorita, testo
     *          an AMAError object if something goes wrong
     *
     **/
    public function &getMessages($user_id, $type, $fields_list = "", $ordering = "")
    {

        /* logger("entered MessageHandler::get_messages - ".
       "[user_id=$user_id, type=$type, fields_list=".serialize($fields_list).
       ", ordering=$ordering]", 2);
       */

        switch ($type) {
            case ADA_MSG_SIMPLE:
                // select the appropriate spool
                $spool = new SimpleSpool($user_id, $this->dsn);

                break;

            case ADA_MSG_AGENDA:
                // select the appropriate spool
                $spool = new AgendaSpool($user_id, $this->dsn);
                break;

                // Modified by Stamatios Filippis. 19/01/2005
            case ADA_MSG_CHAT:
            case ADA_MSG_PRV_CHAT:
                // select the appropriate spool
                $spool = new ChatSpool($user_id, $type, null, $this->dsn);
                break;
        } // end of switch

        // calling the appropriate spool
        $res_ar_ha = $spool->findMessages($fields_list, "", $ordering);
        if (AMADataHandler::isError($res_ar_ha)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        return $res_ar_ha;
    }

    /**
     * get all messages from the spool sent by a given user
     * it is possible to specify the list of fields
     * if not specified, all the fields are returned
     * only those messages not deleted are returned
     *
     * @access public
     *
     * @param   $user_id     - id of the user of the spool
     * @param   $type        - type of the spool
     * @param   $fields_list - list of fields to get  (as an array, or "" or "*")
     * @param   $ordering    - ordering clause (without where)
     *
     * @return  array|AMAError a list of messages as an array of hashes
     * }          keys are among these possible values:
     *           id_messaggio, data_ora, tipo, titolo, id_mittente, priorita, testo
     *          an AMAError object if something goes wrong
     *
     **/
    public function &getSentMessages($user_id, $type, $fields_list = "", $ordering = "")
    {

        /* logger("entered MessageHandler::get_messages - ".
       "[user_id=$user_id, type=$type, fields_list=".serialize($fields_list).
       ", ordering=$ordering]", 2);
       */

        switch ($type) {
            case ADA_MSG_SIMPLE:
                // select the appropriate spool
                $spool = new SimpleSpool($user_id, $this->dsn);

                break;


            case ADA_MSG_AGENDA:
                // select the appropriate spool
                $spool = new AgendaSpool($user_id, $this->dsn);

                break;


            case ADA_MSG_CHAT:
                // select the appropriate spool
                $spool = new ChatSpool($user_id, $type, null, $this->dsn);

                break;
        } // end of switch

        $res_ar_ha = $spool->findSentMessages($fields_list, "", $ordering);
        if (AMADataHandler::isError($res_ar_ha)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        return $res_ar_ha;
    }
    /**
     * get all the messages verifying the given clause
     * the list of fields specified is returned
     * records are sorted by the given order
     * only messages marked as non deleted are retrieved
     *
     * @access  public
     *
     * @param   $user_id        - user of the spool
     * @param   $type           - type of spool
     * @param   $fields_list_ar - a list of fields to return
     * @param   $clause         - a clause to filter records
     *                            (records are always filtered for user and type)
     * @param   $ordering       - the order
     *
     * @return  array a reference to a 2-dim array,
     *           each row will have id_utente in the 0 element
     *           and the fields specified in the list in the others
     *          an AMAError object if something goes wrong
     *
     **/
    public function &findMessages(
        $user_id,
        $type,
        $fields_list = "",
        $clause = "",
        $ordering = ""
    ) {

        /* logger("entered MessageHandler::find_messages - ".
       "[user_id=$user_id, type=$type, fields_list=".serialize($fields_list).
       ", clause=$clause, ordering=$ordering]", 2);
       */

        switch ($type) {
            case ADA_MSG_SIMPLE:
                // select the appropriate spool
                $spool = new SimpleSpool($user_id, $this->dsn);

                break;


            case ADA_MSG_AGENDA:
                // select the appropriate spool
                $spool = new AgendaSpool($user_id, $this->dsn);

                break;

                // Modified by Stamatios Filippis. 25/01/2005
            case ADA_MSG_PRV_CHAT:
            case ADA_MSG_CHAT:
                // select the appropriate spool
                $spool = new ChatSpool($user_id, $type, null, $this->dsn);

                break;
        } // end of switch

        // calling the appropriate spool
        $res_ar_ha = $spool->findMessages($fields_list, $clause, $ordering);
        if (AMADataHandler::isError($res_ar_ha)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        return $res_ar_ha;
    }

    public function &findChatMessages($user_id, $type, $id_chatroom, $fields_list = "", $clause = "", $ordering = "")
    {

        $spool = new ChatSpool($user_id, $type, $id_chatroom, $this->dsn);
        // calling the appropriate spool
        $res_ar_ha = $spool->findChatMessages($fields_list, $clause, $ordering);
        if (AMADataHandler::isError($res_ar_ha)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        return $res_ar_ha;
    }

    /**
     * get all data for a given message (only called while reading a message)
     *
     * @access public
     *
     * @param   $user_id - id of the user of the spool
     * @param   $msg_id  - id of the message
     *
     * @return  an hash with keys:
     *           id_messaggio,
     *           data_ora,
     *           tipo,
     *           titolo,
     *           mittente*,
     *           destinatari**,
     *           priorita,
     *           testo
     *
     *           *mittente is a username
     *           **destinatari is a CVS list of usernames
     *
     **/
    public function getMessage($user_id, $msg_id)
    {

        // logger("entered MessageHandler::get_message - ".
        //       "[user_id = $user_id, msg_id=$msg_id]", 3);

        // create generic instance
        $spool = new Spool($user_id, $this->dsn);

        // get message content and recipients' ids list
        $res_ar = $spool->getMessageInfo($msg_id);

        //vito, 2 feb 2009: qui potrebbe non aver trovato il messaggio


        if (AMADataHandler::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        [$message_ha, $recipients_ids_ar] = $res_ar;

        // create instance of UserDataHandler
        //vito, 26 settembre 2008
        //$udh = new UserDataHandler();
        $udh = UserDataHandler::instance($this->dsn);

        // transform sender id into sender username
        $sender_id = $message_ha['id_mittente'];
        $res_ar = $udh->findUsersList(
            ["username"],
            "id_utente=$sender_id"
        );
        if (AMADataHandler::isError($res_ar)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        $sender_username = $res_ar[0][1];

        // transform recipients' ids array into usernames array
        $recipients_usernames_ar = [];
        foreach ($recipients_ids_ar as $rid) {
            // get username of the current id ($rid)
            $res_ar = $udh->findUsersList(
                ["username"],
                "id_utente=$rid"
            );
            if (AMADataHandler::isError($res_ar)) {
                $retval = new AMAError(AMA_ERR_READ_MSG);
                return $retval;
            }
            if (array_key_exists(0, $res_ar)) {
                $recipients_usernames_ar[] = $res_ar[0][1];
            }
        }

        // create CSV list starting from array
        $recipients_usernames = implode(",", $recipients_usernames_ar);

        // adapt message hash
        $message_ha['mittente'] = $sender_username;
        $message_ha['destinatari'] = $recipients_usernames;

        // set message as read
        $res = $spool->setMessage($msg_id, "read", 'R');
        if (AMADataHandler::isError($res)) {
            $retval = new AMAError(AMA_ERR_UPDATE);
            return $retval;
        }

        // return values
        return $message_ha;
    }

    /**
     * get all data for the message which comes the given message
     * the sorting order is by date, newst messages come first
     * $user and type select the spool in the tabel 'destinatari_messaggi'
     *
     * @access public
     *
     * @param   $msg_id  - id of the message
     * @param   $user_id - user
     * @param   $type    - type of message
     *
     * @return  an hash having the same structure of what get_message returns
     *          an AMAError object if something goes wrong
     *
     **/
    public function getNextMessage($msg_id, $user_id, $type)
    {

        // find current message's index
        $res = $this->getIdsList($user_id, $type, $msg_id);
        if (AMADataHandler::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
        [$current, $msgs_ar] = $res;

        // return content of next message
        return (new Spool($user_id, $this->dsn))->getMessageInfo($msgs_ar[$current + 1]);
    }

    /**
     * get all data for the message which comes before the given message
     * the sorting order is by date, newst messages come first
     * $user and type select the spool in the tabel 'destinatari_messaggi'
     *
     * @access public
     *
     * @param   $msg_id  - id of the message
     * @param   $user_id - user
     * @param   $type    - type of message
     *
     * @return  an hash having the same structure of what get_message returns
     *          an AMAError object if something goes wrong
     *
     **/
    public function getPreviousMessage($id, $user_id, $type)
    {

        // find current message's index
        $res = $this->getIdsList($user_id, $type, $msg_id ?? 0);
        if (AMADataHandler::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
        [$current, $msgs_ar] = $res;

        // return content of next message
        return (new Spool($user_id, $this->dsn))->getMessageInfo($msgs_ar[$current + 1]);
    }

    /**
     * get all data for the first message in the user's spool
     * the sorting order is by date, newst messages come first
     * $user and type select the spool in the tabel 'destinatari_messaggi'
     *
     * @access public
     *
     * @param   $msg_id  - id of the message
     * @param   $user_id - user
     * @param   $type    - type of message
     *
     * @return  an hash having the same structure of what get_message returns
     *          an AMAError object if something goes wrong
     *
     **/
    public function getFirstMessage($user_id, $type)
    {

        // find current message's index
        $res = $this->getIdsList($user_id, $type);
        if (AMADataHandler::isError($res)) {
            //$res is an AMAError object
            return $res;
        }
        $msgs_ar = $res;

        // return content of next message
        return (new Spool($user_id, $this->dsn))->getMessageInfo($msgs_ar[0]);
    }

    /**
     * get all data for the last message in the user's spool
     * the sorting order is by date, newst messages come first
     * $user and type select the spool in the tabel 'destinatari_messaggi'
     *
     * @access public
     *
     * @param   $msg_id  - id of the message
     * @param   $user_id - user
     * @param   $type    - type of message
     *
     * @return  an hash having the same structure of what get_message returns
     *          an AMAError object if something goes wrong
     *
     **/
    public function getLastMessage($user_id, $type)
    {

        // find current message's index
        $res = $this->getIdsList($user_id, $type);
        if (AMADataHandler::isError($res)) {
            // $res is an AMAError object
            return $res;
        }
        $msgs_ar = $res;
        $n = count($msgs_ar);

        // return content of next message
        return (new Spool($user_id, $this->dsn))->getMessageInfo($msgs_ar[$n - 1]);
    }

    /**
     * set a series of messages as read or non read
     *
     * @access public
     *
     * @param   $user_id - id of the owner of the spool
     * @param   $msgs_ar - array of messages id to change
     * @param   $value   - new status (R or N)
     *
     * @return  bool|AMAError an AMAError object if something goes wrong
     *
     **/
    public function setMessages($user_id, $msgs_ar, $value)
    {

        // logger("entered MessageHandler::set_messages - ".
        //       "[user_id=$user_id, msgs_ar=".serialize($msgs_ar).", value=$value]", 3);

        $spool = new Spool($user_id, $this->dsn);

        // only do something if there is something to do!
        if (count($msgs_ar)) {
            $res = $spool->setMessages($msgs_ar, $value);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
            }
        }
        return $retval ?? true;
    }

    /**
     * remove a series of messages from the spool
     *
     * @access public
     *
     * @param   $user_id - id of the owner of the spool
     * @param   $msgs_ar - array of messages id to change
     *
     * @return  bool|AMAError an AMAError object if something goes wrong
     *
     **/
    public function removeMessages($user_id, $msgs_ar)
    {

        // logger("entered MessageHandler::remove_messages - ".
        //       "[user_id=$user_id, msgs_ar=".serialize($msgs_ar)."]", 3);

        // vito, 4 feb 2009
        //$spool = new Spool($user_id);
        $spool = new SimpleSpool($user_id, $this->dsn);
        if (count($msgs_ar)) {
            $res = $spool->removeMessages($msgs_ar);
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_REMOVE);
            }
        }
        return $retval ?? true;
    }


    /**
     * get the index of the current messages
     * and the array of messages ids
     * sorted by descending date
     * useful in the get_next or get_previous public methods
     *
     * @access private
     *
     * @param   $msg_id  - id of the message
     * @param   $user_id - user
     * @param   $type    - type of message
     *
     * @return array|AMAError an array, comprising:
     *          an integer representing the index
     *          the messages' ids array
     *         only the array if the msg_id is not passed
     *         an AMAError object if something goes wrong
     *
     **/
    private function getIdsList($user_id, $type, $msg_id = 0)
    {

        /* logger("entered MessageHandler::_get_current_index - ".
       "[user_id=$user_id, type=$type, msg_id=$msg_id]", 3);
       */
        // instantiate spool object according to type
        switch ($type) {
            case ADA_MSG_SIMPLE:
                // select the appropriate spool
                $spool = new SimpleSpool($user_id, $this->dsn);

                break;


            case ADA_MSG_AGENDA:
                // select the appropriate spool
                $spool = new AgendaSpool($user_id, $this->dsn);

                break;


            case ADA_MSG_CHAT:
                // select the appropriate spool
                $spool = new ChatSpool($user_id, null, null, $this->dsn);

                break;
        } // end of switch

        $res_ar = $spool->findMessages();
        if (AMADataHandler::isError($res_ar)) {
            // $res_ar is an AMAError object
            return $res_ar;
        }

        if ($msg_id != 0) {
            // find the index of the current id
            $current = array_search($msg_id, $res_ar, true);

            return [$current, $res_ar];
        } else {
            return $res_ar;
        }
    }


    /**
     * log  messages to DB
     *
     * @access public
     *
     * @param   $user_id - id of the owner of the spool
     * @param   $msgs_ar - array of messages id to log
     *
     * @return  bool|AMAError an AMAError object if something goes wrong
     *
     **/
    public function logMessages($user_id, $msgs_ar)
    {

        // logger("entered MessageHandler::log_messages - ".
        //       "[user_id=$user_id, msgs_ar=".serialize($msgs_ar)."]", 3);


        $spool = new Spool($user_id, $this->dsn);
        if (count($msgs_ar)) {
            foreach ($msgs_ar as $message_id) {
                $msg_Ha = $spool->getMessageInfo($message_id);
                $res = $spool->logMessage($msg_Ha, []);
            }
            // FIXME: qui gestione errore non e' a posto.
            if (AMADataHandler::isError($res)) {
                $retval = new AMAError(AMA_ERR_ADD);
            }
        }
        return $retval ?? true;
    }

    /**
     * get all messages from the spool sent by a given user and logged
     * it is possible to specify the list of fields
     * if not specified, all the fields are returned
     * ALL  messages not deleted are returned
     *
     * @access public
     *
     * @param   $user_id     - id of the user of the spool
     * @param   $type        - type of the spool
     * @param   $fields_list - list of fields to get  (as an array, or "" or "*")
     * @param   $ordering    - ordering clause (without where)
     *
     * @return  a list of messages as an array of hashes
     * }          keys are among these possible values:
     *           id_messaggio, data_ora, tipo, titolo, id_mittente, priorita, testo
     *          an AMAError object if something goes wrong
     *
     **/
    public function &getLoggedMessages($user_id, $type, $fields_list = "", $ordering = "")
    {

        /* logger("entered MessageHandler::get_messages - ".
       "[user_id=$user_id, type=$type, fields_list=".serialize($fields_list).
       ", ordering=$ordering]", 2);
       */

        // select the appropriate spool
        switch ($type) {
            case ADA_MSG_SIMPLE:
                $spool = new SimpleSpool($user_id, $this->dsn);
                break;
            case ADA_MSG_AGENDA:
                $spool = new AgendaSpool($user_id, $this->dsn);
                break;
            case ADA_MSG_CHAT:
                $spool = new ChatSpool($user_id, null, null, $this->dsn);
                break;
        } // end of switch

        $res_ar_ha = $spool->findLoggedMessages($fields_list, "", $ordering);
        if (AMADataHandler::isError($res_ar_ha)) {
            $retval = new AMAError(AMA_ERR_READ_MSG);
            return $retval;
        }

        return $res_ar_ha;
    }

    /**
     * render ADA-coded links in message as HTML links
     *
     * @access public
     *
     * @param   $string - text of message
     *
     * @return  html text or nothing if no text is passed
     *
     **/

    public function renderMessageTextFN($string)
    {
        $sess_id_course = $GLOBALS['sess_id_course'];
        $user_level = $GLOBALS['user_level'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        if (!empty($string)) {
            $parsing_text[] = $string;
            $unparsed_text = end($parsing_text);
            $link = "<LINK TYPE=INTERNAL VALUE=\"([0-9]{1,4})\">";
            $regs = [];
            $is_linked = preg_match('/' . $link . '/i', $unparsed_text, $regs);
            while ($is_linked) {
                $id_link = $regs[1];
                if (!empty($id_link)) {
                    $linked_node_id = $sess_id_course . "_" . $id_link;
                    $nodeObj = new Node($linked_node_id);
                    if ($nodeObj->full == 1) {
                        $linked_node_level = $nodeObj->level;
                        $name = $nodeObj->name;
                        $link = "<LINK TYPE=INTERNAL VALUE=\"$id_link\">";
                        if ($linked_node_level <= $user_level) {
                            //  $exploded_link = "<a href=\"$http_root_dir/browsing/view.php?id_node=".$linked_node_id."\" target=\"_parent\" ><img src=\"img/_linka.png\" border=\"0\" alt=\"$name\"></a>";
                            $url =  "$http_root_dir/browsing/view.php?id_node=" . $linked_node_id;
                            $exploded_link = "<a href=# onclick=parentLoc('$url');><img src=\"img/_linka.png\" border=\"0\" alt=\"$name\"></a>";
                            $parsing_text[] = preg_replace('/' . $link . '/i', $exploded_link, $unparsed_text);
                        } else {
                            $exploded_link = "<img src=\"img/_linkdis.png\" border=\"0\" alt=\"$name\">";
                            $parsing_text[] = preg_replace('/' . $link . '/i', $exploded_link, $unparsed_text);
                        }
                    } else {
                        $link = "<LINK TYPE=INTERNAL VALUE=\"$id_link\">";
                        $exploded_link = "<img src=\"img/_linkdis.png\" border=\"0\" alt=\"$id_link\">";
                        $parsing_text[] = preg_replace('/' . $link . '/i', $exploded_link, $unparsed_text);
                    }
                }
                $unparsed_text = end($parsing_text);
                $link = "<LINK TYPE=INTERNAL VALUE=\"([0-9]{1,4})\">";
                $is_linked = preg_match('/' . $link . '/i', $unparsed_text, $regs);
            }
            return  end($parsing_text);
        } else {
            return "";
        }
    }
}

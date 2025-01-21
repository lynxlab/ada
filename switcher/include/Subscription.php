<?php

/**
 * Subscription class
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of Subscription
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Switcher;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Subscription
{
    /*
     * Andranno in Subscription_Mapper
     */

    /**
     * Given a classroom identifier, retrieves all the presubscriptions to the
     * classroom, if any.
     *
     * @param integer $classRoomId
     * @return array an array of Subscription objects
     */
    public static function findPresubscriptionsToClassRoom($classRoomId)
    {
        $dh = $GLOBALS['dh'];
        $result = $dh->getPresubscribedStudentsForCourseInstance($classRoomId);

        if (AMADataHandler::isError($result)) {
            return [];
        } else {
            $subscriptionsAr = [];

            foreach ($result as $r) {
                $subscription = new Subscription($r['id_utente'], $classRoomId, $r['data_iscrizione']);
                $subscription->setSubscriberFullname($r['nome'] . ' ' . $r['cognome']);
                $subscription->setSubscriptionStatus($r['status']);
                $subscription->setLastStatusUpdate($r['laststatusupdate']);
                $subscription->loadedStatus = $subscription->getSubscriptionStatus();
                if (defined('MODULES_CODEMAN') && (MODULES_CODEMAN)) {
                    $subscription->setSubscriptionCode($r['codice']);
                }
                $subscriptionsAr[] = $subscription;
            }

            return $subscriptionsAr;
        }
    }

    /**
     * Given a classroom identifier, retrieves all the presubscriptions to the
     * classroom, if any.
     *
     * @param integer $classRoomId
     * @return array an array of Subscriptions
     */
    public static function findSubscriptionsToClassRoom($classRoomId, $all = false)
    {
        $dh = $GLOBALS['dh'];
        $result = $dh->getStudentsForCourseInstance($classRoomId, $all);

        if (AMADataHandler::isError($result)) {
            return [];
        } else {
            $subscriptionsAr = [];

            foreach ($result as $r) {
                $subscription = new Subscription($r['id_utente'], $classRoomId, $r['data_iscrizione']);
                $subscription->setSubscriberFullname($r['nome'] . ' ' . $r['cognome']);
                $subscription->setSubscriberFirstname($r['nome']);
                $subscription->setSubscriberLastname($r['cognome']);
                $subscription->setSubscriberEmail($r['e_mail']);
                $subscription->setSubscriptionStatus($r['status']);
                $subscription->setLastStatusUpdate($r['laststatusupdate']);
                $subscription->loadedStatus = $subscription->getSubscriptionStatus();
                if (defined('MODULES_CODEMAN') && (MODULES_CODEMAN)) {
                    $subscription->setSubscriptionCode($r['codice']);
                }
                $subscriptionsAr[] = $subscription;
            }

            return $subscriptionsAr;
        }
    }

    public static function addSubscription(Subscription $s)
    {
        $dh = $GLOBALS['dh'];
        if ($s->getSubscriptionStatus() == ADA_STATUS_SUBSCRIBED) {
            $result = $dh->courseInstanceStudentPresubscribeAdd(
                $s->getClassRoomId(),
                $s->getSubscriberId(),
                $s->getStartStudentLevel()
            );

            if (!AMADataHandler::isError($result)) {
                $result = $dh->courseInstanceStudentSubscribe(
                    $s->getClassRoomId(),
                    $s->getSubscriberId(),
                    ADA_STATUS_SUBSCRIBED,
                    $s->getStartStudentLevel(),
                    $s->getLastStatusUpdate()
                );
            }

            if (AMADataHandler::isError($result)) {
                //print_r($result);
            }
        } else {
            //echo 'sono qui';
        }
    }
    public static function updateSubscription(Subscription $s)
    {
        $dh = $GLOBALS['dh'];
        if ($s->getSubscriptionStatus() == ADA_STATUS_REMOVED) {
            $result = $dh->courseInstanceStudentPresubscribeRemove(
                $s->getClassRoomId(),
                $s->getSubscriberId()
            );
        } else {
            $result = $dh->courseInstanceStudentSubscribe(
                $s->getClassRoomId(),
                $s->getSubscriberId(),
                $s->getSubscriptionStatus(),
                $s->getStartStudentLevel(),
                $s->getLastStatusUpdate()
            );
        }
        if (AMADataHandler::isError($result)) {
        }

        return $result;
    }
    public static function deleteSubscription(Subscription $s)
    {
    }

    public static function deleteAllSubscriptionsToClassRoom($classRoomId)
    {
        $dh = $GLOBALS['dh'];
        $result = $dh->courseInstanceStudentsSubscriptionsRemoveAll($classRoomId);
        if (AMADataHandler::isError($result)) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param integer $userId the id of the subscribing user
     * @param integer $classRoomId the id of the classroom
     * @param integer $subscriptionDate the timestamp of the subscription
     */
    public function __construct($userId, $classRoomId, $subscriptionDate = 0, $startStudentLevel = 1)
    {
        $this->subscriberId = $userId;
        $this->classRoomId = $classRoomId;
        $this->startStudentLevel = $startStudentLevel;

        if ($subscriptionDate == 0) {
            $this->subscriptionDate = time();
        } else {
            $this->subscriptionDate = $subscriptionDate;
        }

        $this->subscriberFullname = '';
        $this->subscriberUsername = '';
        $this->subscriptionStatus = ADA_STATUS_PRESUBSCRIBED;
    }
    /**
     *
     * @return integer the StartStudentLevel of the subscriber
     */
    public function getStartStudentLevel()
    {
        return $this->startStudentLevel;
    }

    /**
     *
     * @return integer the id of the subscriber
     */
    public function getSubscriberId()
    {
        return $this->subscriberId;
    }
    /**
     *
     * @return integer the id of the classroom
     */
    public function getClassRoomId()
    {
        return $this->classRoomId;
    }
    /**
     *
     * @return string the fullname of the subscriber
     */
    public function getSubscriberFullname()
    {
        return $this->subscriberFullname;
    }
    /**
     *
     * @return string the firstname of the subscriber
     */
    public function getSubscriberFirstname()
    {
        return $this->subscriberFirstname;
    }
    /**
     *
     * @return string the lastname of the subscriber
     */
    public function getSubscriberLastname()
    {
        return $this->subscriberLastname;
    }
    /**
     *
     * @return string the email of the subscriber
     */
    public function getSubscriberEmail()
    {
        return $this->subscriberEmail;
    }
    /**
     *
     * @return string a string representation of the subscription date
     */
    public function getSubscriptionDate()
    {
        return (string) $this->subscriptionDate;
    }
    /**
     *
     * @return string the subscription status as string
     */
    public function getSubscriptionStatus()
    {
        return $this->subscriptionStatus;
    }
    /**
     *
     * @return string the subscription code as string
     */
    public function getSubscriptionCode()
    {
        return $this->subscriptionCode;
    }

    /**
     * @return number last status update timestamp
     */
    public function getLastStatusUpdate()
    {
        return $this->lastStatusUpdate;
    }

    public function setSubscriberFullname($fullname)
    {
        $this->subscriberFullname = $fullname;
    }
    public function setSubscriberFirstname($firstname)
    {
        $this->subscriberFirstname = $firstname;
    }
    public function setSubscriberLastname($lastname)
    {
        $this->subscriberLastname = $lastname;
    }
    public function setSubscriberEmail($email)
    {
        $this->subscriberEmail = $email;
    }
    public function setSubscriptionStatus($status)
    {
        $this->subscriptionStatus = $status;
        if ($this->loadedStatus != $status) {
            $this->setLastStatusUpdate(time());
        }
    }
    public function setStartStudentLevel($startStudentLevel)
    {
        $this->startStudentLevel = $startStudentLevel;
    }
    public function setSubscriptionCode($code)
    {
        $this->subscriptionCode = $code;
    }
    public function setLastStatusUpdate($timestamp)
    {
        $this->lastStatusUpdate = $timestamp;
    }

    public function subscriptionStatusAsString()
    {
        return self::subscriptionStatusArray()[$this->subscriptionStatus];
    }

    public static function subscriptionStatusArray()
    {
        return [
            ADA_STATUS_REGISTERED => translateFN('Registrato'),
            ADA_STATUS_PRESUBSCRIBED => translateFN('Preiscritto'),
            ADA_STATUS_SUBSCRIBED => translateFN('Iscritto'),
            ADA_STATUS_REMOVED => translateFN('Rimosso'),
            ADA_STATUS_VISITOR => translateFN('In visita'),
            ADA_STATUS_COMPLETED => translateFN('Completato'),
            ADA_STATUS_TERMINATED => translateFN('Terminato'),
        ];
    }

    private $subscriberId;
    private $subscriberFullname;
    private $subscriberFirstname;
    private $subscriberLastname;
    private $subscriberEmail;
    private $classRoomId;
    private $subscriptionDate;
    private $subscriptionStatus;
    private $subscriptionCode;
    private $lastStatusUpdate;
    private $loadedStatus;
    private $startStudentLevel;
    private $subscriberUsername;
}

<?php

/**
 * Browsing functions
 *
 * @package
 * @copyright   Copyright (c) 2009-2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.2
 */

namespace Lynxlab\ADA\Main\Helper;

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ViewBaseHelper;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\Badge;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Browsing helper class
 */
class BrowsingHelper extends ViewBaseHelper
{
    protected static $tutor_uname = null;

    /**
     * Builds array keys for the browsing directory scripts
     *
     * @param array $neededObjAr
     *
     * @return array
     */
    public static function init(array $neededObjAr = [])
    {
        if (count(self::$helperData) === 0) {
            self::$helperData = parent::init($neededObjAr);
            self::setSessionMode();
            self::$helperData = array_merge(
                self::$helperData,
                self::getEnabledArray(self::$helperData['userObj'], self::$helperData['courseObj'] ?? null)
            );
            self::$helperData = array_merge(
                self::$helperData,
                self::getUserBrowsingData(self::$helperData['userObj'], self::$helperData['log_enabled']),
                [
                    'user_messages' => self::getUserMessages(self::$helperData['userObj']),
                    'user_agenda' => self::getUserAgenda(self::$helperData['userObj']),
                    'user_events' => self::getUserEvents(self::$helperData['userObj']),
                ]
            );
            self::extract();
        }
        return self::getHelperData();
    }

    /**
     * set in which way video LIS (or other) is showed
     * mode = LIS
     * mode = changeLIS change the LIS status
     * mode = ...
     *
     * @param array|null $dataArr if null defaults to $_REQUEST
     * @return void
     */
    private static function setSessionMode($dataArr = null)
    {
        if (!is_array($dataArr) || is_null($dataArr)) {
            $dataArr = $_REQUEST;
        }
        if (isset($dataArr['mode']) && strlen($dataArr['mode']) > 0) {
            if ($dataArr['mode'] == 'changeLIS') {
                if ($_SESSION['mode'] == 'LIS') {
                    unset($_SESSION['mode']);
                } else {
                    $_SESSION['mode'] = 'LIS';
                }
            } else {
                $_SESSION['mode'] = $dataArr['mode'];
            }
        } else { //when unset the mode session??
            //$status = $_REQUEST['status'];
        }
    }

    /**
     * Builds the reg_enabled, log_enabled, mod_enabled and com_enabled keys
     * for the browsing directory scripts
     *
     * @param \Lynxlab\ADA\Main\User\ADAAbstractUser $userObj
     * @param Course $courseObj
     *
     * @return array
     */
    protected static function getEnabledArray(ADAGenericUser $userObj = null, Course $courseObj = null)
    {
        /**
         * import globals set from module_init
         */
        global $sess_id_course_instance;
        global $sess_id_user;

        $reg_enabled = true; // links to bookmarks enabled
        $log_enabled = true; // links to history enabled
        $mod_enabled = true; // links to modify nodes  enabled
        $com_enabled = true; // links to comunicate among users  enabled

        /**
         * CONTROLLARE DA DOVE VIENE $sess_id_course_instance
         */
        if (method_exists($userObj, 'getStudentStatus')) {
            $user_status = $userObj->getStudentStatus($sess_id_user, $sess_id_course_instance);
        } else {
            $user_status = ADA_STATUS_VISITOR;
        }

        if ($userObj->getType() == AMA_TYPE_STUDENT && ($user_status == ADA_STATUS_VISITOR || $user_status == ADA_STATUS_TERMINATED || $user_status == ADA_STATUS_COMPLETED)) {
            $reg_enabled = false; // links to bookmarks disabled
            $log_enabled = ($user_status != ADA_STATUS_VISITOR); // links to history disabled
            $mod_enabled = false; // links to modify nodes  disabled
            $com_enabled = false;  // links to comunicate among users  disabled
        }

        /**
         * Authors can edit public courses assigned to themselves
         */
        if ($userObj->getType() == AMA_TYPE_AUTHOR && $courseObj instanceof Course && $courseObj->getIsPublic()) {
            $mod_enabled = ($userObj->getId() == $courseObj->getAuthorId());
        }

        if ($com_enabled) {
            self::sendWelcomeMessage($userObj, $sess_id_course_instance);
        }

        return ['reg_enabled' => $reg_enabled, 'log_enabled' => $log_enabled, 'mod_enabled' => $mod_enabled, 'com_enabled' => $com_enabled];
    }

    /**
     * Uses the SERVICECOMPLETE module to check if the passed user has completed the passed instance
     * using the condition set linked to the passed course.
     * Possibly sets the user status to ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED in the passed instance
     *
     * @param ADAGenericUser $userObj
     * @param int $courseId
     * @param int $courseInstanceId
     *
     * @return int
     */
    public static function checkServiceComplete(ADAGenericUser $userObj, $courseId = null, $courseInstanceId = null)
    {
        if ($userObj->getType() == AMA_TYPE_STUDENT && ModuleLoaderHelper::isLoaded('SERVICECOMPLETE')) {
            if (intval($courseInstanceId) > 0 && intval($courseId) > 0 && isset($userObj) && is_object($userObj)) {
                $user_status = self::getUserBrowsingData($userObj)['user_status'];
                if ($user_status != ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED) {
                    $mydh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                    // load the conditionset for this course
                    $conditionSet = $mydh->getLinkedConditionsetForCourse($courseId);
                    $mydh->disconnect();

                    if ($conditionSet instanceof CompleteConditionSet) {
                        // evaluate the conditionset for this instance ID and course ID
                        $is_course_instance_complete = $conditionSet->evaluateSet([$courseInstanceId, $userObj->getId()]);
                    } else {
                        $is_course_instance_complete = false;
                    }

                    // if course is complete, save this information to the db
                    if ($is_course_instance_complete) {
                        $s = new Subscription($userObj->getId(), $courseInstanceId);
                        $s->setSubscriptionStatus(ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED);
                        /**
                         * Following lines are messing up user level if some code
                         * updates it in the db and not in the object.
                         * (e.g. \Lynxlab\ADA\Module\Test\TestTest::saveTest method).
                         * They're commented because it's not sure if user 'livello'
                         * is ever set by some code not in
                         * ADAAbstractUser::getStudentLevel method.
                         * Should someone every notice some weirdness in userObj->livello
                         * please decomment them and comment the setStartStudentLevel call below.
                         */
                        // if (isset($userObj->livello) && intval($userObj->livello) > 0) {
                        //     $s->setStartStudentLevel($userObj->livello);
                        // } else {
                        //     $s->setStartStudentLevel(null); // null means no level update
                        // }
                        $s->setStartStudentLevel(null);
                        $subscribedCount = Subscription::updateSubscription($s);
                        $user_status = ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED;
                    }
                }
                return $user_status;
            }
        }
        return -1; // status not available
    }


    /**
     * Uses the BADGES module to check if the passed user has to be rewarded
     * with some badges in the passed course
     *
     * @param ADAGenericUser $userObj
     * @param int $courseId
     * @param int $courseInstanceId
     *
     * @return void
     */
    public static function checkRewardedBadges(ADAGenericUser $userObj, $courseId = null, $courseInstanceId = null)
    {
        if ($userObj->getType() == AMA_TYPE_STUDENT && ModuleLoaderHelper::isLoaded('BADGES') && ModuleLoaderHelper::isLoaded('SERVICECOMPLETE')) {
            if (
                intval($courseInstanceId) > 0 && intval($courseId) > 0 && isset($userObj) && is_object($userObj)
            ) {
                // need the badges module data handler
                $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                $cdh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                // load the badges linked to this course
                $badgesList = $bdh->findBy('CourseBadge', ['id_corso' => $courseId]);
                if (!AMADB::isError($badgesList) && is_array($badgesList) && count($badgesList) > 0) {
                    /**
                     * @var \Lynxlab\ADA\Module\Badges\CourseBadges $cb
                     */
                    foreach ($badgesList as $cb) {
                        $badge = $bdh->findBy('Badge', ['uuid' => $cb->getBadgeUuid()]);
                        if (is_array($badge) && count($badge) == 1) {
                            $badge = reset($badge);
                            $cs = $cdh->getCompleteConditionSet($cb->getIdConditionset());
                            if ($badge instanceof Badge && $cs instanceof CompleteConditionSet) {
                                if ($cs->evaluateSet([$courseInstanceId, $userObj->getId()])) {
                                    // student is rewarded with the badge
                                    $bdh->saveRewardedBadge([
                                        'badge_uuid' => $badge->getUuid(),
                                        'approved' => 1,
                                        'id_utente' => $userObj->getId(),
                                        'id_corso' => $courseId,
                                        'id_istanza_corso' => $courseInstanceId,
                                    ]);
                                    // don't check for database errors, there's an index preventing badge reward duplication
                                    // therefore some kind of error could be generated on purpose
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Sends a welcome message to the passed user in the passed instance
     *
     * @param ADAGenericUser $userObj
     * @param int $courseInstanceId
     *
     * @return void
     */
    private static function sendWelcomeMessage(ADAGenericUser $userObj, $courseInstanceId)
    {
        global $sess_selected_tester, $sess_id_course;

        $last_access_date = $userObj->getLastAccessFN($courseInstanceId, 'T');
        if ($last_access_date == translateFN("Nessun'informazione")) {
            $user_name = $userObj->username;
            $destAr = [$user_name];
            $language = $_SESSION['sess_user_language'] ?? ADA_DEFAULT_LANGUAGE;
            // FIXME: multiportare, ora e' bloccato sul tester selezionato
            $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
            $message_ha['destinatari'] = $destAr;
            $message_ha['priorita'] = 1;
            $message_ha['data_ora'] = "now";
            $message_ha['titolo'] = translateFN("Messaggio di benvenuto");
            $welcome_file = "service_" . $sess_id_course . "_" . $courseInstanceId . "_welcome_$language.txt";
            // es. course_2_12_welcome_italiano.txt
            if (file_exists($welcome_file)) {
                $fp = fopen($welcome_file, 'r');
                $message_ha['testo'] = fread($fp, filesize($welcome_file));
                fclose($fp);
            } else {
                $message_ha['testo'] = translateFN("Benvenuto in ADA!");
                $message_ha['testo'] .= translateFN("Se hai problemi, dubbi o domande, puoi inviare un messaggio al tuo") . "<a href=\"" . HTTP_ROOT_DIR . "/comunica/send_message.php?destinatari=" . self::$tutor_uname . "\">" . translateFN("E-practitioner") . "</a>.";
            }
            $message_ha['data_ora'] = "now";
            $message_ha['mittente'] = self::$tutor_uname;
            // e-mail
            $message_ha['tipo'] = ADA_MSG_MAIL;
            $res = $mh->sendMessage($message_ha);
            // messaggio interno
            $message_ha['tipo'] = ADA_MSG_SIMPLE;
            //$res = $mh->sendMessage($message_ha);
            // reload messages to show this one !
            // $user_messages = $userObj->getMessagesFN($sess_id_user);
        }
    }

    /**
     * used by the menu to check if the session user is the author
     * of the session node, that must be a note
     *
     * @return boolean
     */
    public static function isSessionUserAuthorOfSessionNote()
    {
        if (isset($_SESSION['sess_id_node']) && isset($_SESSION['sess_id_user']) && isset($_SESSION['sess_id_user_type'])) {
            $node = new Node($_SESSION['sess_id_node']);
            if ($node instanceof Node) {
                return (in_array($node->type, [ADA_NOTE_TYPE, ADA_PRIVATE_NOTE_TYPE])
                    && (
                        $_SESSION['sess_id_user_type'] == AMA_TYPE_TUTOR ||
                        ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT
                            && $node->author['id'] == $_SESSION['sess_id_user'])
                    )
                );
            }
        }
        return false;
    }
}

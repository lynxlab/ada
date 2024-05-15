<?php

/**
 * @package     view
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Helper;

use Exception;
use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\HtmlLibrary\CommunicationModuleHtmlLib;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\GetCallingMethodName;

/**
 * View helper base class
 *
 * base class used to build global variables used by the scripts
 * in the browsing, switcher, tutor, services folders
 */
abstract class ViewBaseHelper
{
    private static $testers_dataAr = null;
    private static $dumpExtract = false;

    /**
     * Array to store all needed data
     * the key will be the name of the var to be extracted
     *
     * @var array
     */
    protected static $helperData = [];

    /**
     * Builds array keys common to all cases, such as:
     * status, userObj, id_profile, user_type, user_name,
     * reg_enabled, log_enabled, mod_enabled, com_enabled and layout_dataAr
     *
     * @param array $neededObjAr
     *
     * @return array
     */
    public static function init(array $neededObjAr = [])
    {
        if (count(self::$helperData) === 0) {
            $userObj = self::getUserObj();
            self::$helperData = array_merge(
                [],
                [
                    'status' => self::getStatus(),
                    'userObj' => $userObj,
                    'user_type' => $userObj->convertUserTypeFN($userObj->getType()),
                    'user_name' => $userObj->getFirstName(),
                    'id_profile' => $userObj->getType(),
                    'layout_dataAr' => [],
                ],
                self::getNeededObjects($userObj, $neededObjAr),
                self::getEnabledArray()
            );
            self::$helperData['template_family'] = self::setSessionTemplate(
                self::$helperData['userObj']->template_family,
                isset(self::$helperData['nodeObj']) ? self::$helperData['nodeObj']->template_family : null,
                isset(self::$helperData['courseInstanceObj']) ? self::$helperData['courseInstanceObj']->template_family : null,
                isset(self::$helperData['courseObj']) ? self::$helperData['courseObj']->template_family : null
            );
            self::$helperData['layout_dataAr']['family'] = self::$helperData['template_family'];
        }
        return self::getHelperData();
    }

    public static function get($name)
    {
        $arr = self::getHelperData();
        if (array_key_exists($name, $arr)) {
            return $arr[$name];
        } else {
            throw new Exception("$name non esiste");
        }
    }

    /**
     * Gets the array built by init
     *
     * @return array
     */
    public static function getHelperData()
    {
        return self::$helperData;
    }

    /**
     * Builds the reg_enabled, log_enabled, mod_enabled and com_enabled keys
     *
     * @param ADAGenericUser $userObj
     * @param Course $courseObj
     *
     * @return array
     */
    protected static function getEnabledArray(ADAGenericUser $userObj = null, Course $courseObj = null)
    {
        $reg_enabled = false; // links to bookmarks enabled
        $log_enabled = false; // links to history enabled
        $mod_enabled = false; // links to modify nodes  enabled
        $com_enabled = false; // links to comunicate among users  enabled

        return ['reg_enabled' => $reg_enabled, 'log_enabled' => $log_enabled, 'mod_enabled' => $mod_enabled, 'com_enabled' => $com_enabled];
    }

    /**
     * Gets the status message
     *
     * @param array|null $dataArr if null defaults to $_REQUEST
     * @param string $defaultStatus default text to be used for the status
     *
     * @return string
     */
    protected static function getStatus($dataArr = null, $defaultStatus = 'navigazione')
    {
        if (!is_array($dataArr) || is_null($dataArr)) {
            $dataArr = $_REQUEST;
        }
        if (!isset($dataArr['status'])) {
            if (isset($dataArr['msg'])) {
                return $dataArr['msg'];
                // $msg = $_REQUEST['msg'];
            } else {
                return translateFN($defaultStatus);
            }
        } else {
            return $dataArr['status'];
        }
    }

    /**
     * get User object, either from session or load it from the DB
     *
     * @return ADAGenericUser|null
     */
    protected static function getUserObj()
    {
        global $sess_id_user;

        if ($_SESSION['sess_userObj'] instanceof ADAGenericUser) {
            return $_SESSION['sess_userObj'];
        } else {
            /** @var ADAGenericUser $userObj */
            $userObj = DBRead::readUser($sess_id_user);
            if (ADAError::isError($userObj)) {
                $userObj->handleError();
                return null;
            } else {
                return $userObj;
            }
        }
    }

    /**
     * Builds user_level, user_score, user_history, user_status...
     *
     * @param \Lynxlab\ADA\Main\User\ADAAbstractUser $userObj
     * @param boolean $log_enabled
     * @return array
     */
    protected static function getUserBrowsingData(ADAGenericUser $userObj, $log_enabled = false)
    {
        global $sess_id_user, $sess_id_course_instance;

        switch ($userObj->getType()) {
            case AMA_TYPE_STUDENT:
                $user_level = "0";
                $user_score = "0";
                $user_history = "";
                $user_status = $userObj->getStudentStatus($sess_id_user, $sess_id_course_instance);
                break;
            case AMA_TYPE_TUTOR:
                $user_level = ADA_MAX_USER_LEVEL;
                $user_score = "";
                $user_history = "";
                $user_status = 0;
                break;
            case AMA_TYPE_SWITCHER:
                $user_level = ADA_MAX_USER_LEVEL;
                $user_score = "";
                $user_history = "";
                $user_status = ADA_STATUS_VISITOR;
                break;
            case AMA_TYPE_AUTHOR:
                $user_level = ADA_MAX_USER_LEVEL;
                $user_score = "";
                $user_history = "";
                $user_status = ADA_STATUS_VISITOR;
                break;
            case AMA_TYPE_ADMIN:
                $user_level = ADA_MAX_USER_LEVEL;
                $user_score = "";
                $user_history = "";
                $user_status = ADA_STATUS_VISITOR;
                break;
            default:
                $user_level = "0";
                $user_score = "0";
                $user_history = "";
                $user_status = AMA_TYPE_VISITOR;
                break;
        }

        if ($userObj->getType() == AMA_TYPE_STUDENT && $log_enabled) {
            $user_level = (string)$userObj->getStudentLevel($sess_id_user, $sess_id_course_instance);
            $user_score = (string)$userObj->getStudentScore($sess_id_user, $sess_id_course_instance);
            $user_history = $userObj->history;
        }

        return [
            'user_level' => $user_level,
            'user_score' => $user_score,
            'user_history' => $user_history,
            'user_status' => $user_status,
        ];
    }

    /**
     * Builds the array keys as requested by the neededObjAr
     *
     * @param \Lynxlab\ADA\Main\User\ADAAbstractUser $userObj
     * @param array $neededObjAr
     * @param string $user_status
     *
     * @return array
     */
    protected static function getNeededObjects(ADAGenericUser $userObj, array $neededObjAr = [])
    {
        global $sess_id_course, $sess_id_course_instance, $sess_selected_tester, $sess_id_user, $dh;

        if (is_array($neededObjAr) && array_key_exists($userObj->getType(), $neededObjAr) && is_array($neededObjAr[$userObj->getType()])) {
            $thisUserNeededObjAr = $neededObjAr[$userObj->getType()];
        } else {
            $thisUserNeededObjAr = [];
        }
        $retArr = [];

        if (in_array('course', $thisUserNeededObjAr)) {
            /**
             * @var \Lynxlab\ADA\Main\Course\Course $courseObj
             */
            $courseObj = DBRead::readCourse($sess_id_course);
            if (ADAError::isError($courseObj)) {
                $courseObj->handleError();
            } else {
                // $course_title = $courseObj->titolo; //title
                // $id_toc = $courseObj->id_nodo_toc;  //id_toc_node
                // $course_media_path = $courseObj->media_path;
                // $course_author_id = $courseObj->id_autore;
                // $course_family = $courseObj->template_family;
                // $course_static_mode = $courseObj->static_mode;
            }

            if (empty($courseObj->media_path)) {
                $media_path = MEDIA_PATH_DEFAULT . $courseObj->id_autore . "/";
            } else {
                $media_path = $courseObj->media_path;
            }
            $retArr['media_path'] = $media_path;
            $retArr['courseObj'] = $courseObj;
        }

        if (in_array('course_instance', $thisUserNeededObjAr)) {
            $retArr['courseInstanceObj'] = new CourseInstance(0);
            if (!ADAError::isError($courseObj) && !$courseObj->getIsPublic()) {
                if (in_array($userObj->getType(), [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER])) {
                    /**
                     *    @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
                     */
                    $courseInstanceObj = DBRead::readCourseInstanceFromDB($sess_id_course_instance);
                    if (ADAError::isError($courseInstanceObj)) {
                        $courseInstanceObj->handleError();
                    } else {
                        // $course_instance_family = $courseInstanceObj->template_family;
                        // $cistatus = $courseInstanceObj->status;
                        // if (($cistatus == ADA_COURSEINSTANCE_STATUS_PUBLIC)
                        //   and (($id_profile == AMA_TYPE_STUDENT) or ($id_profile == AMA_TYPE_GUEST))) {
                        //   $user_status = ADA_STATUS_VISITOR;
                        // }
                        $retArr['courseInstanceObj'] = $courseInstanceObj;
                    }
                }
            }
        }

        if (in_array('tutor', $thisUserNeededObjAr)) {
            global $sess_id_course_instance;
            $calledClass = static::class;

            if (isset($sess_id_course_instance)) {
                if (method_exists($userObj, 'getStudentStatus')) {
                    $user_status = $userObj->getStudentStatus($userObj->getId(), $sess_id_course_instance);
                } else {
                    $user_status = ADA_STATUS_VISITOR;
                }
                if ($user_status != ADA_STATUS_VISITOR) {
                    $tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);
                    if (!empty($tutor_id) && !AMADataHandler::isError($tutor_id)) {
                        $tutorAr = $dh->getTutor($tutor_id);
                        if (!AMADataHandler::isError($tutorAr)) {
                            if (isset($tutorAr['username'])) {
                                $calledClass::$tutor_uname = $tutorAr['username'];
                            }
                            $retArr['tutor_id'] = $tutor_id;
                        }
                    }
                }
            }
        }

        if (in_array('node', $thisUserNeededObjAr)) {
            global $id_node;
            /**
             * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
             * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
             */
            $nodeObj = DBRead::readNodeFromDB($id_node ?? null);
            if (ADAError::isError($nodeObj)) {
                $nodeObj->handleError();
            }
            $retArr['nodeObj'] = $nodeObj;
        }

        if (in_array('chatroom', $thisUserNeededObjAr)) {
            global $id_chatroom;
            global $id_room;

            if (isset($id_room) && intval($id_room) > 0) {
                $id_chatroom = $id_room;
            }
            /*
             * Check if the user has an appointment
             */
            $retArr['exit_reason'] = NO_EXIT_REASON;
            if (!isset($id_chatroom) && isset($_SESSION['sess_id_course_instance'])) {
                $id_chatroom = ChatRoom::getClassChatroomFN($_SESSION['sess_id_course_instance']);
                if (AMADataHandler::isError($id_chatroom)) {
                    $id_chatroom = 0;
                }
            }
            $retArr['chatroomObj'] = new ChatRoom($id_chatroom, $_SESSION['sess_selected_tester_dsn']);
            if ($retArr['chatroomObj']->error == 1) {
                $retArr['exit_reason'] = EXIT_REASON_WRONG_ROOM;
            }
        }

        if (in_array('videoroom', $thisUserNeededObjAr)) {
            /*
             * Check if the user has an appointment today at actual time
             */
            $user_has_app = false;
            if (defined('DATE_CONTROL') and (DATE_CONTROL == false)) {
                $user_has_app = true;
            } else {
                $user_has_app = MultiPort::hasThisUserAVideochatAppointment($userObj);
            }
            if ($user_has_app) {
                $event_token = $user_has_app;
                switch ($userObj->getType()) {
                    case AMA_TYPE_STUDENT:
                        /**
                         * get videoroom Obj
                         */
                        $videoroomObj = VideoRoom::getVideoObj();
                        $tempo_attuale = time();
                        $videoroomObj->videoroomInfo($sess_id_course_instance, $tempo_attuale);
                        if ($videoroomObj->full) {
                            $videoroomObj->serverLogin();
                            if ($videoroomObj->login >= 0) {
                                $videoroomObj->roomAccess(
                                    $userObj->getUserName(),
                                    $userObj->getFirstName(),
                                    $userObj->getLastName(),
                                    $userObj->getEmail(),
                                    $sess_id_user,
                                    $userObj->getType(),
                                    $sess_selected_tester
                                );
                            }
                        } else {
                            $status = addslashes(translateFN("Room not yet opened"));
                            $options_Ar = ['onload_func' => "close_page('$status');"];
                        }
                        break;
                    case AMA_TYPE_TUTOR:
                        $videoroomObj = VideoRoom::getVideoObj();
                        $tempo_attuale = time();
                        $creationDate = AbstractAMADataHandler::tsToDate($tempo_attuale);
                        $videoroomObj->videoroomInfo($sess_id_course_instance, $tempo_attuale);
                        $videoroomObj->serverLogin();
                        if ($videoroomObj->full) {
                            if ($videoroomObj->login >= 0) {
                                $videoroomObj->roomAccess(
                                    $userObj->getUserName(),
                                    $userObj->getFirstName(),
                                    $userObj->getLastName(),
                                    $userObj->getEmail(),
                                    $sess_id_user,
                                    $userObj->getType(),
                                    $sess_selected_tester
                                );
                            }
                        } else {
                            $course_title = (isset($courseObj)) ? $courseObj->getTitle() : '';
                            $room_name = $course_title . ' - ' . translateFN('Tutor') . ': ' . $userObj->getUserName() . ' ' . translateFN('data') . ': ' . $creationDate;
                            $comment = translateFN('inserimento automatico via') . ' ' . PORTAL_NAME;
                            $numUserPerRoom = 4;
                            $id_room = $videoroomObj->addRoom($room_name, $sess_id_course_instance, $sess_id_user, $comment, $numUserPerRoom, $course_title, $sess_selected_tester);
                            if ($videoroomObj->login >= 0 && ($id_room != false)) {
                                $videoroomObj->roomAccess(
                                    $userObj->getUserName(),
                                    $userObj->getFirstName(),
                                    $userObj->getLastName(),
                                    $userObj->getEmail(),
                                    $sess_id_user,
                                    $userObj->getType(),
                                    $sess_selected_tester
                                );
                            }
                        }
                        break;
                }
            } else {
                $close_page_message = addslashes(translateFN("You don't have a videochat appointment at this time."));
                $options_Ar = ['onload_func' => "close_page('$close_page_message');"];
            }
            if (isset($event_token)) {
                $retArr['event_token'] = $event_token;
            }
            if (isset($videoroomObj)) {
                $retArr['videoroomObj'] = $videoroomObj;
            }
            if (isset($options_Ar)) {
                $retArr['options_Ar'] = $options_Ar;
            }
        }

        return $retArr;
    }


    /**
     * Builds the sess_id_node, sess_id_course and sess_id_course_instance
     * array keys that are needed by some of the scritps
     *
     * @return array
     */
    protected static function buildGlobals()
    {
        if (isset($_REQUEST['id_node'])) {
            $sess_id_node = $_REQUEST['id_node'];
        } else {
            $sess_id_node = $_SESSION['sess_id_node'] ?? null;
        }

        if (isset($_REQUEST['id_course'])) {
            $sess_id_course = $_REQUEST['id_course'];
        } else {
            $sess_id_course = $_SESSION['sess_id_course'] ?? null;
        }

        if (isset($_REQUEST['id_course_instance'])) {
            $sess_id_course_instance = $_REQUEST['id_course_instance'];
        } else {
            $sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;
        }

        return ['sess_id_node' => $sess_id_node, 'sess_id_course' => $sess_id_course, 'sess_id_course_instance' => $sess_id_course_instance];
    }

    /**
     * Builds the sess_template_family array key
     *
     * @param string $user_family
     * @param string $node_family
     * @param string $course_instance_family
     * @param string $course_family
     *
     * @return string
     */
    protected static function setSessionTemplate($user_family = null, $node_family = null, $course_instance_family = null, $course_family = null)
    {
        if ((isset($_REQUEST['family'])) && (!empty($_REQUEST['family']))) { // from GET parameters
            $template_family = trim($_REQUEST['family']);
        } elseif ((isset($node_family)) && (!empty($node_family))) { // from node definition
            $template_family = $node_family;
        } elseif ((isset($course_instance_family)) && (!empty($course_instance_family))) { // from course instance definition
            $template_family = $course_instance_family;
        } elseif ((isset($course_family)) && (!empty($course_family))) { // from course definition
            $template_family = $course_family;
        } elseif ((isset($user_family)) && (!empty($user_family))) { // from user's profile
            $template_family = $user_family;
        } else {
            $template_family = ADA_TEMPLATE_FAMILY; // default template famliy
        }
        $_SESSION['sess_template_family'] = $template_family;
        return $template_family;
    }

    /**
     * Builds the user_messages value with a call to
     * CommunicationModuleHtmlLib::getMessagesAsTable
     *
     * @param ADAGenericUser $userObj
     *
     * @return CDOMElement
     */
    protected static function getUserMessages(ADAGenericUser $userObj)
    {
        return CommunicationModuleHtmlLib::getMessagesAsTable(MultiPort::getUserMessages($userObj), self::getTestersDataAr());
    }

    /**
     * Builds the user_agenda value with a call to
     * CommunicationModuleHtmlLib::getAgendaAsTable
     *
     * @param ADAGenericUser $userObj
     *
     * @return CDOMElement
     */
    protected static function getUserAgenda(ADAGenericUser $userObj)
    {
        return CommunicationModuleHtmlLib::getAgendaAsTable(MultiPort::getUserAgenda($userObj), self::getTestersDataAr());
    }

    /**
     * Builds the user_events value with a call to
     * CommunicationModuleHtmlLib::getEventsAsTable
     *
     * @param ADAGenericUser $userObj
     *
     * @return CDOMElement
     */
    protected static function getUserEvents(ADAGenericUser $userObj)
    {
        return CommunicationModuleHtmlLib::getEventsAsTable($userObj, MultiPort::getUserEventsNotRead($userObj), self::getTestersDataAr());
    }

    /**
     * @deprecated use get method instead.
     *
     * Extracts the helperData array to $GLOBALS
     *
     * @return void
     */
    protected static function extract()
    {
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CoreEvent::class,
                    'eventName' => CoreEvent::HELPERINITEXTRACT,
                ],
                GetCallingMethodName(),
                [
                    'data' => self::getHelperData(),
                    'class' => static::class,
                    'script' => $_SERVER['SCRIPT_NAME'],
                ]
            );
            foreach ($event->getArgument('data') as $key => $val) {
                self::$helperData[$key] = $val;
            }
        }

        foreach (self::getHelperData() as $key => $value) {
            if (self::$dumpExtract === true) {
                if (is_object($value)) {
                    $dbgval = 'Object of class ' . $value::class;
                } elseif (is_array($value)) {
                    $dbgval = sprintf("Array with %d elements", count($value));
                } else {
                    $dbgval = $value;
                }
                var_dump(sprintf(
                    "%s \$GLOBALS key '%s': %s",
                    !array_key_exists($key, $GLOBALS) ? 'Setting' : 'Overwriting',
                    $key,
                    $dbgval
                ));
            }
            $GLOBALS[$key] = $value;
        }
    }

    /**
     * Populates the testers_dataAr property calling
     * MultiPort::getTestersPointersAndIds()
     *
     * @return array
     */
    private static function getTestersDataAr()
    {
        if (is_null(self::$testers_dataAr)) {
            self::$testers_dataAr = MultiPort::getTestersPointersAndIds();
        }
        return self::$testers_dataAr;
    }
}

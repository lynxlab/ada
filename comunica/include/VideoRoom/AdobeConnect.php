<?php

/**
 * AdobeConnect specific class
 *
 * @package             videochat
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      giorgio consorti <g.consorti@lynxlab.com>
 * @copyright           Copyright (c) 2015, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Comunica\VideoRoom;

use AdobeConnect\Config;
use DateTime;
use Exception;
use Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectApiClient;
use Lynxlab\ADA\Comunica\VideoRoom\IVideoRoom;
use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AdobeConnect extends VideoRoom implements IVideoRoom
{
    /**
     * Undocumented variable
     *
     * @var \Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectApiClient
     */
    public $apiClient;

    /**
     * Undocumented variable
     *
     * @var \Lynxlab\ADA\Comunica\VideoRoom\ADAAdobeConnectApiClient
     */
    public $apiClientToEnter;

    /*
     * used to memorized the connected user id of conference system
     */
    public $user_connected_id;
    public $userFolderNameId;

    public function __construct($id_course_instance = "")
    {
        parent::__construct($id_course_instance);
        $this->apiClient = new ADAAdobeConnectApiClient(new Config(CONNECT_HOST, CONNECT_ADMIN, CONNECT_PASSWD));
        //        var_dump($this->apiClient);die();
    }

    public function makeApiClient($user = CONNECT_ADMIN, $pwd = CONNECT_PASSWD)
    {
        return new ADAAdobeConnectApiClient(new Config(CONNECT_HOST, $user, $pwd));
        //        var_dump($this->apiClient);
    }

    /**
     * Connected user id
     * @param string $username a username existing in adobe connect
     * @return string the id of username
     */
    public function getConnectIdUser($username = CONNECT_ADMIN)
    {

        $filter['filter_login'] = $username;
        try {
            $userObj = $this->apiClient->principalList($filter);
            $principal_id = VideoRoom::xmlAttribute($userObj[0], 'principal-id');
            $this->user_connected_id = $principal_id;
            return $this->user_connected_id;
        } catch (Exception $exc) {
            return [false, $exc->getMessage()];
        }
    }

    /**
     * return the id of folder name for connected user
     * @param string $folderName the name of folder to be found
     * @return string The id of folder found
     */
    public function getFolderId($folderName = DEFAULT_ROOM_NAME)
    {
        try {
            $folders = $this->apiClient->scoShortcuts();
            foreach ($folders as $OneFolder) {
                if (VideoRoom::xmlAttribute($OneFolder, 'type') == $folderName) {
                    $this->userFolderNameId = VideoRoom::xmlAttribute($OneFolder, 'sco-id');
                    return $this->userFolderNameId;
                }
            }
        } catch (Exception $exc) {
            return [false, $exc->getMessage()];
        }
        return '';
    }

    /**
     * Set permission of meeting room
     * @param string $roomId
     * @param string $principalId
     * @param string $permissionId
     * @return true/false
     */
    public function setPermission($aclId, $principalId = 'public-access', $permissionId = 'view-hidden')
    {
        try {
            $newPermission = $this->apiClient->permissionsUpdate($aclId, $principalId, $permissionId);
            return $newPermission;
        } catch (Exception $exc) {
            return [false, $exc->getMessage()];
        }
    }

    /**
     * Create a meeting room
     * @param string $name the name of the meeting room
     * @param string $sess_id_course_instance the course instance of
     * @return string The id of folder found
     */
    public function createMeeting($name = "service", $sess_id_course_instance = null, $sess_id_user = null, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER)
    {
        if (is_null($sess_id_course_instance) || is_null($sess_id_user)) {
            return false;
        }
        $objDateTime = new DateTime('NOW');
        $dateBegin = $objDateTime->format(DateTime::ISO8601);
        $UT = $objDateTime->getTimestamp();
        $dateEndUt = $UT + (MEETING_ROOM_DURATION * AMA_SECONDS_IN_A_DAY);
        $objDateEnd = new DateTime("@$dateEndUt");
        $dateEnd = $objDateEnd->format(DateTime::ISO8601);
        $urlPath = urlencode($selected_provider . '_' . $sess_id_course_instance);
        $paramsAr = [];
        $paramsAr['type'] = 'meeting';
        $paramsAr['name'] = $selected_provider . '_' . $sess_id_course_instance . ' ' . $course_title;
        $paramsAr['folder-id'] = $this->userFolderNameId;
        $paramsAr['date-begin'] = $dateBegin;
        $paramsAr['date-end'] = $dateEnd;
        $paramsAr['url-path'] = $urlPath;
        //        var_dump($paramsAr);

        try {
            $newRoom = $this->apiClient->scoUpdate($paramsAr);
            $roomCreatedId = VideoRoom::xmlAttribute($newRoom, 'sco-id');
            $roomCreatedPath = (string)$newRoom->{'url-path'};
            return [true, $roomCreatedId, $roomCreatedPath];
        } catch (Exception $exc) {
            //return $exc->getTraceAsString();
            return [false, $exc->getMessage(), $exc->getCode()];
        }
    }

    /**
     * Search a meeting room
     * @param string $sess_id_course_instance the instance of service
     * @param string $selected_provider the provider of the service
     * @return array id (or false) and path of meeting found
     */
    public function searchMeeting($sess_id_course_instance, $selected_provider = ADA_PUBLIC_TESTER)
    {
        $urlPath = urlencode($selected_provider . '_' . $sess_id_course_instance);
        $query = '/' . $urlPath . '/';
        $field = 'url-path';
        try {
            $existingMeeting = $this->apiClient->scoByUrl($query);
            //            $existingMeeting = $this->apiClient->scoSearchByField($query,$field);
            $existingMeetingId = VideoRoom::xmlAttribute($existingMeeting, 'sco-id');
            $existingMeetingPath = (string)$existingMeeting->{'url-path'};
            //            var_dump($existingMeeting);die();
            return [true, $existingMeetingId, $existingMeetingPath];
        } catch (Exception $exc) {
            return [false, $exc->getMessage(), $exc->getCode()];
        }
    }


    /**
     * Search a User
     * @param string $username the username of userObj
     * @return string The Adobe Conncect id of user found
     * @return bolean false if no user found
     */
    public function searchUser($username)
    {
        $filters = [];
        $filters['filter-login'] = $username;
        try {
            $userInfo = $this->apiClient->principalList($filters);
            $ACUserId = VideoRoom::xmlAttribute($userInfo[0], 'principal-id');
            return $ACUserId;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Add a User
     * @param string $username the username of userObj
     * @return string The Adobe Conncect id of user found
     * @return bolean false if any errors occurs
     */

    public function addUser($userObj)
    {
        $common_dh = AMACommonDataHandler::getInstance();
        $userPwd = substr($common_dh->getUserPwd($userObj->getId()), 0, 31);

        $principalData = [];
        $principalData['login'] = $userObj->getUserName();
        $principalData['first-name'] = $userObj->getFirstName();
        $principalData['last-name'] = $userObj->getLastName();
        $principalData['password'] = $userPwd;
        $principalData['type'] = 'user';
        $principalData['send-email'] = false;
        $principalData['has-children'] = '0';
        $principalData['email'] = $userObj->getEmail();

        try {
            $userInfo = $this->apiClient->principalUpdate($principalData);
            //            var_dump($userInfo);die();
            $ACUserId = VideoRoom::xmlAttribute($userInfo, 'principal-id');
            return $ACUserId;
        } catch (Exception) {
            return false;
        }
    }

    public function userLogin($userObj, $sess_id_user)
    {
        $common_dh = AMACommonDataHandler::getInstance();

        $userPwd = substr($common_dh->getUserPwd($sess_id_user), 0, 31);
        $userName = $userObj->getUserName();

        try {
            $ACUserLogin = $this->apiClient->login($userName, $userPwd);
            return $ACUserLogin;
        } catch (Exception) {
            return false;
        }
    }

    /**
     *
     * @param type $ACUserId
     * @return type
     */
    public function setPermissionHost($ACUserId)
    {

        /*
         *  add the permission host to meeting
         */
        $ACroomId = $this->id_room;
        $ACpermissionHost = $this->setPermission($ACroomId, $ACUserId, 'host');
        return $ACpermissionHost;
    }

    /*
     * Creazione videochat in openmeetings e registrazione dei dati nel DB locale
     */
    public function addRoom($name = "service", $sess_id_course_instance = null, $sess_id_user = null, $comment = "Inserimento automatico ", $num_user = 4, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER)
    {
        if (is_null($sess_id_course_instance) || is_null($sess_id_user)) {
            return false;
        }

        $dh            =   $GLOBALS['dh'];
        $error         =   $GLOBALS['error'];
        $debug         =   $GLOBALS['debug'];
        $root_dir      =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];

        $ACconnectedUser = $this->getConnectIdUser();
        if (is_array($ACconnectedUser)) {
            return false;
        }

        $ACfolderId = $this->getFolderId();
        if (is_array($ACfolderId)) {
            return false;
        }

        $ACroomCreatedInfo = $this->createMeeting($name, $sess_id_course_instance, $sess_id_user, $course_title, $selected_provider);
        if (!$ACroomCreatedInfo[0] && !str_contains($ACroomCreatedInfo[1], 'duplicate')) {
            return false;
        } elseif (!$ACroomCreatedInfo[0] && str_contains($ACroomCreatedInfo[1], 'duplicate')) {

            /**
             * @todo Search a meeting room in order to write the correct one in table DB
             *
             * $ACroomExistingInfo = $this->searchMeeting($sess_id_course_instance, $selected_provider);
             * var_dump($ACroomExistingInfo);die();
             *
             */
            return false;
        }

        $ACroomId = $ACroomCreatedInfo[1];
        $this->id_room = $ACroomId;
        $ACroomPath = $ACroomCreatedInfo[2];

        /*
         *  make the meeting public
         */
        $ACpermission = $this->setPermission($ACroomId);

        /**
         * @todo Delete the room just created in case of error in changing the permission of the room
         */
        if (is_array($ACpermission) && !$ACpermission[0]) {
            return false;
        }


        $meeting_duration = (MEETING_ROOM_DURATION * AMA_SECONDS_IN_A_DAY);
        $videoroom_dataAr['id_room'] = $ACroomId;
        $videoroom_dataAr['id_istanza_corso'] = $sess_id_course_instance;
        $videoroom_dataAr['id_tutor'] = $sess_id_user;
        $videoroom_dataAr['tipo_videochat'] = 'meeting';
        $videoroom_dataAr['descrizione_videochat'] = $name;
        $videoroom_dataAr['tempo_avvio'] = time();
        $videoroom_dataAr['tempo_fine'] = time() + $meeting_duration;

        $videoroom_data = $dh->addVideoroom($videoroom_dataAr);
        if (AMADB::isError($videoroom_data)) {
            return false;
        }
        return $ACroomId;
    }

    public function serverLogin()
    {
        $this->login = ($this->apiClient->getLoggedIn() ? 1 : -1);
    }


    /**
     *
     * @param type $username
     * @param type $nome
     * @param type $cognome
     * @param type $user_email
     * @param type $sess_id_user
     * @param type $id_profile
     *
     * @todo error management
     */
    public function roomAccess($username, $nome, $cognome, $user_email, $sess_id_user, $id_profile, $selected_provider)
    {
        $dh = $GLOBALS['dh'];
        $ACroom_id = $this->id_room;
        $room_id = $this->id;
        $ACMeetingInfo = $this->getRoom($ACroom_id);
        if ($ACMeetingInfo == false) {
            $deletedRoom = $dh->deleteVideoroom($ACroom_id);
            $room_name = translateFN('meeting rigenerato') . ' ' . translateFN('Tutor') . ': ' . $username;
            ;
            $comment = translateFN('inserimento automatico via') . ' ' . PORTAL_NAME;
            $numUserPerRoom = 4;
            $sess_id_course_instance = $this->id_istanza_corso;
            $course_title = translateFN('meeting creato in emergenza');

            $ACroom_id = $this->addRoom($room_name, $sess_id_course_instance, $sess_id_user, $comment, $numUserPerRoom, $course_title, $selected_provider);
            if ($ACroom_id == false) {
                return false;
            }
            $ACMeetingInfo = $this->getRoom($ACroom_id);
            if ($ACMeetingInfo == false) {
                return false;
            }
        }
        $ACMeetingPath = (string)$ACMeetingInfo['sco']->{'url-path'};
        if ($id_profile == AMA_TYPE_TUTOR) {
            $ACUserId = $this->searchUser($username);
            if ($ACUserId == false || is_null($ACUserId)) {
                $ACUserId = $this->addUser($GLOBALS['userObj']);
                if ($ACUserId == false) {
                    return false;
                }
            }
            $setPermission = $this->setPermissionHost($ACUserId);
            if (is_array($setPermission) && $setPermission[0] == false) {
                return false;
            }


            $this->apiClient->call('logout');
            $this->apiClient->unsetCookie();
            unset($this->apiClient);

            $common_dh = AMACommonDataHandler::getInstance();
            $userObj = $GLOBALS['userObj'];
            $userPwd = substr($common_dh->getUserPwd($sess_id_user), 0, 31);
            $userName = $userObj->getUserName();
            $this->apiClientToEnter = $this->makeApiClient($userName, $userPwd);

            $cookieVal = $this->apiClientToEnter->getConnection()->getConnectionCookie();
            $this->link_to_room = PROTOCOL . CONNECT_HOST . $ACMeetingPath . '?session=' . $cookieVal;
        } else {
            $this->apiClient->call('logout');
            $this->apiClient->unsetCookie();
            unset($this->apiClient);

            $this->link_to_room = PROTOCOL . CONNECT_HOST . $ACMeetingPath . '?guestName=' . $nome . ' ' . $cognome;
        }
    }

    public function getRoom($id_room)
    {
        try {
            $ACMeetingInfo = $this->apiClient->scoInfo($id_room);
            return $ACMeetingInfo;
        } catch (Exception) {
            return false;
        }
    }
}

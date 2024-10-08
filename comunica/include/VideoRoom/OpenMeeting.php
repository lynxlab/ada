<?php

/**
 * Openmeetings specific class
 *
 * @package             videochat
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      giorgio consorti <g.conorti@lynxlab.com>
 * @copyright           Copyright (c) 2015, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Comunica\VideoRoom;

use Lynxlab\ADA\Comunica\VideoRoom\IVideoRoom;
use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\Main\AMA\AMADB;
use SoapClient;

class OpenMeeting extends VideoRoom implements IVideoRoom
{
    public $resultAddRoom;
    public $listRooms;
    public $error_openmeetings;
    public $secureHash;


    public function __construct($id_course_instance = "")
    {
        parent::__construct($id_course_instance);
    }

    /*
     * Creazione videochat in openmeetings e registrazione dei dati nel DB locale
     */
    public function addRoom($name = "service", $sess_id_course_instance = null, $sess_id_user = null, $comment = "Inserimento automatico via ADA", $num_user = 4, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER)
    {

        if (is_null($sess_id_course_instance) || is_null($sess_id_user)) {
            return false;
        }

        $dh            =   $GLOBALS['dh'];
        $error         =   $GLOBALS['error'];
        $debug         =   $GLOBALS['debug'];
        $root_dir      =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];


        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        $room_type = intval(OM_ROOM_TYPE);
        $ispublic = ROOM_IS_PUBLIC;
        $videoPodWidth = VIDEO_POD_WIDTH;
        $videoPodHeight = VIDEO_POD_HEIGHT;
        $videoPodXPosition = VIDEO_POD_X_POSITION;
        $videoPodYPosition = VIDEO_POD_Y_POSITION;
        $moderationPanelXPosition = MODERATION_PANEL_X_POSITION;
        $showWhiteBoard = SHOW_WHITE_BOARD;
        $whiteBoardPanelXPosition = WHITE_BOARD_PANEL_X_POSITION;
        $whiteBoardPanelYPosition = WHITE_BOARD_PANEL_Y_POSITION;
        $whiteBoardPanelHeight = WHITE_BOARD_PANEL_HEIGHT;
        $whiteBoardPanelWidth = WHITE_BOARD_PANEL_WIDTH;
        $showFilesPanel = SHOW_FILES_PANEL;
        $filesPanelXPosition = FILES_PANEL_X_POSITION;
        $filesPanelYPosition = FILES_PANEL_Y_POSITION;
        $filesPanelHeight = FILES_PANEL_HEIGHT;
        $filesPanelWidth = FILES_PANEL_WIDTH;


        //Create the SoapClient object
        $this->client_room = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/RoomService?wsdl");

        $addRoomWithModerationParams = [
            'SID' => $this->session_id,
            'name' => $name,
            'roomtypes_id' => intval($room_type), //1,
            'comment' => $comment,
            'numberOfPartizipants' => intval($num_user),
            'ispublic' => $ispublic,
            'appointment' => false,
            'isDemoRoom' => false,
            'demoTime' => 0,
            'isModeratedRoom' => true,
        ];
        $this->resultAddRoom = $this->client_room->addRoomWithModeration($addRoomWithModerationParams);
        $this->id_room = $this->resultAddRoom->return;


        $interval = 60 * 60;
        $videoroom_dataAr['id_room'] = $this->id_room;
        $videoroom_dataAr['id_istanza_corso'] = $sess_id_course_instance;
        $videoroom_dataAr['id_tutor'] = $sess_id_user;
        $videoroom_dataAr['tipo_videochat'] = $room_type;
        $videoroom_dataAr['descrizione_videochat'] = $name;
        $videoroom_dataAr['tempo_avvio'] = time();
        $videoroom_dataAr['tempo_fine'] = time() + $interval;

        $videoroom_data = $dh->addVideoroom($videoroom_dataAr);

        if (AMADB::isError($videoroom_data)) {
            return false;
        }
        return $this->id_room;
    }

    public function listRooms()
    {

        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        //Create the SoapClient object
        $this->client_room = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/RoomService?wsdl");

        $rooms_params   = [
            'SID' => $this->session_id,
            'start' => 1,
            'max' => 999,
            'orderby' => "name",
            'asc' => 1,
        ];

        $this->listRooms = $this->client_room->getRooms($rooms_params);
    }

    public function getRoom($id_room)
    {

        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        //Create the SoapClient object
        $this->client_room = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/RoomService?wsdl");

        $rooms_params   = [
            'SID' => $this->session_id,
            'rooms_id' => $id_room,
        ];

        $this->room_properties = $this->client_room->getRoomById($rooms_params);
        //  $this->room_properties = $this->client_room->getRoomWithCurrentUsersById($rooms_params);
    }

    public function serverLogin()
    {
        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        $this->client_user = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/UserService?wsdl");

        //get  new session_id  for accessing and creating rooms
        $result = $this->client_user->getSession();
        $this->session_id = $result->return->session_id;

        //login as admin to create and access rooms

        $login_params   = [
            'SID' => $this->session_id,
            'username' => OPENMEETINGS_ADMIN,
            'userpass' => OPENMEETINGS_PASSWD,
        ];
        $loginResult = $this->client_user->loginUser($login_params);
        $this->login = $loginResult->return;
        if ($this->login <= 0) {
            $error_params = [
                'SID' => $this->session_id,
                'errorid' => $loginResult->return,
                'language_id' => 13,
            ];
            $this->error_openmeetings = $this->client_user->getErrorByCode($error_params);
        }
        /*            else {
                            echo "<br>Login successful<br>";
                            echo "<br>". $login->return ."<br>";
                    }
         *
         */
    }

    public function roomAccess($username, $nome, $cognome, $user_email, $sess_id_user, $id_profile, $selected_provider)
    {

        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        //Create the SoapClient object
        $this->client_room = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/RoomService?wsdl");

        $becomeModeratorAsInt = 0; // 0 = no Moderator 1 = Moderator @todo impostare a moderatore se practitioner
        if ($id_profile == AMA_TYPE_TUTOR) {
            $becomeModeratorAsInt = 1;
            $allowRecording = 1;
        }
        $room_id = $this->id_room;
        $externalUserId = $sess_id_user;
        $externalUserType = "ADA"; // potrebbe essere preso da $userObj->type?
        $showAudioVideoTestAsInt = 0; // 0 = no audio/video test
        if (OPENMEETINGS_VERSION > 0) {
            $user_params   = [
                'SID' => $this->session_id,
                'username' => $username,
                'firstname' => $nome,
                'lastname' => $cognome,
                'profilePictureUrl' => "",
                'email' => $user_email,
                'externalUserId' => $externalUserId,
                'externalUserType' => $externalUserType,
                'room_id' => $room_id,
                'becomeModeratorAsInt' => $becomeModeratorAsInt,
                'showAudioVideoTestAsInt' => $showAudioVideoTestAsInt,
            ];
            /* @var $secureHash <array> needed to access openmeetings room*/
            //          $this->secureHash = $this->client_user->setUserObjectAndGenerateRoomHash($user_params);
            $this->secureHash = $this->client_user->setUserObjectAndGenerateRoomHashByURL($user_params);
            $secureHash = $this->secureHash->return;
            //                print_r($secureHash);
        } else {
            $user_params   = [
                'SID' => $this->session_id,
                'username' => $username,
                'firstname' => $nome,
                'lastname' => $cognome,
                'profilePictureUrl' => "",
                'email' => $user_email,
            ];
            $setUser = $this->client_user->setUserObject($user_params);
        }

        /*
         * LINK A STANZA
         */
        $language = ROOM_DEFAULT_LANGUAGE;
        $sess_lang = $_SESSION['sess_user_language'];
        $videochat_lang = "VIDEOCHAT_LANGUAGE_" . strtoupper($sess_lang);
        if (defined($videochat_lang)) {
            $language = constant($videochat_lang);
        }
        if (OPENMEETINGS_VERSION > 0) {
            $this->link_to_room = "http://" . $host . $port . "/" . $dir . "/?secureHash=" . $secureHash . "&language=" . $language;
        } else {
            $this->link_to_room = "http://" . $host . $port . "/" . $dir . "/main.lzx.lzr=swf8.swf?roomid=" . $this->id_room . "&sid=" . $this->session_id . "&language=" . $language;
        }
    }

    public function deleteRoom($id_room)
    {
        $dh = $GLOBALS['dh'];
        $host = OPENMEETINGS_HOST;
        $port = OPENMEETINGS_PORT;
        $dir = OPENMEETINGS_DIR;

        //Create the SoapClient object
        $this->client_room = new SoapClient("http://" . $host . $port . "/" . $dir . "/services/RoomService?wsdl");

        $params   = [
            'SID' => $this->session_id,
            'rooms_id' => $id_room,
        ];

        $result_openmeetings = $this->client_room->deleteRoom($params);
        if ($result_openmeetings->return == $id_room) { // if deleted ok in openmeetings delete in DB too
            $result = $dh->deleteVideoroom($id_room);
        }
    }
}

<?php

namespace Lynxlab\ADA\Comunica\VideoRoom;

use Lynxlab\ADA\Comunica\VideoRoom\IVideoRoom;
use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Module\JitsiIntegration\ADAJitsiApi;
use Lynxlab\ADA\Module\JitsiIntegration\JitsiIntegrationException;

/**
 * Jitsi meet specific class
 *
 * @package   videochat
 * @author    giorgio consorti <g.conorti@lynxlab.com>
 * @copyright Copyright (c) 2020, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version   0.1
 */

class Jitsi extends VideoRoom implements IVideoRoom
{
    public const ONLOAD_JS = "if (\$j('#" . JITSI_HTML_PLACEHOLDER_ID . "').length>0) {
		\$j.getScript('" . MODULES_JITSI_HTTP . "/ada-jitsi.js.php?parentId=" . JITSI_HTML_PLACEHOLDER_ID . "');
	}";
    public const VIDEOCHATTYPE = 'J';

    private $jitsiAPI = null;
    private $meetingID = null;

    public function __construct($id_course_instance = "")
    {
        parent::__construct($id_course_instance);
        $this->jitsiAPI = new ADAJitsiApi();
    }

    public function addRoom($name = 'service', $sess_id_course_instance = null, $sess_id_user = null, $comment = 'Inserimento automatico via ADA', $num_user = 25, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER)
    {
        try {
            if (is_null($sess_id_course_instance) || is_null($sess_id_user)) {
                throw new JitsiIntegrationException("ID course instance and ID user must not be null");
            }
            $videoroom_dataAr = [];
            $videoroom_dataAr['id_room'] = 0; // will be set to the id by the datahandler
            $videoroom_dataAr['id_istanza_corso'] = $sess_id_course_instance;
            $videoroom_dataAr['id_tutor'] = $sess_id_user;
            $videoroom_dataAr['tipo_videochat'] = self::VIDEOCHATTYPE;
            $videoroom_dataAr['descrizione_videochat'] = $name;
            $videoroom_dataAr['tempo_avvio'] = time();
            $videoroom_dataAr['tempo_fine'] = strtotime('tomorrow midnight') - 1;
            $videoroom_dataAr['room_name'] = $course_title;
            $videoroom_data = $this->jitsiAPI->create($videoroom_dataAr);
            $this->id_room = $videoroom_data['openmeetings_room_id'];
            $this->id_istanza_corso = $videoroom_data['id_istanza_corso'];
            $this->meetingID = $videoroom_data['meetingID'];
            return $this->id_room;
        } catch (JitsiIntegrationException $e) {
            return false;
        }
    }

    /**
     * TODO: this is mock-up that just forces full to be non zero
     * implement it if needed, or remove it
     *
     * @param [type] $id_course_instance
     * @param [type] $tempo_avvio
     * @param [type] $interval
     * @return void
     */
    public function videoroomInfo($id_course_instance, $tempo_avvio = null, $interval = null, $more_query = null)
    {
        // load parent info
        if (is_null($more_query)) {
            $more_query = 'AND `tipo_videochat`="' . self::VIDEOCHATTYPE . '" ORDER BY `tempo_avvio` DESC';
        }
        parent::videoroomInfo($id_course_instance, $tempo_avvio, $interval, $more_query);
        // load Jitsi own info
        $video_roomAr = $this->jitsiAPI->getInfo($this->id);
        $this->meetingID = null;
        if (is_array($video_roomAr) && count($video_roomAr) > 0) {
            $this->meetingID = $video_roomAr['meetingID'];
        }
        $this->full = !is_null($this->meetingID);
    }

    public function serverLogin()
    {
        $this->login = 1;
        return true;
    }

    public function roomAccess($username, $nome, $cognome, $user_email, $sess_id_user, $id_profile, $selected_provider)
    {
        $this->link_to_room = CDOMElement::create('div', 'id:' . JITSI_HTML_PLACEHOLDER_ID);
        $this->link_to_room->setAttribute('data-domain', JITSI_CONNECT_HOST);
        $this->link_to_room->setAttribute('data-width', FRAME_WIDTH);
        $this->link_to_room->setAttribute('data-height', FRAME_HEIGHT);
    }

    public function getRoom($id_room)
    {
    }

    /**
     * Get the value of meetingID
     */
    public function getMeetingID()
    {
        return $this->meetingID;
    }

    /**
     * Set the value of meetingID
     *
     * @return  self
     */
    public function setMeetingID($meetingID)
    {
        $this->meetingID = $meetingID;

        return $this;
    }

    public function getLogoutUrl()
    {
        return $this->jitsiAPI->getLogoutUrl() . $this->getLogoutUrlParams();
    }
}

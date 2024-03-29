<?php

namespace Lynxlab\ADA\Comunica\VideoRoom;

use BigBlueButton\Parameters\JoinMeetingParameters;
use Lynxlab\ADA\Module\BBBIntegration\ADABBBApi;
use Lynxlab\ADA\Module\BBBIntegration\BBBIntegrationException;

/**
 * BigBlueButton specific class
 *
 * @package   videochat
 * @author    Stefano Penge <steve@lynxlab.com>
 * @author    Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author    giorgio consorti <g.conorti@lynxlab.com>
 * @copyright Copyright (c) 2020, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version   0.1
 */

class BigBlueButton extends VideoRoom implements IVideoRoom
{
    public const IFRAMEATTR = ' class=\'ada-videochat-embed bbb\' allowfullscreen allow=\'camera ' . BBB_SERVER . '; microphone ' . BBB_SERVER . '\'';
    public const VIDEOCHATTYPE = 'B';

    private $bbbAPI = null;

    public $meetingID;
    public $moderatorPW;
    public $attendeePW;

    public function __construct($id_course_instance = "")
    {
        parent::__construct($id_course_instance);
        $this->bbbAPI = new ADABBBApi();
    }

    /*
     * Creazione videochat
     */
    public function addRoom($name = 'service', $sess_id_course_instance = null, $sess_id_user = null, $comment = 'Inserimento automatico via ADA', $num_user = 25, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER)
    {
        try {
            if (is_null($sess_id_course_instance) || is_null($sess_id_user)) {
                throw new BBBIntegrationException("ID course instance and ID user must not be null");
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
            $videoroom_data = $this->bbbAPI->create($videoroom_dataAr);
            $this->id_room = $videoroom_data['openmeetings_room_id'];
            $this->id_istanza_corso = $videoroom_data['id_istanza_corso'];
            $this->meetingID = $videoroom_data['meetingID']->toString();
            $this->moderatorPW = $videoroom_data['moderatorPW']->toString();
            $this->attendeePW = $videoroom_data['attendeePW']->toString();
            return $this->id_room;
        } catch (BBBIntegrationException $e) {
            return false;
        }
    }

    public function videoroom_info($id_course_instance, $tempo_avvio = null, $interval = null, $more_query = null)
    {
        // load parent info
        if (is_null($more_query)) {
            $more_query = 'AND `tipo_videochat`="' . self::VIDEOCHATTYPE . '" ORDER BY `tempo_avvio` DESC';
        }
        parent::videoroom_info($id_course_instance, $tempo_avvio, $interval, $more_query);
        // load BigBlueButton own info and check that meeting does exists
        $video_roomAr = $this->bbbAPI->getInfo($this->id);
        $this->meetingID = null;
        if (is_array($video_roomAr) && count($video_roomAr) > 0) {
            $this->meetingID = $video_roomAr['meetingID'];
            if (isset($video_roomAr['attendeePW'])) {
                $this->attendeePW = $video_roomAr['attendeePW'];
            }
            if (isset($video_roomAr['moderatorPW'])) {
                $this->moderatorPW = $video_roomAr['moderatorPW'];
            }
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
        $joinMeetingParams = new JoinMeetingParameters(
            $this->meetingID,
            $nome . ' ' . $cognome,
            ($id_profile == AMA_TYPE_TUTOR ? $this->moderatorPW : $this->attendeePW)
        );
        $joinMeetingParams->setRedirect(true)->setUserId($sess_id_user)->setJoinViaHtml5(true);
        $joinResponse = $this->bbbAPI->getJoinMeetingURL($joinMeetingParams);
        $this->link_to_room = $joinResponse;
    }

    public function getRoom($id_room)
    {
    }
}

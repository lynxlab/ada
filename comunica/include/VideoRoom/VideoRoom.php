<?php

/**
 * videoroom abstract class
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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class VideoRoom
{
    public $id;
    public $id_room;
    public $id_istanza_corso;
    public $id_tutor;
    public $tipo_videochat;
    public $descrizione_videochat;
    public $tempo_avvio;
    public $tempo_fine;
    public $full;

    public $client_user;
    public $session_id;
    public $login;
    public $error_videochat;

    public $client_room;
    public $roomTypes;
    public $rooms; // elenco stanze disponibili sul server
    public $link_to_room;
    public $room_properties;
    public $list_room; // elenco stanze disponibili sul server

    public const EVENT_ENTER = 1;
    public const EVENT_EXIT = 2;

    public function __construct($id_course_instance = "")
    {
        $dh            =   $GLOBALS['dh'];
        $error         =   $GLOBALS['error'];
        $debug         =   $GLOBALS['debug'] ?? null;
        $root_dir      =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];
    }

    public static function getVideoObj()
    {
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && !empty($GLOBALS['user_provider']) && is_readable(ROOT_DIR . '/clients/' . $GLOBALS['user_provider'] . '/' . CONFERENCE_TO_INCLUDE . '.config.inc.php')) {
            require_once ROOT_DIR . '/clients/' . $GLOBALS['user_provider'] . '/' . CONFERENCE_TO_INCLUDE . '.config.inc.php';
        } else {
            require_once ROOT_DIR . '/comunica/include/' . CONFERENCE_TO_INCLUDE . '.config.inc.php';
        }
        require_once $GLOBALS['root_dir'] . '/comunica/include/' . CONFERENCE_TO_INCLUDE . '.class.inc.php';
        $videoObjToInstantiate = CONFERENCE_TO_INCLUDE;
        return new $videoObjToInstantiate();
    }
    /*
     * retrieve infos about room memorized in local DB
     */
    public function videoroom_info($id_course_instance, $tempo_avvio = null, $interval = null, $more_query = null)
    {
        $dh            =   $GLOBALS['dh'];
        $error         =   $GLOBALS['error'];
        $debug         =   $GLOBALS['debug'] ?? null;
        $root_dir      =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];
        $video_roomAr = $dh->get_videoroom_info($id_course_instance, $tempo_avvio, $more_query);

        if (AMA_DataHandler::isError($video_roomAr) || !is_array($video_roomAr)) {
            $this->full = 0;
        } else {
            $this->id_room = $video_roomAr['id_room'];
            $this->id_tutor = $video_roomAr['id_tutor'];
            $this->id = $video_roomAr['id'];
            $this->id_istanza_corso = $video_roomAr['id_istanza_corso'];
            $this->descrizione_videochat = $video_roomAr['descrizione_videochat'];
            $this->tempo_avvio = $video_roomAr['tempo_avvio'];
            $this->tempo_fine = $video_roomAr['tempo_fine'];
            $this->tipo_videochat = $video_roomAr['tipo_videochat'];
            $this->full = 1;
        }
    }

    public static function xml_attribute($object, $attribute)
    {
        if (isset($object[$attribute])) {
            return (string) $object[$attribute];
        }
    }

    public function logEnter($eventData = null)
    {
        return $this->logEvent(self::EVENT_ENTER, $eventData);
    }

    public function logExit($eventData = null)
    {
        return $this->logEvent(self::EVENT_EXIT, $eventData);
    }

    protected function logEvent($event, $eventData = null)
    {
        if (is_null($eventData)) {
            $eventData = [
                'event' => $event,
                'id_user' => $_SESSION['sess_userObj']->getId(),
                'id_room' => $this->id_room,
                'id_istanza_corso' => $this->id_istanza_corso,
                'is_tutor' => $_SESSION['sess_userObj']->getType() == AMA_TYPE_TUTOR,
            ];
        }
        return $GLOBALS['dh']->log_videoroom($eventData);
    }

    public static function getInstanceLog($id_course_instance, $id_room = null, $id_user = null)
    {
        return $GLOBALS['dh']->get_log_videoroom($id_course_instance, $id_room, $id_user);
    }

    public function getLogoutUrlParams()
    {
        return '?p=' . $_SESSION['sess_selected_tester'] .
        '&id_user=' . $_SESSION['sess_userObj']->getId() .
        '&id_room=' . $this->id_room .
        '&id_istanza_corso=' . $this->id_istanza_corso .
        '&ist=' . intval($_SESSION['sess_userObj']->getId() == $this->id_tutor);
    }

    public static function initialToDescr($initial)
    {
        if ($initial === 'J') {
            return 'Jitsi Meet';
        } elseif ($initial === 'Z') {
            return 'Zoom';
        } elseif ($initial === 'B') {
            return 'BigBlueButton';
        }
        return translateFN('Sconosciuto');
    }
}

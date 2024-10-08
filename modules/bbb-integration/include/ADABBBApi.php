<?php

/**
 * @package     ADA BigBlueButton Integration
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\BBBIntegration;

use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use Exception;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\BBBIntegration\AMABBBIntegrationDataHandler;

class ADABBBApi extends BigBlueButton
{
    /**
     * @var AMABBBIntegrationDataHandler
     */
    private $dh;

    public function __construct($tester = null)
    {
        if (isset($GLOBALS['dh']) && $GLOBALS['dh'] instanceof AMABBBIntegrationDataHandler) {
            $this->dh = $GLOBALS['dh'];
        } else {
            if (is_null($tester)) {
                if (isset($_SESSION) && array_key_exists('sess_selected_tester', $_SESSION)) {
                    $tester = $_SESSION['sess_selected_tester'];
                } elseif (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
                    $tester = $GLOBALS['user_provider'];
                }
            }
            $this->dh = AMABBBIntegrationDataHandler::instance(MultiPort::getDSN($tester));
        }

        if (defined('BBB_SERVER_BASE_URL') && strlen('BBB_SERVER_BASE_URL') > 0) {
            putenv('BBB_SERVER_BASE_URL=' . BBB_SERVER_BASE_URL);
        }
        if (defined('BBB_SECRET') && strlen('BBB_SECRET') > 0) {
            putenv('BBB_SECRET=' . BBB_SECRET);
        }
        parent::__construct();
    }

    public function create($meetingData)
    {
        try {
            $meetingData = $this->dh->addVideoroom($meetingData);

            $createParams = new CreateMeetingParameters(
                $meetingData['meetingID']->toString(),
                $meetingData['room_name']
            );
            $createParams->setAttendeePassword($meetingData['attendeePW']->toString())
                ->setModeratorPassword($meetingData['moderatorPW']->toString())
                ->setLogoutUrl($this->getLogoutUrl($meetingData))->setEndCallbackUrl($this->getLogoutUrl($meetingData))
                ->setRecord(true);
            $this->createMeeting($createParams);
            return $meetingData;
        } catch (Exception) {
            return false;
        }
    }

    public function getInfo($roomId)
    {
        try {
            // load meetingID and passwords from the DB
            $meetingData = $this->dh->getInfo($roomId);
            // check if the meetingID is still at the BBB server
            $meetingParams = new GetMeetingInfoParameters(
                $meetingData['meetingID'] ?? null,
                $meetingData['attendeePW'] ?? null
            );
            $serverData = $this->getMeetingInfo($meetingParams);
            if ($serverData->failed()) {
                $meetingData['meetingID'] = null;
                if (isset($meetingData['moderatorPW'])) {
                    unset($meetingData['moderatorPW']);
                }
                if (isset($meetingData['attendeePW'])) {
                    unset($meetingData['attendeePW']);
                }
                // $this->dh->deleteVideoroom($roomId);
            }
            return $meetingData;
        } catch (Exception) {
            return [];
        }
    }

    private function getLogoutUrl($meetingData)
    {
        return MODULES_BIGBLUEBUTTON_HTTP . '/endvideochat.php' .
            '?p=' . $_SESSION['sess_selected_tester'] .
            '&id_user=' . $_SESSION['sess_userObj']->getId() .
            '&id_room=' . $meetingData['openmeetings_room_id'] .
            '&id_istanza_corso=' . $meetingData['id_istanza_corso'] .
            '&is_tutor=' . intval($_SESSION['sess_userObj']->getId() == $meetingData['id_tutor']);
    }
}

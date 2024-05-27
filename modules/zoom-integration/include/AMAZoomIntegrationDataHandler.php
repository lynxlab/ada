<?php

/**
 * @package     ADA Zoom Meeting Integration
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\ZoomIntegration;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;

class AMAZoomIntegrationDataHandler extends AMADataHandler
{
    use WithCUD;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_zoomconf_';

    private const EXCEPTIONCLASS = ZoomIntegrationException::class;

    /**
     * Save a row in the meeting table
     *
     * @param array $saveData
     */
    public function saveMeeting($saveData)
    {
        // update main table
        $result = $this->executeCriticalPrepared("UPDATE `openmeetings_room` SET `id_room`=? WHERE `id`=?", [ $saveData['openmeetings_room_id'], $saveData['openmeetings_room_id'] ]);
        if (AMADB::isError($result)) {
            throw new ZoomIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }

        $result = $this->executeCriticalPrepared($this->sqlInsert(self::PREFIX . 'meeting', $saveData), array_values($saveData));
        if (AMADB::isError($result)) {
            throw new ZoomIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }

        return true;
    }

    public function getInfo($roomId)
    {
        $query = 'SELECT * FROM `' . self::PREFIX . 'meeting` WHERE `openmeetings_room_id` = ?;';
        $result =  $this->getRowPrepared($query, [$roomId], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            throw new ZoomIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }
        if (is_array($result) && count($result) > 0) {
            return $result;
        }
        return [];
    }

    public function addVideoroom($videoroom_dataAr = [])
    {
        $result = parent::addVideoroom($videoroom_dataAr);
        if (!AMADB::isError($result)) {
            $meetingData = [
                'openmeetings_room_id' => $this->lastInsertID(),
                'meetingID' => $videoroom_dataAr['meetingID'],
                'meetingPWD' => $videoroom_dataAr['meetingPWD'],
            ];
            if (
                $this->saveMeeting(
                    array_map(
                        function ($el) {
                            if (method_exists($el, 'getBytes')) {
                                return $el->getBytes();
                            } else {
                                return $el;
                            }
                        },
                        $meetingData
                    )
                )
            ) {
                return array_merge($videoroom_dataAr, $meetingData);
            }
        } else {
            throw new ZoomIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }
        return false;
    }

    public function deleteVideoroom($id_room)
    {
        parent::deleteVideoroom($id_room);
        $sql = "DELETE FROM `" . self::PREFIX . "meeting` WHERE `openmeetings_room_id` = ?";
        $result = $this->queryPrepared($sql, $id_room);
        if (AMADB::isError($result)) {
            throw new ZoomIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }
        return true;
    }
}

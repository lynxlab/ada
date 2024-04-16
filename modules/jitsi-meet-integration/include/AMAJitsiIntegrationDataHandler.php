<?php

/**
 * @package     ADA Jitsi Integration
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\JitsiIntegration;

class AMAJitsiIntegrationDataHandler extends AMADataHandler
{
    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_jitsi_';

    /**
     * Save a row in the meeting table
     *
     * @param array $saveData
     */
    public function saveMeeting($saveData)
    {
        // update main table
        $result = $this->executeCriticalPrepared("UPDATE `openmeetings_room` SET `id_room`=? WHERE `id`=?", [$saveData['openmeetings_room_id'], $saveData['openmeetings_room_id']]);
        if (AMADB::isError($result)) {
            throw new JitsiIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }

        $result = $this->executeCriticalPrepared($this->sqlInsert(self::PREFIX . 'meeting', $saveData), array_values($saveData));
        if (AMADB::isError($result)) {
            throw new JitsiIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }

        return true;
    }

    public function getInfo($roomId)
    {
        $query = 'SELECT * FROM `' . self::PREFIX . 'meeting` WHERE `openmeetings_room_id` = ?;';
        $result =  $this->getRowPrepared($query, [$roomId], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            throw new JitsiIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
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
                'openmeetings_room_id' => $this->getConnection()->lastInsertID(),
                'meetingID' => self::buildMeetingID($videoroom_dataAr),
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
            throw new JitsiIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }
    }

    public function deleteVideoroom($id_room)
    {
        parent::deleteVideoroom($id_room);
        $sql = "DELETE FROM `" . self::PREFIX . "meeting` WHERE `openmeetings_room_id` = ?";
        $result = $this->queryPrepared($sql, $id_room);
        if (AMADB::isError($result)) {
            throw new JitsiIntegrationException($result->getMessage(), is_numeric($result->getCode()) ? $result->getCode() : null);
        }
        return true;
    }

    private static function buildMeetingID($videoroom_dataAr)
    {
        return md5(implode('', $videoroom_dataAr));
    }

    /**
     * Builds an sql update query as a string
     *
     * @param string $table
     * @param array $fields
     * @param string $whereField
     * @return string
     */
    private function sqlUpdate($table, array $fields, $whereField)
    {
        return sprintf(
            "UPDATE `%s` SET %s WHERE `%s`=?;",
            $table,
            implode(',', array_map(function ($el) {
                return "`$el`=?";
            }, $fields)),
            $whereField
        );
    }

    /**
     * Builds an sql insert into query as a string
     *
     * @param string $table
     * @param array $fields
     * @return string
     */
    private function sqlInsert($table, array $fields)
    {
        return sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s);",
            $table,
            implode(',', array_map(function ($el) {
                return "`$el`";
            }, array_keys($fields))),
            implode(',', array_map(function ($el) {
                return "?";
            }, array_keys($fields)))
        );
    }
}

<?php

/**
 * @package     notifications module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Notifications;

use Exception;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Module\Notifications\EmailQueueItem;
use Lynxlab\ADA\Module\Notifications\Notification;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMANotificationsDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_notifications_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\Notifications\\';

    private const EXCEPTIONCLASS = NotificationException::class;

    /**
     * insert or update a Notification
     *
     * @param array $saveData Notification object, as an array
     *
     * @return boolean|NotificationException
     */
    public function saveNotification($saveData)
    {
        $isUpate = array_key_exists('notificationId', $saveData) && !is_null($saveData['notificationId']);
        $saveData['lastEditTS'] = date('Y-m-d H:i:s');
        $this->beginTransaction();
        if ($isUpate) {
            $saveData['isActive'] = !$saveData['isActive'];
            $whereArr = ['notificationId' => $saveData['notificationId']];
            unset($saveData['notificationId']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    Notification::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['notificationId'] = $whereArr['notificationId'];
        } else {
            $saveData['isActive'] = true;
            $saveData['creationTS'] = date('Y-m-d H:i:s');
            $saveData['userId'] = $_SESSION['sess_userObj']->getId();
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    Notification::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        }

        if (!AMADB::isError($result)) {
            if (!$isUpate) {
                $saveData['notificationId'] = intval($this->lastInsertID());
            }
            $this->commit();
            return $saveData;
        } else {
            $this->rollBack();
            return new NotificationException($result->getMessage());
        }
    }

    /**
     * insert or update an email queue item
     *
     * @param array $saveData EmailQueueItem object, as an array
     *
     * @return boolean|NotificationException
     */
    public function saveEmailQueueItem($saveData)
    {
        $isUpate = array_key_exists('id', $saveData) && !is_null($saveData['id']);
        $this->beginTransaction();
        if ($isUpate) {
            $whereArr = ['id' => $saveData['id']];
            unset($saveData['id']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    EmailQueueItem::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['id'] = $whereArr['id'];
        } else {
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    EmailQueueItem::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        }

        if (!AMADB::isError($result)) {
            if (!$isUpate) {
                $saveData['id'] = intval($this->lastInsertID());
            }
            $this->commit();
            return $saveData;
        } else {
            $this->rollBack();
            return new NotificationException($result->getMessage());
        }
    }

    /**
     * insert some EmailQueueItems at once
     *
     * @param array $saveData array of EmailQueueItem objects, each one as an array
     *
     * @return boolean|NotificationException
     */
    public function multiSaveEmailQueueItems($insertData)
    {
        try {
            if (!$this->beginTransaction()) {
                throw new NotificationException(translateFN('Errore avvio transazione DB'));
            }
            $result = $this->queryPrepared(
                $this->insertMultiRow(
                    $insertData,
                    EmailQueueItem::TABLE
                ),
                array_values($insertData)
            );
            if (AMADB::isError($result)) {
                throw new NotificationException($result->getMessage());
            } else {
                $this->commit();
                return true;
            }
        } catch (Exception $e) {
            $this->rollBack();
            return $e;
        }
    }
}

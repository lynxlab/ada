<?php

/**
 * @package     badges module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Badges;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Module\Badges\Badge;
use Lynxlab\ADA\Module\Badges\CourseBadge;
use Lynxlab\ADA\Module\Badges\RewardedBadge;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMABadgesDataHandler extends AMADataHandler
{
    use WithInstance;
    use WithCUD;
    use WithFind;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_badges_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\Badges\\';

    private const EXCEPTIONCLASS = BadgesException::class;

    /**
     * get basic course and course instance info needed to build the
     * array of the course's badges
     *
     * @param integer $id_course_instance
     * @return array|\AMAError
     */
    public function getInstanceWithCourse($id_course_instance)
    {

        $sql = 'SELECT C.id_corso, C.titolo, IC.id_istanza_corso, ' .
            'IC.title, C.tipo_servizio, IC.tipo_servizio as `istanza_tipo_servizio` ' .
            'FROM modello_corso AS C, istanza_corso AS IC ' .
            'WHERE IC.id_istanza_corso = ? AND C.id_corso = IC.id_corso ';
        $result = $this->getAllPrepared($sql, [$id_course_instance], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            return new AMAError(AMA_ERR_GET);
        }
        return $result;
    }

    /**
     * Gets the count of the badges associated to a course
     *
     * @param int $courseId
     * @return int
     */
    public function getBadgesCountForCourse($courseId)
    {
        $sql = 'SELECT COUNT(`badge_uuid_bin`) FROM `' .
            self::PREFIX . 'course_badges` WHERE `id_corso`=?';
        $result = $this->getOnePrepared($sql, [$courseId]);
        return (AMADB::isError($result) ? 0 : intval($result));
    }

    /**
     * Gets the count of rewarded badges in an array having:
     * the key set to the student id and the value set to the count of rewarded badges
     *
     * pass id_corso, id_istanza_corso, id_utente in whereArr to filter the count
     *
     * @param array $whereArr
     * @return array
     */
    public function getRewardedBadgesCount(array $whereArr = [])
    {
        $sql = 'SELECT `id_utente`, COUNT(`badge_uuid_bin`) AS `awardedcount`' .
            ' FROM `' . self::PREFIX . 'rewarded_badges` WHERE `approved`=1 AND `issuedOn` IS NOT NULL';
        foreach ($whereArr as $key => $val) {
            $sql .= " AND `$key`=" . $val;
        }
        $sql .= ' GROUP BY `id_utente`';
        $result = $this->getAllPrepared($sql, [], AMA_FETCH_ASSOC);
        $retArr = [];
        if (!AMADB::isError($result) && is_array($result) && count($result) > 0) {
            foreach ($result as $ares) {
                $retArr[$ares['id_utente']] = intval($ares['awardedcount']);
            }
        }
        return $retArr;
    }

    /**
     * Saves a Course-Badge association
     *
     * @param array $saveData
     * @return BadgesException|int
     */
    public function saveCourseBadge($saveData)
    {
        if (array_key_exists('id_corso', $saveData)) {
            if (array_key_exists('id_conditionset', $saveData)) {
                if (array_key_exists('badge_uuid', $saveData) && Uuid::isValid($saveData['badge_uuid'])) {
                    $exists = $this->findBy(
                        'CourseBadge',
                        [
                            'id_corso' => $saveData['id_corso'],
                            // 'id_conditionset' => $saveData['id_conditionset'],
                            'badge_uuid' => $saveData['badge_uuid'],
                        ]
                    );

                    if (is_array($exists) && count($exists) >= 1) {
                        return new BadgesException(translateFN("L'associazione già esiste"));
                    }

                    $tmpuuid = Uuid::fromString($saveData['badge_uuid']);
                    $saveData['badge_uuid_bin'] = $tmpuuid->getBytes();
                    unset($saveData['badge_uuid']);
                    $result =  $this->executeCriticalPrepared($this->sqlInsert(CourseBadge::TABLE, $saveData), array_values($saveData));

                    if (AMADB::isError($result)) {
                        return new BadgesException($result->getMessage());
                    }
                    return $result;
                } else {
                    return new BadgesException(translateFN('Passare un id badge valido'));
                }
            } else {
                return new BadgesException(translateFN('Passare un id condizioni di completamento valido'));
            }
        } else {
            return new BadgesException(translateFN('Passare un id corso valido'));
        }
    }

    /**
     * Deletes a Course-Badge association
     *
     * @param array $saveData
     * @return BadgesException|bool
     */
    public function deleteCourseBadge($saveData)
    {
        $result = $this->queryPrepared(
            $this->sqlDelete(
                CourseBadge::TABLE,
                $saveData
            ),
            array_values($saveData)
        );

        if (!AMADB::isError($result)) {
            return true;
        } else {
            return new BadgesException($result->getMessage());
        }
    }

    /**
     * Saves a badge
     *
     * @param array $saveData
     * @return BadgesException|Badge
     */
    public function saveBadge($saveData)
    {
        if (array_key_exists('badgeuuid', $saveData)) {
            $isUpdate = true;
        } else {
            $isUpdate = false;
        }

        if (array_key_exists('badgefilefileNames', $saveData) && is_array($saveData['badgefilefileNames']) && count($saveData['badgefilefileNames']) === 1) {
            $badgepng = reset($saveData['badgefilefileNames']);
        }

        unset($saveData['badgefile']);
        unset($saveData['badgefilefileNames']);

        if (!is_dir(MODULES_BADGES_MEDIAPATH)) {
            $oldmask = umask(0);
            $dirok = mkdir(MODULES_BADGES_MEDIAPATH, 0o775, true);
            umask($oldmask);
            if ($dirok === false) {
                return new BadgesException(translateFN('Impossibile creare la directory bagdes!'));
            }
        }

        if (!$isUpdate) {
            $uuid = Uuid::uuid4();
            $saveData['uuid_bin'] = $uuid->getBytes();
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    Badge::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        } else {
            $uuid = Uuid::fromString($saveData['badgeuuid']);
            unset($saveData['badgeuuid']);
            $whereArr = ['uuid' => $uuid->toString()];
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    Badge::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['uuid_bin'] = $uuid->getBytes();
        }

        if (!AMADB::isError($result)) {
            $badge = new Badge($saveData);
            if (isset($badgepng)) {
                $this->moveBadgeFile($badgepng, strtoupper($badge->getUuid()) . '.png');
            }
            return $badge;
        } else {
            return new BadgesException($result->getMessage());
        }
    }

    /**
     * Deletes a Badge
     *
     * @param array $saveData
     * @return BadgesException|bool
     */
    public function deleteBadge($saveData)
    {
        /** @var Badge $badge */
        $badge = $this->findBy('Badge', ['uuid' => $saveData['uuid']]);
        if (is_array($badge) && count($badge) == 1) {
            $badge = reset($badge);
            $deletefile = str_replace(HTTP_ROOT_DIR, ROOT_DIR, $badge->getImageUrl());
        } else {
            $deletefile = '';
        }

        $result = $this->queryPrepared(
            $this->sqlDelete(
                Badge::TABLE,
                $saveData
            ),
            array_values($saveData)
        );

        if (!AMADB::isError($result)) {
            if (is_file($deletefile)) {
                unlink($deletefile);
            }
            return true;
        } else {
            return new BadgesException($result->getMessage());
        }
    }

    /**
     * Save a Rewarded badge object
     *
     * @param array $saveData
     * @return BageExecption|RewardedBadge
     */
    public function saveRewardedBadge($saveData)
    {
        if (array_key_exists('uuid', $saveData)) {
            $isUpdate = true;
            // it's an update, never update the issue timestamp
            if (isset($saveData['issuedOn'])) {
                unset($saveData['issuedOn']);
            }
        } else {
            // it's a new reward, set the timestamp to now and notified to false
            $isUpdate = false;
            $saveData['issuedOn'] = $this->dateToTs('now');
            $saveData['notified'] = 0;
        }

        $badgeUUid = Uuid::fromString($saveData['badge_uuid']);
        unset($saveData['badge_uuid']);
        $saveData['badge_uuid_bin'] = $badgeUUid->getBytes();

        if (!$isUpdate) {
            $uuid = Uuid::uuid4();
            // uuid_bin is only used when inserting, the uuid field (human readable) is MySql virtual generated
            $saveData['uuid_bin'] = $uuid->getBytes();
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    RewardedBadge::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
            unset($saveData['uuid_bin']);
            $saveData['uuid'] = $uuid->toString();
        } else {
            $uuid = Uuid::fromString($saveData['uuid']);
            unset($saveData['uuid']);
            $whereArr = ['uuid' => $uuid->toString()];
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    RewardedBadge::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['uuid_bin'] = $uuid->getBytes();
        }

        if (!AMADB::isError($result)) {
            $saveData['badge_uuid_bin'] = $badgeUUid->getBytes();
            $reward = new RewardedBadge($saveData);
            return $reward;
        } else {
            return new BadgesException($result->getMessage());
        }
    }

    /**
     * Move an uploaded badge png from tmp to actual badges dir
     *
     * @param string $src
     * @param string $dest
     * @return void
     */
    private function moveBadgeFile($src, $dest)
    {
        $src = ADA_UPLOAD_PATH . DIRECTORY_SEPARATOR . MODULES_BADGES_NAME . DIRECTORY_SEPARATOR . $src;
        $dest = MODULES_BADGES_MEDIAPATH . $dest;
        if (!is_dir(MODULES_BADGES_MEDIAPATH)) {
            $oldmask = umask(0);
            mkdir(MODULES_BADGES_MEDIAPATH, 0o775, true);
            umask($oldmask);
        }
        rename($src, $dest);
    }
}

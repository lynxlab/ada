<?php

/**
 * @package     badges module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Badges;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\BadgesBase;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * RewardedBadge class
 *
 * @author giorgio
 *
 */

class RewardedBadge extends BadgesBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE =  AMABadgesDataHandler::PREFIX . 'rewarded_badges';

    protected $uuid;
    protected $badge_uuid;
    protected $issuedOn;
    protected $approved;
    protected $notified;
    protected $id_utente;
    protected $id_corso;
    protected $id_istanza_corso;

    private static $instanceRewards = [];

    /**
     * Tells which properties are to be loaded using a kind-of-join
     *
     * @return array
     */
    public static function doNotLoad()
    {
        return ['instanceRewards'];
    }

    /**
     * Get the value of uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the value of uuid
     *
     * @return  self
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Set the value of uuid, binary version
     *
     * @param string $uuid
     *
     * @return self
     */
    public function setUuidBin($uuid)
    {
        $tmpuuid = Uuid::fromBytes($uuid);
        return $this->setUuid($tmpuuid->toString());
    }

    /**
     * Get the value of badge_uuid
     */
    public function getBadgeUuid()
    {
        return $this->badge_uuid;
    }

    /**
     * Set the value of badge_uuid
     *
     * @return  self
     */
    public function setBadgeUuid($badge_uuid)
    {
        $this->badge_uuid = $badge_uuid;

        return $this;
    }

    /**
     * Set the value of badge_uuid, binary version
     *
     * @param string $uuid
     *
     * @return self
     */
    public function setBadgeUuidBin($uuid)
    {
        $tmpuuid = Uuid::fromBytes($uuid);
        return $this->setBadgeUuid($tmpuuid->toString());
    }

    /**
     * Get the value of issuedOn
     */
    public function getIssuedOn()
    {
        return $this->issuedOn;
    }

    /**
     * Set the value of issuedOn
     *
     * @return  self
     */
    public function setIssuedOn($issuedOn)
    {
        $this->issuedOn = $issuedOn;

        return $this;
    }

    /**
     * Get the value of approved
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set the value of approved
     *
     * @return  self
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * Get the value of notified
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * Set the value of notified
     *
     * @return  self
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;

        return $this;
    }

    /**
     * Get the value of id_utente
     */
    public function getIdUtente()
    {
        return $this->id_utente;
    }

    /**
     * Set the value of id_utente
     *
     * @return  self
     */
    public function setIdUtente($id_utente)
    {
        $this->id_utente = $id_utente;

        return $this;
    }

    /**
     * Get the value of id_corso
     */
    public function getIdCorso()
    {
        return $this->id_corso;
    }

    /**
     * Set the value of id_corso
     *
     * @return  self
     */
    public function setIdCorso($id_corso)
    {
        $this->id_corso = $id_corso;

        return $this;
    }

    /**
     * Get the value of id_istanza_corso
     */
    public function getIdIstanzaCorso()
    {
        return $this->id_istanza_corso;
    }

    /**
     * Set the value of id_istanza_corso
     *
     * @return  self
     */
    public function setIdIstanzaCorso($id_istanza_corso)
    {
        $this->id_istanza_corso = $id_istanza_corso;

        return $this;
    }

    /**
     * approved getter, boolean version
     *
     * @return boolean
     */
    public function isApproved()
    {
        return (bool) $this->getApproved();
    }

    /**
     * notified getter, boolean version
     *
     * @return boolean
     */
    public function isNotified()
    {
        return (bool) $this->getNotified();
    }

    public static function buildStudentRewardHTML($courseId, $instanceId, $studentId)
    {
        $studentsRewards = self::getInstanceRewards()['studentsRewards'];
        $awbadges = array_key_exists($studentId, $studentsRewards) ? $studentsRewards[$studentId] : 0;
        $baseStr = $awbadges . ' ' . translateFN('su') . ' ' . self::getInstanceRewards()['total'];
        if ($awbadges > 0) {
            $retObj = CDOMElement::create('a', 'class:dontwrap,href:' . MODULES_BADGES_HTTP . '/user-badges.php?id_instance=' . $instanceId . '&id_course=' . $courseId . '&id_student=' . $studentId);
        } else {
            $retObj = CDOMElement::create('span');
        }
        $retObj->addChild(new CText($baseStr));
        return $retObj;
    }

    public static function loadInstanceRewards($courseId, $instanceId)
    {
        $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        $totalBadges = $bdh->getBadgesCountForCourse($courseId);
        $studentBadges = $bdh->getRewardedBadgesCount(['id_corso' => $courseId, 'id_istanza_corso' => $instanceId]);
        self::setInstanceRewards(['total' => $totalBadges, 'studentsRewards' => $studentBadges]);
        return self::getInstanceRewards();
    }

    /**
     * Get the value of instanceRewards
     */
    public static function getInstanceRewards()
    {
        return self::$instanceRewards;
    }

    /**
     * Set the value of instanceRewards
     */
    private static function setInstanceRewards($instanceRewards)
    {
        self::$instanceRewards = $instanceRewards;
    }
}

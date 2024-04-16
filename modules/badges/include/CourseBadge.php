<?php

/**
 * @package     badges module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Badges;

use Ramsey\Uuid\Uuid;

/**
 * Badge class
 *
 * @author giorgio
 *
 */
class CourseBadge extends BadgesBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE =  AMABadgesDataHandler::PREFIX . 'course_badges';

    protected $badge_uuid;
    protected $id_corso;
    protected $id_conditionset;

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
     * Get the value of id_conditionset
     */
    public function getIdConditionset()
    {
        return $this->id_conditionset;
    }

    /**
     * Set the value of id_conditionset
     *
     * @return  self
     */
    public function setIdConditionset($id_conditionset)
    {
        $this->id_conditionset = $id_conditionset;

        return $this;
    }
}

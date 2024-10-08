<?php

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

/**
 * Class for a GDPR manager allowed user
 *
 * @author giorgio
 */
class GdprUser extends GdprBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE =  AMAGdprDataHandler::PREFIX . 'users';
    public const KEY = 'id_utente';

    protected $id_utente;
    protected $type;

    private $dbToUse;

    /**
     * GdprUser constructor.
     *
     * @param array $data
     * @param AMAGdprDataHandler|GDPRApi $dbToUse
     */
    public function __construct($data = [], $dbToUse = null)
    {
        $this->type = [];
        $this->dbToUse = $dbToUse;
        parent::__construct($data, $dbToUse);
    }

    /**
     * Tells which properties are to be loaded using a kind-of-join
     *
     * @return string[]
     */
    public static function doNotLoad()
    {
        return ['dbToUse'];
    }

    /**
     * override fromArray method to handle type that must be
     * an array of GdprUserType objects
     *
     * {@inheritDoc}
     * @see \Lynxlab\ADA\Module\GDPR\GdprBase::fromArray()
     *
     * @param array $data
     * @param AMAGdprDataHandler|GDPRApi $dbToUse
     */
    public function fromArray($data = [], $dbToUse = null)
    {
        if (array_key_exists('type', $data)) {
            if (!is_array($data['type'])) {
                $data['type'] = [$data['type']];
            }
            foreach ($data['type'] as $gdprType) {
                $this->addType($gdprType, $dbToUse ?? $this->$dbToUse);
            }
            unset($data['type']);
        }
        return parent::fromArray($data);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($types)
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        foreach ($types as $type) {
            $this->addType($type);
        }
        return $this;
    }

    /**
     * Adds a GdprUserType to the object type property
     *
     * @param integer|GdprUserType $type
     * @param AMAGdprDataHandler|GDPRApi $dbToUse
     *
     * @return \Lynxlab\ADA\Module\GDPR\GdprUser
     */
    public function addType($type, $dbToUse = null)
    {
        if (!($type instanceof GdprUserType)) {
            if (is_null($dbToUse)) {
                $dbToUse = $this->dbToUse ?? new GdprAPI();
            }
            $type = $dbToUse->findBy('GdprUserType', ['id' => $type]);
            $type = reset($type);
        }
        if (false !== $type && $type->getId() != GdprUserType::NONE) {
            $this->type[] = $type;
        }
        return $this;
    }

    /**
     * Removes a GdprUserType to the object type property
     *
     * @param integer|GdprUserType $type
     * @return \Lynxlab\ADA\Module\GDPR\GdprUser
     */
    public function removeType($type, $dbToUse = null)
    {
        foreach ($this->getType() as $key => $aType) {
            if ($this->hasType($aType, $dbToUse)) {
                unset($this->type[$key]);
            }
        }
        return $this;
    }

    /**
     * Checks if GdprUser has the passed type
     *
     * @param integer|GdprUserType $type
     * @return boolean
     */
    private function hasType($type, $dbToUse = null)
    {
        if (!($type instanceof GdprUserType)) {
            if (is_null($dbToUse)) {
                $dbToUse = new GdprAPI();
            }
            $type = $dbToUse->findBy('GdprUserType', ['id' => $type]);
        }
        if (!is_array($type)) {
            $type = [$type];
        }
        foreach ($type as $aType) {
            if (in_array($aType, $this->getType())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if GdprUser has the passed types (as an array of integers or GdprUserType objects)
     *
     * @param array $types
     * @return boolean
     */
    public function hasTypes($types, $dbToUse = null)
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        foreach ($types as $type) {
            if ($this->hasType($type, $dbToUse)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getIdUtente()
    {
        return $this->id_utente;
    }

    /**
     * @param mixed $id_utente
     */
    public function setIdUtente($id_utente)
    {
        $this->id_utente = $id_utente;
        return $this;
    }
}

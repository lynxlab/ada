<?php

use Lynxlab\ADA\Module\GDPR\GdprUserType;

use Lynxlab\ADA\Module\GDPR\GdprBase;

use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;

// Trigger: ClassWithNameSpace. The class GdprUserType was declared with namespace Lynxlab\ADA\Module\GDPR. //

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\GDPR;

/**
 * Class for a GDPR request type
 *
 * @author giorgio
 */
class GdprUserType extends GdprBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE =  AMAGdprDataHandler::PREFIX . 'userTypes';

    /**
     * request types constants
     *
     * @var integer
     */
    public const NONE = 2;
    public const MANAGER = 1;

    protected $id;
    protected $description;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}

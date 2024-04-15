<?php

use Lynxlab\ADA\Main\User\ADAUser;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\User\ADALoggableUser;

// Trigger: ClassWithNameSpace. The class ADAPractitioner was declared with namespace Lynxlab\ADA\Main\User. //

/**
 * User classes
 *
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        user_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\User;

class ADAPractitioner extends ADALoggableUser
{
    protected $tariffa;
    protected $profilo;

    public function __construct($user_dataAr = [])
    {
        parent::__construct($user_dataAr);

        $this->tariffa = $user_dataAr['tariffa'] ?? null;
        $this->profilo = $user_dataAr['profilo'] ?? null;
        $this->isSuper = isset($user_dataAr['tipo']) && $user_dataAr['tipo'] == AMA_TYPE_SUPERTUTOR;
        /**
         * @author giorgio 10/apr/2015
         *
         * a supertutor is a tutor with the isSuper property set to true
         */
        if ($this->isSuper && $this->tipo == AMA_TYPE_SUPERTUTOR) {
            $this->tipo = AMA_TYPE_TUTOR;
        }
        $this->setHomePage(HTTP_ROOT_DIR . '/tutor/tutor.php');
        $this->setEditProfilePage('tutor/edit_tutor.php');
    }

    /**
     * converts the Practitioner to an ADAUser
     *
     * @return ADAUser
     */
    public function toStudent()
    {
        return new ADAUser(array_merge(['id' => $this->getId()], $this->toArray()));
    }

    /*
   * getters
    */
    public function getFee()
    {
        return $this->tariffa;
    }

    public function getProfile()
    {
        return $this->profilo;
    }

    public function isSuper()
    {
        return (bool) $this->isSuper;
    }

    /*
   * setters
    */
    public function setFee($fee)
    {
        $this->tariffa = $fee;
    }

    public function setProfile($profile)
    {
        $this->profilo = $profile;
    }

    public function fillWithArrayData($user_dataAr = null)
    {
        if (!is_null($user_dataAr)) {
            parent::fillWithArrayData($user_dataAr);

            $this->tariffa = $user_dataAr['tariffa'] ?? 0;
            $this->profilo = $user_dataAr['profilo'] ?? null;
        }
    }

    public function toArray()
    {
        $user_dataAr = parent::toArray();

        $user_dataAr['tariffa'] = $this->tariffa;
        $user_dataAr['profilo'] = $this->profilo;

        return $user_dataAr;
    }
}

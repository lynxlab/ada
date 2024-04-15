<?php

use Lynxlab\ADA\Main\User\ADAGuest;

use Lynxlab\ADA\Main\User\ADAGenericUser;

// Trigger: ClassWithNameSpace. The class ADAGuest was declared with namespace Lynxlab\ADA\Main\User. //

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

class ADAGuest extends ADAGenericUser
{
    public function __construct($user_dataHa = [])
    {
        $this->id_user         = 0;
        $this->nome            = 'guest';
        $this->cognome         = 'guest';
        $this->tipo            = AMA_TYPE_VISITOR;
        $this->email           = 'vito@lynxlab.com';
        $this->telefono        = 0;
        $this->username        = 'guest';
        $this->template_family =  ADA_TEMPLATE_FAMILY;
        $this->indirizzo       = null;
        $this->citta           = null;
        $this->provincia       = null;
        $this->nazione         = null;
        $this->codice_fiscale  = null;
        $this->birthdate       = null;
        $this->birthcity       = null;
        $this->birthprovince   = null;
        $this->sesso           = null;
        $this->telefono               = null;
        $this->stato                  = null;
        $this->lingua = 0;
        $this->timezone = 0;
        $this->cap             = null;
        $this->SerialNumber    = null;
        $this->avatar          = null;
        $this->testers = (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) ? [$GLOBALS['user_provider']] : [ADA_PUBLIC_TESTER];

        $this->setHomePage(HTTP_ROOT_DIR);
        $this->setEditProfilePage('');
    }
}

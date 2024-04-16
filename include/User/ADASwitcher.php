<?php

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

class ADASwitcher extends ADALoggableUser
{
    public function __construct($user_dataAr = [])
    {
        parent::__construct($user_dataAr);

        $this->setHomePage(HTTP_ROOT_DIR . '/switcher/switcher.php');
        $this->setEditProfilePage('switcher/edit_switcher.php');
    }
}

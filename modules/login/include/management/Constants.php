<?php

use Lynxlab\ADA\Module\Login\Constants;

// Trigger: ClassWithNameSpace. The class Constants was declared with namespace Lynxlab\ADA\Module\Login. //

/**
 * LOGIN MODULE
 *
 * @package     login module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2015-2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Login;

abstract class Constants
{
    /**
     * module's action codes
     */
    public const MODULES_LOGIN_EDIT_OPTIONSET = 1;
    public const MODULES_LOGIN_EDIT_LOGINPROVIDER = 2;
}

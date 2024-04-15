<?php

use Lynxlab\ADA\Module\Login\ILogin;

// Trigger: ClassWithNameSpace. The class ILogin was declared with namespace Lynxlab\ADA\Module\Login. //

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

interface ILogin
{
    public function doLogin($name, $pass, $remindMe, $language);
    public function getCDOMElement();
    public function getHtml();
}

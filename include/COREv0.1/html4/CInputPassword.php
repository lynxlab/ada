<?php

use Lynxlab\ADA\CORE\html4\CReadonlyTextInput;

use Lynxlab\ADA\CORE\html4\CInputPassword;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CInputPassword was declared with namespace Lynxlab\ADA\CORE\html4. //

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 *
 *@author vito
 */
class CInputPassword extends CReadonlyTextInput
{
    public function __construct()
    {
        $this->setAttribute('type', 'password');
    }
}

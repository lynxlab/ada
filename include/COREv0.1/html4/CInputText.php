<?php

use Lynxlab\ADA\CORE\html4\CReadonlyTextInput;

use Lynxlab\ADA\CORE\html4\CInputText;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CInputText was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CInputText extends CReadonlyTextInput
{
    public function __construct()
    {
        $this->setAttribute('type', 'text');
    }
}

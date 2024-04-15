<?php

use Lynxlab\ADA\CORE\html4\CSubmitInput;

use Lynxlab\ADA\CORE\html4\CInputElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CSubmitInput was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CSubmitInput extends CInputElement
{
    public function __construct()
    {
        $this->setAttribute('type', 'submit');
    }
}

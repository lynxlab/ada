<?php

use Lynxlab\ADA\CORE\html4\CTextInput;

use Lynxlab\ADA\CORE\html4\CReadonlyTextInput;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CReadonlyTextInput was declared with namespace Lynxlab\ADA\CORE\html4. //

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
abstract class CReadonlyTextInput extends CTextInput
{
    protected $readonly;
}

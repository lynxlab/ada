<?php

use Lynxlab\ADA\CORE\html4\CTFElement;

use Lynxlab\ADA\CORE\html4\CFocusableElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CTFElement was declared with namespace Lynxlab\ADA\CORE\html4. //

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
abstract class CTFElement extends CFocusableElement
{
    protected $tabindex;
}

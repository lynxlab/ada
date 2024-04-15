<?php

use Lynxlab\ADA\CORE\html4\CFocusableEmptyElement;

use Lynxlab\ADA\CORE\html4\CEmptyElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CFocusableEmptyElement was declared with namespace Lynxlab\ADA\CORE\html4. //

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
abstract class CFocusableEmptyElement extends CEmptyElement
{
    protected $onfocus;
    protected $onblur;
}

<?php

use Lynxlab\ADA\CORE\html4\CInputElement;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CATFEmptyElement;

// Trigger: ClassWithNameSpace. The class CInputElement was declared with namespace Lynxlab\ADA\CORE\html4. //

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
abstract class CInputElement extends CATFEmptyElement
{
    protected $name;
    protected $type;
    protected $disabled;
    protected $onselect;
    protected $size;
    protected $usemap;
    protected $ismap;
    protected $src;
    protected $alt;
    protected $onchange;
    protected $value;
}

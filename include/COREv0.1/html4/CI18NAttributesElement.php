<?php

use Lynxlab\ADA\CORE\html4\CI18NAttributesElement;

use Lynxlab\ADA\CORE\html4\CBaseElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CI18NAttributesElement was declared with namespace Lynxlab\ADA\CORE\html4. //

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 * abstract class I18NAttributesElement: this class defines base methods common to all
 * of the DOM elements.
 *
 * @author vito
 */
abstract class CI18NAttributesElement extends CBaseElement
{
    protected $lang;
    protected $dir;
}

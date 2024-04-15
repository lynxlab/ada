<?php

use Lynxlab\ADA\CORE\html4\CFrameElement;

use Lynxlab\ADA\CORE\html4\CCoreAttributesElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CFrameElement was declared with namespace Lynxlab\ADA\CORE\html4. //

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

use ReflectionObject;
use ReflectionProperty;

/**
 * abstract class CoreAttributesElement: this class defines base methods common to all
 * of the DOM elements.
 *
 * @author vito
 */
abstract class CFrameElement extends CCoreAttributesElement
{
    protected $longdesc;
    protected $name;
    protected $src;
    protected $frameborder;
    protected $marginwidth;
    protected $marginheight;
    protected $noresize;
    protected $scrolling;
}

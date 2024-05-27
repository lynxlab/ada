<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

use Lynxlab\ADA\CORE\html4\CCoreAttributesElement;

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

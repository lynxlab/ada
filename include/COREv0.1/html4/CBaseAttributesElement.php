<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 * abstract class CBaseElement: this class defines base methods common to all
 * of the DOM elements.
 *
 * @author vito
 */
abstract class CBaseAttributesElement extends CBaseElement
{
    protected $id;
    protected $class;

    protected $lang;
    protected $dir;

    protected $title;
    protected $style;

    protected $onclick;
    protected $ondblclick;
    protected $onmousedown;
    protected $onmouseup;
    protected $onmouseover;
    protected $onmousemove;
    protected $onmouseout;
    protected $onkeypress;
    protected $onkeydown;
    protected $onkeyup;
    protected $role;
    protected $datas;

    public function __construct()
    {
    }
}

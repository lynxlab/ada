<?php

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
class CImg extends CEmptyElement
{
    protected $src;
    protected $alt;
    protected $longdesc;
    protected $name;
    protected $height;
    protected $width;
    protected $usemap;
    protected $ismap;

    public function __construct()
    {
        parent::__construct();
    }
}

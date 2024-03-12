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
class CLink extends CEmptyElement
{
    protected $charset;
    protected $href;
    protected $hreflang;
    protected $type;
    protected $rel;
    protected $rev;
    protected $media;

    public function __construct()
    {
        parent::__construct();
    }
}

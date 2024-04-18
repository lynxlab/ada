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
class CA extends CATFElement
{
    protected $charset;
    protected $type;
    protected $name;
    protected $href;
    protected $hreflang;
    protected $rel;
    protected $rev;
    protected $shape;
    protected $coords;
    protected $target;

    public function __construct()
    {
        parent::__construct();
        $this->addRejected(CA::class);
    }
}

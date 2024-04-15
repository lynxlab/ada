<?php

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CATFEmptyElement;

use Lynxlab\ADA\CORE\html4\CArea;

// Trigger: ClassWithNameSpace. The class CArea was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CArea extends CATFEmptyElement
{
    protected $shape;
    protected $coords;
    protected $href;
    protected $nohref;
    protected $alt;

    public function __construct()
    {
        parent::__construct();
    }
}

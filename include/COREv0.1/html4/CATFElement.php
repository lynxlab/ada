<?php

use Lynxlab\ADA\CORE\html4\CFocusableElement;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CATFElement;

// Trigger: ClassWithNameSpace. The class CATFElement was declared with namespace Lynxlab\ADA\CORE\html4. //

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
abstract class CATFElement extends CFocusableElement
{
    protected $accesskey;
    protected $tabindex;

    public function __construct()
    {
        parent::__construct();
    }
}

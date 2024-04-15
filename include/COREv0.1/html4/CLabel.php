<?php

use Lynxlab\ADA\CORE\html4\CLabel;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CAFElement;

// Trigger: ClassWithNameSpace. The class CLabel was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CLabel extends CAFElement
{
    protected $for;

    public function __construct()
    {
        parent::__construct();
        $this->addRejected('CLabel');
    }
}

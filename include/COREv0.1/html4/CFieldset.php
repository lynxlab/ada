<?php

use Lynxlab\ADA\CORE\html4\CFieldset;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CFieldset was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CFieldset extends CElement
{
    public function __construct()
    {
        parent::__construct();
        // TODO: chiamare addAccepted? Legend, %flow
    }
}

<?php

use Lynxlab\ADA\CORE\html4\CFrameElement;

use Lynxlab\ADA\CORE\html4\CFrame;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CFrame was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CFrame extends CFrameElement
{
    public function __construct()
    {
        parent::__construct();
    }
}

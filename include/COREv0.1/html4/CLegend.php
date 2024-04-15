<?php

use Lynxlab\ADA\CORE\html4\CLegend;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CAccesskeyElement;

// Trigger: ClassWithNameSpace. The class CLegend was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CLegend extends CAccesskeyElement
{
    public function __construct()
    {
        parent::__construct();
    }
}

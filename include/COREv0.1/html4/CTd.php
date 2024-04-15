<?php

use Lynxlab\ADA\CORE\html4\CTd;

use Lynxlab\ADA\CORE\html4\CTableCellElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CTd was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CTd extends CTableCellElement
{
    public function __construct()
    {
        parent::__construct();
    }
}

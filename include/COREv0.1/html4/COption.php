<?php

use Lynxlab\ADA\CORE\html4\CSelectElement;

use Lynxlab\ADA\CORE\html4\COption;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class COption was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class COption extends CSelectElement
{
    protected $disabled;
    protected $selected;
    protected $value;

    public function __construct()
    {
        parent::__construct();
    }
}

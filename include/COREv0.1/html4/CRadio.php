<?php

use Lynxlab\ADA\CORE\html4\CRadio;

use Lynxlab\ADA\CORE\html4\CCheckableInput;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CRadio was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CRadio extends CCheckableInput
{
    public function __construct()
    {
        parent::__construct();
        $this->setAttribute('type', 'radio');
    }
}

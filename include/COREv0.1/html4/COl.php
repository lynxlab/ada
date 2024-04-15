<?php

use Lynxlab\ADA\CORE\html4\COl;

use Lynxlab\ADA\CORE\html4\CLi;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class COl was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class COl extends CElement
{
    protected $start;
    public function __construct()
    {
        parent::__construct();
        $this->addAccepted('CLi');
    }
}

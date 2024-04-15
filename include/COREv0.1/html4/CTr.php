<?php

use Lynxlab\ADA\CORE\html4\CTr;

use Lynxlab\ADA\CORE\html4\CTh;

use Lynxlab\ADA\CORE\html4\CTd;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CAlignableElement;

// Trigger: ClassWithNameSpace. The class CTr was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CTr extends CAlignableElement
{
    public function __construct()
    {
        parent::__construct();
        $this->addAccepted('CTh');
        $this->addAccepted('CTd');
    }
}

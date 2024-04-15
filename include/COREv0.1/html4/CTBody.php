<?php

use Lynxlab\ADA\CORE\html4\CTr;

use Lynxlab\ADA\CORE\html4\CTBody;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CAlignableElement;

// Trigger: ClassWithNameSpace. The class CTBody was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CTBody extends CAlignableElement
{
    public function __construct()
    {
        parent::__construct();
        $this->addAccepted('CTr');
    }
}

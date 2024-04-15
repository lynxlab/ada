<?php

use Lynxlab\ADA\CORE\html4\CCol;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CAlignableEmptyElement;

// Trigger: ClassWithNameSpace. The class CCol was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CCol extends CAlignableEmptyElement
{
    protected $span;
    protected $width;

    public function __construct()
    {
        parent::__construct();
    }
}

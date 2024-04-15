<?php

use Lynxlab\ADA\CORE\html4\CIFrame;

use Lynxlab\ADA\CORE\html4\CFrameElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CIFrame was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CIFrame extends CFrameElement
{
    protected $align;
    protected $height;
    protected $width;

    public function __construct()
    {
        parent::__construct();
    }
}

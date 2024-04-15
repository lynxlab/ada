<?php

use Lynxlab\ADA\CORE\html4\CTextarea;

use Lynxlab\ADA\CORE\html4\CSelect;

use Lynxlab\ADA\CORE\html4\CLabel;

use Lynxlab\ADA\CORE\html4\CForm;

use Lynxlab\ADA\CORE\html4\CFieldset;

use Lynxlab\ADA\CORE\html4\CButton;

use Lynxlab\ADA\CORE\html4\CBase;

use Lynxlab\ADA\CORE\html4\CATFElement;

use Lynxlab\ADA\CORE\html4\CA;

// Trigger: ClassWithNameSpace. The class CButton was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CButton extends CATFElement
{
    protected $name;
    protected $value;
    protected $type;
    protected $disabled;

    public function __construct()
    {
        parent::__construct();
        $this->addRejected('CA');
        $this->addRejected('CInput');
        $this->addRejected('CSelect');
        $this->addRejected('CTextarea');
        $this->addRejected('CLabel');
        $this->addRejected('CButton');
        $this->addRejected('CForm');
        $this->addRejected('CFieldset');
    }
}

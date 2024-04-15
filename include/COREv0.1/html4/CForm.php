<?php

use Lynxlab\ADA\CORE\html4\CForm;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\CORE\html4\CBase;

// Trigger: ClassWithNameSpace. The class CForm was declared with namespace Lynxlab\ADA\CORE\html4. //

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
class CForm extends CElement
{
    protected $action;
    protected $method;
    protected $enctype;
    protected $accept;
    protected $name;
    protected $onsubmit;
    protected $onreset;
    protected $accept_charset;

    public function __construct()
    {
        parent::__construct();
        // TODO: chiamare addAccepted per %block, Script
        $this->addRejected('CForm');
    }
}

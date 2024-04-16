<?php

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

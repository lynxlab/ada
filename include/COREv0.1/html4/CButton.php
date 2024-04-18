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
        $this->addRejected(CA::class);
        $this->addRejected(CInputElement::class);
        $this->addRejected(CSelect::class);
        $this->addRejected(CTextarea::class);
        $this->addRejected(CLabel::class);
        $this->addRejected(CButton::class);
        $this->addRejected(CForm::class);
        $this->addRejected(CFieldset::class);
    }
}

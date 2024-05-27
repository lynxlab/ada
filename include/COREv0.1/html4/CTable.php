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
class CTable extends CElement
{
    protected $summary;
    protected $width;
    protected $border;
    protected $frame;
    protected $rules;
    protected $cellspacing;
    protected $cellpadding;

    public function __construct()
    {
        parent::__construct();
        $this->addAccepted(CCaption::class);
        $this->addAccepted(CCol::class);
        $this->addAccepted(CColgroup::class);
        $this->addAccepted(CTHead::class);
        $this->addAccepted(CTFoot::class);
        $this->addAccepted(CTBody::class);
    }
}

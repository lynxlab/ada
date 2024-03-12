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
        $this->addAccepted('CCaption');
        $this->addAccepted('CCol');
        $this->addAccepted('CColgroup');
        $this->addAccepted('CTHead');
        $this->addAccepted('CTFoot');
        $this->addAccepted('CTBody');
    }
}

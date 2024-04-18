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
class CSelect extends CTFElement
{
    protected $size;
    protected $name;
    protected $multiple;
    protected $disabled;
    protected $onchange;

    public function __construct()
    {
        parent::__construct();
        $this->addAccepted(COptgroup::class);
        $this->addAccepted(COption::class);
    }
}

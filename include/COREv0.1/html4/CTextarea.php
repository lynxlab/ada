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
class CTextarea extends CATFElement
{
    protected $name;
    protected $cols;
    protected $rows;
    protected $disabled;
    protected $readonly;
    protected $onselect;
    protected $onchange;

    public function __construct()
    {
        parent::__construct();
    }
}

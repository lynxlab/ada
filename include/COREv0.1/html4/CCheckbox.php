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
class CCheckbox extends CCheckableInput
{
    public function __construct()
    {
        parent::__construct();
        $this->setAttribute('type', 'checkbox');
    }
}

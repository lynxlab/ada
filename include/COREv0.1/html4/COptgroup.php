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
class COptgroup extends CSelectElement
{
    public function __construct()
    {
        parent::__construct();
        $this->addAccepted('COption');
    }
}
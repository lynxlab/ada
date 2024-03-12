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
class CFileInput extends CInputElement
{
    protected $accept;

    public function __construct()
    {
        $this->setAttribute('type', 'file');
    }
}

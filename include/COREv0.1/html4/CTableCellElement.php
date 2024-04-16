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
abstract class CTableCellElement extends CAlignableElement
{
    protected $abbr;
    protected $axis;
    protected $header;
    protected $scope;
    protected $rowspan;
    protected $colspan;
}

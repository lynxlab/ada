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
class CTObject extends CTabindexElement
{
    protected $declare;
    protected $classid;
    protected $codebase;
    protected $data;
    protected $type;
    protected $codetype;
    protected $archive;
    protected $standby;
    protected $height;
    protected $width;
    protected $usemap;
    protected $name;

    public function __construct()
    {
        parent::__construct();
    }
}

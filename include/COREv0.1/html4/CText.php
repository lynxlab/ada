<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 * class CText
 *
 * @author vito
 */
class CText extends CBase
{
    private $t;

    public function __construct($text)
    {
        $this->t = $text;
    }

    public function getHtml()
    {
        return $this->t;
    }
}

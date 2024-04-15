<?php

use Lynxlab\ADA\CORE\HtmlElements\Tform;

use Lynxlab\ADA\CORE\HtmlElements\Table;

use Lynxlab\ADA\CORE\HtmlElements\HTMLElement;

use Lynxlab\ADA\CORE\HtmlElements\Form;

// Trigger: ClassWithNameSpace. The class HTMLElement was declared with namespace Lynxlab\ADA\CORE\HtmlElements. //

/**
 * Html_element, Table, Ilist, Form and Tform classes
 *
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\CORE\HtmlElements;

class HTMLElement
{
    public $data;
    public $error;

    public function printElement()
    {
        if (empty($this->error) and (!empty($this->data))) {
            print $this->data;
        }
    }

    public function getElement()
    {
        if (empty($this->error) and (!empty($this->data))) {
            return $this->data;
        }
    }

    public function getError()
    {
        if (!empty($this->error)) {
            return $this->error;
        }
    }
}

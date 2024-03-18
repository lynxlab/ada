<?php

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

namespace Lynxlab\ADA\CORE\HmtlElements;

class HTMLElement
{
    public $data;
    public $error;

    public function print_element()
    {
        if (empty($this->error) and (!empty($this->data))) {
            print $this->data;
        }
    }

    public function get_element()
    {
        if (empty($this->error) and (!empty($this->data))) {
            return $this->data;
        }
    }

    public function get_error()
    {
        if (!empty($this->error)) {
            return $this->error;
        }
    }
}

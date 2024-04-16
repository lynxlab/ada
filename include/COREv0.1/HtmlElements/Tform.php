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

namespace Lynxlab\ADA\CORE\HtmlElements;

use Lynxlab\ADA\CORE\HtmlElements\Form;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Tform extends Form
{
    public $name;
    public $target;

    public function setForm($dataHa, $name = "")
    {

        if (!empty($name)) {
            $fname = $name;
        } else {
            $fname = $this->name;
        }
        if ((empty($dataHa)) or (gettype($dataHa) != 'array')) {
            $this->error = translateFN("I dati non sono validi");
        } else {
            $str = "<form name=\"$fname\" method=\"$this->method\" action=\"$this->action\" enctype=\"$this->enctype\" target=\"$this->target\">\r\n";
            $str .= "<table>\n";

            foreach ($dataHa as $riga) {
                if (!strstr($state, 'submit')) {
                    $str .= "<tr>\n";
                }

                foreach ($riga as $campo => $valore) {
                    switch ($campo) {
                        case 'label':
                            $str .= "<td>$valore</td>\n";
                            break;
                        case 'type':
                            switch ($valore) {
                                case 'textarea':
                                    $str .= "<td><textarea";
                                    $state = 'textarea';
                                    break;
                                case 'button':
                                    $str .= "<td><button";
                                    $state = 'button';
                                    break;
                                case 'reset': // reset is compulsory !!! otherwise row will not get closed
                                    $state = 'input';
                                    $str .= "&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"$valore\"";
                                    break;
                                case 'submit':
                                    $state = 'input submit';
                                    $str .= "<td><input type=\"$valore\"";
                                    break;
                                case 'text':
                                case 'password':
                                case 'radio':
                                case 'checkbox':
                                    $state = 'input';
                                    $str .= "<td><input type=\"$valore\"";
                                    break;
                                case 'hidden':
                                    $state = 'input hidden';
                                    $str .= "<td><input type=\"$valore\"";
                                    break;
                                case 'select':
                                    $state = 'select';
                                    $str .= "<td><select ";
                            }
                            break;
                        case 'name':
                            $str .= " name=\"$valore\"";
                            if ($state == 'select') {
                                $str .= ">\n";
                            }
                            break;
                        case 'checked':
                            if ($valore != "") {
                                $str .= " checked=\"$valore\"";
                            }
                            break;
                        case 'disabled':
                            if ($valore != "") {
                                $str .= " disabled=\"$valore\"";
                            }
                            break;
                        case 'readonly':
                            if ($valore != "") {
                                $str .= " readonly=\"$valore\"";
                            }
                            break;
                        case 'value':
                            switch ($state) {
                                case 'textarea':
                                    $textarea_value = $valore;
                                    break;
                                case 'button':
                                    $button_value = $valore;
                                    break;
                                case 'select':
                                    foreach ($valore as $val) {
                                        $str .= "<option value='$val'>$val</option>\n";
                                    }
                                    $str .= " </select>\n ";
                                    break;
                                default:
                                    $str .= " value =\"$valore\"";
                            }
                            break;
                        case 'size':
                            $str .= " size =\"$valore\"";
                            break;
                        case 'maxlength':
                            $str .= " maxlength=\"$valore\"";
                            break;
                        case 'rows':
                            $str .= " rows =\"$valore\"";
                            break;
                        case 'cols':
                            $str .= " cols =\"$valore\"";
                            break;
                        case 'wrap':
                            $str .= " wrap =\"$valore\"";
                            break;
                        case 'onClick':
                            $str .= " onClick =\"$valore\"";
                            break;
                    }
                }
                switch ($state) {
                    case 'textarea':
                        $str .= ">$textarea_value</textarea></td>\r\n";
                        $str .= "</tr>\r\n";
                        break;
                    case 'button':
                        $str .= ">$button_value</button></td>\r\n";
                        $str .= "</tr>\r\n";
                        break;
                    case 'select':
                        $str .= "</td>\r\n";
                        $str .= "</tr>\r\n";
                        break;
                        //case 'input':
                    default:
                        if (strstr($state, 'submit')) {
                            $str .= ">";
                        } else {
                            $str .= "></td>\r\n";
                            $str .= "</tr>\r\n";
                        }
                }
            }
            $str .= "</table>\r\n";
            $str .= "</form>\r\n";

            $this->data = $str;
        }
    }
}

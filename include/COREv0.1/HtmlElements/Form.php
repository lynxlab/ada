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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

class Form extends HTMLElement
{
    /*
Classe per la costruzione di form HTML.
Il parametro $data dev'essere un array associativo con chiavi type,name,label,size,rows,col,wrap,maxlength,value
Se i dati non sono corretti restituisce null e setta la variabile error.

Esempio di chiamata:
$data = array(
                     array(
                          'label'=>'username',
                          'type'=>'text',
                          'name'=>'username',
                          'size'=>'20',
                          'maxlenght'=>'40'
                          ),
                     array(
                          'label'=>'password',
                          'type'=>'password',
                          'name'=>'password',
                          'size'=>'20',
                          'maxlength'=>'40'
                          ),
                     array(
                          'label'=>'',
                          'type'=>'submit',
                          'name'=>'Submit',
                          'value'=>'Clicca qui'
                          )

                    );
$f = new Form();
$f->initForm("http://altrascuola.it/ada/pippo.php","GET","Pippo");
$f-> setForm($data);
$f->printForm();
*/


    public $action;
    public $method;
    public $enctype;

    public function __construct()
    {
        // per default prende il nome del file chiamante
        //      $action =  array_pop(split('[/\\]',$PHP_SELF));  // = index

        $action = whoami() . ".php";
        $this->initForm($action);
    }

    public function initForm($action, $method = 'POST', $enctype = "application/x-www-form-urlencoded")
    {
        if (!empty($action)) {
            $this->action = $action;
        }
        $this->method = $method;
        $this->enctype = $enctype;
    }

    public function setForm($dataHa, $name = "Form1")
    {

        if ((empty($dataHa)) or (gettype($dataHa) != 'array')) {
            $this->error = translateFN("I dati non sono validi");
        } else {
            $str = "<form method=\"$this->method\" action=\"$this->action\" enctype=\"$this->enctype\">\r\n";

            foreach ($dataHa as $riga) {
                foreach ($riga as $campo => $valore) {
                    switch ($campo) {
                        case 'label':
                            $str .= "$valore";
                            break;
                        case 'type':
                            switch ($valore) {
                                case 'textarea':
                                    $str .= "<textarea";
                                    $state = 'textarea';
                                    break;
                                case 'submit':
                                case 'text':
                                case 'password':
                                case 'radio':
                                case 'checkbox':
                                case 'reset':
                                    $state = 'input';
                                    $str .= " <input type=\"$valore\"";
                                    break;
                                case 'hidden':
                                    $state = 'input hidden';
                                    $str .= " <input type=\"$valore\"";
                                    break;
                                case 'select':
                                    $state = 'select';
                                    $str .= " <select ";
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
                        case 'value':
                            switch ($state) {
                                case 'textarea':
                                    $textarea_value = $valore;
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
                    }
                }
                switch ($state) {
                    case 'textarea':
                        $str .= ">$textarea_value</textarea><br><br>\r\n";
                        break;
                    case 'select':
                        $str .= "<br><br>\r\n";
                        break;
                        //case 'input':
                    default:
                        if (strstr($state, 'hidden')) {
                            $str .= ">\r\n";
                        } else {
                            $str .= "><br><br>\r\n";
                        }
                }
            }
            $str .= "</form>\r\n";

            $this->data = $str;
        }
    }

    public function printFrom()
    {
        return $this->print_element();
    }

    public function getForm()
    {
        return $this->get_element();
    }


    // end class Form
}

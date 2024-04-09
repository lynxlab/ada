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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class IList extends HTMLElement
{
    /*
Classe per la costruzione di liste HTML
Il parametro $data dev'essere un array anche multiplo
Se i dati non sono corretti restituisce null e setta la variabile error.

Esempio di chiamata:
  $data = array(
             'pippo',
             'pluto',
             $nipotiniAr,
             'paperino'
              );

$lObj = new IList();
$lObj->initList('1','a',3);
$lObj->setList($data);
$lObj->printList();

oppure:
$var = $lObj->getList();

*/

    public $type; // disc, square, circle
    public $start_tag;
    public $end_tag;
    public $ordered;
    public $startvalue;
    public $style; // a css class


    public function __construct()
    {
        $this->initList();
    }

    public function initList($ordered = '0', $type = '', $startvalue = 1, $style = "default")
    {
        $this->ordered = $ordered;
        $this->startvalue = $startvalue;
        $this->style = $style;
        if ($ordered) {
            $this->start_tag = "<OL class='$style' start='$startvalue'>\n";
            $this->end_tag = "</OL>\n";
        } else {
            $this->start_tag = "<UL class='$style'>\n";
            $this->end_tag = "</UL>\n";
        }
        if (!empty($type)) {
            $this->type = $type;
        } elseif ($ordered) {
            $this->type = '1';
        } else {
            $this->type = 'disc';
        }
    }

    public function setList($data)
    {
        if (gettype($data) != 'array') {
            $this->error = translateFN("Il formato dei dati non &egrave; valido");
        } else {
            $str = $this->start_tag;
            foreach ($data as $riga) {
                if (is_array($riga)) {
                    $lObj = new IList();
                    $lObj->initList($this->ordered, $this->type, $this->startvalue);
                    $lObj->setList($riga);
                    $str .= $lObj->getList();
                } else {
                    if ($this->type) {
                        $str .= "<li class=\"" . $this->style . "_li\" type=" . $this->type . ">$riga</li>\n";
                    } else {
                        $str .= "<li>$riga</li>\n";
                    }
                }
            }
            $str .= $this->end_tag;
            $this->data = $str;
        }
    }

    public function printList()
    {
        return $this->print_element();
    }

    public function getList()
    {
        return $this->get_element();
    }



    // end class IList
}

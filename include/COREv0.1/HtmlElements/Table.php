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

class Table extends HTMLElement
{
    /*
Classe per la costruzione di tabelle HTML
Il parametro $data dev'essere un array associativo con chiavi  uguali ai nomi delle colonne
Se i dati non sono corretti restituisce null e setta la variabile error.

Esempio di chiamata:
  $data = array(
              array('nome'=>'fghj','cognome'=>'sdfg','et�=>'11'),
              array('nome'=>'sdfj','cognome'=>'ghj','et�=>'22'),
              array('nome'=>'fghj','cognome'=>'hjk','et�=>'33')
              );

$t = new Table();
$t->initTable('1','left','2','1','70%');
$t->setTable($data);
$t->printTable();
*/

    public $border;
    public $align;
    public $cellspacing;
    public $cellpadding;
    public $width;
    public $col1;
    public $bcol1;
    public $col2;
    public $bcol2;
    public $id;


    public function __construct()
    {
        $this->initTable();
    }

    public function initTable(
        $border = '?',
        $align = '',
        $cellspacing = '',
        $cellpadding = '',
        $width = '',
        $col1 = '',
        $bcol1 = '',
        $col2 = '',
        $bcol2 = '',
        $labelcol = '',
        $labelrow = '',
        $rules = '',
        $style = 'default',
        $id = null
    ) {
        if ($border == "?") {
            // no specified parameter
            /*
                        $rootdir = $GLOBALS['root_dir'];
                        @include("$rootdir/templates/tables.inc.php");
                        $table_style = 'default';
                        if (isset($tableParametersHa) AND array_key_exists($table_style,$tableParametersHa)){
                           $border= $tableParametersHa[$table_style]['border'];
                           $align=$tableParametersHa[$table_style]['align'];
                           $cellspacing=$tableParametersHa[$table_style]['cellspacing'];
                           $cellpadding=$tableParametersHa[$table_style]['cellpadding'];
                           $width= $tableParametersHa[$table_style]['width'];
                           $col1=$tableParametersHa[$table_style]['col1'];
                           $bcol1=$tableParametersHa[$table_style]['bcol1'];
                           $col2=$tableParametersHa[$table_style]['col2'];
                           $bcol2=$tableParametersHa[$table_style]['bcol2'];
                           $labelcol = $tableParametersHa[$table_style]['labelcol'];
                           $labelrow=$tableParametersHa[$table_style]['labelrow'];
                           $rules=$tableParametersHa[$table_style]['rules'];
                        } else {
                        // no default parameter file found, using hardcoded parameters
                           $border= 1;
                           $align="center";
                           $cellspacing=0;
                           $cellpadding=1;
                           $width= '90%';
                           $col1='';
                           $bcol1='white';
                           $col2='';
                           $bcol2='white';
                           $labelcol = '';
                           $labelrow='';
                           $rules='';
                        }
            */
            // no default parameter file found, using hardcoded parameters
            $border = 1;
            $align = "center";
            $cellspacing = 0;
            $cellpadding = 1;
            $width = '100%';
            $col1 = '';
            $bcol1 = '';
            $col2 = '';
            $bcol2 = '';
            $labelcol = '';
            $labelrow = '';
            $rules = '';
        }

        // setting object variables
        $this->style = $style;
        $this->border = (int)$border;
        $this->align = $align;
        $this->cellspacing = (int)$cellspacing;
        $this->cellpadding = (int)$cellpadding;
        $this->width = $width;
        $this->id = $id;

        if (!empty($col1)) {
            $this->col1 = $col1;
        }
        if (!empty($col2)) {
            $this->col2 = $col2;
        }
        if (!empty($bcol1)) {
            $this->bcol1 = $bcol1;
        }
        if (!empty($bcol2)) {
            $this->bcol2 = $bcol2;
        }

        $this->labelcol = $labelcol;
        $this->labelrow = $labelrow;
        if (!empty($rules)) {
            $this->rules = $rules;
        } else {
            $this->rules = 'groups';
        }
    }

    public function setTable($data, $caption = "Tabella", $summary = "Tabella")
    {
        if (gettype($data) != 'array') {
            $this->error = translateFN("Il formato dei dati non &egrave; valido");
        } else {
            if (count($data)) {
                $firstKey = key($data);
                $riga = $data[$firstKey];
                $totcol = count($riga);
                // vito, 18 feb 2009
                //$str = "<table class=\"".$this->style."_table\" rules=\"".$this->rules."\" summary=\"$summary\" width=\"".$this->width."\" cellspacing =\"".$this->cellspacing."\" cellpadding =\"".$this->cellpadding."\" border=\"".$this->border."\" align=\"".$this->align."\">\r\n";
                $idTable = ' ';
                if ($this->id != null) {
                    $idTable = ' id=' . $this->id;
                }
                $str = "<table class=\"" . $this->style . "_table\" summary=\"$summary\"" . $idTable . ">\r\n";
                //             $str = "<table class=\"".$this->style."_table\" summary=\"$summary\">\r\n";
                $str .= "<caption>$caption</caption>\r\n";
                if ($this->labelcol) {
                    // Colgroups
                    $str .= "<colgroup>\r\n\t";
                    for ($c = 0; $c <= $totcol; $c++) {
                        $str .= "<col id=\"c$c\" />";
                        // $str.="<col>";
                    }


                    $str .= "\r\n</colgroup>\r\n";
                    // Headers
                    // vito, 18 feb 2008
                    //$str.="<thead class=\"".$this->style."_thead\" align=\"".$this->align.">\"";
                    $str .= "<thead class=\"" . $this->style . "_thead\">";
                    $str .= "\t<tr>\r\n";

                    reset($data);
                    $firstKey = key($data);
                    $riga = $data[$firstKey];
                    // $riga = $data[0];
                    $str .= "\t<th class=\"" . $this->style . "_th\">&nbsp;</th>";
                    $h = 0;
                    if (is_array($riga)) {
                        foreach ($riga as $key => $value) {
                            $h++;
                            if (!empty($this->labelcol)) {
                                // $str .= "<th id=a$h>$key</th>";
                                $str .= "<th class=\"" . $this->style . "_th\">$key</th>";
                            } else {
                                // $str .= "<th id=a$h>&nbsp;</th>";
                                $str .= "<th>&nbsp;</th>";
                            }
                        }
                    }
                    $str .= "\t</tr>\r\n";
                    $str .= "\r\n</thead>\r\n";
                } else {
                    $str .= "<thead></thead>\r\n";
                }
                $str .= "<tbody>\r\n";
                reset($data);
                $r = 0;

                foreach ($data as $riga) {
                    $r++;
                    if (gettype($r / 2) == 'integer') {
                        if (!empty($this->col1)) {
                            $str .= "\t<tr style=\"color:" . $this->col1 . ";\"  bgcolor=\"" . $this->bcol1 . "\">";
                        } else {
                            $str .= "\t<tr class=\"" . $this->style . "_tr_odd\">";
                        }
                    } else {
                        if (!empty($this->col2)) {
                            $str .= "\t<tr style=\"color:" . $this->col2 . ";\"  bgcolor=\"" . $this->bcol2 . "\">";
                        } else {
                            $str .= "\t<tr class=\"" . $this->style . "_tr_even\">";
                        }
                    }
                    $str .= "\r\n\t\t";
                    if ($this->labelrow) {
                        $str .= "<td class=\"" . $this->style . "_td_label\">$r</td>";
                    } else {
                        $str .= "<td>&nbsp;</td>";
                    }

                    $h = 0;
                    if (is_array($riga)) {
                        foreach ($riga as $key => $value) {
                            $h++;
                            // $str .= "<td id=a$h>$value</td>";
                            $str .= "<td>$value</td>";
                        }
                    } else {
                        $str .= "<td class=\"" . $this->style . "_td\">&nbsp;</td>";
                    }
                    $str .= "\r\n\t</tr>\r\n";
                }
                $str .= "</tbody>\r\n";
                $str .= "</table>\r\n";
                $this->data = $str;
            }
        }
    }

    public function printTable()
    {
        return $this->print_element();
    }

    public function getTable()
    {
        return $this->get_element();
    }



    // end class Table
}

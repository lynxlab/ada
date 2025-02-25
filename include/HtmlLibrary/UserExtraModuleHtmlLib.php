<?php

/**
 *
 * @package
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2013, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version 0.1
 */

namespace Lynxlab\ADA\Main\HtmlLibrary;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use ReflectionClass;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class UserExtraModuleHtmlLib
{
    /*
     * called by browsing/edit_user.php
     */

    /**
     * get an object representing a row in one of the user 'extra' tables
     * and returns it formatted in HTML.
     *
     * Put all elements inside a table, with $columnsPerRow items per each row.
     * Label and Value are in 2 separate cells but count as 1 together.
     *
     * @param extraTable derived object $extraObject
     * @param int how many columns per row $columnsPerRow
     */
    public static function extraObjectRow($extraObject, $columnsPerRow = 3)
    {
        $className = $extraObject::class;
        $keyProperty = $className::getKeyProperty();
        $fields = $className::getFields();
        $className = lcfirst((new ReflectionClass($className))->getShortName());

        $table = CDOMElement::create('table', 'class:extraTableDatas ' . $className . ',id:' . $className . '_' . $extraObject->$keyProperty);
        $tbody = CDOMElement::create('tbody');

        for ($row = null, $printedRows = 0, $numRow = 0,$numCol = 0, $num = 0; $num < count($fields); $num++) {
            $label = $extraObject->getLabel($num);
            $propertyName = $fields[$num];
            if ($label === null) {
                continue;
            }

            if ($row === null) {
                $row = CDOMElement::create('tr', 'class:extraTableRow row-' . $numRow++);
            }

            $columnLbl = CDOMElement::create('td', 'class:extraTableLabel labelCol-' . $numCol . ',id:lbl_' . $propertyName . '_' . $extraObject->$keyProperty);
            $columnLbl->addChild(new CText($label . ": "));
            $row->addChild($columnLbl);

            $columnVal = CDOMElement::create('td', 'class:extraTableValue valueCol-' . $numCol++ . ',id:val_' . $propertyName . '_' . $extraObject->$keyProperty);
            $columnVal->addChild(new CText($extraObject->$propertyName));
            $row->addChild($columnVal);

            if ((++$printedRows % $columnsPerRow) === 0) {
                $tbody->addChild($row);
                $row = null;
            }
        }

        // check if there's a row to be closed
        if ($row !== null) {
            // printedRows has surely one extra increment, let's recuperate it
            $printedRows--;
            // add empty cells to complete the row
            while ((++$printedRows % $columnsPerRow) !== 0) {   // need 2 empty cells (label and value) for each 'column'
                for ($i = 0; $i < 2; $i++) {
                    $row->addChild(CDOMElement::create('td'));
                }
            }
            $tbody->addChild($row);
        }
        $table->addChild($tbody);

        // generate a div for edit and delete buttons

        $buttonsdiv = CDOMElement::create('div', 'class:extraButtonDiv ' . $className);

        $editbutton    = CDOMElement::create('a', 'class:extraEditButton ui small button');
        $editbutton->setAttribute('href', 'javascript:editExtra(\'' . $className . '\',' . $extraObject->$keyProperty . ');');
        $editbutton->addChild(CDOMElement::create('i', 'class:edit icon'));
        $editbutton->addChild(new CText(translateFN('Modifica')));

        $deletebutton = CDOMElement::create('a', 'class:extraDeleteButton ui small button');
        $deletebutton->setAttribute('href', 'javascript:deleteExtra(\'' . $className . '\',' . $extraObject->$keyProperty . ',\'' . $extraObject::getForeignKeyProperty() . '\');');
        $deletebutton->addChild(CDOMElement::create('i', 'class:trash icon'));
        $deletebutton->addChild(new CText(translateFN('Cancella')));

        $buttonsdiv->addChild($editbutton);
        $buttonsdiv->addChild($deletebutton);

        // generate a div for wrapping up the table
        $div = CDOMElement::create('div', 'class:extraTableContainer ' . $className . ',id:extraDIV_' . $extraObject->$keyProperty);
        $div->addChild($table);
        $div->addChild($buttonsdiv);

        return $div->getHtml() . CDOMElement::create('div', 'class:clearfix')->getHtml();
    }
}

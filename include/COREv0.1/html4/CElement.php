<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

use ReflectionObject;
use ReflectionProperty;

/**
 * abstract class Element: this class implements the method
 * getHtml, declared as abstract in class Base and defines the
 * method to be called for adding a child to the DOM element.
 *
 * @author vito
 */
abstract class CElement extends CBaseAttributesElement
{
    /**
     * children array
     *
     * @var array
     */
    protected $children;

    /**
     * which elements can be added as children
     *
     * @var array
     */
    protected $accept;

    /**
     * which elements cannot be added as children
     *
     * @var array
     */
    protected $reject;

    public function __construct()
    {
        $this->children = [];
        $this->accept   = [];
        $this->reject   = [];
    }

    public function addChild(CBase $child)
    {
        $child_classname = get_class($child);
        if (count($this->accept) > 0) {
            if (isset($this->accept[$child_classname])) {
                array_push($this->children, $child);
                return true;
            } else {
                return false;
            }
        } elseif (count($this->reject) > 0) {
            if (!isset($this->reject[$child_classname])) {
                array_push($this->children, $child);
                return true;
            } else {
                return false;
            }
        } else {
            array_push($this->children, $child);
            return true;
        }
    }

    public function addAccepted($accepted_element_classname)
    {
        $this->accept[$accepted_element_classname] = true;
    }

    public function addRejected($rejected_element_classname)
    {
        $this->reject[$rejected_element_classname] = true;
    }

    public function getHtml()
    {
        $matches   = [];
        $pattern   = [];
        $attribute = [];

        $html_element = get_class($this);
        $template     = CHtmlTags::getTagForHtmlElement($html_element);

        $search_attributes = '/%([a-z-]+)%/';
        preg_match_all($search_attributes, $template, $matches);

        foreach ($matches[1] as $match => $text) {
            // FIXME: avoid skipping newline
            $pattern[$match] = "/\s*%$text%\s*/";

            $attr = str_replace('-', '_', $text);

            if ($attr == 'children') {
                foreach ($this->children as $child) {
                    if (!isset($attribute[$match]) || strlen($attribute[$match]) <= 0) {
                        $attribute[$match] = '';
                    }
                    $attribute[$match] .= $child->getHtml();
                }
            } else {
                if (!property_exists($this, $attr) || $this->$attr === false) {
                    if ($attr !== 'datas') {
                        $this->$attr = 'false';
                    }
                } elseif ($this->$attr === true) {
                    $this->$attr = 'true';
                }

                if ($attr == 'datas') {
                    /**
                     * must load here all the public properties
                     * of the class whose name starts by 'data-'
                     */
                    $str_attribute = '';
                    $ref = new ReflectionObject($this);
                    foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $num => $refValue) {
                        $str_attribute .= ' ' . $refValue->name . '="' . $this->{$refValue->name} . '"';
                    }
                    $attribute[$match] = $str_attribute;
                } elseif (is_null($this->$attr)) {
                    $attribute[$match] = " ";
                } elseif (empty($this->$attr) && $this->$attr !== 0 && $this->$attr !== '0') {
                    $attribute[$match] = " $text";
                } else {
                    // the whitespace at the beginning of the string is needed
                    $attribute[$match] = " $text=\"{$this->$attr}\"";
                }
            }
        }

        $html = preg_replace($pattern, $attribute, $template);

        return $html;
    }
}

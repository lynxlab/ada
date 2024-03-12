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
 *
 * @author vito
 */
abstract class CEmptyElement extends CBaseAttributesElement
{
    public function getHtml()
    {
        $matches   = [];
        $pattern   = [];
        $attribute = [];

        $html_element = get_class($this);
        $template     = CHtmlTags::getTagForHtmlElement($html_element);

        $search_attributes = '/%([a-z]+)%/';
        preg_match_all($search_attributes, $template, $matches);

        foreach ($matches[1] as $match => $text) {
            $pattern[$match] = "/\s*%$text%\s*/";

            if (!property_exists($this, $text) || $this->$text === false) {
                $this->$text = 'false';
            } elseif ($this->$text === true) {
                $this->$text = 'true';
            }

            if ($text == 'datas') {
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
            } elseif (is_null($this->$text)) {
                $attribute[$match] = " ";
            } elseif (empty($this->$text) && $this->$text !== 0 && $this->$text !== '0') {
                $attribute[$match] = " $text";
            } else {
                // the whitespace at the beginning of the string is needed
                $attribute[$match] = " $text=\"{$this->$text}\"";
            }
        }

        $html = preg_replace($pattern, $attribute, $template);

        unset($matches);
        unset($pattern);
        unset($attribute);

        return $html;
    }
}

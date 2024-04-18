<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 * abstract class CBaseElement: this class defines base methods common to all
 * of the DOM elements.
 *
 * @author vito
 */
abstract class CBaseElement extends CBase
{
    /**
     * function getAttribute
     *
     * @param string $attribute_name - the name of the attribute
     */
    public function getAttribute($attribute_name)
    {
        return $this->$attribute_name ?? null;
    }

    public function setAttribute($attribute_name, $attribute_value)
    {
        /**
         * @author giorgio 16/ott/2013
         *
         * Check if passed $attribute_name is a valid html data attribute name by this definition:
         * The data attribute name must be at least one character long and must be prefixed with 'data-'.
         * It should not contain any uppercase letters.
         */
        if (property_exists($this, $attribute_name) || (preg_match('/(data|aria)\-[a-z0-9]{1}[a-z0-9\-]*/', $attribute_name) === 1)) {
            $this->$attribute_name = $attribute_value;
            return true;
        }
        return false;
    }

    public function setAttributes($a_list_of_attribute_value_pairs)
    {
        // FIXME: verificare bene l'espressione regolare relativa al valore
        $attribute_value_pair = '/\s*([a-z-]+)\s*:\s*([\s\(\)a-zA-Z0-9:;\.\[\]\/=\?\+\~%&_@#-]+)\s*/';
        //$attribute_value_pair = '/\s*([a-z]+)\s*:\s*(.*)\s*/';

        $matches = [];
        preg_match_all($attribute_value_pair, $a_list_of_attribute_value_pairs, $matches);

        $attributes       = [];
        $attributes       = $matches[1];
        $attributes_count = count($attributes);

        $values       = [];
        $values       = $matches[2];
        $values_count = count($values);

        for ($i = 0; $i < $attributes_count; $i++) {
            $attribute = str_replace('-', '_', $attributes[$i]);
            $this->setAttribute($attribute, $values[$i]);
        }
    }
}

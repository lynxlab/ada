<?php

/**
 * Class to translate PHP Array element into XML and vice versa.
 *
 * @author    Marco Vito Moscaritolo
 * @copyright GPL 3
 * @tutorial  http://mavimo.org/varie/array_xml_php
 * @example   index.php
 * @version   0.8
 */

namespace Lynxlab\ADA\Core\ArrayToXML;

use SimpleXMLElement;

class ArrayToXML
{
    /**
     * @staticvar string - String to use as key for node attributes into array
     * @todo      Convert this into a value settable from user
     */
    public const ATTR_ARR_STRING = 'attributes';
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @static
     * @param  array $data
     * @param  string $rootNodeName - what you want the root node to be - defaultsto data.
     * @param  \SimpleXMLElement $xml - should only be used recursively
     * @return string XML
     */
    public static function toXml($data, $rootNodeName = 'data', &$xml = null)
    {
        if (is_null($xml)) {
            $xml = new SimpleXMLElement('<' . $rootNodeName . '/>');
        }

        // loop through the data passed in.
        foreach ($data as $key => $value) {
            // if numeric key, assume array of rootNodeName elements
            if (is_numeric($key)) {
                $key = $rootNodeName;
            }
            // Check if is attribute
            if ($key == ArrayToXML::ATTR_ARR_STRING) {
                // Add attributes to node
                foreach ($value as $attr_name => $attr_value) {
                    $xml->addAttribute($attr_name, $attr_value);
                }
            } else {
                // delete any char not allowed in XML element names
                $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

                // if there is another array found recrusively call this function
                if (is_array($value)) {
                    // create a new node unless this is an array of elements
                    $node = ArrayToXML::isAssoc($value) ? $xml->addChild($key) : $xml;

                    // recrusive call - pass $key as the new rootNodeName
                    ArrayToXML::toXml($value, $key, $node);
                } else {
                    // add single node.
                    // $value = htmlentities($value, ENT_COMPAT | ENT_XHTML, ADA_CHARSET);
                    $new_child = $xml->addChild($key);
                    if ($new_child !== null) {
                        $node = dom_import_simplexml($new_child);
                        $no   = $node->ownerDocument;
                        $node->appendChild($no->createCDATASection($value));
                    }
                }
            }
        }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }

    /**
     * The main function for converting to an array.
     * Pass in a XML document and this recrusively loops through and builds up an array.
     *
     * @static
     * @param  string $obj - XML document string (at start point)
     * @param  array  $arr - Array to generate
     * @return array - Array generated
     */
    public static function toArray($obj, &$arr = null)
    {
        if (is_null($arr)) {
            $arr = [];
        }
        if (is_string($obj)) {
            $obj = new SimpleXMLElement($obj);
        }

        // Get attributes for current node and add to current array element
        $attributes = $obj->attributes();
        foreach ($attributes as $attrib => $value) {
            $arr[ArrayToXML::ATTR_ARR_STRING][$attrib] = (string)$value;
        }

        $children = $obj->children();
        $executed = false;
        // Check all children of node
        foreach ($children as $elementName => $node) {
            // Check if there are multiple node with the same key and generate a multiarray
            if (array_key_exists($elementName, $arr) && $arr[$elementName] != null) {
                if (isset($arr[$elementName][0]) && $arr[$elementName][0] !== null) {
                    $i = count($arr[$elementName]);
                    ArrayToXML::toArray($node, $arr[$elementName][$i]);
                } else {
                    $tmp = $arr[$elementName];
                    $arr[$elementName] = [];
                    $arr[$elementName][0] = $tmp;
                    $i = count($arr[$elementName]);
                    ArrayToXML::toArray($node, $arr[$elementName][$i]);
                }
            } else {
                $arr[$elementName] = [];
                ArrayToXML::toArray($node, $arr[$elementName]);
            }
            $executed = true;
        }
        // Check if is already processed and if already contains attributes
        if (!$executed && $children->getName() == "" && !isset($arr[ArrayToXML::ATTR_ARR_STRING])) {
            $arr = (string)$obj;
        }

        return $arr;
    }

    /**
     * Determine if a variable is an associative array
     *
     * @static
     * @param  array $obj - variable to analyze
     * @return boolean - info about variable is associative array or not
     */
    private static function isAssoc($array)
    {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
    }
}

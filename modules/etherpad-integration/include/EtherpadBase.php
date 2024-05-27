<?php

/**
 * @package     etherpad module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\EtherpadIntegration;

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use ReflectionClass;

/**
 * Etherpad module base class
 *
 * @author giorgio
 *
 */
abstract class EtherpadBase
{
    public const GETTERPREFIX = 'get';
    public const SETTERPREFIX = 'set';
    public const ADDERPREFIX  = 'add';

    /**
     * base constructor
     */
    public function __construct($data = [])
    {
        $this->fromArray($data);
    }

    /**
     * Tells which properties are to be loaded using a kind-of-join
     *
     * @return array
     */
    public static function loadJoined()
    {
        return [];
    }

    public static function arrayProperties()
    {
        return [];
    }

    public static function explodeArrayProperties($properties)
    {
        return $properties;
    }

    /**
     * Tells which properties are not to be loaded at all
     *
     * @return array
     */
    public static function doNotLoad()
    {
        return [];
    }

    /**
     * adds class own properties to the passed form
     *
     * @param \Lynxlab\ADA\Main\Forms\lib\classes\FForm $form
     * @return \Lynxlab\ADA\Main\Forms\lib\classes\FForm
     */
    public static function addFormControls(FForm $form)
    {
        return $form;
    }

    /**
     * Populates object properties with the passed values in the data array
     * NOTE: array keys must match object properties names
     *
     * @param array $data
     * @return \Lynxlab\ADA\Module\EtherpadIntegration\EtherpadBase
     */
    public function fromArray($data = [])
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key) && method_exists($this, 'set' . ucfirst($key))) {
                $this->{'set' . ucfirst($key)}($val);
            } else {
                $method = (new Convert('set_' . $key))->toCamel();
                if (property_exists($this, $key) && method_exists($this, $method)) {
                    $this->{$method}($val);
                }
            }
        }
        return $this;
    }

    /**
     * Convert Object (With Protected Values) To Associative Array
     * http://www.beliefmedia.com/object-to-array
     *
     * @return NULL|[]
     */
    public function toArray()
    {
        $reflectionClass = new ReflectionClass(static::class);
        $array = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $toSet = $property->getValue($this);
            $array[$property->getName()] = $toSet;
            $property->setAccessible(false);
        }
        return $array;
    }
}

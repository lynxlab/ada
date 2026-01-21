<?php

/**
 * UserExtraTables.class.inc.php
 *
 * @package        model
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           UserExtraTables.class.inc
 * @version        0.1
 */

/**
 * UserExtraTables abstract class for user extra data handling
 * that are in a 1:n relationship with utente table.
 *
 * @author giorgio
 *
 */

namespace Lynxlab\ADA\Main\User;

use ReflectionClass;
use ReflectionProperty;

abstract class UserExtraTables
{
    /**
     * the name of the unique key in the table
     *
     * @var string
     */
    protected static $keyProperty = null;

    /**
     * the name of the foreign key (i.e. the key that points to the user id)
     *
     * @var string
     */
    protected static $foreignKeyProperty = null;

    /**
     * how many columns in a row in the display row table (UserExtraModuleHtmlLib::extraObjectRow)
     *
     * @var integer
     */
    protected static $columnsPerRow = 3;

    /**
     * array of labels to be used for each filed when rendering
     * to HTML in file /include/HtmlLibrary/UserExtraModuleHtmlLib.inc.php
     *
     * It's populated in the constructor because of the call to translateFN.
     *
     * NOTE: in this case the first two values are not displayed,
     * so labels are set to null value.
     *
     * @var array
     */
    protected $labels;

    protected $isSaved;

    public function __construct($dataAr = [])
    {
        if (!empty($dataAr)) {
            foreach ($dataAr as $propname => $propvalue) {
                if (property_exists($this, $propname)) {
                    $this->$propname = $propvalue;
                }
            }

            if (isset($dataAr['_isSaved']) && $dataAr['_isSaved'] == 0) {
                $this->isSaved = false;
            } else {
                $this->isSaved = true;
            }
        }
    }

    public function setSaveState($saveState)
    {
        $this->isSaved = $saveState;
    }

    public function getSaveState()
    {
        return $this->isSaved;
    }

    /**
     * sets object properties array from post array
     *
     * @param array $postData array of post data
     *
     * @return array
     */
    public static function buildArrayFromPOST($postData)
    {
        $retArray = [];
        $refclass = new ReflectionClass(static::class);
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $retArray[$property->name] = $postData[$property->name];
        }
        // force procteded property isSaved
        if (isset($postData['_isSaved']) && $postData['_isSaved'] == 0) {
            $retArray['_isSaved'] = 0;
        }

        return empty($retArray) ? null : $retArray;
    }

    /**
     * sets object properties array from post array
     *
     * @deprecated
     * use buildArrayFromPOST without passing the className
     *
     * @param string $className name of calling class
     * @param array $postData array of post data
     *
     * @return array
     */
    public static function doBuildArrayFromPOST($className, $postData)
    {
        $retArray = [];
        $refclass = new ReflectionClass($className);
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $retArray[$property->name] = $postData[$property->name];
        }
        // force procteded property isSaved
        if (isset($postData['_isSaved']) && $postData['_isSaved'] == 0) {
            $retArray['_isSaved'] = 0;
        }

        return empty($retArray) ? null : $retArray;
    }

    /**
     * gets object properties list as an array
     *
     * @return array
     */
    public static function getFields()
    {
        $retArray = [];
        $refclass = new ReflectionClass(static::class);
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $retArray[] = $property->name;
        }
        return empty($retArray) ? null : $retArray;
    }

    /**
     * gets object properties list as an array
     *
     * @deprecated
     * use getFields without passing the classname
     *
     * @param string $className calling class name
     *
     * @return array
     */
    protected static function doGetFields($className)
    {
        $retArray = [];
        $refclass = new ReflectionClass($className);
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $retArray[] = $property->name;
        }
        return empty($retArray) ? null : $retArray;
    }

    public static function getKeyProperty()
    {
        if (property_exists(static::class, 'keyProperty')) {
            return static::$keyProperty;
        }
    }

    public static function getForeignKeyProperty()
    {
        if (property_exists(static::class, 'foreignKeyProperty')) {
            return static::$foreignKeyProperty;
        }
    }

    public static function getColumnsPerRow()
    {
        return static::$columnsPerRow;
    }

    public function getLabel($index)
    {
        if ($index < 0 || $index >= count($this->labels)) {
            return null;
        } else {
            return $this->labels[$index];
        }
    }
}

<?php

use Lynxlab\ADA\Main\User\UserExtraTables;

use Lynxlab\ADA\Main\User\ADAUser;

// Trigger: ClassWithNameSpace. The class UserExtraTables was declared with namespace Lynxlab\ADA\Main\User. //

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

    protected static function getFields($className)
    {
        $retArray = [];
        $refclass = new ReflectionClass($className);
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $retArray[] = $property->name;
        }
        return empty($retArray) ? null : $retArray;
    }
}

/**
 * We're namespaced and class autoloaded now, following lines should not be needed
 */
// include all tables as defined in ADAUser $_linkedTables array
// if (is_array(ADAUser::getLinkedTables())) {
//  foreach (ADAUser::getLinkedTables() as $linkedTable) {
//      if (!empty($linkedTable)) @include_once ROOT_DIR . '/include/' . ucfirst ($linkedTable) . '.class.inc.php';
//  }
// }

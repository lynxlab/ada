<?php

/**
 * ADAUser class
 *
 * This is the new implementation of the ADAUser class that
 * the whole ADA system is used to work with.
 *
 * The new implementation shall basically manage any extra required
 * field that a user may have beside the 'standard ones'. Theese are
 * usually stored in the tables named: 'autore', 'studente', 'tutor'
 * depending upon user's role.
 *
 * PLS NOTE:
 * For the 'standard' version this class should only have the hasExtra and
 * extraFieldsArray properties, and class code will take care of everything.
 *
 * For the customizations, you must implement all the stuff you need here,
 * keeping in mind that the parent it's always there to help you, kiddy!
 *
 *
 * @package     model
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2013, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        ADAUser
 * @version     0.1
 */

// include needed table managing class
// include_once ROOT_DIR.'/include/UserExtraTables.class.inc.php';

namespace Lynxlab\ADA\Main\User;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\User\ADAAbstractUser;
use Lynxlab\ADA\Switcher\Subscription;
use ReflectionClass;
use ReflectionProperty;

class ADAUser extends ADAAbstractUser
{
    /**
     * array to list linked tables that have
     * a 1:n relationship with the user, must be private
     * each item MUST have a corresponding class with its own fields.
     *
     * The constructor will build a public variable called $tbl_<array element>
     * of type array to hold the rows from the corresponding table.
     *
     * @var array
     */
    protected static $linkedTables = [];

    /**
     * table prefix used in the DB.
     * eg. if in the linkedTables there is 'moreUserFields'
     * the corresponding table in the db must be $tablesPrefix.'moreUserFields'
     *
     * * @var string
     */
    protected static $tablesPrefix = '';

    /**
     * extra table name: the table where are stored
     * extra datas in a 1:1 relationship with the user
     *
     * @var string
     */
    protected static $extraTableName = 'studente';

    /**
     * extra table (see above) unique index field name
     *
     * @var string
     */
    protected static $extraTableKeyProperty = 'id_utente_studente';

    /**
     * Public properties.
     * PLS set the list of properties you want the extra user to have, and the class code
     * should take care of the rest. If no extra properites are needed, delete them all!
     */
    //  public $samplefield;

    /**
     * boolean to tell if the class is for a customization
     * and thus has extra values (i.e. properties).
     *
     * For readability reason, I feel it's better to valorize hasExtra
     * in this class constructor rather than in ADAAbstractUser.
     *
     * @var boolean
     */
    protected $hasExtra;

    /**
     * boolean to tell if the system must use AJAX or standard POST
     * when saving user data.
     * Defaults to true, because ADA is a cool system and use AJAX
     *
     * NOTE: if $linkedTables is not empty, MUST save through ajax
     *       can use POST only if there are no tabs and you only
     *       have $extraTableName + public properties.
     *
     * @var boolean
     */
    protected $useAjax;

    /**
     * boolean to tell if you are free to decide to save
     * via ajax or not.
     *
     * NOTE: - if there are no extras you are forced to have no tabs
     *         in the form and save using POST
     *       - if there are extras and linkedTables you are forced
     *         to have tabs in the form and save using ajx
     *       - if there are extras and NO linkedTables you are free
     *         to decide if you want: (tabs AND ajax) OR (no tabs AND POST)
     *
     * @var boolean
     */
    private $canSetAjax;

    /**
     * array containg extra fields list, builded automatically in the constructor
     */
    protected $extraFieldsArray;

    /**
     * ADAUser constructor
     *
     * If this is no customization file, calls the parent and sets hasExtra to false
     *
     *
     * @param array $user_dataAr the array of user datas to fill the class properties with
     */
    public function __construct($user_dataAr = [])
    {
        parent::__construct($user_dataAr);

        $this->canSetAjax = true;
        $this->useAjax();

        $this->extraFieldsArray = $this->buildExtraFieldsArray();
        $this->hasExtra = !is_null($this->extraFieldsArray);

        if ($this->hasExtra) {
            // sets the properties with values coming from $user_dataAr
            if (!is_null($this->extraFieldsArray)) {
                foreach ($this->extraFieldsArray as $propertyName) {
                    $this->$propertyName = $user_dataAr[$propertyName] ?? '';
                }
            }

            // build up a property called 'tbl_'.tableName
            // containing an empty array for each linkedTable
            if (isset(self::$linkedTables) && !empty(self::$linkedTables)) {
                // there are some linked tables, must use ajax to save datas
                $this->useAjax(true);
                $this->canSetAjax = false;
                foreach (self::$linkedTables as $tableName) {
                    $varName = 'tbl_' . $tableName;
                    $this->$varName = [];
                }
            }
        } else {
            $this->useAjax(false);
            $this->canSetAjax = false;
        }
    }

    /**
     * converts object to array by calling the corresponding parent method
     * and building up an array with extra properties, finally returning
     * the merge of the two arrays
     *
     * @return array the array containing the converted object
     * @see ADAGenericUser::toArray()
     * @access public
     */
    public function toArray()
    {
        $stdValues = parent::toArray();
        if ($this->hasExtra) {
            foreach ($this->extraFieldsArray as $propertyName) {
                $extraValues[$propertyName] = $this->$propertyName;
            }
            if (property_exists($this, '_linkedTables')) {
                foreach (self::$linkedTables as $tableName) {
                    $propertyName = 'tbl_' . $tableName;
                    if (isset($this->$propertyName) && is_array($this->$propertyName)) {
                        foreach ($this->$propertyName as $num => $tableObject) {
                            foreach ($tableObject->getFields() as $field) {
                                $extraValues[$tableName][$num][$field] = $tableObject->$field;
                            }
                            // force protected property _isSaved
                            if ($tableObject->getSaveState()) {
                                $extraValues[$tableName][$num]['_isSaved'] = 1;
                            }
                        }
                    }
                }
            }
            return array_merge($stdValues, $extraValues);
        } else {
            return $stdValues;
        }
    }

    /**
     * Sets extra values by checking if each array element key has a corresponding class property.
     * If it has, then set the property else do nothing and disregard the key.
     * There should be no need to override this method for each customization.
     *
     * @param array $extraAr array of values to be set
     * @access public
     */
    public function setExtras($extraAr)
    {
        if ($this->hasExtra) {
            foreach ($extraAr as $property => $value) {
                // first check if $property is a class property
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                } elseif (is_array($value) && class_exists($property) && method_exists($property, 'getKeyProperty')) {
                    // if $property is an array, which means
                    // it's a value coming from a table that has a 1:n relationship with the student

                    // in this case must return the key of the new or substituted element
                    $classPropertyName = 'tbl_' . $property;
                    $classKeyProperty = $property::getKeyProperty();
                    // $classProperyName hold something like 'tbl_moreUserFields'

                    foreach ($value as $arrayValues) {
                        if ($arrayValues[$classKeyProperty] > 0 && isset($arrayValues['_isSaved']) && $arrayValues['_isSaved'] == 0) {
                            // look for array index that has the passed id
                            $tempArray = &$this->$classPropertyName;
                            foreach ($tempArray as $key => $aElement) {
                                if ($aElement->$classKeyProperty == $arrayValues[$classKeyProperty]) {
                                    break;
                                }
                            }
                            // substitute the element with the modified one
                            $tempArray[$key] = new $property($arrayValues);
                        } else {
                            // push all incoming arrays into the object array
                            array_push($this->$classPropertyName, new $property($arrayValues));
                            $key = count($this->$classPropertyName) - 1;
                        }
                    }
                }
            }
            if (isset($key)) {
                return $key;
            }
        }
    }

    /**
     * removeExtras
     *
     * remove the passed extra object id from the corresponding user object array
     *
     * @author giorgio 20/giu/2013
     *
     * @param int $extraTableId  the ID of the object to be removed
     * @param string $extraTableClass the class of the object to be removed
     *
     * @access public
     */
    public function removeExtras($extraTableId = null, $extraTableClass = null)
    {
        if ($this->hasExtra && $extraTableId !== null && $extraTableClass !== null) {
            $classPropertyName = 'tbl_' . $extraTableClass;
            $keyFieldName = $extraTableClass::getKeyProperty();
            $propertyArray = &$this->$classPropertyName;

            if (is_array($propertyArray)) {
                foreach ($propertyArray as $key => $extraObject) {
                    if ($extraObject->$keyFieldName == $extraTableId) {
                        // remove matched element and reindex the array
                        unset($propertyArray[$key]);
                        $propertyArray = array_values($propertyArray);
                        // we're done, break out of the loop
                        break;
                    }
                }
            }
        }
    }

    /**
     * method to build the list of all extra properites
     * called only once in the constructor. No one else
     * should ever need to call again this method, but
     * must get the builded array using the getExtraFields method.
     *
     * @return array list of all extra properties, excluding hasExtra
     * @access private
     */
    private function buildExtraFieldsArray()
    {
        $retArray = [];
        // instantiate a ReflectionClass
        $refclass = new ReflectionClass($this);
        // loop through each property
        foreach ($refclass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            // if property class name == the reflection class name,
            // and its name does not start with 'tbl_'
            // then property is one of the elements we are lookin for
            if (
                $property->class == $refclass->name &&
                !str_contains($property->name, 'tbl_')
            ) {
                $retArray[] = $property->name;
            }
        }
        return empty($retArray) ? null : $retArray;
    }

    /**
     * extraFieldsArray getter
     *
     * @return array extraFieldsArray if hasExtra is true, else false
     * @access public
     */
    public function getExtraFields()
    {
        if ($this->hasExtra) {
            return $this->extraFieldsArray;
        } else {
            return null;
        }
    }

    public static function getExtraFieldsLabels()
    {
        return [
            // 'samplefield' => 'Campo di esempio',
        ];
    }

    public static function getLinkedTables()
    {
        if (property_exists(static::class, 'linkedTables') && !empty(self::$linkedTables)) {
            return self::$linkedTables;
        }
    }

    public static function getTablesPrefix()
    {
        if (property_exists(static::class, 'tablesPrefix')) {
            return self::$tablesPrefix;
        }
    }

    public static function getExtraTableName()
    {
        if (property_exists(static::class, 'extraTableName')) {
            return self::$extraTableName;
        }
    }

    public static function getExtraTableKeyProperty()
    {
        if (property_exists(static::class, 'extraTableKeyProperty')) {
            return self::$extraTableKeyProperty;
        }
    }

    /**
     * hasExtra getter
     *
     * @return boolean
     * @access public
     */
    public function hasExtra()
    {
        return $this->hasExtra;
    }

    /**
     * useAjax getter
     *
     * @return boolean
     * @access public
     */
    public function saveUsingAjax()
    {
        return $this->useAjax;
    }

    /**
     * useAjax setter
     *
     * sets the data savemode to use AJAX calls,
     * can set it to false only if forceAjax is not true
     * @param string $mode
     */
    public function useAjax($mode = true)
    {
        if ($this->canSetAjax) {
            $this->useAjax = $mode;
        } else {
            $this->useAjax = true;
        }
    }

    /**
     * getDefaultTester implementation:
     * - if it's not a multiprovider environment, return the user selected provider
     * - else return parent's method
     *
     * @see ADAAbstractUser::getDefaultTester()
     */
    public function getDefaultTester()
    {
        if (!MULTIPROVIDER) {
            $candidate = null;
            /**
             * the default tester is the only one in which the user is listed
             * that is NOT the public tester. So let's take the list of all
             * providers the user is registered into, remove the public one and
             * if what is left has only one element, this is the default tester.
             * Else we cannot tell for certain the default testers and return null.
             */
            $testersArr = $this->getTesters();
            if (!empty($testersArr)) {
                $testersArr = array_values(array_diff($testersArr, [ADA_PUBLIC_TESTER]));
                if (count($testersArr) === 1) {
                    $candidate = $testersArr[0];
                } elseif (isset($GLOBALS['user_provider'])) {
                    $candidate = $GLOBALS['user_provider'];
                }
            }

            $tester = DataValidator::validateTestername($candidate, MULTIPROVIDER);
            if ($tester !== false) {
                return $tester;
            } else {
                return null;
            }
        } else {
            return parent::getDefaultTester();
        }
    }

    /**
     * Sets the terminated status for the passed courseId and courseInstanceId
     * It is usually called from user.php when the user has a subscried status
     * and the subscription_date + duration_subscription is in the past.
     *
     * @param number $courseId
     * @param number $courseInstanceId
     *
     * @return AMAError on error or true on success
     *
     * @access public
     *
     * @author giorgio 03/apr/2015
     */
    public function setTerminatedStatusForInstance($courseId, $courseInstanceId)
    {
        $common_dh = $GLOBALS['common_dh'];
        $s = new Subscription($this->getId(), $courseInstanceId);
        $s->setSubscriptionStatus(ADA_STATUS_TERMINATED);
        $s->setStartStudentLevel(null); // null means no level update
        // search the provider of the current iteration course
        $courseProv = $common_dh->getTesterInfoFromIdCourse($courseId);
        if (!AMADB::isError($courseProv) && is_array($courseProv) && isset($courseProv['puntatore'])) {
            // save the datahandler
            $savedDH = $GLOBALS['dh'];
            // set the datahandler to be used
            $GLOBALS['dh'] = AMADataHandler::instance(MultiPort::getDSN($courseProv['puntatore']));
            // update the subscription
            $retval = Subscription::updateSubscription($s);
            // restore the datahandler
            $GLOBALS['dh'] = $savedDH;
            $GLOBALS['dh']->disconnect();
        }
        return ($retval ?? null);
    }
}

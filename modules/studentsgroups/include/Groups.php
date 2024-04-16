<?php

namespace Lynxlab\ADA\Module\StudentsGroups;

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Module\StudentsGroups\AMAStudentsGroupsDataHandler;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsBase;

class Groups extends StudentsGroupsBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE = AMAStudentsGroupsDataHandler::PREFIX . 'groups';

    /**
     * table name for groups/utente relation
     */
    public const UTENTERELTABLE = AMAStudentsGroupsDataHandler::PREFIX . 'groups_utente';

    /**
     * customField table field prefix
     */
    public const CUSTOMFIELDPRFIX = 'customField';

    /**
     * array of labels for customFields
     */
    public const CUSTOMFIELDLBL = [
        0 => 'Classe',
        1 => 'Sezione',
    ];

    /**
     * array of values for customFields
     */
    public const CUSTOMFIELDSVAL = [
        0 => [
            0 => 'Prima',
            1 => 'Seconda',
            2 => 'Terza',
            3 => 'Quarta',
            4 => 'Quinta',
        ],
        1 => [
            0 => 'A',
            1 => 'B',
            2 => 'C',
            3 => 'D',
            4 => 'E',
            5 => 'F',
            6 => 'G',
        ],
    ];

    protected $id;
    protected $label;
    /**
     * array of \ADAUser objects
     *
     * @var array
     */
    protected $members = [];

    /**
     * must be named self::customFieldPrefix .'s'
     *
     * @var array
     */
    protected $customFields = [];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $customFieldsArr = [];
        foreach (self::getCustomFieldLbl() as $lKey => $lVal) {
            if (array_key_exists(self::CUSTOMFIELDPRFIX . $lKey, $data)) {
                $customFieldsArr[$lKey] = $data[self::CUSTOMFIELDPRFIX . $lKey];
            }
        }
        $this->setCustomFields($customFieldsArr);
    }

    public static function loadJoined()
    {
        return [
            'members' => [
                'reltable' => self::UTENTERELTABLE,
                'key' => [
                    'name' => 'group_id',
                    'getter' => self::GETTERPREFIX . 'Id',
                ],
                'extkey' => 'utente_id',
                'callback' => 'loadMembers',
            ],
        ];
    }

    public function loadMembers($resArr)
    {
        $retArr = [];
        foreach ($resArr as $aRes) {
            foreach ($aRes as $userId) {
                $user = MultiPort::findUser($userId);
                if ($user instanceof ADAUser) {
                    array_push($retArr, $user);
                }
            }
        }
        return $retArr;
    }

    public static function arrayProperties()
    {
        return [ self::CUSTOMFIELDPRFIX . 's' ];
    }

    public static function explodeArrayProperties($properties)
    {
        $arrayProp = self::arrayProperties();
        foreach ($properties as $key => $property) {
            if (in_array($property, $arrayProp)) {
                // build Lbl constant name: e.g. from customFields, build customFieldLbl
                $singular = rtrim($property, 's');
                $labels = constant(get_called_class() . '::' . $singular . 'Lbl');
                foreach (array_keys($labels) as $index) {
                    $properties[] = $singular . $index;
                }
                unset($properties[$key]);
            }
        }
        return array_values($properties);
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of label
     *
     * @return  self
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of customFields
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    /**
     * Set the value of customFields
     *
     * @return  self
     */
    public function setCustomFields($customFields)
    {
        $this->customFields = $customFields;

        return $this;
    }

    /**
     * Get array of \ADAUser objects
     *
     * @return  array
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Set array of \ADAUser objects
     *
     * @param  array  $members  array of \ADAUser objects
     *
     * @return  self
     */
    public function setMembers(array $members)
    {
        $this->members = $members;

        return $this;
    }

    public function addMember($member)
    {
        $this->members[] = $member;

        return $this;
    }

    /**
     * Gets the array of customFieldLbl
     *
     * @return array
     */
    public static function getCustomFieldLbl()
    {
        return self::CUSTOMFIELDLBL;
    }

    /**
     * Gets the array of customFieldsVal
     *
     * @return array
     */
    public static function getCustomFieldsVal()
    {
        return self::CUSTOMFIELDSVAL;
    }
}

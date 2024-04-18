<?php

/**
 * @package     collabora-access-list module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\CollaboraACL;

class FileACL extends CollaboraACLBase
{
    /**
     * table name for this class
     *
     * @var string
     */
    public const TABLE = AMACollaboraACLDataHandler::PREFIX . 'files';

    /**
     * table name for groups/utente relation
     */
    public const UTENTERELTABLE = AMACollaboraACLDataHandler::PREFIX . 'files_utente';

    protected $id;
    protected $filepath;
    protected $id_corso;
    protected $id_istanza;
    protected $id_nodo;
    protected $id_owner;

    /**
     * array of int, users allowred to access the file
     *
     * @var array
     */
    protected $allowedUsers = [];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public static function loadJoined()
    {
        return [
            'allowedUsers' => [
                'reltable' => self::UTENTERELTABLE,
                'key' => [
                    'name' => 'file_id',
                    'getter' => self::GETTERPREFIX . 'Id',
                ],
                'extkey' => 'utente_id',
                'relproperties' => [ 'permissions' ],
            ],
        ];
    }

    public static function isAllowed(array $filesACL = [], $userId = null, $filepath = null, $permissions = CollaboraACLActions::READ_FILE)
    {
        if (!is_null($userId) && !is_null($filepath) && count($filesACL) > 0) {
            $aclCount = count($filesACL);
            $found = false;
            for ($i = 0; !$found && $i < $aclCount; $i++) {
                $found = ($filesACL[$i]->getFilepath() == $filepath);
            }
            // if $filepath is not in the passed file access list, then it's a public file and everyone is allowed
            if (!$found) {
                return true;
            } else {
                // $i-1 is the found filesACL index
                --$i;
                if ($filesACL[$i]->getIdOwner() == $userId) {
                    return true;
                }
                foreach ($filesACL[$i]->getAllowedUsers() as $allowedAr) {
                    if ($allowedAr['utente_id'] == $userId) {
                        return ($allowedAr['permissions'] & $permissions);
                    }
                }
                return false;
            }
        }
        return true; // is a public file
    }

    public static function getObjectById(array $filesACL, $id)
    {
        $retval = array_filter($filesACL, fn ($acl) => $acl->getId() == $id);

        if (is_array($retval) && count($retval) == 1) {
            $retval = reset($retval);
        } else {
            $retval = null;
        }
        return $retval;
    }

    public static function getIdFromFileName(array $filesACL = [], $filepath = '')
    {
        $fileACL = array_filter($filesACL, function ($el) use ($filepath) {
            $elPath = str_replace(ROOT_DIR . DIRECTORY_SEPARATOR, '', $filepath);
            return $el->getFilepath() == $elPath;
        });
        if (is_array($fileACL) && count($fileACL) == 1) {
            $fileACL = reset($fileACL);
            if ($fileACL instanceof self) {
                return $fileACL->getId();
            }
        }
        return null;
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
     * Get the value of filepath
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * Set the value of filepath
     *
     * @return  self
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Get the value of id_nodo
     */
    public function getIdNodo()
    {
        return $this->id_nodo;
    }

    /**
     * Set the value of id_nodo
     *
     * @return  self
     */
    public function setIdNodo($id_nodo)
    {
        $this->id_nodo = $id_nodo;

        return $this;
    }

    /**
     * Get array of \ADAUser objects
     *
     * @return  array
     */
    public function getAllowedUsers()
    {
        return $this->allowedUsers;
    }

    /**
     * Set array of \ADAUser objects
     *
     * @param  array  $allowedUsers  array of \ADAUser objects
     *
     * @return  self
     */
    public function setAllowedUsers(array $allowedUsers)
    {
        $this->allowedUsers = $allowedUsers;

        return $this;
    }

    public function addAllowedUser($allowedUser)
    {
        $this->allowedUsers[] = $allowedUser;

        return $this;
    }

    /**
     * Get the value of id_corso
     */
    public function getIdCorso()
    {
        return $this->id_corso;
    }

    /**
     * Set the value of id_corso
     *
     * @return  self
     */
    public function setIdCorso($id_corso)
    {
        $this->id_corso = $id_corso;

        return $this;
    }

    /**
     * Get the value of id_istanza
     */
    public function getIdIstanza()
    {
        return $this->id_istanza;
    }

    /**
     * Set the value of id_istanza
     *
     * @return  self
     */
    public function setIdIstanza($id_istanza)
    {
        $this->id_istanza = $id_istanza;

        return $this;
    }

    /**
     * Get the value of id_owner
     */
    public function getIdOwner()
    {
        return $this->id_owner;
    }

    /**
     * Set the value of id_owner
     *
     * @return  self
     */
    public function setIdOwner($id_owner)
    {
        $this->id_owner = $id_owner;

        return $this;
    }
}

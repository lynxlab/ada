<?php

/**
 * User classes
 *
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        user_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\User;

use Throwable;
use DateTimeImmutable;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class ADAGenericUser
{
    /**
     * Data stored in table Utente
     */
    public $id_user;
    public $nome;
    public $cognome;
    public $tipo;
    public $email;
    public $telefono;
    public $username;
    public $template_family;   // layout
    // ADA specific
    protected $indirizzo;
    protected $citta;
    protected $provincia;
    protected $nazione;
    protected $codice_fiscale;
    protected $birthdate;
    protected $birthcity;
    protected $birthprovince;
    protected $sesso;
    protected $stato;
    protected $lingua;
    protected $timezone;
    protected $cap;
    protected $SerialNumber;
    protected $avatar;
    protected $isSuper = false;

    // we do not store user's password ???
    protected $password;
    // END of ADA specific

    // ATTENZIONE A QUESTI QUI SOTTO
    public $livello = 1;
    // public $history = '';
    public $exercise = '';
    public $address;
    public $status;
    public $full = 0; //  user exists
    public $error_msg;

    /*
   * Data stored in table Utente_Tester
    */
    protected $testers;
    /*
   * Path to user's home page
    */
    protected $homepage;

    /**
     * path to user's edit profile page
     */
    protected $editprofilepage;

    /*
   * getters
    */

    public function getId()
    {
        return $this->id_user;
    }

    public function getFirstName()
    {
        return $this->nome;
    }

    public function getLastName()
    {
        return $this->cognome;
    }

    public function getFullName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getType()
    {
        return $this->tipo;
    }

    /* 12/05/2020 functions used by menu builder */
    public static function isNotStudent()
    {
        return !(self::isStudent());
    }

    public static function isStudent()
    {
        if (isset($_SESSION['sess_id_user']) && isset($_SESSION['sess_id_user_type'])) {
            return ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT);
        }
        return false;
    }
    /* end menu */

    public function getTypeAsString()
    {
        switch ($this->tipo) {
            case AMA_TYPE_ADMIN:
                return translateFN('Super amministratore');
            case AMA_TYPE_SWITCHER:
                return translateFN('Amministratore del provider');
            case AMA_TYPE_AUTHOR:
                return translateFN('Autore');
            case AMA_TYPE_TUTOR:
                return ($this->isSuper) ? translateFN('Super Tutor') : translateFN('Tutor');
            case AMA_TYPE_STUDENT:
                return translateFN('Studente');
            default:
                return translateFN('Ospite');
        }
    }
    public function getEmail()
    {
        return $this->email;
    }

    public function getAddress()
    {
        if ($this->indirizzo != 'NULL') {
            return $this->indirizzo;
        }
        return '';
    }

    public function getCity()
    {
        if ($this->citta != 'NULL') {
            return $this->citta;
        }
        return '';
    }

    public function getProvincia()
    {
        if ($this->provincia != 'NULL') {
            return $this->provincia;
        }
        return '';
    }

    public function getProvince()
    {
        if ($this->provincia != 'NULL') {
            return $this->provincia;
        }
        return '';
    }

    public function getCountry()
    {
        if ($this->nazione != 'NULL') {
            return $this->nazione;
        }
        return '';
    }

    public function getFiscalCode()
    {
        return $this->codice_fiscale;
    }

    public function getBirthDate()
    {
        return $this->birthdate;
    }

    public function getBirthCity()
    {
        return $this->birthcity;
    }

    public function getBirthProvince()
    {
        return $this->birthprovince;
    }

    public function getGender()
    {
        return $this->sesso ?? 'M';
    }

    public function getPhoneNumber()
    {
        if ($this->telefono != 'NULL') {
            return $this->telefono;
        }
        return '';
    }

    public function getStatus()
    {
        return $this->stato;
    }

    public function getLanguage()
    {
        return $this->lingua;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getUserName()
    {
        return $this->username;
    }

    public function getCap()
    {
        return $this->cap;
    }

    public function getSerialNumber()
    {
        return $this->SerialNumber;
    }

    public function getAvatar()
    {
        if ($this->avatar != '' && file_exists(ADA_UPLOAD_PATH . $this->id_user . '/' . $this->avatar)) {
            $imgAvatar = HTTP_UPLOAD_PATH . $this->id_user . '/' . $this->avatar;
        } else {
            $imgAvatar = HTTP_UPLOAD_PATH . ADA_DEFAULT_AVATAR;
        }
        return $imgAvatar;
    }

    public function getTesters()
    {
        if (is_array($this->testers)) {
            return $this->testers;
        }
        return [];
    }

    public function getDefaultTester()
    {
        if (is_array($this->testers) && sizeof($this->testers) > 0) {
            if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && in_array($GLOBALS['user_provider'], $this->testers)) {
                return $GLOBALS['user_provider'];
            }
            return $this->testers[0];
        }
        return null;
    }

    public function getHomePage($msg = null)
    {
        if ($msg != null) {
            return $this->homepage . "?message=$msg";
        }
        return $this->homepage;
    }

    public function getEditProfilePage()
    {
        return  HTTP_ROOT_DIR . $this->editprofilepage;
    }

    public function getUnreadMessagesCount()
    {
        $msg_simple_count = 0;
        // passing true means get unread message
        $msg_simpleAr =  MultiPort::getUserMessages($this, true);
        foreach ($msg_simpleAr as $msg_simple_provider) {
            $msg_simple_count += count($msg_simple_provider);
        }
        return intval($msg_simple_count);
    }

    /*
   * setters
    */
    public function setFirstName($firstname)
    {
        $this->nome = $firstname;
    }
    public function setLastName($lastname)
    {
        $this->cognome = $lastname;
    }

    public function setType($type)
    {
        $this->tipo = $type;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPhoneNumber($phone_number)
    {
        $this->telefono = $phone_number;
    }

    //  public function setUserName($username) {
    //    // NON SI PUO' MODIFICARE LO USERNAME
    //  }

    public function setLayout($layout)
    {
        if ($layout == 'none' || $layout == 'null' || $layout == 'NULL') {
            $this->template_family = '';
        } else {
            $this->template_family = $layout;
        }
    }
    public function setAddress($address)
    {
        $this->indirizzo = $address;
    }

    public function setCity($city)
    {
        $this->citta = $city;
    }

    public function setProvince($province)
    {
        $this->provincia = $province;
    }

    public function setCountry($country)
    {
        $this->nazione = $country;
    }

    public function setFiscalCode($fiscal_code)
    {
        $this->codice_fiscale = $fiscal_code;
    }

    public function setBirthDate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    public function setBirthCity($birthcity)
    {
        $this->birthcity = $birthcity;
    }

    public function setBirthProvince($birthprovince)
    {
        $this->birthprovince = $birthprovince;
    }

    public function setGender($gender)
    {
        $this->sesso = $gender;
    }



    /**
     *
     * @param $user_id
     */
    // FIXME: controllare se servono questi controlli
    public function setUserId($id_user)
    {
        if (is_numeric($id_user)) {
            $this->id_user = (int)$id_user;
        }
    }



    protected function setHomePage($home_page)
    {
        $this->homepage = $home_page;
    }

    protected function setEditProfilePage($relativeUrl)
    {
        if (isset($relativeUrl) && strlen($relativeUrl) > 0) {
            // make it leading slash-agnostic
            if ($relativeUrl[0] !== DIRECTORY_SEPARATOR) {
                $relativeUrl = DIRECTORY_SEPARATOR . $relativeUrl;
            }

            if (is_file(ROOT_DIR . $relativeUrl)) {
                $this->editprofilepage = $relativeUrl;
            } else {
                $this->editprofilepage = '';
            }
        } else {
            $this->editprofilepage = '';
        }
    }

    public function setTesters($testersAr = [])
    {
        // testersAr is an array containing tester ids.
        $this->testers = $testersAr;
    }

    public function setStatus($status)
    {
        $this->stato = $status;
    }

    public function setLanguage($language)
    {
        $this->lingua = $language;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function setPassword($password)
    {
        if (DataValidator::validatePassword($password, $password) != false) {
            $this->password = sha1($password);
        }
    }

    public function setCap($cap)
    {
        $this->cap = $cap;
    }

    public function setSerialNumber($matricola)
    {
        $this->SerialNumber = $matricola;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }


    public function addTester($tester)
    {
        $tester = DataValidator::validateTestername($tester, MULTIPROVIDER);
        if ($tester !== false) {
            $this->setTesters($this->getTesters());
            array_push($this->testers, $tester);
            return true;
        }
        return false;
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $user_dataAr = [
            'id_utente'              => $this->id_user,
            'nome'                   => $this->nome,
            'cognome'                => $this->cognome,
            'tipo'                   => $this->tipo,
            'e_mail'                 => $this->email,
            'username'               => $this->username,
            'password'               => $this->password, // <--- fare attenzione qui
            'layout'                 => $this->template_family,
            'indirizzo'              => ($this->indirizzo != 'NULL') ? $this->indirizzo : '',
            'citta'                  => ($this->citta != 'NULL') ? $this->citta : '',
            'provincia'              => ($this->provincia != 'NULL') ? $this->provincia : '',
            'nazione'                => $this->nazione,
            'codice_fiscale'         => $this->codice_fiscale,
            'birthdate'              => $this->birthdate,
            'birthcity'                 => ($this->birthcity != null) ? $this->birthcity : '',
            'birthprovince'             => $this->birthprovince,
            'sesso'                  => $this->sesso,
            'telefono'               => ($this->telefono != 'NULL') ? $this->telefono : '',
            'stato'                  => $this->stato,
            'lingua'                 => $this->lingua,
            'timezone'               => $this->timezone,
            'cap'                    => ($this->cap != null) ? $this->cap : '',
            'matricola'              => ($this->SerialNumber != null) ? $this->SerialNumber : '',
            'avatar'                 => ($this->avatar != null) ? $this->avatar : '',

        ];


        if ($this instanceof ADAPractitioner && $this->isSuper) {
            $user_dataAr['tipo'] = AMA_TYPE_SUPERTUTOR;
        }

        return $user_dataAr;
    }

    // MARK: existing methods

    public function getMessagesFN($id_user)
    {
    }


    // FIXME: sarebbe statico, ma viene usato come metodo non statico.
    public static function convertUserTypeFN($id_profile, $translate = true)
    {
        switch ($id_profile) {
            case 0: // reserved
                $user_type = 'utente ada';
                break;

            case AMA_TYPE_AUTHOR:
                $user_type = 'autore';
                break;

            case AMA_TYPE_ADMIN:
                $user_type = 'amministratore';
                break;

            case AMA_TYPE_TUTOR:
                $user_type = 'tutor';
                break;
            case AMA_TYPE_SWITCHER:
                $user_type = 'switcher';
                break;
            case AMA_TYPE_SUPERTUTOR:
                $user_type = 'SuperTutor';
                break;
            case AMA_TYPE_VISITOR:
                $user_type = 'guest';
                break;

            case AMA_TYPE_STUDENT:
            default:
                // FIXME: trovare dove controlliamo $user_type == 'studente' e sostituire con $user_type == 'utente'
                $user_type = 'utente';
        }
        return ($translate ? translateFN($user_type) : $user_type);
    }

    public function getAgendaFN($id_user)
    {
    }

    public static function getOnlineUsersFN($id_course_instance, $mode)
    {
    }

    private static function onlineUsersFN($id_course_instance, $mode = 0)
    {
    }

    public static function isSomeoneThereFN($id_course_instance, $id_node)
    {
    }

    public function getLastAccessFN($id_course_instance = "")
    {
    }

    public static function isVisitedByUserFN($node_id, $course_instance_id, $user_id)
    {
    }

    public static function isVisitedByClassFN($node_id, $course_instance_id, $course_id)
    {
    }

    public static function isVisitedFN($node_id)
    {
    }

    /**
     * check if the user is an adult or not
     *
     * @return bool true if adult, false if not or error
     */
    public function isAdult(): bool
    {
        try {
            $isAdult = false;
            $birthDate = DateTimeImmutable::createFromFormat(str_replace('%', '', ADA_DATE_FORMAT), $this->getBirthDate());
            $today = new DateTimeImmutable();
            $bMonth = (int) $birthDate->format('n');
            $bDay = (int) $birthDate->format('j');
            $bLeap = (int) $birthDate->format('L') === 1;
            $monthLength = [31, $bLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            // Check the range of the day
            $dateOK = $bDay > 0 && $bDay <= $monthLength[$bMonth - 1];
            if ($dateOK) {
                $diff = $today->diff($birthDate);
                $isAdult = $diff->y >= 18;
            }
        } catch (Throwable) {
            $isAdult = false;
        }
        return $isAdult;
    }
}

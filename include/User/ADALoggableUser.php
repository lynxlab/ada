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

use Error;
use Exception;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;
use TypeError;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class ADALoggableUser extends ADAGenericUser
{
    public function __construct($user_dataHa = [])
    {
        /*
   * $user_dataHa is an associative array with the following keys:
   * nome, cognome, tipo, e_mail, telefono, username, layout, indirizzo, citta,
   * provincia, nazione, codice_fiscale, sesso,
   * telefono, stato
        */
        if (isset($user_dataHa['id']) && DataValidator::isUinteger($user_dataHa['id'])) {
            $this->id_user = $user_dataHa['id'];
        } else {
            $this->id_user = 0;
        }
        $this->nome                   = $user_dataHa['nome'] ?? null;
        $this->cognome                = $user_dataHa['cognome'] ?? null;
        $this->tipo                   = $user_dataHa['tipo'] ?? null;
        $this->email                  = $user_dataHa['email'] ?? null;
        $this->username               = $user_dataHa['username'] ?? null;
        $this->template_family        = $user_dataHa['layout'] ?? null;
        $this->indirizzo              = $user_dataHa['indirizzo'] ?? null;
        $this->citta                  = $user_dataHa['citta'] ?? null;
        $this->provincia              = $user_dataHa['provincia'] ?? null;
        $this->nazione                = $user_dataHa['nazione'] ?? null;
        $this->codice_fiscale         = $user_dataHa['codice_fiscale'] ?? null;
        $this->birthdate              = $user_dataHa['birthdate'] ?? null;
        $this->sesso                  = $user_dataHa['sesso'] ?? null;

        $this->telefono               = $user_dataHa['telefono'] ?? null;

        $this->stato                  = $user_dataHa['stato'] ?? null;
        $this->lingua                 = $user_dataHa['lingua'] ?? null;
        $this->timezone               = $user_dataHa['timezone'] ?? null;

        $this->cap                    = $user_dataHa['cap'] ?? null;
        $this->SerialNumber           = $user_dataHa['matricola'] ?? null;
        $this->avatar                 = $user_dataHa['avatar'] ?? null;

        $this->birthcity              = $user_dataHa['birthcity'] ?? null;
        $this->birthprovince          = $user_dataHa['birthprovince'] ?? null;
    }

    public function fillWithArrayData($dataArr = null)
    {
        if (!is_null($dataArr)) {
            $this->setFirstName($dataArr['nome']);
            $this->setLastName($dataArr['cognome']);
            $this->setFiscalCode($dataArr['codice_fiscale']);
            $this->setEmail($dataArr['email']);
            if (trim($dataArr['password']) != '') {
                $this->setPassword($dataArr['password']);
            }
            $this->setSerialNumber(isset($dataArr['matricola']) ?: null);
            $this->setLayout($dataArr['layout']);
            $this->setAddress($dataArr['indirizzo']);
            $this->setCity($dataArr['citta']);
            $this->setProvince($dataArr['provincia']);
            $this->setCountry($dataArr['nazione']);
            $this->setBirthDate($dataArr['birthdate']);
            $this->setGender($dataArr['sesso'] ?? null);
            $this->setPhoneNumber($dataArr['telefono']);
            $this->setLanguage($dataArr['lingua'] ?? null);
            //        $this->setAvatar($dataArr['avatar']);
            if (isset($_SESSION['uploadHelper']['fileNameWithoutPath'])) {
                $this->setAvatar($_SESSION['uploadHelper']['fileNameWithoutPath']);
            }
            $this->setCap($dataArr['cap']);
            if (isset($dataArr['stato'])) {
                $this->setStatus($dataArr['stato']);
            }
            $this->setBirthCity($dataArr['birthcity'] ?? null);
            $this->setBirthProvince($dataArr['birthprovince'] ?? null);
        }
    }

    /**
     * Anonymize user data by replacing the data passed in the keys of $dataArr
     * with random strings.
     * Default anonymized values are: 'nome', 'cognome', 'codice_fiscale',
     * 'email', 'username', 'password', 'matricola'
     *
     * NOTE: this method will just DIE if MODULES_GDPR is not installed
     *
     * @param array $dataArr
     * @return ADALoggableUser
     */
    public function anonymize($dataArr = ['nome', 'cognome', 'codice_fiscale', 'email', 'username', 'password', 'matricola'])
    {
        if (ModuleLoaderHelper::isLoaded('GDPR') === true) {
            try {
                $userArr = $this->toArray();
                foreach ($dataArr as $key) {
                    $value = bin2hex(random_bytes(random_int(8, 16)));
                    if (strcmp($key, 'username') === 0) {
                        $this->username = $value;
                    } else {
                        $userArr[$key] = $value;
                    }
                }
                $this->fillWithArrayData($userArr);
                $this->setStatus(ADA_STATUS_ANONYMIZED);
                return $this;
            } catch (TypeError | Error) {
                die("An unexpected error has occurred");
            } catch (Exception) {
                // If you get this message, the CSPRNG failed hard.
                die("Could not generate a random string. Is our OS secure?");
            }
        } else {
            die("anonymize method cannot be called when MODULES_GDPR is not installed");
        }
    }

    // MARK: USARE MultiPort::getUserMessages
    public function getMessagesFN($id_user)
    {
        return '';
    }

    // MARK: usare MultiPort::getUserAgenda
    public function getAgendaFN($id_user)
    {
        return '';
    }

    public static function getOnlineUsersFN($id_course_instance, $mode)
    {
        $data =  self::onlineUsersFN($id_course_instance, $mode);
        if (gettype($data) == 'string' || $data == 'null') {
            return $data;
        } else {
            $user_list = BaseHtmlLib::plainListElement('class:user_online', $data, false);
            $user_list_html = $user_list->getHtml();
            /*
             *
            $t = new Table();
            $t->initTable('0','center','0','0','100%','','','','','','1');
            $t->setTable($data,$caption="",$summary="Utenti online");
            $tabled_data = $t->getTable();
             */
            //            return $tabled_data;
            return $user_list_html;
        }
    }

    private static function onlineUsersFN($id_course_instance, $mode = 0)
    {
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_node = $_SESSION['sess_id_node'] ?? null;
        $sess_id_course = $_SESSION['sess_id_course'] ?? null;
        $sess_id_user = $_SESSION['sess_id_user'];

        /*
     Viene passato $id_course_instance per filtrare l'istanza di corso
     su cui si sta lavorando.
        */

        /**
         * @author giorgio 28/giu/2013
         * fixed bug: if neither course instance nor session course instance is set, then return null
         */
        if (
            !isset($id_course_instance) && (
                !isset($sess_id_course_instance) || is_null($sess_id_course_instance)
            )
        ) {
            return null;
        }

        if (!isset($id_course_instance)) {
            $id_course_instance = $sess_id_course_instance;
        }
        $now = time();
        // $mode=0;  forcing mode to increase speed
        $tolerance = 240; // 4 minuti
        $limit = $now - $tolerance;
        $out_fields_ar = ['data_visita', 'id_utente_studente', 'session_id'];
        $clause = "data_visita > $limit and id_istanza_corso ='$id_course_instance'";
        $dataHa = $dh->doFindNodesHistoryList($out_fields_ar, $clause);
        if (AMADataHandler::isError($dataHa) || empty($dataHa)) {
            if (gettype($dataHa) == "object") {
                $msg = $dataHa->getMessage();
                return $msg;
            }
            // header("Location: $error?err_msg=$msg");
        } else {
            switch ($mode) {
                case 3:   // username, link to chat
                    // should read from chat table...
                    break;
                case 2:  // username, link to msg & tutor
                    if (count($dataHa)) {
                        //$online_usersAr = array();
                        $online_users_idAr = [];
                        foreach ($dataHa as $user) {
                            $user_id = $user[2];
                            if (!in_array($user_id, $online_users_idAr)) {
                                if ($sess_id_user == $user_id) {
                                    // ora bisogna controllare che la sessione sia la stessa
                                    $user_session_id = $user[3];
                                    if ($user_session_id == session_id()) {


                                        /**
                                         * @author giorgio 17/feb/2016
                                         * added continue; to remove 'io'
                                         * from the online users list
                                         */
                                        continue;

                                        $online_users_idAr[] = $user_id;
                                        //$online_usersAr[$user_id]['user']= "<img src=\"img/_student.png\" border=\"0\"> ".translateFN("io");
                                        $online_usersAr[] = translateFN("io");
                                        // if we don't want to show this user:
                                        //$online_usersAr[$user_id]['user']= "";
                                    } else {
                                        $online_users_idAr[] = $user_id;
                                        //$online_usersAr[$user_id]['user']= "<img src=\"img/_student.png\" border=\"0\"> ".translateFN("Un utente con i tuoi dati &egrave; gi&agrave; connesso!");
                                        $online_usersAr[] = translateFN("Un utente con i tuoi dati &egrave; gi&agrave; connesso!");
                                    }
                                    $currentUserObj = $_SESSION['sess_userObj'];
                                    $current_profile = $currentUserObj->getType();
                                    if ($current_profile == AMA_TYPE_STUDENT) {
                                    }
                                } else {
                                    $userObj = MultiPort::findUser($user_id);
                                    if (gettype($userObj) == 'object') { //instanceof ADAUser) { // && $userObj->getStatus() == ADA_STATUS_REGISTERED) {
                                        //                                    $userObj = new User($user_id);
                                        $online_users_idAr[] = $user_id;
                                        $id_profile = $userObj->getType(); //$userObj->tipo;
                                        if ($id_profile == AMA_TYPE_TUTOR) {
                                            $online_usersAr[] = $userObj->username . " |<a href=\"$http_root_dir/comunica/sendMessage.php?destinatari=" . $userObj->username . "\"  target=\"_blank\">" . translateFN("scrivi un messaggio") . "</a> |"
                                                . " <a href=\"view.php?id_node=$sess_id_node&guide_user_id=" . $userObj->getId() . "\"> " . translateFN("segui") . "</a> |";
                                            //$online_usersAr[$user_id]['user']= "<img src=\"img/_tutor.png\" border=\"0\"> ".$userObj->username. " |<a href=\"$http_root_dir/comunica/sendMessage.php?destinatari=". $userObj->username."\"  target=\"_blank\">".translateFN("scrivi un messaggio")."</a> |";
                                            //$online_usersAr[$user_id]['user'].= " <a href=\"view.php?id_node=$sess_id_node&guide_user_id=".$userObj->id."\"> ".translateFN("segui")."</a> |";
                                        } else {    // STUDENT
                                            // $online_usersAr[$user_id]['user']= "<a href=\"student.php?op=listStudents&id_course_instance=$sess_id_course_instance&id_course=$sess_id_course\"><img src=\"img/_student.png\" border=0></a> ";
                                            $online_usersAr[] = $userObj->username . " |<a href=\"$http_root_dir/comunica/sendMessage.php?destinatari=" . $userObj->username . "\"  target=\"_blank\">" . translateFN("scrivi un messaggio") . "</a> |";
                                            //                                            $online_usersAr[$user_id]['user']= "<img src=\"img/_student.png\" border=\"0\"> ";
                                            //                                            $online_usersAr[$user_id]['user'].= $userObj->username. " |<a href=\"$http_root_dir/comunica/sendMessage.php?destinatari=". $userObj->username."\"  target=\"_blank\">".translateFN("scrivi un messaggio")."</a> |";
                                        }
                                    }
                                }
                            }
                        }

                        return ($online_usersAr ?? null);
                    } else {
                        return  translateFN("Nessuno");
                    }
                    break;
                case 1: // username, mail and timestemp // @FIXME
                    if (count($dataHa)) {
                        //$online_usersAr = array();
                        $online_users_idAr = [];
                        foreach ($dataHa as $user) {
                            $user_id = $user[2];
                            if (!in_array($user_id, $online_users_idAr)) {
                                $userObj = MultiPort::findUser($user_id);
                                $time = date("H:i:s", $user[1]);
                                $online_users_idAr[] = $user_id;
                                $online_usersAr[$user_id]['user'] = $userObj->username;
                                $online_usersAr[$user_id]['email'] = $userObj->email;
                                $online_usersAr[$user_id]['time'] = $time;
                            }
                        }
                        return  $online_usersAr;
                    } else {
                        return  translateFN("Nessuno");
                    }
                    break;
                case 0:
                default:
                    if (count($dataHa)) {
                        $online_users_idAr = [];
                        foreach ($dataHa as $user) {
                            $user_id = $user[2];
                            if (!in_array($user_id, $online_users_idAr)) {
                                $online_users_idAr[] = $user_id;
                            }
                        }
                        return count($online_users_idAr) . " " . translateFN("studente/i"); // only number of users online
                    } else {
                        return translateFN("Nessuno");
                    }
            }
        }
    }

    public static function isSomeoneThereCourseFN($id_course_instance)
    {
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'] ?? null;
        $sess_id_node = $_SESSION['sess_id_node'] ?? null;

        if (!isset($id_course_instance)) {
            $id_course_instance = $sess_id_course_instance;
        }
        if (!isset($id_node)) {
            $id_node = $sess_id_node;
        }

        $now = time();
        // $mode=0;  forcing mode to increase speed
        $tolerance = 600; // dieci minuti
        $limit = $now - $tolerance;
        $out_fields_ar = ['id_nodo', 'data_uscita', 'id_utente_studente'];
        $clause = "data_uscita > $limit and id_istanza_corso ='$id_course_instance'";
        $dataHa = $dh->doFindNodesHistoryList($out_fields_ar, $clause, true);
        if (AMADataHandler::isError($dataHa) || empty($dataHa)) {
            if (gettype($dataHa) == "object") {
                $msg = $dataHa->getMessage();
                return $msg;
            }
            // header("Location: $error?err_msg=$msg");
        } else {
            return $dataHa;
        }
    }

    public static function isSomeoneThereFN($id_course_instance, $id_node)
    {
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];

        if (!isset($id_course_instance)) {
            $id_course_instance = $sess_id_course_instance;
        }
        if (!isset($id_node)) {
            $id_node = $sess_id_node;
        }

        $now = time();
        // $mode=0;  forcing mode to increase speed
        $tolerance = 600; // dieci minuti
        $limit = $now - $tolerance;
        $out_fields_ar = ['data_uscita', 'id_utente_studente'];
        $clause = "data_uscita > $limit and id_istanza_corso ='$id_course_instance' and id_nodo ='$id_node'";
        $dataHa = $dh->doFindNodesHistoryList($out_fields_ar, $clause);
        if (AMADataHandler::isError($dataHa) || empty($dataHa)) {
            if (gettype($dataHa) == "object") {
                $msg = $dataHa->getMessage();
                return $msg;
            }
            // header("Location: $error?err_msg=$msg");
        } else {
            return (count($dataHa) >= 1);
        }
    }


    public function getLastAccessFN($id_course_instance = "", $type = "T", $dh = null)
    {
        if (is_null($dh)) {
            $dh = $GLOBALS['dh'];
        }
        // called by browsing/student.php

        if ($type == "UT") {
            $last_accessAr = $this->doGetLastAccessFN($id_course_instance, $dh, false);
        } else {
            $last_accessAr = $this->doGetLastAccessFN($id_course_instance, $dh);
        }
        if (is_array($last_accessAr)) {
            switch ($type) {
                case "N":
                    return  $last_accessAr[0]; //es. 100_34
                    break;
                case "T":
                default:
                    // vito, 11 mar 2009
                    //return  substr(Utilities::ts2dFN($last_accessAr[1]),0,5); // es. 10/06
                    return  substr($last_accessAr[1], 0, 5); // es. 10/06
                    break;
                case "UT":
                    return  $last_accessAr[1]; // unixtime
            }
        } else {
            return "-";
        }
    }

    /**
     *
     * @param  $id_course_instance
     * @return array
     */
    private function doGetLastAccessFN($id_course_instance = "", $provider_dh = null, $return_dateonly = true)
    {
        // if used by student before entering a course, we must pass the DataHandler
        if ($provider_dh == null) {
            $provider_dh = $GLOBALS['dh'];
        }
        //$error = $GLOBALS['error'];
        // $debug = $GLOBALS['debug'];
        $sess_id_user = $_SESSION['sess_id_user'];

        if (!isset($this->id_user)) {
            $id_user = $sess_id_user;
        } else {
            $id_user = $this->id_user;
        }

        if ($id_course_instance) {
            $last_visited_node = $provider_dh->getLastVisitedNodes($id_user, $id_course_instance, 10);
            /*
            * vito, 10 ottobre 2008: $last_visited_node è Array([0]=>Array([id_nodo], ...))
            */
            if (!AMADB::isError($last_visited_node) && is_array($last_visited_node) && isset($last_visited_node[0])) {
                $last_visited_time =  ($return_dateonly) ? AMADataHandler::tsToDate($last_visited_node[0]['data_uscita']) : $last_visited_node[0]['data_uscita'];

                return [$last_visited_node[0]['id_nodo'], $last_visited_time];
            } else {
                return "-";
            }
        } else {
            /*
            * Sara, 2/07/2014
            * return the last access between all instances course
            */
            $serviceProviders = $this->getTesters();
            if (!empty($serviceProviders) && is_array($serviceProviders)) {
                $i = 0;
                foreach ($serviceProviders as $Provider) {
                    $provider_dh = AMADataHandler::instance(MultiPort::getDSN($Provider));
                    $courseInstances_provider = $provider_dh->getCourseInstancesForThisStudent($this->getId());
                    if (AMADataHandler::isError($courseInstances_provider)) {
                        $courseInstances_provider = new ADAError($courseInstances_provider);
                    } else {
                        $istance_testerAr[$i] = ['istances' => $courseInstances_provider, 'provider' => $Provider];
                    }
                    $i++;
                }
            }
            if (!empty($istance_testerAr)) {
                $Max = 0;
                $id_nodo = null;
                foreach ($istance_testerAr as $istanceTs) {
                    $courseInstancesAr = $istanceTs['istances'];
                    $pointer = $istanceTs['provider'];
                    $tester = AMADataHandler::instance(MultiPort::getDSN($pointer));
                    foreach ($courseInstancesAr as $courseInstance) {
                        $id_instance = $courseInstance['id_istanza_corso'];
                        $last_access = $tester->getLastVisitedNodes($id_user, $id_instance, 10);
                        if (!AMADB::isError($last_access) && is_array($last_access) && count($last_access)) {
                            $last_accessAr = [$last_access[0]['id_nodo'], $last_access[0]['data_uscita']];

                            if ($last_accessAr[1] > $Max) {
                                $id_nodo = $last_accessAr[0];
                                $Max = $last_accessAr[1];
                            }
                        }
                    }
                }
                $Last_accessAr = [0 => $id_nodo, 1 => $Max];
                return $Last_accessAr;
            } else {
                return "-";
            }
        }
    }

    public static function isVisitedByUserFN($node_id, $course_instance_id, $user_id)
    {
        //  returns  the number of visits for this node


        $dh = $GLOBALS['dh'] ?? null;
        $error = $GLOBALS['error'] ?? null;
        $http_root_dir = $GLOBALS['http_root_dir'] ?? null;
        $debug = $GLOBALS['debug'] ?? null;

        $visit_count = 0;
        $out_fields_ar = ['id_utente_studente', 'data_visita', 'data_uscita'];
        $history = $dh->findNodesHistoryList($out_fields_ar, $user_id, $course_instance_id, $node_id);
        foreach ($history as $visit) {
            // $debug=1; Utilities::mydebug(__LINE__,__FILE__,$visit);$debug=0;
            if ($visit[1] == $user_id) {
                $visit_count++;
            }
        }
        return $visit_count;
    }

    public static function isVisitedByClassFN($node_id, $course_instance_id, $course_id)
    {
        //  returns  the number of visits for this node for instance $course_instance_id

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        $sess_id_node = $_SESSION['sess_id_node'];
        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $debug = $GLOBALS['debug'] ?? null;

        $out_fields_ar = ['id_nodo', 'data_visita'];
        $history = $dh->findNodesHistoryList($out_fields_ar, "", $course_instance_id, $node_id);
        $visit_count = count($history);

        return $visit_count;
    }

    public static function isVisitedFN($node_id)
    {
        //  returns  the number of global visits for this node

        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'] ?? null;
        $visit_count = 0;
        $out_fields_ar = ['n_contatti'];
        //$search_fields_ar = array('id_nodo');
        //$history = $dh->findNodesListByKey($out_fields_ar, $node_id, $search_fields_ar); ???
        $clause = "id_nodo = '$node_id'";
        $history = $dh->doFindNodesList($out_fields_ar, $clause);
        $visit_count = sizeof($history) - 1;
        return $visit_count;
    }

    /**
     * gets the last files for the user in course isntance shared docs area.
     *
     * @param number $id_course_instance isntance id for which to get the files.
     * @param number $maxFiles max number of  files to return
     *
     * @return array if success|null if no file exists or on error
     */
    public function getNewFiles($id_course_instance, $maxFiles = 3)
    {
        $dh        = $GLOBALS['dh'];
        $common_dh = $GLOBALS['common_dh'];

        $retval = null;

        $lastAccessArr = $this->doGetLastAccessFN($id_course_instance, null, false);
        $lastAccess = (!is_array($lastAccessArr)) ? time() : intval($lastAccessArr[1]);

        $id_course = $dh->getCourseIdForCourseInstance($id_course_instance);
        $course_ha = $dh->getCourse($id_course);

        if (!AMADataHandler::isError($course_ha)) {
            $author_id = $course_ha['id_autore'];
            //il percorso in cui caricare deve essere dato dal media path del corso, e se non presente da quello di default
            if ($course_ha['media_path'] != "") {
                $media_path = $course_ha['media_path'];
            } else {
                $media_path = MEDIA_PATH_DEFAULT . $author_id;
            }
            $download_path = ROOT_DIR . $media_path;
        }

        if (isset($download_path) && is_dir($download_path)) {
            $sortedDir = [];
            $handle = opendir($download_path);

            while ($file = readdir($handle)) {
                if ($file != '.' && $file != '..') {
                    $ctime  = filectime($download_path . '/' . $file);
                    $filesPart = explode('_', $file, 6);
                    // index 0 is the course instance id
                    $file_id_course = $filesPart[0];
                    // index 1 is the file sender, get her info
                    $file_senderArray = $common_dh->getUserInfo($filesPart[1]);
                    /*
                     *  add files only if:
                     *  + they belong to the passed instance OR
                     *      they've been added to the course by an author
                     *  + they've been modified after user last access
                     */
                    if (
                        !AMADB::isError($file_senderArray) &&
                        ($file_id_course == $id_course_instance ||
                            ($file_senderArray['tipo'] == AMA_TYPE_AUTHOR && $file_id_course == $id_course)) &&
                        $ctime >= $lastAccess
                    ) {
                        $arrKey = $ctime . '-' . $file;
                        $sortedDir[$arrKey]['link'] = $file;
                        $sortedDir[$arrKey]['id_node'] = $id_course . '_' . ADA_DEFAULT_NODE;
                        $sortedDir[$arrKey]['id_course'] = $id_course;
                        $sortedDir[$arrKey]['id_course_instance'] = $id_course_instance;
                        $sortedDir[$arrKey]['displaylink'] = $filesPart[count($filesPart) - 1];
                    }
                }
            }
            closedir($handle);

            if (!empty($sortedDir)) {
                krsort($sortedDir);
                $retval = array_slice($sortedDir, 0, $maxFiles);
            }
        }
        return $retval;
    }

    /**
     * sets the proper $_SESSION var of userObj and redirects to user home page
     *
     * @param ADALoggableUser $userObj user object to be used to set $_SESSION vars
     * @param boolean $remindMe true if remindme check box has been checked
     * @param string $language lang selection at login form: language to be set
     * @param Object $loginProviderObj login provider class used, null if none used
     * @param string $redirectURL url where the user must be redirected
     * @param boolean $forceRedirect false to do NOT redirect and exit, but return a bool instead (defaults to true)
     */
    public static function setSessionAndRedirect($userObj, $remindMe, $language, $loginProviderObj = null, $redirectURL = null, $forceRedirect = true)
    {
        if ($userObj->getStatus() == ADA_STATUS_REGISTERED) {
            /**
             * @author giorgio 12/dic/2013
             * when a user sucessfully logs in, regenerate her session id.
             * this fixes a quite big problem in the 'history_nodi' table
             */
            if (isset($remindMe) && intval($remindMe) > 0) {
                if (session_status() != PHP_SESSION_NONE) {
                    session_write_close();
                }
                ini_set('session.cookie_lifetime', 60 * 60 * 24 * ADA_SESSION_LIFE_TIME);  // day cookie lifetime
                session_start();
            }
            session_regenerate_id(true);

            $user_default_tester = $userObj->getDefaultTester();

            if (!MULTIPROVIDER && $userObj->getType() != AMA_TYPE_ADMIN) {
                if ($user_default_tester != $GLOBALS['user_provider']) {
                    // if the user is trying to login in a provider
                    // that is not his/her own,
                    // redirect to his/her own provider home page
                    $redirectURL = preg_replace("/(http[s]?:\/\/)(\w+)[.]{1}(\w+)/", "$1" . $user_default_tester . ".$3", $userObj->getHomePage());
                    if ($forceRedirect) {
                        header('Location:' . $redirectURL);
                        exit();
                    } else {
                        return true;
                    }
                }
            }

            if (ModuleLoaderHelper::isLoaded('GDPR') === true) {
                // check if user has accepted the mandatory privacy policies
                $gdprApi = new GdprAPI();
                if (!$gdprApi->checkMandatoryPoliciesForUser($userObj)) {
                    $_SESSION[GdprPolicy::SESSIONKEY]['post'] = $_POST;
                    if (!is_null($loginProviderObj)) {
                        $_SESSION[GdprPolicy::SESSIONKEY]['post']['selectedLoginProvider'] = basename(str_replace('\\', '/', $loginProviderObj::class));
                        $_SESSION[GdprPolicy::SESSIONKEY]['post']['selectedLoginProviderID'] = $loginProviderObj->getID();
                    }
                    if (!is_null($redirectURL)) {
                        $_SESSION['subscription_page'] = $redirectURL;
                    }
                    $_SESSION[GdprPolicy::SESSIONKEY]['redirectURL'] = !is_null($redirectURL) ? $redirectURL : $userObj->getHomePage();
                    $_SESSION[GdprPolicy::SESSIONKEY]['userId'] = $userObj->getId();
                    $_SESSION[GdprPolicy::SESSIONKEY]['loginRepeaterSubmit'] = is_null($loginProviderObj) ? basename($_SERVER['SCRIPT_NAME']) : 'index.php';
                    Utilities::redirect(MODULES_GDPR_HTTP . '/' . GdprPolicy::ACCEPTPOLICIESPAGE);
                }
            }

            // user is a ADAuser with status set to 0 OR
            // user is admin, author or switcher whose status is by default = 0
            $_SESSION['sess_user_language'] = $language;
            $_SESSION['sess_id_user'] = $userObj->getId();
            $GLOBALS['sess_id_user']  = $userObj->getId();
            $_SESSION['sess_id_user_type'] = $userObj->getType();
            $GLOBALS['sess_id_user_type']  = $userObj->getType();
            $_SESSION['sess_userObj'] = $userObj;

            /* unset $_SESSION['service_level'] to allow the correct label translatation according to user language */
            unset($_SESSION['service_level']);

            if ($user_default_tester !== null) {
                $_SESSION['sess_selected_tester'] = $user_default_tester;
                // sets var for non multiprovider environment
                $GLOBALS['user_provider'] = $user_default_tester;
            }

            if (!is_null($loginProviderObj)) {
                $_SESSION['sess_loginProviderArr']['className'] = $loginProviderObj::class;
                $_SESSION['sess_loginProviderArr']['id'] = $loginProviderObj->getID();
                $loginProviderObj->addLoginToHistory($userObj->getId());
            }
            if (is_null($redirectURL)) {
                if ($userObj->getType() == AMA_TYPE_STUDENT && defined('FORCE_STUDENT_LOGIN_REDIRECT') && strlen(FORCE_STUDENT_LOGIN_REDIRECT) > 0) {
                    $redirectURL = FORCE_STUDENT_LOGIN_REDIRECT;
                } else {
                    $redirectURL = $userObj->getHomePage();
                }

                if (isset($_REQUEST['r']) && strlen(trim($_REQUEST['r'])) > 0) {
                    $r = trim($_REQUEST['r']);
                    // redirect only if passed URL host matches HTTP_ROOT_DIR host
                    if (parse_url($r, PHP_URL_HOST) === parse_url(HTTP_ROOT_DIR, PHP_URL_HOST)) {
                        $redirectURL = $r;
                        unset($_REQUEST['r']);
                    }
                }
            }
            if ($forceRedirect) {
                header('Location:' . $redirectURL);
                exit();
            } else {
                return true;
            }
        }

        return false;
    }

    public function setExtras($extra)
    {
    }

    public function hasExtra()
    {
        return false;
    }

    public function saveUsingAjax()
    {
    }

    public function isSuper()
    {
        return false;
    }
}

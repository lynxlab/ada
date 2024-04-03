<?php

/**
 * DB_read
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        index
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\DBRead;

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\Layout;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Main\User\ADAGuest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Reads a ADA node from database.
 *
 * @param  string $id_node - a valid ADA node identifier. e.g. '1_0'
 * @return \Lynxlab\ADA\Main\Node\Node a Node object on success, on failure raises a ADA_Error.
 */
function read_node_from_DB($id_node)
{
    if (DataValidator::validate_node_id($id_node) !== false) {
        $read_id_node = $id_node;
    } else {
        $read_id_node = $_SESSION['sess_id_node'] ?? null;
    }

    if (isset($read_id_node)) {
        $nodeObj = new Node($read_id_node);
        if ($nodeObj->full == 0) {
            /*
             * Return a ADA_Error object with delayedErrorHandling set to TRUE.
             */
            return new ADA_Error(
                null,
                translateFN('Errore in lettura oggetto nodo'),
                'read_node_from_DB',
                ADA_ERROR_ID_NODE_REQUIRED_BUT_NOT_FOUND,
                null,
                null,
                true
            );
        }
        return $nodeObj;
    }
    /*
     * Return a ADA_Error object with delayedErrorHandling set to TRUE.
     */
    return new ADA_Error(
        null,
        translateFN('Errore in lettura oggetto nodo'),
        'read_node_from_DB',
        ADA_ERROR_ID_NODE_REQUIRED_BUT_NOT_FOUND,
        null,
        null,
        true
    );
}

/**
 * Wrapper function for read_course_from_DB.
 * @see read_course_from_DB
 */
function read_course($id_course = null)
{
    /*
     * Return the course object in sess_courseObj
     */
    if (is_null($id_course)) {
        $sess_courseObj = $_SESSION['sess_courseObj'] ?? null;
        if ($sess_courseObj instanceof Course) {
            return $sess_courseObj;
        }
        return new ADA_Error(
            null,
            translateFN('Errore in lettura oggetto corso in sessione'),
            'read_course',
            null,
            null,
            null,
            true
        );
    }

    $sess_id_course = $_SESSION['sess_id_course'] ?? null;

    if (DataValidator::is_uinteger($id_course) !== false) {
        $read_id_course = $id_course;
    } else {
        $read_id_course = $sess_id_course;
    }

    if ($read_id_course == $sess_id_course) {
        $sess_courseObj = $_SESSION['sess_courseObj'] ?? null;
        if ($sess_courseObj instanceof Course && $sess_courseObj->getId() == $read_id_course) {
            return $_SESSION['sess_courseObj'];
        }
    }
    /*
     * get course object from database
     */
    return read_course_from_DB($read_id_course);
}

/**
 * Reads a ADA service from database.
 * @param  int $id_course - a valid ADA service identifier
 * @return a Course object on success, on failure raises a ADA_Error
 */
function read_course_from_DB($id_course)
{

    if (isset($id_course)) {
        $courseObj = new Course($id_course);
        if ($courseObj->full == 0) {
            /*
             * Return a ADA_Error object with delayedErrorHandling set to TRUE.
             */
            return new ADA_Error(
                null,
                translateFN('Errore in lettura oggetto corso'),
                'read_course_from_DB',
                null,
                null,
                null,
                true
            );
        }
        return $courseObj;
    } else {
        /*
         * Return a ADA_Error object with delayedErrorHandling set to TRUE.
         */
        return new ADA_Error(
            null,
            translateFN('Errore in lettura oggetto corso'),
            'read_course_from_DB',
            null,
            null,
            null,
            true
        );
    }
}

/**
 * @param  int $id_course_instance - a valid ADA
 * @return a Course_instance object on success, on failure raises a ADA_Error
 */
function read_course_instance_from_DB($id_course_instance)
{

    if (DataValidator::is_uinteger($id_course_instance) !== false) {
        $read_id_course_instance = $id_course_instance;
    } else {
        $read_id_course_instance = $_SESSION['sess_id_course_instance'];
    }

    if (isset($read_id_course_instance)) {
        $courseInstanceObj = new CourseInstance($read_id_course_instance);
        if ($courseInstanceObj->full == 0) {
            /*
             * Return a ADA_Error object with delayedErrorHandling set to TRUE.
             */
            return new ADA_Error(
                null,
                translateFN('Errore in lettura oggetto istanza corso'),
                'read_course_instance_from_DB',
                null,
                null,
                null,
                true
            );
        }
        return $courseInstanceObj;
    } else {
        /*
         * Return a ADA_Error object with delayedErrorHandling set to TRUE.
         */
        return new ADA_Error(
            null,
            translateFN('Errore in lettura oggetto istanza corso'),
            'read_course_instance_from_DB',
            null,
            null,
            null,
            true
        );
    }
}

/**
 * Wrapper function for read_user_from_DB.
 *
 * @return \Lynxlab\ADA\Main\User\ADAGenericUser
 * @see read_user_from_DB
 */
function read_user($id_user = null)
{

    /*
     * Return the user object in sess_userObj
     */
    if (is_null($id_user)) {
        $sess_userObj = $_SESSION['sess_userObj'];
        if ($sess_userObj instanceof ADAGenericUser) {
            return $sess_userObj;
        }
        return new ADA_Error(
            null,
            translateFN('Errore in lettura oggetto utente in sessione'),
            'read_user',
            null,
            null,
            null,
            true
        );
    }
    // FIXME: qui $id_user diventa ZERO. VEDIAMO DI CAPIRE PERCHE'.
    //$id_user = is_int($id_user) ? $id_user : 0;

    if (isset($_SESSION['sess_id_user']) && $id_user === $_SESSION['sess_id_user']) {
        $sess_userObj = $_SESSION['sess_userObj'];
        if ($sess_userObj instanceof ADAGenericUser && $sess_userObj->getId() == $id_user) {
            // QUI DEVO VEDERE QUALI SONO I TESTER ASSOCIATI A QUESTO UTENTE.
            $user_testersAr = $GLOBALS['common_dh']->get_testers_for_user($id_user);
            if (!AMA_Common_DataHandler::isError($user_testersAr)) {
                $sess_userObj->setTesters($user_testersAr);
                $_SESSION['sess_userObj'] = $sess_userObj;
            }

            return $_SESSION['sess_userObj'];
        }
    }

    return read_user_from_DB($id_user);
}
/**
 * Reads a ADA user from database.
 * @param  int $id_user - a valid ADA user identifier
 * @return \Lynxlab\ADA\Main\User\ADAAbstractUser object on success, on failure raises a ADA_Error
 */
function read_user_from_DB($id_user)
{

    if ($id_user > 0) {
        /*
         * leggi utente da database
         */
        $userObj = MultiPort::findUser($id_user);
        if (is_null($userObj)) {
            /*
             * Return a ADA_Error object with delayedErrorHandling set to TRUE.
             */
            return new ADA_Error(
                null,
                translateFN('Errore in lettura oggetto utente'),
                'read_user_from_DB',
                null,
                null,
                null,
                true
            );
        }
        return $userObj;
    } else {
        return new ADAGuest();
    }
    //$sess_id_user = $_SESSION['sess_id_user'];
    //$sess_id_course_instance = $_SESSION['sess_id_course_instance'];
    //$sess_user_level = $_SESSION['sess_user_level'];
    //
    //$dh = $GLOBALS['dh'];
    //
    //if (!isset($id_user))
    //   $id_user = $sess_id_user;
    //
    //if (isset($id_user)) {
    //             $userObj = new User($id_user);
    //             $id_profile = $userObj->tipo;
    //             if ($id_profile==AMA_TYPE_STUDENT) {
    //                  $userObj = new Student($id_user,$sess_id_course_instance);//
    ////                  echo "dopo userobj<BR>";
    //             }
    //             if (!is_object($userObj))   {
    //                                      $error_msg = translateFN("Errore nella creazione dell'utente $sess_id_user");
    //                                      //mydebug(__LINE__,__FILE__,array('msg'=>translateFN("ADA: Errore nella creazione dell'utente $sess_id_user")));
    //                                      $result = $error_msg; //
    //             } else {
    //                       if  (!$userObj->full) {
    //                                      $error_msg = $userObj->error_mg;
    //                                      $result = $error_msg; //
    //                       } else  {
    //
    //                         if ($id_profile==AMA_TYPE_STUDENT){
    //                              $level = 0;
    //                                    if (isset($sess_id_course_instance))
    //                                         $level = $dh->get_student_level($id_user,$sess_id_course_instance);
    //                                // or else:
    //                                  //  $data_Ha = $dh->get_subscription($id_user, $sess_id_course_instance);
    //                                  //  $level =  $data_Ha["livello"];
    //                                    $userObj->level = $level;
    //                          }
    //                          $result =$userObj;
    //                        }
    //            }
    //} else {
    //               $error_msg = translateFN("Errore: utente non specificato");
    //              //mydebug(__LINE__,__FILE__,array('msg'=>translateFN("ADA: Errore nella creazione dell'utente $sess_id_user")));
    //              $result = $error_msg; //
    //}
    //
    //return $result;
}


function read_layout_from_DB($id_profile, $family = "", $node_type = "", $node_author_id = "", $node_course_id = "", $module_dir = "")
{

    /**
     * obey MAINTENANCE_MODE if true
     */
    if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true && $_SESSION['sess_userObj']->getType() != AMA_TYPE_SWITCHER) {
        $GLOBALS['self'] = MAINTENANCE_TPL;
    }

    $self = $GLOBALS['self'] ?? null;
    if (empty($node_type)) {
        $read_node_type = $self;
    } else {
        $read_node_type = $node_type;
    }

    $layoutObj = new Layout($id_profile, $read_node_type, $family, $node_author_id, $node_course_id, $module_dir);
    // FIXME: controllare $layoutObj lanciare eventualmente un errore ADA_Error

    return $layoutObj;
}


function get_max_idFN($id_course = 1, $id_toc = '', $depth = 1)
{
    // return the max id_node of the course
    $dh = $GLOBALS['dh'];
    $id_node_max = $dh->doGet_max_idFN($id_course, $id_toc, $depth);
    // vito, 15/07/2009
    if (AMA_DataHandler::isError($id_node_max)) {
        /*
         * Return a ADA_Error object with delayedErrorHandling set to TRUE.
         */
        return new ADA_Error(
            $id_node_max,
            translateFN('Errore in lettura max id'),
            'get_max_idFN',
            null,
            null,
            null,
            true
        );
    }
    return $id_node_max;
}

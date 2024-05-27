<?php

namespace Lynxlab\ADA\Comunica\AddressBook;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class ADAAddressBook
{
    protected static function fillAddressBook(ADALoggableUser $userObj, $user_types_Ar = [])
    {
        $user_type = $userObj->getType();
        $common_dh = AMACommonDataHandler::getInstance();
        $dh = $GLOBALS['dh'];

        // this tells getUsersByType method to get nome, cognome....
        $retrieve_extended_data = true;

        if (!is_array($user_types_Ar[$user_type]) || empty($user_types_Ar[$user_type])) {
            return false;
        }


        switch ($user_type) {
            case AMA_TYPE_ADMIN:
                /*
                 * Ottieni tutti i practitioner, gli autori e gli switcher da tutti i
                 * tester
                 */
                // FIXME: differisce dagli altri casi !!!
                $users[] = $common_dh->getUsersByType($user_types_Ar[AMA_TYPE_ADMIN], $retrieve_extended_data);
                if (AMACommonDataHandler::isError($users)) {
                    // Gestione errore
                }

                break;

            case AMA_TYPE_SWITCHER:
                /*
                 * Ottieni tutti i practitioner e gli utenti dal suo tester
                 */
                $tester = $userObj->getDefaultTester();
                $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                $tester_info_Ar = $common_dh->getTesterInfoFromPointer($tester);
                $tester_name = $tester_info_Ar[1];

                $users[$tester_name] = $tester_dh->getUsersByType($user_types_Ar[AMA_TYPE_SWITCHER], $retrieve_extended_data);
                if (AMACommonDataHandler::isError($users)) {
                    $users[$tester_name] = [];
                }
                /*
                 * Ottiene tutti i practitioner presenti sul tester
                 */
                //         $practitioners_Ar = $tester_dh->getUsersByType(array(AMA_TYPE_TUTOR), $retrieve_extended_data);
                //         if(AMADataHandler::isError($practitioners_Ar) || !is_array($practitioners_Ar)) {
                //           $practitioners_Ar = array();
                //         }
                /*
                 * Ottiene tutti gli utenti che hanno richiesto un servizio sul tester
                 * e che sono in attesa di assegnamento ad un practitioner
                 */
                // $users_Ar = $tester_dh->getRegisteredStudentsWithoutTutor();
                //         if(AMADataHandler::isError($users_Ar) || !is_array($users_Ar)) {
                //           $users_Ar = array();
                //         }
                //         $users[$tester_name] = array_merge($practitioners_Ar, $users_Ar);
                break;

            case AMA_TYPE_TUTOR:
                /*
                 * Ottieni lo switcher del suo tester, gli utenti con i quali è in relazione,
                 * eventualmente gli altri practitioner sul suo tester
                 */
                $tester = $userObj->getDefaultTester();
                $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                $tester_info_Ar = $common_dh->getTesterInfoFromPointer($tester);
                $tester_name = $tester_info_Ar[1];

                if (in_array(AMA_TYPE_STUDENT, $user_types_Ar[$user_type])) {
                    /*
                     * STUDENTS
                     */

                    //        $users[$tester_name] = $tester_dh->getListOfTutoredUsers($userObj->id_user);
                    if (!$userObj->isSuper()) {
                        $students_Ar = $tester_dh->getListOfTutoredUniqueUsers($userObj->id_user);
                    } else {
                        $students_Ar = $tester_dh->getUsersByType([AMA_TYPE_STUDENT], $retrieve_extended_data);
                    }
                    //        $users[$tester_name] = $tester_dh->getUsersByType($user_types_Ar[AMA_TYPE_TUTOR], $retrieve_extended_data);
                    if (AMADataHandler::isError($students_Ar) || !is_array($students_Ar)) {
                        $students_Ar = [];
                    }
                } else {
                    $students_Ar = [];
                }

                if (in_array(AMA_TYPE_TUTOR, $user_types_Ar[$user_type])) {
                    /*
                     * TUTORS
                     */

                    $tutors_Ar =  $tester_dh->getUsersByType([AMA_TYPE_TUTOR], $retrieve_extended_data);
                    if (AMADataHandler::isError($tutors_Ar) || !is_array($tutors_Ar)) {
                        $tutors_Ar = [];
                    }
                } else {
                    $tutors_Ar = [];
                }

                if (in_array(AMA_TYPE_SWITCHER, $user_types_Ar[$user_type])) {
                    /*
                     * SWITCHERS
                     */

                    $switchers_Ar =  $tester_dh->getUsersByType([AMA_TYPE_SWITCHER], $retrieve_extended_data);
                    if (AMADataHandler::isError($switchers_Ar) || !is_array($switchers_Ar)) {
                        $switchers_Ar = [];
                    }
                } else {
                    $switchers_Ar = [];
                }

                $users[$tester_name] = array_merge($tutors_Ar, $students_Ar, $switchers_Ar);


                break;


            case AMA_TYPE_STUDENT:
                /*
                 * Se sono all'interno di un tester, vedo solo i practitioner di questo
                 * tester con i quali sono in relazione
                 * Se sono nella home dell'utente, vedo tutti i practitioner di tutti i
                 * tester con i quali sono in relazione
                 *
                 * Come faccio a capirlo qui? posso Verificare che sess_selected_tester == ADA_DEFAULT_TESTER
                 */
                if (MultiPort::isUserBrowsingThePublicTester()) {
                    // home di user o navigazione nei contenuti pubblici
                    $testers = $userObj->getTesters();
                    foreach ($userObj->getTesters() as $tester) {
                        if (($tester != ADA_PUBLIC_TESTER) or count($testers) == 1) {
                            $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                            $tester_info_Ar = $common_dh->getTesterInfoFromPointer($tester);
                            $tester_name = $tester_info_Ar[1];

                            $tutors_Ar = $tester_dh->getTutorsForStudent($userObj->getId());
                            if (AMADataHandler::isError($tutors_Ar) || !is_array($tutors_Ar)) {
                                $tutors_Ar = [];
                            }
                            $tutors_Ar = array_unique($tutors_Ar, SORT_REGULAR);

                            $switcher_Ar =  $tester_dh->getUsersByType([AMA_TYPE_SWITCHER], $retrieve_extended_data);
                            if (AMADataHandler::isError($switcher_Ar) || !is_array($switcher_Ar)) {
                                $switcher_Ar = [];
                            }

                            /*
                             * OTHER STUDENTS RELATED TO USER
                             */
                            $subscribed_instances = $tester_dh->getIdCourseInstancesForThisStudent($userObj->getId());
                            $students_Ar = $tester_dh->getUniqueStudentsForCourseInstances($subscribed_instances);
                            if (AMADataHandler::isError($students_Ar) || !is_array($students_Ar)) {
                                $students_Ar = [];
                            }

                            /*
                                      foreach ($subscribed_instances as $subscribed_instance) {
                                          $subscribed_instance_id = $subscribed_instance['id_istanza_corso'];
                                          $students_Ar = array_merge($tester_dh->getStudentsForCourseInstance($subscribed_instance_id));
                                      }
                             *
                             */
                            $users[$tester_name] = array_merge($tutors_Ar, $switcher_Ar, $students_Ar);
                        }
                    }
                } else {
                    $tester = $_SESSION['sess_selected_tester'];
                    $tester_info_Ar = $common_dh->getTesterInfoFromPointer($tester);
                    $tester_name = $tester_info_Ar[1];
                    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));


                    /*
                     * GET TUTORS OF TESTER
                     */

                    $tutors_Ar = $tester_dh->getTutorsForStudent($userObj->getId());
                    if (AMADataHandler::isError($tutors_Ar) || !is_array($tutors_Ar)) {
                        $tutors_Ar = [];
                    }

                    $tutors_Ar = array_unique($tutors_Ar, SORT_REGULAR);

                    /*
                     * GET SWITCHER OF TESTER
                     */

                    $switcher_Ar =  $tester_dh->getUsersByType([AMA_TYPE_SWITCHER], $retrieve_extended_data);
                    if (AMADataHandler::isError($switcher_Ar) || !is_array($switcher_Ar)) {
                        $switcher_Ar = [];
                    }

                    /*
                     * OTHER STUDENTS RELATED TO USER
                     */
                    $subscribed_instances = $tester_dh->getIdCourseInstancesForThisStudent($userObj->getId());
                    $students_Ar = $tester_dh->getUniqueStudentsForCourseInstances($subscribed_instances);
                    if (AMADataHandler::isError($students_Ar) || !is_array($students_Ar)) {
                        $students_Ar = [];
                    }

                    $users[$tester_name] = array_merge($tutors_Ar, $switcher_Ar, $students_Ar);
                }
                break;

            case AMA_TYPE_AUTHOR:
            default:
                return false;
        }
        return $users;
    }



    protected static function getAddressBook(ADALoggableUser $userObj, $user_types_Ar = [], $result_Ar = [])
    {
        $user_type = $userObj->getType();

        $address_book = CDOMElement::create('div', 'id:addressbook_div');

        $buttons = CDOMElement::create('div', 'id:buttons_div');

        //    $users_Ar = array();
        //    foreach($result as $tester => $users) {
        //      $users_Ar[$tester][$users['tipo']] = array($users['e_mail'], $users['username']);
        //    }

        $selects = CDOMElement::create('div');

        if (in_array(AMA_TYPE_SWITCHER, $user_types_Ar[$user_type])) {
            $switcher_bt = CDOMElement::create('a', 'id:js_switcher_bt, name:js_switcher_bt');
            $switcher_bt->setAttribute('onclick', "showMeHideOthers('js_switcher_sel');");
            $switcher_bt->addChild(new CText(translateFN('Switcher')));
            $buttons->addChild($switcher_bt);

            $switcher_sel = CDOMElement::create('select', 'id:js_switcher_sel, name:js_switcher_sel, size:10, class: hidden_element');
            $switcher_sel->setAttribute('onchange', 'add_addressee(this);');

            foreach ($result_Ar as $tester_name => $user_data_Ar) {
                $optgroup = CDOMElement::create('optgroup');
                $optgroup->setAttribute('label', $tester_name);
                foreach ($user_data_Ar as $user) {
                    if ($user['tipo'] == AMA_TYPE_SWITCHER) {
                        $option = CDOMElement::create('option', 'value:' . $user['username']);
                        if (isset($user['cognome']) || isset($user['nome'])) {
                            $displayname = $user['cognome'] . ' ' . $user['nome'];
                        } else {
                            $displayname = $user['username'];
                        }
                        $option->addChild(new CText($displayname));
                        $optgroup->addChild($option);
                    }
                }

                $switcher_sel->addChild($optgroup);
            }

            $selects->addChild($switcher_sel);
        }

        if (in_array(AMA_TYPE_TUTOR, $user_types_Ar[$user_type])) {
            $practitioner_bt = CDOMElement::create('a', 'id:js_practitioner_bt, name:js_practitioner_bt');
            $practitioner_bt->setAttribute('onclick', "showMeHideOthers('js_practitioner_sel');");

            $practitioner_bt->addChild(new CText(translateFN('Tutor')));
            $buttons->addChild($practitioner_bt);

            $practitioner_sel = CDOMElement::create('select', 'id:js_practitioner_sel, name: js_practitioner_sel, size:10, class: hidden_element');
            $practitioner_sel->setAttribute('onchange', 'add_addressee(this);');
            foreach ($result_Ar as $tester_name => $user_data_Ar) {
                $optgroup = CDOMElement::create('optgroup');
                $optgroup->setAttribute('label', $tester_name);
                foreach ($user_data_Ar as $user) {
                    if ($user['tipo'] == AMA_TYPE_TUTOR) {
                        $option = CDOMElement::create('option', 'value:' . $user['username']);
                        if (isset($user['cognome']) || isset($user['nome'])) {
                            $displayname = $user['cognome'] . ' ' . $user['nome'];
                        } else {
                            $displayname = $user['username'];
                        }
                        $option->addChild(new CText($displayname));
                        $optgroup->addChild($option);
                    }
                }

                $practitioner_sel->addChild($optgroup);
            }

            $selects->addChild($practitioner_sel);
        }

        if (in_array(AMA_TYPE_STUDENT, $user_types_Ar[$user_type])) {
            $user_bt = CDOMElement::create('a', 'id:js_user_bt, name:js_user_bt');
            $user_bt->setAttribute('onclick', "showMeHideOthers('js_user_sel');");
            $user_bt->addChild(new CText(translateFN('Students')));
            $buttons->addChild($user_bt);

            $user_sel = CDOMElement::create('select', 'id:js_user_sel, name: js_user_sel, size:10, class: hidden_element');
            $user_sel->setAttribute('onchange', 'add_addressee(this);');
            foreach ($result_Ar as $tester => $user_data_Ar) {
                $optgroup = CDOMElement::create('optgroup');
                $optgroup->setAttribute('label', $tester_name);
                foreach ($user_data_Ar as $user) {
                    /**
                     * @author giorgio 28/apr/2015
                     *
                     * tutors are students for an ADA_SERVICE_TUTORCOMMUNITY type of course,
                     * so add them to the address book if they're returned in the $result_Ar
                     */
                    if (
                        $user['tipo'] == AMA_TYPE_STUDENT ||
                        ($user['tipo'] == AMA_TYPE_TUTOR && $userObj->getType() == AMA_TYPE_TUTOR && !$userObj->isSuper() && isset($user['id_utente']) && $user['id_utente'] != $userObj->getId())
                    ) {
                        $option = CDOMElement::create('option', 'value:' . $user['username']);
                        if (isset($user['cognome']) || isset($user['nome'])) {
                            $displayname = $user['cognome'] . ' ' . $user['nome'];
                        } else {
                            $displayname = $user['username'];
                        }
                        $option->addChild(new CText($displayname));
                        $optgroup->addChild($option);
                    }
                }

                $user_sel->addChild($optgroup);
            }

            $selects->addChild($user_sel);
        }


        $address_book->addChild($buttons);
        $address_book->addChild($selects);
        return $address_book;
    }
}

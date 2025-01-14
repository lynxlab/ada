<?php

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Test\RootTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TestTest extends RootTest
{
    public const NODE_TYPE = ADA_TYPE_TEST;
    public const CHILD_CLASS = TopicTest::class;

    protected $barrier;

    /**
     * used to configure object with database's data options
     *
     * @access protected
     *
     */
    protected function configureProperties()
    {
        $this->shuffle_answers = true;

        //first character
        if ($this->tipo[0] != self::NODE_TYPE) {
            return false;
        }

        //second character ignored because not applicable
        //third character delegated to parent class
        //fourth character delegated to parent class

        //fifth character
        switch ($this->tipo[4]) {
            default:
            case ADA_NO_TEST_BARRIER:
                $this->barrier = false;
                break;
            case ADA_YES_TEST_BARRIER:
                $this->barrier = true;
                break;
        }

        //sixth character delegated to parent class

        return parent::configureProperties();
    }

    /**
     * save test's data (e.g. points earned, end time, level gained, etc.)
     * send a message to tutor and switcher when user reaches max course's level
     * set course subscription to complete
     *
     * @access protected
     *
     * @return returns true if test data is saved, false otherwise
     */
    protected function saveTest()
    {
        $dh = $GLOBALS['dh'];

        $r = parent::saveTest();

        $sess_id_course = $_SESSION['sess_id_course'];
        $sess_id_course_instance = $_SESSION['sess_id_course_instance'];
        if ($this->id_istanza == 0 || $this->id_istanza == null) {
            $this->id_istanza = $sess_id_course_instance;
        }

        if ($r) {
            //check for barrier and conseguent level up
            if ($this->barrier) {
                $level_gained = null;
                if (!is_null($r['min_barrier_points']) && $r['points'] >= $r['min_barrier_points']) {
                    $level = $dh->getStudentLevel($_SESSION['sess_id_user'], $this->id_istanza);
                    if ($level < $this->livello) {
                        $level = $this->livello;
                    }
                    if ($dh->setStudentLevel($this->id_istanza, [$_SESSION['sess_id_user']], $level)) {
                        $level_gained = $level;
                        $res = $dh->testSaveTest($r['id_history_test'], $r['tempo_scaduto'], $r['points'], $r['repeatable'], $r['min_barrier_points'], $level_gained);
                        if (is_object($res) && ($res::class == AMAError::class)) {
                            $this->onSaveError = true;
                            $this->rollBack();
                            return false;
                        }

                        //Send message to switcher and tutor when the user reaches max course's level
                        //Set course subscription to complete
                        $userObj = DBRead::readUser($_SESSION['sess_id_user']);
                        $max_level = $dh->getCourseMaxLevel($sess_id_course);
                        if ($level >= $max_level) {
                            // se è l'ultimo esercizio (ovvero se il livello dello studente è il massimo possibile)
                            // e l'esercizio è di tipo sbarramento
                            // 1. cambia lo stato dell'iscrizione dello studente all'istanza corso
                            /**
                             * @author giorgio disabled on 12/nov/2014 completion is now
                             * handled using modules/service-complete module
                             */
                            // $dh->courseInstanceStudentSubscribe($_SESSION['sess_id_course_instance'], $_SESSION['sess_id_user'], ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED, $level);
                            /*
                            // 2. genera il messaggio da inviare allo switcher
                            $tester = $userObj->getDefaultTester();
                            $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                            $tester_info_Ar = $dh->getTesterInfoFromPointer($tester); // common?
                            $tester_name = $tester_info_Ar[1];
                            $switchers_Ar = $tester_dh->getUsersByType(array(AMA_TYPE_SWITCHER));
                            if (AMADataHandler::isError($switchers_Ar) || !is_array($switchers_Ar)) {
                                // ??
                            }
                            else {
                                $switcher_id = $switchers_Ar[0];
                                //
                                // FIXME: only the first switcher per provider !
                                if ($switcher_id) {
                                    $switcher = $dh->get_switcher($switcher_id);
                                    if (!AMADataHandler::isError($switcher)) {
                                        // prepare message to send
                                        $message_ha['destinatari'] = $switcher['username'];
                                        $message_ha['titolo'] = translateFN("Completamento corso") . "<br>";

                                        //                      $message_ha['testo'] = $correttore->getMessageForTutor($user_name, $exercise);
                                        // FIXME should be a function of ExerciseCorrectionFactory??
                                        $message_ha['testo'] = translateFN("Il corsista") . " $user_name " . translateFN("ha terminato il corso con id") . " " . $sess_id_course . "/" . $sess_id_course_instance;
                                        $message_ha['data_ora'] = "now";
                                        $message_ha['tipo'] = ADA_MSG_SIMPLE;
                                        $message_ha['priorita'] = 1;
                                        $message_ha['mittente'] = $user_name;
                                        $mh = new MessageHandler();
                                        $mh->sendMessage($message_ha);
                                    }
                                }
                            }

                            // genera il messaggio da inviare al tutor
                            // codice precedente
                            $tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);
                            if (AMADataHandler::isError($tutor_id)) {
                                //?
                            }
                            // only one tutor per class
                            if ($tutor_id) {
                                $tutor = $dh->getTutor($tutor_id);
                                if (!AMADataHandler::isError($tutor)) {
                                    // prepare message to send
                                    $message_ha['destinatari'] = $tutor['username'];
                                    $message_ha['titolo'] = translateFN("Esercizio svolto da ") . $user_name . "<br>";
                                    $message_ha['testo'] = $correttore->getMessageForTutor($user_name, $exercise);
                                    $message_ha['data_ora'] = "now";
                                    $message_ha['tipo'] = ADA_MSG_SIMPLE;
                                    if ($course_completed) {
                                        $message_ha['tipo'] = ADA_MSG_MAIL;
                                        $message_ha['testo'].= translateFN("Il corsista") . " " . translateFN("ha terminato il corso con id") . " " . $sess_id_course . "/" . $sess_id_course_instance;
                                    }
                                    $message_ha['priorita'] = 1;
                                    $message_ha['mittente'] = $user_name;
                                    $mh = new MessageHandler();
                                    $mh->sendMessage($message_ha);
                                }
                            }
                            */
                        } // max level attained
                    }
                }
            }
        }

        // call helper function to check service completeness using modules/service-complete
        $userObj = DBRead::readUser($_SESSION['sess_id_user']);
        BrowsingHelper::checkServiceComplete($userObj, $sess_id_course, $sess_id_course_instance);

        return true;
    }

    /**
     * Render the object structure when the test cannot be repeated
     *
     * @access protected
     *
     * @param $return_html choose the return type
     *
     * @return an object of CDOMElement
     */
    protected function renderNoRepeat($return_html = true)
    {
        $html = CDOMElement::create('div');
        $html->addChild(new CText(translateFN('Non puoi ripetere questo test')));

        if ($return_html) {
            return $html->getHtml();
        } else {
            return $html;
        }
    }

    /**
     * Render the object structure when the test/survet cannot be accessed by student
     *
     * @access protected
     *
     * @param $return_html choose the return type
     *
     * @return an object of CDOMElement
     */
    protected function renderNoLevel($return_html = true)
    {
        $html = CDOMElement::create('div');
        $html->addChild(new CText(translateFN('Non puoi accedere a questo test')));

        if ($return_html) {
            return $html->getHtml();
        } else {
            return $html;
        }
    }
}

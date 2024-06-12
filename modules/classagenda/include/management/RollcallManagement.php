<?php

/**
 * Calendars Management Class
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classagenda
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classagenda;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for managing roll calls
 *
 * @author giorgio
 */
class RollcallManagement extends AbstractClassAgendaManagement
{
    public $id_course_instance = null;
    public $eventData = null;

    private $userObj = null;

    public function __construct($id_course_instance = null)
    {
        parent::__construct(['id_course_instance' => $id_course_instance]);

        $this->userObj = $_SESSION['sess_userObj'];

        if ($this->userObj instanceof ADALoggableUser) {
            $this->eventData = $this->findClosestCourseInstance();

            if (!is_null($this->eventData) && array_key_exists('id_istanza_corso', $this->eventData)) {
                $this->id_course_instance = $this->eventData['id_istanza_corso'];
            }
        }
    }

    /**
     * build, manage and display the module's pages
     *
     * @return array
     *
     * @access public
     */
    public function run($action = null)
    {
        /* @var $html   string holds html code to be retuned */
        $htmlObj = null;
        /* @var $path   string  path var to render in the help message */
        $help = '';
        /* @var $status string status var to render in the breadcrumbs */
        $title = translateFN('Foglio presenze');

        switch ($action) {
            case MODULES_CLASSAGENDA_DO_ROLLCALL:
            case MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY:
                $htmlObj = CDOMElement::create('div', 'id:rollcallContainer');
                if (
                    empty($this->eventData) ||
                    !isset($this->id_course_instance) || is_null($this->id_course_instance) ||
                    strlen($this->id_course_instance) <= 0 || !is_numeric($this->id_course_instance) ||
                    !$this->isTutorOfInstance()
                ) {
                    $htmlObj->addChild(new CText(translateFN('Nessun evento da mostrare trovato')));
                } else {
                    /**
                     * get list of students subscribed to passed instance
                     */
                    $studentsList = $this->getStudentsList($action);
                    if (!is_null($studentsList)) {
                        if ($action == MODULES_CLASSAGENDA_DO_ROLLCALL) {
                            /**
                             * add data and action field to the student list
                             */
                            $studentsList = $this->addDetailsAndActionToStudentList($studentsList);
                            /**
                             * setup arrays and variables to build the table
                             */
                            $header = [
                                'id',
                                translateFN('Nome'),
                                translateFN('Cognome'),
                                translateFN('E-Mail'),
                                translateFN('Dettagli'),
                                translateFN('Azioni'),
                            ];
                            $caption = translateFN('Registro Entrate-Uscite del');
                            [$startDate, $starTime] = explode(' ', $this->eventData['start'] . ' ');
                            [$endDate, $endTime] = explode(' ', $this->eventData['end'] . ' ');
                            $caption .= ' ' . $startDate . ' ' . translateFN('ore') . ' ' . $starTime;
                            $caption .= ' ' . translateFN('al') . ' ' . $endDate . ' ' . translateFN('ore') . ' ' . $endTime;
                            $tableID = 'rollcallTable';
                            /**
                             * set the help message
                             */
                            $help = translateFN('Gestione foglio presenze');
                        } elseif ($action == MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY) {
                            /**
                             * add presence details to the student list
                             */
                            $studentsList = $this->addRollCallHistoryToStudentList($studentsList);
                            /**
                             * setup arrays and variables to build the table
                             */

                            /**
                             * 1. get the timestamps of the first student
                             * and use them to build the header of the table
                             */
                            $timestamps = array_keys(array_slice($studentsList[0], 3, null, true));
                            for ($i = 0; $i < count($timestamps); $i++) {
                                $timestamps[$i] = Utilities::ts2dFN($timestamps[$i]);
                            }
                            /**
                             * 2. build the header with 'Nome e Cognome' in the
                             * first position and then all the timestamps converted
                             * into human readable dates
                             */
                            $header = array_merge(['id', translateFN('Nome'), translateFN('Cognome')], $timestamps);

                            $caption = translateFN('Riepilogo presenze studenti');
                            $tableID = 'rollcallHistoryTable';
                            /**
                             * set the help message
                             */
                            $help = translateFN('Riepilogo presenze');
                        }
                        /**
                         * get passed instance name and add it to help message
                         */
                        $instancename = $this->getInstanceName();
                        if (!is_null($instancename)) {
                            $help .= ' ' . translateFN('della classe') . ' ' . $instancename;
                        }
                        /**
                         * build the html table
                         */
                        $tableObj = BaseHtmlLib::tableElement('id:' . $tableID, $header, $studentsList, null, $caption);
                        $tableObj->setAttribute('class', $tableObj->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
                        if ($action == MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY) {
                            $tableObj->setAttribute('class', 'display nowrap ' . $tableObj->getAttribute('class'));
                        }
                        $htmlObj->addChild($tableObj);
                    } else {
                        $htmlObj->addChild(new CText(translateFN('Nessuno studente iscritto')));
                    }
                }
                break;
            default:
                /**
                 * return an empty page as default action
                 */
                break;
        }

        return [
            'htmlObj'   => $htmlObj,
            'help'      => $help,
            'title'     => $title,
        ];
    }

    /**
     * builds export rollcall history array data
     * return an empty array or an array with 'header' and 'studentsList' keys
     *
     * @return array
     */
    public function exportRollCallHistory()
    {

        if (
            !isset($this->id_course_instance) || is_null($this->id_course_instance) ||
            strlen($this->id_course_instance) <= 0 || !is_numeric($this->id_course_instance) ||
            !$this->isTutorOfInstance()
        ) {
            return [];
        } else {
            /**
             * get list of students subscribed to passed instance
             */
            $studentsList = $this->getStudentsList(MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY);
            if (!is_null($studentsList)) {
                /**
                 * add presence details to the student list
                 */
                $studentsList = $this->addRollCallHistoryToStudentList($studentsList);
                /**
                 * setup arrays and variables to build the table
                 */

                /**
                 * 1. get the timestamps of the first student
                 * and use them to build the header of the table
                 */
                $timestamps = array_keys(array_slice($studentsList[0], 3, null, true));
                for ($i = 0; $i < count($timestamps); $i++) {
                    $timestamps[$i] = Utilities::ts2dFN($timestamps[$i]);
                }
                /**
                 * 2. build the header with 'Nome e Cognome' in the
                 * first position and then all the timestamps converted
                 * into human readable dates
                 */
                $header = array_merge(['id', translateFN('Nome'), translateFN('Cognome')], $timestamps);

                foreach ($studentsList as $skey => $astud) {
                    foreach ($astud as $key => $val) {
                        $studentsList[$skey][$key] = str_replace('<br/>', "\n", $val);
                    }
                }

                /**
                 * return data
                 */
                return [
                    'header' => $header,
                    'studentsList' => $studentsList,
                ];
            } else {
                return [];
            }
        }
    }

    /**
     * check if $this->_userObj is a tutor for $this->id_course instance
     *
     * @return boolean true on success
     *
     * @access private
     */
    private function isTutorOfInstance()
    {
        if (is_null($this->userObj) || $this->userObj->getType() != AMA_TYPE_TUTOR) {
            return false;
        }

        $dh = $GLOBALS['dh'];
        $res = $dh->courseTutorInstanceGet($this->userObj->getId());
        if (!AMADB::isError($res) && is_array($res) && $res !== false) {
            foreach ($res as $tutored_instance) {
                if ($this->id_course_instance == $tutored_instance[0]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * gets the instance name of $this_>id_course_instance
     *
     * @return string|NULL
     *
     * @access private
     */
    private function getInstanceName()
    {
        $dh = $GLOBALS['dh'];
        $courseInstance = $dh->courseInstanceGet($this->id_course_instance);
        if (!AMADB::isError($courseInstance) && isset($courseInstance['title']) && strlen($courseInstance['title']) > 0) {
            return $courseInstance['title'];
        } else {
            return null;
        }
    }

    /**
     * adds detail and action buttons to student list array
     *
     * @param array $studentsList
     *
     * @return array $studentsList with added fields 'details' and 'actions'
     *
     * @access private
     */
    private function addDetailsAndActionToStudentList($studentsList)
    {
        if (is_array($studentsList) && count($studentsList) > 0 && !empty($this->eventData)) {
            $dh = $GLOBALS['dh'];

            foreach ($studentsList as $i => $student) {
                $userDetailsSPAN = CDOMElement::create('span', 'id:' . $student[0] . '_details');
                $isEnterButtonVisibile = true;
                $detailsStr = '';

                /**
                 * load and display details data column
                 */
                if (array_key_exists('module_classagenda_calendars_id', $this->eventData)) {
                    $detailsAr = $dh->getRollCallDetails($student[0], $this->eventData['module_classagenda_calendars_id']);

                    if (!AMADB::isError($detailsAr) && is_array($detailsAr) && count($detailsAr) > 0) {
                        foreach ($detailsAr as $j => $enterexittime) {
                            if (strlen($enterexittime['entertime']) > 0) {
                                if ($j > 0) {
                                    $detailsStr .= '<br/>';
                                }
                                $detailsStr .= translateFN('Entrata alle: ');
                                $detailsStr .= Utilities::ts2tmFN($enterexittime['entertime']);
                                $isEnterButtonVisibile = false;
                            }
                            if (strlen($enterexittime['exittime']) > 0) {
                                $detailsStr .= '<br/>';
                                $detailsStr .= translateFN('Uscita alle: ');
                                $detailsStr .= Utilities::ts2tmFN($enterexittime['exittime']);
                                $isEnterButtonVisibile = true;
                            }
                        }
                    }
                }

                if (strlen($detailsStr) > 0) {
                    $userDetailsSPAN->addChild(new CText($detailsStr . '<br/>'));
                }

                $studentsList[$i]['details'] = $userDetailsSPAN->getHtml();
                $studentsList[$i]['actions'] = $this->buildEnterExitButtons($student[0], $isEnterButtonVisibile);
            }
        }
        return $studentsList;
    }

    /**
     * builds the enter and exit buttons for the currrent table row
     *
     * @param number $id_student the student for whom the buttons are genertated
     * @param boolean $isEnterButtonVisibile true if enter button must be made visible
     *
     * @return CDiv
     *
     * @access private
     */
    private function buildEnterExitButtons($id_student, $isEnterButtonVisibile = true)
    {

        [$startDate, $startTime] = explode(' ', $this->eventData['start'] . ' ');
        // compare dates without times to determine if event has started
        $started = Utilities::dt2tsFN(date('d/m/Y')) >= Utilities::dt2tsFN($startDate);

        $buttonsDIV = CDOMElement::create('div', 'class:buttonsContainer');

        if ($started) {
            $enterButton = CDOMElement::create('button', 'class:enterbutton');
            if (!$isEnterButtonVisibile) {
                $enterButton->setAttribute('style', 'display:none');
            }
            if (array_key_exists('module_classagenda_calendars_id', $this->eventData)) {
                $enterButton->setAttribute('onclick', 'javascript:toggleStudentEnterExit($j(this), ' . $id_student . ',' . $this->eventData['module_classagenda_calendars_id'] . ',true);');
            }
            $enterButton->addChild(new CText(translateFN('Entra')));

            $exitButton = CDOMElement::create('button', 'class:exitbutton');
            if ($isEnterButtonVisibile) {
                $exitButton->setAttribute('style', 'display:none');
            }
            if (array_key_exists('module_classagenda_calendars_id', $this->eventData)) {
                $exitButton->setAttribute('onclick', 'javascript:toggleStudentEnterExit($j(this), ' . $id_student . ',' . $this->eventData['module_classagenda_calendars_id'] . ',false);');
            }
            $exitButton->addChild(new CText(translateFN('Esce')));

            $buttonsDIV->addChild($enterButton);
            $buttonsDIV->addChild($exitButton);
        } else {
            $msgSPAN = CDOMElement::create('span', 'class:notStartedMsg');
            $msgSPAN->addChild(new CText(translateFN('Potrai registrare le entrate e le uscite degli studenti a partire dal ') . $startDate));

            $buttonsDIV->addChild($msgSPAN);
        }

        return $buttonsDIV->getHtml();
    }

    /**
     * adds the roll call history to each element of the students list
     *
     * @param array $studentsList
     *
     * @return array the passed array, with the added roll call history
     *
     * @access private
     */
    private function addRollCallHistoryToStudentList($studentsList)
    {

        $dh = $GLOBALS['dh'];
        $allTimestamps = [];

        foreach ($studentsList as $i => $student) {
            $result = $dh->getRollCallDetailsForInstance($student['id'], $this->id_course_instance);

            if (!AMADB::isError($result) && is_array($result) && count($result) > 0) {
                foreach ($result as $aRow) {
                    if (strlen($aRow['entertime']) > 0) {
                        // get entertime date only as a timestamp for the array key
                        $arrKey = $dh::dateToTs(Utilities::ts2dFN($aRow['entertime']));
                        if (!in_array($arrKey, $allTimestamps)) {
                            $allTimestamps[] = $arrKey;
                        }

                        if (isset($studentsList[$i][$arrKey]) && strlen($studentsList[$i][$arrKey]) > 0) {
                            $studentsList[$i][$arrKey] .= '<br/>';
                        } else {
                            $studentsList[$i][$arrKey] = '';
                        }

                        $studentsList[$i][$arrKey] .= translateFN('Entrata alle: ');
                        $studentsList[$i][$arrKey] .= Utilities::ts2tmFN($aRow['entertime']);

                        if (strlen($aRow['exittime']) > 0) {
                            $studentsList[$i][$arrKey] .= '<br/>';
                            $studentsList[$i][$arrKey] .= translateFN('Uscita alle: ');
                            $studentsList[$i][$arrKey] .= Utilities::ts2tmFN($aRow['exittime']);
                        }
                    }
                }
            }
        }

        /**
         * every array MUST have all the generated keys (timestamps)
         * for the HTML table to be properly rendered
         */
        sort($allTimestamps, SORT_NUMERIC);
        $retArray = [];

        foreach ($studentsList as $i => $student) {
            $retArray[$i]['id'] = $student['id'];
            $retArray[$i]['name'] = ucwords(strtolower($student['name']));
            $retArray[$i]['lastname'] = ucwords(strtolower($student['lastname']));
            foreach ($allTimestamps as $timestamp) {
                $retArray[$i][$timestamp] = (!array_key_exists($timestamp, $student)) ? '' : $studentsList[$i][$timestamp];
            }
        }

        return $retArray;
    }

    /**
     * gets the student list to be displayed either when doing a roll call
     * or displaying the roll call history details
     *
     * @param number $action
     *
     * @return Ambigous <NULL, array>
     *
     * @access private
     */
    private function getStudentsList($action)
    {
        $dh = $GLOBALS['dh'];
        $student_listHa = [];

        $stud_status = ADA_STATUS_SUBSCRIBED; //only subscribed students
        $students =  $dh->courseInstanceStudentsPresubscribeGetList($this->id_course_instance, $stud_status);
        if (!AMADB::isError($students) && is_array($students) && count($students) > 0) {
            foreach ($students as $one_student) {
                $id_stud = $one_student['id_utente_studente'];
                if ($dh->getUserType($id_stud) == AMA_TYPE_STUDENT) {
                    $studn = $dh->getStudent($id_stud);
                    if ($action == MODULES_CLASSAGENDA_DO_ROLLCALL) {
                        $row = [
                            $one_student['id_utente_studente'],
                            $studn['nome'],
                            $studn['cognome'],
                            $studn['email'],
                        ];
                    } elseif ($action == MODULES_CLASSAGENDA_DO_ROLLCALLHISTORY) {
                        $row = [
                            'id' => $one_student['id_utente_studente'],
                            'name' => $studn['nome'],
                            'lastname' => $studn['cognome'],
                        ];
                    }
                    array_push($student_listHa, $row);
                }
            }
        }
        return (count($student_listHa) > 0) ? $student_listHa : null;
    }

    private function findClosestCourseInstance()
    {
        $dh = $GLOBALS['dh'];
        $result = $dh->findClosestClassroomEvent($this->userObj->getId(), $this->id_course_instance);

        if ($result === false || AMADB::isError($result)) {
            return null;
        } else {
            $result['start'] = isset($result['start']) ? Utilities::ts2dFN($result['start']) . ' ' . Utilities::ts2tmFN($result['start']) : '';
            $result['end'] = isset($result['end']) ? Utilities::ts2dFN($result['end']) . ' ' . Utilities::ts2tmFN($result['end']) : '';
            return $result;
        }
    }
}

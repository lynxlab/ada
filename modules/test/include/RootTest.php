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
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\Test\NodeTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class RootTest extends NodeTest
{
    protected $rating_answer = false;
    protected $feedback = false;
    protected $rating = false;
    protected $sequenced = false;
    protected $repeatable = false;
    protected $returnLink = false;
    protected $noRepeat = false;
    protected $noLevel = false;
    protected $minLevel = 0;

    protected $session;
    protected $id_history_test;
    protected $randomQuestion;

    public const EOT = 'endOfTest';
    //topic variables used to separate test/survey in multiple pages
    private $currentTopic = 0;
    protected $onSaveError = false;
    protected $shuffle_answers = false;

    /**
     * this function contains (and execute) all object logic
     * (e.g.: manipulating session / database)
     *
     * @access public
     *
     */
    public function run($id_history_test = null)
    {
        if (!is_null($id_history_test) && intval($id_history_test) > 0) {
            $this->feedback = true;
            $this->currentTopic = self::EOT;
            $this->id_history_test = $id_history_test;
        } elseif ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT) {
            if (!$this->checkStudentLevel()) {
                $this->noLevel = true;
            } elseif (!$this->checkRepeatable()) {
                $this->noRepeat = true;
            } else {
                $this->setSession();
                $this->setCurrentTopic();
                $this->pickRandomQuestion();

                if ($this->endOfTest()) {
                    $this->saveAnswers();
                    $this->saveTest();
                } else {
                    $this->recordAttempt();
                    $this->setTimeLimit();
                    $this->saveAnswers();
                }
            }
        } else {
            $this->setCurrentTopicFromGET();
        }
    }

    /**
     * return true if the user has reach end of test
     *
     * @access protected
     *
     * @return boolean
     */
    protected function endOfTest()
    {
        return ($this->currentTopic === self::EOT);
    }

    /**
     * retrieves and saves user answer in database.
     * if something goes wrong during the process, all answers previously saved are deleted.
     *
     * @access protected
     */
    protected function saveAnswers()
    {
        $dh = $GLOBALS['dh'];

        $this->onSaveError = false;

        $this->normalizeAnswers();

        if (!empty($_POST[self::POST_TOPIC_VAR])) {
            $result = true;
            $ids = [];
            $i = 0;
            foreach ($_POST[self::POST_TOPIC_VAR] as $topic_id => $question_array) {
                foreach ($question_array as $question_id => $answer_data) {
                    if (isset(self::$nodesArray[$question_id])) {
                        $question = self::$nodesArray[$question_id];
                        $correction = $question->exerciseCorrection($answer_data);
                        if (is_array($correction)) {
                            $points = $correction['points'];
                            $attachment = $correction[self::POST_ATTACHMENT_VAR];
                        } else {
                            $points = $correction;
                            $attachment = null;
                        }
                        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
                            $realAnswer = $answer_data;
                            $thisArr = $this->toArray();
                            $thisArr = reset($thisArr);
                            $event = ADAEventDispatcher::buildEventAndDispatch(
                                [
                                    'eventClass' => ModuleTestEvent::class,
                                    'eventName' => ModuleTestEvent::PRESAVEANSWER,
                                ],
                                static::class,
                                [
                                    'answer_data' => $answer_data,
                                ]
                            );
                            $args = $event->getArguments();
                            if (array_key_exists('answer_data', $args)) {
                                $answer_data = $args['answer_data'];
                            }
                        }
                        $answer_data = $question->serializeAnswers($answer_data);
                        $obj = $dh->testSaveAnswer($this->id_history_test, $_SESSION['sess_id_user'], $topic_id, $question_id, $_SESSION['sess_id_course'], $_SESSION['sess_id_course_instance'], $answer_data, $points, $attachment);
                        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
                            /**
                             * $thisArr and $realAnswer are set before the PRESAVEANSWER is dispatched.
                             */
                            if (is_array($realAnswer[self::POST_ANSWER_VAR])) {
                                $realAnswer = array_map(
                                    fn ($answer) => $thisArr['topics'][$topic_id]['questions'][$question_id]['answers'][$answer],
                                    $realAnswer[self::POST_ANSWER_VAR]
                                );
                            } elseif (array_key_exists(self::POST_ANSWER_VAR, $realAnswer)) {
                                $realAnswer = [
                                    array_merge(
                                        $thisArr['topics'][$topic_id]['questions'][$question_id]['answers'][$realAnswer[self::POST_ANSWER_VAR]] ?? [],
                                        [self::POST_EXTRA_VAR => $realAnswer[self::POST_EXTRA_VAR] ?? null],
                                    ),
                                ];
                            }
                            $realAnswer[self::POST_ANSWER_VAR][0]['points'] = $points;
                            ADAEventDispatcher::buildEventAndDispatch(
                                [
                                    'eventClass' => ModuleTestEvent::class,
                                    'eventName' => ModuleTestEvent::POSTSAVEANSWER,
                                ],
                                static::class,
                                [
                                    'idHistoryTest' => $this->id_history_test,
                                    'idUser' => $_SESSION['sess_id_user'],
                                    'idCourse' => $_SESSION['sess_id_course'],
                                    'idCourseInstance' => $_SESSION['sess_id_course_instance'],
                                    'idTopic' => $topic_id,
                                    'survey' => [
                                        'id' => $thisArr['id'],
                                        'nome' => $thisArr['nome'],
                                        'titolo' => $thisArr['titolo'],
                                    ],
                                    self::GET_TOPIC_VAR => [
                                        'id' => $topic_id,
                                        'nome' => $thisArr['topics'][$topic_id]['nome'],
                                        'titolo' => $thisArr['topics'][$topic_id]['titolo'],
                                    ],
                                    self::POST_TOPIC_VAR => [
                                        'id' => $question_id,
                                        'nome' => $thisArr['topics'][$topic_id]['questions'][$question_id]['nome'],
                                        'titolo' => $thisArr['topics'][$topic_id]['questions'][$question_id]['titolo'],
                                        'consegna' => $thisArr['topics'][$topic_id]['questions'][$question_id]['consegna'],
                                        'answers' => $thisArr['topics'][$topic_id]['questions'][$question_id]['answers'],
                                    ],
                                    self::POST_ANSWER_VAR => $realAnswer ?? [],
                                    'points' => $points,
                                    'attachment' => $attachment,
                                ]
                            );
                        }
                        if (is_object($obj) && ($obj::class == AMAError::class || is_subclass_of($obj, 'PEAR_Error'))) {
                            $result = false;
                            break 2;
                        }
                        $ids[$i] = $obj;
                        $i++;
                    }
                }
            }

            //if something went wrong, let's delete previous answers
            if (!$result) {
                $this->onSaveError = true;
                if (!empty($ids)) {
                    $dh->testRemoveTestAnswerNode($ids);
                }
                $this->rollBack();
            } else {
                Utilities::redirect($_SERVER['REQUEST_URI']);
            }
        }
    }

    /**
     * to be called when an error occurs while saving answers
     *
     * @access protected
     */
    protected function rollBack()
    {
        if ($this->currentTopic === self::EOT) {
            $this->currentTopic = count($this->children) - 1;
        } else {
            $this->currentTopic--;
            if ($this->currentTopic < 0) {
                $this->currentTopic = 0;
            }
        }
    }

    /**
     * save test's data (e.g. points earned, end time, level gained, etc.)
     *
     * @access protected
     *
     * @return returns true if test data is saved, false otherwise
     */
    protected function saveTest()
    {
        $dh = $GLOBALS['dh'];

        $points = $dh->testRetrieveTestPoints($this->id_history_test);
        $repeatable = ($this->repeatable) ? 1 : 0;

        $min_barrier_points = $this->correttezza;
        $level_gained = null;

        //let's check if time expired
        if (isset($this->session['timestamps'])) {
            $tempo_scaduto = ($this->session['timestamps']['start'] >= $this->session['timestamps']['start']) ? 1 : 0;
        } else {
            $tempo_scaduto = 0;
        }
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => ModuleTestEvent::class,
                    'eventName' => ModuleTestEvent::PRESAVETEST,
                    'eventPrefix' => realpath($_SERVER['SCRIPT_FILENAME']),
                ],
                static::class,
                array_merge(
                    ['id_history_test' => $this->id_history_test],
                    compact('tempo_scaduto', 'points', 'repeatable', 'min_barrier_points', 'level_gained')
                )
            );
        }
        $res = $dh->testSaveTest($this->id_history_test, $tempo_scaduto, $points, $repeatable, $min_barrier_points, $level_gained);

        //checking if we got errors
        $retval = [];
        if (is_object($res) && ($res::class == AMAError::class || is_subclass_of($res, 'PEAR_Error'))) {
            $this->onSaveError = true;
            $this->rollBack();
            $retval = false;
        } else {
            $id_history_test = $this->id_history_test; //copying this variable that will be destruct with unset($this->_session)
            unset($_SESSION[$this->getSessionKey()]);
            unset($this->session);
            $retval = [
                'id_history_test' => $id_history_test,
                'tempo_scaduto' => $tempo_scaduto,
                'points' => $points,
                'repeatable' => $repeatable,
                'min_barrier_points' => $min_barrier_points,
                'level_gained' => $level_gained,
            ];
        }
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => ModuleTestEvent::class,
                    'eventName' => ModuleTestEvent::POSTSAVETEST,
                    'eventPrefix' => realpath($_SERVER['SCRIPT_FILENAME']),
                ],
                static::class,
                $retval
            );
            $args = $event->getArguments();
            if (array_key_exists('retval', $args)) {
                $retval = $args['retval'];
            }
        }
        return $retval;
    }

    /**
     * get currentTopic value from $_GET parameter and set it to _currentTopic
     *
     * @access protected
     *
     */
    protected function setCurrentTopicFromGET()
    {
        if (isset($_GET['topic']) && intval($_GET['topic']) > 0) {
            $topic = intval($_GET['topic']);
        } else {
            $topic = 0;
        }
        $this->currentTopic = $topic;
    }

    /**
     * set _currentTopic attribute to match the right topic to display (valid only on sequenced test)
     *
     * @access protected
     *
     */
    protected function setCurrentTopic()
    {
        if (isset($_POST[self::POST_SUBMIT_VAR])) {
            $this->currentTopic++;
        } elseif (empty($this->currentTopic)) {
            $this->currentTopic = 0;
        }

        if ((!$this->sequenced && $this->currentTopic > 0) || $this->currentTopic >= count($this->children)) {
            $this->currentTopic = self::EOT;
        }
    }

    /**
     * calculate and set references to session variables.
     * binds _session to relative macro variable  in session.
     * binds id_history_test to relative variable in session.
     *
     * @access protected
     *
     */
    protected function setSession()
    {
        $key = $this->getSessionKey();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        $this->session = &$_SESSION[$key];
        $this->currentTopic = &$_SESSION[$key]['_currentTopic'];
        $this->id_history_test = &$_SESSION[$key]['id_history_test'];
        $this->randomQuestion = &$_SESSION[$key]['randomQuestion'];
    }

    /**
     * register any test attempt made and initialize a new history_test record
     *
     * @access protected
     */
    protected function recordAttempt()
    {
        $dh = $GLOBALS['dh'];

        if (isset($_GET['unload']) && $_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT) {
            if (!empty($this->id_history_test) && intval($this->id_history_test) > 0) {
                $dh->testUpdateEndTestDate($this->id_history_test);
            }
        } elseif (intval($this->id_history_test) > 0) {
            $res = $dh->testGetHistoryTest($this->id_history_test);
            if ($dh->isError($res) || $res[0]['id_history_test'] != $this->id_history_test) {
                $this->id_history_test = null;
                $this->recordAttempt();
            }
        } else {
            $questions = serialize($this->randomQuestion);
            $id = $dh->testRecordAttempt($this->id_nodo, $_SESSION['sess_id_course_instance'], $_SESSION['sess_id_course'], $_SESSION['sess_id_user'], $questions);
            $this->id_history_test = $id;
            $dh->testCountVisit($this->id_nodo);
        }
    }

    /**
     * set time limit to execute test when its necessary based on data_inizio attribute
     *
     * @access protected
     *
     * @return true when time limit needed to be setted, false otherwise
     */
    protected function setTimeLimit()
    {
        if (!$this->endOfTest()) {
            $time = time();

            $durata = 0;
            $global = true;
            foreach ($this->children as $k => $v) {
                $durata += $v->durata;
            }
            if ($this->sequenced && $this->currentTopic !== self::EOT) {
                $global = false;
                $durata = $this->children[$this->currentTopic]->durata;
                //if no duration is assigned to currentTopic, let's pick the maximum duration of other topics
                if ($durata == 0) {
                    $max = 0;
                    foreach ($this->children as $k => $v) {
                        if ($max < $v->durata) {
                            $max = $v->durata;
                        }
                    }
                    $durata = $max;
                }
            }

            if ($durata > 0) {
                $this->session['timestamps']['start'] = $time;
                if ((!isset($this->session['timestamps']['stop']) || ($_POST[self::POST_SUBMIT_VAR] && !$global))) {
                    $this->session['timestamps']['stop'] = $time + $durata;
                }
                $return = true;
            } else {
                $return = false;
            }

            //check if time is runned out. if it's true, end the test
            if (
                isset($this->session['timestamps'])
                && $this->session['timestamps']['start'] >= $this->session['timestamps']['stop']
            ) {
                $this->currentTopic = self::EOT;
            }
            return $return;
        }
        return false;
    }

    /**
     * choose what questions must be show
     *
     * @access protected
     */
    protected function pickRandomQuestion()
    {
        $dh = $GLOBALS['dh'];

        if ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT) {
            $changeState = true;
            if (!is_array($this->randomQuestion) || empty($this->randomQuestion)) {
                $res = $dh->testGetHistoryTest(['id_utente ' => $_SESSION['sess_id_user'],'id_nodo' => $this->id_nodo,'consegnato' => 0,'tempo_scaduto' => 0]);
                if (!$dh->isError($res) && !empty($res[count($res) - 1]['domande'])) {
                    $this->randomQuestion = unserialize($res[count($res) - 1]['domande']);
                } else {
                    $changeState = false;
                    $this->randomQuestion = [];

                    mt_srand(time());
                    if (!empty($this->children)) {
                        foreach ($this->children as $k => $sub) {
                            $this->pickRandomQuestionTopic($sub);
                        }
                    }
                    sort($this->randomQuestion);
                }
            }

            if ($changeState) {
                //switching off every topic and subtopic / question
                if (!empty($this->children)) {
                    foreach ($this->children as $sub) {
                        $sub->setDisplay(false);
                        if (!empty($sub->children)) {
                            foreach ($sub->children as $i) {
                                $i->setDisplay(false);
                                if (is_a($i, TopicTest::class)) {
                                    if (!empty($i->children)) {
                                        foreach ($i->children as $v) {
                                            $v->setDisplay(false);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                //switching on only needed topic and subtopic / question
                foreach ($this->randomQuestion as $k => $v) {
                    if (isset(self::$nodesArray[$v])) {
                        $q = self::$nodesArray[$v];
                        $q->setDisplay(true);
                        $t = $q->searchParent(TopicTest::class);
                        if (!is_null($t)) {
                            $t->setDisplay(true);
                            $t = $t->searchParent(TopicTest::class);
                            if (!is_null($t)) {
                                $t->setDisplay(true);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function pickRandomQuestionTopic($sub)
    {
        $n = count($sub->children ?? []);
        if ($sub->randomQuestions && intval($sub->livello) > 0) {
            $num_domande = intval($sub->livello);
        } else {
            $num_domande = $n;
        }

        $tmpList = [];
        while (count($tmpList) != $num_domande) {
            $r = random_int(0, $n);
            if (isset($sub->children[$r])) {
                $i = $sub->children[$r];
                if (!in_array($i->id_nodo, $tmpList)) {
                    $tmpList[] = $i->id_nodo;
                    if (is_a($i, QuestionTest::class)) {
                        $this->randomQuestion[] = $i->id_nodo;
                    } else {
                        $this->pickRandomQuestionTopic($i);
                    }
                }
            }
        }

        if (!empty($tmpList)) {
            foreach ($sub->children as $k => $i) {
                if (!in_array($i->id_nodo, $tmpList)) {
                    $i->setDisplay(false);
                }
            }
        }
    }

    /**
     * used to configure object with database's data options
     *
     * @access protected
     *
     */
    protected function configureProperties()
    {

        //first character delegated to child class
        //second character
        $this->returnLink = $this->tipo[1];

        //third character
        switch ($this->tipo[2]) {
            default:
            case ADA_RATING_TEST_INTERACTION:
                $this->feedback = true;
                $this->rating = true;
                $this->rating_answer = false;
                break;
            case ADA_FEEDBACK_TEST_INTERACTION:
                $this->feedback = true;
                $this->rating = false;
                $this->rating_answer = false;
                break;
            case ADA_BLIND_TEST_INTERACTION:
                $this->feedback = false;
                $this->rating = false;
                $this->rating_answer = false;
                break;
            case ADA_CORRECT_TEST_INTERACTION:
                $this->feedback = true;
                $this->rating = false;
                $this->rating_answer = true;
                break;
        }

        //fourth character
        switch ($this->tipo[3]) {
            default:
            case ADA_ONEPAGE_TEST_MODE:
                $this->sequenced = false;
                break;
            case ADA_SEQUENCE_TEST_MODE:
                $this->sequenced = true;
                break;
        }

        //fifth character delegated to child class
        //sixth character
        switch ($this->tipo[5]) {
            default:
            case ADA_NO_TEST_REPETEABLE:
                $this->repeatable = false;
                break;
            case ADA_YES_TEST_REPETEABLE:
                $this->repeatable = true;
                break;
        }

        //durata is used to store minim student level
        $this->minLevel = $this->durata;

        return true;
    }

    /**
     * Render the object structure
     *
     * @access public
     *
     * @param $return_html choose the return type
     * @return an object of CDOMElement or a string containing html
     *
     * (non-PHPdoc)
     * @see NodeTest::render()
     *
     * @author giorgio 20/ott/2014
     *
     * added feedback, rating and rating_answer parameters
     * that are not used here, but are needed to make the
     * declaration compatible with NodeTest::render()
     */
    public function render($return_html = true, $feedback = false, $rating = false, $rating_answer = false)
    {
        $html = $this->renderingHtml($ref);

        if (is_null($ref)) {
            $ref = $html;
        }

        if ($this->noLevel) {
            return $this->renderNoLevel();
        } elseif ($this->noRepeat) {
            return $this->renderNoRepeat();
        } elseif ($this->currentTopic === self::EOT) {
            return $this->renderEndTest($this->id_history_test);
        } elseif (!empty($this->children)) {
            if ($this->sequenced) {
                while (!isset($this->children[$this->currentTopic])) {
                    $this->currentTopic--;
                }
                $ref->addChild($this->children[$this->currentTopic]->render(false));
            } else {
                foreach ($this->children as $v) {
                    $ref->addChild($v->render(false));
                }
            }
        } else {
            $li = CDOMElement::create('li');
            $li->addChild(new CText(translateFN('Nessuna sessione inserita.')));
            $ref->addChild($li);
        }

        if ($return_html) {
            return $html->getHtml();
        } else {
            return $html;
        }
    }

    /**
     * return necessaries html objects that represent the object
     *
     * @access protected
     *
     * @param $ref reference to the object that will contain this rendered object
     * @param $feedback "show feedback" flag on rendering
     * @param $rating "show rating" flag on rendering
     * @param $rating_answer "show correct answer" on rendering
     *
     * @return an object of CDOMElement
     */
    protected function renderingHtml(&$ref = null, $feedback = false, $rating = false, $rating_answer = false)
    {
        if ($feedback || $_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $out = CDOMElement::create('div', 'id:testForm');
        } else {
            $out = CDOMElement::create('form', 'id:testForm,method:post');
            $out->setAttribute('enctype', 'multipart/form-data');
            $out->setAttribute('onsubmit', 'confirmSubmit(this); return false;');
            $out->addChild(new CText('<script type="text/javascript">var confirmEmptyAnswers = "' . translateFN('Non hai risposto ad una o più domande. Confermi l\'invio?') . '";</script>'));
        }

        $hidden = CDOMElement::create('hidden', 'name:' . self::POST_SUBMIT_VAR . ',value:1');
        $out->addChild($hidden);

        $this->buildStatusBox($out);

        if (!empty($this->session['timestamps'])) {
            $start = $this->session['timestamps']['start'];
            $stop = $this->session['timestamps']['stop'];

            $timer = CDOMElement::create('div');
            $timer->setAttribute('class', 'absoluteTimer');
            $out->addChild($timer);

            $out->addChild(new CText('<script type="text/javascript" language="javascript">
				testTimer(' . $start . ',' . $stop . ',\'' . translateFN('Tempo Scaduto. Il modulo sarà inviato automaticamente') . '.\');
			</script>'));
        }

        if (!empty($this->testo)) {
            $divTesto = CDOMElement::create('div');
            $divTesto->setAttribute('id', 'test_description');
            $divTesto->addChild(new CText($this->replaceInternalLinkMedia($this->testo)));
            $out->addChild($divTesto);
        }

        if ($_SESSION['sess_id_user_type'] == AMA_TYPE_AUTHOR) {
            $div = CDOMElement::create('div', 'class:admin_link');

            $div->addChild(new CText('[ '));
            $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');
            $addLink = CDOMElement::create('a', 'href:' . MODULES_TEST_HTTP . '/edit_topic.php?action=add&id_test=' . $this->id_nodo . $get_topic);
            $addLink->addChild(new CText(translateFN('Aggiungi sessione')));
            $div->addChild($addLink);
            $div->addChild(new CText(' ]'));

            $out->addChild($div);
        }

        $ul = CDOMElement::create('ul');
        $ul->setAttribute('class', 'topic_group_test');
        //$ul->setAttribute('start', $this->_currentTopic+1);
        $out->addChild($ul);

        if (!$feedback) {
            if ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT) {
                $submit = CDOMElement::create('button', 'type:submit,id:confirm,class:ui large green labeled icon button');
                $submit->setAttribute('value', translateFN('Conferma'));
                $submit->addChild(CDOMElement::create('i', 'class:checkmark icon'));
                $submit->addChild(new CText($submit->getAttribute('value')));

                $reset = CDOMElement::create('button', 'type:reset,id:redo,class:ui large blue labeled icon button');
                $reset->setAttribute('value', translateFN('Ripeti'));
                $reset->addChild(CDOMElement::create('i', 'class:undo icon'));
                $reset->addChild(new CText($reset->getAttribute('value')));

                $div = CDOMElement::create('div');
                $div->setAttribute('class', 'submit_test');
                $div->addChild($reset);
                $div->addChild(new CText('&nbsp;'));
                $div->addChild($submit);

                $out->addChild(CDOMElement::create('div', 'class:clearfix'));
                $out->addChild($div);
            } elseif ($this->sequenced) {
                //paginazione nel caso di test sequenziale
                $div = CDOMElement::create('div');
                $div->setAttribute('class', 'submit_test');
                if ($this->currentTopic > 0) {
                    $a = CDOMElement::create('a');
                    $a->setAttribute(
                        'href',
                        MODULES_TEST_HTTP . '/index.php?id_test=' .
                        $this->id_nodo . '&topic=' .
                        ((int)($this->currentTopic == self::EOT ? $this->countChildren() : $this->currentTopic) - 1)
                    );
                    $a->addChild(new CText(translateFN('Pagina precedente')));
                    $div->addChild(new CText(' [ '));
                    $div->addChild($a);
                    $div->addChild(new CText(' ] '));
                }

                for ($i = 0; $i < count($this->children); $i++) {
                    if ($this->currentTopic == $i) {
                        $div->addChild(new CText(' [ ' . ($i + 1) . ' ]'));
                    } else {
                        $a = CDOMElement::create('a');
                        $a->setAttribute('href', MODULES_TEST_HTTP . '/index.php?id_test=' . $this->id_nodo . '&topic=' . $i);
                        $a->addChild(new CText($i + 1));
                        $div->addChild(new CText(' [ '));
                        $div->addChild($a);
                        $div->addChild(new CText(' ] '));
                    }
                }

                if ($this->currentTopic < count($this->children) - 1) {
                    $a = CDOMElement::create('a');
                    $a->setAttribute(
                        'href',
                        MODULES_TEST_HTTP . '/index.php?id_test=' .
                        $this->id_nodo . '&topic=' .
                        ((int)($this->currentTopic == self::EOT ? $this->countChildren() : $this->currentTopic) + 1)
                    );
                    $a->addChild(new CText(translateFN('Pagina successiva')));
                    $div->addChild(new CText(' [ '));
                    $div->addChild($a);
                    $div->addChild(new CText(' ] '));
                }
                $out->addChild(CDOMElement::create('div', 'class:clearfix'));
                $out->addChild($div);
            }
        }

        $ref = $ul;
        return $out;
    }

    /**
     * Render the object structure when the test is over
     *
     * @access protected
     *
     * @param $id_history_test the id of test instance
     * @param $return_html choose the return type
     *
     * @return an object of CDOMElement
     */
    protected function renderEndTest($id_history_test, $return_html = true)
    {
        $dh = $GLOBALS['dh'];

        if ($_SESSION['sess_id_user_type'] == AMA_TYPE_TUTOR) {
            $this->feedback = true;
            $this->rating = true;
        }

        if ($this->feedback) {
            //check if $id_history_test param is an integer and retrieve rows from database
            if (intval($id_history_test) <= 0) {
                $html = CDOMElement::create('div');
                $html->addChild(new CText(translateFN('Test non valido!')));
            } else {
                $id_history_test = intval($id_history_test);
                $givenTest = $dh->testGetHistoryTest($id_history_test);
                $givenTest = $givenTest[0];
                $givenAnswers = $dh->testGetGivenAnswers($id_history_test);
                if (AMADataHandler::isError($givenTest) || AMADataHandler::isError($givenAnswers)) {
                    $html = CDOMElement::create('div');
                    $html->addChild(new CText(translateFN('Si è verificato un errore durante il recupero delle informazioni!')));
                } else {
                    //search every question objects
                    $genericItems = $this->children;
                    $questions = [];
                    while (!empty($genericItems)) {
                        foreach ($genericItems as $k => $child) {
                            if (!is_subclass_of($child, QuestionTest::class)) {
                                for ($i = 0; $i < $child->countChildren(); $i++) {
                                    $genericItems[] = $child->getChild($i);
                                }
                            } else {
                                $questions[] = $child;
                            }
                            unset($genericItems[$k]);
                        }
                    }
                }
                unset($genericItems);

                //assign data to objects
                if (!empty($questions)) {
                    foreach ($questions as $q) {
                        $q->setDisplay(false);
                    }
                }

                $max_score = 0;
                $score = 0;
                if (!empty($givenAnswers)) {
                    foreach ($givenAnswers as $a => $answer) {
                        if (!empty($questions)) {
                            foreach ($questions as $k => $q) {
                                if ($answer['id_nodo'] == $q->id_nodo) {
                                    $answer['risposta'] = unserialize($answer['risposta']);
                                    $q->setGivenAnswer($answer);
                                    $q->setDisplay(true);
                                    //while assigning calculate scores
                                    $score += $answer['punteggio'];
                                    $max_score += $q->getMaxScore();
                                    unset($questions[$k]);
                                    unset($givenAnswers[$a]);
                                    break;
                                }
                            }
                        }
                    }
                }
                unset($givenAnswers);

                //rendering test
                $html = $this->renderingHtml($ref, $this->feedback, $this->rating, $this->rating_answer);
                if (is_null($ref)) {
                    $ref = $html;
                }
                if (!empty($this->children)) {
                    foreach ($this->children as $v) {
                        $ref->addChild($v->render(false, $this->feedback, $this->rating, $this->rating_answer));
                    }
                }

                //show total Score
                if ($this->rating) {
                    $div = CDOMElement::create('div', 'id:test_score');
                    $div->setAttribute('id', 'score_test');
                    if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
                        $testo = translateFN('Lo studente ha totalizzato %s punti su %s');
                    } else {
                        $testo = translateFN('Hai totalizzato %s punti su %s');
                    }
                    $div->addChild(new CText(sprintf($testo, $score, $max_score)));
                    $html->addChild($div);
                }

                // prints control to set test repeatable
                if (!$this->repeatable && $_SESSION['sess_id_user_type'] == AMA_TYPE_TUTOR) {
                    $div = CDOMElement::create('div', 'id:test_controls');

                    $label = CDOMElement::create('label');
                    $label->addChild(new CText(translateFN('Permettere all\'utente di ripetere il test?')));
                    $div->addChild($label);

                    $radioSi = CDOMElement::create('radio', 'id:repeateTestYes,name:repeateTest');
                    $radioSi->setAttribute('onchange', 'toggleRepeatable(' . $id_history_test . ',true);');
                    if ($givenTest['ripetibile']) {
                        $radioSi->setAttribute('checked', '');
                    }
                    $div->addChild($radioSi);
                    $div->addChild(new CText(translateFN('Si')));

                    $radioNo = CDOMElement::create('radio', 'id:repeateTestNo,name:repeateTest');
                    $radioNo->setAttribute('onchange', 'toggleRepeatable(' . $id_history_test . ',false);');
                    if (!$givenTest['ripetibile']) {
                        $radioNo->setAttribute('checked', '');
                    }
                    $div->addChild($radioNo);
                    $div->addChild(new CText(translateFN('No')));

                    $html->addChild($div);
                }
            }
        } else {
            $html = CDOMElement::create('div');
            $html->addChild(new CText(Node::prepareInternalLinkMediaForEditor($this->consegna)));
        }

        //return link
        $return_link = null;
        switch ($this->returnLink) {
            default:
            case ADA_NO_TEST_RETURN:
                $label = null;
                $return_link = null;
                break;
            case ADA_NEXT_NODE_TEST_RETURN:
                $node_obj = null;
                if (!is_null($this->id_nodo_riferimento)) {
                    $node_obj = new Node($this->id_nodo_riferimento);
                } else {
                    $survey = $dh->testGetCourseSurveys(['id_test' => $this->id_nodo,'id_corso' => $this->id_corso]);
                    if (!AMADB::isError($survey) && is_array($survey) && count($survey) == 1) {
                        $survey = array_shift($survey);
                        $node_obj = new Node($survey['id_nodo']);
                    }
                }
                if (!is_null($node_obj) && !empty($node_obj->next_id)) {
                    $label = translateFN('Procedi');
                    $return_link = HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $node_obj->next_id;
                    $buttonClass = 'ui right labeled icon green button';
                    $buttonIcon = 'right arrow';
                }
                break;
            case ADA_INDEX_TEST_RETURN:
                $label = translateFN('Torna all\'indice del corso');
                $return_link = HTTP_ROOT_DIR . '/browsing/main_index.php';
                $buttonClass = 'ui labeled icon purple button';
                $buttonIcon = 'sitemap';
                break;
            case ADA_COURSE_INDEX_TEST_RETURN:
                $label = translateFN('Torna all\'elenco dei corsi');
                $return_link = HTTP_ROOT_DIR . '/browsing/user.php';
                $buttonClass = 'ui labeled icon orange button';
                $buttonIcon = 'home';
                break;
            case ADA_COURSE_FIRSTNODE_TEST_RETURN:
                $course_obj = new Course($this->id_corso);
                if (strlen($course_obj->id_nodo_iniziale) > 0) {
                    $label = translateFN('Torna all\'inizio del corso');
                    $return_link = HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $this->id_corso . '_' . $course_obj->id_nodo_iniziale;
                    $buttonClass = 'ui right labeled icon blue button';
                    $buttonIcon = 'repeat';
                }
                break;
        }

        if (!is_null($return_link)) {
            $a = CDOMElement::create('a', 'href:' . $return_link);
            if (isset($buttonClass) && strlen($buttonClass) > 0) {
                $a->setAttribute('class', $buttonClass);
            }
            if (isset($buttonIcon) && strlen($buttonIcon) > 0) {
                $a->addChild(CDOMElement::create('i', 'class: ' . $buttonIcon . ' icon'));
            }
            $a->addChild(new CText($label));
            $div = CDOMElement::create('div', 'id:return_link');
            $div->addChild($a);
            $html->addChild(CDOMElement::create('div', 'class:clearfix'));
            $html->addChild($div);
        }

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => ModuleTestEvent::class,
                    'eventName' => ModuleTestEvent::POSTRENDERENDTEST,
                    'eventPrefix' => realpath($_SERVER['SCRIPT_FILENAME']),
                ],
                static::class,
            );
            $args = $event->getArguments();
            if (array_key_exists('statusbox', $args)) {
                /* hack to insert statusbox as first element */
                $newhtml = CDOMElement::create('div');
                $newhtml->addChild($args['statusbox']);
                $newhtml->addChild($html);
                $html = $newhtml;
            }
        }

        if ($return_html) {
            return $html->getHtml();
        } else {
            return $html;
        }
    }

    /**
     * Render the object structure when the test/survey cannot be repeated
     *
     * @access protected
     *
     * @param $return_html choose the return type
     *
     * @return an object of CDOMElement
     */
    abstract protected function renderNoRepeat($return_html = true);


    /**
     * Render the object structure when the test/survey cannot be accessed by student
     *
     * @access protected
     *
     * @param $return_html choose the return type
     *
     * @return an object of CDOMElement
     */
    abstract protected function renderNoLevel($return_html = true);

    /**
     * return session key
     *
     * @access protected
     *
     * @return string
     */
    protected function getSessionKey()
    {
        return 'test_' . $this->id_nodo;
    }

    /**
     * return current topic
     *
     * @access public
     *
     * @return int
     */
    public function getCurrentTopic()
    {
        return $this->currentTopic;
    }

    /**
     * return status box
     *
     * @access public
     *
     */
    protected function buildStatusBox($out)
    {
        if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $div = CDOMElement::create('fieldset');
            $div->setAttribute('id', 'test_info');

            $legend = CDOMElement::create('legend');
            $legend->addChild(new CText(translateFN('Impostazioni')));
            $div->addChild($legend);

            $ul = CDOMElement::create('ul', 'id:ul_test_info');
            $i = 0;

            //ripetibile
            $ripetibile = ($this->repeatable) ? translateFN('Si') : translateFN('No');
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Ripetibile') . '</b>: ' . $ripetibile));

            //suddivisione per sessioni
            $sessioni = ($this->sequenced) ? translateFN('Si') : translateFN('No');
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Suddividi per sessioni') . '</b>: ' . $sessioni));

            //feedback utente
            if ($this->feedback) {
                if ($this->rating) {
                    $feedback = translateFN('Mostra correzioni risposte e punteggio ottenuto');
                } else {
                    $feedback = translateFN('Mostra correzioni risposte');
                }
            } else {
                $feedback = translateFN('Nessun feedback');
            }
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Feedback all\'utente') . '</b>: ' . $feedback));

            //durata
            $durata = 0;
            if (!empty($this->children)) {
                foreach ($this->children as $k => $v) {
                    $durata += $v->durata;
                }
            }
            $durata = ($durata == 0) ? translateFN('Nessun limite') : round($durata / 60, 2) . ' ' . translateFN('minuti');
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Durata totale') . '</b>: ' . $durata));

            //livello minimo
            if (!empty($this->durata)) {
                $this->durata = intval($this->durata) . '° ' . translateFN('livello');
            } else {
                $this->durata = translateFN('Nessuno');
            }
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Livello minimo') . '</b>: ' . $this->durata));


            //test di sbarramento
            if (is_a($this, TestTest::class)) {
                $sbarramento = ($this->barrier) ? translateFN('Si') : translateFN('No');
                $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
                $lis[$i]->addChild(new CText('<b>' . translateFN('Test di sbarramento') . '</b>: ' . $sbarramento));

                if ($this->barrier) {
                    $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
                    $lis[$i]->addChild(new CText('<b>' . translateFN('Punteggio minimo') . '</b>: ' . intval($this->correttezza)));

                    $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
                    $lis[$i]->addChild(new CText('<b>' . translateFN('Livello acquisito') . '</b>: ' . $this->livello));
                }
            }

            //link di ritorno
            $options = [
                ADA_NO_TEST_RETURN => translateFN('Non mostrare link'),
                ADA_NEXT_NODE_TEST_RETURN => translateFN('Mostra link al nodo successivo del corso'),
                ADA_INDEX_TEST_RETURN => translateFN('Mostra link all\'indice del corso'),
                ADA_COURSE_INDEX_TEST_RETURN => translateFN('Mostra link all\'elenco dei corsi'),
                ADA_COURSE_FIRSTNODE_TEST_RETURN => translateFN('Mostra link al nodo iniziale del corso'),
            ];
            $returnLink = $options[$this->returnLink];
            $lis[++$i] = CDOMElement::create('li', 'class:li_test_info');
            $lis[$i]->addChild(new CText('<b>' . translateFN('Link di ritorno') . '</b>: ' . $returnLink));

            foreach ($lis as $li) {
                $ul->addChild($li);
            }
            $div->addChild($ul);
            $out->addChild($div);
        } elseif ($this->onSaveError) {
            $error = CDOMElement::create('div');
            $error->setAttribute('class', 'test_error');
            $error->addChild(new CText(translateFN('Riscontrato un errore durante il salvataggio dei dati. Si prega di riprovare.')));
            $out->addChild($error);
        }
    }

    /**
     * Method called to generate empty answer for each answer not given by student.
     * Injects data into $_POST directly
     *
     */
    protected function normalizeAnswers()
    {
        if (!empty($_POST[self::POST_SUBMIT_VAR])) {
            $questions = [];
            $start = 0;
            $limit = $this->countChildren();
            if ($this->sequenced) {
                $start = (int)($this->currentTopic == self::EOT ? $limit : $this->currentTopic) - 1;
                if ($start < 0) {
                    $start = 0;
                }
                $limit = $start + 1;
            }
            for ($i = $start; $i < $limit; $i++) {
                $topic = $this->getChild($i);
                if (!empty($topic->children)) {
                    foreach ($topic->children as $subTopic) {
                        if (is_a($subTopic, TopicTest::class)) {
                            foreach ($subTopic->children as $v) {
                                $questions[] = $v;
                            }
                        } elseif (is_a($subTopic, QuestionTest::class)) {
                            $questions[] = $subTopic;
                        }
                    }
                }
            }
            if (!empty($questions)) {
                foreach ($questions as $i => $q) {
                    if (in_array($q->id_nodo, $this->randomQuestion)) {
                        $topicId = $q->id_nodo_parent;
                        $questionId = $q->id_nodo;
                        if (!isset($_POST[self::POST_TOPIC_VAR][$topicId][$questionId][self::POST_ANSWER_VAR])) {
                            switch ($q->tipo[1]) {
                                case ADA_MULTIPLE_CHECK_TEST_TYPE:
                                case ADA_CLOZE_TEST_TYPE:
                                    $answer = [];
                                    break;
                                default:
                                    $answer = '';
                                    break;
                            }
                            $_POST[self::POST_TOPIC_VAR][$topicId][$questionId][self::POST_ANSWER_VAR] = $answer;
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks if a student can be admitted to the test or survey
     *
     * @return boolean
     *
     */
    protected function checkStudentLevel()
    {
        return ($_SESSION['sess_userObj']->livello >= $this->minLevel);
    }

    /**
     * Checks if a test or a survey is repeatable by current student
     *
     * @global type $dh
     *
     * @return boolean
     */
    protected function checkRepeatable()
    {
        $dh = $GLOBALS['dh'];

        if ($this->repeatable) {
            return true;
        }

        $where = ['id_nodo' => $this->id_nodo, 'id_utente' => $_SESSION['sess_id_user']];
        if (isset($_SESSION['sess_id_course_instance'])) {
            $where['id_istanza_corso'] = $_SESSION['sess_id_course_instance'];
        } else {
            error_log('*** WARNING!!! $_SESSION[\'sess_id_course_instance\'] not set in ' . __METHOD__ . ': ' . __FILE__ . ':' . __LINE__);
        }
        $res = $dh->testGetHistoryTest(
            array_merge(
                $where,
                ['consegnato' => 1]
            )
        );
        if ($dh->isError($res) || empty($res)) {
            $res = $dh->testGetHistoryTest(
                array_merge(
                    $where,
                    ['tempo_scaduto' => 1]
                )
            );
        }
        if (!$dh->isError($res)) {
            if (empty($res)) {
                return true;
            } else {
                if ($res[count($res) - 1]['ripetibile'] == 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return feedback setting
     *
     * @return boolean
     */
    public function getFeedback()
    {
        return $this->feedback;
    }
}

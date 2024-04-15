<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Module\Test\TopicTest;

use Lynxlab\ADA\Module\Test\TestTest;

use Lynxlab\ADA\Module\Test\SurveyTest;

use Lynxlab\ADA\Module\Test\RootTest;

use Lynxlab\ADA\Module\Test\QuestionTest;

use Lynxlab\ADA\Module\Test\QuestionStandardTest;

use Lynxlab\ADA\Module\Test\QuestionSlotClozeTest;

use Lynxlab\ADA\Module\Test\QuestionSelectClozeTest;

use Lynxlab\ADA\Module\Test\QuestionOpenUploadTest;

use Lynxlab\ADA\Module\Test\QuestionOpenManualTest;

use Lynxlab\ADA\Module\Test\QuestionOpenAutomaticTest;

use Lynxlab\ADA\Module\Test\QuestionNormalClozeTest;

use Lynxlab\ADA\Module\Test\QuestionMultipleClozeTest;

use Lynxlab\ADA\Module\Test\QuestionMultipleCheckTest;

use Lynxlab\ADA\Module\Test\QuestionMediumClozeTest;

use Lynxlab\ADA\Module\Test\QuestionLikertTest;

use Lynxlab\ADA\Module\Test\QuestionEraseClozeTest;

use Lynxlab\ADA\Module\Test\QuestionDragDropClozeTest;

use Lynxlab\ADA\Module\Test\NullTest;

use Lynxlab\ADA\Module\Test\NodeTest;

use Lynxlab\ADA\Module\Test\AnswerTest;

use Lynxlab\ADA\Module\Test\AMATestDataHandler;

use Lynxlab\ADA\Main\History\NavigationHistory;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\CORE\html4\CDOMElement;

use Lynxlab\ADA\Main\AMA\AMAError;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

// Trigger: ClassWithNameSpace. The class NodeTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Node\Node;

use function Lynxlab\ADA\Main\Utilities\redirect;

abstract class NodeTest
{
    public const NODE_TYPE = null;
    public const CHILD_CLASS = null;
    public const GET_TOPIC_VAR = 'topic';
    public const POST_TOPIC_VAR = 'question';
    public const POST_SUBMIT_VAR = 'testSubmit';
    public const POST_ANSWER_VAR = 'answer';
    public const POST_OTHER_VAR = 'other';
    public const POST_EXTRA_VAR = 'extra';
    public const POST_ATTACHMENT_VAR = 'attachment';

    protected $id_nodo;
    protected $id_corso;
    protected $id_posizione;
    protected $id_utente;
    protected $id_istanza;
    protected $nome;
    protected $titolo;
    protected $consegna;
    protected $testo;
    protected $tipo;
    protected $data_creazione;
    protected $ordine;
    protected $id_nodo_parent;
    protected $id_nodo_radice;
    protected $id_nodo_riferimento;
    protected $livello;
    protected $versione;
    protected $n_contatti;
    protected $icona;
    protected $colore_didascalia;
    protected $colore_sfondo;
    protected $correttezza;
    protected $copyright;
    protected $didascalia;
    protected $durata;
    protected $titolo_dragdrop;

    protected $parent = null;
    public $children = null;
    protected $session = null;
    protected $display = true;

    protected static $nodesArray = [];

    /**
     * class constructor
     * use static function createNode to instantiate a on object
     *
     * @access protected
     *
     * @param $data database record as array
     *
     */
    protected function __construct($data, $parent = null)
    {
        $this->setParent($parent);

        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }

        $this->configureProperties();
    }

    /**
     * generic getter method
     *
     * @access public
     * @param $name attribute to retrieve
     *
     * @return attribute value or null if doesn't exist
     */
    public function __get($name)
    {
        if (!property_exists(get_class($this), $name) || !isset($this->{$name})) {
            return null;
        } else {
            return $this->{$name};
        }
    }

    /**
     * used to configure object with database's data options
     *
     * @access protected
     *
     */
    abstract protected function configureProperties();

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
     * @return \Lynxlab\ADA\CORE\html4\CElement an object of CDOMElement
     */
    abstract protected function renderingHtml(&$ref = null, $feedback = false, $rating = false, $rating_answer = false);

    /**
     * retrieve node's data from database and validates it
     *
     * @access private
     *
     * @param $data id node or database record as array
     * @return an array with the node's data or an AMAError object
     */
    private static function readData($data)
    {
        $dh = $GLOBALS['dh'];

        //check if the passed $data is the node id
        if (!is_array($data)) {
            if (intval($data) > 0) {
                $data = intval($data);
                $data = $dh->testGetNode($data);
                if (AMADataHandler::isError($data)) {
                    return $data;
                }
            } elseif (!is_array($data) || empty($data)) {
                //or it's not the record's array
                return new AMAError(AMA_ERR_WRONG_ARGUMENTS);
            }
            //then it is the record's array
        }

        //eventually clean record's array from fields not compliant
        foreach ($data as $k => $v) {
            if (!property_exists(get_class(), $k)) {
                return new AMAError(AMA_ERR_INCONSISTENT_DATA);
            }
        }
        return $data;
    }

    /**
     * Create node retrieving data from database
     *
     * @access public
     *
     * @param $data id node or database record as array
     * @param $parent object reference to parent node
     * @return object relative node object or an AMAError object
     */
    public static function readNode($data, $parent = null)
    {
        //read data from database
        $data = self::readData($data);
        if (is_object($data) && (get_class($data) == 'AMAError')) {
            return $data;
        } else {
            //and if data is valid, let's check what kind of object we need to instantiate
            //first character
            switch ($data['tipo'][0]) {
                default:
                    return new NullTest($data, $parent);
                    break;
                case ADA_TYPE_TEST:
                    return new TestTest($data, $parent);
                    break;
                case ADA_TYPE_SURVEY:
                    return new SurveyTest($data, $parent);
                    break;
                case ADA_GROUP_TOPIC:
                    return new TopicTest($data, $parent);
                    break;
                case ADA_GROUP_QUESTION:
                    //second character
                    switch ($data['tipo'][1]) {
                        case ADA_MULTIPLE_CHECK_TEST_TYPE:
                            return new QuestionMultipleCheckTest($data, $parent);
                            break;
                        default:
                        case ADA_STANDARD_TEST_TYPE:
                            return new QuestionStandardTest($data, $parent);
                            break;
                        case ADA_LIKERT_TEST_TYPE:
                            return new QuestionLikertTest($data, $parent);
                            break;
                        case ADA_OPEN_MANUAL_TEST_TYPE:
                            return new QuestionOpenManualTest($data, $parent);
                            break;
                        case ADA_OPEN_AUTOMATIC_TEST_TYPE:
                            return new QuestionOpenAutomaticTest($data, $parent);
                            break;
                        case ADA_CLOZE_TEST_TYPE:
                            //fourth character
                            switch ($data['tipo'][3]) {
                                case ADA_NORMAL_TEST_SIMPLICITY:
                                    return new QuestionNormalClozeTest($data, $parent);
                                    break;
                                case ADA_SELECT_TEST_SIMPLICITY:
                                    return new QuestionSelectClozeTest($data, $parent);
                                    break;
                                case ADA_MEDIUM_TEST_SIMPLICITY:
                                    return new QuestionMediumClozeTest($data, $parent);
                                    break;
                                case ADA_DRAGDROP_TEST_SIMPLICITY:
                                    return new QuestionDragDropClozeTest($data, $parent);
                                    break;
                                case ADA_ERASE_TEST_SIMPLICITY:
                                    return new QuestionEraseClozeTest($data, $parent);
                                    break;
                                case ADA_SLOT_TEST_SIMPLICITY:
                                    return new QuestionSlotClozeTest($data, $parent);
                                    break;
                                case ADA_MULTIPLE_TEST_SIMPLICITY:
                                    return new QuestionMultipleClozeTest($data, $parent);
                                    break;
                            }
                            break;
                        case ADA_OPEN_UPLOAD_TEST_TYPE:
                            return new QuestionOpenUploadTest($data, $parent);
                            break;
                    }
                    break;
                case ADA_LEAF_ANSWER:
                    return new AnswerTest($data, $parent);
                    break;
            }
        }
    }

    /**
     * Create a full structured test
     *
     * @access public
     *
     * @param $data id node
     * @return the relative nodes structure or an AMAError object
     */
    public static function readTest($id_nodo, $dh = null)
    {
        if (is_null($dh)) {
            $dh = $GLOBALS['dh'];
        }

        //check if $id_nodo param is an integer and retrieve rows from database
        if (intval($id_nodo) > 0) {
            $id_nodo = intval($id_nodo);
            $data = $dh->testGetNodesByRadix($id_nodo);
            if (AMADataHandler::isError($data)) {
                return $data;
            } else {
                $objects = [];
                $root = null;
                //ciclying all rows to instantiate and attach nodes to form a three
                //the external loop is used to catch all the nodes that doesn't find a father on first tries
                while (!empty($data)) {
                    foreach ($data as $k => $v) {
                        $tipo = $v['tipo'][0];
                        $parent = $v['id_nodo_parent'];
                        $id = $v['id_nodo'];

                        //this search the root
                        if (is_null($root) && ($tipo == ADA_TYPE_TEST || $tipo == ADA_TYPE_SURVEY)) {
                            $objects[$id] = NodeTest::readNode($v);
                            $root = $objects[$id];
                            self::$nodesArray[$root->id_nodo] = $root;
                            //once the row is attach, it can be deleted
                            unset($data[$k]);
                        } elseif (!is_null($parent) && isset($objects[$parent])) {
                            //this attach nodes to the right element
                            $objects[$id] = NodeTest::readNode($v, $objects[$parent]);
                            $objects[$parent]->addChild($objects[$id]);
                            //once the row is attach, it can be deleted
                            unset($data[$k]);
                        }
                    }
                }
                //free resources
                unset($objects);
                //if $root is still null, the test doesn't exists!
                if (is_null($root)) {
                    return new AMAError(AMA_ERR_INCONSISTENT_DATA);
                } else {
                    return $root;
                }
            }
        } else {
            return new AMAError(AMA_ERR_WRONG_ARGUMENTS);
        }
    }

    /**
     * Adds a child to object
     *
     * @access public
     *
     * @param $child child object
     * @return the relative nodes structure or an AMAError object
     */
    public function addChild(NodeTest $child)
    {
        //use pipes in CHILD_CLASS constant to specifiy more than one possible child class
        $constants = constant(get_class($this) . '::CHILD_CLASS');
        $constants = explode('|', $constants);
        foreach ($constants as $v) {
            if (get_class($child) == $v || is_subclass_of($child, $v)) {
                if (is_null($this->children)) {
                    $this->children = [];
                }
                $this->children[] = $child;
                self::$nodesArray[$child->id_nodo] = &$child;
                return true;
            }
        }
        return false;
    }

    /**
     * Add parent reference to node
     *
     * @param NodeTest $parent parent node reference
     */
    public function setParent(NodeTest $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Render the object structure
     *
     * @access public
     *
     * @param $return_html choose the return type
     * @param $feedback "show feedback" flag on rendering
     * @param $rating "show rating" flag on rendering
     * @param $rating_answer "show correct answer" on rendering
     *
     * @return string|\Lynxlab\ADA\CORE\html4\CElement an object of CDOMElement or a string containing html
     */
    public function render($return_html = true, $feedback = false, $rating = false, $rating_answer = false)
    {

        $html = $this->renderingHtml($ref, $feedback, $rating, $rating_answer);

        if (is_null($ref)) {
            $ref = $html;
        }

        if (!empty($this->children) && $this->display) {
            foreach ($this->children as $v) {
                $ref->addChild($v->render(false, $feedback, $rating, $rating_answer));
            }
        }

        if ($return_html) {
            return $html->getHtml();
        } else {
            return $html;
        }
    }

    /**
     * Return the desired child
     *
     * @access public
     *
     * @param $i child index
     * @return object|false if the requested object doesn't exist, the object otherwise
     */
    public function getChild($i)
    {
        if (isset($this->children[$i])) {
            return $this->children[$i];
        } else {
            return false;
        }
    }

    /**
     * Return how many children the object has
     *
     * @access public
     *
     * @return the number of children or false otherwise
     */
    public function countChildren()
    {
        if (is_array($this->children)) {
            return count($this->children);
        } else {
            return false;
        }
    }

    /**
     * search a node on directly descendant children
     *
     * @access public
     *
     * @param $value value to find
     * @param $field field used in comparison
     * @return reference to object, an array of objects, false if not found
     */
    public function searchChild($value, $field = 'id_nodo', $forceArray = false)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $results = [];
        if (!empty($this->children)) {
            foreach ($this->children as $c) {
                if (in_array($c->{$field}, $value)) {
                    $results[] = $c;
                }
            }
        }

        if (count($results) == 0) {
            return false;
        } elseif ($forceArray || count($results) > 1) {
            return $results;
        } else {
            return $results[0];
        }
    }

    /**
     * search a node on all children in a breadth-first manner
     *
     * @access public
     *
     * @param $id_nodo child id_nodo
     * @return reference to object or false if not found
     */
    public function searchBreadthChild($id_nodo)
    {
        $found = false;
        $array = [$this];

        foreach ($array as $k => $f) {
            $found = $f->searchChild($id_nodo);
            if ($found != false) {
                break;
            } else {
                for ($i = 0; $i < $f->countChildren(); $i++) {
                    $array[] = $f->getChild($i);
                }
                unset($array[$k]);
            }
        }

        return $found;
    }

    /**
     * search a node on all children in a breadth-first manner
     *
     * @access public
     *
     * @param $id_nodo child id_nodo
     * @return reference to object or false if not found
     */
    public function searchParent($type)
    {
        $parent = $this->parent;
        while (!is_a($parent, $type) && !is_null($parent)) {
            $parent = $parent->_parent;
        }
        return $parent;
    }

    /**
     * setter method for display variable
     *
     * @access public
     *
     * @param $value boolean
     *
     * @return boolean
     */
    public function setDisplay($value)
    {
        if (is_bool($value)) {
            $this->display = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * method that search and replace media tag found in text
     *
     * @access public
     *
     * @param $text text (string)
     *
     * @return string
     */
    protected function replaceInternalLinkMedia($text)
    {
        /**
         * call parseInternalLinkMedia passing -1 as level
         * to tell it's been called from a test node
         *
         * Actual test admission check on user level is
         * done by RootTest::checkStudentLevel method
         */
        return Node::parseInternalLinkMedia($text, -1, null, null, null);
    }

    /**
     * Checks if the passed nodeObj has a linked test node or survey
     * and does the redirection to proper url if needed
     *
     * @param Node $nodeObj the object to be checked
     */
    public static function checkAndRedirect($nodeObj)
    {

        $isView = (strstr($_SERVER['PHP_SELF'], 'view.php') !== false);

        $redirectTo = '';
        $node = null;
        $test_db = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        $res = $test_db->testGetNodes(['id_nodo_riferimento' => $nodeObj->id]);

        if (!empty($res) && count($res) == 1 && !AMADataHandler::isError($res)) {
            $node = array_shift($res);
        }

        // Redirect only if found node is not a survey
        if (!is_null($node) &&  $node['tipo'][0] != ADA_TYPE_SURVEY) {
            if ($_SESSION['sess_id_user_type'] != AMA_TYPE_AUTHOR) {
                $redirectTo  = MODULES_TEST_HTTP . '/index.php?id_test=' . $node['id_nodo'];
            } else {
                if (!$isView) {
                    $redirectTo = HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $nodeObj->id;
                }
            }
        } else {
            $res = $test_db->testGetCourseSurveys(['id_nodo' => $nodeObj->id]);
            if (!empty($res) && count($res) == 1 && !AMADataHandler::isError($res)) {
                $node = array_shift($res);
                if ($_SESSION['sess_id_user_type'] != AMA_TYPE_AUTHOR) {
                    $redirectTo = MODULES_TEST_HTTP . '/index.php?id_test=' . $node['id_test'];
                } else {
                    if (!$isView) {
                        $redirectTo = HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $nodeObj->id;
                    }
                }
            }
        }
        // Do redirect if needed
        if (strlen($redirectTo) > 0) {
            /*Remove the last item to NavigationHistory to increase the value of back button correctly*/
            $_SESSION['sess_navigation_history']->removeLastItem();
            redirect($redirectTo);
        }
    }

    /**
     * Tries to flatten the node tree (i.e. discarding parents to prevent circular references) and builds an array with it
     *
     * @return array[]
     */
    public function toArray()
    {
        $retArray = [];
        if (!empty($this->children)) {
            if (!isset($retArray[$this->id_nodo])) {
                $retArray[$this->id_nodo] = ['id' => $this->id_nodo, 'nome' => $this->nome, 'titolo' => $this->titolo, 'topics' => []];
            }

            /** @var TopicTest $topic */
            foreach ($this->children as $topic) {
                if (!isset($retArray[$this->id_nodo]['topics'][$topic->id_nodo])) {
                    $retArray[$this->id_nodo]['topics'][$topic->id_nodo] = [
                        'id' => $topic->id_nodo,
                        'nome' => $topic->nome,
                        'titolo' => $topic->titolo,
                        'questions' => [],
                    ];
                }

                if (!empty($topic->children)) {
                    /** @var QuestionTest $question */
                    foreach ($topic->children as $question) {
                        if (!isset($retArray[$this->id_nodo]['topics'][$topic->id_nodo]['questions'][$question->id_nodo])) {
                            $retArray[$this->id_nodo]['topics'][$topic->id_nodo]['questions'][$question->id_nodo] = [
                                'id' => $question->id_nodo,
                                'nome' => $question->nome,
                                'titolo' => $question->titolo,
                                'consegna' => $question->consegna,
                                'answers' => [],
                            ];
                        }

                        if (!empty($question->children)) {
                            /** @var AnswerTest $answer */
                            foreach ($question->children as $answer) {
                                if (!isset($retArray[$this->id_nodo]['topics'][$topic->id_nodo]['questions'][$question->id_nodo]['answers'][$answer->id_nodo])) {
                                    $retArray[$this->id_nodo]['topics'][$topic->id_nodo]['questions'][$question->id_nodo]['answers'][$answer->id_nodo] = [
                                        'id' => $answer->id_nodo,
                                        'nome' => $answer->nome,
                                        'titolo' => $answer->titolo,
                                        'testo' => $answer->testo,
                                        'consegna' => $answer->consegna,
                                        'correttezza' => $answer->correttezza,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $retArray;
    }
}

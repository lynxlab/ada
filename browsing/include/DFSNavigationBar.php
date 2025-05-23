<?php

/**
 * DFSNavigationBar.inc.php
 *
 * Contains the DFSNavigationBar class.
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2011, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Browsing;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\NodeTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * This class is responsible for rendering the previous and next node link id,
 * given a node.
 * These two nodes are calculated in respect of a depth first search ordering.
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2011, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class DFSNavigationBar
{
    /**
     *
     * @param Node $n The node for which calculate the previous and next node link
     * @param array $params
     */
    public function __construct(Node $n, $params = [])
    {
        $this->currentNode = $n->id;
        if (!isset($params['prevId'])) {
            $params['prevId'] = null;
        }
        if (!isset($params['nextId'])) {
            $params['nextId'] = null;
        }

        $prevId = DataValidator::validateNodeId($params['prevId']);
        if ($prevId !== false) {
            $this->previousNode = $prevId;
        } else {
            $this->findPreviousNode($n, $params['userLevel']);
        }

        $nextId = DataValidator::validateNodeId($params['nextId']);
        if ($nextId !== false) {
            $this->nextNode = $nextId;
        } else {
            $this->findNextNode($n, $params['userLevel']);
        }

        /**
         * set the tester to be used to be the one stored in session...
         */
        if (isset($_SESSION['sess_selected_tester']) && strlen($_SESSION['sess_selected_tester'])) {
            $this->testerToUse = $_SESSION['sess_selected_tester'];
        }
        /**
         * ...unless a testerToUse params has been passed, in which case force that
         */
        if (isset($params['testerToUse']) && DataValidator::validateTestername($params['testerToUse'], MULTIPROVIDER)) {
            $this->testerToUse = $params['testerToUse'];
        }

        /**
         * @author giorgio 08/ott/2013
         * check if this is a node wich has been generated when creating a test.
         * If it is, next node is the first topic of the test.
         * BUT, I'll pass the computed $this->_nextNode to give a callBack point
         * to be used when user is in the last topic of the test.
         */
        if (ModuleLoaderHelper::isLoaded('TEST')) { // && strpos($n->type,(string) constant('ADA_PERSONAL_EXERCISE_TYPE')) === 0) {
            if (isset($GLOBALS['dh'])) {
                $GLOBALS['dh']->disconnect();
            }
            $test_db = AMATestDataHandler::instance(MultiPort::getDSN($this->testerToUse));
            if (!is_null($n->id)) {
                $res = $test_db->testGetNodes(['id_nodo_riferimento' => $n->id]);
            } else {
                $res = [];
            }

            if (!empty($res) && count($res) == 1 && !AMADB::isError($res)) {
                $node = array_shift($res);
                $this->nextTestNode = $node['id_nodo'];
            }

            /**
             * @author giorgio 06/nov/2013
             * must check if computed $this->_previousNode points to a test
             * and get last topic if it does.
             */
            if (!is_null($this->previousNode)) {
                $res = $test_db->testGetNodes(['id_nodo_riferimento' => $this->previousNode]);
            } else {
                $res = [];
            }

            if (!empty($res) && count($res) == 1 && !AMADB::isError($res)) {
                $node = array_shift($res);
                $test = NodeTest::readTest($node['id_nodo'], $test_db);
                $this->prevTestTopic = count($test->_children ?? []);
                $this->prevTestNode = $node['id_nodo'];
            }
            $test_db->disconnect();
        }
    }
    /**
     * Finds the node preceding $n in a depth first search
     * and sets $this->_previousNode
     *
     * @param Node $n
     */
    protected function findPreviousNode(Node $n, $userLevel = ADA_MAX_USER_LEVEL)
    {
        $dh = $GLOBALS['dh'];

        if ($n->parent_id == null || $n->parent_id == 'NULL') {
            $this->previousNode = null;
            return;
        }
        /*
         * Esiste fratello con ordine n-1?
         */
        if ($n->order >= 1) {
            $result = $dh->childExists($n->parent_id, $n->order - 1, $userLevel, '<=');
            if (!AMADataHandler::isError($result) && $result != null) {
                $found = false;
                $id = $result;
                while (!$found) {
                    $result = $dh->lastChildExists($id, $userLevel);
                    if (!AMADataHandler::isError($result)) {
                        if ($result != null) {
                            $id = $result;
                        } else {
                            $this->previousNode = $id;
                            $found = true;
                        }
                    }
                }
            } else {
                $this->previousNode = $n->parent_id;
            }
        } else {
            $this->previousNode = $n->parent_id;
        }
    }
    /**
     * Finds the node following $n in a depth first search
     * and sets $this->_nextNode
     *
     * @param Node $n
     */
    protected function findNextNode(Node $n, $userLevel = ADA_MAX_USER_LEVEL)
    {
        $dh = $GLOBALS['dh'];

        if ($n->type == ADA_GROUP_TYPE) {
            $result = $dh->childExists($n->id, 0, $userLevel, '>=');
            if (!AMADataHandler::isError($result) && $result !== false) {
                $this->nextNode = $result;
                return;
            }
        } else {
            $result = $dh->childExists($n->parent_id, $n->order + 1, $userLevel, '>=');
            if (!AMADataHandler::isError($result) && $result != null) {
                $this->nextNode = $result;
                return;
            }
        }
        $found = false;
        $id = $n->id;
        while (!$found) {
            $node_info = $dh->getNodeInfo($id);
            if (!AMADataHandler::isError($node_info)) {
                $parentId = $node_info['parent_id'];
                $order = $node_info['ordine'];

                if ($parentId == null || $parentId == 'NULL') {
                    return;
                }

                $result = $dh->childExists($parentId, $order + 1, $userLevel, '>=');
                if (!AMADataHandler::isError($result) && $result != null) {
                    $this->nextNode = $result;
                    $found = true;
                } else {
                    $id = $parentId;
                }
            }
        }
    }
    /**
     * Renders the navigation bar
     *
     * @param string|array $hrefText if what is null, must be an array of ['next','prev'] holding
     * the text to be used in the respective hrefs. if what is a string, than hrefText is the string
     * to be used as the hreftext
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement|null
     */
    public function render($hrefText = null)
    {
        $prevText = null;
        $nextText = null;

        if (isset($hrefText['prev']) && strlen($hrefText['prev']) > 0) {
            $prevText = $hrefText['prev'];
        }
        if (isset($hrefText['next']) && strlen($hrefText['next']) > 0) {
            $nextText = $hrefText['next'];
        }

        $prevLink = $this->renderPreviousNodeLink($prevText);
        $nextLink = $this->renderNextNodeLink($nextText);

        if (is_null($prevLink) && is_null($nextLink)) {
            $navigationBar = null;
        } else {
            $navigationBar = CDOMElement::create('div', 'class:dfsNavigationBar ui basic segment');
            if (!is_null($prevLink)) {
                $prevLink->setAttribute('id', 'prevNodeBtn');
                $prevLink->setAttribute('class', 'ui medium left floated red animated button');
                if (!$this->isPrevEnabled()) {
                    $prevLink->setAttribute('class', $prevLink->getAttribute('class') . ' disabled');
                    $prevLink->setAttribute('style', 'pointer-events: none;');
                }
                $iconDIV = CDOMElement::create('div', 'class:hidden content');
                $iconDIV->addChild(new CText('<i class="left arrow icon"></i>'));
                $prevLink->addChild($iconDIV);
                $navigationBar->addChild($prevLink);
            }
            if (!is_null($nextLink)) {
                $nextLink->setAttribute('id', 'nextNodeBtn');
                $nextLink->setAttribute('class', 'ui medium right floated teal animated button');
                if (!$this->isNextEnabled()) {
                    $nextLink->setAttribute('class', $nextLink->getAttribute('class') . ' disabled');
                    $nextLink->setAttribute('style', 'pointer-events: none;');
                }
                $iconDIV = CDOMElement::create('div', 'class:hidden content');
                $iconDIV->addChild(new CText('<i class="right arrow icon"></i>'));
                $nextLink->addChild($iconDIV);
                $navigationBar->addChild($nextLink);
            }
        }

        return $navigationBar;
    }

    /**
     * Renders the navigation bar
     *
     * @param string $what can be one of: <next,prev> to get only the specified link
     * if empty the whole navbar will be returned wrapped in a div with its class
     * @param string|array $hrefText if what is null, must be an array of ['next','prev'] holding
     * the text to be used in the respective hrefs. if what is a string, than hrefText is the string
     * to be used as the hreftext
     *
     * @return string
     */
    public function getHtml($what = '', $hrefText = null)
    {
        $retElement = null;
        if (preg_match('/^next$/i', $what) > 0) {
            $retElement = $this->renderNextNodeLink($hrefText);
        } elseif (preg_match('/^prev$/i', $what) > 0) {
            $retElement = $this->renderPreviousNodeLink($hrefText);
        } else {
            $retElement = $this->render($hrefText);
        }

        return (!is_null($retElement)) ? $retElement->getHtml() : '';
    }
    /**
     * Renders the link to the previous node
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement|null
     */
    protected function renderPreviousNodeLink($hrefText = null)
    {
        if (is_null($hrefText)) {
            $hrefText = translateFN('Precedente');
        } else {
            $hrefText = translateFN($hrefText);
        }

        $retElement = CDOMElement::create('a');
        $hrefTextElement = CDOMElement::create('div', 'class:visible content');
        $hrefTextElement->addChild(new CText($hrefText));
        $retElement->addChild($hrefTextElement);
        $href = null;

        if ($this->currentNode != null && $this->previousNode != null && $this->prevTestNode == null) {
            $href = HTTP_ROOT_DIR . '/browsing/' . $this->linkScriptForNode($this->previousNode)
                . '?id_node=' . $this->previousNode
                . (($this->currentNode != $this->previousNode) ? '&nextId=' . $this->currentNode : '');
        } elseif ($this->currentNode != null && $this->prevTestNode != null) {
            // @author giorgio 08/ott/2013, check if prev node points to a test node
            $href = MODULES_TEST_HTTP . '/index.php?id_test=' . $this->prevTestNode
                . (!is_null($this->prevTestTopic) ? '&topic='  . ($this->prevTestTopic - 1) : '');
        }

        if (!is_null($href)) {
            $retElement->setAttribute('href', $href);
            return $retElement;
        }
        return null;
    }
    /**
     * Renders the link to the next node
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement|null
     */
    protected function renderNextNodeLink($hrefText = null)
    {
        if (is_null($hrefText)) {
            $hrefText = translateFN('Successivo');
        } else {
            $hrefText = translateFN($hrefText);
        }

        $retElement = CDOMElement::create('a');
        $hrefTextElement = CDOMElement::create('div', 'class:visible content');
        $hrefTextElement->addChild(new CText($hrefText));
        $retElement->addChild($hrefTextElement);
        $href = null;

        if ($this->currentNode != null && $this->nextNode != null && $this->nextTestNode == null) {
            $href = HTTP_ROOT_DIR . '/browsing/' . $this->linkScriptForNode($this->nextNode)
                . '?id_node=' . $this->nextNode
                . (($this->currentNode != $this->nextNode) ? '&prevId=' . $this->currentNode : '');
        } elseif ($this->currentNode != null && $this->nextTestNode != null) {
            // @author giorgio 08/ott/2013, check if next node points to a test node
            $href = MODULES_TEST_HTTP . '/index.php?id_test=' . $this->nextTestNode
                . (!is_null($this->nextTestTopic) ? '&topic='  . ($this->nextTestTopic + 1) : '');
        }

        if (!is_null($href)) {
            $retElement->setAttribute('href', $href);
            return $retElement;
        }
        return null;
    }
    /**
     * Returns the parameters needed to invoke the navigation bar
     *
     * @return string
     */
    public function getNavigationBarParameters()
    {
        return 'id_node=' . $this->currentNode
            . '&prevId=' . $this->previousNode
            . '&nextId=' . $this->nextNode;
    }

    /**
     * tell the php script to be called for node_id, if it's view or exercise
     *
     * @param string $node_id
     *
     * @return string
     */
    private function linkScriptForNode($node_id)
    {
        $dh = $GLOBALS['dh'];

        $retLink = 'view.php';
        $nodeAr =  $dh->getNodeInfo($node_id);
        if (!AMADB::isError($nodeAr) && Node::isNodeExercise($nodeAr['type'])) {
            $retLink = 'exercise.php';
        }
        return $retLink;
    }

    /**
     * used when it's a test in the test module
     * see DFSTestNavigationBar derived class
     * into modules/test/include
     *
     * @var number
     */
    protected $topic = null;
    protected $nextTestTopic = null;
    protected $prevTestTopic = null;
    /**
     * used when it's a test in the test module
     * see DFSTestNavigationBar derived class
     * into modules/test/include
     * @var string
     */
    protected $nextTestNode = null;
    /**
     * used when it's a test in the test module
     * see DFSTestNavigationBar derived class
     * into modules/test/include
     * @var string
     */
    protected $prevTestNode = null;

    /**
     *
     * @var string
     */
    protected $currentNode = null;
    /**
     *
     * @var string
     */
    protected $previousNode = null;
    /**
     *
     * @var string
     */
    protected $nextNode = null;

    /**
     * tester to be used
     *
     * @var string
     */
    protected $testerToUse = null;

    /**
     * true if next button is enabled
     *
     * @var bool
     */
    protected $nextEnabled = true;

    /**
     * true if prev button is enabled
     *
     * @var bool
     */
    protected $prevEnabled = true;

    /**
     * Get true if next button is enabled
     */
    public function isNextEnabled(): bool
    {
        return $this->nextEnabled;
    }

    /**
     * Set true if next button is enabled
     */
    public function setNextEnabled(bool $nextEnabled): self
    {
        $this->nextEnabled = $nextEnabled;

        return $this;
    }

    /**
     * Get true if prev button is enabled
     */
    public function isPrevEnabled(): bool
    {
        return $this->prevEnabled;
    }

    /**
     * Set true if prev button is enabled
     */
    public function setPrevEnabled(bool $prevEnabled): self
    {
        $this->prevEnabled = $prevEnabled;

        return $this;
    }
}

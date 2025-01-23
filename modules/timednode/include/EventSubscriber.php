<?php

/**
 * @package     timednode module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Timednode;

use Lynxlab\ADA\Browsing\DFSNavigationBar;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements ADAScriptSubscriberInterface, EventSubscriberInterface
{
    /**
     * Node duration as set by the author, in seconds.
     *
     * @var int
     */
    protected $duration;

    /**
     * How much time the student spent in the node.
     *
     * @var int
     */
    protected $timeInNode;

    /**
     * The logged in user id
     *
     * @var int
     */
    protected $userId;

    /**
     * The courseInstance id.
     *
     * @var int
     */
    protected $instanceId;

    /**
     * true if prerender method has to be run.
     *
     * @var bool
     */
    protected $doPrerender;

    /**
     * The session node.
     *
     * @var \Lynxlab\ADA\Main\Node\Node
     */
    protected $node;

    public function __construct()
    {
        $this->setDuration(MODULES_TIMEDNODE_DEFAULTDURATION);
        $this->setDoPrerender(false);
    }

    /**
     * EventSubscriber must implements EventSubscriberInterface
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [];
    }


    /**
     * Gets events per script.
     *
     * @return array
     */
    public static function getSubscribedScripts()
    {
        return [
            'view.php' => [
                CoreEvent::POSTMODULEINIT => 'setClassProperties',
                CoreEvent::PAGEPRERENDER => 'viewPreRender',
            ],
        ];
    }

    public function setClassProperties(CoreEvent $event)
    {
        $args = $event->getArguments();
        $this->setDoPrerender(AMA_TYPE_STUDENT == $args['session']['sess_id_user_type'] ?? 0);
        if ($this->isDoPrerender()) {
            $this->setInstanceId($args['session']['sess_id_course_instance'] ?? 0)
                ->setUserId((int) $args['session']['sess_id_user'] ?? 0)
                ->setNode(new Node($args['session']['sess_id_node'] ?? 0));
            $magicWord = TimedNode::getMagicWord($this->getNode()->getKeywords());
            /*
             * WARNING!!!
             * Duration must be properly set by the
             * author to a string like '<magicword>=h:m:s'
             */
            $this->setDoPrerender(!empty($magicWord));
            if ($this->isDoPrerender()) {
                $timeArr = TimedNode::extractTime($magicWord);
                if (count($timeArr) == 3) {
                    $this->setDuration($timeArr[0] * 3600 + $timeArr[1] * 60 + $timeArr[2]);
                }
            }
        }
    }

    /**
     * Add proper javascript if a keyword containing $magicWord=int is found.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function viewPreRender(CoreEvent $event)
    {
        if ($this->isDoPrerender() && $this->getDuration() > 0) {
            $this->setTimeInNode(TimedNode::calcTimeSpentInNode(
                [
                    'id_user' => $this->getUserId(),
                    'id_course_instance' => $this->getInstanceId(),
                    'id_node' => $this->getNode()->id,
                ],
                ''
            ));
            $renderData = $event->getArguments();

            // remove magic words from keywords
            $newKeywords = array_filter(
                explode(',', $renderData['content_dataAr']['keywords'] ?? ''),
                fn ($keyword) => !TimedNode::hasMagicWord(strip_tags($keyword))
            );
            $renderData['content_dataAr']['keywords'] = implode(',', $newKeywords);

            // do the timed node only if must spend some more time in the node
            $timeLeft = $this->getDuration() - $this->getTimeInNode();

            // force the navigation bar to always be there passing ADA_MAX_USER_LEVEL
            $renderData['content_dataAr']['navigation_bar'] = (
            new DFSNavigationBar(
                $this->getNode(),
                [
                    'prevId' => $_GET['prevId'] ?? null,
                    'nextId' => $_GET['nextId'] ?? null,
                    'userLevel' => ADA_MAX_USER_LEVEL,
                ]
            ))->setNextEnabled($timeLeft <= 0)->getHtml();

            if ($timeLeft > 0) {
                $help = CDOMElement::create('div', 'id:node-duration, class:ui small message');
                $help->addChild(CDOMElement::create('i', 'class: ui time icon'));

                $helpTextCont = CDOMElement::create('div','id:help-text-container');

                $helpTextLbl = CDOMElement::create('span','id:node-time-label');
                $helpTextLbl->addChild(new CText(translateFN('Tempo di fruizione:').'&nbsp;'));
                $helpTextCont->addChild($helpTextLbl);

                $helpText = CDOMElement::create('span','id:node-time-left');
                $helpText->addChild(new CText(static::formatTimeInNode($this->getDuration())));
                $helpTextCont->addChild($helpText);

                $help->addChild($helpTextCont);

                $waitAnim = CDOMElement::create('div', 'class:lds-ellipsis');
                for ($w = 0; $w < 4; $w++) {
                    $waitAnim->addChild(CDOMElement::create('div'));
                }
                $help->addChild($waitAnim);

                $moduleJS = [
                    'content_dataAr' => [
                        'navigation_bar' => [
                            'initval' => '',
                            'additems' => fn ($v) => $help->getHtml() . $v,
                        ],
                    ],
                    'layout_dataAr' => [
                        'JS_filename' => [
                            'initval' => [],
                            'additems' => [
                                MODULES_TIMEDNODE_PATH . '/js/modules_define.js.php',
                                MODULES_TIMEDNODE_PATH . '/js/timedNodeManager.js',
                            ],
                        ],
                        'CSS_filename' => [
                            'initval' => [],
                            'additems' => [
                                MODULES_TIMEDNODE_PATH . '/css/timednode.css',
                            ],
                        ],
                    ],
                    'options' => [
                        'onload_func' => [
                            'initval' => '',
                            'additems' => fn ($v) => $v . 'new timedNodeManager(' .
                                htmlentities(json_encode([
                                    'duration' => $timeLeft,
                                    'userId' => $this->getUserId(),
                                    'instanceId' => $this->getInstanceId(),
                                ]), ENT_COMPAT, ADA_CHARSET)
                                . ');',
                        ],
                    ],
                ];
                /**
                 * modify render data
                 */
                $renderData = self::addRenderData($renderData, $moduleJS);
            }
            $event->setArguments($renderData);
        }
    }

    /**
     * Adds the passed data array to the render array
     *
     * @param array $renderData
     * @param array $addData
     *
     * @return array
     */
    private static function addRenderData($renderData, $addData = [])
    {
        foreach ($addData as $renderKey => $renderSubkeys) {
            foreach ($renderSubkeys as $renderSubKey => $renderVal) {
                if (!array_key_exists($renderSubKey, $renderData[$renderKey])) {
                    $renderData[$renderKey][$renderSubKey] = $renderVal['initval'];
                }
                $addItems = $renderVal['additems'];
                if (is_callable($addItems)) {
                    $renderData[$renderKey][$renderSubKey] = $addItems($renderData[$renderKey][$renderSubKey]);
                } elseif (is_array($renderData[$renderKey][$renderSubKey])) {
                    if (is_array($addItems)) {
                        $renderData[$renderKey][$renderSubKey] = array_merge($renderData[$renderKey][$renderSubKey], $addItems);
                    } else {
                        $renderData[$renderKey][$renderSubKey][] = $addItems;
                    }
                } else {
                    $renderData[$renderKey][$renderSubKey] = $addItems;
                }
            }
        }
        return $renderData;
    }

    /**
     * format the passed time as HH:mm:ss string
     *
     * @param integer $time
     *   time to be formatted in seconds
     * @return string
     *   the formatted string
     */
    public static function formatTimeInNode($time = 0): string
    {
        $int_hours = floor($time / 3600);
        $rest_sec = $time - ($int_hours * 3600);
        $int_mins = floor($rest_sec / 60);
        $int_secs = floor($time - ($int_hours * 3600) - ($int_mins * 60));
        return sprintf("%02d:%02d:%02d", $int_hours, $int_mins, $int_secs);
    }

    /**
     * Get the value of duration
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set the value of duration
     *
     * @return self
     */
    private function setDuration($duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get the session node
     *
     * @return \Lynxlab\ADA\Main\Node\Node
     */
    public function getNode(): Node
    {
        return $this->node;
    }

    /**
     * Set the session node
     *
     * @return self
     */
    public function setNode(Node $node): self
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get the value of timeInNode
     *
     * @return int
     */
    public function getTimeInNode()
    {
        return $this->timeInNode;
    }

    /**
     * Set the value of timeInNode
     *
     * @return self
     */
    public function setTimeInNode($timeInNode): self
    {
        $this->timeInNode = $timeInNode;

        return $this;
    }

    /**
     * Get doPrerender
     *
     * @return bool
     */
    public function isDoPrerender(): bool
    {
        return $this->doPrerender;
    }

    /**
     * Set doPrerender
     *
     * @return self
     */
    public function setDoPrerender(bool $doPrerender): self
    {
        $this->doPrerender = $doPrerender;

        return $this;
    }

    /**
     * Get the logged in user id
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the logged in user id
     *
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the courseInstance id
     *
     * @return int
     */
    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    /**
     * Set the courseInstance id
     *
     * @return self
     */
    public function setInstanceId(int $instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }
}

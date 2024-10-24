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
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements ADAScriptSubscriberInterface, EventSubscriberInterface
{
    /**
     * Magic words that if found in the keywords will trigger the timed node.
     *
     * @var array
     */
    private static $magicWords = [
        'durata',
        'duration',
        't',
        'time',
    ];

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
            $magicWord = array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        trim($this->getNode()->getKeywords() ?? '')
                    )
                ),
                fn ($el) => static::hasMagicWord($el)
            );
            /*
             * WARNING!!!
             * Duration must be properly set by the
             * author to a string like '<magicword>=h:m:s'
             */
            if (1 == count($magicWord)) {
                $magicWord = reset($magicWord);
                [$magicWord, $time] = explode('=', $magicWord);
                $timeArr = array_map('trim', explode(':', $time));
                if (count($timeArr) == 3) {
                    $this->setDuration($timeArr[0] * 3600 + $timeArr[1] * 60 + $timeArr[2]);
                }
            }
            $this->setTimeInNode(static::calcTimeSpentInNode($args['session']));
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
            $renderData = $event->getArguments();

            // remove magic words from keywords
            $newKeywords = array_filter(
                explode(',', $renderData['content_dataAr']['keywords'] ?? ''),
                fn ($keyword) => !static::hasMagicWord(strip_tags($keyword))
            );
            $renderData['content_dataAr']['keywords'] = implode(',', $newKeywords);

            // force the navigation bar to always be there passing ADA_MAX_USER_LEVEL
            $renderData['content_dataAr']['navigation_bar'] = (
            new DFSNavigationBar(
                $this->getNode(),
                [
                    'prevId' => $_GET['prevId'] ?? null,
                    'nextId' => $_GET['nextId'] ?? null,
                    'userLevel' => ADA_MAX_USER_LEVEL,
                ]
            ))->getHtml();

            // do the timed node only if must spend some more time in the node
            $timeLeft = $this->getDuration() - $this->getTimeInNode();
            if ($timeLeft > 0) {
                $moduleJS = [
                    'layout_dataAr' => [
                        'JS_filename' => [
                            'initval' => [],
                            'additems' => [
                                MODULES_TIMEDNODE_PATH . '/js/modules_define.js.php',
                                MODULES_TIMEDNODE_PATH . '/js/timedNodeManager.js',
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
     * calc the time a user has spent in a node
     *
     * @param array $data
     *   array used to get the time, must have keys (prefixed by $dataPrefix):
     *   - id_user
     *   - id_course_instance
     *   - id_node
     * @param string $dataPrefix
     *   prefix for the keys of the data array ('sess_' if using session)
     * @return integer
     *   time the user has spent in node, in seconds
     */
    public static function calcTimeSpentInNode($data = [], $dataPrefix = 'sess_'): int
    {
        /**
         * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
         */
        $dh = $GLOBALS['dh'];

        $history = array_filter(
            $dh?->getLastVisitedNodesInPeriod($data[$dataPrefix . 'id_user'], $data[$dataPrefix . 'id_course_instance'], 0) ?? [],
            fn ($el) => $el['id_nodo'] == $data[$dataPrefix . 'id_node'] ?? -1
        );
        return array_sum(array_map(fn ($el) => ($el['data_uscita'] ?? 0) - ($el['data_visita'] ?? 0), $history));
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
     * checks if a keyword is one of the magic words
     *
     * @param string $el
     *   the keyword to check
     * @return boolean
     *   true if it's a magic word
     */
    private static function hasMagicWord($el): bool
    {
        $el = str_replace(' ', '', $el);
        foreach (static::$magicWords as $magicWord) {
            if (str_starts_with($el, $magicWord . '=')) {
                return true;
            }
        }
        return false;
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

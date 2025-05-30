<?php

/**
 * @package     notifications module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Notifications;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\ForumEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAMethodSubscriberInterface;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;
use Lynxlab\ADA\Module\Notifications\AMANotificationsDataHandler;
use Lynxlab\ADA\Module\Notifications\EmailQueueItem;
use Lynxlab\ADA\Module\Notifications\Notification;
use Lynxlab\ADA\Module\Notifications\NotificationActions;
use Lynxlab\ADA\Module\Notifications\QueueManager;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements ADAMethodSubscriberInterface, ADAScriptSubscriberInterface, EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ForumEvent::INDEXACTIONINIT => 'addForumIndexActions',
            NodeEvent::POSTADDREDIRECT => 'postAddRedircet',
            NodeEvent::POSTEDITREDIRECT => 'postEditRedirect',
        ];
    }

    public static function getSubscribedMethods()
    {
        return [
            'AMATesterDataHandler::getNotesForThisCourseInstance' => [
                CoreEvent::AMAPDOPREGETALL => 'preGetNotes',
                CoreEvent::AMAPDOPOSTGETALL => 'postGetNotes',
            ],
        ];
    }

    public static function getSubscribedScripts()
    {
        return [
            'main_index.php' => [
                CoreEvent::PAGEPRERENDER => 'mainIndexPreRender',
            ],
            'view.php' => [
                CoreEvent::PAGEPRERENDER => 'viewPreRender',
            ],
        ];
    }

    /**
     * Add module's own query parts where needed
     *
     * @param CoreEvent $event
     * @return void
     */
    public function preGetNotes(CoreEvent $event)
    {
        $args = $event->getArguments();
        $queryParts = new PHPSQLParser($args['query']);
        $add = new PHPSQLParser('SELECT N.id_istanza');
        if (is_array($queryParts->parsed['SELECT']) && count($queryParts->parsed['SELECT']) > 0) {
            // set delimiter of the last parsed SELECT
            $queryParts->parsed['SELECT'][count($queryParts->parsed['SELECT']) - 1]['delim'] = ',';
        }
        // add own fields
        foreach ($add->parsed['SELECT'] as $v) {
            $queryParts->parsed['SELECT'][] = $v;
        }
        $query = new PHPSQLCreator($queryParts->parsed);
        $args['query'] = $query->created;
        $event->setArguments($args);
    }

    /**
     * Modify query results where needed
     *
     * @param CoreEvent $event
     * @return void
     */
    public function postGetNotes(CoreEvent $event)
    {
        $args = $event->getArguments();
        $values = $args['retval'];
        $id_instance = intval(reset($values)['id_istanza']);
        $ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        $result = $ntDH->findBy('Notification', [
            'userId' => $_SESSION['sess_userObj']->getId(),
            'instanceId' => $id_instance,
            'notificationType' => Notification::getNotificationFromNodeType(ADA_NOTE_TYPE),
        ]);
        $notificationNodes = [];
        foreach ($result as $notification) {
            $notificationNodes[$notification->getNodeId()] = [
                'notificationId' => $notification->getNotificationId(),
                'isActive' => $notification->getIsActive(),
            ];
        }
        $values = array_map(function ($el) use ($notificationNodes) {
            if (array_key_exists($el['id_nodo'], $notificationNodes)) {
                $el['hasNotifications'] = $notificationNodes[$el['id_nodo']]['isActive'];
                $el['notificationId'] = $notificationNodes[$el['id_nodo']]['notificationId'];
            }
            return $el;
        }, $values);
        $args['retval'] = $values;
        $event->setArguments($args);
    }

    /**
     * ForumEvent::INDEXACTIONDONE
     * Add the bell icon button to set or unset active notification flag on forum notes
     *
     * @param ForumEvent $event
     * @return void
     */
    public function addForumIndexActions(ForumEvent $event)
    {
        $container = $event->getSubject();
        $nodeData = $event->getArguments();
        if (array_key_exists('level', $nodeData['params']) && $nodeData['params']['level'] >= 1) {
            $container->addChild(
                self::buildNotificationButton(
                    $nodeData['params']['node'] + ['id_istanza' => $nodeData['external_params']['id_course_instance'] ?? null],
                    Notification::getNotificationFromNodeType(ADA_NOTE_TYPE)
                )
            );
        }
    }

    /**
     * Check, build and add the notification button in view.php navigation panel,
     * will fill the notification_subscribe template field
     *
     * @param CoreEvent $event
     * @return void
     */
    public function viewPreRender(CoreEvent $event)
    {
        /**
         * if these GLOBALS are not in view.php there must be a problem somewhere else, not here.
         */
        if (array_key_exists('nodeObj', $GLOBALS) && array_key_exists('userObj', $GLOBALS) && array_key_exists('courseInstanceObj', $GLOBALS)) {
            if (NotificationActions::canDo(NotificationActions::ADDNOTIFICATION, null, $GLOBALS['userObj']->getType())) {
                $renderData = $event->getArguments();

                $ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                $noteNotification = $ntDH->findOneBy('Notification', [
                    'userId' => $GLOBALS['userObj']->getId(),
                    'nodeId' => $GLOBALS['nodeObj']->id,
                    'instanceId' => $GLOBALS['courseInstanceObj']->getId(),
                    'notificationType' => Notification::getNotificationFromNodeType(ADA_NOTE_TYPE),
                    // 'isActive' => true,
                ]);

                $nodeData = (array) $GLOBALS['nodeObj'];
                $nodeData['id_nodo'] = $nodeData['id'];

                if ($noteNotification instanceof Notification) {
                    $nodeData['hasNotifications'] = $noteNotification->getIsActive();
                    $nodeData['notificationId'] = $noteNotification->getNotificationId();
                } else {
                    $nodeData['hasNotifications'] = false;
                }
                $noteContainer = CDOMElement::create('div', 'class:noteActions');
                $span = CDOMElement::create('span');
                $span->addChild(new CText(translateFN('Notifiche Note')));
                $button = self::buildNotificationButton([
                    'id_istanza' => $GLOBALS['courseInstanceObj']->getId(),
                ] + $nodeData, Notification::getNotificationFromNodeType(ADA_NOTE_TYPE));
                $noteContainer->addChild($span);
                $noteContainer->addChild($button);

                $nodeContainer = null;
                if (!in_array($nodeData['type'], [ADA_NOTE_TYPE])) {
                    $nodeNotification = $ntDH->findOneBy('Notification', [
                        'userId' => $GLOBALS['userObj']->getId(),
                        'nodeId' => $GLOBALS['nodeObj']->id,
                        'instanceId' => null,
                        'notificationType' => Notification::getNotificationFromNodeType(ADA_LEAF_TYPE),
                        // 'isActive' => true,
                    ]);
                    if ($nodeNotification instanceof Notification) {
                        $nodeData['hasNotifications'] = $nodeNotification->getIsActive();
                        $nodeData['notificationId'] = $nodeNotification->getNotificationId();
                    } else {
                        $nodeData['hasNotifications'] = false;
                        if (isset($nodeData['notificationId'])) {
                            unset($nodeData['notificationId']);
                        }
                    }
                    $nodeContainer = CDOMElement::create('div', 'class:nodeActions');
                    $span = CDOMElement::create('span');
                    $span->addChild(new CText(translateFN('Notifiche Contenuto')));
                    $button = self::buildNotificationButton($nodeData, Notification::getNotificationFromNodeType(ADA_LEAF_TYPE));
                    $nodeContainer->addChild($span);
                    $nodeContainer->addChild($button);
                }

                $moduleJS = [
                    'content_dataAr' => [
                        'note_notification_subscribe' => [
                            'initval' => '',
                            'additems' => $noteContainer?->getHtml(),
                        ],
                        'node_notification_subscribe' => [
                            'initval' => '',
                            'additems' => $nodeContainer?->getHtml(),
                        ],
                    ],
                    'layout_dataAr' => [
                        'JS_filename' => [
                            'initval' => [],
                            'additems' => [
                                MODULES_NOTIFICATIONS_PATH . '/js/modules_define.js.php',
                                MODULES_NOTIFICATIONS_PATH . '/js/notificationsManager.js',
                            ],
                        ],
                        'CSS_filename' => [
                            'initval' => [],
                            'additems' => [
                                MODULES_NOTIFICATIONS_PATH . '/layout/' . $_SESSION['sess_template_family'] . '/css/view.css',
                                MODULES_NOTIFICATIONS_PATH . '/layout/' . $_SESSION['sess_template_family'] . '/css/showHideDiv.css',
                            ],
                        ],
                    ],
                    'options' => [
                        'onload_func' => [
                            'initval' => '',
                            'additems' => fn ($v) => $v .
                                '; new NotificationsManager().addSubscribeHandler(\'.noteActions\',\'button.noteSubscribe\').addSubscribeHandler(\'.nodeActions\',\'button.nodeSubscribe\');',
                        ],
                    ],
                ];
                /**
                 * modify render data
                 */
                $renderData = self::addRenderData($renderData, $moduleJS);
                $event->setArguments($renderData);
            }
        }
    }

    /**
     * Add this module's own javascript where needed
     *
     * @param CoreEvent $event
     * @return void
     */
    public function mainIndexPreRender(CoreEvent $event)
    {
        $renderData = $event->getArguments();
        $moduleJS = [
            'layout_dataAr' => [
                'JS_filename' => [
                    'initval' => [],
                    'additems' => [
                        JQUERY_UI,
                        MODULES_NOTIFICATIONS_PATH . '/js/modules_define.js.php',
                        MODULES_NOTIFICATIONS_PATH . '/js/notificationsManager.js',
                    ],
                ],
                'CSS_filename' => [
                    'initval' => [],
                    'additems' => [
                        MODULES_NOTIFICATIONS_PATH . '/layout/' . $_SESSION['sess_template_family'] . '/css/main_index.css',
                        MODULES_NOTIFICATIONS_PATH . '/layout/' . $_SESSION['sess_template_family'] . '/css/showHideDiv.css',
                    ],
                ],
            ],
            'options' => [
                'onload_func' => [
                    'initval' => '',
                    'additems' => fn ($v) => $v . '; new NotificationsManager().addSubscribeHandler(\'.noteActions\',\'button.noteSubscribe\');',
                ],
            ],
        ];
        /**
         * modify render data
         */
        $renderData = self::addRenderData($renderData, $moduleJS);
        $event->setArguments($renderData);
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
     * Closes the browser connection, then enqueues the forum note notification and runs the queue
     *
     * @param \Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent $event
     *
     * @return void
     */
    public function postAddRedircet(NodeEvent $event)
    {
        self::closeBrowserConnection();
        // populate the email queue
        $this->enqueueForumNote($event, true);
        // run the queuemanager on the emailqueue, using a dayly log file (true param)
        (new QueueManager(EmailQueueItem::fqcn()))->run(true);
    }

    /**
     * Closes the browser connection, then enqueues the course ndoe notification and runs the queue
     *
     * @param \Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent $event
     *
     * @return void
     */
    public function postEditRedirect(NodeEvent $event)
    {
        self::closeBrowserConnection();
        // populate the email queue
        $this->enqueueCourseNode($event, true);
        // run the queuemanager on the emailqueue, using a dayly log file (true param)
        (new QueueManager(EmailQueueItem::fqcn()))->run(true);
    }

    /**
     * Enques a notification email for each student subscribed to the course
     *
     * NOTE: As of 2025 Mar 24, course notification preferences are not managed yet!
     *
     * @param \Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent $event
     * @param boolean $isNewNode
     *
     * @return void
     */
    public function enqueueCourseNode(NodeEvent $event, $isNewNode = true)
    {
        $nodeData = $event->getSubject();
        $nodeId = $nodeData['id'] ?? '';
        $courseId =  substr($nodeId, 0, strpos($nodeId, '_'));
        $studentsList = [];

        if ($courseId && $nodeId && $isNewNode) {
            if (in_array($nodeData['type'], [ADA_LEAF_TYPE, ADA_GROUP_TYPE])) {
                $ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                $notificationType = Notification::getNotificationFromNodeType($nodeData['type']);

                if ($ntDH->courseHasInstances($courseId)) {
                    $fieldListAr = [];
                    $instances = $ntDH->courseInstanceStartedGetList($fieldListAr, $courseId);
                    if (!AMADB::isError($instances)) {
                        $instances = array_map(fn ($el) => $el[0], $instances);
                        foreach ($instances as $i) {
                            $sList = $ntDH->getStudentsForCourseInstance($i);
                            if (!AMADB::isError($sList)) {
                                $studentsList[$i] = array_filter(
                                    $sList,
                                    fn ($el) => $el['status'] == ADA_STATUS_SUBSCRIBED
                                );

                                if (!empty($studentsList[$i])) {
                                    // load users notification preferences for the course node content
                                    $notifyUserList = $ntDH->findBy('Notification', [
                                        'userId' => [
                                            'op' => 'IN',
                                            'value' => sprintf("(%s)", implode(', ', array_map(fn ($el) => $el['id_utente'], $studentsList[$i]))),
                                        ],
                                        'nodeId' => $nodeId,
                                        'instanceId' => null,
                                        'notificationType' => $notificationType,
                                        'isActive' => true,
                                    ]);
                                    if (!AMADB::isError($notifyUserList) && !empty($notifyUserList)) {
                                        $this->buildAndEnqueueNotifications(
                                            array_map(fn (Notification $el) => $el->setInstanceId($i), $notifyUserList),
                                            $studentsList[$i],
                                            ['id_instance' => $i] + $nodeData,
                                            EmailQueueItem::EDITCOURSENODE
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Enques a notification email for each student and tutor subscribed to note instance
     * and having set their notification preferences to receive emails
     *
     * @param \Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent $event
     * @param boolean $isNewNode
     *
     * @return void
     */
    public function enqueueForumNote(NodeEvent $event, $isNewNode = true)
    {
        $nodeData = $event->getSubject();
        if ($isNewNode) {
            if (in_array($nodeData['type'], [ADA_NOTE_TYPE])) {
                $instanceId = array_key_exists('id_instance', $nodeData) ? $nodeData['id_instance'] : $_SESSION['sess_id_course_instance'];
                $instanceSubscribedList = [];
                $notifyUserList = [];
                $ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                // load all students and tutors of the course instance
                $students =  $ntDH->getStudentsForCourseInstance($instanceId);
                if (!AMADB::isError($students)) {
                    $instanceSubscribedList = array_merge($instanceSubscribedList, array_map(fn ($el) => [
                        'id_utente' => intval($el['id_utente']),
                        'nome' => $el['nome'],
                        'cognome' => $el['cognome'],
                        'e_mail' => $el['e_mail'],
                        'type' => AMA_TYPE_STUDENT,
                    ], $students));
                }

                $tutors = $ntDH->courseInstanceTutorInfoGet($instanceId);
                if (!AMADB::isError($tutors)) {
                    $instanceSubscribedList = array_merge($instanceSubscribedList, array_map(fn ($el) => [
                        'id_utente' => intval($el['id_utente_tutor']),
                        'nome' => $el['nome'],
                        'cognome' => $el['cognome'],
                        'e_mail' => $el['e_mail'],
                        'type' => AMA_TYPE_TUTOR,
                    ], $tutors));
                }

                if (count($instanceSubscribedList) > 0) {
                    // load users notification preferences for the forum notes
                    $notifyUserList = $ntDH->findBy('Notification', [
                        'userId' => [
                            'op' => 'IN',
                            'value' => sprintf("(%s)", implode(', ', array_map(fn ($el) => $el['id_utente'], $instanceSubscribedList))),
                        ],
                        'nodeId' => $nodeData['parent_id'],
                        'instanceId' => $instanceId,
                        'notificationType' => Notification::getNotificationFromNodeType($nodeData['type']),
                        'isActive' => true,
                    ]);
                    $this->buildAndEnqueueNotifications($notifyUserList, $instanceSubscribedList, $nodeData, EmailQueueItem::NEWFORUMNOTE);
                }
            }
        }
    }

    /**
     * builds and enqueues notifications on the emailqueue
     *
     * @param array $notifyUserList
     *   Array of \Lynxlab\ADA\Module\Notifications\Notification objects
     * @param array $recipientsList
     *   Array of recipients (i.e. users array as returned by AMATesterDataHandler::getStudentsForCourseInstance )
     * @param array $nodeData
     *   Node object as an array
     * @param string $emailType
     *   One of the EmailQueueItem constants, (i.e. NEWFORUMNOTE or EDITCOURSENODE...)
     * @return void
     */
    private function buildAndEnqueueNotifications($notifyUserList, $recipientsList, $nodeData, $emailType)
    {
        if (is_array($notifyUserList) && count($notifyUserList) > 0) {
            $ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
            $instanceId = array_key_exists('id_instance', $nodeData) ? $nodeData['id_instance'] : $_SESSION['sess_id_course_instance'];
            $qItem = new EmailQueueItem();
            $qItem->setEmailType($emailType);
            // prepare data for the emailqueue: course, course instance, layout objects
            $instanceObj = new CourseInstance($instanceId);
            $courseObj = new Course($instanceObj->getCourseId());
            $layoutObj = Notification::getLayoutObj(EmailQueueItem::getEmailConfigFromType($qItem->getEmailType())['template']);
            $qItem->setSubject(
                trim(
                    sprintf(
                        "[%s] %s %s",
                        PORTAL_NAME,
                        translateFN(EmailQueueItem::getEmailConfigFromType($qItem->getEmailType())['subject']),
                        $courseObj->getTitle()
                    )
                )
            );
            $qItem->setStatus(EmailQueueItem::STATUS_ENQUEUED);

            $saveData = [];
            // foreach notifyUserList, build an EmailQueueItem with rendered template fields
            foreach ($notifyUserList as $notifyUser) {
                $userData = array_filter($recipientsList, fn ($el) => $notifyUser->getUserId() == $el['id_utente'] && strlen($el['e_mail']) > 0);
                if (is_array($userData) && count($userData) > 0) {
                    $userData = reset($userData);
                    $qItem->setUserId($userData['id_utente']);
                    $qItem->setRecipientEmail($userData['e_mail']);
                    $qItem->setRecipientFullName($userData['nome'] . ' ' . $userData['cognome']);
                    $qItem->setBody(
                        trim(
                            Notification::HTMLFromTPL(
                                EmailQueueItem::getEmailConfigFromType($qItem->getEmailType())['template'],
                                [
                                    'userFirstName' => $userData['nome'],
                                    'userLastName' => $userData['cognome'],
                                    'courseTitle' => $courseObj->getTitle(),
                                    'instanceTitle' => $instanceObj->getTitle(),
                                    'nodeName' => $nodeData['name'],
                                    'nodeContent' => $nodeData['text'],
                                    'indexHref' => sprintf(
                                        "%s/browsing/main_index.php?op=forum&id_course=%d&id_course_instance=%d#%s",
                                        HTTP_ROOT_DIR,
                                        $courseObj->getId(),
                                        $instanceObj->getId(),
                                        $nodeData['id']
                                    ),
                                    'replyHref' => sprintf(
                                        "%s/services/addnode.php?id_parent=%s&id_course=%d&id_course_instance=%d&type=NOTE",
                                        HTTP_ROOT_DIR,
                                        $nodeData['id'],
                                        $courseObj->getId(),
                                        $instanceObj->getId()
                                    ),
                                    'nodeHref' => sprintf(
                                        "%s/browsing/view.php?id_node=%s&id_course=%d&id_course_instance=%d",
                                        HTTP_ROOT_DIR,
                                        $nodeData['id'],
                                        $courseObj->getId(),
                                        $instanceObj->getId()
                                    ),
                                ],
                                MODULES_NOTIFICATIONS_PATH,
                                $layoutObj
                            )
                        )
                    );
                    $qItem->setEnqueueTS(time());
                    $saveData[] = $qItem->toArray();
                }
            }
            // add entries to the queue table
            if (!empty($saveData)) {
                $result = $ntDH->multiSaveEmailQueueItems($saveData);
            }
        }
    }

    /**
     * Does header and buffer stuff to close the connection to the browser
     *
     * @return void
     */
    private static function closeBrowserConnection()
    {
        session_write_close();
        // buffer the output, close the connection with the browser and run a "background" task
        ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true);
        // capture output
        ob_start();
        // flush all output
        ob_end_flush();
        flush();
        @ob_end_clean();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Adds a build a notification button
     *
     * @param array $nodeData
     * @param int $notificationType
     *
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    private static function buildNotificationButton($nodeData, $notificationType)
    {
        if ($notificationType == Notification::TYPES[ADA_NOTE_TYPE]) {
            $cssClass = 'noteSubscribe';
        } elseif (in_array($notificationType, [Notification::TYPES[ADA_LEAF_TYPE], Notification::TYPES[ADA_GROUP_TYPE]])) {
            $cssClass = 'nodeSubscribe';
        }
        $button = CDOMElement::create('button', 'class:ui tiny icon button ' . ($cssClass ?? ''));
        $title = [
            'red' => translateFN('Non ricevi notifiche; clicca per attivarle'),
            'green' => translateFN('Ricevi notifiche; clicca per disattivarle'),
        ];
        $color = 'red';
        $isActive = false;
        if (array_key_exists('hasNotifications', $nodeData) && $nodeData['hasNotifications']) {
            $color = 'green';
            $isActive = true;
        }
        $button->addChild(CDOMElement::create('i', 'class:bell outline icon'));
        if (array_key_exists('notificationId', $nodeData)) {
            $button->setAttribute('data-notification-id', $nodeData['notificationId']);
        }
        foreach ($title as $k => $v) {
            $button->setAttribute('data-title-' . $k, $v);
        }
        $button->setAttribute('class', $button->getAttribute('class') . ' ' . $color);
        $button->setAttribute('title', $title[$color]);
        $button->setAttribute('data-node-id', $nodeData['id_nodo']);
        if (array_key_exists('id_istanza', $nodeData) && !empty($nodeData['id_istanza'])) {
            $button->setAttribute('data-instance-id', $nodeData['id_istanza']);
        }
        $button->setAttribute('data-is-active', (int)$isActive);
        $button->setAttribute('data-notification-type', $notificationType);
        return $button;
    }
}

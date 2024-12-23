<?php

/**
 * @package     maxtries module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\MaxTries;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Service\ServiceImplementor;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAScriptSubscriberInterface;
use Lynxlab\ADA\Module\Test\ModuleTestEvent;
use Lynxlab\ADA\Module\Test\TestTest;
use Lynxlab\ADA\Switcher\Subscription;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EventSubscriber Class, defines node events names and handlers for this module.
 *
 * NOTE: This subscriber is added only if MODULES_MAXTRIES_COUNT is > 0
 * in config/config.inc.php file of this module!
 */
class EventSubscriber implements ADAScriptSubscriberInterface, EventSubscriberInterface
{
    /**
     * The logged in user.
     *
     * @var \Lynxlab\ADA\Main\User\ADAUser
     */
    protected $user;

    /**
     * The courseInstance id.
     *
     * @var int
     */
    protected $instanceId;

    /**
     * Session selected provider.
     *
     * @var string
     */
    protected $provider;

    /**
     * Test repeatable status.
     *
     * @var bool
     */
    protected $repeatable;

    /**
     * User points in the test.
     *
     * @var int
     */
    protected $points;

    /**
     * Test min barrier points.
     *
     * @var int
     */
    protected $minBarrierPoints;

    /**
     * EventSubscriber must implements ADAScriptSubscriberInterface.
     *
     * @return array
     */
    public static function getSubscribedScripts()
    {
        if (defined('MODULES_MAXTRIES_COUNT') && MODULES_MAXTRIES_COUNT > 0) {
            return [
                MODULES_TEST_PATH . '/index.php' => [
                    ModuleTestEvent::POSTSAVETEST => 'postSaveTest',
                    ModuleTestEvent::POSTRENDERENDTEST => 'postRenderEndTest',
                ],
                'user.php' => [
                    CoreEvent::PAGEPRERENDER => 'userPreRender',
                ],
            ];
        }
        return [];
    }

    /**
     * EventSubscriber must implements EventSubscriberInterface.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvent::POSTMODULEINIT => 'setClassProperties',
        ];
    }

    /**
     * Set the class properties.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function setClassProperties(CoreEvent $event)
    {
        $args = $event->getArguments();
        if ($args['session']['sess_userObj'] instanceof ADAUser) {
            $this->setUser($args['session']['sess_userObj'] ?? null)
                ->setInstanceId($args['session']['sess_id_course_instance'] ?? 0)
                ->setProvider($args['session']['sess_selected_tester'] ?? null);
        }
    }

    /**
     * Add a label to the user page to show the number of tries.
     *
     * @param CoreEvent $event
     * @return void
     */
    public function userPreRender(CoreEvent $event)
    {
        if (defined('MODULES_MAXTRIES_COUNT') && MODULES_MAXTRIES_COUNT > 0) {
            if (in_array($this->getUser()->getType(), [AMA_TYPE_STUDENT])) {
                $args = $event->getArguments();
                /*
                 * user.php has not yet set the session client, course and instance id,
                 * try to get them from the $_GET request or user history
                 * and then get the provider from the course.
                 */
                $courseId = null;
                $instanceId = null;
                if (isset($_GET['id_course'])) {
                    $courseId = (int)$_GET['id_course'];
                } elseif (isset($this->getUser()->history->id_course)) {
                    $courseId = (int)$this->getUser()->history->id_course;
                }
                if (isset($_GET['id_course_instance'])) {
                    $instanceId = (int)$_GET['id_course_instance'];
                } elseif (isset($this->getUser()->history->id_instance)) {
                    $instanceId = (int)$this->getUser()->history->id_course_instance;
                }
                if ($courseId && $instanceId) {
                    $serviceImplementationObj = ServiceImplementor::findImplementor($courseId);
                    if (!empty($serviceImplementationObj->getProviderPointer())) {
                        $dh = AMAMaxTriesDataHandler::instance(
                            MultiPort::getDSN($serviceImplementationObj->getProviderPointer())
                        );
                        $tries = $dh->getTriesCount($this->getUser()->getId(), $instanceId);
                        if (++$tries <= (int)MODULES_MAXTRIES_COUNT) {
                            $class = '';
                            $icon = 'ok';
                            $msg = sprintf(translateFN("Tentativo %d di %d"), $tries, MODULES_MAXTRIES_COUNT);
                        } else {
                            $class = ' unsatisfied';
                            $icon = 'ban';
                            $msg = translateFN("Tentativi esauriti");
                        }
                        $args['content_dataAr']['completeSummary'] =
                            '<span class="maxtries label item condition' . $class . '" ><i class="' .
                            $icon . ' circle icon" ></i>' . $msg . '</span>' .
                            $args['content_dataAr']['completeSummary'] ?? '';
                    }
                }
                $event->setArguments($args);
            }
        }
    }

    /**
     * Check if the test has been passed or not and update the subscription status.
     *
     * @param ModuleTestEvent $event
     * @return void
     */
    public function postRenderEndTest(ModuleTestEvent $event)
    {
        $retargs = [];
        if (defined('MODULES_MAXTRIES_COUNT') && MODULES_MAXTRIES_COUNT > 0) {
            if ($event->getSubject() == TestTest::class) {
                if (in_array($this->getUser()->getType(), [AMA_TYPE_STUDENT])) {
                    if ($this->isRepeatable() && $this->getMinBarrierPoints() > 0) {
                        $dh = AMAMaxTriesDataHandler::instance(
                            MultiPort::getDSN($this->getProvider())
                        );
                        $tries = $dh->getTriesCount($this->getUser()->getId(), $this->getInstanceId());
                        if ($this->getPoints() < $this->getMinBarrierPoints()) {
                            // test non superato.
                            $s = array_filter(
                                Subscription::findSubscriptionsToClassRoom($this->getInstanceId()),
                                fn($s) => $this->getUser()->getId() == $s->getSubscriberId()
                            );
                            /** @var Subscription $s */
                            $s = reset($s);
                            if (++$tries < (int)MODULES_MAXTRIES_COUNT) {
                                // test non superato e numero tentativi non superato.
                                $dh->backupUserLog($this->getUser()->getId(), $this->getInstanceId(), $tries);
                                $courseInstanceObj = new CourseInstance($this->getInstanceId());
                                $s->setStartStudentLevel($courseInstanceObj->start_level_student);
                                $s->setSubscriptionStatus($s->getSubscriptionStatus());
                            } else {
                                // test non superato e numero tentativi superato.
                                $s->setSubscriptionStatus(ADA_STATUS_TERMINATED);
                            }
                            $dh->updateTriesCount($this->getUser()->getId(), $this->getInstanceId(), $tries);
                            Subscription::updateSubscription($s);
                            $retargs['statusbox'] = self::buildStatusBox($tries);
                        }
                    }
                }
            }
        }
        $event->setArguments($retargs);
    }

    /**
     * Build the status box.
     *
     * @param int $tries
     * @param int $maxtries
     * @return CDOMElement
     */
    private static function buildStatusBox($tries, $maxtries = MODULES_MAXTRIES_COUNT)
    {
        $header = translateFN('Non hai superato il questionario di apprendimento');
        $message = [
            'warning' => [
                translateFN("In base alla normativa devi ricominciare il corso dall'inizio."),
                sprintf(translateFN("Questo è il tentativo %d di %d"), $tries, $maxtries),
            ],
            'error' => [
                translateFN("Hai esaurito i tentativi, in base alla normativa non puoi più ripetere il corso."),
            ],
        ];
        $what = $tries < $maxtries ? 'warning' : 'error';
        $box = CDOMElement::create('div', 'style: margin-top:1em,class:ui message ' . $what);
        $headerDIV = CDOMElement::create('div', 'class:header');
        $headerDIV->addChild(new CText($header));
        $box->addChild($headerDIV);
        $box->addChild(new CText(implode('<br/>', $message[$what])));
        return $box;
    }

    /**
     * Set the class properties after save test.
     *
     * @param ModuleTestEvent $event
     * @return void
     */
    public function postSaveTest(ModuleTestEvent $event)
    {
        if (defined('MODULES_MAXTRIES_COUNT') && MODULES_MAXTRIES_COUNT > 0) {
            if ($event->getSubject() == TestTest::class) {
                if (in_array($this->getUser()->getType(), [AMA_TYPE_STUDENT])) {
                    $args = $event->getArguments();
                    $this->setRepeatable(((int)$args['repeatable'] ?? 0) > 0)->setMinBarrierPoints((int)$args['min_barrier_points'] ?? 0)
                        ->setPoints((int)$args['points'] ?? 0);
                }
            }
        }
    }

    /**
     * Adds the passed data array to the render array.
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
     * Get the logged in user.
     */
    public function getUser(): ?ADAUser
    {
        return $this->user;
    }

    /**
     * Set the logged in user.
     */
    public function setUser(?ADAUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the courseInstance id.
     */
    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    /**
     * Set the courseInstance id.
     */
    public function setInstanceId(int $instanceId): self
    {
        $this->instanceId = $instanceId;

        return $this;
    }

    /**
     * Get session selected provider.
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Set session selected provider.
     */
    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get test repeatable status.
     */
    public function isRepeatable(): bool
    {
        return $this->repeatable;
    }

    /**
     * Set test repeatable status.
     */
    public function setRepeatable(bool $repeatable): self
    {
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * Get user points in the test.
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * Set user points in the test.
     */
    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    /**
     * Get test min barrier points.
     */
    public function getMinBarrierPoints(): int
    {
        return $this->minBarrierPoints;
    }

    /**
     * Set test min barrier points.
     */
    public function setMinBarrierPoints(int $minBarrierPoints): self
    {
        $this->minBarrierPoints = $minBarrierPoints;

        return $this;
    }
}

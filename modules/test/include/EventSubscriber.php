<?php

/**
 * @package
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent;
use Soundasleep\Html2Text;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class for module test
 *
 * @return array
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * Return the events to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $events = [
            NodeEvent::POSTSAVE => 'onPostSaveNode',
        ];
        if (defined('ADA_SURVEY_TO_CSV') && ADA_SURVEY_TO_CSV) {
            $events = array_merge($events, [
                ModuleTestEvent::PRESAVEANSWER => 'onPreSaveAnswer',
                ModuleTestEvent::POSTSAVEANSWER => 'onPostSaveAnswer',
            ]);
        }
        return $events;
    }

    /**
     * Update the test node level when saving a personal exercise node
     *
     * @param NodeEvent $event
     * @return void
     */
    public function onPostSaveNode(NodeEvent $event)
    {
        $args = $event->getArguments();
        $nodeArr = $event->getSubject();
        if ($args['saveResult'] && str_starts_with($nodeArr['type'] ?? null, (string) constant('ADA_PERSONAL_EXERCISE_TYPE'))) {
            $testNode = null;
            $test_db = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
            $res = $test_db->testGetNodes(['id_nodo_riferimento' => $nodeArr['id']]);
            if (!empty($res) && count($res) == 1 && !AMADataHandler::isError($res)) {
                $testNode = array_shift($res);
            }
            if (!empty($testNode)) {
                /*
                 * If the node is a test node, set the testNode level to the node level
                 * For some resaon the level is in the durata field
                 */
                $testNode['durata'] = $nodeArr['level'];
                $test_db->testUpdateNode($testNode['id_nodo'], $testNode);
            }
        }
    }

    /**
     * Set the answer_data field to an empty string when saving a SurveyTest
     *
     * @param ModuleTestEvent $event
     * @return void
     */
    public function onPreSaveAnswer(ModuleTestEvent $event)
    {
        if ($event->getSubject() == SurveyTest::class) {
            $args = $event->getArguments();
            if (array_key_exists('answer_data', $args)) {
                $args['answer_data'] = ['answer' => ''];
            }
            $event->setArguments($args);
        }
    }

    /**
     * Save the answer of the SurveyTest to a CSV file
     *
     * @param ModuleTestEvent $event
     * @return void
     */
    public function onPostSaveAnswer(ModuleTestEvent $event)
    {
        if ($event->getSubject() == SurveyTest::class) {
            /**
             * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
             */
            $dh = $GLOBALS['dh'];
            $arguments = $event->getArguments();
            $tutorIds = $dh->courseInstanceTutorGet($arguments['idCourseInstance'], 'ALL');
            if ($tutorIds !== false && !AMADB::isError($tutorIds)) {
                $fields = [
                    $arguments[NodeTest::POST_TOPIC_VAR]['nome'],
                    Html2Text::convert(strip_tags($arguments[NodeTest::POST_TOPIC_VAR]['consegna'])),
                    $arguments[NodeTest::POST_ANSWER_VAR][0]['nome'] ?? '',
                    $arguments[NodeTest::POST_ANSWER_VAR][0]['points'] ?? 0,
                    Html2Text::convert($arguments[NodeTest::POST_ANSWER_VAR][0][NodeTest::POST_EXTRA_VAR] ?? ''),
                ];
                foreach ($tutorIds as $tutorId) {
                    $fileInfo = SurveyTest::buildCSVFileInfo(
                        $tutorId,
                        $arguments['idCourse'],
                        $arguments['idCourseInstance'],
                        $arguments['survey']['nome'],
                        false,
                        false
                    );
                    if ($fileInfo['filemtime'] == 0) {
                        $filePath = pathinfo($fileInfo['fileName'], PATHINFO_DIRNAME);
                        if (!is_dir($filePath)) {
                            mkdir($filePath, 0755, true);
                        }
                    }
                    $fp = fopen($fileInfo['fileName'], 'a');
                    if ($fp) {
                        fputcsv($fp, $fields);
                    }
                    fclose($fp);
                }
            }
        }
    }
}

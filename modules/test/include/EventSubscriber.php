<?php

/**
 * @package
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\AMA\AMADB;
use Soundasleep\Html2Text;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class for module test
 */
class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ModuleTestEvent::PRESAVEANSWER => 'onPreSaveAnswer',
            ModuleTestEvent::POSTSAVEANSWER => 'onPostSaveAnswer',
        ];
    }

    public function onPreSaveAnswer(ModuleTestEvent $event)
    {
        $args = $event->getArguments();
        if (array_key_exists('answer_data', $args)) {
            $args['answer_data'] = ['answer' => ''];
        }
        $event->setArguments($args);
    }

    public function onPostSaveAnswer(ModuleTestEvent $event)
    {
        if ($event->getSubject() == SurveyTest::class) {
            /**
             * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
             */
            $dh = $GLOBALS['dh'];
            $arguments = $event->getArguments();
            $fileName = (new Convert(
                $arguments['idCourse'] . ' ' .
                $arguments['idCourseInstance'] . ' ' .
                $arguments['survey']['nome']
            )
            )->toKebab();
            $tutorIds = $dh->courseInstanceTutorGet($arguments['idCourseInstance'], 'ALL');
            if (!AMADB::isError($tutorIds)) {
                $fields = [
                    $arguments[NodeTest::POST_TOPIC_VAR]['nome'],
                    Html2Text::convert(strip_tags($arguments[NodeTest::POST_TOPIC_VAR]['consegna'])),
                    $arguments[NodeTest::POST_ANSWER_VAR][0]['nome'] ?? '',
                    $arguments[NodeTest::POST_ANSWER_VAR][0]['points'] ?? 0,
                    Html2Text::convert($arguments[NodeTest::POST_ANSWER_VAR][0][NodeTest::POST_EXTRA_VAR] ?? ''),
                ];
                foreach ($tutorIds as $tutorId) {
                    $filePath = ROOT_DIR . MEDIA_PATH_DEFAULT . $tutorId . DIRECTORY_SEPARATOR . 'csv-surveys';
                    if (!is_dir($filePath)) {
                        mkdir($filePath, 0755);
                    }
                    $fp = fopen($filePath . DIRECTORY_SEPARATOR . $fileName . '.csv', 'a');
                    if ($fp) {
                        fputcsv($fp, $fields);
                    }
                    fclose($fp);
                }
            }
        }
    }
}
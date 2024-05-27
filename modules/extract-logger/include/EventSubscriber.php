<?php

/**
 * EXTRACT-LOGGER MODULE.
 *
 * @package        extract-logger module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           oauth2
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\ExtractLogger;

use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            CoreEvent::HELPERINITEXTRACT => 'extractLogger',
        ];
    }

    public function extractLogger(CoreEvent $event)
    {
        $arguments = $event->getArguments();
        (AMAExtractloggerDataHandler::instance())->logData($arguments['script'], $arguments['class'], $arguments['data']);
        $event->setArguments($arguments);
    }
}

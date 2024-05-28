<?php

/**
 * @package     studentsgroups module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\StudentsGroups;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ActionsEvent::LIST_INSTANCES => 'addListInstancesActions',
        ];
    }

    public function addListInstancesActions(ActionsEvent $e)
    {
        [$courseId, $instanceId] = array_values($e->getSubject());
        $actionsArr = $e->getArgument('actionsArr');

        if (ModuleLoaderHelper::isLoaded('STUDENTSGROUPS')) {
            $subscribeGroup_img = CDOMElement::create('img', 'class:subscribe-group-icon,src:img/add_instances.png,alt:' . translateFN('Iscrivi gruppo'));
            $subscribeGroup_link = BaseHtmlLib::link('javascript:void(0)', $subscribeGroup_img);
            $subscribeGroup_link->setAttribute('class', 'subscribe-group');
            $subscribeGroup_link->setAttribute('data-courseid', $courseId);
            $subscribeGroup_link->setAttribute('data-instanceid', $instanceId);
            $subscribeGroup_link->setAttribute('title', translateFN('Iscrivi gruppo'));
            /**
             * insert subscribeGroup link before deletelink
             */
            array_splice($actionsArr, count($actionsArr) - 1, 0, [$subscribeGroup_link]);
        }
        // set argument to be returned
        $e->setArgument('actionsArr', $actionsArr);
    }
}

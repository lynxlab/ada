<?php

/**
 * CLASSBUDGET MODULE.
 *
 * @package        classbudget module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2015, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classbudget
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\MenuEvent;
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
            MenuEvent::PRERENDER => 'addMenuItems',
        ];
    }

    public function addListInstancesActions(ActionsEvent $e)
    {
        $actionsArr = $e->getArgument('actionsArr');

        [$courseId, $instanceId] = array_values($e->getSubject());

        if (ModuleLoaderHelper::isLoaded('MODULES_CLASSBUDGET')) {
            $budgetImg = CDOMElement::create('img', 'alt:' . translateFN('budget') . ',title:' . translateFN('budget'));
            $budgetImg->setAttribute('src', MODULES_CLASSBUDGET_HTTP . '/layout/' .
                ($GLOBALS['template_family'] ?? ADA_TEMPLATE_FAMILY) . '/img/budget_icon.png');
            $budget_link = BaseHtmlLib::link(MODULES_CLASSBUDGET_HTTP . "/index.php?id_course=$courseId&id_course_instance=$instanceId", $budgetImg->getHtml());
            /**
             * insert budget link before deletelink
             */
            array_splice($actionsArr, 1, 0, [$budget_link]);
        }
        // set argument to be returned
        $e->setArgument('actionsArr', $actionsArr);
    }

    public function addMenuItems(MenuEvent $event)
    {
        if (false !== stristr(realpath($_SERVER['SCRIPT_FILENAME']), MODULES_CLASSBUDGET_PATH)) {
            $baseItem = [
                'label' => '',
                'extraHTML' => null,
                'icon' => '',
                'icon_size' => null,
                'href_properties' => null,
                'href_prefix' => '%MODULES_CLASSBUDGET_HTTP%',
                'href_path' => null,
                'href_paramlist' => 'id_course, id_course_instance',
                'extraClass' => null,
                'groupRight' => '0',
                'specialItem' => '0',
                'order' => '0',
                'enabledON' => '%MODULES_CLASSBUDGET%',
                'menuExtraClass' => '',
                'children' => null,
            ];
            $menu = $event->getSubject();
            $left = $menu->getLeftItemsArray();

            $exportPDF = array_merge($baseItem, [
                'label' => 'File PDF',
                'icon' => 'file outline',
                'href_properties' => json_encode(['target' => '_blank']),
                'href_path' => 'index.php?export=pdf',
            ]);
            $exportCSV = array_merge($baseItem, [
                'label' => 'File CSV',
                'icon' => 'file',
                'href_properties' => json_encode(['target' => '_blank']),
                'href_path' => 'index.php?export=csv',
            ]);

            $item = array_merge($baseItem, [
                'label' => 'esporta',
                'icon' => 'basic export',
                'icon_size' => 'large',
                'order' => 0,
                'children' => [$exportPDF, $exportCSV],
            ]);
            // // Insert item at 2nd position, i.e. after Home
            array_splice($left, 1, 0, [$item]);
            $menu->setLeftItemsArray($left);
        }
    }
}

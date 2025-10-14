<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package        classagenda module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2014, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classagenda
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Classagenda;

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
            MenuEvent::PRERENDER => 'addMenuItems',
        ];
    }

    public function addMenuItems(MenuEvent $event)
    {
        $menu = $event->getSubject();
        $left = $menu->getLeftItemsArray() ?? [];
        $baseItem = [
            'label' => '',
            'extraHTML' => null,
            'icon' => '',
            'icon_size' => null,
            'href_properties' => null,
            'href_prefix' => '%MODULES_CLASSAGENDA_HTTP%',
            'href_path' => null,
            'href_paramlist' => null,
            'extraClass' => null,
            'groupRight' => '0',
            'specialItem' => '0',
            'order' => '0',
            'enabledON' => '%MODULES_CLASSAGENDA%',
            'menuExtraClass' => '',
            'children' => null,
        ];

        if (!empty($left)) {
            /**
             * add calendars.php menu item if not already found in the DB.
             */
            $item = array_filter($left, fn ($el) => $el['label'] === strtolower(translateFN('strumenti')));
            if (!empty($item)) {
                $itemKey = key($item);
                $calitem = array_filter($left[$itemKey]['children'] ?? [], fn ($el) => $el['href_path'] == 'calendars.php');
                if (empty($calitem)) {
                    $left[$itemKey]['children'][] = array_merge($baseItem, [
                        'label' => 'Calendario corsi',
                        'icon' => 'calendar outline',
                        'href_path' => 'calendars.php',
                        'order' => 30,
                    ]);
                    uasort($left[$itemKey]['children'], fn ($a, $b) => $a['order'] - $b['order']);
                } else {
                    foreach (array_keys($calitem) as $key) {
                        if (!isset($_SESSION['sess_selected_tester'])) {
                            unset($left[$itemKey]['children'][$key]);
                        }
                    }
                }
            }

            if (false !== stristr(realpath($_SERVER['SCRIPT_FILENAME']), MODULES_CLASSAGENDA_PATH . '/calendars.php')) {
                if (empty(array_filter($left, fn ($el) => $el['label'] === strtolower(translateFN('esporta'))))) {
                    /**
                     * add export menu if not already found in the DB.
                     */
                    $exportPDF = array_merge($baseItem, [
                        'label' => 'File PDF',
                        'icon' => 'file outline',
                        'href_properties' => json_encode(['data-type' => 'pdf']),
                        'extraClass' => 'calendarexportmenuitem',
                        'href_path' => 'exportCalendar.php?type=pdf',
                    ]);
                    $exportCSV = array_merge($baseItem, [
                        'label' => 'File CSV',
                        'icon' => 'file',
                        'href_properties' => json_encode(['data-type' => 'csv']),
                        'extraClass' => 'calendarexportmenuitem',
                        'href_path' => 'exportCalendar.php?type=csv',
                    ]);

                    $item = array_merge($baseItem, [
                        'label' => 'esporta',
                        'icon' => 'basic export',
                        'icon_size' => 'large',
                        'order' => 90,
                        'children' => [$exportPDF, $exportCSV],
                    ]);
                    // // Insert item at 2nd position, i.e. after Home
                    array_splice($left, 1, 0, [$item]);
                }
            }
        }
        $menu->setLeftItemsArray($left);
    }
}

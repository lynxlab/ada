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

use DateTimeImmutable;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\Classroom\AMAClassroomDataHandler;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\MenuEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAMethodSubscriberInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements EventSubscriberInterface, ADAMethodSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ActionsEvent::LIST_TUTOR_COURSES => 'addListTutorCoursesActions',
            MenuEvent::PRERENDER => 'addMenuItems',
        ];
    }

    public static function getSubscribedMethods()
    {
        $subscriptions = [];
        if (ModuleLoaderHelper::isLoaded('CLASSROOM')) {
            $subscriptions[AMAClassroomDataHandler::class . '::classroomDeleteClassroom'] = [
                CoreEvent::PREPREPAREANDEXECUTE => 'setNullClassroom',
            ];
        }
        return $subscriptions;
    }

    /**
     * set events classroomid to null when the
     * classroom module is deleting a classroom
     *
     * @param CoreEvent $event
     * @return CoreEvent
     */
    public function setNullClassroom(CoreEvent $event)
    {
        if (str_starts_with(strtoupper($event->getArgument('sql')->queryString), 'DELETE')) {
            $values = $event->getArgument('values') ?? [];
            if (!empty($values)) {
                $classroomID = array_shift($values);
                if ($classroomID > 0) {
                    $events = $this->getDH()->getClassRoomEventsForClassroom($classroomID);
                    $instanceIDs = array_unique(array_column($events, 'id_istanza_corso'));
                    $dh = $this->getDH();
                    /**
                     * double foreach to get all events with same instanceID and venueID
                     */
                    foreach ($instanceIDs as $instanceID) {
                        $venueIDs = array_unique(
                            array_column(
                                array_filter($events, fn ($el) => $el['id_istanza_corso'] == $instanceID),
                                'id_venue'
                            )
                        );
                        foreach ($venueIDs as $venueID) {
                            $eventsData = array_map(
                                fn ($el) => [
                                    'id' => $el[AMAClassagendaDataHandler::$PREFIX . 'calendars_id'],
                                    'start' => (new DateTimeImmutable())->setTimestamp($el['start'])->format('Y-m-d\TH:i:s'),
                                    'end' => (new DateTimeImmutable())->setTimestamp($el['end'])->format('Y-m-d\TH:i:s'),
                                    'cancelled' => ($el['cancelled']) != null ?
                                        (DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $el['cancelled']))->format('Y-m-d\TH:i:s')
                                        : null,
                                    'classroomID' => null,
                                    'tutorID' => $el['id_utente_tutor'],
                                ],
                                array_values(
                                    array_filter($events, fn ($el) => $el['id_istanza_corso'] == $instanceID && $el['id_venue'] == $venueID)
                                )
                            );
                            if (!empty($eventsData)) {
                                foreach ($eventsData as $anEvent) {
                                    $dh->saveClassroomEvent($instanceID, $anEvent);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $event;
    }

    private function getDH(): AMAClassagendaDataHandler
    {
        return AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
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

    public function addListTutorCoursesActions(ActionsEvent $event)
    {
        $actionsArr = $event->getArgument('actionsArr');

        [$courseId, $instanceId] = array_values($event->getSubject());

        if (ModuleLoaderHelper::isLoaded('CLASSAGENDA')) {
            $presenzeImg = CDOMElement::create('img', 'alt:' . translateFN('presenze') . ',class:tooltip, title:' . translateFN('presenze'));
            $presenzeImg->setAttribute('src', HTTP_ROOT_DIR . '/layout/' .
                ($GLOBALS['template_family'] ?? ADA_TEMPLATE_FAMILY) . '/img/badge.png');
            $presenzeLink = BaseHtmlLib::link(
                MODULES_CLASSAGENDA_HTTP . '/rollcall.php?id_course=' . $courseId . '&id_course_instance=' . $instanceId,
                $presenzeImg->getHtml()
            );
            $registroImg = CDOMElement::create('img', 'alt:' . translateFN('registro') . ',class:tooltip, title:' . translateFN('registro'));
            $registroImg->setAttribute('src', HTTP_ROOT_DIR . '/layout/' .
                ($GLOBALS['template_family'] ?? ADA_TEMPLATE_FAMILY) . '/img/registro.png');
            $registroLink = BaseHtmlLib::link(
                MODULES_CLASSAGENDA_HTTP . '/rollcallhistory.php?id_course=' . $courseId . '&id_course_instance=' . $instanceId,
                $registroImg->getHtml()
            );
            /**
             * insert links after videochat report link
             */
            array_splice($actionsArr, 3, 0, [$presenzeLink, $registroLink]);
        }
        // set argument to be returned
        $event->setArgument('actionsArr', $actionsArr);
        return $event;
    }
}

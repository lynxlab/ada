<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();  // = admin!

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
SwitcherHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */

//$courseId = DataValidator::isUinteger($_GET['course']);
//if($courseId !== false && $courseId > 0) {


if ($courseObj instanceof Course && $courseObj->isFull()) {
    $courseId = $courseObj->getId();
    $course_title = $courseObj->getTitle();



    $fieldsAr = ['data_inizio', 'data_inizio_previsto', 'durata', 'data_fine', 'title'];
    $instancesAr = $dh->courseInstanceGetList($fieldsAr, $courseId);
    if (is_array($instancesAr) && count($instancesAr) > 0) {
        $thead_data = [
            translateFN('id'),
            translateFN('classe'),
            translateFN('data inizio previsto'),
            translateFN('durata'),
            translateFN('data inizio'),
            translateFN('data fine'),
            translateFN('tutor'),
            translateFN('iscritti'),
            translateFN('azioni'),
        ];
        $tbody_data = [];

        $edit_img = CDOMElement::create('img', 'src:img/edit.png,alt:edit');
        $delete_img = CDOMElement::create('img', 'src:img/trash.png,alt:' . translateFN('Delete instance'));
        //$view_img = CDOMElement::create('img', 'src:img/zoom.png,alt:view');
        if (ModuleLoaderHelper::isLoaded('STUDENTSGROUPS')) {
            $subscribeGroup_img = CDOMElement::create('img', 'class:subscribe-group-icon,src:img/add_instances.png,alt:' . translateFN('Iscrivi gruppo'));
        }

        foreach ($instancesAr as $instance) {
            $instanceId = $instance[0];

            /*
             * Da migliorare, spostare l'ottenimento dei dati necessari in un'unica query
             * per ogni istanza corso (qualcosa che vada a sostituire courseInstanceGetList solo in questo caso.
             */
            $tutorId = $dh->courseInstanceTutorGet($instanceId);
            if (!AMADataHandler::isError($tutorId) && $tutorId !== false) {
                $tutor_infoAr = $dh->getTutor($tutorId);
                if (!AMADataHandler::isError($tutor_infoAr)) {
                    $tutorFullName = $tutor_infoAr['nome'] . ' ' . $tutor_infoAr['cognome'];
                } else {
                    $tutorFullName = translateFN('Utente non trovato');
                }
            } else {
                $tutorFullName = translateFN('Nessun tutor');
            }

            $edit_link = BaseHtmlLib::link("edit_instance.php?id_course=$courseId&id_course_instance=$instanceId", $edit_img->getHtml());
            $edit_link->setAttribute('title', translateFN('Modifica istanza'));
            //  $view_link = BaseHtmlLib::link("view_instance.php?id=$instanceId", $view_img->getHtml());
            $delete_link = BaseHtmlLib::link("delete_instance.php?id_course=$courseId&id_course_instance=$instanceId", $delete_img->getHtml());
            $delete_link->setAttribute('title', translateFN('Cancella istanza'));
            $actionsArr = [
                $edit_link,
                // $view_link,
                $delete_link,
            ];
            if (ModuleLoaderHelper::isLoaded('STUDENTSGROUPS')) {
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

            if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
                $event = ADAEventDispatcher::buildEventAndDispatch(
                    [
                        'eventClass' => ActionsEvent::class,
                        'eventName' => ActionsEvent::LIST_INSTANCES,
                    ],
                    ['id_course' => $courseId, 'id_course_instance' => $instanceId],
                    ['actionsArr' => $actionsArr]
                );
                try {
                    $actionsArr = $event->getArgument('actionsArr');
                } catch (InvalidArgumentException) {
                    // do nothing
                }
            }

            $actions = BaseHtmlLib::plainListElement('class:actions inline_menu', $actionsArr);

            if ($instance[1] > 0) {
                $start_date = AMADataHandler::tsToDate($instance[1]);
            } else {
                $start_date = translateFN('Non iniziato');
            }
            $duration = sprintf("%d giorni", $instance[3]);
            $scheduled = AMADataHandler::tsToDate($instance[2]);
            $end_date =  AMADataHandler::tsToDate($instance[4]);
            $title = $instance[5];

            $assign_tutor_link = BaseHtmlLib::link("assign_tutor.php?id_course=$courseId&id_course_instance=$instanceId", $tutorFullName);
            $subscriptions_link = BaseHtmlLib::link(
                "course_instance.php?id_course=$courseId&id_course_instance=$instanceId",
                translateFN('Lista studenti')
            );
            $tbody_data[] = [
                $instanceId,
                $title,
                $scheduled,
                $duration,
                $start_date,
                $end_date,
                $assign_tutor_link,
                $subscriptions_link,
                $actions,
            ];
        }
        $data = BaseHtmlLib::tableElement('id:list_instances, class:' . ADA_SEMANTICUI_TABLECLASS, $thead_data, $tbody_data);
    } else {
        $data = new CText(translateFN('Non sono state trovate istanze per il corso selezionato'));
    }
} else {
    $data = new CText(translateFN('Non sono state trovate istanze per il corso selezionato'));
}


$label = translateFN('Lista istanze del corso') . ' ' . $course_title;
$help = translateFN('Da qui il provider admin può vedere la lista delle istanze del corso selezionato');

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_NO_CONFLICT,
];

$dataForJS = [
    'datatables' => ['list_instances'],
];

if (ModuleLoaderHelper::isLoaded('STUDENTSGROUPS')) {
    $layout_dataAr['JS_filename'][] = MODULES_STUDENTSGROUPS_PATH . '/js/instanceSubscribe.js';
    $layout_dataAr['CSS_filename'][] = MODULES_STUDENTSGROUPS_PATH . '/layout/ada_blu/css/showHideDiv.css';
    $dataForJS['loadModuleJS'] = [
        [
            'baseUrl' => MODULES_STUDENTSGROUPS_HTTP,
            'className' => 'studentsgroupsAPI.GroupInstanceSubscribe',
        ],
    ];
}


$optionsAr = ['onload_func' => 'initDoc(' . htmlentities(json_encode($dataForJS), ENT_COMPAT, ADA_CHARSET) . ');'];

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'edit_profile' => $userObj->getEditProfilePage(),
    'data' => $data->getHtml(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

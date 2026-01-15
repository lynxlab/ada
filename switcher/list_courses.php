<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
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
    AMA_TYPE_SWITCHER => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();

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

$coursesAr = $dh->getCoursesList(['nome', 'titolo', 'descrizione', 'tipo_servizio']);
if (is_array($coursesAr) && count($coursesAr) > 0) {
    $thead_data = [
        null,
        translateFN('id'),
        translateFN('codice'),
        translateFN('tipo'),
        translateFN('titolo'),
        translateFN('descrizione'),
        translateFN('azioni'),
    ];
    $tbody_data = [];

    $edit_img = CDOMElement::create('img', 'src:img/edit.png,alt:edit');
    $view_img = CDOMElement::create('img', 'src:img/zoom.png,alt:view');
    $instances_img = CDOMElement::create('img', 'src:img/instances.png,alt:view');
    $add_instance_img = CDOMElement::create('img', 'src:img/add_instances.png,alt:view');
    $survey_img = CDOMElement::create('img', 'src:img/_exer.png,alt:view');
    $delete_img = CDOMElement::create('img', 'src:img/trash.png,alt:view');
    if (ModuleLoaderHelper::isLoaded('BADGES')) {
        $coursebadges_img = CDOMElement::create('img', 'src:' . MODULES_BADGES_HTTP . '/layout/' . $_SESSION['sess_template_family'] . '/img/course-badges.png');
    }
    if (ModuleLoaderHelper::isLoaded('IMPEXPORT') && defined('MODULES_IMPEXPORT_REPODIR') && strlen(MODULES_IMPEXPORT_REPODIR) > 0) {
        $exporttorepo_img = CDOMElement::create('img', 'src:' . MODULES_IMPEXPORT_HTTP . '/layout/' . $_SESSION['sess_template_family'] . '/img/export-to-repo.png');
    }

    foreach ($coursesAr as $course) {
        $isPublicCourse = isset($_SESSION['service_level_info'][$course['tipo_servizio']]['isPublic']) &&
            ($_SESSION['service_level_info'][$course['tipo_servizio']]['isPublic'] != 0);
        $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/details_open.png');
        $imgDetails->setAttribute('class', 'imgDetls tooltip');
        $imgDetails->setAttribute('title', translateFN('visualizza/nasconde la descrizione del corso'));

        $courseId = $course[0];
        $actions = [];
        $edit_link = BaseHtmlLib::link("edit_course.php?id_course=$courseId", $edit_img->getHtml());

        if (isset($edit_link)) {
            $title = translateFN('Clicca per modificare il corso');
            $div_edit = CDOMElement::create('div');
            $div_edit->setAttribute('title', $title);
            $div_edit->setAttribute('class', 'tooltip');
            $div_edit->addChild(($edit_link));
            $actions[] = $div_edit;
        }

        $view_link = BaseHtmlLib::link("view_course.php?id_course=$courseId", $view_img->getHtml());

        if (isset($view_link)) {
            $title = translateFN('Clicca per visualizzare il corso');
            $div_view = CDOMElement::create('div');
            $div_view->setAttribute('title', $title);
            $div_view->setAttribute('class', 'tooltip');
            $div_view->addChild(($view_link));
            $actions[] = $div_view;
        }

        if (!$isPublicCourse) {
            $instances_link = BaseHtmlLib::link("list_instances.php?id_course=$courseId", $instances_img->getHtml());
        }

        if (isset($instances_link)) {
            $title = translateFN('Gestione classi');
            $div_instances = CDOMElement::create('div');
            $div_instances->setAttribute('title', $title);
            $div_instances->setAttribute('class', 'tooltip');
            $div_instances->addChild(($instances_link));
            $actions[] = $div_instances;
        }

        if (!$isPublicCourse && MODULES_TEST) {
            $survey_link = BaseHtmlLib::link(MODULES_TEST_HTTP . '/switcher.php?id_course=' . $courseId, $survey_img->getHtml());
            $title = translateFN('Sondaggi');
            $div_survey = CDOMElement::create('div');
            $div_survey->setAttribute('title', $title);
            $div_survey->setAttribute('class', 'tooltip');
            $div_survey->addChild(($survey_link));
            $actions[] = $div_survey;
        }

        if (!$isPublicCourse) {
            if (ModuleLoaderHelper::isLoaded('BADGES')) {
                $badges_link = BaseHtmlLib::link(MODULES_BADGES_HTTP . '/course-badges.php?id_course=' . $courseId, $coursebadges_img->getHtml());
                $title = translateFN('Badges');
                $div_badges = CDOMElement::create('div');
                $div_badges->setAttribute('title', ucfirst($title));
                $div_badges->setAttribute('class', 'tooltip');
                $div_badges->addChild(($badges_link));
                $actions[] = $div_badges;
            }
            if (ModuleLoaderHelper::isLoaded('IMPEXPORT') && defined('MODULES_IMPEXPORT_REPODIR') && strlen(MODULES_IMPEXPORT_REPODIR) > 0) {
                $extorepo_link = BaseHtmlLib::link(MODULES_IMPEXPORT_HTTP . '/export.php?exporttorepo=1&id_course=' . $courseId, $exporttorepo_img->getHtml());
                $title = translateFN('Esporta nel repository');
                $div_extorepo = CDOMElement::create('div');
                $div_extorepo->setAttribute('title', ucfirst($title));
                $div_extorepo->setAttribute('class', 'tooltip');
                $div_extorepo->addChild(($extorepo_link));
                $actions[] = $div_extorepo;
            }
        }

        if (!$isPublicCourse) {
            $add_instance_link = BaseHtmlLib::link("add_instance.php?id_course=$courseId", $add_instance_img->getHtml());
        }

        if (isset($add_instance_link)) {
            $title = translateFN('Aggiungi classe');
            $div_AddInstances = CDOMElement::create('div');
            $div_AddInstances->setAttribute('title', $title);
            $div_AddInstances->setAttribute('class', 'tooltip');
            $div_AddInstances->addChild(($add_instance_link));
            $actions[] = $div_AddInstances;
        }

        $delete_course_link = BaseHtmlLib::link("delete_course.php?id_course=$courseId", $delete_img->getHtml());

        if (isset($delete_course_link)) {
            $title = translateFN('Cancella corso');
            $div_delete = CDOMElement::create('div');
            $div_delete->setAttribute('title', $title);
            $div_delete->setAttribute('class', 'tooltip');
            $div_delete->addChild(($delete_course_link));
            $actions[] = $div_delete;
        }

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => ActionsEvent::class,
                    'eventName' => ActionsEvent::LIST_COURSES,
                ],
                ['id_course' => $courseId],
                ['actionsArr' => $actions]
            );
            try {
                $actions = $event->getArgument('actionsArr');
            } catch (InvalidArgumentException) {
                // do nothing
            }
        }

        $actions = BaseHtmlLib::plainListElement('class:inline_menu', $actions);
        $servicelevel = null;
        /* if isset $_SESSION['service_level'] it means that the istallation supports course type */
        if (isset($_SESSION['service_level'][$course[4]])) {
            $servicelevel = $_SESSION['service_level'][$course[4]];
        }
        if (!isset($servicelevel)) {
            $servicelevel = DEFAULT_SERVICE_TYPE_NAME;
        }


        $tbody_data[] = [$imgDetails, $courseId, $course[1], translateFN($servicelevel),  $course[2], $course[3], $actions];
    }
    $data = BaseHtmlLib::tableElement('id:table_list_courses', $thead_data, $tbody_data);
    $data->setAttribute('class', $data->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
} else {
    $data = new CText(translateFN('Non sono stati trovati corsi'));
}

$filter = null;
if (isset($_GET['filter']) && isset($_SESSION['service_level'])) {
    $filter = $_SESSION['service_level'][$_GET['filter']];
    $label = translateFN('Lista corsi di tipo "') . $filter . '"';
} else {
    $label = translateFN('Lista corsi');
}

$help = translateFN('Da qui il provider admin può vedere la lista dei corsi presenti sul provider');
$Li_edit_home_page = "";

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module ?? '',
    'edit_profile' => $userObj->getEditProfilePage(),
    'messages' => $user_messages->getHtml(),
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];

$render = null;
$filter = "'" . $filter . "'";
$options['onload_func'] = 'initDoc(' . $filter . ')';

ARE::render($layout_dataAr, $content_dataAr, $render, $options);

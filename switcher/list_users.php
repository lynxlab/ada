<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\DataValidator;
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

/**
 * enable server side datatable only if ENCRYPT module is not loaded
 * because filtering and ordering will not work with encrypted data
 */
$serverSide = !ModuleLoaderHelper::isLoaded('ENCRYPT');

$usersType = DataValidator::checkInputValues('list', 'Value', INPUT_GET, 'students');
$fieldsAr = ['nome', 'cognome', 'username', 'tipo', 'stato'];
switch ($usersType) {
    case 'authors':
        $profilelist = translateFN('lista degli autori');
        $amaUserType = AMA_TYPE_AUTHOR;
        break;
    case 'tutors':
        $profilelist = translateFN('lista dei tutors');
        $amaUserType = AMA_TYPE_TUTOR;
        break;
    case 'students':
    default:
        $profilelist = translateFN('lista degli studenti');
        $amaUserType = AMA_TYPE_STUDENT;
        break;
}

$thead_data = [
    null,
    translateFN('id'),
    translateFN('nome'),
    translateFN('cognome'),
    translateFN('username'),
    translateFN('azioni'),
    translateFN('Confermato'),
];

if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
    $event = ADAEventDispatcher::buildEventAndDispatch(
        [
            'eventClass' => ActionsEvent::class,
            'eventName' => ActionsEvent::LIST_USERS,
        ],
        ['userType' => $amaUserType],
        ['thead' => $thead_data]
    );
    try {
        $thead_data = $event->getArgument('thead');
    } catch (InvalidArgumentException) {
        // do nothing
    }
}

if (!$serverSide) {
    $usersCount = 0;
    $tbody_data = require_once('ajax/listUsers.php');
    if (is_array($tbody_data) && count($tbody_data) > 0) {
        $usersCount = count($tbody_data);
    } else {
        $data = CDOMElement::create('span');
        $data->addChild(new CText(translateFN('Non sono stati trovati utenti')));
    }
} else {
    $tbody_data = [];
}

$data = BaseHtmlLib::tableElement('id:table_users', $thead_data, $tbody_data);
$data->setAttribute('class', $data->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);

$label = $profilelist;
if (!$serverSide && $usersCount > 0) {
    $helpSpan = CDOMElement::create('span');
    $helpSpan->addChild(new CText(ucfirst(translateFN($profilelist . ' presenti nel provider') . ': ')));
    $helpSpan->addChild(new CText($usersCount ?? 0));
} else {
    $helpSpan = null;
}

/**
 * @author steve 28/mag/2020
 *
 * adding link to Tutor Subscrition from file
 */
if ($usersType == 'tutors') {
    /**
     * @author steve 28/mag/2020
     *
     * adding link to Tutor Subscrition from file
     */
    $buttonSubscriptions = CDOMElement::create('button', 'class:Subscription_Button');
    $buttonSubscriptions->setAttribute('onclick', 'javascript:goToSubscription(\'tutor_subscriptions\');');
    $buttonSubscriptions->addChild(new CText(translateFN('Carica da file') . '...'));
    $helpSpan = CDOMElement::create('span');
    $helpSpan->addChild($buttonSubscriptions);
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $helpSpan?->getHtml(),
    'data' => $data->getHtml(),
    'edit_profile' => $userObj->getEditProfilePage(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    ROOT_DIR . '/js/include/jquery/dataTables/selectSortPlugin.js',
    JQUERY_NO_CONFLICT,
];


$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];
$render = null;
$optionsAr['onload_func'] = 'initDoc(\'' . $usersType . '\', ' . ($serverSide ? 'true' : 'false') . ');';
if (ModuleLoaderHelper::isLoaded('IMPERSONATE')) {
    $layout_dataAr['JS_filename'][] = MODULES_IMPERSONATE_PATH . '/js/impersonateAPI.js';
    $layout_dataAr['CSS_filename'][] = MODULES_IMPERSONATE_PATH . '/layout/css/showHideDiv.css';
}
ARE::render($layout_dataAr, $content_dataAr, $render, $optionsAr);

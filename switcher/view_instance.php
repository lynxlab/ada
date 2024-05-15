<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

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
    AMA_TYPE_SWITCHER => ['layout', 'course_instance'],
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
if ($courseInstanceObj instanceof CourseInstance && $courseInstanceObj->isFull()) {
    if ($courseInstanceObj->getStartDate() == '') {
        $start_date = translateFN('Non iniziata');
    } else {
        $start_date = $courseInstanceObj->getStartDate();
    }

    $listData = [
        'id istanza' => $courseInstanceObj->getId(),
        'data inizio' => $start_date,
        'data inizio previsto' => $courseInstanceObj->getScheduledStartDate(),
        //'layout' => $courseInstanceObj->getLayoutId(),
        'durata' => sprintf('%d giorni', $courseInstanceObj->getDuration()),
        'data fine' => $courseInstanceObj->getEndDate(),
    ];
    $data = BaseHtmlLib::labeledListElement('class:view_info', $listData);
} else {
    $data = new CText(translateFN('Classe non trovata'));
}

$label = translateFN("Visualizzazione dei dati dell'istanza corso");
$help = translateFN('Da qui il provider admin può visualizzare i dati di una istanza corso esistente');

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'edit_profile' => $userObj->getEditProfilePage(),
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module,
    'messages' => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

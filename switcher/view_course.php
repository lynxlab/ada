<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

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
$self = whoami();

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
SwitcherHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
    $data = new CText(translateFN('Corso non trovato'));
} else {
    $authorObj = MultiPort::findUser($courseObj->getAuthorId());
    $language_info = Translator::getLanguageInfoForLanguageId($courseObj->getLanguageId());

    $formData = [
        'id corso' => $courseObj->getId(),
        'autore' => $authorObj->getFullName(),
        'lingua' => $language_info['nome_lingua'],
        //'id_layout' => $courseObj->getLayoutId(),
        'codice corso' => $courseObj->getCode(),
        'titolo' => $courseObj->getTitle(),
        'descrizione' => $courseObj->getDescription(),
        'id nodo iniziale' => $courseObj->getRootNodeId(),
        'id nodo toc' => $courseObj->getTableOfContentsNodeId(),
        'media path' => $courseObj->getMediaPath(),
        //'static mode' => $courseObj->getStaticMode(),
        'data di creazione' => $courseObj->getCreationDate(),
        'data di pubblicazione' => $courseObj->getPublicationDate(),
        'crediti' => $courseObj->getCredits(),
    ];

    if (defined('MODULES_SERVICECOMPLETE') && MODULES_SERVICECOMPLETE) {
        $cdh = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        $conditionset = $cdh->getLinkedConditionsetForCourse($courseObj->getId());
        $formData['condizione di completamento'] = ($conditionset instanceof CompleteConditionSet) ? $conditionset->description : translateFN('Nessuna');
    }

    if (defined('MODULES_BADGES') && MODULES_BADGES) {
        $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
        $badges = $bdh->findBy('CourseBadge', ['id_corso' => $courseObj->getId()]);
        if (!\AMADB::isError($badges) && is_array($badges) && count($badges) > 0) {
            $formData['badges'] = implode(', ', array_map(function ($el) use ($bdh) {
                $b = $bdh->findBy('Badge', ['uuid' => $el->getBadgeUuid()]);
                if (is_array($b) && count($b) === 1) {
                    $b = reset($b);
                    return $b->getName();
                } else {
                    return '';
                }
            }, $badges));
        }
    }

    $data = BaseHtmlLib::labeledListElement('class:view_info', $formData);
}

$label = translateFN('Visualizzazione dei dati del corso');
$help = translateFN('Da qui il provider admin può visualizzare i dati di un corso esistente');

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'edit_profile' => $userObj->getEditProfilePage(),
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];

$layout_dataAr['JS_filename'] = [
    ROOT_DIR . '/js/switcher/edit_content.js',
];
$optionsAr['onload_func'] = 'buildCourseAttachmentsTable(' . $courseObj->getId() . ', false, $j(\'ul.view_info\'));';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

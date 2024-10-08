<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Forms\CourseModelForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Services\NodeEditing\NodeEditing;

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
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $providerAuthors = $dh->findAuthorsList(['username'], '');
    $authors = [];
    foreach ($providerAuthors as $author) {
        $authors[$author[0]] = $author[1];
    }

    $availableLanguages = Translator::getSupportedLanguages();
    $languages = [];
    foreach ($availableLanguages as $language) {
        $languages[$language['id_lingua']] = $language['nome_lingua'];
    }

    $form = new CourseModelForm($authors, $languages);
    $form->fillWithPostData();
    if ($form->isValid()) {
        $course = [
            'nome' => $_POST['nome'],
            'titolo' => $_POST['titolo'],
            'descr' => $_POST['descrizione'],
            'd_create' => Utilities::ts2dFN(time()), //$_POST['data_creazione'],
            'd_publish' => $_POST['data_pubblicazione'] ?? null,
            'id_autore' => $_POST['id_utente_autore'],
            'id_nodo_toc' => $_POST['id_nodo_toc'],
            'id_nodo_iniziale' => $_POST['id_nodo_iniziale'],
            'media_path' => $_POST['media_path'],
            'id_lingua' => $_POST['id_lingua'],
            'static_mode' => $_POST['static_mode'],
            'crediti' => $_POST['crediti'],
            'duration_hours' => $_POST['duration_hours'],
            'service_level' => $_POST['service_level'],
        ];

        $id_course = $dh->addCourse($course);
        if (!AMADataHandler::isError($id_course)) {
            $node_data = [
                'id' => $id_course . '_' . $_POST['id_nodo_iniziale'],
                'name' => $_POST['titolo'],
                'type' => ADA_GROUP_TYPE,
                'id_node_author' => $_POST['id_utente_autore'],
                'id_nodo_parent' => null,
                'parent_id' => null,
                'text' => $_POST['descrizione'],
                'id_course' => $id_course,
            ];
            $result = NodeEditing::createNode($node_data);
            if (AMADataHandler::isError($result)) {
                //
            }

            // add a row in common.servizio
            $service_dataAr = [
                'service_name' => $_POST['titolo'],
                'service_description' => $_POST['descrizione'],
                'service_level' => $_POST['service_level'],
                'service_duration' => 0,
                'service_min_meetings' => 0,
                'service_max_meetings' => 0,
                'service_meeting_duration' => 0,
            ];
            $id_service = $common_dh->addService($service_dataAr);
            if (!AMADataHandler::isError($id_service)) {
                $tester_infoAr = $common_dh->getTesterInfoFromPointer($sess_selected_tester);
                if (!AMADataHandler::isError($tester_infoAr)) {
                    $id_tester = $tester_infoAr[0];
                    $result = $common_dh->linkServiceToCourse($id_tester, $id_service, $id_course);
                    if (AMADataHandler::isError($result)) {
                        $errObj = new ADAError($result);
                    } else {
                        header('Location: list_courses.php');
                        exit();
                    }
                } else {
                    $errObj = new ADAError($result);
                    $form = new CText(translateFN('Si è verificato un errore durante la creazione del corso. (1)'));
                }
            } else {
                $errObj = new ADAError($result);
                $form = new CText(translateFN('Si è verificato un errore durante la creazione del corso. (2)'));
            }
        } else {
            //          $errObj = new ADAError($id_course);
            $help = translateFN('Si è verificato un errore durante la creazione del corso: codice corso duplicato ');
        }
    } else {
        $form = new CText(translateFN('I dati inseriti nel form non sono validi'));
    }
} else {
    $providerAuthors = $dh->findAuthorsList(['username'], '');
    $authors = [];
    foreach ($providerAuthors as $author) {
        $authors[$author[0]] = $author[1];
    }

    $availableLanguages = Translator::getSupportedLanguages();
    $languages = [];
    foreach ($availableLanguages as $language) {
        $languages[$language['id_lingua']] = $language['nome_lingua'];
    }

    $form = new CourseModelForm($authors, $languages);
}

$label = translateFN('Aggiunta corso');
if (!isset($help)) {
    $help = translateFN('Da qui il provider admin può creare un nuovo corso');
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $form->getHtml(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_MASKEDINPUT,
    JQUERY_NO_CONFLICT,
    ROOT_DIR . '/js/switcher/edit_content.js',
];

$optionsAr['onload_func'] = 'initDateField(); includeFCKeditor(\'descrizione\');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

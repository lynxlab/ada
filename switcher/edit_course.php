<?php

/**
 * File edit_course.php
 *
 * The switcher can use this module to update the informations about an existing
 * course.
 *
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Forms\CourseModelForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Translator;

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
$self = whoami();  // = admin!

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
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $providerAuthors = $dh->find_authors_list(['username'], '');
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
            'nome' => $_POST['nome'] ?? null,
            'titolo' => $_POST['titolo'] ?? null,
            'descr' => $_POST['descrizione'] ?? null,
            'd_create' => $_POST['data_creazione'] ?? null,
            'd_publish' => $_POST['data_pubblicazione'] ?? null,
            'id_autore' => $_POST['id_utente_autore'] ?? null,
            'id_nodo_toc' => $_POST['id_nodo_toc'] ?? null,
            'id_nodo_iniziale' => $_POST['id_nodo_iniziale'] ?? null,
            'media_path' => $_POST['media_path'] ?? null,
            'id_lingua' => $_POST['id_lingua'] ?? null,
            'static_mode' => $_POST['static_mode'] ?? null,
            'crediti' => $_POST['crediti'] ?? null,
            'duration_hours' => $_POST['duration_hours'] ?? null,
            'service_level' => $_POST['service_level'] ?? null,
        ];
        $result = $dh->set_course($_POST['id_corso'], $course);

        if (!AMA_DataHandler::isError($result)) {
            $service_dataAr = $common_dh->get_service_info_from_course($_POST['id_corso']);
            if (!AMA_Common_DataHandler::isError($service_dataAr)) {
                $update_serviceDataAr = [
                    'service_name' => $_POST['titolo'],
                    'service_description' => $_POST['descrizione'],
                    'service_level' => $_POST['service_level'],
                    'service_duration' => $service_dataAr[4],
                    'service_min_meetings' => $service_dataAr[5],
                    'service_max_meetings' => $service_dataAr[6],
                    'service_meeting_duration' => $service_dataAr[7],
                ];
                $result = $common_dh->set_service($service_dataAr[0], $update_serviceDataAr);
                if (AMA_Common_DataHandler::isError($result)) {
                    $form = new CText("Si è verificato un errore durante l'aggiornamento dei dati del corso");
                } else {
                    // AGGIORNARE l'oggetto corso in sessione e poi fare il redirect a view_course.php
                    //header('Location: view_course.php?id_course=' . $_POST['id_corso']);
                    header('Location: list_courses.php');
                    exit();
                }
            }
        } else {
            $form = new CText("Si è verificato un errore durante l'aggiornamento dei dati del corso");
        }
    } else {
        $form = new CText('Form non valido');
    }
} else {
    if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
        $form = new CText(translateFN('Corso non trovato'));
    } else {
        $providerAuthors = $dh->find_authors_list(['username'], '');
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
        $form->addFileSection();

        if ($courseObj instanceof Course && $courseObj->isFull()) {
            $formData = [
                'id_corso' => $courseObj->getId(),
                'id_utente_autore' => $courseObj->getAuthorId(),
                'id_lingua' => $courseObj->getLanguageId(),
                'id_layout' => $courseObj->getLayoutId(),
                'nome' => $courseObj->getCode(),
                'titolo' => $courseObj->getTitle(),
                'descrizione' => $courseObj->getDescription(),
                'id_nodo_iniziale' => $courseObj->getRootNodeId(),
                'id_nodo_toc' => $courseObj->getTableOfContentsNodeId(),
                'media_path' => $courseObj->getMediaPath(),
                'static_mode' => $courseObj->getStaticMode(),
                'data_creazione' => $courseObj->getCreationDate(),
                'data_pubblicazione' => $courseObj->getPublicationDate(),
                'crediti' =>  $courseObj->getCredits(), // modifica in Course
                'duration_hours' => $courseObj->getDurationHours(),
                'service_level'  => $courseObj->getServiceLevel(),
            ];
            $form->fillWithArrayData($formData);
        } else {
            $form = new CText(translateFN('Corso non trovato'));
        }
    }
}

$label = translateFN('Modifica dei dati del corso');
$help = translateFN('Da qui il provider admin può modificare un corso esistente');

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

$optionsAr['onload_func'] = 'initDateField();  includeFCKeditor(\'descrizione\');';
if ($courseObj instanceof Course && $courseObj->isFull()) {
    $optionsAr['onload_func'] .= 'initEditCourse(' . $userObj->getId() . ',' . $courseObj->getId() . ');';
}
ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

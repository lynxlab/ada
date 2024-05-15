<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Forms\CourseInstanceForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
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
    AMA_TYPE_SWITCHER => ['layout', 'course'],
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
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = new CourseInstanceForm();
    $form->fillWithPostData();
    if ($form->isValid()) {
        $course_instanceAr = [
            'data_inizio_previsto' => Utilities::dt2tsFN($_POST['data_inizio_previsto']),
            'durata' => $_POST['durata'],
            'price' => $_POST['price'],
            'self_instruction' => $_POST['self_instruction'],
            'self_registration' => $_POST['self_registration'],
            'title' => $_POST['title'],
            'duration_subscription' => $_POST['duration_subscription'],
            'start_level_student' => $_POST['start_level_student'],
            'open_subscription' => $_POST['open_subscription'],
            'duration_hours' => $_POST['duration_hours'],
            'service_level' => $_POST['service_level'],
        ];
        $result = $dh->courseInstanceAdd($_POST['id_course'], $course_instanceAr);
        if (AMADataHandler::isError($result)) {
            $form = new CText(translateFN('Si è verificato un errore durante la creazione della nuova istanza'));
        } else {
            /*
             * Creazione della chat
             */
            $data_inizio_previsto = Utilities::dt2tsFN($_POST['data_inizio_previsto']);
            $durata = $_POST['durata'];
            $data_fine = $dh->addNumberOfDays($durata, $data_inizio ?? null);
            /**
             * giorgio 13/01/2021: force data_fine to have time set to 23:59:59
             */
            $data_fine = strtotime('tomorrow midnight', $data_fine) - 1;
            $id_istanza_corso = $result;
            $chatroom_ha['id_chat_owner'] = $userObj->id_user;
            $chatroom_ha['chat_title'] = $course_title; // $_POST['chat_title'];
            //            $chatroom_ha['chat_title'] = translateFN('Chat di classe'); // $_POST['chat_title'];
            $chatroom_ha['chat_topic'] = translateFN('Chat di classe');
            $chatroom_ha['welcome_msg'] = translateFN('Benvenut* nella chat della tua classe');
            $chatroom_ha['max_users'] = 99;
            $chatroom_ha['start_time'] = $data_inizio_previsto;
            $chatroom_ha['end_time'] = $data_fine;
            $chatroom_ha['id_course_instance'] = $id_istanza_corso;

            // add chatroom_ha to the database
            $chatroom = ChatRoom::addChatroomFN($chatroom_ha);

            header('Location: list_instances.php?id_course=' . $_POST['id_course']);
            exit();
        }
    } else {
        $form = new CText(translateFN('I dati inseriti nel form non sono validi'));
    }
} else {
    if ($courseObj instanceof Course && $courseObj->isFull()) {
        $formData = [
            'id_course' => $courseObj->getId(),
            'duration_hours' => $courseObj->getDurationHours(),
            'service_level' => $courseObj->getServiceLevel(),
        ];
        $course_title = $courseObj->getTitle();
        $form = new CourseInstanceForm();
        $form->fillWithArrayData($formData);
    } else {
        $form = new CText(translateFN('Corso non trovato'));
    }
}

$label = translateFN('Aggiunta di una classe (istanza) del corso:') . ' ' . $course_title;
$help = translateFN('Da qui il provider admin può creare una istanza di un corso');
$error_div = CDOMElement::create('DIV', 'id:error_form');
$error_div->setAttribute('class', 'hide_error');
$error_div->addChild(new CText(translateFN("ATTENZIONE: Ci sono degli errori nel modulo!")));
$help .= $error_div->getHtml();

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

ARE::render($layout_dataAr, $content_dataAr);

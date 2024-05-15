<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
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
    AMA_TYPE_SWITCHER => ['layout', 'course', 'course_instance'],
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

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
        $data = new CText(translateFN('Corso non trovato'));
    } elseif (!($courseInstanceObj instanceof CourseInstance) || !$courseInstanceObj->isFull()) {
        $data = new CText(translateFN('Classe non trovata'));
    } else {
        $form = new CourseInstanceForm();
        $form->fillWithPostData();
        if ($form->isValid()) {
            if ($_POST['started'] == 0) {
                $start_date = 0;
            } elseif ($courseInstanceObj->isStarted()) {
                $start_date = Utilities::dt2tsFN($courseInstanceObj->getStartDate());
            } else {
                $start_date = time();
            }
            $course_instanceAr = [
                'data_inizio' => $start_date,
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
            $result = $dh->courseInstanceSet($_POST['id_course_instance'], $course_instanceAr);
            if (AMADataHandler::isError($result)) {
                $data = new CText(translateFN("Si sono verificati degli errori durante l'aggiornamento") . '(1)');
            } else {
                /*
                 * For each course instance, a class chatroom with the same duration
                 * is made available. Every time there is an update in the course instance
                 * duration, this chatroom needs to be updated too.
                 */
                $id_instance = $_POST['id_course_instance'];
                $start_time = $start_date;
                $end_time = $dh->addNumberOfDays($_POST['durata'], $start_time);
                //               $end_time   = $course_instance_data_before_update['data_fine'];
                //               $id_chatroom = ChatRoom::getClassChatroomWithDurationFN($id_instance,$start_time,$end_time);
                $id_chatroom = ChatRoom::getClassChatroomForInstance($id_instance, 'C');

                if (AMADataHandler::isError($id_chatroom)) {
                    if ($id_chatroom->code == AMA_ERR_NOT_FOUND) {
                        /*
                         * if a class chatroom with the same duration of the course instance does not exist,
                         * create it.
                         */
                        $id_course = $dh->getCourseIdForCourseInstance($id_instance);
                        if (AMADataHandler::isError($id_course)) {
                            // gestire l'errore
                        }

                        $course_data = $dh->getCourse($id_course);
                        if (AMADataHandler::isError($course_data)) {
                            // gestire l'errore
                        }

                        $id_tutor = $dh->courseInstanceTutorGet($id_instance);
                        if (!AMADataHandler::isError($id_tutor)) {
                            $chatroom_ha['id_chat_owner'] = $id_tutor;
                        } else {
                            $chatroom_ha['id_chat_owner'] = $sess_id_user;
                        }


                        $chatroom_ha = [
                            'chat_title'    => $course_data['titolo'],
                            'chat_topic'    => translateFN('Discussione sui contenuti del corso'),
                            'start_time'    => $start_time,
                            'end_time'      => $end_time,
                            'max_utenti'    => '999',
                            'id_course_instance' => $id_instance,
                        ];

                        $result = ChatRoom::addChatroomFN($chatroom_ha);
                        if (AMADataHandler::isError($result)) {
                            // gestire l'errore
                        }
                    } else {
                        // e' un errore, gestire
                    }
                } else {
                    /*
                     * An existing chatroom with duration == class duration
                     * already exists, so update this chatroom start and end time.
                     */
                    $chatroomObj = new Chatroom($id_chatroom, MultiPort::getDSN($_SESSION['sess_selected_tester']));
                    $id_tutor = $dh->courseInstanceTutorGet($id_instance);
                    if (!AMADataHandler::isError($id_tutor)) {
                        $chatroom_data['id_chat_owner'] = $id_tutor;
                    } else {
                        $chatroom_data['id_chat_owner'] = $sess_id_user;
                    }
                    $chatroom_data = [
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'max_utenti'    => '999',
                    ];

                    $result = $chatroomObj->setChatroomFN($chatroomObj->id_chatroom, $chatroom_data);

                    if (AMADataHandler::isError($result)) {
                        // gestire l'errore
                    }
                }


                header('Location: list_instances.php?id_course=' . $courseObj->getId());
                exit();
            }
        } else {
            $data = new CText(translateFN('I dati inseriti nel form non sono validi'));
        }
    }
} else {
    if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
        $data = new CText(translateFN('Corso non trovato'));
    } elseif (!($courseInstanceObj instanceof CourseInstance) || !$courseInstanceObj->isFull()) {
        $data = new CText(translateFN('Classe non trovata'));
    } else {
        if (is_null($courseInstanceObj->getServiceLevel())) {
            $courseInstanceObj->service_level = $courseObj->getServiceLevel();
        }

        $formData = [
            'id_course' => $courseObj->getId(),
            'id_course_instance' => $courseInstanceObj->getId(),
            'data_inizio_previsto' => $courseInstanceObj->getScheduledStartDate(),
            'durata' => $courseInstanceObj->getDuration(),
            'started' => $courseInstanceObj->isStarted() ? 1 : 0,
            'price' => $courseInstanceObj->getPrice(),
            'self_instruction' => $courseInstanceObj->getSelfInstruction() ? 1 : 0,
            'self_registration' => $courseInstanceObj->getSelfRegistration() ? 1 : 0,
            'title' => $courseInstanceObj->getTitle(),
            'duration_subscription' => $courseInstanceObj->getDurationSubscription(),
            'start_level_student' => $courseInstanceObj->getStartLevelStudent(),
            'open_subscription' => $courseInstanceObj->getOpenSubscription() ? 1 : 0,
            'duration_hours' => $courseInstanceObj->getDurationHours(),
            'service_level' => $courseInstanceObj->getServiceLevel(),
        ];
        $data = new CourseInstanceForm();
        $data->fillWithArrayData($formData);
    }
}
$help = translateFN('Da qui il provider admin può modificare una istanza corso esistente');
$error_div = CDOMElement::create('DIV', 'id:error_form');
$error_div->setAttribute('class', 'hide_error');
$error_div->addChild(new CText(translateFN("ATTENZIONE: Ci sono degli errori nel modulo!")));
$help .= $error_div->getHtml();

$label = translateFN('Modifica istanza corso');

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

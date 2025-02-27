<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Forms\TutorSecondaryAssignmentForm;
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

/*
 * Handle practitioner assignment
 */
if (
    isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'
    //        && isset($id_tutors_new) && !empty($id_tutors_new)) {
    && (isset($id_tutors_new) || !empty($_POST['id_tutors_old']))
) {
    $courseInstanceId = $_POST['id_course_instance'];
    $courseId = $_POST['id_course'];
    $id_tutors_old = $_POST['id_tutors_old'];

    if ($id_tutors_old != 'no' && !empty($id_tutors_old)) {
        $id_tutors_old = explode(',', $id_tutors_old);
        foreach ($id_tutors_old as $idTutorOld) {
            if ($idTutorOld != '' && is_numeric($idTutorOld) && $idTutorOld > 0) {
                $result = $dh->courseInstanceTutorUnsubscribe($courseInstanceId, $idTutorOld);
                if (AMADataHandler::isError($result)) {
                    $errObj = new ADAError($result, translateFN('Errore nel disassociare il practitioner dal client'));
                }
            }
        }
    }
    if (is_array($id_tutors_new)) {
        foreach ($id_tutors_new as $id_tutor_new) {
            if ($id_tutor_new != '' && is_numeric($id_tutor_new) && $id_tutor_new > 0) {
                $result = $dh->courseInstanceTutorSubscribe($courseInstanceId, $id_tutor_new);
                if (AMADataHandler::isError($result)) {
                    $errObj = new ADAError($result, translateFN('Errore durante assegnazione del practitioner al client'));
                } else {
                    /*
                     * For each course instance, a class chatroom with the same duration
                     * is made available. Every time there is an update in the course instance
                     * duration, this chatroom needs to be updated too.
                     */
                    $id_instance = $courseInstanceId;
                    /*
                     *                $start_time = $start_date;
                                   $end_time = $dh->addNumberOfDays($_POST['durata'],$start_time);
                    //               $end_time   = $course_instance_data_before_update['data_fine'];
                    */

                    $id_chatroom = ChatRoom::getClassChatroomForInstance($id_instance, 'C');

                    if (!AMADataHandler::isError($id_chatroom)) {
                        /*
                         * An existing chatroom with id class and type = C (chat classroom)
                         * already exists, so update this chatroom owner (= tutor id).
                         */
                        $chatroomObj = new ChatRoom($id_chatroom);
                        $chatroom_data['id_chat_owner'] = $id_tutor_new;

                        $result = $chatroomObj->setChatroomFN($chatroomObj::$id_chatroom, $chatroom_data);

                        if (AMADataHandler::isError($result)) {
                            // gestire l'errore
                        }
                    }
                }
            }
        }
    }
    header('Location: list_instances.php?id_course=' . $courseId);
    exit();
} else {
    //    $id_course = $_GET['id_course'];
    if ($courseInstanceObj instanceof CourseInstance && $courseInstanceObj->isFull()) {
        $number = 'ALL';
        $id_course = $courseInstanceObj->getCourseId();
        $className = $courseInstanceObj->getTitle();
        $idInstance = $courseInstanceObj->getId();
        $result = $dh->courseInstanceTutorGet($courseInstanceObj->getId(), $number);
        if (AMADataHandler::isError($result)) {
            // FIXME: verificare che si venga redirezionati alla home page del'utente
            $errObj = new ADAError($result, translateFN('Errore in lettura tutor'));
        }
        if ($result === false) {
            $id_tutors_old = 'no';
        } else {
            $id_tutors_old = $result;
        }

        // array dei tutor
        $field_list_ar = ['nome', 'cognome'];
        $tutors_ar = $dh->getTutorsList($field_list_ar);
        if (AMADataHandler::isError($tutors_ar)) {
            $errObj = new ADAError($tutors_ar, translateFN('Errore in lettura dei tutor'));
        }


        $tutors = [];
        $ids_tutor = [];

        if ($id_tutors_old == 'no') {
            $tutors['no'] = translateFN('Nessun tutor');
        }

        foreach ($tutors_ar as $tutor) {
            $ids_tutor[] = $tutor[0];
            $nome = $tutor[1] . ' ' . $tutor[2];
            $link = CDOMElement::create('a');
            $link->setAttribute('id', 'tooltip' . $tutor[0]);
            $link->setAttribute('title', ''); // this is needed by the jquery-ui tooltip
            $link->setAttribute('href', 'javascript:void(0);');
            $link->addChild(new CText($nome));
            $tutors[$tutor[0]] = $link->getHtml();
        }

        $tutor_monitoring = $dh->getTutorsAssignedCourseInstance($ids_tutor);

        //create tooltips with tutor's assignments (html + javascript)
        $tooltips = '';
        $js = '<script type="text/javascript">';
        foreach ($tutor_monitoring as $k => $v) {
            $ul = CDOMElement::create('ul');
            if (!empty($v)) {
                foreach ($v as $i => $l) {
                    $nome_corso = $l['titolo'] . (!empty($l['title']) ? ' - ' . $l['title'] : '');
                    $li = CDOMElement::create('li');
                    $li->addChild(new CText($nome_corso));
                    $ul->addChild($li);
                }
            } else {
                $nome_corso = translateFN('Nessun corso trovato');
                $li = CDOMElement::create('li');
                $li->addChild(new CText($nome_corso));
                $ul->addChild($li);
            }

            $tip = CDOMElement::create('div', 'id:tooltipContent' . $k);
            $tip->setAttribute('style', 'display:none');
            $tip->addChild(new CText(translateFN('Tutor assegnato ai seguenti corsi:<br />')));
            $tip->addChild($ul);
            $tooltips .= $tip->getHtml();
            $js .= "\$j('#tooltip$k').tooltip({
                content: () => '<div class=\'assigntutor more\'>' + \$j('#tooltipContent$k').html() + '</div>'
            });";
        }
        $js .= '</script>';
        $tooltips .= $js;
        //end

        $data = new TutorSecondaryAssignmentForm($tutors, $id_tutors_old);
        $data->fillWithArrayData(
            [
                'id_tutors_old' => implode(',', $id_tutors_old),
                'id_course_instance' => $courseInstanceObj->getId(),
                'id_course' => $id_course,
            ]
        );
    } else {
        $data = new CText(translateFN('Classe non trovata'));
    }
}

$title = translateFN('Assegnazione di tutors alla classe');
$help = translateFN('Da qui il Provider Admin può assegnare dei tutors alla classe');
$help .= ' ' . $className . ' id: ' . $idInstance . ' - ' . translateFN('Corso') . ' ' . $id_course;
$status = translateFN('Assegnazione tutor');

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
  ];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
];

$content_dataAr = [
    'data' => $data->getHtml() . $tooltips,
    'menu' => $menu ?? null,
    'help' => $help,
    'status' => $status,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

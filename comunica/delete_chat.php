<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\ChatRemovalForm;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout'];
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
$self = whoami();  // = admin!
$self = 'list_chatrooms'; // x template

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
ComunicaHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['delete'] == 0) {
        $list_chatrooms = HTTP_ROOT_DIR . '/comunica/list_chatrooms.php';
        //        $msg = translateFN("<b>Non esiste nessuna chatroom con il chatroom ID specificato! Impossibile proseguire</b>");
        header("Location: $list_chatrooms");
        exit();
    }
    $chatId = DataValidator::isUinteger($_POST['id_room']);
    if ($chatId !== false) {
        $chatRoomHa = ChatRoom::getInfoChatroomFN($chatId);
        if (!AMADataHandler::isError($chatRoomHa)) {
            // check to see if the chatromm is started, in that case we disable some fields
            // $chatroom_started = $chatroomObj->isChatroomStartedFN($chatId);
            $classId = $chatRoomHa['id_istanza_corso'];
            $chatTitle = $chatRoomHa['titolo_chat'];
            $chat_deleted = ChatRoom::removeChatroomFN($chatId);
            if ($chat_deleted) {
                $data = new CText(translateFN('Chat cancellata'));
            } else {
                $data = new CText(translateFN('Errore nella cancellazione della Chat'));
            }
        } else {
            $data = new CText(translateFN('Chatroom non trovata'));
        }
    } else {
        $data = new CText(translateFN('Id chat non valido'));
    }
} else {
    $chatId = DataValidator::isUinteger($_GET['id_room']);
    if ($chatId === false) {
        $data = new CText(translateFN('Id chat non valido') . '(1)');
    } else {
        //         $chatroomObj = new ChatRoom($chatId);
        $chatRoomHa = ChatRoom::getInfoChatroomFN($chatId);
        if (!AMADataHandler::isError($chatRoomHa)) {
            $classId = $chatRoomHa['id_istanza_corso'];
            $chatTitle = $chatRoomHa['titolo_chat'];
            $formData = [
             'id_room' => $chatId,
            ];
            $data = new ChatRemovalForm();
            $data->fillWithArrayData($formData);
        } else {
            $data = new CText(translateFN('Chatroom non trovata') . '(1)');
        }
    }
}

$label = translateFN('Cancellazione chatroom') . ' ' . $chatTitle . ', id: ' . $chatId;
$label .= ' - ' . translateFN('Classe') . ': ' . $classId;
$help = translateFN('Da qui il provider admin può cancellare una chat esistente');

/*
 *
$content_dataAr = array(
    'chat_name' => $chat_name,
    'chat_type' => $chat_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module
//    'messages' => $chat_messages->getHtml()
);
 *
 */

$content_dataAr =  [
                'status' => $status,
                'user_name' => $user_name,
                'user_type' => $user_type,
                'help' => $help,
//                'label' => $label,
                'course_title' => $label,
                'data' => $data->getHtml(),
                'error' => $err_msg,
                ];

ARE::render($layout_dataAr, $content_dataAr);

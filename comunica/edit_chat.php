<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\ChatManagementForm;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
//    AMA_TYPE_STUDENT => array('layout'),
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],

];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = Utilities::whoami();
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

//print_r($GLOBALS);
$common_dh = AMACommonDataHandler::getInstance();
$dh = $GLOBALS['dh'];


// display message that explains the functionality of the current script
$help = translateFN("Da qui l'utente puo' creare una nuova chatroom inserendo i valori negli appositi campi.
	 <br><br>Attenzione!<br>Per il corretto funzionamento della chat e' importante inserire i valori corretti.");

$star = translateFN("I campi contrassegnati con * sono obbligatori, non possono essere lasciati vuoti!");
$status = translateFN("Modifica di una chatroom");
// different chat type options are available for admins and for tutors
if ($id_profile == AMA_TYPE_SWITCHER) {
    $options_of_chat_types = [
        // 'Privata' => 'Privata',
    'Classe' => 'Classe',
    'Pubblica' => 'Pubblica'];
}
if ($id_profile == AMA_TYPE_TUTOR) {
    $options_of_chat_types = [
//        'Privata' => 'Privata',
    'Classe' => 'Classe',
    'Pubblica' => 'Pubblica'];
    //  $options_of_chat_types = array('Privata' => 'Privata');
}
//***********************************

$id_room = DataValidator::checkInputValues('id_room', 'Integer', INPUT_GET, 0);
// initialize a new ChatDataHandler object
$chatroomObj = new ChatRoom($id_room);
// chek to see if the chatromm is started, in that case we disable some fields
$chatroom_started = $chatroomObj->isChatroomStartedFN($id_room);
$id_owner = $chatroomObj->id_chat_owner;

if ($chatroom_started) {
    $readonly = 'readonly';
} else {
    $readonly = 0;
}

// check user type
// owner can edit the chatroom
if ($id_owner == $sess_id_user) {
    $msg = translateFN("Utente abilitato per questa operazione.");
} elseif ($id_profile == AMA_TYPE_SWITCHER) {
    // admins can edit the chatroom
    $msg = translateFN("Utente abilitato per questa operazione.");
} elseif (($chatroom_started)) {
    // a moderator can edit the chatroom if chatroom is running
    $is_moderator = $chatroomObj->isUserModeratorFN($sess_id_user, $id_room);
    if ($is_moderator) {
        $msg = translateFN("Utente abilitato per questa operazione.");
    }
} else {
    $msg = translateFN("Utente non abilitato per questa operazione. Impossibile proseguire");
    $location = $navigationHistoryObj->lastModule();
    header("Location: $location?err_msg=$msg&msg=$msg");
}

// display message that explains the functionality of the current script
$help = translateFN("Da qui l'utente puo' modificare i dati di chatroom esistente inserendo i valori negli appositi campi.
 <br><br>Attenzione!<br>Per il corretto funzionamento della chat e' importante inserire i valori corretti.");
// title of the script
$status = translateFN("Modifica di una chatroom");
// indicates which fields are compulsory
$star = translateFN("I campi contrassegnati con * sono obbligatori, non possono essere lasciati vuoti!");

// Has the form been posted?
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $form = new ChatManagementForm();
    $form->fillWithPostData();
    if ($form->isValid()) {
        switch ($new_chat_type) {
            case 'Privata':
                $chatroom_ha['chat_type'] = INVITATION_CHAT;
                break;
            case 'Classe':
                $chatroom_ha['chat_type'] = CLASS_CHAT;
                break;
            case 'Pubblica':
                $chatroom_ha['chat_type'] = PUBLIC_CHAT;
                break;
            case '-- select --':
                $chatroom_old_ha = $chatroomObj->getInfoChatroomFN($id_room);
                $chatroom_ha['chat_type'] = $chatroom_old_ha['tipo_chat'];
                break;
            default:
        } // switch
        /*
         *
         * transfrom username's into user's_id
         *
         */
        $id_owner = $common_dh->findUserFromUsername($_POST['chat_owner']);
        if (AMADataHandler::isError($id_owner) or $id_owner == '') {
            $id_owner = $_POST['id_owner']; // old owner
        }
        // return new AMAError(AMA_ERR_READ_MSG);
        // getting only user_id

        // create a unix data date format
        $start_data_array =  [$_POST['start_day'],$_POST['start_time']];
        $start_data = Utilities::sumDateTimeFN($start_data_array);
        // create a unix data date format
        $end_data_array =  [$_POST['end_day'],$_POST['end_time']];
        $end_data = Utilities::sumDateTimeFN($end_data_array);

        $chatroom_ha['id_chat_owner'] = $id_owner;
        $chatroom_ha['chat_title'] = $chat_title;
        $chatroom_ha['chat_topic'] = $chat_topic;
        $chatroom_ha['welcome_msg'] = $welcome_msg;
        $chatroom_ha['max_users'] = $max_users;
        $chatroom_ha['start_time'] = $start_data;
        $chatroom_ha['end_time'] = $end_data;
        $chatroom_ha['id_course_instance'] = $id_course_instance;

        // update chatroom_ha to the database
        $chatroom = $chatroomObj->setChatroomFN($id_room, $chatroom_ha);

        if ($chatroom) {
            $err_msg = translateFN("<strong>La chatroom e' stata aggiornata con successo!</strong><br/>");
            $err_msg .= translateFN("Torna all'elenco delle tue");
            $err_msg .= " <a href=list_chatrooms.php>" . translateFN("chatroom") . "</a>";
        } else {
            if (is_object($chatroom)) {
                $errorObj = $chatroom->message;
                if ($errorObj == "errore: record già esistente") {
                    $err_msg = "<strong>" . translateFN("La chatroom &egrave; stata gi&agrave; aggiornata con questi dati.") . "</strong>";
                }
            }
        }
    }
} else {
    //get the array with all the current info of the chatoorm to be modified
    $chatroom_old_ha = $chatroomObj->getInfoChatroomFN($id_room);
    if (!is_array($chatroom_old_ha)) {
        $msg = translateFN("<b>Non esiste nessuna chatroom con il chatroom ID specificato! Impossibile proseguire</b>");
        header("Location: $error?err_msg=$msg");
    }
    // get the owner of the room
    $chat_room_HA['id_room'] = $id_room;
    $id_owner = $chatroom_old_ha['id_proprietario_chat'];
    $res_ar = $common_dh->getUserInfo($id_owner);
    if (AMADataHandler::isError($res_ar)) {
        return new AMAError(AMA_ERR_READ_MSG);
    }

    // getting username that is the name of the owner from the array
    //$owner_name = $res_ar['nome']. ' ' . $res_ar['cognome'];
    $owner_name = $res_ar['username'];
    $chat_room_HA['chat_owner'] = $owner_name;

    // get and visualize the actual chatroom type
    switch ($chatroom_old_ha['tipo_chat']) {
        case PUBLIC_CHAT:
            $old_chat_type = translateFN("pubblica");
            break;
        case CLASS_CHAT:
            $old_chat_type = translateFN("classe");
            break;
        case INVITATION_CHAT:
            $old_chat_type = translateFN("privata");
            break;
        default:
    }// switch
    $chat_room_HA['actual_chat_type'] = $old_chat_type;

    //get time and date and transform it to sting format
    //Utilities::ts2dFN()
    $old_start_time = AMADataHandler::tsToDate($chatroom_old_ha['tempo_avvio'], "%H:%M:%S");
    $old_start_day = AMADataHandler::tsToDate($chatroom_old_ha['tempo_avvio']);
    $old_end_time = AMADataHandler::tsToDate($chatroom_old_ha['tempo_fine'], "%H:%M:%S");
    $old_end_day = AMADataHandler::tsToDate($chatroom_old_ha['tempo_fine']);
    // different chat type options are available for admins and for tutors
    // admin case
    $chat_room_HA['start_day'] = $old_start_day;
    $chat_room_HA['start_time'] = $old_start_time;
    $chat_room_HA['end_day'] = $old_end_day;
    $chat_room_HA['end_time'] = $old_end_time;

    if ($id_profile == AMA_TYPE_SWITCHER) {
        $options_of_chat_types = [
           '-- select --' => '-- select --',
           'Privata' => 'Privata',
           'Classe' => 'Classe',
           'Pubblica' => 'Pubblica',
        ];
    }
    // tutor case
    if ($id_profile == AMA_TYPE_TUTOR) {
        $options_of_chat_types = [
           '-- select --' => '-- select --',
           'Classe' => 'Classe',
        ];
    }
    $chat_room_HA['new_chat_type'] = $options_of_chat_types;
    $chat_room_HA['chat_title'] = $chatroom_old_ha['titolo_chat'];
    $chat_room_HA['chat_topic'] = $chatroom_old_ha['argomento_chat'];
    $chat_room_HA['welcome_msg'] = $chatroom_old_ha['msg_benvenuto'];
    $chat_room_HA['max_users'] = $chatroom_old_ha['max_utenti'];
    $chat_room_HA['id_course_instance'] = $chatroom_old_ha['id_istanza_corso'];
    $chat_title = $chat_room_HA['chat_title'];
    $form = new ChatManagementForm();
    //$form->fillWithPostData();
    $form->fillWithArrayData($chat_room_HA);
}

$course_title = $chat_title;
// array with data to be sended to the browser
$data =  [
               'status' => $status,
               'user_name' => $user_name,
               'user_type' => $user_type,
               'help' => $help,
               'star' => $star,
               'course_title' => $course_title,
               'data' => $form->getHtml(),
               'error' => $err_msg ?? '',
              ];


ARE::render($layout_dataAr, $data);

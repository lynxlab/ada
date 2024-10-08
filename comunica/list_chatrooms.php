<?php

use Lynxlab\ADA\Comunica\ChatRoom;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
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

$variableToClearAR = ['layout'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],

];

/**
 * Performs basic controls before entering this module
 */
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
ComunicaHelper::init($neededObjAr);

$help = translateFN("Da qui l'utente puo' vedere la lista di tutte le chatrooms a cui puo' accedere.");
$status = ''; //translateFN('lista delle chatrooms');
$modulo = translateFN('lista delle chatrooms');

$chat_label = translateFN('entra');
$edit_label = translateFN('modifica');
$delete_label = translateFN('cancella');
$add_users_label = translateFN('aggiungi utenti');



switch ($id_profile) {
    // ADMINISTRATOR
    case AMA_TYPE_ADMIN:
    case AMA_TYPE_SWITCHER:
        // gets an array with all the chatrooms
        $all_chatrooms_ar = ChatRoom::getAllChatroomsFN();
        if (!AMADB::isError($all_chatrooms_ar)) {
            //initialize an array
            $list_chatrooms = [];
            // sort the chatrooms in reverse order, so we can visualize first the most recent chatrooms
            rsort($all_chatrooms_ar);
            $tbody_data = [];
            foreach ($all_chatrooms_ar as $id_chatroom) {
                //initialize a chatroom Object
                $chatroomObj = new ChatRoom($id_chatroom);
                //get the array with all the current info of the chatoorm
                $chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
                $id_course_instance = $chatroom_ha['id_istanza_corso'];
                $id_course = $dh->getCourseIdForCourseInstance($chatroom_ha['id_istanza_corso']);
                $courseObj = DBRead::readCourse($id_course);
                if (is_object($courseObj) && !AMADB::isError($courseObj)) {
                    $course_title = $courseObj->titolo; //title
                    $id_toc = $courseObj->id_nodo_toc;  //id_toc_node
                }

                // get the title of the chatroom
                $chat_title = $chatroom_ha['titolo_chat'];
                // get the type of the chatroom
                $c_type = $chatroom_ha['tipo_chat'];
                switch ($c_type) {
                    case PUBLIC_CHAT:
                        $chat_type = translateFN("pubblica");
                        break;
                    case CLASS_CHAT:
                        $chat_type = translateFN("classe");
                        break;
                    case INVITATION_CHAT:
                        $chat_type = translateFN("privata");
                        break;
                    default:
                } // switch $c_type
                // verifiy the status of the chatroom
                $started = $chatroomObj->isChatroomStartedFN($id_chatroom);
                $running = $chatroomObj->isChatroomActiveFN($id_chatroom);
                //$not_expired = $chatroomObj->isChatroomNotExpiredFN($id_chatroom);
                if ($running) {
                    $chatroom_status = translateFN('in corso');
                    switch ($c_type) {
                        case PUBLIC_CHAT:
                            $enter = "<a href=\"chat.php?id_room=$id_chatroom&id_course=$id_course\" target=\"_blank\"><img src=\"img/_chat.png\" alt=\"$chat_label\" border=\"0\"></a>";
                            break;
                        case CLASS_CHAT:
                            $enter = translateFN("- - - ");
                            break;
                        case INVITATION_CHAT:
                            $present = $chatroomObj->getUserStatusFN($sess_id_user, $id_chatroom);
                            if (
                                ($present == STATUS_OPERATOR) or ($present == STATUS_ACTIVE) or
                                    ($present == STATUS_MUTE) or ($present == STATUS_BAN)
                                    or ($present == STATUS_INVITED) or ($present == STATUS_EXIT)
                            ) {
                                $enter = "<a href=\"chat.php?id_room=$id_chatroom\" target=\"_blank\"><img src=\"img/_chat.png\" alt=\"$chat_label\" border=\"0\"></a>";
                            } else {
                                $enter = translateFN("- - - ");
                            }
                            break;
                        default:
                    } // switch $c_type
                } elseif (!$started) {
                    $chatroom_status = translateFN('non avviata');
                    $enter = translateFN("- - - ");
                } else {
                    $chatroom_status = translateFN('terminata');
                    $enter = translateFN("- - - ");
                }
                if ($c_type == INVITATION_CHAT) {
                    $add_users = "<a href=\"add_users_chat.php?id_room=$id_chatroom\"><img src=\"img/addUser.png\" alt=\"$add_users_label\" border=\"0\"></a>";
                } else {
                    $add_users = translateFN("- - -");
                }

                // create the entries for the table
                $tbody_data[] = [
                    $course_title ?? '',
                    $id_course_instance ?? '',
                    $chat_title ?? '',
                    $chatroom_status ?? '',
                    $chat_type ?? '',
                    $enter ?? '',
                    "<a href=\"edit_chat.php?id_room=$id_chatroom\"><img src=\"img/edit.png\" alt=\"$edit_label\" border=\"0\"></a>",
                    "<a href=\"delete_chat.php?id_room=$id_chatroom\"><img src=\"img/delete.png\" alt=\"$delete_label\" border=\"0\"></a>",
                  ];
            }

            // initialize a new Table object that will visualize the list of the chatrooms
            $thead_data = [
                    translateFN('corso'),
                    translateFN('classe'),
                    translateFN('titolo'),
                    translateFN('stato'),
                    translateFN('tipo'),
                    translateFN('entra'),
                    translateFN('modifica'),
                    translateFN('cancella'),
    //                translateFN('aggiungi utenti') => $add_users
             ];
            $table_room = BaseHtmlLib::tableElement('class:sortable', $thead_data, $tbody_data);
            $list_chatrooms_table = $table_room->getHtml();
        } else {
            $list_chatrooms_table = translateFN('Nessuna chat room trovata!');
        }
        break;
    case AMA_TYPE_TUTOR: // TUTOR
        // get the pubblic chatroom
        $public_chatroom = ChatRoom::findPublicChatroomFN();
        // get the instances for which the user is the tutor of the class
        $course_instances_ar = $dh->courseTutorInstanceGet($sess_id_user);
        // get only the ids of the courses instances
        foreach ($course_instances_ar as $value) {
            $course_instances_ids_ar[] = $value[0];
        }
        $class_chatrooms_ar = [];
        // get a bidimensional array with all the chatrooms for every course instance
        foreach ($course_instances_ids_ar as $id_course_instance) {
            $class_chatrooms = ChatRoom::getAllClassChatroomsFN($id_course_instance);
            if (is_array($class_chatrooms)) {
                $class_chatrooms_ar[] = $class_chatrooms;
            }
        }
        $chatrooms_class_ids_ar = [];
        // get only the ids of the chatrooms
        foreach ($class_chatrooms_ar as $value) {
            foreach ($value as $id) {
                $chatrooms_class_ids_ar[] = $id;
            }
        }
        // merge class chatrooms with the public chatroom
        //vito 9gennaio2009
        if (!AMADataHandler::isError($public_chatroom)) {
            array_push($chatrooms_class_ids_ar, $public_chatroom);
        }

        // get all the private chatrooms of the user
        $private_chatrooms_ar = ChatRoom::getAllPrivateChatroomsFN($sess_id_user);
        if (is_array($private_chatrooms_ar)) {
            $all_chatrooms_ar = array_merge($chatrooms_class_ids_ar, $private_chatrooms_ar);
        } else {
            $all_chatrooms_ar = $chatrooms_class_ids_ar;
        }
        // sort the chatrooms in reverse order, so we can visualize first the most recent chatrooms
        rsort($all_chatrooms_ar);
        //initialize the array of the chatrooms to be displayed on the screen
        $list_chatrooms = [];
        // start the construction of the table contaning all the chatrooms
        $tbody_data[] = [];
        foreach ($all_chatrooms_ar as $id_chatroom) {
            //initialize a chatroom Object
            if (!is_object($id_chatroom)) {
                $chatroomObj = new ChatRoom($id_chatroom, MultiPort::getDSN($sess_selected_tester));
                //get the array with all the current info of the chatoorm
                $chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
                $id_course_instance = $chatroom_ha['id_istanza_corso'];
                $id_course = $dh->getCourseIdForCourseInstance($chatroom_ha['id_istanza_corso']);
                $courseObj = DBRead::readCourse($id_course);
                if ((is_object($courseObj)) && (!AMADataHandler::isError($userObj))) {
                    $course_title = $courseObj->titolo; //title
                    $id_toc = $courseObj->id_nodo_toc;  //id_toc_node
                }

                // get the owner of the room
                $chat_title = $chatroom_ha['titolo_chat'];
                // get the type of the chatroom
                $c_type = $chatroom_ha['tipo_chat'];
                switch ($c_type) {
                    case PUBLIC_CHAT:
                        $chat_type = translateFN("pubblica");
                        break;
                    case CLASS_CHAT:
                        $chat_type = translateFN("classe");
                        break;
                    case INVITATION_CHAT:
                        $chat_type = translateFN("privata");
                        break;
                    default:
                } // switch $c_type
                // verify the status of the chatroom
                $started = $chatroomObj->isChatroomStartedFN($id_chatroom);
                $running = $chatroomObj->isChatroomActiveFN($id_chatroom);
                //$not_expired = $chatroomObj->isChatroomNotExpiredFN($id_chatroom);
                if ($running) {
                    $chatroom_status = translateFN('in corso');
                    $enter = "<a href=\"chat.php?id_room=$id_chatroom&id_course=$id_course\" target=\"_blank\"><img src=\"img/_chat.png\" alt=\"$chat_label\" border=\"0\"></a>";
                } elseif (!$started) {
                    $chatroom_status = translateFN('non avviata');
                    $enter = translateFN("- - -");
                } else {
                    $chatroom_status = translateFN('terminata');
                    // vito, 22 apr 2009
                    $enter = translateFN("- - -");
                    //$enter = "<a href=\"report_chat.php?id_room=$id_chatroom\" target=\"_self\">" . translateFN('Report') . "</a>";
                }
                $report = "<a href=\"report_chat.php?id_room=$id_chatroom\" target=\"_self\">" . translateFN('Report') . "</a>";
                //check if he is the owner of the chatroom in order to give access for edit and delete
                $id_owner = $chatroom_ha['id_proprietario_chat'];
                // get the title of the chatroom
                if ($id_owner == $sess_id_user) {
                    $edit = "<a href=\"edit_chat.php?id_room=$id_chatroom\"><img src=\"img/edit.png\" alt=\"$edit_label\" border=\"0\"></a>";
                    $delete = "<a href=\"delete_chat.php?id_room=$id_chatroom\"><img src=\"img/delete.png\" alt=\"$delete_label\" border=\"0\"></a>";
                    if ($c_type == INVITATION_CHAT) {
                        $add_users = "<a href=\"add_users_chat.php?id_room=$id_chatroom\"><img src=\"img/addUser.png\" alt=\"$add_users_label\" border=\"0\"></a>";
                    } else {
                        $add_users = translateFN("- - -");
                    }
                } else {
                    $edit = translateFN("- - -");
                    $delete = translateFN("- - -");
                    $add_users = translateFN("- - -");
                }
                // create the entries for the table
                $tbody_data[] = [
                    $course_title,
                    $id_course_instance,
                    $chat_title,
                    $chatroom_status,
                    $chat_type,
                    $enter,
                    $edit,
                    $report,
                  ];
            }
        }
        // initialize a new Table object that will visualize the list of the chatrooms
        $thead_data = [
                translateFN('corso'),
                translateFN('classe'),
                translateFN('titolo'),
                translateFN('stato'),
                translateFN('tipo'),
                translateFN('entra'),
                translateFN('modifica'),
                translateFN('report'),
         ];
        $table_room = BaseHtmlLib::tableElement('class:sortable', $thead_data, $tbody_data);
        $list_chatrooms_table = $table_room->getHtml();

        break;

        // AUTHOR
    case AMA_TYPE_AUTHOR:
        /*
         * vito, 22 apr 2009:
         * an author can only enter chatrooms he is invited to.
         */

        $available_chatrooms = ChatRoom::getAllPrivateChatroomsFN($sess_id_user);
        if (AMADataHandler::isError($available_chatrooms)) {
            if ($available_chatrooms->code != AMA_ERR_NOT_FOUND) {
                // there aren't chatrooms available.
                $available_chatrooms = [];
            } else {
                // an error occurred
                // ottenere la pagina da cui l'autore proviene
                // costruire un messaggio da passare a $status
                // redirigere l'autore alla pagina
            }
        }

        $list_chatrooms = [];

        foreach ($available_chatrooms as $id_chatroom) {
            $chatroomObj = new ChatRoom($id_chatroom);

            if (!AMADataHandler::isError($chatroomObj)) {
                switch ($chatroomObj->chat_type) {
                    case PUBLIC_CHAT:
                        $chat_type = translateFN('pubblica');
                        break;

                    case INVITATION_CHAT:
                        $chat_type = translateFN('privata');
                        break;

                    case CLASS_CHAT:
                    default:
                }

                // verify the status of the chatroom
                $started = $chatroomObj->isChatroomStartedFN($id_chatroom);
                $running = $chatroomObj->isChatroomActiveFN($id_chatroom);

                if ($running) {
                    $chatroom_status = translateFN('in corso');
                    //          $enter= "<a href=\"../comunica/adaChat.php?id_chatroom=$id_chatroom&id_course=$id_course\" target=_blank><img src=\"img/_chat.gif\" alt=\"$chat_label\" border=\"0\"></a>";
                    $enter = "<a href=\"chat.php?id_room=$id_chatroom\" target=\"_blank\"><img src=\"img/_chat.png\" alt=\"" . translateFN('Entra nella chat') . "\" border=\"0\"></a>";
                } elseif (!$started) {
                    $chatroom_status = translateFN('non avviata');
                    $enter = translateFN("- - -");
                } else {
                    $chatroom_status = translateFN('terminata');
                    // vito, 22 apr 2009
                    //$enter= translateFN("- - -");
                    $enter = "<a href=\"report_chat.php?id_room=$id_chatroom\" target=\"_self\">" . translateFN('Report') . "</a>";
                }
                // create the entries for the table
                $row = [
                    translateFN('titolo') => translateFN($chatroomObj->chat_title),
                    translateFN('stato') => $chatroom_status,
                    translateFN('tipo') => $chat_type,
                    translateFN('entra') => $enter,
                ];
                array_push($list_chatrooms, $row);
            }
        }

        // initialize a new Table object that will visualize the list of the chatrooms
        $tObj = new Table();
        $tObj->initTable('1', 'center', '2', '2', '100%', '', '', '', '', '1', '', '');
        $caption = '<strong>' . translateFN('La lista delle tue chatroom') . '</strong>';
        $summary = translateFN('La lista delle tue chatroom');
        $tObj->setTable($list_chatrooms, $caption, $summary);
        $list_chatrooms_table = $tObj->getTable();

        break;

    case AMA_TYPE_STUDENT: // STUDENT
        // get the public chatroom
        $public_chatroom = ChatRoom::findPublicChatroomFN();

        // get the active classes to which the user is subscribed
        $field_ar = ['id_corso'];
        $all_instances = $dh->courseInstanceStartedGetList($field_ar);
        // get only the ids of the classes
        foreach ($all_instances as $one_instance) {
            $id_course_instance = $one_instance[0];
            $sub_courses = $dh->getSubscription($_SESSION['sess_id_user'], $id_course_instance);
            //print_r($sub_courses);
            if ((is_array($sub_courses)) && ($sub_courses['tipo'] == ADA_STATUS_SUBSCRIBED)) {
                $class_instances_ids_ar[] = $id_course_instance;
            }
        }
        // get the ACTIVE chatroom, if exists, of each class

        $class_chatrooms_ar = [];
        if (is_array($class_instances_ids_ar)) {
            // get a bidimensional array with all the chatrooms for every course instance
            foreach ($class_instances_ids_ar as $id_course_instance) {
                $chatroom_class = ChatRoom::getClassChatroomFN($id_course_instance);
                //vito 9gennaio2009
                //if(!is_object($chatroom_class)){
                if (!AMADataHandler::isError($chatroom_class)) {
                    $class_chatrooms_ar[] = $chatroom_class;
                }
            }
            // merge class chatrooms with the public chatroom
            //vito 9gennaio2009
            if (!AMADataHandler::isError($public_chatroom)) {
                array_push($class_chatrooms_ar, $public_chatroom);
            }
        }



        // get all the private chatrooms of the user
        $private_chatrooms_ar = ChatRoom::getAllPrivateChatroomsFN($sess_id_user);
        if (is_array($private_chatrooms_ar)) {
            $all_chatrooms_ar = array_merge($class_chatrooms_ar, $private_chatrooms_ar);
        } else {
            $all_chatrooms_ar = $class_chatrooms_ar;
        }
        // sort the chatrooms in reverse order, so we can visualize first the most recent chatrooms
        rsort($all_chatrooms_ar);
        //initialize the array of the chatrooms to be displayed on the screen
        $list_chatrooms = [];
        // start the construction of the table contaning all the chatrooms
        foreach ($all_chatrooms_ar as $id_chatroom) {
            //initialize a chatroom Object
            $chatroomObj = new ChatRoom($id_chatroom);
            //get the array with all the current info of the chatoorm
            $chatroom_ha = $chatroomObj->getInfoChatroomFN($id_chatroom);
            // vito, 16 mar 2009
            $id_course = $dh->getCourseIdForCourseInstance($chatroom_ha['id_istanza_corso']);

            // get the owner of the room
            $chat_title = $chatroom_ha['titolo_chat'];
            // get the type of the chatroom
            $c_type = $chatroom_ha['tipo_chat'];
            switch ($c_type) {
                case PUBLIC_CHAT:
                    $chat_type = translateFN('pubblica');
                    break;
                case CLASS_CHAT:
                    $chat_type = translateFN('classe');
                    break;
                case INVITATION_CHAT:
                    $chat_type = translateFN('privata');
                    break;
                default:
            } // switch $c_type
            // verify the status of the chatroom
            $started = $chatroomObj->isChatroomStartedFN($id_chatroom);
            $running = $chatroomObj->isChatroomActiveFN($id_chatroom);
            //$not_expired = $chatroomObj->isChatroomNotExpiredFN($id_chatroom);
            if ($running) {
                $chatroom_status = translateFN('in corso');
                $enter = "<a href=\"chat.php?id_room=$id_chatroom&id_course=$id_course\" target=\"_blank\"><img src=\"img/_chat.png\" alt=\"$chat_label\" border=\"0\"></a>";
            } elseif (!$started) {
                $chatroom_status = translateFN('non avviata');
                $enter = translateFN("- - -");
            } else {
                $chatroom_status = translateFN('terminata');
                $enter = translateFN("- - -");
            }
            // create the entries for the table
            $row = [
                translateFN('titolo') => translateFN($chat_title),
                translateFN('stato') => $chatroom_status,
                translateFN('tipo') => $chat_type,
                translateFN('entra') => $enter,
            ];
            array_push($list_chatrooms, $row);
        }
        // initialize a new Table object that will visualize the list of the chatrooms
        $tObj = new Table();
        $tObj->initTable('1', 'center', '2', '2', '100%', '', '', '', '', '1', '', '');
        $caption = "<strong>" . translateFN("La lista delle tue chatroom") . "</strong>";
        $summary = translateFN("La lista delle tue chatroom");
        $tObj->setTable($list_chatrooms, $caption, $summary);
        $list_chatrooms_table = $tObj->getTable();
}




$content_dataAr = [
  'user_name' => $user_name,
  'user_type' => $user_type,
//  'messages'     => $messages->getHtml(),
  'status' => $status,
  'course_title' => $modulo,
  'help' => $help,
  'data' => $list_chatrooms_table,
  'chat_users' => $online_users ?? '',
  'edit_profile' => $userObj->getEditProfilePage(),
];

ARE::render($layout_dataAr, $content_dataAr);

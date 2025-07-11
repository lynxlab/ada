<?php

use Lynxlab\ADA\Browsing\DFSNavigationBar;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\ForkedPaths\ForkedPathsNode;
use Lynxlab\ADA\Module\Test\NodeTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_VISITOR => ['node', 'layout', 'course'],
    AMA_TYPE_STUDENT => ['node', 'layout', 'tutor', 'course', 'course_instance'],
    AMA_TYPE_TUTOR => ['node', 'layout', 'course', 'course_instance'],
    AMA_TYPE_AUTHOR => ['node', 'layout', 'course'],
    AMA_TYPE_SWITCHER => ['node', 'layout', 'course'],
];

//FIXME: course_instance is needed by videochat BUT not for guest user


/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';

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
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
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
BrowsingHelper::init($neededObjAr);

if (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) {
    $self_instruction = $courseInstanceObj->getSelfInstruction();
} else {
    $self_instruction = null;
}

$dsfExtraParams = [];
if ($userObj instanceof ADAGuest) {
    $self = 'guest_view';
    $dsfExtraParams['testerToUse'] = ADA_PUBLIC_TESTER;
} elseif ($userObj->tipo == AMA_TYPE_STUDENT) {
    /**
     * before doing anything, check if the passed node is in an autosubscribe course
     */
    if (isset($courseObj) && $courseObj instanceof Course && $courseObj->getAutoSubscription()) {
        // then check if user is subscribed to the instance
        $subCheck = $dh->getSubscription($userObj->getId(), $courseInstanceObj->getId());
        if (AMADB::isError($subCheck) && $subCheck->getCode() == AMA_ERR_NOT_FOUND) {
            // subscribe: mimc the info.php / subscribe section behaviour
            // 00. add the user to the session provider
            if (false !== MultiPort::setUser($userObj, [$_SESSION['sess_selected_tester']])) {
                // 01. presubscribe
                $temp = $dh->courseInstanceStudentPresubscribeAdd($courseInstanceObj->getId(), $userObj->getId(), $courseInstanceObj->getStartLevelStudent());
                if (!AMADB::isError($temp) || $temp->code == AMA_ERR_UNIQUE_KEY) {
                    // 02. subscribe
                    $temp = $dh->courseInstanceStudentSubscribe($courseInstanceObj->getId(), $userObj->getId(), ADA_STATUS_SUBSCRIBED, $courseInstanceObj->getStartLevelStudent());
                    if (AMADB::isError($temp)) {
                        // handle subscription error if needed
                    } else {
                        // handle subscription success if needed
                    }
                } else {
                    // handle presubscription error if needed
                }
            } else {
                // handle add to provider error if needed
            }
        }
    }
    /**
     * done autosubscribe checks and possibly done the subscription
     */

    if ($self_instruction) {
        $self = 'viewSelfInstruction';
    } else {
        $self = Utilities::whoami();
    }
    // $self='tutorSelfInstruction';
} elseif ($userObj->tipo == AMA_TYPE_AUTHOR) {
    $self = 'viewAuthor';
} else {
    $self = Utilities::whoami();
}

if ($nodeObj->type != ADA_NOTE_TYPE && $nodeObj->type != ADA_PRIVATE_NOTE_TYPE) {
    $navBar = new DFSNavigationBar($nodeObj, [
        'prevId' => $_GET['prevId'] ?? null,
        'nextId' => $_GET['nextId'] ?? null,
        'userLevel' => $user_level,
    ] + $dsfExtraParams);
} else {
    $navBar = new CText('');
}

//redirect to test module if necessary
if (MODULES_TEST && ADA_REDIRECT_TO_TEST && str_starts_with($nodeObj->type, (string) constant('ADA_PERSONAL_EXERCISE_TYPE'))) {
    NodeTest::checkAndRedirect($nodeObj);
}

// search
// versione con campo UNICO

$l_search = 'standard_node';

$form_dataHa = [
    // SEARCH FIELDS
    [
        'label' => translateFN('Parola') . "<br>",
        'type' => 'text',
        'name' => 's_node_text',
        'size' => '20',
        'maxlength' => '40',
        'value' => $s_node_text ?? null,
    ],
    [
        'label' => '',
        'type' => 'hidden',
        'name' => 'l_search',
        'value' => $l_search ?? null,
    ],
    [
        'label' => '',
        'type' => 'submit',
        'name' => 'submit',
        'value' => translateFN('Cerca'),
    ],
];

$fObj = new Form();
$fObj->initForm("search.php?op=lemma", "POST");
$fObj->setForm($form_dataHa);
$search_form = $fObj->getForm();

/**
 * Backurl: if user bookmarked an address and tried to get it directly...
 *
 * if (isset($_SESSION['sess_backurl'])) {
 * unset($_SESSION['sess_backurl']);
 * }
 *
 * if (!isset($_SESSION['sess_id_user'])) {
 * $_SESSION['sess_backurl'] = $_SERVER['REQUEST_URI'];
 * header("Location: $http_root_dir"); // to login page
 * exit();
 * }
 */


/**
 * ANONYM Browsing
 *
 * if status of course_instance is ADA_STATUS_PUBLIC
 * user can visit the node but:
 * - no history
 * - no messagery
 * - no logging
 */


/**
 * Guided browsing:
 * if a guide is selected, the node id selected by the  student is overrided
 * $guide_user_id = id number of tutor
 */
if ($id_profile == AMA_TYPE_STUDENT) {
    if ($user_status <> ADA_STATUS_VISITOR) {
        //...... do we need it in ADA?
    }
}


// querystring

if (!isset($_REQUEST['querystring'])) {  // word to be enlighten
    $querystring = "";
} else {
    $querystring = urldecode($_REQUEST['querystring']);
}

// node
$id_node = $nodeObj->id;
$node_type = $nodeObj->type;
$node_title = $nodeObj->name;
$node_keywords = ltrim($nodeObj->title);
$node_level = $nodeObj->level;
$node_date = $nodeObj->creation_date;
$node_icon = $nodeObj->icon;
$node_version = $nodeObj->version;
if (is_array($nodeObj->author)) {
    $authorHa = $nodeObj->author;
    $node_author_id = $authorHa['id'];
    $node_author = $authorHa['nome'] . " " . $authorHa['cognome'];
    $author_uname = $authorHa['username'];
} else {
    $node_author = "";
    $author_uname = "";
}
$node_parent = $nodeObj->parent_id;
$node_path = $nodeObj->findPathFN();
$node_index = $nodeObj->indexFN('', 1, $user_level, $user_history, $id_profile);
$node_family = $nodeObj->template_family;
$sess_id_node = $id_node;
$data = $nodeObj->filterNodeFN($user_level, $user_history, $id_profile, $querystring);



// history:
if (
    (
        ($id_profile == AMA_TYPE_STUDENT && !in_array($user_status, [ADA_STATUS_COMPLETED, ADA_STATUS_TERMINATED]))
        || ($id_profile == AMA_TYPE_VISITOR)
        || ($id_profile == AMA_TYPE_TUTOR)
    )
    /* && (!empty($sess_id_course_instance)) */
    /* && ($is_istance_active) */
    && ($node_type != ADA_PRIVATE_NOTE_TYPE)
) {
    /*
     * We need to save visits made by guest users (i.e. users not logged in)
     */
    if (isset($_SESSION['ada_remote_address'])) {
        $remote_address = $_SESSION['ada_remote_address'];
    } else {
        $remote_address = $_SERVER['REMOTE_ADDR'];
        $_SESSION['ada_remote_address'] = $remote_address;
    }

    if (isset($_SESSION['ada_access_from'])) {
        $accessed_from = $_SESSION['ada_access_from'];
    } else {
        $accessed_from = ADA_GENERIC_ACCESS;
    }
    if (!isset($sess_id_course_instance)  || $courseObj->getIsPublic()) {
        $dh->addNodeHistory($sess_id_user, 0, $sess_id_node, $remote_address, HTTP_ROOT_DIR, $accessed_from);
    } else {
        $dh->addNodeHistory($sess_id_user, $sess_id_course_instance, $sess_id_node, $remote_address, HTTP_ROOT_DIR, $accessed_from);
        if (isset($courseObj) && isset($courseInstanceObj)) {
            BrowsingHelper::checkServiceComplete($userObj, $courseObj->getId(), $courseInstanceObj->getId());
            BrowsingHelper::checkRewardedBadges($userObj, $courseObj->getId(), $courseInstanceObj->getId());
        }
    }
}

// info on author and tutor and link for writing to tutor and author
if (isset($tutor_uname)) {
    $write_to_tutor_link = "<a href=\"$http_root_dir/comunica/send_message.php?destinatari=$tutor_uname\">$tutor_uname</a>";
    if (isset($tutor_id)) {
        $tutor_info_link = $tutor_uname;
    } else {
        $tutor_info_link = null;
    }
} else {
    $write_to_tutor_link = null;
    $tutor_info_link = null;
}

if (isset($node_author)) {
    if (isset($author_uname)) {
        $write_to_author_link = "<a href=\"$http_root_dir/comunica/send_message.php?destinatari=$author_uname\">$node_author</a>";
    } else {
        $write_to_author_link = null;
    }

    if (isset($node_author_id)) {
        $author_info_link = $node_author;
    } else {
        $author_info_link = null;
    }
} else {
    $write_to_author_link = null;
    $author_info_link = null;
}

// E-portal
$eportal = PORTAL_NAME;

if ($id_profile == AMA_TYPE_AUTHOR && $mod_enabled) {
    $edit_node = "<a href=\"$http_root_dir/services/edit_node.php?op=edit&id_node=$sess_id_node&id_course=$sess_id_course&type=$node_type\">" .
        translateFN('modifica nodo') . "</a>";

    $delete_node = "<a href=\"$http_root_dir/services/edit_node.php?op=delete&id_node=$sess_id_node&id_course=$sess_id_course&type=$node_type\">" .
        translateFN('elimina nodo') . "</a>";

    $add_exercise = "<a href=\"$http_root_dir/services/add_exercise.php?id_node=$sess_id_node\">" .
        translateFN('aggiungi esercizio') . "</a>";
}
if (is_array($nodeObj->children) && count($nodeObj->children) > 0) {
    if ($node_type == ADA_GROUP_TYPE) {
        $go_map = '<a href="map.php?id_node=' . $sess_id_node . '">'
            . translateFN('mappa') . '</a>';
    } elseif ($node_type == ADA_GROUP_WORD_TYPE) {
        $go_map = '<a href="map.php?id_node=' . $sess_id_node . '&map_type=lemma">'
            . translateFN('mappa') . '</a>';
    } else {
        $go_map = '';
    }
} else {
    $go_map = '';
}

switch ($id_profile) {
    case AMA_TYPE_STUDENT:
    case AMA_TYPE_TUTOR:
        $add_note = "<a href=\"$http_root_dir/services/addnode.php?id_parent=$sess_id_node&id_course=$sess_id_course&id_course_instance=$sess_id_course_instance&type=NOTE\">" .
            translateFN('aggiungi nota di classe') . '</a>';
        $add_private_note = "<a href=\"$http_root_dir/services/addnode.php?id_parent=$sess_id_node&id_course=$sess_id_course&id_course_instance=$sess_id_course_instance&type=PRIVATE_NOTE\">" .
            translateFN('aggiungi nota personale') . '</a>';

        if ($nodeObj->type == ADA_PRIVATE_NOTE_TYPE || $nodeObj->type == ADA_NOTE_TYPE) {
            // if it's a note
            if (
                ($node_author_id == $userObj->getId() && $id_profile == AMA_TYPE_STUDENT) ||
                $id_profile == AMA_TYPE_TUTOR
            ) {
                $edit_note = "<a href=\"" . $http_root_dir . "/services/edit_node.php?op=edit&id_node=" . $sess_id_node . "&id_course=" . $sess_id_course . "&id_course_instance=" . $sess_id_course_instance . "&type=" . $node_type . "\">"
                    . translateFN('modifica nota') . "</a>";
                $delete_note = "<a href=\"" . $http_root_dir . "/services/edit_node.php?op=delete&id_node=" . $sess_id_node . "&id_course=" . $sess_id_course . "&id_course_instance=" . $sess_id_course_instance . "&type=" . $node_type . "\">"
                    . translateFN('elimina nota') . "</a>";
                /**
                 * student can promote only PRIVATE_NOTE to NOTE
                 * tutor can do everything
                 */
                if (
                    ($nodeObj->type == ADA_PRIVATE_NOTE_TYPE && $id_profile == AMA_TYPE_STUDENT) ||
                    ($id_profile == AMA_TYPE_TUTOR)
                ) {
                    $publish_note = "<a href=\"" . $http_root_dir . "/services/edit_node.php?" .
                        "op=publish" .
                        "&id_node=" . $sess_id_node .
                        "&id_course=" . $sess_id_course .
                        "&id_course_instance=" . $sess_id_course_instance .
                        "&type=" . $node_type . "\">"  .
                        translateFN("pubblica nota") . "</a>";
                }
            }
        }

        break;
    default:
        $add_note = '';
        $add_private_note = '';
        $edit_note = '';
        $delete_note = '';
        break;
}
/*  gli studenti dei corsi in autoistruzione non devono poter inviare media etc etc
         *  TODO: va riportata la modifica fatta per ADA Icon
         * */
if ($id_profile == AMA_TYPE_STUDENT && isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance && $courseInstanceObj->getSelfInstruction()) {
    $mod_enabled = false;
    $com_enabled = false;
} elseif ($id_profile == AMA_TYPE_VISITOR) {
    $mod_enabled = false;
    $com_enabled = false;
}

//show course istance name if isn't empty - valerio
if (!empty($courseInstanceObj->title)) {
    $course_title .= ' - ' . $courseInstanceObj->title;
}
// keywords linked to search separately
$linksAr  = [];
$keyAr = explode(',', $node_keywords); // or space?
$keyAr = array_map('trim', $keyAr);
foreach ($keyAr as $keyword) {
    if (ModuleLoaderHelper::isLoaded('FORKEDPATHS') && $keyword == ForkedPathsNode::MAGIC_KEYWORD) {
        // just skip the ForkedPathsNode::MAGIC_KEYWORD
        continue;
    }
    if (strlen($keyword) > 0) {
        $linksAr[] = "<a href=\"search.php?s_node_title=$keyword&submit=cerca&l_search=all\">$keyword</a>";
    }
}
$linked_node_keywords = implode(',', $linksAr);

/**
 * content_data
 * @var array
 */
$content_dataAr = [
    'eportal' => $eportal,
    'course_title' => "<a href='main_index.php'>" . $course_title . "</a>",
    'main_index' => "<a href='main_index.php?op=glossary'>" . translateFN('Indice delle parole') . "</a>",
    'main_index_text' => "<a href='main_index.php'>" . translateFN('Indice dei testi') . "</a>",
    'user_name' => $user_name,
    'user_type' => $user_type,
    'user_level' => $user_level,
    'user_score' => $user_score,
    'status' => $status,
    'node_level' => $node_level,
    'visited' => $visited ?? null,
    'path' => $node_path,
    'title' => $node_title,
    'version' => $node_version,
    'date' => $node_date,
    // FIXME: non esiste ancora...??
    //   'icon' => CourseViewer::getClassNameForNodeType($node_type),
    'icon' => $node_icon,
    // 'keywords' => "<a href=\"search.php?s_node_title=$node_keywords&submit=cerca&l_search=all\">$node_keywords</a>",
    'keywords' => $linked_node_keywords,
    'author' => $author_info_link, //'author'=>$node_author,
    'tutor' => $tutor_info_link, //'tutor'=>$tutor_uname,
    'search_form' => $search_form,
    'index' => $node_index,
    'go_map' => $go_map,
    'edit_profile' => $userObj->getEditProfilePage(),
    'navigation_bar' => $navBar->getHtml(),
    //        'messages' => $user_messages,
    //        'agenda' => $user_agenda
];

//dynamic data from $nodeObj->filterNodeFN

$content_dataAr['text'] = $data['text'];
$content_dataAr['link'] = $data['link'];
$content_dataAr['media'] = $data['media'];
$content_dataAr['user_media'] = $data['user_media'];
$content_dataAr['exercises'] = $data['exercises'];
$content_dataAr['notes'] = strlen($data['notes'] ?? '') > 0 ? $data['notes'] : translateFN('Nessuna');
$content_dataAr['personal'] = strlen($data['private_notes'] ?? '') > 0 ? $data['private_notes'] : translateFN('Nessuna');

if ($node_type == ADA_GROUP_WORD_TYPE or $node_type == ADA_LEAF_WORD_TYPE) {
    $content_dataAr['text'] .= $data['extended_node'];
    /*
     * generate dattilo images DISABLED IN ADA

    $img_dir = $root_dir.'/browsing/dattilo/img';
    $url_dir = $http_root_dir.'/browsing/dattilo/img';
    if (file_exists($img_dir.'/a.jpg')) {
        $dattilo = Utilities::convertiDattiloFN($node_title,$url_dir);
        $content_dataAr['dattilo'] = $dattilo;
    }
    * */
}

if ($reg_enabled && isset($addBookmark)) {
    $content_dataAr['addBookmark'] = $addBookmark;
} else {
    $content_dataAr['addBookmark'] = "";
}

$content_dataAr['bookmark'] = $bookmark  ?? null;
$content_dataAr['go_bookmarks_1'] = $go_bookmarks ?? null;
$content_dataAr['go_bookmarks_2'] = $go_bookmarks ?? null;

if ($mod_enabled) {
    if (isset($edit_node)) {
        $content_dataAr['edit_node'] = $edit_node;
    }
    if (isset($delete_node)) {
        $content_dataAr['delete_node'] = $delete_node;
    }
    if (isset($add_exercise)) {
        $content_dataAr['add_exercise'] = $add_exercise;
    }
    if (isset($add_note)) {
        $content_dataAr['add_note'] = $add_note;
    }
    if (isset($add_private_note)) {
        $content_dataAr['add_private_note'] = $add_private_note;
    }
    if (isset($edit_note)) {
        $content_dataAr['edit_note'] = $edit_note;
    }
    if (isset($delete_note)) {
        $content_dataAr['delete_note'] = $delete_note;
    }
    if (isset($publish_note)) {
        $content_dataAr['publish_note'] = $publish_note;
    }
} else {
    $content_dataAr['edit_node'] = '';
    $content_dataAr['delete_node'] = '';
    $content_dataAr['add_note'] = '';
    $content_dataAr['add_private_note'] = '';
    $content_dataAr['edit_note'] = '';
    $content_dataAr['delete_note'] = '';
}

if ($com_enabled) {
    $online_users_listing_mode = 2;
    $online_users = ADALoggableUser::getOnlineUsersFN($sess_id_course_instance, $online_users_listing_mode);

    $content_dataAr['messages'] = $user_messages->getHtml();
    $content_dataAr['agenda'] = $user_agenda->getHtml();
    $content_dataAr['events'] = $user_events->getHtml();
    $content_dataAr['chat_users'] = $online_users;
} else {
    $content_dataAr['chat_link'] = translateFN("chat non abilitata");
    $content_dataAr['messages'] = translateFN("messaggeria non abilitata");
    $content_dataAr['agenda'] = translateFN("agenda non abilitata");
    $content_dataAr['chat_users'] = "";
}
if ($id_profile == AMA_TYPE_STUDENT) {
    $content_dataAr['exercise_history'] = '<a href="exercise_history.php?id_course_instance=' . $sess_id_course_instance . '">' . translateFN('storico esercizi') . '</a>';
}
$content_dataAr['id_node_parent'] = strcasecmp('null', $node_parent) != 0 ? $node_parent : $sess_id_node;

$op ??= null;
switch ($op) {
    case 'viewXML':
        $XML_optionsAr = [
            'id' => $id_node,
            'url' => $_SERVER['URI'],
            'course_title' => strip_tags($content_dataAr['course_title']),
            'portal' => $eportal,
        ];
        ARE::render($layout_dataAR, $content_dataAr, ARE_XML_RENDER, $XML_optionsAr);
        break;

    case 'print':
        $PRINT_optionsAr = [
            'id' => $id_node,
            'url' => $_SERVER['URI'],
            'course_title' => strip_tags($content_dataAr['course_title']),
            'portal' => $eportal,
        ];
        ARE::render($layout_dataAR, $content_dataAr, ARE_PRINT_RENDER, $PRINT_optionsAr);
        break;
    case 'exe':
        // execute the code (!!!)
        //  $content_dataAr['text'] = eval($data['text']); DISABLED IN ADA
        //eval($data['text']);
        // Sends data to the rendering engine
        ARE::render($layout_dataAR, $content_dataAr, null, null);
        break;
    case 'view':
    default:
        // Sends data to the rendering engine

        // giorgio 06/set/2013, jquery inclusion

        $layout_dataAR['JS_filename'] = [
            JQUERY,
            JQUERY_UI,
            JQUERY_NIVOSLIDER,
            JQUERY_JPLAYER,
            JQUERY_NO_CONFLICT,
        ];
        if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction || $nodeObj->isForkedPaths)) {
            //$self='viewSelfInstruction';
            $layout_dataAR['JS_filename'][] = ROOT_DIR . '/js/browsing/view.js';
            $layout_dataAR['JS_filename'][] = MODULES_FORKEDPATHS_PATH . '/js/browsing/viewForkedPaths.js';
        }
        /**
         * if the jquery-ui theme directory is there in the template family,
         * do not include the default jquery-ui theme but use the one imported
         * in the .css file instead
         */
        if (!isset($userObj->template_family) || $userObj->template_family == '') {
            $userObj->template_family = ADA_TEMPLATE_FAMILY;
        }

        if (!is_dir(ROOT_DIR . '/layout/' . $userObj->template_family . '/css/jquery-ui')) {
            $layout_dataAR['CSS_filename'] = [
                JQUERY_UI_CSS,
            ];
        } else {
            $layout_dataAR['CSS_filename'] = [];
        }

        array_push($layout_dataAR['CSS_filename'], JQUERY_NIVOSLIDER_CSS);
        array_push($layout_dataAR['CSS_filename'], ROOT_DIR . '/js/include/jquery/nivo-slider/themes/default/default.css');
        array_push($layout_dataAR['CSS_filename'], JQUERY_JPLAYER_CSS);

        if ($userObj->getType() == AMA_TYPE_STUDENT) {
            $layout_dataAR['widgets']['courseStatus'] = [
                /*
                 * use commented condition to activate the widget in all non public courses
                 * for students not having the status to completed or terminated
                 */
                'isActive' => 0, // !$courseObj->getIsPublic() && !in_array($user_status, array(ADA_STATUS_COMPLETED, ADA_STATUS_TERMINATED)),
                'courseId' => $courseObj->getId(),
                'courseInstanceId' => (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) ? $courseInstanceObj->getId() : -1,
                'userId' => $userObj->getId(),
            ];

            // NOTE: the widget code will set the notified flag of the reward to true
            // so that the notification box will show one time only
            $layout_dataAR['widgets']['badges'] = [
                'isActive' => ModuleLoaderHelper::isLoaded('BADGES'),
                'courseId' => $courseObj->getId(),
                'courseInstanceId' => (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) ? $courseInstanceObj->getId() : -1,
                'userId' => $userObj->getId(),
            ];

            if ($self_instruction || $nodeObj->isForkedPaths) {
                //$self='viewSelfInstruction';
                $layout_dataAR['JS_filename'][] = ROOT_DIR . '/js/browsing/view.js';

                if (ModuleLoaderHelper::isLoaded('FORKEDPATHS') && $nodeObj->isForkedPaths) {
                    $newself = $self . 'ForkedPaths';
                    // self must be a relative path
                    $self = '/../../../../modules/' . basename(MODULES_FORKEDPATHS_PATH) . '/layout/' . $userObj->template_family . '/templates/browsing/' . $newself;
                    array_push($layout_dataAR['CSS_filename'], ROOT_DIR . '/layout/' . $userObj->template_family . '/css/browsing/view.css');
                    array_push($layout_dataAR['CSS_filename'], MODULES_FORKEDPATHS_PATH . '/layout/' . $userObj->template_family . '/css/browsing/' . $newself . '.css');
                    $content_dataAr['forkedPathsButtons'] = ForkedPathsNode::buildForkedPathsButtons($nodeObj)->getHtml();
                }
            }
        } else {
            $layout_dataAR['widgets']['courseStatus'] = [
                'isActive' => 0,
            ];
            $layout_dataAR['widgets']['badges'] = [
                'isActive' => 0,
            ];
        }

        if (ModuleLoaderHelper::isLoaded('COLLABORAACL')) {
            $layout_dataAR['widgets']['collaborafiles'] = [
                'courseId' => $courseObj->getId(),
                'courseInstanceId' => (isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance) ? $courseInstanceObj->getId() : -1,
                'nodeId' => $nodeObj->id,
                'userId' => $userObj->getId(),
                'doneCallback' => 'nodeAttachmentsDone',
            ];
            $layout_dataAR['JS_filename'][] = MODULES_COLLABORAACL_PATH . '/js/browsing/view.js';
        } else {
            $layout_dataAR['widgets']['collaborafiles'] = [
                'isActive' => 0,
            ];
        }

        $optionsAr['onload_func'] = 'initDoc();';

        if (isset($msg)) {
            $help = CDOMElement::create('label');
            $help->addChild(new CText(translateFN(ltrim($msg))));
            $divhelp = CDOMElement::create('div');
            $divhelp->setAttribute('id', 'help');
            $divhelp->addChild($help);
            $content_dataAr['help'] = $divhelp->getHtml();
        }
        $menuOptions['self_instruction'] = $self_instruction;
        $menuOptions['id_course'] = $sess_id_course;
        $menuOptions['id_course_instance'] = $sess_id_course_instance;
        $menuOptions['id_node'] = $sess_id_node;
        $menuOptions['id_parent'] = $sess_id_node;
        $menuOptions['id_student'] = $userObj->getId();
        $menuOptions['type'] = $nodeObj->type;

        // define to enable author menu items
        define('MODULES_TEST_MOD_ENABLED', ModuleLoaderHelper::isLoaded('TEST') && $mod_enabled);

        /**
         * this is modified here to test parameters passing on new menu
         */
        $content_dataAr['test_history'] = 'op=test&id_course_instance=' . $sess_id_course_instance . '&id_course=' . $sess_id_course;

        /**
         * Send cross orgin isolation headers
         * Only needed for zoom web sdk to work.
         */
        if ($nodeObj->hasZoomMeeting()) {
            Utilities::sendCrossOriginIsolation();
        }

        ARE::render($layout_dataAR, $content_dataAr, null, $optionsAr, $menuOptions);
}

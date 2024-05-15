<?php

use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout', 'course', 'course_instance'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

$self =  Utilities::whoami();
TutorHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */


$online_users_listing_mode = 2;
$id_course_instance = $courseInstanceObj->getId();
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);

$content_dataAr = [
    'course_title' => ucwords(translateFN('Log videochat')) . ' &gt; ' . $courseObj->getTitle() . ' &gt; ' . $courseInstanceObj->getTitle(),
    'user_name' => $user_name,
    'user_type' => $user_type,
    'edit_profile' => $userObj->getEditProfilePage(),
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'help'  => $help ?? null,
    // 'dati'  => $data,
    'status' => $status,
    'chat_users' => $online_users,
    'chat_link' => $chat_link ?? '',
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    ROOT_DIR . '/js/include/jquery/dataTables/formattedNumberSortPlugin.js',
    JQUERY_NO_CONFLICT,
];

$menuOptions = [];
if (isset($id_course)) {
    $menuOptions['id_course'] = $id_course;
}
if (isset($id_instance)) {
    $menuOptions['id_instance'] = $id_instance;
}
if (isset($id_instance)) {
    $menuOptions['id_course_instance'] = $id_instance;
}
if (isset($id_student)) {
    $menuOptions['id_student'] = $id_student;
}
/**
 * add a define for the supertutor menu item to appear
 */
if ($userObj instanceof ADAPractitioner && $userObj->isSuper()) {
    define('IS_SUPERTUTOR', true);
} else {
    define('NOT_SUPERTUTOR', true);
}

$optionsAr['onload_func'] = 'initDoc(' . $courseObj->getId() . ', ' . $courseInstanceObj->getId() . ');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr, $menuOptions);

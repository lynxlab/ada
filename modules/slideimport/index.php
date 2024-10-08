<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
ServiceHelper::init($neededObjAr);

$self = 'slideimport';

$layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
];

$content_dataAr = [
        'user_name'    => $user_name,
        'user_type'    => $user_type,
        'edit_profile' => $userObj->getEditProfilePage(),
        'status' => $status,
        'title' => translateFN('Importa Presentazione'),
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        MODULES_SLIDEIMPORT_PATH . '/js/jquery.lazyload.js',
        MODULES_SLIDEIMPORT_PATH . '/js/tree.jquery.js',
        JS_VENDOR_DIR . '/dropzone/dist/min/dropzone.min.js',
        JQUERY_NO_CONFLICT,
];

if (isset($_GET['id_course']) && intval($_GET['id_course']) > 0) {
    $course_id = intval($_GET['id_course']);
} else {
    $course_id = 0;
}

$optionsAr['onload_func'] = 'initDoc(' . $userObj->getId() . ', ' . $userObj->getType() . ', ' . $course_id . ', ' .
                            '\'' . MODULES_SLIDEIMPORT_UPLOAD_SESSION_VAR . '\');';

// clear session var
if (isset($_SESSION[MODULES_SLIDEIMPORT_UPLOAD_SESSION_VAR]['filename'])) {
    unset($_SESSION[MODULES_SLIDEIMPORT_UPLOAD_SESSION_VAR]['filename']);
}

if (isset($data)) {
    $content_dataAr['data'] = $data->getHtml();
}

$avatar = CDOMElement::create('img', 'class:img_user_avatar,src:' . $userObj->getAvatar());
$content_dataAr['user_avatar'] = $avatar->getHtml();
$content_dataAr['user_modprofilelink'] = $userObj->getEditProfilePage();

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

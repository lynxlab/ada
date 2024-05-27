<?php

use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Specific Openmeetings config file
 */
require_once 'include/videochat_config.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_STUDENT         => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
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

/**
 * Specific room object .
 */

//require_once ROOT_DIR.'/comunica/include/videoroom_classes.inc.php';

if ($_REQUEST['id_room']) {
    $id_room = $_REQUEST['id_room'];
}
/*
$id_profile = $userObj->getType();
  if ($id_profile==AMA_TYPE_TUTOR){
    $videoroomObj = new videoroom();
    //$videoroomObj->videoroomInfo($sess_id_course_instance);
    //if ($videoroomObj->full) {
    //  $id_room = $videoroomObj->id_room;
    $videoroomObj->server_login();
    $videoroomObj->deleteRoom($id_room);
    header('Location:'. HTTP_ROOT_DIR . '/tutor/eguidance_tutor_form.php?event_token='.$_GET['event_token']);
    exit();
  } else {
    $options_Ar = array('onload_func'=>"close_page('Good_bye');");
    $content = "";
    $content_dataAr = array (
        'data'      => $content
    );
    ARE::render($layout_dataAr,$content_dataAr,NULL,$options_Ar);
  }
 *
 */
$options_Ar = ['onload_func' => "close_page('Good_bye');"];
$content = "";
$content_dataAr =  [
    'data'      => $content,
];
ARE::render($layout_dataAr, $content_dataAr, null, $options_Ar);

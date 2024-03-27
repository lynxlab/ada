<?php

######################
#
#    REDIRECT
#
######################
/**
 * Base config file
 */

use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */


$variableToClearAR = ['node','layout', 'user', 'course'];

/**
 * Get needed objects
 */
$neededObjAr = ['layout','user'];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = 'index';



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
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/**
 * Redirecting on user type basis
 */
if (is_object($userObj)) {
    $homepage = $userObj->getHomepage();
    $msg =   translateFN("Ridirezionamento automatico");
    header("Location: $homepage?err_msg=$msg");
    exit;
} else {
    $homepage = $http_root_dir . "/index.php";
    $msg =  urlencode($errObj->msg);
    header("Location: $homepage?err_msg=$msg");
    exit;
}

// old version
/*
if (is_object($userObj)){
        switch ($id_profile){
        case AMA_TYPE_STUDENT:
              $homepage = "$http_root_dir/browsing/user.php";
              $msg =   translateFN("Ridirezionamento automatico");
              header("Location: $homepage?err_msg=$msg");
              exit;
              break;
        case AMA_TYPE_TUTOR:
              $homepage = "$http_root_dir/tutor/tutor.php";
              $msg =   translateFN("Ridirezionamento automatico");
              header("Location: $homepage?err_msg=$msg");
              exit;
              break;
        case AMA_TYPE_AUTHOR:
              $homepage = "$http_root_dir/services/author.php";
              $msg =   translateFN("Ridirezionamento automatico");
              header("Location: $homepage?err_msg=$msg");
              exit;
              break;
        case AMA_TYPE_SWITCHER:
              $homepage = "$http_root_dir/switcher/switcher.php";
              $msg =   translateFN("Ridirezionamento automatico");
              header("Location: $homepage?err_msg=$msg");
              exit;
              break;
        case AMA_TYPE_ADMIN:
              // vito, 9 mar 2009
              //$homepage = "$http_root_dir/admin/menu.php";
              $homepage = "$http_root_dir/admin/admin.php";
              $msg =   translateFN("Ridirezionamento automatico");
              header("Location: $homepage?err_msg=$msg");
              exit;
              break;
        }
} else {
     $homepage = $http_root_dir."/index.php";
     $msg =  urlencode($errObj->msg);
     header("Location: $homepage?err_msg=$msg");
     exit;

}
*/

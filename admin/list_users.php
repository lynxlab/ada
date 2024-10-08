<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGenericUser;
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
$allowedUsersAr = [AMA_TYPE_ADMIN];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_ADMIN => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();  // = admin!

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
AdminHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */
$id_tester = DataValidator::checkInputValues('id_tester', 'Integer', INPUT_GET);
$page = DataValidator::checkInputValues('page', 'Integer', INPUT_GET, 1);
$userTypeToFilter = DataValidator::checkInputValues('user_type', 'Integer', INPUT_GET);


$users_per_page = 20;

if ($id_tester !== false) {
    $tester_info = $common_dh->getTesterInfoFromId($id_tester);
    $tester_dsn = MultiPort::getDSN($tester_info[10]);
    if ($tester_dsn != null) {
        $tester_dh = AMADataHandler::instance($tester_dsn);

        if ($userTypeToFilter !== false) {
            $user_typesAr = [$userTypeToFilter];
        } else {
            $user_typesAr = [AMA_TYPE_STUDENT,AMA_TYPE_AUTHOR,AMA_TYPE_TUTOR,AMA_TYPE_SWITCHER,AMA_TYPE_ADMIN,AMA_TYPE_SUPERTUTOR];
        }
        $users_count = $tester_dh->countUsersByType($user_typesAr);
        if (AMADataHandler::isError($users_count)) {
            $errObj = new ADAError($users_count);
        } else {
            $users_dataAr = $tester_dh->getUsersByType($user_typesAr, true);
            if (AMADataHandler::isError($users_dataAr)) {
                $user_type = ADAGenericUser::convertUserTypeFN($userTypeToFilter);
                $data = CDOMElement::create('div');
                $data->addChild(new CText(translateFN('No user of type ') . $user_type));
                //        $errObj = new ADAError($users_dataAr);
            } else {
                $data = AdminModuleHtmlLib::displayUsersOnThisTester($id_tester, null, null, $users_dataAr, false);
            }
        }
    }
} else {
    /*
     * non e' stato passato id_tester
     */
}

$label = translateFN("Lista degli utenti presenti sul provider");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));
$tester_profile_link = CDOMElement::create('a', 'href:tester_profile.php?id_tester=' . $id_tester);
$tester_profile_link->addChild(new CText(translateFN("Profilo del provider")));
$module = $home_link->getHtml() . ' > ' . $tester_profile_link->getHtml() . ' > ' . $label;

$help  = translateFN("Lista degli utenti presenti sul provider");

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $data->getHtml(),
  'module'       => $module,
];
$menuOptions['id_tester'] = $id_tester;

$layout_dataAr['JS_filename'] = [
  JQUERY,
  JQUERY_UI,
  JQUERY_DATATABLE,
  SEMANTICUI_DATATABLE,
  JQUERY_DATATABLE_DATE,
  JQUERY_NO_CONFLICT,
];

$layout_dataAr['CSS_filename'] = [
  JQUERY_UI_CSS,
  SEMANTICUI_DATATABLE_CSS,
];
$render = null;
$options['onload_func'] = 'initDoc(' . (($userObj->getType() == AMA_TYPE_ADMIN) ? 1 : 0) . ')';

ARE::render($layout_dataAr, $content_dataAr, $render, $options, $menuOptions);

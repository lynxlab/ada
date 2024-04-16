<?php

/**
 * ZOOM TUTOR.
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

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
  AMA_TYPE_SWITCHER => ['layout'],
];


require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  'switcher';  // = switcher!

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
SwitcherHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
$tutor_id = DataValidator::isUinteger($_GET['id']);
if ($tutor_id == false) {
    header('Location: ' . $userObj->getHomePage());
    exit();
}

$tutor_ha = $dh->getTutor($tutor_id);
if (AMADataHandler::isError($tutor_ha)) {
    $errObj = new ADAError($tutor_ha, translateFN('An error occurred while reading tutor data.'));
}

//$tutored_users_number = $dh->getNumberOfTutoredUsers($id);
$tutored_user_ids = $dh->getTutoredUserIds($id);
if (AMADataHandler::isError($tutored_user_ids)) {
    $errObj = new ADAError($tutored_user_ids, translateFN('An error occurred while reading tutored user ids'));
}

$number_of_active_tutored_users = $common_dh->getNumberOfUsersWithStatus($tutored_user_ids, ADA_STATUS_REGISTERED);
if (AMACommonDataHandler::isError($number_of_active_tutored_users)) {
    $errObj = new ADAError($number_of_active_tutored_users, translateFN('An error occurred while retrieving the number of active tutored users.'));
}

$tutor_ha['utenti seguiti'] = $number_of_active_tutored_users;

unset($tutor_ha['tipo']);
unset($tutor_ha['layout']);
unset($tutor_ha['tariffa']);
unset($tutor_ha['codice_fiscale']);

$data = BaseHtmlLib::plainListElement('', $tutor_ha);


$status = translateFN('Caratteristiche del practitioner');

// preparazione output HTML e print dell' output
$title = translateFN('ADA - dati epractitioner');

$content_dataAr = [
  'menu'      => $menu,
  'dati'      => $data->getHtml(),
  'help'      => $help,
  'status'    => $status,
  'user_name' => $user_name,
  'edit_profile' => $userObj->getEditProfilePage(),
  'user_type' => $user_type,
  'messages'  => $user_messages->getHtml(),
  'agenda'    => $user_agenda->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

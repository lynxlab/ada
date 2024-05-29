<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classagenda
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;
use Lynxlab\ADA\Module\Classagenda\CalendarsManagement;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = 'calendars';

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$calendarsManager = new CalendarsManagement();
$data = $calendarsManager->run(MODULES_CLASSAGENDA_EDIT_CAL);

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'help' => $data['help'],
    'title' => $data['title'],
    'data' => $data['htmlObj']->getHtml(),
];

$layout_dataAr['JS_filename'] =  [JQUERY, JQUERY_UI];
$layout_dataAr['CSS_filename'] = [JQUERY_UI_CSS];

// NOTE: if i18n file is not found it'll be discarded by the rendering engine
array_push($layout_dataAr['JS_filename'], MODULES_CLASSAGENDA_PATH . '/js/vendor/moment/min/moment.min.js');
array_push($layout_dataAr['JS_filename'], MODULES_CLASSAGENDA_PATH . '/js/vendor/fullcalendar/dist/fullcalendar.js');
array_push($layout_dataAr['JS_filename'], MODULES_CLASSAGENDA_PATH . '/js/vendor/fullcalendar/dist/locale/' . $_SESSION['sess_user_language'] . '.js');
array_push($layout_dataAr['JS_filename'], MODULES_CLASSAGENDA_PATH . '/js/vendor/fullcalendar/dist/gcal.js');
array_push($layout_dataAr['CSS_filename'], MODULES_CLASSAGENDA_PATH . '/js/vendor/fullcalendar/dist/fullcalendar.min.css');

//  $optionsAr ['onload_func'] = 'initDoc(\''.htmlentities(json_encode($datetimesAr)).'\',\''.htmlentities(json_encode($inputProposalNames)).'\','.MAX_PROPOSAL_COUNT.');';

$optionsAr['onload_func'] = 'initDoc(' . $userObj->getType() . ');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

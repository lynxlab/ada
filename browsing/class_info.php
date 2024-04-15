<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\ARE;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\Course\CourseInstance;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

/*
error_reporting(E_ALL);
ini_set('display_errors', '1');
*/

/**
 * CLASS INFO
 *
 * @package     class_info
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        user
 * @version     0.1
 */

use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

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
$allowedUsersAr = [AMA_TYPE_STUDENT];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'default_tester'],
];
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();

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

// ini_set ("display_errors","1"); error_reporting(E_ALL);

//print_r($_SESSION);
/*
$courseInstances = array();
$serviceProviders = $userObj->getTesters();

if (count($serviceProviders) == 1) {
    $provider_dh = AMADataHandler::instance(MultiPort::getDSN($serviceProviders[0]));
    $courseInstances = $provider_dh->getCourseInstancesForThisStudent($userObj->getId());
} else {
    foreach ($serviceProviders as $Provider) {
        $provider_dh = AMADataHandler::instance(MultiPort::getDSN($Provider));
        $GLOBALS['dh'] = $provider_dh;
        $courseInstances = $provider_dh->getCourseInstancesForThisStudent($userObj->getId());
    }
}
 *
 */
$id_course = (isset($_GET['id_course']) && intval($_GET['id_course']) >= 0) ? intval($_GET['id_course']) : -1;
$providerAr = $common_dh->getTesterInfoFromIdCourse($id_course);
$client = $providerAr['puntatore'];
$provider_dh = AMADataHandler::instance(MultiPort::getDSN($client));
$GLOBALS['dh'] = $provider_dh;
$courseInstances = $provider_dh->getCourseInstancesForThisStudent($userObj->getId());

if (!AMADataHandler::isError($courseInstances)) {
    $found = count($courseInstances);
    $data = "";
    if (isset($id_course_instance) and isset($id_course)) {
        $stud_status = ADA_STATUS_SUBSCRIBED; //only subscribed students
        $students =  $provider_dh->courseInstanceStudentsPresubscribeGetList($id_course_instance, $stud_status);
        $student_listHa = [];
        foreach ($students as $one_student) {
            $id_stud = $one_student['id_utente_studente'];
            if ($provider_dh->getUserType($id_stud) == AMA_TYPE_STUDENT) {
                $studn = $provider_dh->getStudent($id_stud); // var_dump($studn);
                $row = [
                       // $studn['username'], è uguale all'email
                $studn['nome'],
                $studn['cognome'],
                       $studn['email'],
                ];
                array_push($student_listHa, $row);
            }
        }
        $tObj = new Table();
        $tObj->initTable('1', 'center', '0', '1', '100%', '', '', '', '', '0', '1');
        // Syntax: $border,$align,$cellspacing,$cellpadding,$width,$col1, $bcol1,$col2, $bcol2
        $caption = "<strong>" . translateFN("Elenco degli iscritt* al corso ") . "</strong>";
        $summary = translateFN("Elenco degli iscritt* al corso ");
        $tObj->setTable($student_listHa, $caption, $summary);

        $data = $tObj->getTable();
        $data = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $data, 1); // replace first occurence of class
    } else {
        $data =   translateFN("Errore nei dati");
    }
} else {
    $data = translateFN('Non sei iscritto a nessuna classe');
}

/*
 * Last access link
 */

if (isset($_SESSION['sess_id_course_instance'])) {
    $last_access = $userObj->getLastAccessFN(($_SESSION['sess_id_course_instance']), "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
} else {
    $last_access = $userObj->getLastAccessFN(null, "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
}
if ($last_access == '' || is_null($last_access)) {
    $last_access = '-';
}
/*
 * Output
 */
$content_dataAr = [
    'today' => $ymdhms,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'last_visit' => $last_access,
    'edit_profile' => $userObj->getEditProfilePage(),
    'message' => $message,
    'user_level' => $user_level,
//    'iscritto' => $sub_course_data,
//    'iscrivibili' => $to_sub_course_data,
    'course_title' => translateFN("Home dell'utente"),
//    'corsi' => $corsi,
//    'profilo' => $profilo,

    // 'data' => $data->getHtml(),

    'data' => $data,

    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'events' => $user_events->getHtml(),
    'status' => $status,
];

ARE::render($layout_dataAr, $content_dataAr);

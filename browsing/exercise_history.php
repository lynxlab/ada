<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\ARE;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use Lynxlab\ADA\Main\ADAError;

use function \translateFN;

/**
 * STUDENT EXERCISE HISTORY
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Valerio Riva <valerio.riva@gmail.com>
 * @copyright           Copyright (c) 2009-2011, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;
use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use function Lynxlab\ADA\Main\AMA\DBRead\readNodeFromDB;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['node', 'layout', 'tutor', 'course', 'course_instance'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();

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

/*
 * YOUR CODE HERE
 */
$history = '';

if (!isset($op)) {
    $op = 'default';
}

switch ($op) {
    case 'exe':
        $user_answer = $dh->getExHistoryInfo($id_exe);
        if (AMADataHandler::isError($user_answer)) {
            //print("errore");
            $errObj = new ADAError($user_answer, translateFN("Errore nell'ottenimento della risposta utente"));
        }

        $node            = $user_answer['node_id'];
        //$student_id      = $user_answer['student_id'];
        //$course_instance = $user_answer['course_id'];
        $id_student      = $user_answer['student_id'];
        $id_course_instance = $user_answer['course_id'];

        $exercise = ExerciseDAO::getExercise($node, $id_exe);

        $_SESSION['exercise_object'] = serialize($exercise);

        if (AMADataHandler::isError($exercise)) {
            //print("errore");
            $errObj = new ADAError($exercise, translateFN("Errore nella lettura dell'esercizio"));
        }
        $viewer  = ExerciseViewerFactory::create($exercise->getExerciseFamily());
        $history = $viewer->getExerciseHtml($exercise);
        $status = translateFN('Esercizio dello studente');
        break;
    case 'list':
    case 'default':
        // lettura dei dati dal database
        // Seleziona gli esercizi dello studente selezionato nel corso selezionato

        $userObj->getExerciseDataFN($id_course_instance, $userObj->getId()) ;

        // Esercizi svolti e relativi punteggi
        $history .= '<p>';
        $history .= $userObj->historyExDoneFN($userObj->id_user, AMA_TYPE_STUDENT, $id_course_instance) ;
        $history .= '</p>';
        $status = translateFN('Esercizi dello studente');

        break;
}
// CHAT, BANNER etc


// Costruzione del link per la chat.
// per la creazione della stanza prende solo la prima parola del corso (se piu' breve di 24 caratteri)
// e ci aggiunge l'id dell'istanza corso
$help = translateFN("Da qui lo studente può rivedere i propri esercizi.");

if (!isset($status)) {
    $status = '';
}

$courseInstanceObj = new CourseInstance($id_course_instance);
$courseObj = new Course($courseInstanceObj->id_corso);
$course_title = $courseObj->titolo;
//show course istance name if isn't empty - valerio
if (!empty($courseInstanceObj->title)) {
    $course_title .= ' - ' . $courseInstanceObj->title;
}

if (!is_object($nodeObj)) {
    $nodeObj = readNodeFromDB($node);
}
if (!ADAError::isError($nodeObj) and isset($courseObj->id)) {
    $_SESSION['sess_id_course'] = $courseObj->id;
    $node_path = $nodeObj->findPathFN();
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


$content_dataAr = [
    'course_title' => translateFN('Storico Esercizi') . ' > <a href="main_index.php">' . $course_title . '</a>',
    'path' => $node_path,
    // 'class'=>$class . ' ' . translateFN('iniziata il') . ' ' . $start_date,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'student' => $userObj->getFullName(),
    'level' => $userObj->livello,
    'edit_profile' => $userObj->getEditProfilePage(),
    'data' => $history,
    'user_level' => $user_level,
    'last_visit' => $last_access,
    'help' => $help,
    'status' => $status,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

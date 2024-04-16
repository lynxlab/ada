<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Services\Exercise\ADAEsercizio;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;
use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use function Lynxlab\ADA\Main\AMA\DBRead\readNodeFromDB;
use function Lynxlab\ADA\Main\AMA\DBRead\readUserFromDB;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

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
    AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();

/*
 * YOUR CODE HERE
 */

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
TutorHelper::init($neededObjAr);

$history = '';

$studentObj = readUserFromDB($id_student);
if ((is_object($studentObj)) && (!AMA_dataHandler::isError($studentObj))) {
    if ($studentObj instanceof ADAPractitioner) {
        /**
         * @author giorgio 14/apr/2015
         *
         * If student is actually a tutor, build a new student
         * object for history and evaluation purposes
         */
        $studentObj = $studentObj->toStudent();
    }
    $id_profile_student = $studentObj->getType();
    $student_type = $studentObj->convertUserTypeFN($id_profile_student);
    $user_name_student =  $studentObj->getUserName();
    $student_name = $studentObj->getFullName();
    $student_level = $studentObj->livello;
} else {
    $errObj = new ADA_error(translateFN('Utente non trovato'), translateFN('Impossibile proseguire.'));
}

//if (isset($button)) {
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    //$op = 'default';

    if (!isset($_SESSION['exercise_object'])) {
        //print("errore");
        $errObj = new ADAError(null, translateFN('Esercizio non in sessione'));
    }

    $exercise = unserialize($_SESSION['exercise_object']);

    if (!($exercise instanceof ADAEsercizio)) {
        $errObj = new ADAError(null, translateFN("L'oggetto in sessione non è un esercizio ADA"));
    }

    if (isset($ripetibile)) {
        $exercise->setRepeatable(true);
    }

    if (isset($punteggio)) {
        $exercise->setRating($punteggio);
    }

    $exercise->setTutorComment($comment);

    if (!ExerciseDAO::save($exercise)) {
        //print("Errore nel salvataggio dell'esercizio<BR>");
        $errObj = new ADAError(null, translateFN("Errore nel salvataggio dell'esercizio"));
    }

    unset($_SESSION['exercise_object']);

    if (isset($messaggio)) {
        $studentObj = readUserFromDB($exercise->getStudentId());
        // controllo errore
        $subject = translateFN('Esercizio: ') . $exercise->getTitle() . "\n";

        //if ($exe_type == 4) $testo .= "\n" .$correzione;
        /*
         * I problemi potrebbero verificarsi qui.
         */
        $message_ha = [
            'destinatari' => $studentObj->getUserName(),
            'data_ora' => 'now',
            'tipo' => ADA_MSG_SIMPLE,
            'mittente' => $user_uname,
            'testo' => $comment,
            'titolo' => $subject,
            'priorita' => 2,
        ];
        $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
        $result = $mh->sendMessage($message_ha);
        if (AMADataHandler::isError($result)) {
            // GESTIRE ERRORE
        }
    }
    $history = 'fine della correzione<br />';

    header("Location: tutor_exercise.php?op=list&id_student=$student_id&id_instance=$course_instance");
    exit();
}

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
        $history = $viewer->getTutorForm("$self.php", $exercise);

        $menu_03 = "<a href=" .  $http_root_dir . "/tutor/tutor_exercise.php?op=list&id_student=" . $id_student;
        $menu_03 .= "&id_instance=" . $id_course_instance . ">";
        $menu_03 .= translateFN('Elenco esercizi') . "</a>";
        $status = translateFN('Esercizio dello studente');
        break;
    case 'list':
    case 'default':
        // lettura dei dati dal database
        // Seleziona gli esercizi dello studente selezionato nel corso selezionato

        $studentObj->getExerciseDataFN($id_course_instance, $id_student);

        // Esercizi svolti e relativi punteggi
        $history .= '<p>';
        $history .= $studentObj->historyExDoneFN($id_student, null, $id_course_instance);
        $history .= '</p>';
        $status = translateFN('Esercizi dello studente');
        $layout_dataAr['JS_filename'] = [
            JQUERY,
            JQUERY_DATATABLE,
            SEMANTICUI_DATATABLE,
            JQUERY_DATATABLE_DATE,
            JQUERY_NO_CONFLICT,
        ];
        $layout_dataAr['CSS_filename'] = [
            SEMANTICUI_DATATABLE_CSS,
        ];
        $optionsAr['onload_func'] = 'dataTablesExec()';
}
// CHAT, BANNER etc


// Costruzione del link per la chat.
// per la creazione della stanza prende solo la prima parola del corso (se piu' breve di 24 caratteri)
// e ci aggiunge l'id dell'istanza corso
$help = translateFN("Da qui il tutor del corso può vedere e correggere gli esercizi degli studenti. Può anche inviare un commento e/o decidere di far ripetere l'esercizio allo studente.");

if (!isset($menu_03)) {
    $menu_03 = '';
}
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

if (!isset($nodeObj) || !is_object($nodeObj)) {
    if (!isset($node)) {
        $node = null;
    }
    $nodeObj = readNodeFromDB($node);
}
if (!ADAError::isError($nodeObj) and isset($courseObj->id)) {
    $_SESSION['sess_id_course'] = $courseObj->id;
    $node_path = $nodeObj->findPathFN();
}
$content_dataAr = [
    'course_title' => translateFN('Modulo tutor') . ' > <a href="' . HTTP_ROOT_DIR . '/browsing/main_index.php">' . $course_title . '</a>',
    'path' => $node_path ?? '',
    'class' => (isset($class) && isset($start_date)) ? $class . ' ' . translateFN('iniziata il') . ' ' . $start_date : '',
    'user_name' => $user_name,
    'user_type' => $user_type,
    'student' => $student_name,
    'level' => $student_level,
    'data' => $history,
    'menu_03' => $menu_03,
    'menu_04' => '', //$chat_link,
    'help' => $help,
    'status' => $status,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
];

$menuOptions['id_instance'] = $id_course_instance;
$menuOptions['id_student'] = $id_student;

if (!isset($optionsAr)) {
    ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions);
} else {
    //    print_r($optionsAr);
    ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr, $menuOptions);
}

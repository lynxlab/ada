<?php

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Test\NodeTest;
use Lynxlab\ADA\Services\Exercise\ExerciseCorrectionFactory;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;
use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_VISITOR      => ['node','layout','course'],
        AMA_TYPE_STUDENT         => ['node','layout','tutor','course','course_instance'],
        AMA_TYPE_TUTOR => ['node','layout','course','course_instance'],
        AMA_TYPE_AUTHOR       => ['node','layout','course'],
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
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADAUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

$id_node = $nodeObj->id;

//redirect to test module if necessary
if (MODULES_TEST && ADA_REDIRECT_TO_TEST && str_starts_with($nodeObj->type, (string) constant('ADA_PERSONAL_EXERCISE_TYPE'))) {
    NodeTest::checkAndRedirect($nodeObj);
}
if (!isset($op)) {
    $op = null;
}
switch ($op) {
    case 'answer':
        if (isset($useranswer)) {
            $exercise   = ExerciseDAO::getExercise($id_node);

            $correttore = ExerciseCorrectionFactory::create($exercise->getExerciseFamily());
            $correttore->rateStudentAnswer($exercise, $useranswer, $sess_id_user, $sess_id_course_instance);

            /*
             * salviamo l'esercizio svolto solo se l'utente che lo ha svolto
             * e' uno studente, altrimenti si tratta di un autore o di un tutor che ha
             * testato l'esercizio.
             */
            if ($id_profile == AMA_TYPE_STUDENT) {
                if (!ExerciseDAO::save($exercise)) {
                    $errObj = new ADAError(null, translateFN('Errore nel salvataggio della risposta utente'));
                }

                // se l'esercizio appena corretto è un esercizio di sbarramento e lo studente lo ha superato,
                // aumenta di uno il livello dello studente
                if ($correttore->raiseUserLevel($exercise)) {
                    $result = $dh->raiseStudentLevel($sess_id_user, $sess_id_course_instance, 1);
                    if (AMADataHandler::isError($result)) {
                        $errObj = new ADAError($result, translateFN("Errore nell'aggiornamento dati utente"));
                    }
                    //$new_user_level = $user_level + 1;
                    $new_user_level = $userObj->getStudentLevel($sess_id_user, $sess_id_course_instance);
                    //$max_level = ADA_MAX_USER_LEVEL; // da config_install.inc.php
                    $max_level = $dh->getCourseMaxLevel($sess_id_course);
                    if ($new_user_level >= $max_level) {
                        // se è l'ultimo esercizio (ovvero se il livello dello studente è il massimo possibile)
                        // e l'esercizio è di tipo sbarramento?
                        // genera il messaggio da inviare allo switcher
                        $tester = $userObj->getDefaultTester();
                        $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
                        $tester_info_Ar = $dh->getTesterInfoFromPointer($tester); // common?
                        $tester_name = $tester_info_Ar[1];
                        $switchers_Ar = $tester_dh->getUsersByType([AMA_TYPE_SWITCHER]);
                        if (AMADataHandler::isError($switchers_Ar) || !is_array($switchers_Ar)) {
                            // ??
                        } else {
                            $switcher_id = $switchers_Ar[0];
                            //
                            /* FIXME:
                             * only the firset switcher per provider !
                             */
                            if ($switcher_id) {
                                $switcher = $dh->get_switcher($switcher_id);
                                if (!AMADataHandler::isError($switcher)) {
                                    // prepare message to send
                                    $message_ha['destinatari'] = $switcher['username'];
                                    $message_ha['titolo'] = translateFN("Completamento corso") . "<br>";

                                    //$message_ha['testo'] = $correttore->getMessageForTutor($user_name, $exercise);
                                    /* FIXME
                                     * should be a function of ExerciseCorrectionFactory??
                                     */
                                    $message_ha['testo'] = translateFN("Il corsista") . " $user_name " . translateFN("ha terminato il corso con id") . " " . $sess_id_course . "/" . $sess_id_course_instance;
                                    $message_ha['data_ora'] = "now";
                                    $message_ha['tipo'] = ADA_MSG_SIMPLE;
                                    $message_ha['priorita'] = 1;
                                    $message_ha['mittente'] = $user_name;
                                    $mh = new MessageHandler();
                                    $mh->sendMessage($message_ha);
                                }
                            }
                        }

                        // genera il messaggio da inviare al tutor
                        // codice precedente
                        $tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);
                        if (AMADataHandler::isError($tutor_id)) {
                            //?
                        }
                        // only one tutor per class
                        if ($tutor_id) {
                            $tutor = $dh->getTutor($tutor_id);
                            if (!AMADataHandler::isError($tutor)) {
                                // prepare message to send
                                $message_ha['destinatari'] = $tutor['username'];
                                $message_ha['titolo'] = translateFN("Esercizio svolto da ") . $user_name . "<br>";

                                $message_ha['testo'] = $correttore->getMessageForTutor($user_name, $exercise);

                                $message_ha['data_ora'] = "now";
                                $message_ha['tipo'] = ADA_MSG_SIMPLE;
                                $message_ha['priorita'] = 1;
                                $message_ha['mittente'] = $user_name;
                                $mh = new MessageHandler();
                                $mh->sendMessage($message_ha);
                            }
                        }
                    } // max level attained
                }
            }
            // genera il messaggio per lo studente
            // $dataHa['exercise'] = $correttore->getMessageForStudent($user_name, $exercise);
            $message = $correttore->getMessageForStudent($user_name, $exercise);
            $dataHa['exercise'] = $message->getHtml();



            // ottiene il prossimo esercizio da svolgere, se previsto.
            $next_exercise_id = ExerciseDAO::getNextExerciseId($exercise, $sess_id_user);
            if (AMADataHandler::isError($next_exercise_id)) {
                $errObj = new ADAError($next_exercise_id, translateFN('Errore nel caricamento del prossimo esercizio'));
            } elseif ($next_exercise_id) {
                $dataHa['exercise'] .= "<a href=\"$http_root_dir/browsing/exercise.php?id_node=$next_exercise_id\">";
                $dataHa['exercise'] .= translateFN('Prossimo esercizio') . '</a>';
            }
        }
        break;
    case 'view':
    default:
        $exercise = ExerciseDAO::getExercise($id_node);
        if ($user_level < $exercise->getExerciseLevel()) {
            $form = translateFN("Esercizio di livello superiore al tuo");
        } else {
            $viewer   = ExerciseViewerFactory::create($exercise->getExerciseFamily());
            $action = 'exercise.php';
            $form = $viewer->getViewingForm($userObj, $exercise, $sess_id_course_instance, $action);

            // vito 26 gennaio 2009
            if (($id = ExerciseDAO::getNextExerciseId($exercise, $sess_id_user)) != null) {
                $next_exercise_menu_link = CDOMElement::create('a');
                $next_exercise_menu_link->setAttribute('href', "$http_root_dir/browsing/exercise.php?id_node=$id");
                $next_exercise_menu_link->addCHild(new CText(translateFN('Prossimo esercizio')));
                $dataHa['go_back'] .= $next_exercise_menu_link->getHtml();
            }
        }
        $dataHa['exercise'] = $form;
        $node_title = $exercise->getTitle();
        $icon = CourseViewer::getCSSClassNameForExerciseType($exercise->getExerciseFamily());
        break;
}

/*
 * Actions menu
*/
if ($id_profile == AMA_TYPE_AUTHOR) {
    /*
     * build onclick event for new menu.
    */

    $link   = HTTP_ROOT_DIR . '/services/edit_exercise.php?op=delete';
    $text   = addslashes(translateFN('Confermi cancellazione esercizio?'));
    $onclick = "confirmCriticalOperationBeforeRedirect('$text','$link')";
} else {
    $edit_exercise = new CText('');
}

/*
 * Output
 */
$content_dataAr = [
        'path' => $nodeObj->findPathFN(),
        'user_name' => $user_uname,
        'user_type' => $user_type,
        'user_level' => $user_level,
        'visited' => '-',
        'icon' => $icon ?? '',
        'text' => $dataHa['exercise'] ?? null,
        'onclick' => $onclick,
        'title' => $nodeObj->name,
        'author' => $nodeObj->author['username'],
        'node_level' => 'livello nodo',
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        //'course_title' => '',
        //'media' => 'media',
];

$menuOptions['id_node'] = $id_node;
ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions);

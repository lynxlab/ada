<?php

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;
use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node','layout'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout','node'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

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
ServiceHelper::init($neededObjAr);

$self =  whoami();
/*
 * YOUR CODE HERE
*/
$id_node = $nodeObj->id;

if (!isset($op)) {
    $op = 'do';
}

/*
if (!isset($status)) {
    if (isset($msg)) {
        $status = $msg;
    } else {
        $status = translateFN(" Modifiche esercizio");
    }
}
*/


switch ($op) {
    case 'edit':
        /*
           * posso modificare l'esercizio solo se:
           * 1. nessuna istanza di corso attiva
           * 2. istanza del corso attiva, ma nessuno studente ha già svolto l'esercizio.
           *
           * quindi deve:
           * 1. ottieni istanze corso attive (esiste già il metodo AMA?)
           * 2. se istanza corso attiva, verifica se esiste studente che ha svolto esercizio
           * in questa istanza corso.
           * se tutto ok, mostra il form per la modifica dell'esercizio,
           * altrimenti mostra un messaggio che spiega perche' non e' possibile modificare l'esercizio.
           *
           * conviene implementare un nuovo metodo per ExerciseViewer che si occupa di mostrare il
           * form per la modifica dell'esercizio.
        */

        /*
           * 1. mostra il form contenente l'esercizio. Ciò che è modificabile ha un link modifica.
           * 2. al click su un link modifica, viene mostrato il form per la modifica dell'elemento
           *    selezionato.
           *    se l'utente clicca su salva modifica, la modifica viene salvata e si ritorna a 1.
           *    se l'utente clicca su annulla modifica, si torna a 1
        */  $status = translateFN('Modifiche esercizio');

        $edit_form_base_action = "$self.php?op=edit";

        /*
       * The exercise object is stored in a session variable, in order
       * to update it as soon as the author updates exercise data during editing.
       *
       * If the exercise is not in the session variable, it means that it's the first
       * load of the editing form, so there's the need to check if the author can
       * modify this exercise. If this is possible, store the exercise in session and
       * present the exercise editing form. Otherwise a message will be shown.
        */
        $navigation_history = $_SESSION['sess_navigation_history'];

        $need_to_unset_session = strcmp($navigation_history->previousItem(), __FILE__);

        if (!isset($_SESSION['sess_edit_exercise']['exercise'])  || $need_to_unset_session !== 0) {
            if ($need_to_unset_session !== 0) {
                unset($_SESSION['sess_edit_exercise']);
            }
        }

        if (!isset($_SESSION['sess_edit_exercise']['exercise'])) {
            $exercise = ExerciseDAO::getExercise($id_node);

            if (!ExerciseDAO::canEditExercise($id_node)) {
                $dataHa['exercise'] = translateFN("L'esercizio non può essere modificato.");

                $icon = CourseViewer::getCSSClassNameForExerciseType($exercise->getExerciseFamily());
                break;
            }

            $_SESSION['sess_edit_exercise']['exercise'] = serialize($exercise);
        } else {
            $exercise = unserialize($_SESSION['sess_edit_exercise']['exercise']);
        }

        if (isset($edit)  /*&&!empty($edit)*/) {
            $field = $edit;
            $viewer = ExerciseViewerFactory::create($exercise->getExerciseFamily());
            if (isset($add) && !empty($add)) {
                $form = $viewer->getAddAnswerForm($edit_form_base_action, $exercise, $field);
            } else {
                $form = $viewer->getEditFieldForm($edit_form_base_action, $exercise, $field);
            }
            $dataHa['exercise'] = $form->getHtml();

            $icon = CourseViewer::getCSSClassNameForExerciseType($exercise->getExerciseFamily());
        } elseif (isset($update) && !empty($update)) {
            $field = $update;

            $exercise->updateExercise($_POST);

            if (!ExerciseDAO::save($exercise)) {
                $errObj = new ADAError(null, translateFN("Errore nel salvataggio delle modifiche apportate all'esercizio"));
            } else {
                /*
             * Update the session variable too.
                */
                $_SESSION['sess_edit_exercise']['exercise'] = serialize($exercise);

                header("Location: $edit_form_base_action");
                exit();
            }
        } elseif (isset($add_answer_to) && !empty($add_answer_to)) {
            $position = $_POST['position'] - 1;

            ExerciseDAO::addAnswer($exercise, $_POST);
            $id = $exercise->getId();
            unset($_SESSION['sess_edit_exercise']['exercise']);
            $exercise = null;
            $exercise = ExerciseDAO::getExercise($id);
            $_SESSION['sess_edit_exercise']['exercise'] = serialize($exercise);

            header("Location: $edit_form_base_action");
            exit();
        } elseif (isset($delete) && !empty($delete)) {
            $node_id = $delete;

            $exercise->deleteDataItem($node_id);

            if (!ExerciseDAO::save($exercise)) {
                $errObj = new ADAError(null, translateFN("Errore nel salvataggio delle modifiche apportate all'esercizio"));
            } else {
                /*
             * Update the session variable too.
                */
                $_SESSION['sess_edit_exercise']['exercise'] = serialize($exercise);

                header("Location: $edit_form_base_action");
                exit();
            }
        } elseif (isset($save) && !empty($save)) {
            unset($_SESSION['sess_edit_exercise']['exercise']);
            //        if ( !ExerciseDAO::save($exercise) ) {
            //          $errObj = new ADAError(NULL, translateFN("Errore nel salvataggio delle modifiche apportate all'esercizio"));
            //       }
            //        else {
            header('Location: ' . HTTP_ROOT_DIR . '/browsing/exercise.php?op=view');
            exit();
            //        }
        } else { //edit form base action
            $viewer = ExerciseViewerFactory::create($exercise->getExerciseFamily());

            $form = $viewer->getEditForm($edit_form_base_action, $exercise);

            $dataHa['exercise'] = $form->getHtml();
            $icon = CourseViewer::getCSSClassNameForExerciseType($exercise->getExerciseFamily());
        }

        break;

    case 'delete':
        /*
           * posso cancellare l'esercizio solo se:
           * 1. nessuna istanza di corso attiva
           * 2. istanza del corso attiva, ma nessuno studente ha già svolto l'esercizio.
           *
           * quindi deve:
           * 1. ottieni istanze corso attive (esiste già il metodo AMA?)
           * 2. se istanza corso attiva, verifica se esiste studente che ha svolto esercizio
           * in questa istanza corso.
           * se tutto ok, cancella l'esercizio,
           * altrimenti mostra un messaggio che spiega perche' non e' possibile cancellare l'esercizio.
           *
        */
        if (!ExerciseDAO::canEditExercise($id_node)) {
            $dataHa['exercise'] = translateFN("L'esercizio non può essere eliminato.");
            break;
        }
        $result = ExerciseDAO::delete($id_node);
        if (AMADataHandler::isError($result)) {
            $errObj = new ADAError($result, translateFN("Errore durante la cancellazione dell'esercizio"));
        }
        $dataHa['exercise'] = translateFN("L'esercizio &egrave; stato cancellato correttamente");
        break;
}

//$dataHa['go_back'].= "<BR><a href=\"$http_root_dir/browsing/exercise.php?id_node=$id_next_exercise\">".translateFN("Prossimo esercizio")."</a>";

$content_dataAr = [
        'status' => $status,
        'course_title' => $course_title ?? '',
        'user_name' => $user_name,
        'user_type' => $user_type,
        'user_level' => $user_level,
        'author' => $node_author ?? '',
        'node_level' => $node_level ?? '',
        'visited' => $visited ?? '',
        'path' => $node_path,
// 'index'=>$node_index,
// 'link'=>$data['link'],
        'title' => $node_title,
        'form' => $dataHa['exercise'],
        'media' => $dataHa['media'] ?? '',
//                   'edit_exercise'=>$edit_exercise_html,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'chat_users' => $online_users ?? '',
        'icon' => $icon,

];
// FIXME: non dovrebbe essere necessario aggiungere questa riga!
$layout_dataAr['node_type'] = '';
ARE::render($layout_dataAr, $content_dataAr);

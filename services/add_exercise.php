<?php

use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\HtmlLibrary\ServicesModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

$self =  Utilities::whoami();

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
ServiceHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
*/

####################### recupero variabili dalla get
$step = DataValidator::checkInputValues('step', 'Value', INPUT_GET, '1');

$status = translateFN("Aggiunta di un esercizio. Step: " . $step);

$level = 0; // default
$chat_link = "";
$target = $http_root_dir . "/browsing/view";
$online_users_listing_mode = 2;
if (!isset($id_course_instance)) {
    $id_course_instance = null;
}
$online_users = ADAGenericUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);


$break_error = ''; // system var, indica eventuali errori
/*
 * Generazione dei form per l'inserimento dell'esercizio.
 *
*/

if (isset($step) && !isset($verify)) {
    /**
     * controlla esecuzione operazione
     *
     * casi:
     *      1 - dati generali dell'esercizio (tipo, interazione, esecuzione)
     *      2 - dati specifici per ciascun tipo di esercizio
     *
     */


    switch ($step) {
        case '1':
        default:
            /*
                 * Generazione del form per l'inserimento del titolo dell' esercizio
                 * e per la scelta della tipologia di esercizio.
                 * Inoltre eseguiamo il controllo sui dati inviati da questo form prima di
                 * passare al form per l'inserimento dell'esercizio.
            */

            // Verifico l'eventuale segnalazione di un errore nel submit del tipo di esercizio
            if (
                isset($_SESSION['add_exercise']['error_flag']) && !empty($_SESSION['add_exercise']['error_flag'])
                    && isset($_SESSION['add_exercise']['error_msg']) && !empty($_SESSION['add_exercise']['error_msg'])
            ) {
                $messaggio_errore = $_SESSION['add_exercise']['error_msg'];
                $_SESSION['add_exercise']['error_flag'] = 0;
                $_SESSION['add_exercise']['error_msg'] = 0;
            }

            //            // Genero il form per la scelta del tipo di esercizio.
            //            $exercise_family = array (
            //                    ADA_STANDARD_EXERCISE_TYPE       => translateFN("Multiple Choice"),
            //                    ADA_OPEN_MANUAL_EXERCISE_TYPE    => translateFN("Open with Manual Correction"),
            //                    ADA_OPEN_AUTOMATIC_EXERCISE_TYPE => translateFN("Open with Automatic Correction"),
            //                    ADA_OPEN_UPLOAD_EXERCISE_TYPE    => translateFN("Open Manual + Upload"),
            //                    ADA_CLOZE_EXERCISE_TYPE          => translateFN("CLOZE")
            //            );
            //
            //            $exercise_interaction = array (
            //                    ADA_BLIND_EXERCISE_INTERACTION    => translateFN("No Feedback"),
            //                    ADA_FEEDBACK_EXERCISE_INTERACTION => translateFN("With Feedback"),
            //                    ADA_RATING_EXERCISE_INTERACTION   => translateFN("With Feedback and Rating")
            //            );
            //
            //            $test_mode = array (
            //                    ADA_SINGLE_EXERCISE_MODE   => translateFN("Only One Exercise"),
            //                    ADA_SEQUENCE_EXERCISE_MODE => translateFN("Next Exercise will be Shown"),
            //                    ADA_RANDOM_EXERCISE_MODE   => translateFN("A Random Picked Exercise will be Shown")
            //            );
            //
            //            $test_simplification = array (
            //                    ADA_NORMAL_EXERCISE_SIMPLICITY   => translateFN("Normal Exercise"),
            //                    ADA_MEDIUM_EXERCISE_SIMPLICITY   => translateFN("Medium Exercise"),
            //                    ADA_SIMPLIFY_EXERCISE_SIMPLICITY => translateFN("Simplified Exercise")
            //            );
            //
            //            $test_barrier = array (
            //                    ADA_NO_EXERCISE_BARRIER  => translateFN("No barrier"),
            //                    ADA_YES_EXERCISE_BARRIER => translateFN("With barrier")
            //            );
            //
            //            $f =& new Form_html();
            //            $f->form_name = 'add_exercise';
            //            $f->method = "POST";
            //            $f->action = "add_exercise.php?verify=1";
            //            $form  = $f->write_form();
            //            $form .= $messaggio_errore;
            //            $form .= $f->html_input_text(translateFN("Nodo Parent"), 'parent_node', $id_node, 20, 100, 0).'<BR>';
            //            $form .= $f->html_input_text(translateFN("Titolo esercizio"), 'exercise_title', "", 20, 100, 0).'<BR>';
            //            $form .= $f->html_select(translateFN("Tipo esercizio"), 'exercise_family', $exercise_family, "", 0, FALSE,"",0,null,null).'<BR>';
            //            $form .= $f->html_select(translateFN("Tipo di interazione"), 'exercise_interaction', $exercise_interaction, "", 0, FALSE,"",0,null,null).'<BR>';
            //            $form .= $f->html_select(translateFN("Modalit&agrave; di esecuzione"), 'test_mode', $test_mode, "", 0, FALSE,"",0,null,null).'<BR>';
            //            $form .= $f->html_select(translateFN("Semplicit&agrave; dell'esercizio"), 'test_simplification', $test_simplification, "", 0, FALSE,"",0,null,null).'<BR>';
            //            $form .= $f->html_select(translateFN("Con sbarramento"), 'test_barrier', $test_barrier, "", 0, FALSE,"",0,null,null).'<BR>';
            //            $form .= $f->html_input_submit("submit","button",translateFN("Procedi"));
            //            $form .= $f->html_input_reset(translateFN("Reset"));
            //            $form .= $f->close_form();
            $form_dataAr = [
              'parent_node' => $id_node,
            ];
            $form = ServicesModuleHtmlLib::getAddExerciseForm($form_dataAr)->getHtml();
            break;

        case '2':
            /*
                * In base al tipo di esercizio selezionato al passo precedente, mostra il form appropriato
                * per l'inserimento.
            */

            $tipo_esercizio = isset($_SESSION['add_exercise']['exercise_family']) ? (int) $_SESSION['add_exercise']['exercise_family'] : null;
            $viewer = ExerciseViewerFactory::create($tipo_esercizio);
            $exercise_data = $_SESSION['add_exercise'] ?? null;
            $form   = $viewer->getAuthorForm("add_exercise.php?verify=2", $exercise_data);
            break;

        case '3':
            /*
                 * Inserimento dell'esercizio nel db.
            */
            $id_course = explode("_", $_SESSION['add_exercise']['parent_node']);

            $last_node = DBRead::getMaxIdFN($id_course[0]);
            $tempAr = explode("_", $last_node);
            $new_id = $tempAr[1]; // get only the part of node
            $new_id = $new_id + 1;
            $node_exercise = $id_course[0] . "_" . $new_id;
            $order = $dh->getOrdineMaxVal($_SESSION['add_exercise']['parent_node']);
            //controllo errori su $order

            $esercizio['id'] = $node_exercise;
            $esercizio['id_node_author'] = $_SESSION['sess_id_user'];
            //$esercizio['title']          = $_SESSION['add_exercise']['exercise_title'];
            $esercizio['name']           = $_SESSION['add_exercise']['exercise_title'];
            $esercizio['text']           = $_SESSION['add_exercise']['question'];
            //$esercizio['type']=$_SESSION['add_exercise']['exercise_family'].$_SESSION['add_exercise']['exercise_interaction'].$_SESSION['add_exercise']['test_mode'];
            $esercizio['type']           = $_SESSION['add_exercise']['exercise_family'] . $_SESSION['add_exercise']['exercise_interaction'] . $_SESSION['add_exercise']['test_mode'] . $_SESSION['add_exercise']['test_simplification'] . $_SESSION['add_exercise']['test_barrier'];
            $esercizio['parent_id']      = $_SESSION['add_exercise']['parent_node'];
            $esercizio['order']          = $order + 1;
            $esercizio['creation_date']  = Utilities::todayDateFN();
            $esercizio['pos_x0']         = 0;
            $esercizio['pos_y0']         = 0;
            $esercizio['pos_x1']         = 0;
            $esercizio['pos_y1']         = 0;

            $result = $dh->addNode($esercizio); // esercizio

            ##### eventuali risposte
            if (sizeof($_SESSION['add_exercise']['answers']) > 0) {
                foreach ($_SESSION['add_exercise']['answers'] as $answer) {
                    $last_node = DBRead::getMaxIdFN($id_course[0]);
                    $tempAr = explode("_", $last_node);
                    $new_id = $tempAr[1]; // get only the part of node
                    $new_id = $new_id + 1;
                    $node_risp = $id_course[0] . "_" . $new_id;

                    $risp['id']             = $node_risp;
                    $risp['id_node_author'] = $_SESSION['sess_id_user'];
                    $risp['title']          = "";
                    $risp['name']           = $answer['answer'];
                    //                  if ( isset( $answer['comment'] ) )
                    //                  {
                    //                      $risp['text'] = $answer['comment'];
                    //                  }
                    //                  else
                    //                  {
                    //                      $risp['text'] ="";
                    //                  }
                    $risp['text'] = $answer['comment'] ?? "";
                    $risp['type']           = 1;
                    $risp['parent_id']      = $node_exercise;
                    $risp['correctness']    = $answer['correctness'];
                    $risp['creation_date']  = Utilities::todayDateFN();
                    $risp['pos_x0']         = 0;
                    $risp['pos_y0']         = 0;
                    $risp['pos_x1']         = 0;
                    $risp['pos_y1']         = 0;
                    // se si tratta di una parola da nascondere in un esercizio di tipo CLOZE
                    // memorizzo anche la posizione nella frase
                    if ($_SESSION['add_exercise']['exercise_family'] == ADA_CLOZE_EXERCISE_TYPE) {
                        $risp['order'] = $answer['hide'];
                    }
                    $dh->addNode($risp); // risposta
                }
            }


            ##### eventuale file
            //if (!empty($_SESSION['add_exercise']['file'])) {
            //
            //}

            unset($_SESSION['add_exercise']);
            // vito 26 gennaio 2009
            //header("Location: $http_root_dir/browsing/view.php?id_node={$exercise['parent_id']}");
            header("Location: $http_root_dir/browsing/exercise.php?id_node=$node_exercise");
            exit();
            break;
    }
} elseif (isset($verify)) {
    /*
     * Verifichiamo che i dati per l'inserimento dell'esercizio siano completi.
     * In caso non siano completi, rimandiamo al passo di inserimento dati, specificando l'errore rilevato.
    */
    switch ($verify) {
        case 1:
            // Se post non è vuoto, verifico che siano stati inviati tutti i dati necessari
            // per proseguire con l'inserimento dell'esercizio.
            if (!empty($_POST)) {
                if (
                    !isset($_POST['parent_node']) || empty($_POST['parent_node']) ||
                        !isset($_POST['exercise_title']) || empty($_POST['exercise_title']) ||
                        !isset($_POST['exercise_family']) || // empty($_POST['exercise_family']) ||
                        !isset($_POST['exercise_interaction']) || // empty($_POST['exercise_interaction']) ||
                        !isset($_POST['test_mode']) || // empty($_POST['test_mode']) ||
                        !isset($_POST['test_simplification']) || // empty($_POST['test_simplification']) ||
                        !isset($_POST['test_barrier']) //|| empty($_POST['test_barrier'])
                ) {
                    $_SESSION['add_exercise']['error_flag'] = 1;
                    $_SESSION['add_exercise']['error_msg']  = "Attenzione: campi obbligatori non compilati.";

                    header('location: add_exercise.php?step=1');
                    break;
                } else {
                    // Tutti i campi necessari sono stati impostati, rimandiamo al form
                    // per l'inserimento dell'esercizio.
                    $_SESSION['add_exercise']['parent_node'] = $_POST['parent_node'];
                    $_SESSION['add_exercise']['exercise_title'] = $_POST['exercise_title'];
                    $_SESSION['add_exercise']['exercise_family'] = $_POST['exercise_family'];
                    $_SESSION['add_exercise']['exercise_interaction'] = $_POST['exercise_interaction'];
                    $_SESSION['add_exercise']['test_mode'] = $_POST['test_mode'];
                    $_SESSION['add_exercise']['test_simplification'] = $_POST['test_simplification'];
                    $_SESSION['add_exercise']['test_barrier'] = $_POST['test_barrier'];

                    header('location: add_exercise.php?step=2');
                    break;
                }
            }
            break;
        case 2:
            $tipo_esercizio = (int) $_SESSION['add_exercise']['exercise_family'];

            $viewer = ExerciseViewerFactory::create($tipo_esercizio);
            $exercise_data = $_SESSION['add_exercise'];

            //if ( !$viewer->checkAuthorInput($_POST, &$exercise_data) )
            if (!$viewer->checkAuthorInput($_POST, $exercise_data)) {
                $_SESSION['add_exercise'] = $exercise_data;
                header("Location: add_exercise.php?step=2");
                exit();
            } else {
                $_SESSION['add_exercise'] = $exercise_data;

                if (!isset($_POST['finito']) || (isset($_POST['finito']) && $_POST['finito'])) {
                    header("Location: add_exercise.php?step=3");
                    exit();
                } elseif (isset($_POST['finito']) && !$_POST['finito']) {
                    header("location: add_exercise.php?step=2");
                    exit();
                }
            }
            break;
        default:
            break;
    }
}

// per la visualizzazione del contenuto della pagina


$content_dataAr = [
        'head' => $head_form ?? '',
        'form' => $form ?? '',
        'status' => $status,
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'title' => $node_title ?? '',
        'course_title' => $course_title ?? '',
        'path' => $node_path ?? '',
        'back' => $back ?? '',
];

ARE::render($layout_dataAr, $content_dataAr);

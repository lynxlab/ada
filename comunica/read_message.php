<?php

use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout','user','course'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT         => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_SWITCHER     => ['layout'],
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
ComunicaHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
$status = translateFN('Lettura messaggio');

if ($id_course) {
    $sess_id_course = $id_course;
}

if (isset($id_course_instance)) {
    $sess_id_course_instance = $id_course_instance;
} else {
    $sess_id_course_instance = null;
}

if (isset($del_msg_id) and (!empty($del_msg_id))) {
    // vito, 19 gennaio 2009, qui va in errore durante il log del messaggio
    //$res = $mh->removeMessages($sess_id_user, array($del_msg_id));
    $res = MultiPort::removeUserMessages($userObj, [$del_msg_id]);
    if (AMADataHandler::isError($res)) {
        $errObj = new ADAError(
            $msg_ha,
            translateFN('Errore in cancellazione messaggi'),
            null,
            null,
            null,
            'comunica/list_messages.php?status=' . urlencode(translateFN('Errore in cancelllazione messaggi'))
        );
    } else {
        $status = urlencode(translateFN('Cancellazione eseguita'));
    }
    header("Location: list_messages.php?status=$status");
    exit();
}

// get message content
//$msg_ha = $mh->getMessage($sess_id_user, $msg_id);
$msg_ha = MultiPort::getUserMessage($userObj, $msg_id);
if (AMADataHandler::isError($msg_ha)) {
    $errObj = new ADAError(
        $msg_ha,
        translateFN('Errore in lettura messaggio'),
        null,
        null,
        null,
        'comunica/list_messages.php?status=' . urlencode(translateFN('Errore in lettura messaggio'))
    );
}

$mittente = $msg_ha['mittenteFullname'];
/*
 * usare $msg_ha['id_mittente'] e $sess_id_user per ottenere corso e istanza corso comuni.
 * cosa fare se entrambe gli utenti sono iscritti a due classi?
 */

$Data_messaggio = AMADataHandler::tsToDate($msg_ha['data_ora'], "%d/%m/%Y - %H:%M:%S");
$oggetto        = $msg_ha['titolo'];
$destinatario   = str_replace(",", ", ", $msg_ha['destinatariFullnames']);
$message_text   = $msg_ha['testo'];
$node_title = ""; // empty

$dest_encode = urlencode($mittente);
$testo       = urlencode(trim($message_text));
$oggetto_url = urlencode(trim($oggetto));

// Registrazione variabili per replay
$destinatari_replay = $mittente; //
$_SESSION['destinatari_replay'] = $destinatari_replay;

$testo_replay = trim($message_text);
$_SESSION['testo_replay'] = $testo_replay;
$titolo_replay = trim($oggetto);
$_SESSION['titolo_replay'] = $titolo_replay;

// Registrazione variabili per replay_all
$destinatari_replay_all = $mittente . "," . $destinatario; //
$_SESSION['destinatari_replay_all'] = $destinatari_replay_all;


/*
$testo_ar = explode(chr(13),  chop($message_text));
$testo = "";
foreach($testo_ar as $riga) {
  $testo .= MessageHandler::renderMessageTextFN($riga) ."<BR>";
}
*/

$testo = str_replace("\r\n", '<br />', $message_text);

$content_dataAr = [
  'course_title'   => '<a href="../browsing/main_index.php">' . $course_title . '</a>',
  'user_name'      => $user_name,
  'user_type'      => $user_type,
  'level'          => $user_level,
  'mittente'       => $mittente,
  'Data_messaggio' => $Data_messaggio,
  'oggetto'        => $oggetto,
  'destinatario'   => $destinatario,
  'message_text'   => $testo,
  'status'         => $status,
];
$menuOptions['del_msg_id'] = $msg_id;
ARE::render($layout_dataAr, $content_dataAr, null, null, $menuOptions);

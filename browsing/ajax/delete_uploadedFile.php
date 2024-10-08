<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Module\CollaboraACL\AMACollaboraACLDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Do not track this in navigation history
 */
$trackPageToNavigationHistory = false;

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_TUTOR => ['layout','node','course','course_instance'],
  AMA_TYPE_STUDENT => ['layout','node','course','course_instance'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

$self =  'download';

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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
*/

$languages = Translator::getLanguagesIdAndName();

$retArray = [];
$title = translateFN('Cancellazione File');
// print_r ($fileName); die();

if (!is_null($fileName) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $courseObj = $_SESSION['sess_courseObj'];
    if ($courseObj instanceof Course) {
        $author_id = $courseObj->id_autore;
        //il percorso in cui caricare deve essere dato dal media path del corso, e se non presente da quello di default
        if ($courseObj->media_path != "") {
            $media_path = $courseObj->media_path;
        } else {
            $media_path = MEDIA_PATH_DEFAULT . $author_id ;
        }
        $download_path = $root_dir . $media_path;

        $success = unlink($download_path . DIRECTORY_SEPARATOR . $fileName);

        if ($success) {
            $retArray =  ["status" => "OK", "title" => $title, "msg" => translateFN('File cancellato')];
            if (ModuleLoaderHelper::isLoaded('COLLABORAACL')) {
                $aclDH = AMACollaboraACLDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                $filesACL = $aclDH->findBy('FileACL', [ 'filepath' => str_replace(ROOT_DIR . DIRECTORY_SEPARATOR, '', $download_path . DIRECTORY_SEPARATOR . $fileName) ]);
                if (is_array($filesACL) && count($filesACL) > 0) {
                    foreach ($filesACL as $fileACL) {
                        $aclDH->deleteFileACL($fileACL->getId());
                    }
                }
            }
        } else {
            $retArray =  ["status" => "ERROR", "title" => $title, "msg" => "Errore nella cancellazione del file"];
        }
    } else {
        $retArray =  ["status" => "ERROR", "title" => $title, "msg" => "Errore nel caricamento del corso"];
    }
} elseif (is_null($fileName)) {
    $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("Il nome del file da cancellare non può essere vuoto")];
} else {
    $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "title" => $title, "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

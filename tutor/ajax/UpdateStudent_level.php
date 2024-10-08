<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

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

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();

/*
 * YOUR CODE HERE
 */

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (intval($_POST['level']) < 0) {
        die(json_encode(["status" => "ERROR","msg" =>  translateFN("Il livello non può andare sotto lo zero"),"title" =>  translateFN('Notifica')]));
    }
    $level = $_POST['level'];
    $id_student = $_POST['id_student'];
    $id_instance = $_POST['id_instance'];
    $id_course = $_POST['id_course'];

    $studenti_ar = [$id_student];
    $info_course = $dh->getCourse($id_course);
    if (AMADataHandler::isError($info_course)) {
        $retArray = ["status" => "ERROR","msg" =>  translateFN("Problemi nell'aggiornamento del livello") . '<br/>' . translateFN('Provare ad aggiornare il report e ripetere l\'operazione'),"title" =>  translateFN('Notifica')];
    } else {
        $updated = $dh->setStudentLevel($id_instance, $studenti_ar, $level);
        if (AMADataHandler::isError($updated)) {
            $retArray = ["status" => "ERROR","msg" =>  translateFN("Problemi nell'aggiornamento del livello") . '<br/>' . translateFN('Provare ad aggiornare il report e ripetere l\'operazione'),"title" =>  translateFN('Notifica')];
        } else {
            $retArray = ["status" => "OK","msg" =>  translateFN("Hai aggiornato correttamente il livello dello studente") . '<br />' . translateFN('Ricordarsi di aggiornare il report dopo aver finito le modifiche ai livelli degli studenti.'),"title" =>  translateFN('Notifica')];
        }
    }
    echo json_encode($retArray);
}

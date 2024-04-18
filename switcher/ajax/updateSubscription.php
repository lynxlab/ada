<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userStatus = $_POST['status'];
    $id_user = $_POST['id_user'];
    $id_instance = $_POST['id_instance'];

    $s = new Subscription($id_user, $id_instance);
    $s->setSubscriptionStatus($userStatus);
    $s->setStartStudentLevel(null); // null means no level update
    $result = Subscription::updateSubscription($s);

    if (AMADataHandler::isError($result)) {
        $retArray = ["status" => "ERROR", "msg" =>  translateFN("Problemi nell'aggiornamento dello stato dell'iscrizione"), "title" =>  translateFN('Notifica')];
    } else {
        $retArray = ["status" => "OK", "msg" =>  translateFN("Hai aggiornato correttamente lo stato dell'iscrizione"), "title" =>  translateFN('Notifica')];
    }

    echo json_encode($retArray);
}

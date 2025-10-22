<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

// MODULE's OWN IMPORTS

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reminderEventID']) && intval($_POST['reminderEventID']) > 0) {
        if (isset($_POST['reminderEventHTML']) && strlen(trim($_POST['reminderEventHTML'])) > 0) {
            $result = $GLOBALS['dh']->saveReminderForEvent(intval($_POST['reminderEventID']), trim($_POST['reminderEventHTML']));

            if (!AMADB::isError($result) && intval($result) > 0) {
                $msg = 'Promemoria salvato' . (MODULES_CLASSAGENDA_EMAIL_REMINDER ? ' e inviato' : '');
                $retArray = ["status" => "OK", "reminderID" => $result, "msg" => translateFN($msg)];
            } else {
                $retArray = ["status" => "ERROR", "msg" => translateFN("Errore nel salvataggio")];
            }
        } else {
            $retArray = ["status" => "ERROR", "msg" => translateFN("Testo promemoria vuoto")];
        } // if isset html
    } else {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Selezionare un evento")];
    } // if isset eventID
} // if method is POST

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}
header('Content-Type: application/json');
echo json_encode($retArray);

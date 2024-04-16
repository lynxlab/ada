<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Notifications\AMANotificationsDataHandler;
use Lynxlab\ADA\Module\Notifications\Notification;
use Lynxlab\ADA\Module\Notifications\NotificationActions;
use Lynxlab\ADA\Module\Notifications\NotificationException;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(NotificationActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMANotificationsDataHandler $ntDH
 */
$ntDH = AMANotificationsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];
session_write_close();

// sanitizie data
$passedData = [];
$needed = [
    [
        'key' => 'notificationType',
        'sanitize' => function ($v) {
            return (in_array($v, Notification::TYPES) ? $v : 0);
        },
    ],
    [
        'key' => 'isActive',
        'sanitize' => function ($v) {
            return (bool) $v;
        },
    ],
    [
        'key' => 'instanceId',
        'sanitize' => function ($v) {
            return intval($v) > 0 ? intval($v) : null;
        },
    ],
    [
        'key' => 'notificationId',
        'sanitize' => function ($v) {
            return intval($v) > 0 ? intval($v) : null;
        },
    ],
    [
        'key' => 'nodeId',
        'sanitize' => function ($v) {
            $v = trim($v);
            return DataValidator::validateNodeId($v) ? $v : null;
        },
    ],
];

foreach ($needed as $n) {
    if (array_key_exists($n['key'], $_REQUEST)) {
        $passedData[$n['key']] = $n['sanitize']($_REQUEST[$n['key']]);
    } else {
        $passedData[$n['key']] = null;
    }
}

$res = new NotificationException(translateFN('Errore sconosciuto'));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed data
     */
    if (array_key_exists('sess_userObj', $_SESSION)) {
        if ($passedData['notificationType'] > 0) {
            $res = $ntDH->saveNotification($passedData);
        } else {
            $res = new NotificationException(translateFN('Tipo di notifica non valido'));
        }
    } else {
        $res = new NotificationException(translateFN('Nessun utente in sessione'));
    }
}

if (AMADB::isError($res) || $res instanceof NotificationException) {
    // if it's an error display the error message
    $retArray['status'] = "ERROR";
    $retArray['msg'] = $res->getMessage();
} else {
    $retArray['status'] = "OK";
    $retArray['msg'] = translateFN("Preferenze di notifica impostate");
    $retArray['data'] = $res;
}

header('Content-Type: application/json');
echo json_encode($retArray);

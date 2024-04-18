<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Login\AMALoginDataHandler;

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
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

$GLOBALS['dh'] = AMALoginDataHandler::instance();

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    if (!isset($_POST['option_id'])  && !isset($_POST['provider_id'])) {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Non so a cosa cambiare stato")];
    } else {
        $status = intval($_POST['status']);
        if (isset($_POST['option_id'])) {
            $result = $GLOBALS['dh']->setEnabledOptionSet(intval($_POST['option_id']), $status);
            $vowel = 'a';
        } elseif (isset($_POST['provider_id'])) {
            $result = $GLOBALS['dh']->setEnabledLoginProvider(intval($_POST['provider_id']), $status);
            $vowel = 'o';
        }

        if (!AMADB::isError($result)) {
            if ($status) {
                $statusText = translateFN('Abilitat' . $vowel);
                $buttonTitle = translateFN('Disabilita');
            } else {
                $statusText = translateFN('Disabilitat' . $vowel);
                $buttonTitle = translateFN('Abilita');
            }
            $retArray =  ["status" => "OK", "statusText" => $statusText, "buttonTitle" => $buttonTitle];
        } else {
            $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore nell'impostare lo stato") ];
        }
    }
} else {
    $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

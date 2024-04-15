<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\AMA\AMADB;

use function \translateFN;

/**
 * LOGIN MODULE
 *
 * @package     login module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2015-2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Login\AMALoginDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

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

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['option_id']) && !isset($_POST['provider_id'])) {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Non so cosa cancellare")];
    } else {
        if (isset($_POST['option_id'])) {
            if (isset($key) && strlen($key) > 0) {
                $result = $GLOBALS['dh']->deleteOptionByKey(intval($_POST['option_id']), trim($key));
                $deletedElement = 'Chiave'; // translateFN delayed when building msg
                $vowel = 'a';
            } else {
                $result = $GLOBALS['dh']->deleteOptionSet(intval($_POST['option_id']));
                $deletedElement = 'Fonte';  // translateFN delayed when building msg
                $vowel = 'a';
            }
        } elseif (isset($_POST['provider_id'])) {
            $result = $GLOBALS['dh']->deleteLoginProvider(intval($_POST['provider_id']));
            $deletedElement = 'Login Provider'; // translateFN delayed when building msg
            $vowel = 'o';
        }

        if (!AMADB::isError($result)) {
            $retArray =  ["status" => "OK", "msg" => translateFN($deletedElement . " cancellat" . $vowel)];
        } else {
            $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore di cancellazione") ];
        }
    }
} else {
    $retArray =  ["status" => "ERROR", "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

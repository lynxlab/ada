<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

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
$allowedUsersAr = [AMA_TYPE_VISITOR];

/**
 * Get needed objects
 */
$neededObjAr = [AMA_TYPE_VISITOR => ['layout']];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
require_once ROOT_DIR . '/browsing/include/browsing_functions.inc.php';
BrowsingHelper::init($neededObjAr);

$data = new stdClass();
$data->unameok = false;
$data->exception = [];

try {
    $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (array_key_exists('uname', $_POST) && strlen(trim($_POST['uname'])) > 0 && DataValidator::validateUsername(trim($_POST['uname']))) {
        $userId = MultiPort::findUserByUsername(trim($_POST['uname']));
        if (!AMADB::isError($userId) && $userId > 0) {
            // username exists
            throw new Exception(translateFN('Username esistente'));
        } else {
            $data->unameok = true;
        }
    } else {
        throw new Exception(translateFN('Utente non valido'));
    }
} catch (Exception $e) {
    // header(' ', true, 400);
    $data->exception['code'] = $e->getCode();
    $data->exception['message'] = $e->getMessage();
}

header('Content-Type: application/json');
die(json_encode($data));

<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprAPI;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(GdprActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$data = new stdClass();
$data->title = '<i class="basic error icon"></i>' . translateFN('Errore salvataggio');
$data->status = 'ERROR';
$data->message = translateFN('Errore nel salvataggio della policy');

try {
    $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (array_key_exists('content', $_POST) && strlen(trim($_POST['content'])) > 0) {
        $postParams['content'] = trim($_POST['content']);
    }
    $result = (new GdprAPI())->savePolicy($postParams);
    $data->title = '<i class="info icon"></i>' . translateFN('Policy salvata');
    $data->status = 'OK';
    $data->message = translateFN('La policy è stata salvata correttamente');
    if (property_exists($result, 'redirecturl')) {
        $data->saveResult['redirecturl'] = $result->redirecturl;
    }
} catch (Exception $e) {
    header(' ', true, 400);
    $data->title .= ' (' . $e->getCode() . ')';
    $data->message = $e->getMessage();
    $data->errorMessage = $e->getCode() . PHP_EOL . $e->getMessage();
    if (array_key_exists('debugForm', $postParams) && intval($postParams['debugForm']) === 1) {
        $data->errorTrace = $e->getTraceAsString();
    }
}

header('Content-Type: application/json');
die(json_encode($data));

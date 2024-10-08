<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Lynxlab\ADA\Module\GDPR\GdprRequest;
use Ramsey\Uuid\Uuid;

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
$data->title = '<i class="basic error icon"></i>' . translateFN('Errore evasione richiesta');
$data->status = 'ERROR';
$data->message = translateFN('Errore sconosciuto');

try {
    $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    $isClose = array_key_exists('isclose', $postParams) && intval($postParams['isclose']) === 1;

    if (!array_key_exists('requestuuid', $postParams)) {
        throw new GdprException(translateFN("L'ID pratica non può essere vouto"));
    } elseif (array_key_exists('requestuuid', $postParams) && !Uuid::isValid(trim($postParams['requestuuid']))) {
        throw new GdprException(translateFN("L'ID pratica non è valido"));
    }
    // so far so good, load the request
    $gdprAPI = new GdprAPI();
    $tmp = $gdprAPI->findBy($gdprAPI->getObjectClasses()[AMAGdprDataHandler::REQUESTCLASSKEY], ['uuid' => trim($postParams['requestuuid'])]);
    $request = reset($tmp);
    if (!($request instanceof GdprRequest)) {
        throw new GdprException(translateFN("ID pratica non trovato"));
    } elseif (
        ($isClose && !GdprActions::canDo(GdprActions::FORCE_CLOSE_REQUEST, $request)) ||
               (!is_null($request->getType()) && !GdprActions::canDo($request->getType()->getLinkedAction(), $request))
    ) {
        throw new GdprException(translateFN("Utente non abilitato all'azione richiesta"));
    } else {
        if ($isClose) {
            $data = new stdClass();
            $data->reloaddata = true;
            $request->close();
        } else {
            $data = $request->handle();
        }
        $data->status = 'OK';
    }
} catch (Exception $e) {
    header(' ', true, 400);
    $data->errorCode = $e->getCode();
    //  $data->title .= ' ('.$e->getCode().')';
    $data->message = $e->getMessage();
    $data->errorMessage = $e->getCode() . PHP_EOL . $e->getMessage();
    if (array_key_exists('debug', $postParams) && intval($postParams['debug']) === 1) {
        $data->errorTrace = $e->getTraceAsString();
    }
}

header('Content-Type: application/json');
die(json_encode($data));

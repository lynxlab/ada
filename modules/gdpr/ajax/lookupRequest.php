<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

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
$data->title = '<i class="basic error icon"></i>' . translateFN('Errore ricerca richiesta');
$data->status = 'ERROR';
$data->message = translateFN('Errore sconosciuto');

try {
    $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    $captchaText = null;
    if (array_key_exists('captchaText', $_SESSION)) {
        $captchaText = $_SESSION['captchaText'];
        unset($_SESSION['captchaText']);
    }
    session_write_close();

    if (!array_key_exists('checktxt', $postParams)) {
        throw new GdprException(translateFN('Il codice di controllo inserito è vuoto'), GdprException::CAPTCHA_EMPTY);
    } elseif (array_key_exists('checktxt', $postParams) && strcmp($postParams['checktxt'], $captchaText) !== 0) {
        throw new GdprException(translateFN('Il codice di controllo inserito non corrisponde'), GdprException::CAPTCHA_NOMATCH);
    } elseif (!array_key_exists('requestUUID', $postParams)) {
        throw new GdprException(translateFN("L'ID pratica non può essere vouto"));
    } elseif (array_key_exists('requestUUID', $postParams) && !Uuid::isValid(trim($postParams['requestUUID']))) {
        throw new GdprException(translateFN("L'ID pratica non è valido"));
    } else {
        $result = AMAGdprDataHandler::lookupRequest(trim($postParams['requestUUID']));
        $data = new stdClass();
        $data->saveResult = $result;
        $data->status = 'OK';
    }
} catch (Exception $e) {
    header(' ', true, 400);
    $data->errorCode = $e->getCode();
    //  $data->title .= ' ('.$e->getCode().')';
    $data->message = $e->getMessage();
    $data->errorMessage = $e->getCode() . PHP_EOL . $e->getMessage();
    if (array_key_exists('debugForm', $postParams) && intval($postParams['debugForm']) === 1) {
        $data->errorTrace = $e->getTraceAsString();
    }
}

header('Content-Type: application/json');
die(json_encode($data));

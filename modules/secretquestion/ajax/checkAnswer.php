<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Secretquestion\AMASecretQuestionDataHandler;

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
BrowsingHelper::init($neededObjAr);

$data = new stdClass();
$data->title = '<i class="basic error icon"></i>' . translateFN('Errore controllo risposta');
$data->status = 'ERROR';
$data->message = translateFN('Errore nel controllo della risposta segreta');

try {
    $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    if (array_key_exists('userId', $_POST) && intval(trim($_POST['userId'])) > 0) {
        $userId = intval($_POST['userId']);
        if (array_key_exists('secretanswer', $_POST) && strlen(trim($_POST['secretanswer'])) > 0) {
            $answer = trim($_POST['secretanswer']);
            $sqdh = AMASecretQuestionDataHandler::instance();
            // this will throw an exception on wrong answer or error
            $result = $sqdh->checkAnswer($userId, $answer);
            $data->title = '<i class="info icon"></i>' . translateFN('Risposta corretta');
            $data->status = 'OK';
            $data->message = translateFN('Verrai ridirezionato alla pagina di modifica password');
            if (array_key_exists('redirecturl', $result)) {
                $data->saveResult['redirecturl'] = $result['redirecturl'];
            }
        } else {
            throw new Exception(translateFN('Risposta vuota'));
        }
    } else {
        throw new Exception(translateFN('Utente non valido'));
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

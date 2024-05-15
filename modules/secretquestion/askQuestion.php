<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Secretquestion\AMASecretQuestionDataHandler;
use Lynxlab\ADA\Module\Secretquestion\SecretQuestionForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

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
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = Utilities::whoami();

try {
    $userId = filter_input(INPUT_GET, 'userId', FILTER_SANITIZE_NUMBER_INT);
    if ($userId > 0) {
        $sqdh = AMASecretQuestionDataHandler::instance();
        $question = $sqdh->getUserQuestion($userId);
        if (strlen($question) > 0) {
            $form = new SecretQuestionForm(false, true);
            $form->fillWithArrayData(['secretquestion' => htmlentities($question), 'userId' => $userId]);
            $data = $form->getHtml();
            $optionsAr['onload_func'] = 'initDoc(\'' . $form->getName() . '\');';
        } else {
            throw new Exception(translateFN('Impossibile trovare la domanda segreta'));
        }
    } else {
        throw new Exception(translateFN('Utente non valido'));
    }
} catch (Exception $e) {
    $message = CDOMElement::create('div', 'class:ui icon error message');
    $message->addChild(CDOMElement::create('i', 'class:attention icon'));
    $mcont = CDOMElement::create('div', 'class:content');
    $mheader = CDOMElement::create('div', 'class:header');
    $mheader->addChild(new CText(translateFN('Errore modulo domanda segreta')));
    $span = CDOMElement::create('span');
    $span->addChild(new CText($e->getMessage()));
    $mcont->addChild($mheader);
    $mcont->addChild($span);
    $message->addChild($mcont);
    $data = $message->getHtml();
    $optionsAr = null;
}

$content_dataAr = [
    'user_name' => $userObj->getFirstName(),
    'user_homepage' => $userObj->getHomePage(),
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'data' => $data,
    'help' => translateFN('Rispondi alla domanda per impostare la nuova password'),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

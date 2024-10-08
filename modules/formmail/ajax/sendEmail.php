<?php

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\FormMail\AMAFormmailDataHandler;

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
$variableToClearAR = ['layout'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_STUDENT, AMA_TYPE_SUPERTUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_TUTOR => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
        AMA_TYPE_STUDENT => ['layout'],
        AMA_TYPE_SUPERTUTOR => ['layout'],
];


/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$retArray = ['status' => "ERROR", 'title' => '<i class="basic error icon"></i>' . translateFN('Errore'), 'msg' => translateFN("Errore sconosciuto")];

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['helpTypeID']) && intval(trim($_POST['helpTypeID'])) > 0 &&
    isset($_POST['helpType'])   && strlen(trim($_POST['helpType'])) > 0   &&
    isset($_POST['subject'])    && strlen(trim($_POST['subject'])) > 0    &&
    isset($_POST['recipient'])  && strlen(trim($_POST['recipient'])) > 0  &&
    isset($_POST['msgbody'])    && strlen(trim($_POST['msgbody'])) > 0
) {
    $GLOBALS['dh'] = AMAFormmailDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

    $selfSend = isset($_POST['selfSend']) && (intval($_POST['selfSend']) === 1);

    /**
     * Initializre the PHPMailer
     */
    $phpmailer = new ADAPHPMailer();
    $phpmailer->CharSet = strtolower(ADA_CHARSET);
    $phpmailer->configSend();
    $phpmailer->SetFrom($userObj->getEmail(), $userObj->getFullName());
    $phpmailer->AddReplyTo($userObj->getEmail(), $userObj->getFullName());
    $phpmailer->IsHTML(true);
    $phpmailer->Subject = '[' . trim($_POST['helpType']) . '] - ' . trim($_POST['subject']);
    $phpmailer->AddAddress(trim($_POST['recipient']));
    $phpmailer->Body = trim($_POST['msgbody']);
    if (stripos(trim($_POST['recipient']), 'incoming.gitlab.com') !== false) {
        // fixes to have working new lines in gitlab Service Desk
        $phpmailer->Encoding = $phpmailer::ENCODING_QUOTED_PRINTABLE;
        $phpmailer->AltBody = strip_tags(html_entity_decode($phpmailer->Body, ENT_QUOTES, ADA_CHARSET), '<br>');
        $phpmailer->AltBody = trim(str_replace(['<br>', '<br/>', '<br />','<br/ >', PHP_EOL], PHP_EOL . PHP_EOL, $phpmailer->AltBody)) . PHP_EOL;
    } else {
        $phpmailer->AltBody = strip_tags(html_entity_decode($phpmailer->Body, ENT_QUOTES, ADA_CHARSET));
    }

    if (isset($_POST['attachments']) && is_array($_POST['attachments']) && count($_POST['attachments']) > 0) {
        foreach ($_POST['attachments'] as $name => $realfile) {
            $toattach = ADA_UPLOAD_PATH . $userObj->getId() . '/' . $realfile;
            if (is_file($toattach) && is_readable($toattach)) {
                $phpmailer->addAttachment($toattach, $name);
            } else {
                unset($_POST['attachments'][$name]);
            }
        }
        $attachmentStr = serialize($_POST['attachments']);
    } else {
        $attachmentStr = null;
    }

    $sentOK = $phpmailer->send();
    if ($selfSend) {
        $phpmailer->clearAllRecipients();
        $phpmailer->AddAddress($userObj->getEmail(), $userObj->getFullName());
        $phpmailer->send();
    }

    if (!$sentOK) {
        $retArray['msg'] = translateFN('La richiesta non è stata spedita') . '<br/>' .
                           translateFN('Possibile causa') . ': ' . $phpmailer->ErrorInfo;
    } else {
        $retArray = ['status' => "OK"];
    }

    $GLOBALS['dh']->saveFormMailHistory(
        $userObj->getId(),
        intval(trim($_POST['helpTypeID'])),
        $phpmailer->Subject,
        $phpmailer->Body,
        $attachmentStr,
        ($selfSend ? 1 : 0),
        ($sentOK ? 1 : 0)
    );
} else {
    $retArray['msg'] = translateFN('Numero di parametri non corretto');
}

header('Content-Type: application/json');
echo json_encode($retArray);

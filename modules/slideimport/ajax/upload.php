<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Upload\FileUploader;

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
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR   => ['layout'],
        AMA_TYPE_TUTOR    => ['layout'],
        AMA_TYPE_STUDENT  => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$fileUploader = new FileUploader(ADA_UPLOAD_PATH . $userId . '/');
$data = '';
$error = true;
$isPdf = false;

if ($fileUploader->upload() == false) {
    $data = $fileUploader->getErrorMessage();
} else {
    if (
        isset($GLOBALS['IMPORT_MIME_TYPE'][$fileUploader->getType()]) &&
        $GLOBALS['IMPORT_MIME_TYPE'][$fileUploader->getType()]['permission'] === ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE
    ) {
        $error = false;
        $_SESSION[$sessionVar]['filename'] = $fileUploader->getPathToUploadedFile();
        $isPdf = (stripos($fileUploader->getType(), "pdf") > 0);
    } else {
        $error = true;
        $data = translateFN('Caricare solo presentazioni, documenti o PDF') . '<br/>' .
                translateFN('Il file caricato si identifica come') . ': ' . $fileUploader->getType();
    }
}

if (!$error) {
    $data = json_encode(['isPdf' => $isPdf]);
    header('Content-Type: application/json');
} else {
    header(' ', true, 400);
    unlink($fileUploader->getPathToUploadedFile());
    if (strlen($data) <= 0) {
        $data = translateFN('Errore sconosciuto');
    }
}

echo $data;

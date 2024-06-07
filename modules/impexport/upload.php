<?php

/**
 * EXPORT TEST.
 *
 * @package     export/import course
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            impexport
 * @version     0.1
 */

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Upload\FileUploader;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

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
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$fieldUploadName = (string) DataValidator::checkInputValues('uploaded_file', 'Value', INPUT_POST, 'uploaded_file');
$fileUploader = new FileUploader(ADA_UPLOAD_PATH . $userObj->getId() . '/', $fieldUploadName);
if ($fileUploader->upload(false) == false) {
    $error = $fileUploader->getErrorMessage();
    $response['success'] = 0;
    $response['data'] = ['error' => strlen($error) == 0 ? translateFN('Errore sconosciuto') : $error];
    unlink($fileUploader->getPathToUploadedFile());
    header(' ', true, 400);
} else {
    $response['success'] = 1;
    $response['data'] = ['fileName' => $fileUploader->getFileName()];
    $_SESSION['importHelper']['filename'] = $fileUploader->getPathToUploadedFile();
}

header('Content-Type: application/json');
die(json_encode($response));

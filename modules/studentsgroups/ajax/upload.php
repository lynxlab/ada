<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Upload\FileUploader;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsActions;

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
[$allowedUsersAr, $neededObjAr] = array_values(StudentsGroupsActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$fileUploader = new FileUploader(ADA_UPLOAD_PATH . DIRECTORY_SEPARATOR . MODULES_STUDENTSGROUPS_NAME . DIRECTORY_SEPARATOR, key($_FILES));
$data = '';
$error = true;

if ($fileUploader->upload() == false) {
    $data = $fileUploader->getErrorMessage();
} else {
    $data = json_encode(['fileName' => $fileUploader->getFileName()]);
    $error = false;
}

if ($error !== false) {
    header(' ', true, 400);
    unlink($fileUploader->getPathToUploadedFile());
    if (strlen($data) <= 0) {
        $data = translateFN('Errore sconosciuto');
    }
}

header('Content-Type: application/json');
echo $data;

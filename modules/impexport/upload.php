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
    $data = $fileUploader->getErrorMessage();
    $response['success'] = 0;
    $response['error'] = $fileUploader->getErrorMessage();
} else {
    $_SESSION['importHelper']['filename'] = $fileUploader->getPathToUploadedFile();
    $response['success'] = 1;
}

echo $response['success'] == 0 ? $response['error'] : $response['success'];
die();
// echo json_encode($response);

<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\Course;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

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

$error = true;
$data = null;
if (array_key_exists('resourceID', $_POST) && intval($_POST['resourceID']) > 0) {
    if (array_key_exists('courseID', $_POST) && intval($_POST['courseID']) > 0) {
        $resourceID = intval($_POST['resourceID']);
        $courseID = intval($_POST['courseID']);
        $resInfo = $GLOBALS['dh']->getRisorsaEsternaInfo($resourceID);
        $res = $GLOBALS['dh']->delRisorseNodi($courseID, $resourceID);
        if (!AMADB::isError($res)) {
            $res = $GLOBALS['dh']->removeRisorsaEsterna($resourceID);
            if (!AMADB::isError($res) && !AMADB::isError($resInfo) && array_key_exists('nome_file', $resInfo)) {
                unlink(Course::MEDIA_PATH_DEFAULT . $courseID . '/' . str_replace(' ', '_', $resInfo['nome_file']));
                // this will remove the courseID dir only if it's empty
                @rmdir(Course::MEDIA_PATH_DEFAULT . $courseID);
                $error = false;
                $data = translateFN('Risorsa cancellata');
            } else {
                $data = $res->getMessage();
            }
        } else {
            $data = $res->getMessage();
        }
    } else {
        $data = translateFN('Passare un id corso valido');
    }
} else {
    $data = translateFN('Passare un id risorsa valido');
}

if ($error) {
    header(' ', true, 500);
}
header('Content-Type: application/json');
die(json_encode(['message' => $data]));

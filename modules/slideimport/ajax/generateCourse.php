<?php

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Module\Slideimport\Functions\generateRandomString;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_AUTHOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

$courseID = -1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['courseName']) && strlen(trim($_POST['courseName'])) > 0) {
    $courseArr = [
            'nome' => generateRandomString(8),
            'titolo' => trim($_POST['courseName']),
            'descr' => '',
            'd_create' => Utilities::ts2dFN(time()),
            'd_publish' => null,
            'id_autore' => $userObj->getId(),
            'id_nodo_toc' => 0,
            'id_nodo_iniziale' => 0,
            'media_path' => null,
            'id_lingua' => $userObj->getLanguage(),
            'static_mode' => 0,
            'crediti' => 0,
            'duration_hours' => 0,
            'service_level' => DEFAULT_SERVICE_TYPE,
    ];

    $rename_count = 3;
    do {
        $courseNewID = $GLOBALS['dh']->addCourse($courseArr);
        if (AMADB::isError($courseNewID)) {
            $courseArr['nome'] = generateRandomString(8);
            $rename_count--;
        }
    } while (AMADB::isError($courseNewID) && $rename_count >= 0);

    if (!AMADB::isError($courseNewID)) {
        // add a row in common.servizio
        $service_dataAr = [
                'service_name' => trim($_POST['courseName']),
                'service_description' => '',
                'service_level' => DEFAULT_SERVICE_TYPE,
                'service_duration' => 0,
                'service_min_meetings' => 0,
                'service_max_meetings' => 0,
                'service_meeting_duration' => 0,
        ];
    }

    $id_service = $common_dh->addService($service_dataAr);
    if (!AMADB::isError($id_service)) {
        $tester_infoAr = $common_dh->getTesterInfoFromPointer($_SESSION['sess_selected_tester']);
        if (!AMADB::isError($tester_infoAr)) {
            $id_tester = $tester_infoAr[0];
            $result = $common_dh->linkServiceToCourse($id_tester, $id_service, $courseNewID);
            if (AMADB::isError($result)) {
                $courseNewID = -1;
            }
        }
    }
}
header('Content-Type: application/json');
echo json_encode(['courseID' => $courseNewID]);

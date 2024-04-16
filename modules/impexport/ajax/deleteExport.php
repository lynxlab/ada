<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Impexport\AMARepositoryDataHandler;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

$rdh = AMARepositoryDataHandler::instance();

$retArray = ['status' => 'ERROR'];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, delete the export with the passed id
     */
    try {
        $postParams = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $res = $rdh->deleteExport($postParams);
        if (AMADB::isError($res)) {
            // if it's an error display the error message
            $retArray['status'] = "ERROR";
            $retArray['msg'] = $res->getMessage();
        } else {
            $retArray['status'] = "OK";
            $retArray['msg'] = translateFN('Esportazione cancellata');
        }
    } catch (Exception $e) {
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($retArray);

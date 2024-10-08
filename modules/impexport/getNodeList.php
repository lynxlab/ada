<?php

/**
 * EXPORT MODULE.
 *
 * @package     export/import course
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            impexport
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Impexport\AMAImpExportDataHandler;
use Lynxlab\ADA\Module\Impexport\ExportHelper;

ini_set('display_errors', '0');
error_reporting(E_ALL);
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
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * this is called async by the tree view to populate itself
 */

$courseID = (isset($_GET['courseID']) && (intval($_GET['courseID']) > 0)) ? intval($_GET['courseID']) : 0;

if ($courseID > 0) {
    $rootNode = $courseID . ExportHelper::$courseSeparator . "0";
    // need an Import/Export DataHandler
    $dh = AMAImpExportDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

    $exportHelper = new ExportHelper($courseID);

    $a = $exportHelper->getAllChildrenArray($rootNode, $dh);

    echo json_encode([$a]);
}

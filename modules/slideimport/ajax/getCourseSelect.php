<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;

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

/**
 * load course list from the DB and output the generated select in a template field
 */
$providerCourses = $GLOBALS['dh']->findCoursesList(['nome','titolo'], '`id_utente_autore`=' . $userObj->getId());
$html = translateFN('Nessun corso trovato');

if (!AMADB::isError($providerCourses)) {
    $courses = [];
    if (isset($_GET['selectedID']) && intval($_GET['selectedID']) > 0) {
        $selectedID = intval($_GET['selectedID']);
    } else {
        $selectedID = 0;
    }

    foreach ($providerCourses as $course) {
        $courses[$course[0]] = '(' . $course[0] . ') ' . $course[1] . ' - ' . $course[2];
        if (intval($course[0]) == $selectedID) {
            $idToSelect = $selectedID;
        }
    }

    if (count($courses) > 0) {
        if (!isset($idToSelect)) {
            $idToSelect = array_key_first($courses);
        }
        $html = BaseHtmlLib::selectElement2('id:courseSelect,class:ui search selection dropdown', $courses, $idToSelect)->getHtml();
    }
}
echo $html;

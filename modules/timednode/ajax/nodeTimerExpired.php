<?php

/**
 * @package     timednode module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout'],
];
$allowedUsersAr = array_keys($neededObjAr);

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$retArray = ['status' => 'ERROR'];
session_write_close();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Convert input into a PHP array
    $json = json_decode(file_get_contents('php://input') ?? '', true);

    foreach (['userId', 'instanceId', 'nextNode'] as $m) {
        if (!array_key_exists($m, $json)) {
            $retArray['msg'] = sprintf(translateFN('Must pass %s', $m));
        }
    }

    if (!array_key_exists('msg', $retArray)) {
        /**
         * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
         */
        $dh = $GLOBALS['dh'];
        $currentLevel = (int) $dh->getStudentLevel($json['userId'], $json['instanceId']);
        $res = $dh->getNodeInfo($json['nextNode']);
        if (!AMADB::isError($res)) {
            $nextLevel = (int) $res['level'] ?? 0;
            $retArray['data'] = ['oldLevel' => $currentLevel, 'newLevel' => $nextLevel];
            if ($currentLevel + 1 == $nextLevel) {
                // set new student level
                $res = $dh->setStudentLevel($json['instanceId'], [$json['userId']], $nextLevel);
            } elseif ($currentLevel + 1 >= $nextLevel) {
                // do nothing, but do not reutrn error
                $res = true;
            } else {
                $retArray['msg'] = translateFN('Level not updated');
            }
        }
    }

    if (AMADB::isError($res) && !array_key_exists('msg', $retArray)) {
        $retArray['msg'] = $res->getMessage();
    }
    $retArray['status'] = (AMADB::isError($res) || array_key_exists('msg', $retArray)) ? 'ERROR' : 'OK';
}

header('Content-Type: application/json');
echo json_encode($retArray);
die();

<?php

/**
 * @package     main index
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        impexport
 * @version     0.1
 */

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Logger\ADALogger;
use Lynxlab\ADA\Main\Node\Node;

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

$nodeId = DataValidator::checkInputValues('nodeId', 'NodeId', INPUT_POST);
$direction = DataValidator::checkInputValues('direction', 'String', INPUT_POST);
$what = DataValidator::checkInputValues('what', 'String', INPUT_POST);

$directions = ['up', 'down'];
$things = [
    'ordine' => 'order',
    'livello' => 'level',
];
$response = true;

if (false !== $nodeId) {
    if (in_array($direction, $directions)) {
        if (in_array($what, array_keys($things))) {
            $node = (array) new Node($nodeId);
            if ($direction == 'up') {
                $node[$things[$what]]++;
            } elseif ($direction == 'down') {
                $node[$things[$what]]--;
            }
            $dh->doEditNode($node);
            $response = $node;
        } else {
            ADALogger::log("Invalid 'what' value: $what");
        }
    } else {
        ADALogger::log("Invalid 'direction' value: $direction");
    }
}

header('Content-Type: application/json');
die(json_encode($response));

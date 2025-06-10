<?php

/**
 * @package     edit node
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        impexport
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Services\NodeEditing\NodeEditing;

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

$nodeId = DataValidator::checkInputValues('id', 'NodeId', INPUT_POST);
$response = [
    'error' => false,
    'message' => '',
];

if (false !== $nodeId) {
    $node = new Node($nodeId);

    if ($node->full) {
        $node = (array) $node;
        $newData = [
            'name' => DataValidator::checkInputValues('name', 'String', INPUT_POST),
            'title' => DataValidator::checkInputValues('title', 'String', INPUT_POST),
            'level' => DataValidator::checkInputValues('level', 'String', INPUT_POST),
            'icon' => DataValidator::checkInputValues('icon', 'String', INPUT_POST),
            'type' => DataValidator::checkInputValues('type', 'String', INPUT_POST),
            'position' => DataValidator::checkInputValues('position', 'String', INPUT_POST),
            'order' => DataValidator::checkInputValues('order', 'String', INPUT_POST),
        ];

        foreach ($newData as $key => $val) {
            if ($val !== false) {
                if ($key === 'isforkedpaths') {
                    $node['is_forkedpaths'] = $val;
                }
                $node[$key] = $val;
            }
        }

        $result = NodeEditing::saveNode($node);
        if (AMADB::isError($result)) {
            $response['error'] = true;
            $response['message'] = $result->errorMessage();
        }
    } else {
        $response['error'] = true;
        $response['message'] = translateFN('Errore in lettura oggetto nodo');
    }
} else {
    $response['error'] = true;
    $response['message'] = translateFN('ID nodo non valido');
}

header('Content-Type: application/json');
die(json_encode($response));

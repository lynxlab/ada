<?php

use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\Utilities;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

TutorHelper::init($neededObjAr);

$error = true;
$data = null;

$id_user = (array_key_exists('id_user', $_GET) && intval($_GET['id_user']) > 0) ? intval($_GET['id_user']) : null;
$id_room = (array_key_exists('id_room', $_GET) && intval($_GET['id_room']) > 0) ? intval($_GET['id_room']) : null;

$data = VideoRoom::getInstanceLog($courseInstanceObj->getId(), $id_room, $id_user);
$data = array_map(function ($el) {
    $el['details']['rowId'] = 'row_' . $el['details']['id_room'];
    $el['details']['tipo_videochat_descr'] = VideoRoom::initialToDescr($el['details']['tipo_videochat']);
    if (array_key_exists('users', $el)) {
        $el['users'] = array_map(function ($u) use ($el) {
            if (array_key_exists('events', $u)) {
                $i = 0;
                $u['events'] = array_values($u['events']);
                while ($i < count($u['events']) - 1) {
                    if ($u['events'][$i]['uscita'] == $u['events'][$i + 1]['entrata'] && !is_null($u['events'][$i + 1]['uscita'])) {
                        $u['events'][$i + 1]['entrata'] = $u['events'][$i]['entrata'];
                        array_splice($u['events'], $i, 1);
                        $i = 0;
                    } else {
                        $i++;
                    }
                }
                foreach ($u['events'] as $i => $event) {
                    foreach (['entrata' => 'inizio', 'uscita' => 'fine'] as $what => $detail) {
                        $u['events'][$i][$what] = [
                            'wasnull' => is_null($event[$what]),
                            'timestamp' => is_null($event[$what]) ? $el['details'][$detail] : $event[$what],
                        ];
                        $u['events'][$i][$what]['display'] = Utilities::ts2dFN($u['events'][$i][$what]['timestamp']) . ' ' . Utilities::ts2tmFN($u['events'][$i][$what]['timestamp']);
                    }
                }
                $u['events'] = array_filter($u['events'], fn ($el) => $el['entrata']['timestamp'] != $el['uscita']['timestamp']);
            }
            return $u;
        }, $el['users']);
    }
    return $el;
}, $data);

$error = !(is_array($data) && count($data) > 0);

if ($error !== false) {
    $data = ['data' => []];
}

header('Content-Type: application/json');
die(json_encode(['data' => $data]));

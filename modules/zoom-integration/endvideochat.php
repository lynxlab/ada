<?php

/**
 * @package     zoom integration module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ComunicaHelper;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

if (!defined('CONFERENCE_TO_INCLUDE')) {
    define('CONFERENCE_TO_INCLUDE', 'ZoomConf');
}

if (!defined('DATE_CONTROL')) {
    define('DATE_CONTROL', false);
}

$request_body = file_get_contents('php://input');
$data = [];
if (strlen($request_body) > 0) {
    parse_str($request_body, $data);
}
if (count($data) == 0) {
    $data = $_REQUEST;
}

if (isset($data['p']) && strlen($data['p']) && DataValidator::validateTestername($data['p'])) {
    $GLOBALS['dh'] = new AMADataHandler(MultiPort::getDSN($data['p']));
    $videoroomObj = VideoRoom::getVideoObj();
    $logData = [
    'event' => VideoRoom::EVENT_EXIT,
    'id_user' => intval($data['id_user']),
    'id_room' => intval($data['id_room']),
    'id_istanza_corso' => intval($data['id_istanza_corso']),
    'is_tutor' => intval($data['ist']),
    ];
} else {
    /**
     * Clear node and layout variable in $_SESSION
     */
    $variableToClearAR = [];
    array_push($variableToClearAR, 'layout');
    array_push($variableToClearAR, 'user');
    array_push($variableToClearAR, 'course');
    array_push($variableToClearAR, 'course_instance');

    /**
     * Users (types) allowed to access this module.
     */
    $allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];

    /**
     * Performs basic controls before entering this module
     */
    $neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'tutor', 'course', 'course_instance', 'videoroom'],
    AMA_TYPE_TUTOR => ['layout', 'tutor', 'course', 'course_instance', 'videoroom'],
    ];

    /**
     * Performs basic controls before entering this module
     */
    $trackPageToNavigationHistory = false;
    require_once ROOT_DIR . '/include/module_init.inc.php';

    ComunicaHelper::init($neededObjAr);
    $logData = null;
}

if (isset($videoroomObj)) {
    $videoroomObj->logExit($logData);
}
?>
<script type="text/javascript">
  window.parent.postMessage('endVideochat', '<?php echo HTTP_ROOT_DIR; ?>');
</script>

<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_STUDENT => ['layout', 'course'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
$retArray = ['status' => 'ERROR', 'title' => Utilities::whoami(), 'msg' => translateFN("Errore sconosciuto")];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_SESSION['ada_remote_address'])) {
        $remote_address = $_SESSION['ada_remote_address'];
    } else {
        $remote_address = $_SERVER['REMOTE_ADDR'];
    }

    if (isset($_SESSION['ada_access_from'])) {
        $accessed_from = $_SESSION['ada_access_from'];
    } else {
        $accessed_from = ADA_GENERIC_ACCESS;
    }

    $nodeId = (isset($_POST['nodeId']) && strlen($_POST['nodeId']) > 0) ? trim($_POST['nodeId']) : $sess_id_node;
    $instanceId =  (!isset($sess_id_course_instance)  || $courseObj->getIsPublic()) ? 0 : $sess_id_course_instance;
    $retArray['data'] = [
        'idUser' => $sess_id_user,
        'idInstance' => $instanceId,
        'idNode' => $nodeId,
    ];

    if (true === $GLOBALS['dh']->addNodeHistory($sess_id_user, $instanceId, $nodeId, $remote_address, HTTP_ROOT_DIR, $accessed_from, true)) {
        $retArray['status'] = $retArray['msg'] = "OK";
    }
}

header('Content-Type: application/json');
die(json_encode($retArray));

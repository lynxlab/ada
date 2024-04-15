<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Main\Helper\ServiceHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();  // = author!

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
ServiceHelper::init($neededObjAr);

$self =  whoami();

/*
 * YOUR CODE HERE
 */

$success = 'author.php';
$menu = 'author.php';
$error = 'author.php';

$course_has_istance = $dh->courseHasInstances($id_course);
if (!$course_has_istance) {
    $res = $dh->removeCourse($id_course);
    if (AMADataHandler::isError($res)) {
        $msg = $res->getMessage();
    } else {
        $msg = translateFN('Cancellazione modello corso riuscita');
    }
    header("Location: $menu?msg=$msg");
    exit();
} else {
    $msg = translateFN('Cancellazione del corso non riuscita. Il corso ha istanze.');
    header("Location: $error?msg=$msg");
    exit();
}

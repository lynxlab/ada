<?php

/**
 * VideoPlayer Panel Code
 *
 * @author      Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2014, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        view
 * @version     0.1
 */

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Media\MediaViewer;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout','course'],
  AMA_TYPE_STUDENT      => ['layout','tutor','course','course_instance'],
  AMA_TYPE_TUTOR        => ['layout','course','course_instance'],
  AMA_TYPE_AUTHOR       => ['layout','course'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = 'index';

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
BrowsingHelper::init($neededObjAr);

/**
 * YOUR CODE HERE
 */

 /**
 * extract the author id from the passed media_url
 */
$regExp = preg_quote(HTTP_ROOT_DIR . MEDIA_PATH_DEFAULT, '/') . '(\d+)\/.*';
preg_match('/' . $regExp . '/', $_GET['media_url'], $matches);

// mathces[1] will hold the author id
if (!is_null($matches) && !empty($matches) && isset($matches[1]) && is_numeric($matches[1])) {
    $mediaObj = new MediaViewer(
        HTTP_ROOT_DIR . MEDIA_PATH_DEFAULT . $matches[1] . '/',
        [],
        [_VIDEO => VIDEO_PLAYING_MODE]
    );
    /**
     * only videos gets the openInRightPanel onclick function call
     * so I assume it's a video that we are going to render
     */
    $retVal = $mediaObj->getViewer([
            'type' => _VIDEO,
            'value' => basename($matches[0]),
            'width' => ((isset($_GET['width'])  && intval($_GET['width']) > 0) ? intval($_GET['width']) : null),
            'height' => ((isset($_GET['height']) && intval($_GET['height']) > 0) ? intval($_GET['height']) : null),
            ]);
} else {
    $retVal = translateFN('La url passata non è valida');
}

die($retVal);

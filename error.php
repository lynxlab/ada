<?php

use Lynxlab\ADA\Main\History\NavigationHistory;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/config_path.inc.php';

$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR,
                        AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN, AMA_TYPE_SWITCHER];
$neededObjAr = [
    AMA_TYPE_VISITOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_ADMIN => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],
];
/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();

/*
 * By default, on a fatal error, we redirect the user to the login page.
 */
$homepage = HTTP_ROOT_DIR;
/*
 * Here we check if there's a logged user, with a proper navigation history.
 * If both exist, we check if the module that have raised a fatal error is
 * different from the user's home page.
 * In this case, it is safe to redirect him to his/her homepage.
 * Otherwise he/she will be redirected to the ada login page.
 */
if (isset($_SESSION['sess_userObj'])) {
    $userObj = $_SESSION['sess_userObj'];
    if ($userObj instanceof ADALoggableUser) {
        $user_name = $userObj->getFirstName();
        $user_type = $userObj->getTypeAsString();
        $userHomePage = $userObj->getHomePage();

        if (isset($_SESSION['sess_navigation_history'])) {
            $navigationHistory = $_SESSION['sess_navigation_history'];
            if ($navigationHistory instanceof NavigationHistory) {
                if ($navigationHistory->lastModule() != $userHomePage) {
                    $homepage = $userHomePage;
                }
            }
        }
    }
}

$error_message = translateFN('A fatal error occurred. You can try to enter your home page. If it does not work, please contact the webmaster.');

$error_div = '<div class="unrecoverable">'
           . $error_message
           . '</div>';

$content_dataAr = [
    'home_link' => $homepage,
    'today' => $ymdhms,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'course_title' => translateFN('Notifica errore'),
    'data' => $error_div,
    'status' => translateFN('Notifica di errore'),
];
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);

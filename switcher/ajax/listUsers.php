<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\AMADatatables;
use Lynxlab\ADA\Main\AMA\AMATesterDataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\ActionsEvent;
use Lynxlab\ADA\Module\Impersonate\AMAImpersonateDataHandler;
use Lynxlab\ADA\Module\Impersonate\ImpersonateException;
use Lynxlab\ADA\Module\Impersonate\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * serverSide is either not set or coming as a global
 * from the including script (e.g. switcher/list_users.php).
 * If it's not set the request is coming from a server side datatable.
 */

$serverSide = $GLOBALS['serverSide'] ?? true;

if ($serverSide) {
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
    $allowedUsersAr = [AMA_TYPE_SWITCHER];

    /**
     * Performs basic controls before entering this module
     */
    $neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
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
     * @var object $user_messages
     * @var object $user_agenda
     * @var array $user_events
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
    SwitcherHelper::init($neededObjAr);
}

$usersType = DataValidator::checkInputValues('list', 'Value', INPUT_GET, null);
if (is_null($usersType)) {
    $usersType = DataValidator::checkInputValues('list', 'Value', INPUT_POST, 'students');
}
$fieldsAr = ['nome', 'cognome', 'username', 'tipo', 'stato'];
$amaUserType = AMA_TYPE_VISITOR;
/**
 * @var AMATesterDataHandler $dh
 */
$dh = $GLOBALS['dh'];
$amaUserType = match ($usersType) {
    'authors' => AMA_TYPE_AUTHOR,
    'tutors' => AMA_TYPE_TUTOR,
    'students' => AMA_TYPE_STUDENT,
    default => AMA_TYPE_STUDENT,
};

if (ModuleLoaderHelper::isLoaded('IMPERSONATE')) {
    // get the list of users linked to the current listed type
    $impDH = AMAImpersonateDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
    try {
        $linkedUsers = $impDH->findBy('LinkedUsers', [
            'source_type' => $amaUserType,
            'is_active' => true,
        ]);
    } catch (ImpersonateException) {
        $linkedUsers = [];
    }
}

$usersAr = $dh->getAjaxUsersList($fieldsAr, $amaUserType);

if ($usersAr instanceof AMADatatables) {
    $response = $usersAr->generate()->toArray();
    $usersAr = $response['data'] ?? [];
} else {
    $usersAr = [];
}

if (is_array($usersAr) && count($usersAr) > 0) {
    $imgHttp = HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'];
    $edit_img = CDOMElement::create('img', 'src:' . $imgHttp . '/img/edit.png,alt:edit');
    $view_img = CDOMElement::create('img', 'src:' . $imgHttp . '/img/zoom.png,alt:view');
    $delete_img = CDOMElement::create('img', 'src:' . $imgHttp . '/img/trash.png,alt:delete');
    $undelete_img = CDOMElement::create('img', 'src:' . $imgHttp . '/img/revert.png,alt:undelete');

    foreach ($usersAr as $userKey => $user) {
        array_shift($user); // shift fake field added for the datatable to work
        $userId = $user[0];
        if ($user[4] == AMA_TYPE_SUPERTUTOR) {
            $imgDetails = CDOMElement::create('img', 'src:' . $imgHttp . '/img/supertutoricon.png');
            $imgDetails->setAttribute('title', translateFN('Super Tutor'));
        } elseif ($user[5] == ADA_STATUS_REGISTERED || $user[5] == ADA_STATUS_ANONYMIZED) {
            $imgDetails = CDOMElement::create('img', 'src:' . $imgHttp . '/img/details_open.png');
            $imgDetails->setAttribute('title', translateFN('visualizza/nasconde i dettagli dell\'utente'));
            $imgDetails->setAttribute('onclick', "toggleDetails($userId,this);");
            $imgDetails->setAttribute('style', 'cursor:pointer;');
        }
        if (isset($imgDetails)) {
            $imgDetails->setAttribute('class', 'imgDetls tooltip');
        } else {
            $imgDetails = CDOMElement::create('span');
        }

        $actionsArr = [];

        if ($user[5] != ADA_STATUS_ANONYMIZED) {
            $edit_link = BaseHtmlLib::link("edit_user.php?id_user=$userId&usertype=" . $user[4], $edit_img->getHtml());
            $edit_link->setAttribute('class', 'tooltip');
            $edit_link->setAttribute('title', translateFN('Modifica dati utente'));
            $actionsArr[] = $edit_link;
        } else {
            $view_link = BaseHtmlLib::link("view_user.php?id_user=$userId", $view_img->getHtml());
            $view_link->setAttribute('class', 'tooltip');
            $view_link->setAttribute('title', translateFN('Visualizza dati utente'));
            $actionsArr[] = $view_link;
        }

        if ($user[5] == ADA_STATUS_REGISTERED) {
            $delete_link = BaseHtmlLib::link("delete_user.php?id_user=$userId", $delete_img->getHtml());
            $delete_link->setAttribute('class', 'tooltip');
            $delete_link->setAttribute('title', translateFN('Cancella utente'));
            $actionsArr[] = $delete_link;
        } elseif ($user[5] != ADA_STATUS_ANONYMIZED) {
            $undelete_link = BaseHtmlLib::link("delete_user.php?restore=1&id_user=$userId", $undelete_img->getHtml());
            $undelete_link->setAttribute('class', 'tooltip');
            $undelete_link->setAttribute('title', translateFN('Ripristina utente'));
            $actionsArr[] = $undelete_link;
        }

        if (ModuleLoaderHelper::isLoaded('IMPERSONATE') && $user[5] == ADA_STATUS_REGISTERED) {
            $impActions = Utils::buildActionsLinks($userId, $user[4], $linkedUsers);
            if (is_array($impActions) && count($impActions) > 0) {
                $actionsArr =  array_merge($actionsArr, $impActions);
            }
        }

        $actions = BaseHtmlLib::plainListElement('class:inline_menu', $actionsArr);

        /**
         * @author giorgio 11/apr/2018
         *
         * add the stato field for all user types
         */
        $isConfirmed = ($user[5] == ADA_STATUS_REGISTERED) ? translateFN("Si") : translateFN("No");

        array_unshift($user, $imgDetails->getHtml());
        $user[5] = $actions->getHtml();
        $user[6] = $isConfirmed;
        $usersAr[$userKey] = $user;

        unset($imgDetails);
    }

    if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
        $event = ADAEventDispatcher::buildEventAndDispatch(
            [
                'eventClass' => ActionsEvent::class,
                'eventName' => ActionsEvent::LIST_USERS,
            ],
            ['userType' => $amaUserType],
            [
                'tbody' => $usersAr,
            ]
        );
        try {
            $usersAr = $event->getArgument('tbody');
        } catch (InvalidArgumentException) {
            // do nothing
        }
    }

    $response['data'] = $usersAr;
}

if ($serverSide) {
    header('Content-Type: application/json');
    die(json_encode($response));
} else {
    return $response['data'] ?? [];
}

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Impersonate\AMAImpersonateDataHandler;
use Lynxlab\ADA\Module\Impersonate\ImpersonateException;
use Lynxlab\ADA\Module\Impersonate\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

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

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();  // = admin!

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

/*
 * YOUR CODE HERE
 */

$usersType = DataValidator::validateNotEmptyString($_GET['list']);
$fieldsAr = ['nome', 'cognome', 'username', 'tipo', 'stato'];
$amaUserType = AMA_TYPE_VISITOR;
switch ($usersType) {
    case 'authors':
        $usersAr = $dh->getAuthorsList($fieldsAr);
        $profilelist = translateFN('lista degli autori');
        $amaUserType = AMA_TYPE_AUTHOR;
        break;
    case 'tutors':
        $usersAr = $dh->getTutorsList($fieldsAr);
        if (defined('AMA_TYPE_SUPERTUTOR')) {
            $usersAr = array_merge($usersAr, $dh->getSupertutorsList($fieldsAr));
        }
        $profilelist = translateFN('lista dei tutors');
        /**
         * @author steve 28/mag/2020
         *
         * adding link to Tutor Subscrition from file
         */
        $buttonSubscriptions = CDOMElement::create('button', 'class:Subscription_Button');
        $buttonSubscriptions->setAttribute('onclick', 'javascript:goToSubscription(\'tutor_subscriptions\');');
        $buttonSubscriptions->addChild(new CText(translateFN('Carica da file') . '...'));
        $amaUserType = AMA_TYPE_TUTOR;
        break;
    case 'students':
    default:
        /**
         * @author giorgio 29/mag/2013
         *
         * if we're listing students, let's add the stato field as well
         */
        $usersAr = $dh->getStudentsList($fieldsAr);
        $profilelist = translateFN('lista degli studenti');
        $amaUserType = AMA_TYPE_STUDENT;
        break;
}

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

if (is_array($usersAr) && count($usersAr) > 0) {
    $UserNum = count($usersAr);
    $thead_data = [
        null,
        translateFN('id'),
        translateFN('nome'),
        translateFN('cognome'),
        translateFN('username'),
        translateFN('azioni'),
        translateFN('Confermato'),
    ];
    /**
     * @author giorgio 29/mag/2013
     *
     * if we're listing students, let's add the stato field as well
     */

    $tbody_data = [];
    $edit_img = CDOMElement::create('img', 'src:img/edit.png,alt:edit');
    $view_img = CDOMElement::create('img', 'src:img/zoom.png,alt:view');
    $delete_img = CDOMElement::create('img', 'src:img/trash.png,alt:delete');
    $undelete_img = CDOMElement::create('img', 'src:img/revert.png,alt:undelete');

    foreach ($usersAr as $user) {
        $userId = $user[0];
        if ($user[4] == AMA_TYPE_SUPERTUTOR) {
            $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/supertutoricon.png');
            $imgDetails->setAttribute('title', translateFN('Super Tutor'));
        } elseif ($user[5] == ADA_STATUS_REGISTERED || $user[5] == ADA_STATUS_ANONYMIZED) {
            $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/details_open.png');
            $imgDetails->setAttribute('title', translateFN('visualizza/nasconde i dettagli dell\'utente'));
            $imgDetails->setAttribute('onclick', "toggleDetails($userId,this);");
            $imgDetails->setAttribute('style', 'cursor:pointer;');
        }
        if (isset($imgDetails)) {
            $imgDetails->setAttribute('class', 'imgDetls tooltip');
        } else {
            $imgDetails = CDOMElement::create('span');
        }


        $User_firstname = CDOMElement::create('span');
        $User_firstname->setAttribute('class', 'fullname');
        $User_firstname->addChild(new CText($user[1]));

        $User_lastname = CDOMElement::create('span');
        $User_lastname->setAttribute('class', 'fullname');
        $User_lastname->addChild(new CText($user[2]));

        $span_UserName = CDOMElement::create('span');
        $span_UserName->setAttribute('class', 'UserName');
        $span_UserName->addChild(new CText($user[3]));

        $actionsArr = [];

        if ($user[5] == ADA_STATUS_REGISTERED) {
            $edit_link = BaseHtmlLib::link("edit_user.php?id_user=$userId&usertype=" . $user[4], $edit_img->getHtml());
            $edit_link->setAttribute('class', 'tooltip');
            $edit_link->setAttribute('title', translateFN('Modifica dati utente'));
            $actionsArr[] = $edit_link;

            $view_link = BaseHtmlLib::link("view_user.php?id_user=$userId", $view_img->getHtml());
            $view_link->setAttribute('class', 'tooltip');
            $view_link->setAttribute('title', translateFN('Visualizza dati utente'));
            $actionsArr[] = $view_link;

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

        $tmpArray = [$imgDetails->getHtml(), $userId, $User_firstname->getHtml(), $User_lastname->getHtml(), $span_UserName->getHtml(), $actions, $isConfirmed];
        unset($imgDetails);

        $tbody_data[] = $tmpArray;
    }
    $data = BaseHtmlLib::tableElement('id:table_users', $thead_data, $tbody_data);
    $data->setAttribute('class', $data->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
} else {
    $data = CDOMElement::create('span');
    $data->addChild(new CText(translateFN('Non sono stati trovati utenti')));
}

$label = $profilelist;

$helpSpan = CDOMElement::create('span');
$helpSpan->addChild(new CText(ucfirst(translateFN($profilelist . ' presenti nel provider') . ': ')));
$helpSpan->addChild(new CText($UserNum ?? 0));
/**
 * @author steve 28/mag/2020
 *
 * adding link to Tutor Subscrition from file
 */
if ($usersType == 'tutors') {
    $helpSpan->addChild($buttonSubscriptions);
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $helpSpan->getHtml(),
    'data' => $data->getHtml(),
    'edit_profile' => $userObj->getEditProfilePage(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    ROOT_DIR . '/js/include/jquery/dataTables/selectSortPlugin.js',
    JQUERY_NO_CONFLICT,
];


$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];
$render = null;
$optionsAr['onload_func'] = 'initDoc();';
if (ModuleLoaderHelper::isLoaded('IMPERSONATE')) {
    $layout_dataAr['JS_filename'][] = MODULES_IMPERSONATE_PATH . '/js/impersonateAPI.js';
    $layout_dataAr['CSS_filename'][] = MODULES_IMPERSONATE_PATH . '/layout/css/showHideDiv.css';
}
ARE::render($layout_dataAr, $content_dataAr, $render, $optionsAr);

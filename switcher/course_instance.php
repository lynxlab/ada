<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Badges\RewardedBadge;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

/**
 * Base config file
 */
require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'user', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'user', 'course_instance', 'course'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();  // = tutor!

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
 * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
SwitcherHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
/*
 * 1. ottieni gli studenti iscritti a questa istanza
 * 2. ottieni gli studenti preiscritti a questa istanza
 */

if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
    $data = new CText(translateFN('Corso non trovato'));
    $data = $data->getHtml();
} elseif (!($courseInstanceObj instanceof CourseInstance) || !$courseInstanceObj->isFull()) {
    $data = new CText(translateFN('Istanza corso non trovata'));
    $data = $data->getHtml();
} else {
    $courseId = $courseObj->getId();
    $instanceId = $courseInstanceObj->getId();
    $isTutorCommunity = $courseInstanceObj->isTutorCommunity();

    $subscriptions = Subscription::findSubscriptionsToClassRoom($instanceId, true);

    if (count($subscriptions) == 0) {
        $thead_data = [
            translateFN('Notifica'),
        ];
        $dataAr = [];
        $result_table = BaseHtmlLib::tableElement('id:course_instance_Table', $thead_data, $dataAr);
        $result_table->setAttribute('class', $result_table->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
        $table = $result_table->getHtml();
    } else {
        $ids_student = [];
        foreach ($subscriptions as $k => $v) {
            $ids_student[] = $v->getSubscriberId();
        }
        // retrieve data for subscription and add it to preexistent array
        if (!empty($ids_student)) {
            foreach ($dh->getStudentsSubscribedCourseInstance($ids_student) as $k => $v) {
                $student_subscribed_course_instance[$k] = $v;
            }
        }

        $arrayUsers = $subscriptions;

        $dataAr = [];
        $tooltipDiv = CDOMElement::create('div');

        $thead_data = [
            translateFN('Hidden_status'),
            translateFN('Id'),
            translateFN('Nome'),
            translateFN('Cognome'),
            translateFN('Status'),
            translateFN('Id_istance'),
            translateFN('Data iscrizione'),
            translateFN('Livello'),

        ];

        if (MODULES_BADGES) {
            $badgesKey = translateFN("Badges");
            array_push($thead_data, $badgesKey);
            RewardedBadge::loadInstanceRewards($courseId, $instanceId);
        }
        if (!$isTutorCommunity && ModuleLoaderHelper::isLoaded('CODEMAN')) {
            array_push($thead_data, translateFN('Codice iscrizione'));
        }
        if (!$isTutorCommunity && defined('ADA_PRINT_CERTIFICATE') && (ADA_PRINT_CERTIFICATE)) {
            array_push($thead_data, translateFN('Certificato'));
        }

        // $arrayOptionText associates ADA_STATUS code to text to print
        $arrayOptionText = [
            ADA_STATUS_PRESUBSCRIBED,
            ADA_STATUS_SUBSCRIBED,
            ADA_STATUS_REMOVED,
            ADA_STATUS_VISITOR,
            ADA_STATUS_REMOVED,
            ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED,
            ADA_SERVICE_SUBSCRIPTION_STATUS_TERMINATED,
        ];
        // $arrayOptions contains HTML Objects of option controller
        $arrayOptions = [];
        foreach ($arrayOptionText as $o) {
            $arrayOptions[$o] = CDOMElement::create('option');
            $arrayOptions[$o]->setAttribute('value', $o);
            $arrayOptions[$o]->addChild(new CText(Subscription::subscriptionStatusArray()[$o]));
        }

        foreach ($arrayUsers as $user) {
            $name = $user->getSubscriberFirstname();
            $lastname = $user->getSubscriberLastname();

            /* add tooltip */
            if (isset($student_subscribed_course_instance[$user->getSubscriberId()])) {
                $UserInstances = $student_subscribed_course_instance[$user->getSubscriberId()];
            } else {
                $UserInstances = null;
            }

            if (!is_null($UserInstances) && is_array($UserInstances) && count($UserInstances) > 0) {
                $title = 'Studente iscritto ai seguenti corsi :' . '<br />';

                foreach ($UserInstances as $UserInstance) {
                    $title = $title . '' . $UserInstance['titolo'] . ' - ' . $UserInstance['title'] . '<br />';
                }
            }
            $span_label = CDOMElement::create('span');
            $span_label->setAttribute('title', $title ?? '');
            $span_label->setAttribute('class', 'UserName tooltip');
            $span_label->setAttribute('id', $user->getSubscriberId());
            $span_label->addChild(new CText($name));

            $span_lastname = CDOMElement::create('span');
            $span_lastname->setAttribute('class', 'LastName');
            $span_lastname->addChild(new CText($lastname));

            $Tooltip = CDOMElement::create('div');
            $Tooltip->setAttribute('title', $title ?? '');
            $Tooltip->setAttribute('class', 'UserName tooltip');
            $Tooltip->setAttribute('id', 'user_tooltip_' . $user->getSubscriberId());
            $tooltipDiv->addChild($Tooltip);

            $title = '';

            /* select user status */
            $select = CDOMElement::create('select', 'class:select_status');
            // $select->setAttribute('id', $user->getSubscriberId().'_status');

            $opts = [];
            foreach ($arrayOptions as $status => $optObj) {
                $opts[$status] = clone $optObj;
            }
            $opts[$user->getSubscriptionStatus()]->setAttribute('selected', 'selected');
            $span_selected = CDOMElement::create('span');
            $span_selected->setAttribute('class', 'hidden_status');
            $span_selected->addChild(new CText(Subscription::subscriptionStatusArray()[$user->getSubscriptionStatus()]));
            foreach ($opts as $op) {
                $select->addChild($op);
            }
            $select->setAttribute('onchange', 'saveStatus(this)');

            $livello = $dh->getStudentLevel($user->getSubscriberId(), $instanceId);

            if (is_int($user->getSubscriptionDate())) { //if getSubscriptionDate() return an int, means that it is setted in Subscription costructor to time()
                $data_iscrizione = '-';
            } else {
                $data_iscrizione = Utilities::ts2dFN($user->getSubscriptionDate());
            }
            //            $span_idUser = CDOMElement::create('span');
            //            $span_idUser->setAttribute('class', 'idUser');
            //            $span_idUser->addChild(new CText($user->getSubscriberId()));
            //
            $span_instance = CDOMElement::create('span');
            $span_instance->setAttribute('class', 'id_instance');
            $span_instance->addChild(new CText($instanceId));

            //             $span_data = CDOMElement::create('span');
            //             $span_data->setAttribute('class', 'date');
            //             $span_data->addChild(new CText($data_iscrizione));

            $userArray = [
                translateFN('Hidden_status') => $span_selected->getHtml(),
                translateFN('Id') => $user->getSubscriberId(),
                translateFN('Nome') => $span_label->getHtml(),
                translateFN('Cognome') => $span_lastname->getHtml(),
                translateFN('Status') => $select->getHtml(),
                translateFN('Id_istance') => $span_instance->getHtml(),
                translateFN('Data iscrizione') => $data_iscrizione,
                translateFN('Livello') => $livello,
            ];

            if (MODULES_BADGES) {
                $userArray[$badgesKey] = RewardedBadge::buildStudentRewardHTML($courseId, $instanceId, $user->getSubscriberId())->getHtml();
            }
            if (!$isTutorCommunity && ModuleLoaderHelper::isLoaded('CODEMAN')) {
                $code = $user->getSubscriptionCode();
                $userArray[translateFN('Codice iscrizione')] = $code;
            }

            if (!$isTutorCommunity && defined('ADA_PRINT_CERTIFICATE') && (ADA_PRINT_CERTIFICATE)) {
                $certificate = !$isTutorCommunity && ADAUser::checkRequirementsCertificate($user->getSubscriberId(), $user->getSubscriptionStatus());
                if ($certificate) {
                    $linkCertificate = CDOMElement::create('a', 'href:../browsing/userCertificate.php?id_user=' . $user->getSubscriberId() . '&id_instance=' . $instanceId);
                    $linkCertificate->setAttribute('class', 'linkCertificate');
                    $imgDoc = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/document.png');
                    $imgDoc->setAttribute('class', 'imgDoc tooltip');
                    $imgDoc->setAttribute('title', translateFN('stampa certificato'));
                    $linkCertificate->addChild($imgDoc);
                } else {
                    $linkCertificate = CDOMElement::create('a', 'href:#');
                    $linkCertificate->setAttribute('class', 'linkCertificate');
                    $imgDoc = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/document.png');
                    $imgDoc->setAttribute('class', 'imgDoc tooltip');
                    $imgDoc->setAttribute('title', translateFN('certificato non disponibile'));
                    $linkCertificate->addChild($imgDoc);
                }
                $userArray[translateFN('Certificato')] = $linkCertificate->getHtml();
            }


            array_push($dataAr, $userArray);
        }

        $result_table = BaseHtmlLib::tableElement('id:course_instance_Table', $thead_data, $dataAr);
        $result_table->setAttribute('class', $result_table->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
        $table = $result_table->getHtml();
    }
}
$help = translateFN('Da qui il provider admin può gestire le iscrizioni alla classe selezionata: ') . $courseInstanceObj->getTitle();

$buttonSubscription = CDOMElement::create('button', 'class:Subscription_Button');
$buttonSubscription->setAttribute('onclick', 'javascript:goToSubscription(\'subscribe\');');
$buttonSubscription->addChild(new CText(translateFN('Iscrivi studente')));
/**
 * @author steve 28/mag/2020
 *
 * If module StudentsGroups is loaded, this button now is not useful, since we can (and should) load students as groups
 */
if (ModuleLoaderHelper::isLoaded('STUDENTSGROUPS') === true) {
    $buttonSubscriptions = CDOMElement::create('span');
    $buttonSubscriptions->addChild(new CText(''));
} else {
    $buttonSubscriptions = CDOMElement::create('button', 'class:Subscription_Button');
    $buttonSubscriptions->setAttribute('onclick', 'javascript:goToSubscription(\'subscriptions\');');
    $buttonSubscriptions->addChild(new CText(translateFN('Upload file')));
}

$buttondownloadCertificates = CDOMElement::create('button', 'class:Subscription_Button');
$buttondownloadCertificates->setAttribute('onclick', 'javascript:downloadCertificates(' . $instanceId . ');');
$buttondownloadCertificates->addChild(new CText(translateFN('Download certificati')));

/*
* OUTPUT
*/
$optionsAr = ['onload_func' => "initDoc();"];

$content_dataAr = [
    'path' => $path ?? '',
    'label' => $label ?? '',
    'course_title' => translateFN('Gestione classe - ') . $courseObj->getTitle() . ' [' . $courseId . '] - ' . $courseInstanceObj->getTitle() . ' [' . $instanceId . ']',
    'status' => $status,
    'user_name' => $user_name,
    'user_type' => $user_type,
    'menu' => $menu ?? '',
    'help' => $help,
    'data' => $data ?? '',
    'table' => $table,
    'tooltip' => isset($tooltipDiv) ? $tooltipDiv->getHtml() : '',
    'edit_profile' => $userObj->getEditProfilePage(),
    'buttonSubscription' => $buttonSubscription->getHtml(),
    'buttonSubscriptions' => $buttonSubscriptions->getHtml(),
    'messages' => $user_messages->getHtml(),
    'agenda ' => $user_agenda->getHtml(),
];
if (defined('ADA_PRINT_CERTIFICATE') && (ADA_PRINT_CERTIFICATE === true)) {
    $content_dataAr['buttondownloadCertificates'] = $buttondownloadCertificates->getHtml();
}
$layout_dataAr['CSS_filename'] =  [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_REDRAW,
    JQUERY_DATATABLE_DATE,
    JS_VENDOR_DIR . '/jquery-blockui/jquery.blockUI.js',
    ROOT_DIR . '/js/include/jquery/dataTables/selectSortPlugin.js',
    JQUERY_NO_CONFLICT,

];



ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

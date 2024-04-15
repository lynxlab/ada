<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\User\ADAPractitioner;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\ARE;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\History\History;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;

use function \translateFN;

/**
 *
 * @package     subscription
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright           Copyright (c) 2009-2012, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        info
 * @version     0.2
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\InstancePaypalForm;
use Lynxlab\ADA\Main\Forms\InstanceTransferForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\todayDateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Performs basic controls before entering this module
 */
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'course', 'course_instance'],
];

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
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/*
 * INCLUSIONE SPECIFICA PER PAYPAL
 */
if (file_exists(ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php')) {
    require_once ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php';
    $paypal_allowed = true;
}

$today_date = todayDateFN();

//$id_course_instance = $_REQUEST['id_instance'];
//$id_studente = $_REQUEST['id_student'];

$providerId = DataValidator::isUinteger($_GET['provider']);
$courseId = DataValidator::isUinteger($_GET['id_course']);
$instanceId = DataValidator::isUinteger($_GET['id_course_instance']);


$testerInfoAr = $common_dh->getTesterInfoFromId($providerId, AMA_FETCH_ASSOC);
//var_dump($testerInfoAr);
if (!AMACommonDataHandler::isError($testerInfoAr)) {
    $provider_name = $testerInfoAr['nome'];
    $tester = $testerInfoAr['puntatore'];
    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
    //  var_dump($newTesterId);die();
    $GLOBALS['dh'] = $tester_dh;
    /*
     * Instance Object
     */
    $instanceObj = new CourseInstance($instanceId);
    //    print_r($instanceObj);
    $price = $instanceObj->getPrice();
    $id_course = $instanceObj->getCourseId();
    $course = $dh->get_course($courseId);
    //    print_r($course);
    $course_name = $course['titolo'];

    $item_desc = translateFN('Iscrizione al corso');
    if (floatval($price) > 0) {
        $self =  'iscrizione_pagamento';
        $GLOBALS['self'] = $self;
    } else {
        $self =  'iscrizione_gratis';
        $GLOBALS['self'] = $self;
    }

    /*
     * Get/set Paypal defintion
     */
    if ($paypal_allowed) {
        $business = PAYPAL_ACCOUNT;
        $action = PAYPAL_ACTION;
        $currency_code = CURRENCY_CODE;
        $rm = RM;
        $amount1 = $price;
        if ($amount1 > 0) {
            $studentId = $userObj->getId();
            $cmd = PAYPAL_CMD;
            $no_shipping = NO_SHIPPING;
            $price = str_replace(".", ",", $amount1);
            $notify_url = "$http_root_dir/browsing/student_course_instance_subscribe_ipn.php?instance=$instanceId&student=$studentId&provider=$providerId&course=$courseId";
            $return_url = "$http_root_dir/browsing/student_course_instance_subscribe_confirm.php?instance=$instanceId&student=$studentId&provider=$providerId&course=$courseId";
            $item_desc = translateFN('Iscrizione al corso');
            $formData = [
                'id_course_instance' => $instanceId,
                'business' => $business,
                'action' => $action,
                'currency_code' => $currency_code,
                'notify_url' => $notify_url,
                'return' => $return_url,
                'upload' => "1",
                'address1' => $userObj->getAddress(),
                'city' => $userObj->getCity(),
                'zip' => '00000', //$userObj->getCAP(),
                'country' => $userObj->getCountry(),
                'first_name' => $userObj->getFirstName(),
                'last_name' => $userObj->getLastName(),
                'address_override' => "0",
                'email' => $userObj->getEmail(),
                'amount_1' => $amount1,
                'cmd' => $cmd,
                'rm' => $rm,
                'item_name_1' => $item_desc . " " . $course_name,
                'no_shipping' => $no_shipping,
            ];

            $form = new InstancePaypalForm();
            $form->fillWithArrayData($formData);
            $data = $form->getHtml();
            //            print_r($form);
            //$form->fillWithRequestData($request);
        }
    }

    $formDataTransfer = [
        'instance' => $instanceId,
        'course' => $courseId,
        'provider' => $providerId,
        'student' => $studentId,
    ];

    $formTransfer = new InstanceTransferForm();
    $formTransfer->fillWithArrayData($formDataTransfer);
    $dataTransfer = $formTransfer->getHtml();
    /*
        $href_conferma_bonifico = "$http_root_dir/browsing/student_course_instance_bonifico.php?instance=$instanceId&student=$studentId&provider=$providerId&course=$courseId";
        $link_conferma_bonifico = '<a href="'.$href_conferma_bonifico.'">'.translateFN('pagherò con Bonifico') . '</a>';
     *
     */
    $link_annulla_iscrizione = '<a href="' . $http_root_dir . '/info.php?op=undo_subscription&instance=' . $instanceId . '&student=' . $studentId
                               . '&provider=' . $providerId . '&course=' . $courseId
                               . '">' . translateFN('Annulla iscrizione') . '</a>';
    $content_dataAr = [
    // 'home'=>$home,
    'menu' => $menu ?? null,
     'data' => $data,
     'data_bonifico' =>  $dataTransfer,
     'help' => $help ?? null,
    // 'status'=>$status,
     'user_name' => $user_name,
     'user_type' => $user_type,
     'messages' => $user_messages->getHtml(),
     'agenda' => $user_agenda->getHtml(),
     'titolo_corso' => $course_name,
     'annulla_iscrizione' => $link_annulla_iscrizione,
     'price' => $price,
     'complete_name' => $userObj->getFirstName() . ' ' . $userObj->getLastName(),
    ];
}
$help = '';
$optionsAr['onload_func'] = 'initDoc();';

//print_r($content_dataAr);
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

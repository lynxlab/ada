<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Forms\UserFindForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;
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
    AMA_TYPE_SWITCHER => ['layout', 'user', 'course', 'course_instance'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();

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
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $canSubscribeUser = false;
    if ($courseInstanceObj instanceof  CourseInstance && $courseInstanceObj->isFull()) {
        $startStudentLevel = $courseInstanceObj->start_level_student;
    }

    if (isset($_POST['findByUsername'])) {
        $form = new UserFindForm();
        $form->fillWithPostData();
        if ($form->isValid()) {
            $courseInstanceId = $_POST['id_course_instance'];
            $subscriberObj = MultiPort::findUserByUsername($_POST['username']);
            $canSubscribeUser = false;
            if ($subscriberObj instanceof ADAUser) {
                $result = $dh->studentCanSubscribeToCourseInstance($subscriberObj->getId(), $courseInstanceId);
                if (!AMADataHandler::isError($result) && $result !== false) {
                    $canSubscribeUser = $courseInstanceObj instanceof CourseInstance &&
                        $courseInstanceObj->isFull() &&
                        $courseInstanceObj->getServiceLevel() != ADA_SERVICE_TUTORCOMMUNITY;
                }
            } elseif ($subscriberObj instanceof ADAPractitioner) {
                /**
                 * @author giorgio 14/apr/2015
                 *
                 * If the switcher is trying to subscribe a tutor, do it only
                 * if the course instance belongs to a service of type
                 * ADA_SERVICE_TUTORCOMMUNITY
                 */
                $canSubscribeUser = $courseInstanceObj instanceof CourseInstance &&
                    $courseInstanceObj->isFull() &&
                    $courseInstanceObj->getServiceLevel() == ADA_SERVICE_TUTORCOMMUNITY;
            } else {
                $canSubscribeUser = false;
            }
            if ($canSubscribeUser) {
                $courseProviderAr = $common_dh->getTesterInfoFromIdCourse($courseObj->getId());
                if (!AMADB::isError($courseProviderAr) && is_array($courseProviderAr) && isset($courseProviderAr['puntatore'])) {
                    if (!in_array($courseProviderAr['puntatore'], $subscriberObj->getTesters())) {
                        // subscribe user to course provider
                        $canSubscribeUser = Multiport::setUser($subscriberObj, [$courseProviderAr['puntatore']]);
                        if (!$canSubscribeUser) {
                            $data = new CText(translateFN('Problemi nell\'iscrizione utente al provider del corso.') . ' ' . translateFN('Utente non iscritto'));
                        }
                    }
                    if ($canSubscribeUser) {
                        $subscriptionDate = 0;
                        $s = new Subscription($subscriberObj->getId(), $courseInstanceId, $subscriptionDate, $startStudentLevel);
                        $s->setSubscriptionStatus(ADA_STATUS_SUBSCRIBED);
                        Subscription::addSubscription($s);
                        $data = new CText(translateFN('Utente iscritto'));
                    } else {
                        $data = new CText(translateFN('Problemi') . ' ' . translateFN('Utente non iscritto'));
                    }
                } else {
                    $data = new CText(translateFN('Problemi nel recuperare il provider del corso.') . ' ' . translateFN('Utente non iscritto'));
                }
            } else {
                $data = new CText(translateFN('Problemi') . ' ' . translateFN('Utente non iscritto'));
            }
        } else {
            $data = new CText(translateFN('Dati inseriti non validi'));
        }
    } else {
        $data = new CText('');
    }
} else {
    if ($courseInstanceObj instanceof  CourseInstance && $courseInstanceObj->isFull()) {
        $formData = [
            'id_course_instance' => $courseInstanceObj->getId(),
        ];
        $data = new UserFindForm();
        $data->fillWithArrayData($formData);
    } else {
        $data = new CText(translateFN('Classe non trovata'));
    }
}
$help = translateFN('Da qui il provider admin può iscrivere uno studente già registrato alla classe selezionata');

/*
 * OUTPUT
 */
$content_dataAr = [
    'path' => $path ?? '',
    'label' => $label ?? '',
    'status' => $status ?? '',
    'user_name' => $user_name,
    'user_type' => $user_type,
    'menu' => $menu ?? '',
    'help' => $help ?? '',
    'data' => isset($data) ? $data->getHtml() : '',
    'messages' => isset($user_messages) ? $user_messages->getHtml() : '',
    'agenda ' => isset($user_agenda) ? $user_agenda->getHtml() : '',
];

ARE::render($layout_dataAr, $content_dataAr);

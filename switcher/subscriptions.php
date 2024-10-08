<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Forms\FileUploadForm;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Upload\FileUploader;
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
$variableToClearAR = ['layout', 'user', 'course', 'course_instance'];

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
$self =  Utilities::whoami();

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
$common_dh = AMACommonDataHandler::getInstance();

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    //    $fileUploader = new FileUploader(ROOT_DIR . '/uploadFile/uploaded_files/switcher/' . $userObj->getId().'/');
    $fileUploader = new FileUploader(ADA_UPLOAD_PATH . $userObj->getId() . '/');

    if ($fileUploader->upload() == false) {
        $data = new CText($fileUploader->getErrorMessage());
    } else {
        $courseId = $_POST['id_course'];
        $courseInstanceId = $_POST['id_course_instance'];
        $FlagFileWellFormat = true;
        if (is_readable($fileUploader->getPathToUploadedFile())) {
            $usersToSubscribe = file($fileUploader->getPathToUploadedFile());

            /*remove blanck line form array*/
            foreach ($usersToSubscribe as $key => $value) {
                if (!trim($value)) {
                    unset($usersToSubscribe[$key]);
                }
            }


            foreach ($usersToSubscribe as $subscriber) {
                $userDataAr = explode(',', $subscriber);
                $countAr = count($userDataAr);
                if ($countAr < 3) {
                    $FlagFileWellFormat = false;
                    break;
                }
                if ($userDataAr[0] == null) {
                    $FlagFileWellFormat = false;
                    break;
                }
                if ($userDataAr[1] == null) {
                    $FlagFileWellFormat = false;
                    break;
                }
                if ($userDataAr[2] == null) {
                    $FlagFileWellFormat = false;
                    break;
                }
                if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true && $userDataAr[3] == null) {
                    $FlagFileWellFormat = false;
                    break;
                }
            }
            if ($FlagFileWellFormat) {
                $subscribed = 0;
                $alreadySubscribed = 0;
                $notStudents = 0;
                $subscribers = count($usersToSubscribe);

                $admtypeAr = [AMA_TYPE_ADMIN];
                $admList = $common_dh->getUsersByType($admtypeAr);
                if (!AMADataHandler::isError($admList)) {
                    $adm_uname = $admList[0]['username'];
                } else {
                    $adm_uname = ''; // ??? FIXME: serve un superadmin nel file di config?
                }

                $courseTitle = $courseObj->getTitle();

                foreach ($usersToSubscribe as $subscriber) {
                    $canSubscribeUser = false;
                    $userDataAr = array_map('trim', explode(',', $subscriber));

                    $subscriberObj = MultiPort::findUserByUsername(trim($userDataAr[2]));
                    if ($subscriberObj == null) {
                        $subscriberObj = new ADAUser(
                            [
                                'nome' => trim($userDataAr[0]),
                                'cognome' => trim($userDataAr[1]),
                                'email' => trim($userDataAr[2]),
                                'tipo' => AMA_TYPE_STUDENT,
                                'username' => trim($userDataAr[2]),
                                'stato' => ADA_STATUS_PRESUBSCRIBED,
                                'birthcity' => '',
                            ]
                        );

                        if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
                            $subscriberObj->setPassword((isset($userDataAr[3]) && strlen($userDataAr[3]) > 0) ? $userDataAr[3] : time());
                            $subscriberObj->setEmail('');
                            $subscriberObj->setStatus(ADA_STATUS_REGISTERED);
                        } else {
                            $subscriberObj->setPassword(time());
                        }

                        /**
                         * @author giorgio 06/mag/2014 11:25:21
                         *
                         * If it's not a multiprovider environment,
                         * user must be subscribed to switcher's own
                         * provider only.
                         * User must be subscribed to the ADA_PUBLIC_TESTER
                         * only in a multiprovider environment.
                         */

                        $provider_to_subscribeAr =  [$sess_selected_tester];
                        if (MULTIPROVIDER) {
                            array_unshift($provider_to_subscribeAr, ADA_PUBLIC_TESTER);
                        }
                        $result = MultiPort::addUser($subscriberObj, $provider_to_subscribeAr);
                        if ($result > 0) {
                            $id_user = $result;
                            $tokenObj = TokenManager::createTokenForUserRegistration($subscriberObj);
                            if ($tokenObj == false) {
                                $message = translateFN('An error occurred while performing your request. Pleaser try again later.');
                                header('Location:' . HTTP_ROOT_DIR . "/index.php?message=$message");
                                exit();
                            }
                            $token = $tokenObj->getTokenString();
                            $title = PORTAL_NAME . ': ' . translateFN('ti preghiamo di confermare la tua registrazione.');

                            $text = sprintf(
                                translateFN('Gentile %s, ti chiediamo di confermare la tua registrazione in ') . PORTAL_NAME . '.',
                                $subscriberObj->getFullName()
                            )
                                . PHP_EOL . PHP_EOL
                                . translateFN('Lo username che ti è stato assegnato è il seguente:')
                                . ' ' . $subscriberObj->getUserName()
                                . PHP_EOL . PHP_EOL
                                . translateFN('Puoi confermare la tua registrazione in ') . PORTAL_NAME . ' ' . translateFN('seguendo questo link') . ': '
                                . PHP_EOL
                                . ' ' . HTTP_ROOT_DIR . "/browsing/confirm.php?uid=$id_user&tok=$token";

                            $message_ha = [
                                'titolo' => $title,
                                'testo' => $text,
                                'destinatari' => [$subscriberObj->getUserName()],
                                'data_ora' => 'now',
                                'tipo' => ADA_MSG_MAIL,
                                'mittente' => $adm_uname,
                            ];

                            if (MULTIPROVIDER) {
                                $mh = MessageHandler::instance(MultiPort::getDSN(ADA_PUBLIC_TESTER));
                            } else {
                                $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
                            }

                            /**
                             * Send the message as an internal message
                             */
                            //                        $result = $mh->sendMessage($message_ha);
                            //                        if(AMADataHandler::isError($result)) {
                            //                        }
                            /**
                             * Send the message an email message
                             */
                            //                        $message_ha['tipo'] = ADA_MSG_MAIL;
                            $result = $mh->sendMessage($message_ha);
                            if (AMADataHandler::isError($result)) {
                            }

                            $canSubscribeUser = true;
                        }
                    } elseif ($subscriberObj instanceof ADAUser) {
                        $courseProviderAr = $common_dh->getTesterInfoFromIdCourse($courseId);
                        $isUserInProvider = in_array($courseProviderAr['puntatore'], $subscriberObj->getTesters());
                        if (!$isUserInProvider) {
                            // subscribe user to course provider
                            $isUserInProvider = Multiport::setUser($subscriberObj, [$courseProviderAr['puntatore']]);
                        }
                        if ($isUserInProvider) {
                            $result = $dh->studentCanSubscribeToCourseInstance($subscriberObj->getId(), $courseInstanceId);
                            if (!AMADataHandler::isError($result) && $result !== false) {
                                $canSubscribeUser = true;
                            } else {
                                $alreadySubscribed++;
                            }
                        }
                    } else {
                        $notStudents++;
                    }

                    if ($canSubscribeUser) {
                        $s = new Subscription($subscriberObj->getId(), $courseInstanceId);
                        $s->setSubscriptionStatus(ADA_STATUS_SUBSCRIBED);
                        Subscription::addSubscription($s);

                        $title = PORTAL_NAME . ': ' . translateFN('sei stato iscritto al corso') . ' ' . $courseTitle;

                        $text = sprintf(
                            translateFN('Gentile %s,  ') . translateFN('sei stato iscritto al corso ') . ' ' . $courseTitle . '.',
                            $subscriberObj->getFullName()
                        )
                            . PHP_EOL . PHP_EOL
                            . translateFN('Per accedere al corso dovrai fare login, scrivendo il tuo username e la tua password a questo indirizzo:')
                            . PHP_EOL
                            . ' ' . HTTP_ROOT_DIR . "/index.php";

                        $message_ha = [
                            'titolo' => $title,
                            'testo' => $text,
                            'destinatari' => [$subscriberObj->getUserName()],
                            'data_ora' => 'now',
                            'tipo' => (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) ? ADA_MSG_SIMPLE : ADA_MSG_MAIL,
                            'mittente' => $adm_uname,
                        ];

                        if (MULTIPROVIDER) {
                            $mh = MessageHandler::instance(MultiPort::getDSN(ADA_PUBLIC_TESTER));
                        } else {
                            $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
                        }
                        /**
                         * Send the message an email message
                         */
                        $result = $mh->sendMessage($message_ha);
                        if (AMADataHandler::isError($result)) {
                        }

                        $subscribed++;
                    }
                }
                $message = sprintf(translateFN('Sono stati iscritti %d studenti su %d'), $subscribed, $subscribers);

                if ($alreadySubscribed == 1) {
                    $message .= '<br />' . translateFN('Uno studente risulta già iscritto');
                } elseif ($alreadySubscribed > 1) {
                    $message .= '<br />' . sprintf(translateFN('%d studenti risultano già iscritti'), $alreadySubscribed);
                }


                if ($notStudents == 1) {
                    $message .= '<br />' . translateFN('Un utente tra quelli indicati non è di tipo studente');
                } elseif ($notStudents > 1) {
                    $message .= '<br />' . sprintf(translateFN('%d utenti tra quelli indicati non sono di tipo studente'), $alreadySubscribed);
                }

                $data = new CText($message);

                //            header("Location: course_instance.php?id_course=$courseId&id_course_instance=$courseInstanceId");
                //            exit();
            } else {
                $fields = (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) ? 'nome,cognome,mail' : 'nome,cognome,username,password';
                $data = new CText('Il file non è ben formato sottometterlo di nuovo con: ' . $fields);
            }
        } else {
            $data = new CText('File non leggibile');
        }
    }
} else {
    if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
        $data = new CText(translateFN('Corso non trovato'));
    } elseif (!($courseInstanceObj instanceof CourseInstance) || !$courseInstanceObj->isFull()) {
        $data = new CText(translateFN('Classe non trovata'));
    } else {
        $data = new FileUploadForm();
        $formData = [
            'id_course' => $courseObj->getId(),
            'id_course_instance' => $courseInstanceObj->getId(),
        ];
        $data->fillWithArrayData($formData);
    }
}
$help = translateFN('Da qui il provider admin può iscrivere una lista di studenti alla classe selezionata.');
$help .= '<BR />';
$help .= translateFN('Il file deve avere estensione txt e deve contenere in ogni riga i seguenti dati: nome, cognome, email');

/*
 * OUTPUT
 */
$content_dataAr = [
    'path' => $path ?? '',
    'label' => $label ?? '',
    'status' => $status ?? '',
    'user_name' => $user_name ?? '',
    'user_type' => $user_type ?? '',
    'menu' => $menu ?? '',
    'help' => $help ?? '',
    'data' => $data->getHtml(),
    'messages' => $user_messages->getHtml(),
    'agenda ' => $user_agenda->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

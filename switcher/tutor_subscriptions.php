<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Forms\FileUploadForm;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Upload\FileUploader;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 *
 * @package     Switcher
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
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
    AMA_TYPE_SWITCHER => ['layout', 'user'],
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

$label = translateFN('Carica utenti da file');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $fileUploader = new FileUploader(ADA_UPLOAD_PATH . $userObj->getId() . '/');

    if ($fileUploader->upload() == false) {
        $data = new CText($fileUploader->getErrorMessage());
    } else {
        $FlagFileWellFormat = true;
        if (is_readable($fileUploader->getPathToUploadedFile())) {
            $usersToSubscribe = file($fileUploader->getPathToUploadedFile());

            /*remove blank line from array*/
            foreach ($usersToSubscribe as $key => $value) {
                if (!trim($value)) {
                    unset($usersToSubscribe[$key]);
                }
            }


            foreach ($usersToSubscribe as $subscriber) {
                // File well formed:  name, surname, email/username, password
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
            }
            if ($FlagFileWellFormat) {
                $subscribed = 0;
                $alreadySubscribed = 0;
                $notTutors = 0;
                $subscribers = count($usersToSubscribe);

                $admtypeAr = [AMA_TYPE_ADMIN];
                $admList = $common_dh->getUsersByType($admtypeAr);
                if (!AMADataHandler::isError($admList)) {
                    $adm_uname = $admList[0]['username'];
                } else {
                    $adm_uname = '';
                }

                foreach ($usersToSubscribe as $subscriber) {
                    $canSubscribeUser = false;
                    $userDataAr = array_map('trim', explode(',', $subscriber));

                    $subscriberObj = MultiPort::findUserByUsername(trim($userDataAr[2]));
                    if ($subscriberObj == null) { // user doesn't exist yet
                        $subscriberObj = new ADAPractitioner(
                            [
                                'nome' => trim($userDataAr[0]),
                                'cognome' => trim($userDataAr[1]),
                                'email' => trim($userDataAr[2]),
                                'tipo' => AMA_TYPE_TUTOR,
                                'username' => trim($userDataAr[2]), //  trim($userDataAr[1]). trim($userDataAr[2]) ???
                                'stato' => ADA_STATUS_PRESUBSCRIBED,
                                'birthcity' => '',
                            ]
                        );
                        $subscriberObj->setPassword(time());

                        /**
                         * @author giorgio 06/mag/2014 11:25:21
                         *
                         * If it's not a multiprovider environment,
                         * user must be subscribed to switcher's own
                         * provider only.
                         *
                         */
                        $provider_to_subscribeAr = [$sess_selected_tester];
                        $result = MultiPort::addUser($subscriberObj, $provider_to_subscribeAr);
                        if ($result > 0) { // addUser returns -1 on error!!!
                            $subscribed++;
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

                            $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));

                            $result = $mh->sendMessage($message_ha);
                            if (AMADataHandler::isError($result)) {
                                $help = translateFN('Errore');
                                $data = new CText('Invio mail non riuscita');
                            }
                        } else { // cannot insert user for some reason
                            $help = translateFN('Errore');
                            $data = new CText('Inserimento non riuscito');
                        }
                    } elseif ($subscriberObj instanceof ADAPractitioner) {  // user already exists and is a Tutor
                        $alreadySubscribed++;
                    } else { // user exists but with some other role
                        $notTutors++;
                    }
                }
                $message = sprintf(translateFN('Sono stati iscritti %d docenti su %d'), $subscribed, $subscribers);

                if ($alreadySubscribed == 1) {
                    $message .= '<br />' . translateFN('Un docente risulta già iscritto');
                } elseif ($alreadySubscribed > 1) {
                    $message .= '<br />' . sprintf(translateFN('%d docenti risultano già iscritti'), $alreadySubscribed);
                }

                if ($notTutors == 1) {
                    $message .= '<br />' . translateFN('Un utente tra quelli indicati non è di tipo docente');
                } elseif ($notTutors > 1) {
                    $message .= '<br />' . sprintf(translateFN('%d utenti tra quelli indicati non sono di tipo docente'), $alreadySubscribed);
                }
                $data = new CText($message);
            } else {
                $fields = (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) ? 'nome,cognome,mail' : 'nome,cognome,username';
                $help = translateFN('Errore');
                $data = new CText('Il file non è ben formato sottometterlo di nuovo con: ' . $fields);
            }
        } else {
            $help = translateFN('Errore');
            $data = new CText('File non leggibile');
        }
    }
} else {
    $data = new FileUploadForm();
    $formData = [
        'id_course' => '',
        'id_course_instance' => '',
    ];
    $data->fillWithArrayData($formData);

    $help = translateFN('Da qui il provider admin può iscrivere una lista di docenti.');
    $help .= '<BR />';
    $help .= translateFN('Il file deve essere di tipo CSV e deve contenere in ogni riga i seguenti dati:');
    $help .= (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) ? 'nome,cognome,email' : 'nome,cognome,username';
}

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

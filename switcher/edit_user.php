<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserProfileForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Secretquestion\AMASecretQuestionDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

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
$self = whoami();

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

/**
 * Check if the switcher is editing a student profile
 */
$isEditingAStudent = (DataValidator::isUinteger($_GET['usertype'] ?? null) === AMA_TYPE_STUDENT);

if (!$isEditingAStudent) {
    /**
     * Code to execute when the switcher is not editing a student
     */
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {

        /**
         * @author giorgio 29/mag/2013
         *
         * added parameters to force allowEditConfirm
         */
        $form = new UserProfileForm([], false, true);
        $form->fillWithPostData();
        $password = trim($_POST['password']);
        $passwordcheck = trim($_POST['passwordcheck']);
        if (DataValidator::validatePasswordModified($password, $passwordcheck) === false) {
            $message = translateFN('Le password digitate non corrispondono o contengono caratteri non validi.');
            header("Location: edit_user.php?message=$message&id_user=" . $_POST['id_utente']);
            exit();
        }
        if ($form->isValid()) {
            if (isset($_POST['layout']) && $_POST['layout'] != 'none') {
                $user_layout = $_POST['layout'];
            } else {
                $user_layout = '';
            }
            $userId = DataValidator::isUinteger($_POST['id_utente']);
            if ($userId > 0) {
                $editedUserObj = MultiPort::findUser($userId);
                $editedUserObj->fillWithArrayData($_POST);
                if ($password != '') {
                    $editedUserObj->setPassword($password);
                }
                if (defined('MODULES_SECRETQUESTION') && MODULES_SECRETQUESTION === true) {
                    if (
                        array_key_exists('secretquestion', $_POST) &&
                        array_key_exists('secretanswer', $_POST) &&
                        strlen($_POST['secretquestion']) > 0 && strlen($_POST['secretanswer']) > 0
                    ) {
                        /**
                         * Save secret question and answer and set the registration as successful
                         */
                        $sqdh = AMASecretQuestionDataHandler::instance();
                        $sqdh->saveUserQandA($editedUserObj->getId(), $_POST['secretquestion'], $_POST['secretanswer']);
                    }
                }
                $result = MultiPort::setUser($editedUserObj, [], true);
            }

            if (!AMADataHandler::isError($result)) {
                header('Location: view_user.php?id_user=' . $editedUserObj->getId());
                exit();
            }




            //        if($result > 0) {
            //          if($userObj instanceof ADAAuthor) {
            //              AdminUtils::performCreateAuthorAdditionalSteps($userObj->getId());
            //          }
            //
            //          $message = translateFN('Utente aggiunto con successo');
            //          header('Location: ' . $userObj->getHomePage($message));
            //          exit();
            //        } else {
            //            $form = new CText(translateFN('Si sono verificati dei problemi durante la creazione del nuovo utente'));
            //        }
        } else {
            $form = new CText(translateFN('I dati inseriti nel form non sono validi'));
        }
    } else {
        $userId = DataValidator::isUinteger($_GET['id_user']);
        if ($userId === false) {
            $data = new CText('Utente non trovato');
        } else {
            $editedUserObj = MultiPort::findUser($userId);
            $formData = $editedUserObj->toArray();
            $formData['email'] = $formData['e_mail'];
            $formData['uname'] = $formData['username'];
            unset($formData['e_mail']);
            /**
             * @author giorgio 29/mag/2013
             *
             * added parameters to force allowEditConfirm
             */
            $data = new UserProfileForm([], false, true);
            $data->fillWithArrayData($formData);
        }
    }

    $label = translateFN('Modifica utente');
    $help = translateFN('Da qui il provider admin può modificare il profilo di un utente esistente');
    if (!is_null($editedUserObj)) {
        $label .= ': ' . $editedUserObj->getUserName() . ' (' . $editedUserObj->getFullName() . ')';
    }

    $layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
    ];

    $optionsAr['onload_func'] = 'initDateField();';

    /*
     * Display error message  if the password is incorrect
     */
    if (isset($_GET['message'])) {
        $help = $_GET['message'];
    }

    $content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'status' => $status,
        'label' => $label,
        'help' => $help,
        'data' => $data->getHtml(),
        'module' => $module ?? '',
        'messages' => $user_messages->getHtml(),
    ];
} else {
    /**
     * If the switcher is editing a student, use browsing/edit_user.php
     */
    include realpath(__DIR__) . '/../browsing/edit_user.php';

    $label = translateFN('Modifica utente');
    $help = translateFN('Da qui il provider admin può modificare il profilo di un utente esistente');

    if (!is_null($editUserObj)) {
        $label .= ': ' . $editUserObj->getUserName() . ' (' . $editUserObj->getFullName() . ')';
    }

    $content_dataAr['label'] = $label;
    $content_dataAr['help'] = $help;
}

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

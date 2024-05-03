<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserProfileForm;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\TutorHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
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
$allowedUsersAr = [AMA_TYPE_TUTOR];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_TUTOR => ['layout'],
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
TutorHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
$languages = Translator::getLanguagesIdAndName();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = new UserProfileForm($languages, true);
    $form->fillWithPostData();
    $password = trim($_POST['password']);
    $passwordcheck = trim($_POST['passwordcheck']);
    if (DataValidator::validatePasswordModified($password, $passwordcheck) === false) {
        $message = translateFN('Le password digitate non corrispondono o contengono caratteri non validi.');
        header("Location: edit_tutor.php?message=$message");
        exit();
    }
    if ($form->isValid()) {
        $userObj->fillWithArrayData($_POST);
        if ($password != '') {
            $userObj->setPassword($password);
        }
        if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
            if (
                array_key_exists('secretquestion', $_POST) &&
                array_key_exists('secretanswer', $_POST) &&
                strlen($_POST['secretquestion']) > 0 && strlen($_POST['secretanswer']) > 0
            ) {
                /**
                 * Save secret question and answer and set the registration as successful
                 */
                $sqdh = AMASecretQuestionDataHandler::instance();
                $sqdh->saveUserQandA($userObj->getId(), $_POST['secretquestion'], $_POST['secretanswer']);
            }
        }
        MultiPort::setUser($userObj, [], true);

        /* unset $_SESSION['service_level'] to reload it with the correct  user language translation */
        unset($_SESSION['service_level']);

        $help = translateFN('Dati salvati');
        /*$navigationHistoryObj = $_SESSION['sess_navigation_history'];
        $location = $navigationHistoryObj->lastModule();
        header('Location: ' . $location.'?saveData');
        exit();*/
    }
} else {
    $form = new UserProfileForm($languages, true);
    $user_dataAr = $userObj->toArray();
    unset($user_dataAr['password']);
    $user_dataAr['email'] = $user_dataAr['e_mail'];
    $user_dataAr['uname'] = $user_dataAr['username'];
    unset($user_dataAr['e_mail']);
    $form->fillWithArrayData($user_dataAr);
    $help = translateFN('Modifica dati utente');
}

$label = translateFN('Modifica dati utente');


$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
        ROOT_DIR . '/js/include/jquery/pekeUpload/pekeUpload.js',
];

$layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        ROOT_DIR . '/js/include/jquery/pekeUpload/pekeUpload.css',
];

$maxFileSize = (int) (ADA_FILE_UPLOAD_MAX_FILESIZE / (1024 * 1024));

$optionsAr['onload_func'] = 'initDoc(' . $maxFileSize . ',' . $userObj->getId() . ');';

// $optionsAr['onload_func'] = 'initDateField();';

/*
 * Display error message  if the password is incorrect
 */
$message = DataValidator::checkInputValues('message','Message',INPUT_GET);
if ($message !== false) {
    $help = $message;
}


$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'path' => translateFN('Modifica dati utente'),
    'data' => $form->getHtml(),
    'help' => $help,
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

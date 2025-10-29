<?php

use Lynxlab\ADA\Admin\AdminUtils;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Forms\UserSubscriptionForm;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAAdmin;
use Lynxlab\ADA\Main\User\ADAAuthor;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Main\User\ADASwitcher;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Secretquestion\AMASecretQuestionDataHandler;

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

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = new UserSubscriptionForm();
    $form->fillWithPostData();

    if ($form->isValid()) {
        if (isset($_POST['layout']) && $_POST['layout'] != 'none') {
            $user_layout = $_POST['layout'];
        } else {
            $user_layout = '';
        }

        $user_dataAr = $_POST;
        $user_dataAr['layout'] = $user_layout;
        $user_dataAr['stato'] = 0;
        if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
            $user_dataAr['username'] = $user_dataAr['uname'];
        }

        switch ($_POST['tipo']) {
            case AMA_TYPE_STUDENT:
                $userObj = new ADAUser($user_dataAr);
                break;
            case AMA_TYPE_AUTHOR:
                $userObj = new ADAAuthor($user_dataAr);
                break;
            case AMA_TYPE_SUPERTUTOR:
            case AMA_TYPE_TUTOR:
                $userObj = new ADAPractitioner($user_dataAr);
                break;
            case AMA_TYPE_SWITCHER:
                $userObj = new ADASwitcher($user_dataAr);
                break;
            case AMA_TYPE_ADMIN:
                $userObj = new ADAAdmin($user_dataAr);
                break;
        }
        $userObj->setPassword($_POST['password'] ?? null);
        $result = MultiPort::addUser($userObj, [$sess_selected_tester]);
        if ($result > 0) {
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
            if ($userObj instanceof ADAAuthor) {
                AdminUtils::performCreateAuthorAdditionalSteps($userObj->getId());
            }

            $message = translateFN('Utente aggiunto con successo');
            header('Location: ' . $userObj->getHomePage($message));
            exit();
        } else {
            $form = new CText(translateFN('Si sono verificati dei problemi durante la creazione del nuovo utente'));
        }
    } else {
        $form = new CText(translateFN('I dati inseriti nel form non sono validi'));
    }
} else {
    $form = new UserSubscriptionForm();
}

$label = translateFN('Aggiunta utente');
$help = translateFN('Da qui il provider admin può creare un nuovo utente');

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_MASKEDINPUT,
    JQUERY_NO_CONFLICT,
    ROOT_DIR . '/js/browsing/registration.js',
];

$optionsAr['onload_func'] = 'initDateField();  initRegistration();';

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $form->getHtml(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

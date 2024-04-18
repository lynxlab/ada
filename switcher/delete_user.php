<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserRemovalForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;

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
$self = whoami();  // = admin!

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
$restore = isset($_REQUEST['restore']);
$prefix = $restore ? '' : 'dis';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = DataValidator::isUinteger($_POST['id_user']);
    $postKey = $restore ? 'restore' : 'delete';
    if ($userId !== false && isset($_POST[$postKey]) && intval($_POST[$postKey]) === 1) {
        $userToDeleteObj = MultiPort::findUser($userId);
        if ($userToDeleteObj instanceof ADALoggableUser) {
            $userToDeleteObj->setStatus($restore ? ADA_STATUS_REGISTERED : ADA_STATUS_PRESUBSCRIBED);
            MultiPort::setUser($userToDeleteObj, [], true);
            $data = new CText(sprintf(
                translateFN("L'utente \"%s\" è stato {$prefix}abilitato."),
                $userToDeleteObj->getFullName()
            ));
        } else {
            $data = new CText(translateFN('Utente non trovato') . '(3)');
        }
    } else {
        $data = new CText(translateFN("Utente non {$prefix}abilitato."));
    }
} else {
    $userId = DataValidator::isUinteger($_GET['id_user']);
    $restore = (isset($_GET['restore']) && intval($_GET['restore']) === 1);
    if ($userId === false) {
        $data = new CText(translateFN('Utente non trovato') . '(1)');
    } else {
        $userToDeleteObj = MultiPort::findUser($userId);
        if ($userToDeleteObj instanceof ADALoggableUser) {
            $formData = [
                'id_user' => $userId,
            ];
            $data = new UserRemovalForm($restore);
            $data->fillWithArrayData($formData);
        } else {
            $data = new CText(translateFN('Utente non trovato') . '(2)');
        }
    }
}

$label = ucfirst(strtolower(translateFN($prefix . 'abilitazione utente')));
$help = translateFN('Da qui il provider admin può ' . $prefix . 'abilitare un utente esistente');

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

ARE::render($layout_dataAr, $content_dataAr);

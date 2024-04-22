<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
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
$userId = false;
if ($_SESSION['sess_userObj']->getType() == AMA_TYPE_SWITCHER) {
    $userId = DataValidator::isUinteger($_GET['id_user']);
}

if ($userId === false && isset($_SESSION['sess_userObj']) && $_SESSION['sess_userObj'] instanceof ADALoggableUser) {
    $userId = $_SESSION['sess_userObj']->getId();
}

if ($userId === false) {
    $data = new CText('Utente non trovato');
} else {
    $user_info = $dh->getUserInfo($userId);
    if (AMADataHandler::isError($userId)) {
        $data = new CText('Utente non trovato');
    } else {
        $viewedUserObj = MultiPort::findUser($userId);
        $viewedUserObj->toArray();
        $user_dataAr = [
            'id' => $viewedUserObj->getId(),
            'tipo' => $viewedUserObj->getTypeAsString(),
            'nome e cognome' => $viewedUserObj->getFullName(),
            'data di nascita' => $viewedUserObj->getBirthDate(),
            'Comune o stato estero di nascita' => $viewedUserObj->getBirthCity(),
            'Provincia di nascita' => $viewedUserObj->getBirthProvince(),
            'genere' => $viewedUserObj->getGender(),
            'email' => $viewedUserObj->getEmail(),
            'telefono' => $viewedUserObj->getPhoneNumber(),
            'indirizzo' => $viewedUserObj->getAddress(),
            'citta' => $viewedUserObj->getCity(),
            'provincia' => $viewedUserObj->getProvince(),
            'nazione' => $viewedUserObj->getCountry(),
            'confermato' => ($viewedUserObj->getStatus() == ADA_STATUS_REGISTERED) ? translateFN("Si") : translateFN("No"),
        ];

        $data = BaseHtmlLib::labeledListElement('class:view_info', $user_dataAr);
    }
}

$label = translateFN('Profilo utente');
$help = translateFN('Da qui il provider admin può visualizzare il profilo di un utente esistente');

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $data->getHtml(),
    'edit_profile' => $userObj->getEditProfilePage(),
    'module' => $module ?? '',
    'messages' => $user_messages->getHtml(),
];
$options = null;
if (isset($_GET['pdfExport']) && intval($_GET['pdfExport']) === 1) {
    $options['outputfile'] = $viewedUserObj->getFullName() . '-' . date("d m Y");
    $options['forcedownload'] = true;
}

ARE::render($layout_dataAr, $content_dataAr, null, $options);

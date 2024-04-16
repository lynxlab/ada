<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_STUDENT => ['layout'],
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

$self =  whoami();

if (isset($_GET['id']) && DataValidator::isUinteger($_GET['id'])) {
    $tutorObj = MultiPort::findUser($_GET['id']);
    if ($tutorObj instanceof ADAPractitioner) {
        $dati = CDOMElement::create('div');
        $fullname = CDOMElement::create('div');
        $fullname->addChild(new CText(translateFN('User: ') . ' ' . $tutorObj->getFullName()));
        $username = CDOMElement::create('div');
        $username->addChild(new CText(translateFN('Username: ') . ' ' . $tutorObj->getUserName()));
        $tutorProfile = $tutorObj->getProfile();
        if ($tutorProfile == 'NULL') {
            $tutorProfile = '';
        }
        $profile = CDOMElement::create('div');
        $profile->addChild(new CText(translateFN('Profile: ') . ' ' . $tutorProfile));
        $dati->addChild($fullname);
        $dati->addChild($username);
        $dati->addChild($profile);
    } else {
        header('Location: ' . $userObj->getHomePage());
    }
} else {
    header('Location: ' . $userObj->getHomePage());
}

$help   = '';
$status = '';

$menu = '';

$label = translateFN("practitioner's profile");

$home_link = CDOMElement::create('a', 'href:user.php');
$home_link->addChild(new CText(translateFN("Home dell'Utente")));
$module = $home_link->getHtml() . ' > ' . $label;

$title = translateFN("ADA - practitioner's profile");

$content_dataAr = [
  'menu'      => $menu,
  'iscrivi'   => $dati->getHtml(),
  'help'      => $help,
  'status'    => $status,
  'label'     => $label,
  'course_title' => $module,
  'user_name' => $user_name,
  'user_type' => $user_type,
  'messages'  => $user_messages->getHtml(),
  'agenda'    => $user_agenda->getHtml(),
  'events'    => $user_events->getHtml(),
];

ARE::render($layout_dataAr, $content_dataAr);

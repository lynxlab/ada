<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];
/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_STUDENT  => ['layout'],
  AMA_TYPE_TUTOR    => ['layout'],
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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

$self =  Utilities::whoami();

if (
    !isset($_GET['instances']) && !isset($_GET['node']) &&
    trim($_GET['instances']) === '' && trim($_GET['node']) === ''
) {
    // if no instances list is passed, redirect the user to home page
    Utilities::redirect($_SESSION['sess_userObj']->getHomePage());
} else {
    /*
     * Display the select instance page.
    */
    $helpDIV  = CDOMElement::create('div');

    $help = translateFN('Il contenuto richiesto appartiene a più di una istanza');
    switch ($_SESSION['sess_userObj']->getType()) {
        case AMA_TYPE_STUDENT:
            $help .= ' ' . translateFN('a cui sei iscritto');
            break;
        case AMA_TYPE_TUTOR:
            $help .= ' ' . translateFN('di cui sei tutor');
            break;
    }
    $help .= '.';

    $helpSPAN = CDOMElement::create('span');
    $helpSPAN->setAttribute('class', 'help first');
    $helpSPAN->addChild(new CText($help));
    $helpDIV->addChild($helpSPAN);

    $helpSPAN = CDOMElement::create('span');
    $helpSPAN->setAttribute('class', 'help last');
    $helpSPAN->addChild(new CText(translateFN('Seleziona quella a cui vuoi andare da questo elenco') . ':'));
    $helpDIV->addChild($helpSPAN);

    $instances = explode(',', $_GET['instances']);

    $selectInstanceOL = CDOMElement::create('ol', 'class:select-instance');

    foreach ($instances as $instanceID) {
        $courseInstanceObj = new CourseInstance($instanceID);
        $selectLI = CDOMElement::create('li');
        $link = CDOMElement::create('a', 'href:view.php?id_node=' . trim($_GET['node']) .
                                                                '&id_course=' . $courseInstanceObj->id_corso .
                                                                '&id_course_instance=' . $courseInstanceObj->id);
        $link->addChild(new CText($courseInstanceObj->title));
        $selectLI->addChild($link);

        $selectInstanceOL->addChild($selectLI);
    }

    $data = $selectInstanceOL->getHtml();


    $layout_dataAr['JS_filename'] = [
            JQUERY,
            JQUERY_MASKEDINPUT,
            JQUERY_NO_CONFLICT,
    ];

    $title = translateFN("Scegli un'istanza");

    $content_dataAr = [
            'user_name'  => $user_name,
            'data'       => $data,
            'help'       => $helpDIV->getHtml(),
            'status'     => $status,
    ];

    /**
     * Sends data to the rendering engine
     */
    ARE::render($layout_dataAr, $content_dataAr);
}

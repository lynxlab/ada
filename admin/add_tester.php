<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
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
$allowedUsersAr = [AMA_TYPE_ADMIN];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_ADMIN => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  Utilities::whoami();  // = admin!

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
AdminHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /*
     * Handle data from $_POST:
     * 1. validate user submitted data
     * 2. if there are errors, display the add user form updated with error messages
     * 3. if there aren't errors, add this user to the common database and to
     *    the tester databases associated with this user.
     */


    /*
     * Validazione dati
     */
    $errorsAr = [];

    if (DataValidator::validateNotEmptyString($_POST['tester_name']) === false) {
        $errorsAr['tester_name'] = true;
    }

    if (DataValidator::validateString($_POST['tester_rs']) === false) {
        $errorsAr['tester_rs'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_address']) === false) {
        $errorsAr['tester_address'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_province']) === false) {
        $errorsAr['tester_province'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_city']) === false) {
        $errorsAr['tester_city'] = true;
    }

    if (DataValidator::validateNotEmptyString($_POST['tester_country']) === false) {
        $errorsAr['tester_country'] = true;
    }

    if (DataValidator::validatePhone($_POST['tester_phone']) === false) {
        $errorsAr['tester_phone'] = true;
    }

    if (DataValidator::validateEmail($_POST['tester_email']) === false) {
        $errorsAr['tester_email'] = true;
    }

    if (DataValidator::validateString($_POST['tester_desc']) === false) {
        $errorsAr['tester_desc'] = true;
    }

    if (DataValidator::validateString($_POST['tester_resp']) === false) {
        $errorsAr['tester_resp'] = true;
    }
    // validateTestername valida il puntatore, non il nome del tester.
    if (DataValidator::validateTestername($_POST['tester_pointer'], MULTIPROVIDER) === false) {
        $errorsAr['tester_pointer'] = true;
    }

    if (array_key_exists('tester_iban', $_POST) && strlen(trim($_POST['tester_iban'])) > 0 && DataValidator::validateIban(trim($_POST['tester_iban'])) === false) {
        $errorsAr['tester_iban'] = true;
    }

    if (array_key_exists('db_host', $_POST)) {
        if (DataValidator::validateNotEmptyString($_POST['db_host']) === false) {
            $errorsAr['db_host'] = true;
        } else {
            [$h, $p] = array_pad(explode(':', $_POST['db_host']), 2, '');
            if (strlen($h) > 0 && strlen($p) > 0 && intval($p) <= 0) {
                $errorsAr['db_host'] = true;
            }
        }
    } else {
        $h = $p = '';
    }

    if (DataValidator::validateNotEmptyString($_POST['db_name']) === false) {
        $errorsAr['db_name'] = true;
    }

    if (array_key_exists('db_user', $_POST)) {
        if (DataValidator::validateNotEmptyString($_POST['db_user']) === false) {
            $errorsAr['db_user'] = true;
        }
    } else {
        $_POST['db_user'] = null;
    }

    if (array_key_exists('db_password', $_POST)) {
        if (DataValidator::validateNotEmptyString($_POST['db_password']) === false) {
            $errorsAr['db_password'] = true;
        }
    } else {
        $_POST['db_password'] = null;
    }


    if (count($errorsAr) > 0) {
        $tester_dataAr = $_POST;
        $testersAr = [];
        $form = AdminModuleHtmlLib::getAddTesterForm($testersAr, $tester_dataAr, $errorsAr);
    } else {
        unset($_POST['submit']);
        $tester_dataAr = $_POST;

        $createProvider = AdminHelper::createProvider(array_map('trim', [
        'host' => $h . (strlen($p) > 0 ? ':' . $p : ''),
        'dbname' => $_POST['db_name'],
        'username' => $_POST['db_user'],
        'password' => $_POST['db_password'],
        'pointer' => $_POST['tester_pointer'],
        ]));
        if ($createProvider['status'] == false) {
            $errorBox = '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
            $errorBox .= '<div class="header">' . translateFN('Errore nel creare il provider') . '</div>';
            if (array_key_exists('message', $createProvider) && strlen($createProvider['message']) > 0) {
                $errorBox .= '<p>' . $createProvider['message'] . '</p>';
            }
            $errorBox .= '</div></div>';
            $tester_dataAr = $_POST;
            $testersAr = [];
            $form = AdminModuleHtmlLib::getAddTesterForm($testersAr, $tester_dataAr, $errorsAr);
        } else {
            $result = $common_dh->addTester($tester_dataAr);
            if (AMACommonDataHandler::isError($result)) {
                $errObj = new ADAError($result);
                $form = new CText('');
            } else {
                $adminUsersArr = $common_dh->getUsersByType([AMA_TYPE_ADMIN]);
                if (!AMADB::isError($adminUsersArr) && is_array($adminUsersArr) && count($adminUsersArr) > 0) {
                    foreach ($adminUsersArr as $adminUser) {
                        $adminUserObj = MultiPort::findUserByUsername($adminUser['username']);
                        if (!AMADB::isError($adminUserObj)) {
                            MultiPort::setUser($adminUserObj, [ $tester_dataAr['tester_pointer'] ]);
                        }
                    }
                }
                header('Location: ' . $userObj->getHomePage());
                exit();
            }
        }
    }
} else {
    /*
     * Display the add user form
     */
    $testersAr = [];
    $form = AdminModuleHtmlLib::getAddTesterForm($testersAr);
}
$label = translateFN("Aggiunta provider");
$help  = translateFN("Da qui l'amministratore puo' creare un nuovo provider");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));
$module = $home_link->getHtml() . ' > ' . $label;

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => ($errorBox ?? '') . $form->getHtml(),
  'module'       => $module,
];

ARE::render($layout_dataAr, $content_dataAr);

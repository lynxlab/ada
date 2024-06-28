<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\UserExtraModuleHtmlLib;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_STUDENT => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
        AMA_TYPE_SWITCHER => ['layout'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = Utilities::whoami();

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

/*
 * YOUR CODE HERE
*/
$languages = Translator::getLanguagesIdAndName();

$retArray = [];
$title = translateFN('Salvataggio');

/**
 * Set the $editUserObj depending on logged user type
 */
$editUserObj = null;

if (!isset($_POST['extraTableName'])) {
    $retArray = ["status" => "ERROR", "title" => $title, "msg" => translateFN("Non so cosa salvare")];
} else {
    /**
     * include and instantiate form class based on extraTableName POST
     * variable that MUST be set, else dont' know what and how to save.
     */
    $extraTableClass = trim($_POST['extraTableName']);
    $extraTableFqcn = ADAUser::getClassForLinkedTable($extraTableClass);
    $extraTableFormClass = ADAUser::getFormClassForLinkedTable($extraTableClass);

    if (null == $extraTableFormClass || null == $extraTableFqcn) {
        die(
            json_encode(
                ["status" => "ERROR", "title" => $title, "msg" => "Form or data class not found, don't know how to save"]
            )
        );
    }
}

switch ($userObj->getType()) {
    case AMA_TYPE_STUDENT:
    case AMA_TYPE_AUTHOR:
        $editUserObj = &$userObj;
        break;
    case AMA_TYPE_SWITCHER:
        $userId = DataValidator::isUinteger($_POST[$extraTableFqcn::getForeignKeyProperty()]);
        if ($userId !== false) {
            $editUserObj = MultiPort::findUser($userId);
        }
        break;
}

if (!is_null($editUserObj) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = new $extraTableFormClass($languages);
    $form->fillWithPostData();

    if ($form->isValid()) {
        $arr = [];
        $arr[$extraTableClass][0] = $extraTableFqcn::buildArrayFromPOST($_POST);
        // setExtras returns the index of the updated element, be it inserted or updated
        $updatedElementKey = $editUserObj->setExtras($arr);
        // setUser returns last insert id, or empty on update
        $result = MultiPort::setUser($editUserObj, [], true, $extraTableClass);

        if (!AMADB::isError($result)) {
            /**
             * need to set the added extra arr
             * state to saved and to give it the returned id
             */
            $extraTableProperty = 'tbl_' . $extraTableClass;
            // $lastInsertKey = count($editUserObj->$extraTableProperty)-1;

            /**
             * WEIRD STUFF:  NEED TO ACCESS OBJECT THIS WAY OTHERWISE WON'T WORK
             */
            $extraTableKeyProperty = $extraTableFqcn::getKeyProperty();
            $temp1 = $editUserObj->$extraTableProperty;
            //          $temp =  $temp1[$lastInsertKey];
            $temp =  $temp1[$updatedElementKey];
            $temp->$extraTableKeyProperty = $result;
            $temp->setSaveState(true);

            $myhtml = UserExtraModuleHtmlLib::extraObjectRow($temp);
            /**
             * Set the session user to the saved one if it's not
             * a switcher, that is not saving its own profile
             */
            if ($userObj->getType() != AMA_TYPE_SWITCHER) {
                $_SESSION['sess_userObj'] = $editUserObj;
            }

            $retArray =  ["status" => "OK", "title" => $title, "msg" => translateFN("Scheda salvata"),
                                "extraID" => $result,"html" => $myhtml  ];
        } else {
            $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("Errore nel salvataggio") ];
        }
    } else {
        $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("I dati non sono validi") ];
    }
} elseif (is_null($editUserObj)) {
    $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("Utente non trovato")];
} else {
    $retArray =  ["status" => "ERROR", "title" => $title, "msg" => translateFN("Errore nella trasmissione dei dati")];
}

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "title" => $title, "msg" => translateFN("Errore sconosciuto")];
}

echo json_encode($retArray);

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserExtraForm;
use Lynxlab\ADA\Main\Forms\UserProfileForm;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\UserExtraModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;
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
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout'],
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = Utilities::whoami();

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
$self = Utilities::whoami();
$languages = Translator::getLanguagesIdAndName();

/**
 * Set the $editUserObj depending on logged user type
 */
$editUserObj = null;
$self_instruction ??= null;

switch ($userObj->getType()) {
    case AMA_TYPE_STUDENT:
    case AMA_TYPE_AUTHOR:
    case AMA_TYPE_TUTOR:
        $editUserObj = clone $userObj;
        break;
    case AMA_TYPE_SWITCHER:
        $userId = DataValidator::isUinteger($_GET['id_user']);
        if ($userId !== false) {
            $editUserObj = MultiPort::findUser($userId);
        } else {
            $data = new CText(translateFN('Utente non trovato'));
        }
        break;
}

if (!is_null($editUserObj) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = new UserProfileForm($languages);
    $form->fillWithPostData();
    $password = trim($_POST['password']);
    $passwordcheck = trim($_POST['passwordcheck']);
    if (DataValidator::validatePasswordModified($password, $passwordcheck) === false) {
        $message = translateFN('Le password digitate non corrispondono o contengono caratteri non validi.');
        header("Location: edit_user.php?message=$message");
        exit();
    }
    if ($form->isValid()) {
        if (isset($_POST['layout']) && $_POST['layout'] != 'none') {
            $user_layout = $_POST['layout'];
        } else {
            $user_layout = '';
        }

        // set user datas
        $editUserObj->fillWithArrayData($_POST);

        if ($password != '') {
            $editUserObj->setPassword($password);
        }

        // set user extra datas if any
        if ($editUserObj->hasExtra()) {
            $editUserObj->setExtras($_POST);
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
                $sqdh->saveUserQandA($editUserObj->getId(), $_POST['secretquestion'], $_POST['secretanswer']);
            }
        }

        MultiPort::setUser($editUserObj, [], true, ADAUser::getExtraTableName());
        /**
         * Set the session user to the saved one if it's not
         * a switcher, that is not saving its own profile
         */
        if ($_SESSION['sess_userObj']->getType() != AMA_TYPE_SWITCHER) {
            $_SESSION['sess_userObj'] = $editUserObj;
        }
        /* unset $_SESSION['service_level'] to reload it with the correct  user language translation */
        unset($_SESSION['service_level']);

        $navigationHistoryObj = $_SESSION['sess_navigation_history'];
        $location = $navigationHistoryObj->lastModule();
        header('Location: ' . $location);
        exit();
    }
} elseif (!is_null($editUserObj)) {
    $allowEditProfile = false;
    /**
     * If the user is a switcher, can edit confirmation state of student
     */
    $allowEditConfirm = ($userObj->getType() == AMA_TYPE_SWITCHER);
    $user_dataAr = $editUserObj->toArray();
    if ($userObj->getType() == AMA_TYPE_AUTHOR || $userObj->getType() == AMA_TYPE_TUTOR) {
        header('Location: ' . $userObj->getEditProfilePage());
        exit();
    }
    // the standard UserProfileForm is always needed.
    // Let's create it
    if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
        $self = Utilities::whoami(); //allowing to build action form
    }
    $form = new UserProfileForm($languages, $allowEditProfile, $allowEditConfirm, $self . '.php');
    unset($user_dataAr['password']);
    $user_dataAr['email'] = $user_dataAr['e_mail'];
    unset($user_dataAr['e_mail']);
    if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
        $user_dataAr['uname'] = $editUserObj->username;
        $sqdh = AMASecretQuestionDataHandler::instance();
        $user_dataAr['secretquestion'] = htmlentities($sqdh->getUserQuestion($editUserObj->getId()));
    }
    $form->fillWithArrayData($user_dataAr);

    if (!$editUserObj->hasExtra()) {
        // user has no extra, let's display it
        $data = $form->render();
    } else {
        $extraForm = new UserExtraForm();
        $extraForm->fillWithArrayData($user_dataAr);

        $tabContents =  [];

        /**
         * @author giorgio 22/nov/2013
         * Uncomment and edit the below array to have the needed
         * jQuery tabs to be used for user extra data and tables
         */

        //      $tabsArray = array (
        //              array (translateFN ("Anagrafica"), $form),
        //              array (translateFN ("Sample Extra 1:1"), $extraForm),
        //              array (translateFN ("Sample Extra 1:n"), 'oneToManyDataSample'),
        //      );

        /**
         * If you want the $extraForm fields embedded in the $form tab instead
         * of being on its own tab, use the following:
         */
        //      $tabsArray = array (
        //              array (translateFN ("Anagrafica"), $form, 'withExtra'=>true),
        //              array (translateFN ("Sample Extra 1:n"), 'oneToManyDataSample'),
        //      );

        $data = "";
        $linkedTablesInADAUser = !is_null(ADAUser::getLinkedTables()) ? ADAUser::getLinkedTables() : [];
        for ($currTab = 0; $currTab < count($tabsArray); $currTab++) {
            // if is a subclass of FForm the it's a multirow element
            $doMultiRowTab = !is_subclass_of($tabsArray[$currTab][1], 'FForm');

            if ($doMultiRowTab === true) {
                $extraTableName = $tabsArray[$currTab][1];
                $extraTableFormClass = "User" . ucfirst($extraTableName) . "Form";

                /*
                 * if extraTableName is not in the linked tables array or there's
                 * no form classes for the extraTableName skip to the next iteration
                 *
                 * NOTE: there's no need to check for classes (data classes, not for ones)
                 * existance here because if they did not existed you'd get an error while loggin in.
                 */
                if (
                    !in_array($extraTableName, $linkedTablesInADAUser)
                ) {
                    continue;
                }

                // if the file is included, but still there's no form class defined
                // skip to the next iteration
                if (!class_exists($extraTableFormClass)) {
                    continue;
                }

                // generate the form
                $form = new $extraTableFormClass();
                $form->fillWithArrayData([
                        $extraTableName::getForeignKeyProperty() => $editUserObj->getId(),
                ]);

                // create a div for placing 'new' and 'discard changes button'
                $divButton = CDOMElement::create('div', 'class:formButtons');

                $showButton = CDOMElement::create('a');
                $showButton->setAttribute('href', 'javascript:toggleForm(\'' . $form->getName() . '\', true);');
                $showButton->setAttribute('class', 'showFormButton ' . $form->getName());
                $showButton->addChild(new CText(translateFN('Nuova scheda')));

                $hideButton = CDOMElement::create('a');
                $hideButton->setAttribute('href', 'javascript:toggleForm(\'' . $form->getName() . '\');');
                $hideButton->setAttribute('class', 'hideFormButton ' . $form->getName());
                $hideButton->setAttribute('style', 'display:none');
                $hideButton->addChild(new CText(translateFN('Chiudi e scarta modifiche')));

                $divButton->addChild($showButton);
                $divButton->addChild($hideButton);

                $objProperty = 'tbl_' . $extraTableName;
                // create a div to wrap up all the rows of the array tbl_educationTrainig
                $container = CDOMElement::create('div', 'class:extraRowsContainer,id:container_' . $extraTableName);

                // if have 3 or more rows, add the new and discard buttons on top also
                if (count($editUserObj->$objProperty) >= 3) {
                    $divButton->setAttribute('class', $divButton->getAttribute('class') . ' top');
                    $container->addChild(new CText($divButton->getHtml()));
                    // reset the button class by removing top
                    $divButton->setAttribute('class', str_ireplace(' top', '', $divButton->getAttribute('class')));
                }

                if (count($editUserObj->$objProperty) > 0) {
                    foreach ($editUserObj->$objProperty as $num => $aElement) {
                        $keyFieldName = $aElement::getKeyProperty();
                        $keyFieldVal = $aElement->$keyFieldName;
                        $container->addChild(new CText(UserExtraModuleHtmlLib::extraObjectRow($aElement)));
                    }
                }
                // in these cases the form is added here
                $container->addChild(CDOMElement::create('div', 'class:clearfix'));
                $container->addChild(new CText($form->render()));
                // unset the form that's going to be userd in next iteration
                unset($form);
                $container->addChild(CDOMElement::create('div', 'class:clearfix'));
                // add the new and discard buttons after the container
                $divButton->setAttribute('class', $divButton->getAttribute('class') . ' bottom');
                $container->addChild(new CText($divButton->getHtml()));
            } else {
                /**
                 * place the form in the tab
                 */
                $currentForm = $tabsArray[$currTab][1];
            }

            // if a tabs container is needed, create one
            if (!isset($tabsContainer)) {
                $tabsContainer = CDOMElement::create('div', 'id:tabs');
                $tabsUL = CDOMElement::create('ul');
                $tabsContainer->addChild($tabsUL);
            }

            // add a tab only if there's something to fill it with
            if (isset($container) || isset($currentForm)) {
                // create a LI
                $tabsLI = CDOMElement::create('li');
                // add the save icon to the link
                $tabsLI->addChild(CDOMElement::create('span', 'class:ui-icon ui-icon-disk,id:tabSaveIcon' . $currTab));
                // add a link to the div that holds tab content
                $tabsLI->addChild(BaseHtmlLib::link('#divTab' . $currTab, $tabsArray [$currTab][0]));
                $tabsUL->addChild($tabsLI);
                $tabContents [$currTab] = CDOMElement::create('div', 'id:divTab' . $currTab);

                if (isset($container)) {
                    // add the container to the current tab
                    $tabContents [$currTab]->addChild($container);
                } elseif (isset($currentForm)) {
                    // if form of current tab wants the UserExtraForm fields embedded, obey it
                    if (isset($tabsArray[$currTab]['withExtra']) && $tabsArray[$currTab]['withExtra'] === true) {
                        UserExtraForm::addExtraControls($currentForm);
                        $currentForm->fillWithArrayData($user_dataAr);
                    }
                    $tabContents [$currTab]->addChild(new CText($currentForm->render()));
                    unset($currentForm);
                }
                $tabsContainer->addChild($tabContents [$currTab]);
            }
        } // end cycle through all tabs

        if (isset($tabsContainer)) {
            $data .= $tabsContainer->getHtml();
        } elseif (isset($form)) {
            if (isset($extraForm)) {
                // if there are extra controls and NO tabs
                // add the extra controls to the standard form
                UserExtraForm::addExtraControls($form, true);
                $form->fillWithArrayData($user_dataAr);
            }
            $data .= $form->render();
        } else {
            $data = 'No form to display :(';
        }
    }
}

$label = translateFN('Modifica dati utente');
$help =  $label; // or set it to whatever you like

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
//if a course instance is self_instruction
$self_instruction = DataValidator::checkInputValues('self_instruction', 'Value', INPUT_GET, null);
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $self = 'editUserSelfInstruction';
} else {
    $self = Utilities::whoami();
}
$maxFileSize = (int) (ADA_FILE_UPLOAD_MAX_FILESIZE / (1024 * 1024));
if ($userObj->tipo == AMA_TYPE_STUDENT && ($self_instruction)) {
    $layout_dataAr['CSS_filename'][] =  ROOT_DIR . '/layout/' . $template_family . '/css/browsing/edit_user.css';
    $layout_dataAr['JS_filename'][] =  ROOT_DIR . '/js/browsing/edit_user.js';
}
$navigation_history = $_SESSION['sess_navigation_history'];
$last_visited_node  = $navigation_history->lastModule();

/**
 * do the form have to be submitted with an AJAX call?
 * defalut answer is true, call this method to set it to false.
 *
 * $editUserObj->useAjax(false);
 */
if (!is_null($editUserObj)) {
    $optionsAr['onload_func']  = 'initDoc(' . $maxFileSize . ',' . $editUserObj->getId() . ');';
    $optionsAr['onload_func'] .= 'initUserRegistrationForm(' . (int)(isset($tabsContainer)) . ', ' . (int)$editUserObj->saveUsingAjax() . ');';
} else {
    $optionsAr = null;
}

//$optionsAr['onload_func'] = 'initDateField();';

/*
 * Display error message  if the password is incorrect
 */
$message = DataValidator::checkInputValues('message', 'Message', INPUT_GET);
if ($message !== false) {
    $help = $message;
}

if (isset($_SESSION['sess_id_course_instance'])) {
    $last_access = $userObj->getLastAccessFN(($_SESSION['sess_id_course_instance']), "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
} else {
    $last_access = $userObj->getLastAccessFN(null, "UT", null);
    $last_access = AMADataHandler::tsToDate($last_access);
    $user_level = translateFN('Nd');
}
if ($last_access == '' || is_null($last_access)) {
    $last_access = '-';
}

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'course_title' => translateFN('Modifica profilo'),
    'data' => $data,
    'last_visit' => $last_access,
    'help' => $help,
    'user_level' => $user_level,
    ];

/**
 * If it's a switcher the renderer is called by switcher/edit_user.php
 */
if ($userObj->getType() != AMA_TYPE_SWITCHER) {
    $menuOptions['self_instruction'] = $self_instruction;
    ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr, $menuOptions);
}

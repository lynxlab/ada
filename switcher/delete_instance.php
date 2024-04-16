<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Forms\CourseInstanceRemovalForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\History\History;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAPractitioner;
use Lynxlab\ADA\Switcher\Subscription;

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
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course', 'course_instance'],
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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
SwitcherHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        $courseInstanceObj instanceof CourseInstance && $courseInstanceObj->isFull()
        && $courseObj instanceof Course && $courseObj->isFull()
    ) {
        $form = new CourseInstanceRemovalForm();
        if ($form->isValid()) {
            if ($_POST['delete'] == 1) {
                $courseInstanceId = $courseInstanceObj->getId();
                if (Subscription::deleteAllSubscriptionsToClassRoom($courseInstanceId)) {
                    $result = $dh->courseInstanceTutorsUnsubscribe($courseInstanceId);
                    if ($result === true) {
                        $result = $dh->courseInstanceRemove($courseInstanceId);
                        if (!AMADataHandler::isError($result)) {
                            // fare unset di sess_courseInstanceObj se c'è
                            header('Location: list_instances.php?id_course=' . $courseObj->getId());
                            exit();
                        } else {
                            $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione della classe.') . '(1)');
                        }
                    } else {
                        $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione della classe') . '(2)');
                    }
                } else {
                    $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione della classe') . '(3)');
                }
            } else {
                $data = new CText(translateFN('La cancellazione della classe è stata annullata'));
            }
        } else {
            $data = new CText(translateFN('I dati inseriti nel form non sono validi'));
        }
    } else {
        $data = new CText(translateFN('Classe non trovata'));
    }
} else {
    if (!($courseObj instanceof Course) || !$courseObj->isFull()) {
        $data = new CText(translateFN('Corso non trovato'));
    } elseif (!($courseInstanceObj instanceof CourseInstance) || !$courseInstanceObj->isFull()) {
        $data = new CText(translateFN('Classe non trovata'));
    } else {
        $formData = [
            'id_course' => $courseObj->getId(),
            'id_course_instance' => $courseInstanceObj->getId(),
        ];
        $data = new CourseInstanceRemovalForm();
        $data->fillWithArrayData($formData);
    }
}

$label = translateFN('Cancellazione di una istanza corso');
$help = translateFN('Da qui il provider admin può cancellare una istanza corso esistente');

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

<?php

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Forms\CourseRemovalForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;

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
    AMA_TYPE_SWITCHER => ['layout', 'course'],
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
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($courseObj instanceof Course && $courseObj->isFull()) {
        $form = new CourseRemovalForm($courseObj);
        if ($form->isValid()) {
            if ($_POST['deleteCourse'] == 1) {
                $courseId = $courseObj->getId();
                $serviceInfo = $common_dh->getServiceInfoFromCourse($courseId);
                if (!AMACommonDataHandler::isError($serviceInfo)) {
                    $serviceId = $serviceInfo[0];
                    $result = $common_dh->deleteService($serviceId);
                    if (!AMACommonDataHandler::isError($result)) {
                        $result = $common_dh->unlinkServiceFromCourse($serviceId, $courseId);
                        if (!AMADataHandler::isError($result)) {
                            $result = $dh->removeCourse($courseId);
                            if (AMADataHandler::isError($result)) {
                                $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione del corso.') . '(1)');
                            } else {
                                if (defined('MODULES_TEST') && MODULES_TEST) {
                                    $test_db = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                                    if (AMADB::isError($test_db->testRemoveCourseNodes($courseId))) {
                                        // handle error here if needed
                                    }
                                }
                                unset($_SESSION['sess_courseObj']);
                                unset($_SESSION['sess_id_course']);
                                header('Location: list_courses.php');
                                exit();
                            }
                        } else {
                            $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione del corso.') . '(2)');
                        }
                    } else {
                        $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione del corso.') . '(3)');
                    }
                } else {
                    $data = new CText(translateFN('Si sono verificati degli errori durante la cancellazione del corso.') . '(4)');
                }
            } else {
                $data = new CText(sprintf(translateFN('La cancellazione del corso "%s" è stata annullata.'), $courseObj->getTitle()));
            }
        } else {
            $data = new CText(translateFN('I dati inseriti nel form non sono validi'));
        }
    } else {
        $data = new CText(translateFN('Corso non trovato'));
    }
} else {
    if ($courseObj instanceof Course && $courseObj->isFull()) {
        $result = $dh->courseHasInstances($courseObj->getId());
        if (AMADataHandler::isError($result)) {
            $data = new CText(translateFN('Si è verificato un errore nella lettura dei dati del corso'));
        } elseif ($result == true) {
            $data = new CText(
                sprintf(
                    translateFN('Il corso "%s" ha delle classi associate, non è possibile rimuoverlo direttamente.'),
                    $courseObj->getTitle()
                )
            );
        } else {
            $data = new CourseRemovalForm($courseObj);
        }
    } else {
        $data = new CText(translateFN('Corso non trovato'));
    }
}


$label = translateFN('Cancellazione di un corso');
$help = translateFN('Da qui il provider admin può cancellare un corso esistente');

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

<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;
use Lynxlab\ADA\Module\Servicecomplete\CompleteRulesManagement;
use Lynxlab\ADA\Module\Servicecomplete\Operation;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Servicecomplete\Functions\fixPOSTArray;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$self = Utilities::whoami();

$GLOBALS['dh'] = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
$data = '';
$containerDIV = CDOMElement::create('div', 'id:moduleContent');

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST)) {
        // there are post datas, save them
        $conditionPOST = $_POST['condition'];
        $conditionParamsPOST = $_POST['param'];

        $savedOK = false;

        fixPOSTArray($conditionPOST, $conditionParamsPOST);

        if (!empty($conditionPOST)) {
            $conditionSetId = (isset($_POST['conditionSetId']) && intval($_POST['conditionSetId']) > 0) ? intval($_POST['conditionSetId']) : null;
            $description = (isset($_POST['description']) && trim($_POST['description']) !== '') ? trim($_POST['description']) : '';

            if ($description !== '') {
                $saveCondition = new CompleteConditionSet($conditionSetId, $description);
                $saveCondition->setOperation(Operation::buildOperationTreeFromPOST($conditionPOST));

                $savedOK = $GLOBALS['dh']->saveCompleteConditionSet($saveCondition);

                if ($savedOK) {
                    $msg = translateFN('set di condizioni salvato');
                } else {
                    $msg = translateFN('problemi con il salvataggio');
                }
            } else {
                $msg = translateFN('la descrizione non pu&ograve; essere vuota');
            }
        } else {
            $msg = translateFN('niente da salvare');
        }

        /// if it's an ajax request, output html and die
        if (isset($_POST['requestType']) && trim($_POST['requestType']) === 'ajax') {
            echo json_encode([ 'OK' => $savedOK, 'msg' => $msg, 'title' => translateFN('Regola di completamento') ]);
            die();
        } else {
            // this is used if not saving using AJAX
            $containedElement = CDOMElement::create('div', 'class:saveResults nonAjax');

            $spanmsg = CDOMElement::create('span', 'class:saveResultstext');
            $spanmsg->addChild(new CText($msg));

            $button = CDOMElement::create('button', 'id:saveResultsbutton');
            $button->addChild(new CText(translateFN('OK')));

            if ($savedOK) {
                $href = 'self.document.location.href=\'' . MODULES_SERVICECOMPLETE_HTTP . '\'';
            } else {
                $href = 'history.go(-1);';
            }

            $button->setAttribute('onclick', 'javascript:' . $href);

            $containedElement->addChild($spanmsg);
            $containedElement->addChild($button);

            $data = $containedElement->getHtml();
        }
    } else {
        // build the form, possibly passing data to be edited
        $formData = null;

        $conditionSetId = (isset($_GET['conditionSetId']) && intval($_GET['conditionSetId']) > 0) ? intval($_GET['conditionSetId']) : 0;

        if ($conditionSetId > 0) {
            $conditionSetToEdit = $GLOBALS['dh']->getCompleteConditionSet($conditionSetId);
            if (!is_null($conditionSetToEdit)) {
                $formData['description'] = $conditionSetToEdit->description;
                $formData['conditionSetId'] = $conditionSetToEdit->getID();
                $formData['condition'] = $conditionSetToEdit->getOperandsForPriority();
            }
            $helpmsg = translateFN('Modifica della regola');
        } else {
            $helpmsg = translateFN('Crea una nuova regola');
        }

        $management = new CompleteRulesManagement();
        $form_return = $management->form($formData);

        $data = $form_return['html'];
    }
} catch (Exception $e) {
    $data = $e->getMessage();
}

$containerDIV->addChild(new CText($data));
$data = $containerDIV->getHtml();
/**
 * include proper jquery ui css file depending on wheter there's one
 * in the template_family css path or the default one
 */
if (!is_dir(MODULES_SERVICECOMPLETE_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui')) {
    $layout_dataAr['CSS_filename'] = [
            JQUERY_UI_CSS,
    ];
} else {
    $layout_dataAr['CSS_filename'] = [
            MODULES_SERVICECOMPLETE_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui/jquery-ui-1.10.3.custom.min.css',
    ];
}

array_push($layout_dataAr['CSS_filename'], MODULES_SERVICECOMPLETE_PATH . '/layout/tooltips.css');

$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'title' => translateFN('complete module'),
        'help' => $helpmsg,
        'data' => $data,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);

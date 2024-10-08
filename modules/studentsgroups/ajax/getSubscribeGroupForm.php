<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\StudentsGroups\AMAStudentsGroupsDataHandler;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsActions;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsException;
use Lynxlab\ADA\Module\StudentsGroups\SubscribeGroupForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(StudentsGroupsActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMAStudentsGroupsDataHandler $GLOBALS['dh']
 */
$GLOBALS['dh'] = AMAStudentsGroupsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed group data
     */
    // try to save it
    $res = $GLOBALS['dh']->saveSubscribeGroup($_POST);

    if (AMADB::isError($res) || $res instanceof StudentsGroupsException) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = translateFN('Gruppo iscritto alla classe');
        if (is_array($res)) {
            $retArray['msg'] .= '<br/>' . sprintf(
                "%d studenti totali: %d nuove iscrizioni, %d già iscritti",
                $res['alreadySubscribed'] + $res['subscribed'],
                $res['subscribed'],
                $res['alreadySubscribed']
            );
        }
    }
} elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $res = $GLOBALS['dh']->findAll('Groups', ['label' => 'ASC']);
    if (!AMADB::isError($res)) {
        if (is_array($res) && count($res) > 0) {
            // display the form with loaded data
            $form = new SubscribeGroupForm('subscribegroups', null, $res);
            $retArray['status'] = "OK";
            $retArray['html'] = $form->withSubmit()->toSemanticUI()->getHtml();
            $retArray['dialogTitle'] = translateFN('Iscrivi Gruppo');
        } else {
            $retArray['status'] = "ERROR";
            $retArray['msg'] = AMADB::isError($res) ? $res->getMessage() : translateFN('Nessun gruppo trovato');
        }
    } else {
        // if it's an error display the error message without the form
        $retArray['status'] = "ERROR";
        $retArray['msg'] = AMADB::isError($res) ? $res->getMessage() : translateFN('Errore caricamento gruppi');
    }
}

header('Content-Type: application/json');
echo json_encode($retArray);

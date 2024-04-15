<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\AMA\AMADB;

use function \translateFN;

/**
 * @package     badges module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Ramsey\Uuid\Uuid;
use Lynxlab\ADA\Module\Badges\BadgesActions;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Badges\BadgeForm;
use Lynxlab\ADA\Module\Badges\Badge;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(BadgesActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMABadgesDataHandler $GLOBALS['dh']
 */
$GLOBALS['dh'] = AMABadgesDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed badge data
     */
    // try to save it
    $res = $GLOBALS['dh']->saveBadge($_POST);

    if (AMADB::isError($res)) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
    } else {
        // redirect to classrooms page
        $retArray['status'] = "OK";
        $retArray['msg'] = translateFN('Badge salvato');
    }
} elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['uuid']) && Uuid::isValid($_GET['uuid'])) {
        // try to load
        $res = $GLOBALS['dh']->findBy('Badge', [ 'uuid' => trim($_GET['uuid']) ]);
        if (!AMADB::isError($res) && is_array($res) && count($res) === 1) {
            $badge = reset($res);
            // display the form with loaded data
            $form = new BadgeForm($badge, 'editbadge', null);
            $retArray['status'] = "OK";
            $retArray['html'] = $form->withSubmit()->toSemanticUI()->getHtml();
            $retArray['dialogTitle'] = translateFN('Modifica Badge');
        } else {
            // if it's an error display the error message without the form
            $retArray['status'] = "ERROR";
            $retArray['msg'] = AMADB::isError($res) ? $res->getMessage() : translateFN('Errore caricamento badge');
        }
    } else {
        /**
         * display the empty form
         */
        $form = new BadgeForm(new Badge(), 'editbadge', null);
        $retArray['status'] = "OK";
        $retArray['html'] = $form->withSubmit()->toSemanticUI()->getHtml();
        $retArray['dialogTitle'] = translateFN('Nuovo Badge');
    }
}

header('Content-Type: application/json');
echo json_encode($retArray);

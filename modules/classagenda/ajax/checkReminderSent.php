<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['reminderEventID']) && intval($_GET['reminderEventID']) > 0) {
        $result = $GLOBALS['dh']->getReminderForEvent(intval($_GET['reminderEventID']));

        if (!AMADB::isError($result) && $result !== false) {
            $reminderContentDIVId = 'reminderContent';

            $reminderDIV = CDOMElement::create('div', 'class:reminderDetailsContainer');
            $reminderDIV->setAttribute('style', 'display:none;');

            $reminderSPAN = CDOMElement::create('span', 'class:reminderDetails');
            $reminderSPAN->addChild(
                new CText(
                    translateFN(MODULES_CLASSAGENDA_EMAIL_REMINDER ? 'Promemoria inviato il' : 'Promemoria salvato il') .
                        ' ' . $result['date'] . ' ' . translateFN('alle') . ' ' . $result['time']
                )
            );

            $reminderButton = CDOMElement::create('button');
            if (MODULES_CLASSAGENDA_EMAIL_REMINDER) {
                $reminderButton->addChild(new CText(translateFN('Vedi Promemoria')));
                $reminderButton->setAttribute('data-email-reminder', 'true');
                $reminderButton->setAttribute('onclick', 'javascript:openReminder(\'#' . $reminderContentDIVId . '\');');
            } else {
                $reminderButton->addChild(new CText(translateFN('Modifica')));
                $reminderButton->setAttribute('data-email-reminder', 'false');
                $reminderButton->setAttribute('onclick', 'javascript:reminderSelectedEvent($j(this))');
            }

            $reminderDIV->addChild($reminderSPAN);
            $reminderDIV->addChild($reminderButton);

            $reminderContent = CDOMElement::create('div', 'id:' . $reminderContentDIVId);
            $reminderContent->setAttribute('style', 'display:none');
            $reminderContent->addChild(new CText($result['html']));

            $retArray = ["status" => "OK", "html" => $reminderDIV->getHtml(), "content" => $reminderContent->getHtml()];
        } else {
            $retArray = ["status" => "OK"];
        }
    } else {
        $retArray = ["status" => "ERROR", "msg" => translateFN("Selezionare un evento")];
    } // if isset eventID
} // if method is GET

if (empty($retArray)) {
    $retArray = ["status" => "ERROR", "msg" => translateFN("Errore sconosciuto")];
}
header('Content-Type: application/json');
echo json_encode($retArray);

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

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler;
use Lynxlab\ADA\Module\Classagenda\CalendarsManagement;

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

// MODULE's OWN IMPORTS

$GLOBALS['dh'] = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reminderID']) && intval($_POST['reminderID']) > 0) {
        $reminderData = $GLOBALS['dh']->getReminderDataToEmail(intval($_POST['reminderID']));

        if (!AMADB::isError($reminderData)) {
            $recipients = $GLOBALS['dh']->getStudentsForCourseInstance($reminderData['id_istanza_corso']);

            if (!AMADB::isError($recipients) && is_array($recipients) && count($recipients) > 0) {
                $senderEmail = $_SESSION['sess_userObj']->getEmail();
                $senderFullName = $_SESSION['sess_userObj']->getFullName();

                ignore_user_abort(true);
                session_write_close();

                $sleepTime = intval(3600 / MODULES_CLASSAGENDA_EMAILS_PER_HOUR * 1000000); // sleep time in microseconds

                $logFile = MODULES_CLASSAGENDA_LOGDIR . 'log-' . $reminderID . '-' . date('d-m-Y_His');
                if (!is_dir(MODULES_CLASSAGENDA_LOGDIR)) {
                    mkdir(MODULES_CLASSAGENDA_LOGDIR, 0o777, true);
                }
                if (!is_file($logFile)) {
                    touch($logFile);
                }

                /**
                 * perform general substitutions
                 */
                $searchArray = [];
                $replaceArray = [];
                // fields that must be replaced per user!
                $userDataFields = ['name', 'lastname', 'e-mail'];
                foreach (CalendarsManagement::reminderPlaceholders() as $placeHolder => $label) {
                    if (!in_array($placeHolder, $userDataFields)) {
                        $searchArray[] = '{' . $placeHolder . '}';
                        if (isset($reminderData[$placeHolder]) && strlen($reminderData[$placeHolder]) > 0) {
                            $replaceArray[] = $reminderData[$placeHolder];
                        } else {
                            $replaceArray[] = '';
                        }
                    }
                }
                $HTMLModelText = str_replace($searchArray, $replaceArray, $reminderData['html']);
                // perform general substitutions for relative path images
                $HTMLModelText = preg_replace('/(src=[\'"])\/?[^>]*(\/?services\/media\/)/', '$1' . HTTP_ROOT_DIR . '/$2', $HTMLModelText);

                // email class init and common values
                $phpmailer = new ADAPHPMailer();
                $phpmailer->CharSet = ADA_CHARSET;
                $phpmailer->configSend();

                $phpmailer->SetFrom($senderEmail, $senderFullName);
                $phpmailer->AddReplyTo($senderEmail, $senderFullName);
                $phpmailer->IsHTML(true);
                $phpmailer->Subject = translateFN('Promemoria per il corso') . ' ' . $reminderData['coursename'] .
                    ' - ' . translateFN('classe') . ': ' . $reminderData['instancename'];

                foreach ($recipients as $num => $recipient) {
                    set_time_limit(0);
                    ADAFileLogger::log('sending out#' . $num . ' userID=' . $recipient['id_utente'] . ' e-mail=' . $recipient['e_mail'], $logFile);

                    // performs user substitutions
                    $HTMLText = str_replace(
                        ['{name}', '{lastname}', '{e-mail}'],
                        [$recipient['nome'], $recipient['cognome'], $recipient['e_mail']],
                        $HTMLModelText
                    );

                    $userFullName = ucwords(strtolower($recipient['nome'] . ' ' . $recipient['cognome']));

                    $newlineTags = ['<br>', '<br/>', '<br />', '</div>', '</p>', '</span>'];
                    $PLAINText = strip_tags(str_replace($newlineTags, PHP_EOL, $HTMLText));

                    $phpmailer->AddAddress($recipient['e_mail'], $userFullName);
                    $phpmailer->Body = $HTMLText;
                    $phpmailer->AltBody = $PLAINText;
                    $phpmailer->Send();
                    $phpmailer->ClearAllRecipients();
                    if ($num < count($recipients) - 1) {
                        ADAFileLogger::log('goin to sleep...', $logFile);
                        usleep($sleepTime);
                        ADAFileLogger::log('...got woken up', $logFile);
                    }
                } // foreach ($recipients as $num=>$recipient)
            } // if (!AMADB::isError($recipients) ...
        } // if (!AMADB::isError($reminderData))
    }
} // if method is POST

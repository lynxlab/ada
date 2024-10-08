<?php

/**
 * NEWSLETTER MODULE.
 *
 * @package     newsletter module
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            newsletter
 * @version     0.1
 */

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use Lynxlab\ADA\Module\Newsletter\AMANewsletterDataHandler;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
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

$GLOBALS['dh'] = AMANewsletterDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = [];

// buffer the output, close the connection with the browser and run a "background" task

ini_set('zlib.output_compression', 0);
// Turn off output buffering
ini_set('output_buffering', 'off');
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_end_clean();

header("Connection: close\r\n");
header("Content-Encoding: none\r\n");
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
ignore_user_abort(true);
// remove duplicate cookies set when calling CastingSystemQueueManagement::setSessionMessage
// clear_duplicate_cookies();
// capture output
ob_start();
echo str_pad(' ', 5 * 1024);
// these headers tell the browser to close the connection
// once all content has been transmitted
header("Content-Type: application/html\r\n");
header("Content-Length: " . ob_get_length() . "\r\n");
// flush all output
ob_end_flush();
flush();
@ob_end_clean();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && intval($_POST['id']) > 0) {
        $id_newsletter = intval($_POST['id']);
        $newsLetterArray = $dh->getNewsletter($_POST['id']);
        $filterArray = $dh->buildFilterFromArray($_POST);
        $recipients = $dh->getUsersFiltered($filterArray, false);
        $count = count($recipients);

        $history_id = $dh->saveNewsletterHistory($id_newsletter, $filterArray, $count, AMANewsletterDataHandler::MODULES_NEWSLETTER_HISTORY_STATUS_UNDEFINED);

        if (!AMADB::isError($history_id)) {
            ignore_user_abort(true);
            session_write_close();

            $sleepTime = intval(3600 / MODULES_NEWSLETTER_EMAILS_PER_HOUR * 1000000); // sleep time in microseconds

            $logFile = MODULES_NEWSLETTER_LOGDIR . 'log-' . $id_newsletter . '-' . date('d-m-Y_His');
            if (!is_dir(MODULES_NEWSLETTER_LOGDIR)) {
                mkdir(MODULES_NEWSLETTER_LOGDIR, 0777, true);
            }
            if (!is_file($logFile)) {
                touch($logFile);
            }

            ADAFileLogger::log("Sending out to: \n" . print_r($recipients, true), $logFile);

            $dh->setHistoryStatus($history_id, AMANewsletterDataHandler::MODULES_NEWSLETTER_HISTORY_STATUS_SENDING);
            register_shutdown_function('Lynxlab\ADA\Module\Newsletter\Functions\shutDown', $dh, $history_id);

            /**
             * get datas for general substitution
             */
            $courseTitle = '';
            if (!is_null($filterArray['idCourse'])) {
                $courseInfo = $dh->getCourse(intval($filterArray['idCourse']));
                if (!AMADB::isError($courseInfo)) {
                    $courseTitle = $courseInfo['nome'] . '-' . $courseInfo['titolo'];
                }
            }

            $instanceTitle = '';
            if (!is_null($filterArray['idInstance'])) {
                $instanceInfo = $dh->courseInstanceGet(intval($filterArray['idInstance']));
                if (!AMADB::isError($instanceInfo)) {
                    $instanceTitle = $instanceInfo['title'];
                }
            }

            $senderEmail = MODULES_NEWSLETTER_DEFAULT_EMAIL_ADDRESS; // uncomment to get domain from HTTP_ROOT_DIR.'@'.getDomain(HTTP_ROOT_DIR);
            $senderFullName = $newsLetterArray['sender'] ?? $senderEmail;

            // perform general substitutions for course and instance
            $HTMLModelText = str_replace(['{coursename}','{instancename}'], [ $courseTitle, $instanceTitle], $newsLetterArray['htmltext']);
            $PLAINModelText = str_replace(['{coursename}','{instancename}'], [ $courseTitle, $instanceTitle], $newsLetterArray['plaintext']);

            // perform general substitutions for relative path images
            $HTMLModelText = preg_replace('/(src=[\'"])\/?[^>]*(\/?services\/media\/)/', '$1' . HTTP_ROOT_DIR . '/$2', $HTMLModelText);

            // email class init and common values
            $phpmailer = new ADAPHPMailer();
            $phpmailer->CharSet = 'UTF-8';

            $phpmailer->configSend();

            $phpmailer->SetFrom($senderEmail, $senderFullName);
            $phpmailer->AddReplyTo($senderEmail, $senderFullName);
            $phpmailer->IsHTML(true);
            $phpmailer->Subject = $newsLetterArray['subject'];

            foreach ($recipients as $num => $recipient) {
                set_time_limit(0);

                ADAFileLogger::log('sending out#' . $num . ' userID=' . $recipient[0] . ' e-mail=' . $recipient[1], $logFile);

                $userInfo = $dh->getUser($recipient[0]);

                if (strlen($recipient[1]) > 0) {
                    $userFullName = '';

                    if (!AMADB::isError($userInfo)) {
                        // performs user substitutions
                        $HTMLText = str_replace(
                            ["{name}","{lastname}","{e-mail}"],
                            [$userInfo['nome'], $userInfo['cognome'], $userInfo['email']],
                            $HTMLModelText
                        );

                        $PLAINText = str_replace(
                            ["{name}","{lastname}","{e-mail}"],
                            [$userInfo['nome'], $userInfo['cognome'], $userInfo['email']],
                            $PLAINModelText
                        );

                        $userFullName = ucwords(strtolower($userInfo['nome'] . ' ' . $userInfo['cognome']));
                    } else {
                        $HTMLText = $HTMLModelText;
                        $PLAINText = $PLAINModelText;
                        $userFullName = '';
                    }

                    // $recipient[1] is the email in the current run loop
                    $phpmailer->AddAddress($recipient[1], $userFullName);
                    $phpmailer->Body = $HTMLText;
                    $phpmailer->AltBody = $PLAINText;
                    $phpmailer->Send();
                    $phpmailer->ClearAllRecipients();

                    if ($num < count($recipients) - 1) {
                        ADAFileLogger::log('goin to sleep...', $logFile);
                        usleep($sleepTime);
                        ADAFileLogger::log('...got woken up', $logFile);
                    }
                } else {
                    ADAFileLogger::log('empty email#' . $num . ' userID=' . $recipient[0] . ' e-mail=' . $recipient[1], $logFile);
                }
            }
            $res = $dh->setHistoryStatus($history_id, AMANewsletterDataHandler::MODULES_NEWSLETTER_HISTORY_STATUS_SENT);
            if (AMADB::isError($res)) {
                ADAFileLogger::log(print_r($res, true), $logFile);
            }
            ADAFileLogger::log('Done... OK!', $logFile);
        }
    }
}

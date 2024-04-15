<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Course\Course;

use function \translateFN;

/**
 * build certificates for all students in the passed instance and downloads as a zip file
 *
 * @package
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Switcher\Subscription;
use PHPMailer\PHPMailer\Exception;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Switcher\Functions\formatBytes;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

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
    AMA_TYPE_SWITCHER => ['layout', 'user', 'course', 'course_instance'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
SwitcherHelper::init($neededObjAr);

$doDownload = false;
$data = null;

$checkOnly = (array_key_exists('check', $_REQUEST) && intval($_REQUEST['check']) >= 0) ? (bool)intval($_REQUEST['check']) : true;
$sendEmail = (array_key_exists('email', $_REQUEST) && intval($_REQUEST['email']) >= 0) ? (bool)intval($_REQUEST['email']) : false;

$logfile = ROOT_DIR . '/log/instanceCertificate.log';
if (!is_file($logfile)) {
    touch($logfile);
}
ini_set("log_errors", 1);
ini_set("error_log", $logfile);

$maxCertdownloadCount = defined('MAX_CERTDOWNLOAD_COUNT') ? constant('MAX_CERTDOWNLOAD_COUNT') : 50;

if (!$checkOnly && isset($_REQUEST['c']) && isset($_REQUEST['t']) && strlen($_REQUEST['c']) > 0 && strlen($_REQUEST['t']) > 0) {
    // a cookie name and token has been passed, send them back to the server in a cookie
    setcookie($_REQUEST['c'], $_REQUEST['t'], time() + 600, "/"); // expires in 10 minutes
}

// check if it's ok to run the export
if (array_key_exists('selectedIds', $_REQUEST) && is_array($_REQUEST['selectedIds']) && count($_REQUEST['selectedIds']) > 0) {
    $selectedIs = $_REQUEST['selectedIds'];
    if (count($selectedIs) <= $maxCertdownloadCount) {
        if (array_key_exists('id_instance', $_REQUEST) && intval($_REQUEST['id_instance']) > 0) {
            $courseInstanceObj = new CourseInstance(intval($_REQUEST['id_instance']));
            if ($courseInstanceObj instanceof CourseInstance && $courseInstanceObj->full == 1) {
                if (!$courseInstanceObj->isTutorCommunity() && defined('ADA_PRINT_CERTIFICATE') && (ADA_PRINT_CERTIFICATE)) {
                    $subscriptions = Subscription::findSubscriptionsToClassRoom($courseInstanceObj->getId(), true);
                    if (is_array($subscriptions) && count($subscriptions) > 0) {
                        // filter out students not having the id in the selectedIds array
                        $subscriptions = array_filter($subscriptions, function ($asub) use ($selectedIs) {
                            return in_array($asub->getSubscriberId(), $selectedIs);
                        });
                        unset($selectedIs);
                        // filter out students not having the requirements
                        $subscriptions = array_filter($subscriptions, function ($asub) {
                            return ADAUser::CheckRequirementsCertificate($asub->getSubscriberId(), $asub->getSubscriptionStatus());
                        });
                        if (is_array($subscriptions) && count($subscriptions) > 0) {
                            // do the report
                        } else {
                            $data = translateFN('Nessuno studente iscritto per cui esportare il certificato');
                        }
                    } else {
                        $data = translateFN('Nessuno studente iscritto per cui esportare il certificato');
                    }
                } else {
                    $data = translateFN('Comunità di tutor o certificati disabilitati');
                }
            } else {
                $data = translateFN("Impossibile caricare l'istanza");
            }
        } else {
            $data = translateFN('Passare un id istanza valido');
        }
    } else {
        $data = translateFN(sprintf("Il Numero massimo di studenti per ogni download è %d<br/>Ne sono stati selezionati %d", $maxCertdownloadCount, count($selectedIs)));
    }
} else {
    $data = translateFN('Nessuno studente per cui esportare il certificato');
}

// if $data is still null, run the export
if (is_null($data)) {
    if (!$checkOnly) {
        if ($sendEmail) {
            if (strlen($userObj->getEmail()) <= 0) {
                $data = translateFN("Impostare un indirizzo email nel proprio profilo");
            } else {
                // headers to close connection and send to the background....
                $data = sprintf(translateFN('Il file con i %d certificati %s sarà inviato per email appena pronto'), count($subscriptions), '<br/>');
                // buffer the output, close the connection with the browser and run a "background" task
                session_write_close();
                ob_end_clean();
                header("Connection: close\r\n");
                header("Content-Encoding: none\r\n");
                ignore_user_abort(true);
                // capture output
                ob_start();
                echo json_encode(['data' => $data]);
                // these headers tell the browser to close the connection
                // once all content has been transmitted
                header("Content-Type: application/json\r\n");
                header("Content-Length: " . ob_get_length() . "\r\n");
                // flush all output
                ob_end_flush();
                flush();
                @ob_end_clean();
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        }

        ini_set('memory_limit', '512M');
        $_GET['forcereturn'] = true;
        $_GET['id_course_instance'] = $courseInstanceObj->getId();
        // These are needed by the Rendering Engine called by the userCertificate inclusion
        $self = 'userCertificate';
        $GLOBALS['self'] = $self;
        $layout_dataAr['module_dir'] = 'browsing/';

        $count = 0;
        $total = count($subscriptions);
        // $addFiles = [];
        $dirname = ADA_UPLOAD_PATH . str_replace(' ', '-', microtime());
        mkdir($dirname);
        $dirname .= DIRECTORY_SEPARATOR;

        foreach ($subscriptions as $subscription) {
            ++$count;
            ADAFileLogger::log(sprintf("student ID %4d (%03d/%03d) [Mem.%5s]", $subscription->getSubscriberId(), $count, $total, formatBytes(memory_get_peak_usage(true))), $logfile);
            // must set the id_user to be used by userCertificate
            $_GET['id_user'] = $subscription->getSubscriberId();
            set_time_limit(120);
            $pdfArr = include ROOT_DIR . '/browsing/userCertificate.php';
            if (array_key_exists('content', $pdfArr) && strlen($pdfArr['content']) > 0) {
                $zipname = (array_key_exists('filename', $pdfArr) && strlen($pdfArr['filename']) > 0) ? $pdfArr['filename'] : translateFN('studente') . '-' . $subscription->getSubscriberId() . '.pdf';
                file_put_contents($dirname . $zipname, $pdfArr['content']);
            }
            unset($pdfArr);
            if ($count == $total || ($count % 25) === 0) {
                ADAFileLogger::log('Collect garbage...', $logfile);
                gc_collect_cycles();
                if (function_exists('gc_mem_caches')) {
                    gc_mem_caches();
                }
            }
        }

        if ($count > 0) {
            // Prepare ZipArchive
            $filename = translateFN('Certificati-classe-') . $courseInstanceObj->getId();
            $file = $dirname . $filename . '.zip';
            $zip = new \ZipArchive();
            $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addGlob($dirname . '*.{pdf}', GLOB_BRACE, ['add_path' => $filename . DIRECTORY_SEPARATOR, 'remove_all_path' => true]);
            $zip->close();
            array_map('unlink', glob($dirname . '*.{pdf}', GLOB_BRACE));
            $doDownload = !$sendEmail;
        }

        if ($doDownload) {
            //Set headers
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary");
            header('Content-Type: application/zip');
            header('Content-Length: ' . filesize($file));
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            readfile($file);
        } elseif ($sendEmail) {
            // code to send the email here
            // true will make PHPMailer throw exceptions
            $phpmailer = new ADAPHPMailer(true);
            // $phpmailer->SMTPDebug = 1;
            // $phpmailer->Debugoutput = function($str, $level) use ($logfile) {
            //  if ($level <= 1) ADAFileLogger::log('MAILER: '.$str, $logfile);
            // };
            try {
                $phpmailer->configSend();
                $phpmailer->CharSet = ADA_CHARSET;
                $phpmailer->SetFrom(ADA_NOREPLY_MAIL_ADDRESS);
                $phpmailer->AddReplyTo(ADA_NOREPLY_MAIL_ADDRESS);
                $phpmailer->IsHTML(true);
                $phpmailer->Subject = translateFN('Certificati per la classe') . ': ' . $courseInstanceObj->getTitle();
                $phpmailer->AddAddress($userObj->getEmail(), $userObj->getFullName());
                $phpmailer->Body = translateFN('In allegato il file richiesto');
                $phpmailer->AltBody = $phpmailer->Body;

                ADAFileLogger::log(sprintf("Add attachment [Mem.%5s]", formatBytes(memory_get_peak_usage(true))), $logfile);
                $phpmailer->AddAttachment($file, basename($file));
                ADAFileLogger::log(sprintf("Send email [Mem.%5s]", formatBytes(memory_get_peak_usage(true))), $logfile);
                $emailed = $phpmailer->Send();
                ADAFileLogger::log(sprintf("Send email result %s [Mem.%5s]", ($emailed ? 'true' : 'false'), formatBytes(memory_get_peak_usage(true))), $logfile);
            } catch (Exception $e) {
                $data = $e->getMessage();
                ADAFileLogger::log('exception message: ' . $data, $logfile);
            }
        } else {
            $data = translateFN('Nessun certificato da scaricare');
        }
        ADAFileLogger::log('unlink ' . basename($file), $logfile);
        @unlink($file);
        @rmdir(rtrim($dirname, DIRECTORY_SEPARATOR));
    } else {
        $data = 'OK';
    }
}

if ($checkOnly && !is_null($data)) {
    header('Content-Type: application/json');
    $out['data'] = $data;
    $out['count'] = (isset($subscriptions) && is_array($subscriptions)) ? count($subscriptions) : 0;
    die(json_encode($out));
}
die();

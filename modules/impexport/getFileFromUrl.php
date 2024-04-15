<?php

use function \progressCallback;

use function \formatSizeUnits;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * EXPORT MODULE.
 *
 * @package     export/import course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        impexport
 * @version     0.1
 */

function formatSizeUnits($bytes)
{

    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}

function progressCallback($curlRes, $download_size, $downloaded_size, $upload_size, $uploaded_size)
{

    if (curl_getinfo($curlRes, CURLINFO_HTTP_CODE) == 200) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if ($downloaded_size < $download_size) {
            $_SESSION['importProgress']['progressSTATUS'] = 'RUNNING';
            $_SESSION['importProgress']['progressMSG'] = formatSizeUnits($downloaded_size) .
                " " . translateFN("di") . " " . formatSizeUnits($download_size);
            session_write_close();
        } else {
            $_SESSION['importProgress']['progressSTATUS'] = 'DONE';
            $_SESSION['importProgress']['progressMSG'] = formatSizeUnits($downloaded_size) .
                " " . translateFN("di") . " " . formatSizeUnits($downloaded_size);
            // prevent ERR_RESPONSE_HEADERS_TOO_BIG on browser
            header_remove('Set-Cookie');
        }
    }
}

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

unset($_SESSION['importProgress']);

$_SESSION['importProgress']['status'] = 'DOWNLOAD';
$_SESSION['importProgress']['progressSTATUS'] = 'ERROR';

$retArray =  ['status' => 'ERROR'];

$url = (isset($_GET['url']) && strlen(trim($_GET['url'])) > 0) ? $url = trim($url, '!"#$%&\'()*+,-./@:;<=>[\\]^_`{|}~') : false;
$url = DataValidator::validateUrl($url);

if ($url !== false) {
    if (is_dir(ADA_UPLOAD_PATH) && is_writable(ADA_UPLOAD_PATH)) {
        $urlBaseName = basename($url);

        // check if url ends with a .zip
        if (preg_match("/\.zip$/i", $urlBaseName, $dummy) === 1) {
            // process the url
            $targetFile = ADA_UPLOAD_PATH . $urlBaseName;

            $fh = fopen($targetFile, 'wb');
            if ($fh !== false) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOPROGRESS, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progressCallback');
                curl_setopt($ch, CURLOPT_FILE, $fh);
                curl_exec($ch);

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $errno = curl_errno($ch);
                curl_close($ch);
                fclose($fh);

                if ($errno === 0 && $httpCode == 200) {
                    $retArray['status'] = 'OK';
                    $retArray['filename'] = $urlBaseName;
                } else {
                    unlink($targetFile);
                    // Check for errors and display the error message
                    if ($errno !== 0) {
                        $error_message = curl_strerror($errno);
                        $retArray['msg'] = "cURL error ({$errno}):\n {$error_message}";
                    } else {
                        $retArray['msg'] = translateFN('Errore HTTP: ') . $httpCode;
                    }
                }
            } else {
                $retArray['msg'] = translateFN('Impossibile salvare il download');
            }
        } else {
            $retArray['msg'] = translateFN('La URL deve terminare con estensione .zip');
        }
    } else {
        $retArray['msg'] = translateFN('Directory di destinazione inesistente o non scrivibile');
    }
} else {
    $retArray['msg'] = translateFN('URL non valida');
}


header('Content-Type: application/json');
echo json_encode($retArray);

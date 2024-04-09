<?php

/**
 * SLIDEIMPORT MODULE.
 *
 * @package        slideimport module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           slideimport
 * @version        0.1
 */

try {
    if (!@include_once(MODULES_SLIDEIMPORT_PATH . '/vendor/autoload.php')) {
        // @ - to suppress warnings,
        throw new Exception(
            json_encode([
                'header' => 'Slideimport module will not work because autoload file cannot be found!',
                'message' => 'Please run <code>composer install</code> in the module subdir',
            ])
        );
    } else {
        // MODULE'S OWN DEFINES HERE
        // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
        /**
         * session var name of the uploaded file
         */
        define('MODULES_SLIDEIMPORT_UPLOAD_SESSION_VAR', 'slideimportFile');

        define('IMPORT_IMAGE_HEIGHT', 800);
        define('IMPORT_PREVIEW_HEIGHT', 210);

        define('IMAGE_FORMAT', 'jpg');
        define('IMAGE_COMPRESSION_QUALITY', 90);
        define('IMAGE_HEADER_PREVIEW', 'image/jpeg');

        /**
         * Define IMPORT_MIME_TYPE as a subset of ADA_MIME_TYPE
         */
        $GLOBALS['IMPORT_MIME_TYPE']["application/pdf"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/x-pdf"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/msword"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/mspowerpoint"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.ms-powerpoint"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.ms-excel"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.ms-office"]['permission'] = _GO;
        // docx, xslx, pptx etc...
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.wordprocessingml.document"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.wordprocessingml.template"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.spreadsheetml.template"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.presentationml.presentation"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.presentationml.template"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.openxmlformats-officedocument.presentationml.slideshow"]['permission'] = _GO;
        // odt, ods, odp etc...
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.text"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.spreadsheet"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.presentation"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.graphics"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.chart"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.image"]['permission'] = _GO;
        $GLOBALS['IMPORT_MIME_TYPE']["application/vnd.oasis.opendocument.text-master"]['permission'] = _GO;

        // phpcs:enable
        return true;
    }
} catch (Exception $e) {
    $text = json_decode($e->getMessage(), true);
    // populating $_GET['message'] is a dirty hack to force the error message to appear in the home page at least
    if (!isset($_GET['message'])) {
        $_GET['message'] = '';
    }
    $_GET['message'] .= '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
    if (array_key_exists('header', $text) && strlen($text['header']) > 0) {
        $_GET['message'] .= '<div class="header">' . $text['header'] . '</div>';
    }
    if (array_key_exists('message', $text) && strlen($text['message']) > 0) {
        $_GET['message'] .= '<p>' . $text['message'] . '</p>';
    }
    $_GET['message'] .= '</div></div>';
}
return false;

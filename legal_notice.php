<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;

/*
 *
 * Comportamento
 * in base alla lingua passata (default. en) carica unfile dal modello privacy_LINGUA.ESTENSIONE
 * se non viene passata l'estensione o se è pdf restituisce un PDF, altrimenti
 * se viene passata l'estensione e se è html produce una pagina normale con template default
 */

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course'];

$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout'],
  AMA_TYPE_STUDENT         => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_SWITCHER     => ['layout'],
  AMA_TYPE_AUTHOR       => ['layout'],
  AMA_TYPE_ADMIN        => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);


$self =  "index";
$docDir = "/docs/";


if (isset($_GET['type'])) {
    $fileext = $_GET['type'];
} else {
    $fileext = 'html';
}
if (isset($_GET['lan'])) {
    $language = $_GET['lan'];
} else {
    $language = 'en';
}

$short_legal_notice_file_name = "legal_notice_$language.$fileext";
$legal_notice_file = ROOT_DIR . $docDir . $short_legal_notice_file_name;

if ($fileext == "html") {
    header('Location: ' . HTTP_ROOT_DIR . "/browsing/external_link.php?file=$short_legal_notice_file_name");
    exit();
} elseif ($fileext == "pdf") {
    $PDF_text =  @file_get_contents($legal_notice_file, 'r');
    if ($PDF_text != null) {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");          // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");                          // HTTP/1.0
        header("Content-Type: application/pdf");
        // header("Content-Length: ".filesize($name));
        header("Content-Disposition: attachment; filename=$short_legal_notice_file_name");
        echo $PDF_text;
        // header ("Connection: close");
        exit;
    } else {
        $message = translateFN("File not found");
        $status = translateFN("Error");
    }
}
$content_dataAr = [
    'user_name' => $user_name,
    'home'      => $home,
    'text'      => $html_text,
    'menu'      => $menu,
    'message'   => $message,
    'status'    => $status,
];
/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);

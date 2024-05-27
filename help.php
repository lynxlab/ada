<?php

use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/*
 *
 * Comportamento:
 * in base alla lingua passata (default. en) carica un file dal modello help_RUOLO_(ARG_)LINGUA.ESTENSIONE
 * se non viene passata l'estensione o se è pdf restituisce un PDF, altrimenti
 * se viene passata l'estensione e se è html produce una pagina normale con template index
 */

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course'];
/**
 * Users (types) allowed to access this module.
 */
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

$self =  'index';
$docDir = '/docs/';
$helpPath = '';

$fileext = DataValidator::checkInputValues('type', 'ExtensionType', INPUT_GET, 'html');

$language = DataValidator::checkInputValues('lan', 'Language', INPUT_GET, $_SESSION['sess_user_language']);


switch ($id_profile) {
    case AMA_TYPE_SWITCHER:
        $usertype = 'switcher';
        break;
    case AMA_TYPE_STUDENT:
        $usertype = 'user';
        break;
    case AMA_TYPE_TUTOR:
        $usertype = 'practitioner';
        break;
    case AMA_TYPE_AUTHOR:
        $usertype = 'author';
        break;
    case AMA_TYPE_ADMIN:
        $usertype = 'admin';
        break;
    case AMA_TYPE_VISITOR:
    default:
        $usertype = 'guest';
        break;
}


$args = DataValidator::checkInputValues('arg', 'Value', INPUT_GET);
if ($arg !== false) {
    $short_help_file_name = $usertype . '_' . $arg . '_' . $language . '.' . $fileext;
    $title = translateFN("Help for") . " $usertype " . translateFN("on") . " $arg";
} else {
    $title = translateFN("Help for") . " $usertype ";
    $short_help_file_name = $usertype . '_' . $language . '.' . $fileext;
}
$help_file = ROOT_DIR . $docDir . $short_help_file_name;

if ($fileext == 'html') {
    header('Location: ' . HTTP_ROOT_DIR . $helpPath . "/browsing/external_link.php?file=$short_help_file_name");
    exit();
} elseif ($fileext == 'pdf') {
    $PDF_text =  @file_get_contents($help_file, 'r');
    if ($PDF_text != null) {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");          // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");                          // HTTP/1.0
        header("Content-Type: application/pdf");
        // header("Content-Length: ".filesize($name));
        header("Content-Disposition: attachment; filename=$short_help_file_name");
        echo $PDF_text;
        // header ("Connection: close");
        exit();
    } else {
        $message = translateFN("File not found: ") . $short_help_file_name;
        $status = translateFN("Error");
    }
}

$content_dataAr = [
'user_name' => $user_name,
'title'     => $title,
'text'      => $html_text,
'menu'      => $menu,
'message'   => $message,
'status'    => $status,
];

/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $content_dataAr);

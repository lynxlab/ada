<?php

use Lynxlab\ADA\CORE\xml\CourseXmlFileProcess;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

$self =  Utilities::whoami();

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
ServiceHelper::init($neededObjAr);
/*
 * YOUR CODE HERE
*/
// inizializzazione variabili
$dati = '';
$ris_ar = [];
$course_ha = [];
$author_id = '';
$mediapath = '';
$set_ha = [];
$xp = '';
$xml_file = $xml;

$course_ha = $dh->getCourse($id);
if (AMADataHandler::isError($course_ha)) {
    $errObj = new ADAError($course_ha);
}

$author_id = $course_ha['id_autore'];
//$author_ha = $dh->getAuthor($author_id);


/* Controlla la presenza del mediapath ed eventualmente crea la directory
   per i media.
   Se non esiste assegna come mediapath quello creato all'interno del
   MEDIA_PATH_DEFAULT con nome directory = all' id dell'autore.
*/
$DIR_autore = "$author_id/";


if ($course_ha['media_path'] == "") {
    $mediapath = realpath(ROOT_DIR . MEDIA_PATH_DEFAULT)
               . DIRECTORY_SEPARATOR . $DIR_autore;
} else {
    $course_media_path = str_replace("\\", "/", $course_ha['media_path']);
    if (strstr($course_media_path, (string) $root_dir)) {
        $mediapath = $course_media_path ;
    } else {
        $mediapath = ROOT_DIR . DIRECTORY_SEPARATOR . $course_media_path ;
    }
}


if (!@is_dir($mediapath)) {
    // crea la directory dei media per l'autore nella directory mediapath di default
    mkdir($mediapath, ADA_WRITABLE_DIRECTORY_PERMISSIONS);
}

// XML file process
// utilizzo classe processa XML
$xp = new CourseXmlFileProcess();

$set_ha = [
    'id_author' => $author_id,
    'id_course' => $id,
    'xml_file'  => realpath(AUTHOR_COURSE_PATH_DEFAULT . '/' . $xml_file),
    'media_path' => $mediapath,
];


// inizializzazione
if ($xp->setInit($set_ha)) {
    // parsing file xml
    $ris_ar = $xp->courseXmlFileParse();

    $xp->dataVoid();

    if ($ris_ar['0'] != 'errore') {
        $dati =  translateFN('Risultato: ') . $ris_ar[0] . '<br>' ;
        $dati .=  translateFN('Nodi processati: ') . $ris_ar['1'] . '<br>' ;
        $dati .=  translateFN('Media copiati: ') . $ris_ar['2'] . '<br>' ;
        $dati .=  translateFN('Media non copiati: ') . count($ris_ar['3']) . '<br>' ;
        $backup_copy = copy(realpath(AUTHOR_COURSE_PATH_DEFAULT . '/' . $xml_file), realpath(UPLOAD_PATH . $author_id . '/' . $id . '.xml'));
    } else {
        $dati =  translateFN('Risultato: ') . $ris_ar[0] . '<br>' ;
        $dati .= translateFN('Nodi processati: ') . @$ris_ar['1'] . '<br>' ;
        $dati .= translateFN('Media copiati: ') . @$ris_ar['2'] . '<br>' ;
        $dati .= translateFN('Media non copiati: ') . count(@$ris_ar['3']) . '<br>' ;
        foreach ($ris_ar['errori'] as $key => $val) {
            $dati .= $val . '<br>' ;
        }
    }
} else {
    Utilities::mydebug(__LINE__, __FILE__, $xp->init_error);
    $dati .= translateFN('ERRORE: Non è stato scelto un file XML o la sintassi non &egrave; corretta.') ;
}

// elimina l'oggetto dalla memoria
unset($xp);

$menu = '<a href="' . HTTP_ROOT_DIR . '/courses/author.php">' . translateFN('home') . '</a>';
$menu .= '<br><a href="' . HTTP_ROOT_DIR . '/admin/author_add_course.php">' . translateFN('nuovo corso') . '</a>';


$help = translateFN("Da qui l'Autore del corso pu&ograve; inserire un corso in formato XML nel database ADA.");
$status = translateFN('Inserimento corso');

// preparazione output HTML e print dell' output
$title = translateFN('ADA - Inserimento del corso nel database');
$content_dataAr = [
    'menu' => $menu,
    'dati' => $dati,
// 'course_title'=>$course_title,
// 'course_istance'=>$course_date,
    'help' => $help,
    'status' => $status,
    'user_name' => $user_name,
    'user_type' => $user_type,
  'agenda'  => $user_agenda->getHtml(),
  'messages' => $user_messages->getHtml(),
];
ARE::render($layout_dataAr, $content_dataAr);

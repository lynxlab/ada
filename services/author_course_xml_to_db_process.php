<?php
/**
 *
 * @package     Default
 * @author		Stefano Penge <steve@lynxlab.com>
 * @author		Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @copyright	Copyright (c) 2009, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version		0.1
 */

use Lynxlab\ADA\Main\Helper\ServiceHelper;

use function Lynxlab\ADA\Main\Utilities\mydebug;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)).'/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = array();

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = array(AMA_TYPE_AUTHOR);
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = array(
        AMA_TYPE_AUTHOR => array('layout')
);

require_once ROOT_DIR.'/include/module_init.inc.php';

$self =  whoami();

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
 * @var array $user_messages
 * @var array $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var History $user_history
 * @var Course $courseObj
 * @var Course_Instance $courseInstanceObj
 * @var ADAPractitioner $tutorObj
 * @var Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
ServiceHelper::init($neededObjAr);
/*
 * YOUR CODE HERE
*/
include_once ROOT_DIR .'/include/xml_parse_class.inc.php';

// inizializzazione variabili
$dati = '';
$ris_ar = array();
$course_ha = array();
$author_id = '';
$mediapath = '';
$set_ha = array();
$xp = '';
$xml_file = $xml;

$course_ha = $dh->get_course($id);
if (AMA_DataHandler::isError($course_ha)) {
    $errObj = new ADA_Error($course_ha);
}

$author_id = $course_ha['id_autore'];
//$author_ha = $dh->get_author($author_id);


/* Controlla la presenza del mediapath ed eventualmente crea la directory
   per i media.
   Se non esiste assegna come mediapath quello creato all'interno del
   MEDIA_PATH_DEFAULT con nome directory = all' id dell'autore.
*/
$DIR_autore = "$author_id/";


if($course_ha['media_path']=="") {
    $mediapath = realpath(ROOT_DIR . MEDIA_PATH_DEFAULT)
               . DIRECTORY_SEPARATOR . $DIR_autore;
} else {
    $course_media_path = str_replace("\\","/",$course_ha['media_path']);
    if (strstr($course_media_path,$root_dir)) {
        $mediapath = $course_media_path ;
    } else {
        $mediapath = ROOT_DIR . DIRECTORY_SEPARATOR . $course_media_path ;
    }
}


if(!@is_dir($mediapath)) {
    // crea la directory dei media per l'autore nella directory mediapath di default
    mkdir($mediapath, ADA_WRITABLE_DIRECTORY_PERMISSIONS);
}

// XML file process
// utilizzo classe processa XML
$xp = new course_xml_file_process ;

$set_ha = array(
    'id_author' => $author_id,
    'id_course' =>$id,
    'xml_file'  => realpath(AUTHOR_COURSE_PATH_DEFAULT .'/'. $xml_file),
    'media_path' => $mediapath
);


// inizializzazione
if($xp->set_init($set_ha)) {
    // parsing file xml
    $ris_ar = $xp->course_xml_file_parse();

    $xp->data_void();

    if($ris_ar['0']!='errore') {
        $dati =  translateFN('Risultato: '). $ris_ar[0] .'<br>' ;
        $dati .=  translateFN('Nodi processati: '). $ris_ar['1'] .'<br>' ;
        $dati .=  translateFN('Media copiati: '). $ris_ar['2'] .'<br>' ;
        $dati .=  translateFN('Media non copiati: '). count($ris_ar['3']) .'<br>' ;
        $backup_copy = copy (realpath(AUTHOR_COURSE_PATH_DEFAULT .'/'. $xml_file),realpath(UPLOAD_PATH . $author_id .'/'. $id.'.xml'));
    }else {
        $dati =  translateFN('Risultato: '). $ris_ar[0] .'<br>' ;
        $dati .= translateFN('Nodi processati: '). @$ris_ar['1'] .'<br>' ;
        $dati .= translateFN('Media copiati: '). @$ris_ar['2'] .'<br>' ;
        $dati .= translateFN('Media non copiati: '). count(@$ris_ar['3']) .'<br>' ;
        foreach ($ris_ar['errori'] as $key => $val) {
            $dati .= $val .'<br>' ;
        }
    }
}else {
    mydebug(__LINE__,__FILE__,$xp->init_error);
    $dati .= translateFN('ERRORE: Non è stato scelto un file XML o la sintassi non &egrave; corretta.') ;

}

// elimina l'oggetto dalla memoria
unset($xp);

$menu = '<a href="'. HTTP_ROOT_DIR .'/courses/author.php">'.translateFN('home').'</a>';
$menu .= '<br><a href="'.HTTP_ROOT_DIR.'/admin/author_add_course.php">'.translateFN('nuovo corso').'</a>';

$banner = include ROOT_DIR.'/include/banner.inc.php';

$help = translateFN("Da qui l'Autore del corso pu&ograve; inserire un corso in formato XML nel database ADA.");
$status = translateFN('Inserimento corso');

// preparazione output HTML e print dell' output
$title = translateFN('ADA - Inserimento del corso nel database');
$content_dataAr = array(
    'menu'=>$menu,
    'banner'=>$banner,
    'dati'=>$dati,
// 'course_title'=>$course_title,
// 'course_istance'=>$course_date,
    'help'=>$help,
    'status'=>$status,
    'user_name'=>$user_name,
    'user_type'=>$user_type,
  'agenda'  => $user_agenda->getHtml(),
  'messages'=> $user_messages->getHtml()
);
ARE::render($layout_dataAr, $content_dataAr);
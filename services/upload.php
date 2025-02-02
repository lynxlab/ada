<?php

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\HtmlLibrary\UserModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\CollaboraACL\AMACollaboraACLDataHandler;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLException;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Upload\Functions\uploadFile;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_AUTHOR => ['layout','course'],
  AMA_TYPE_TUTOR => ['layout','course'],
  AMA_TYPE_STUDENT => ['layout','course'],
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
if (isset($err_msg)) {
    $status = $err_msg;
} else {
    $status = translateFN('Invio documenti allegati ad un nodo');
}

$help = translateFN('Da qui lo studente pu&ograve; inviare un file da allegare al nodo corrente');

/*
 * vito, modifica all'upload dei file per l'upload dei file dall'editor dei nodi
 */

//if ( defined('DEVdoEdit_node') )
if (isset($_GET['caller']) && $_GET['caller'] == 'editor') {
    $dh = $GLOBALS['dh'];
    /*
     * dati passati dal form di upload del file
     */
    $course_id          = $_POST['course_id'];
    $course_instance_id = $_POST['course_instance_id'];
    $user_id            = $_POST['user_id'];
    $node_id            = $_POST['node_id'];
    /*
     * dati relativi al file uploadato
     */
    $filename          = $_FILES['file_up']['name'];
    $source            = $_FILES['file_up']['tmp_name'];
    $file_size         = $_FILES['file_up']['size'];
    $up_file_type      = $_FILES['file_up']['type'];
    $file_upload_error = $_FILES['file_up']['error'];
    // contiene il codice di errore da restituire al chiamante
    $error_code = 0;
    $ada_filetype = -1;

    /*
     * Obtain the uploaded file's mimetype
     */
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            $file_type = false;
        } else {
            $file_type = finfo_file($finfo, $source);
        }
    } else {
        $file_type = mime_content_type($_FILES['file_up']['type']);
    }

    /*
     * codice esistente:
     */

    $course_ha = $dh->getCourse($course_id);//$id_course);
    if (AMADataHandler::isError($course_ha)) {
        $msg = $course_ha->getMessage();
        header("Location: " . $http_root_dir . "/browsing/student.php?status=$msg");
        exit();
    }
    // look for the author, starting from author's id
    $author_id = $course_ha['id_autore'];
    //il percorso in cui caricare deve essere dato dal media path del corso, e se non presente da quello di default
    if (isset($_POST['media_path']) && $_POST['media_path'] != '') {
        $media_path = $_POST['media_path'];
    } elseif ($course_ha['media_path'] != "") {
        $media_path = $course_ha['media_path'];
    } else {
        //        $media_path = MEDIA_PATH_DEFAULT . $author_id ;
        $media_path = MEDIA_PATH_DEFAULT . $user_id ;
    }
    /*
     * fine codice esistente.
     */

    /*
     * controllo che la cartella indicata da $media_path esista e sia scrivibile
     */
    $upload_path = $root_dir . $media_path;
    if (!is_dir($upload_path) || !is_writable($upload_path)) {
        // restituire un messaggio di errore e saltare la parte di scrittura del file
        $error_code = ADA_FILE_UPLOAD_ERROR_UPLOAD_PATH;
    } else {
        // cartella di upload presente e scrivibile
        /*
         * controllo che sia stato inviato un file e che non si siano verificati errori
         * durante l'upload.
         */
        $empty_filename = empty($filename);
        $accepted_mimetype = ($ADA_MIME_TYPE[$file_type]['permission'] == ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE);
        // if php detected mimetype is not accepted, try with browser declared mimetype
        if ($accepted_mimetype == false) {
            $accepted_mimetype = array_key_exists($up_file_type, $ADA_MIME_TYPE) && ($ADA_MIME_TYPE[$up_file_type]['permission'] == ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE);
            if ($accepted_mimetype != false) {
                $file_type = $up_file_type;
            }
        }
        $accepted_filesize = ($file_size < ADA_FILE_UPLOAD_MAX_FILESIZE);

        if (
            !$empty_filename && !$file_upload_error && $file_type !== false
             && $accepted_mimetype && $accepted_filesize
        ) {
            /*
             * qui spostamento del file
             */
            // vito, 19 mar 2009, clean filename here.
            $filename = strtr($filename, [' ' => '_', '\'' => '_']);


            //echo 'tutto ok';
            if ($id_profile == AMA_TYPE_AUTHOR) {
                $filename_prefix = '';
            } else {
                /*
                 * vito, 30 mar 2009:
                 * in case this file has been uploaded by a tutor or by a student,
                 * build the prefix for the uploaded filename adding the ADA type
                 * of the uploaded file.
                 */
                $uploaded_file_type = $ADA_MIME_TYPE[$file_type]['type'];

                $filename_prefix = $course_instance_id . '_' . $user_id . '_' . $node_id . '_' . $uploaded_file_type . '_';
            }
            $destination = $upload_path . DIRECTORY_SEPARATOR . $filename_prefix . $filename;

            /*
             * se esiste gia' un file con lo stesso nome di quello che stiamo
             * caricando, rinominiamo il nuovo file.
             * es. pippo.txt -> ggmmaa_hhmmss_pippo.txt
             */
            if (is_file($destination) && isset($_POST['overwrite']) && $_POST['overwrite'] == false) {
                $date = date('dmy_His');
                $filename  = $date . '_' . $filename;
                $destination = $upload_path . DIRECTORY_SEPARATOR . $filename_prefix . $filename;
            }

            /*
             * codice esistente:
             */
            $file_move = uploadFile($_FILES, $source, $destination);

            if ($file_move[0] == "no") {
                // restituisco l'errore di problemi in uploadFile
                $error_code = ADA_FILE_UPLOAD_ERROR_UPLOAD;
            }
            /*
             * fine codice esistente:
             */
            /*
             * Se il file e' stato uploadato correttamente , inserisco il file come risorsa collegata all'autore
             * nella tabella risorse_nodi
             */
            $ada_filetype = $ADA_MIME_TYPE[$file_type]['type'] ?? null;
            $res_ha = [
                'nome_file' => $filename_prefix . $filename,
                'tipo'      => $ada_filetype, //array associativo definito in ada_config.php
                'copyright' => 0,
                'id_utente' => $user_id];

            $result = $dh->addOnlyInRisorsaEsterna($res_ha);
            if (AMADataHandler::isError($result)) {
                return $result;
            }
        } elseif ($empty_filename) {
            // questo lo posso gestire da javascript, comunque lascio il controllo anche qui
            //echo 'filename non passato';
            echo $filename;
        } elseif ($file_upload_error) {
            // restituisco l'errore verificatosi durante l'upload
            // codice di errore definito da PHP, al momento in [1,8]
            $error_code = $file_upload_error;
        } elseif ($file_type === false) {
            $error_code = ADA_FILE_UPLOAD_ERROR_MIMETYPE;
        } elseif (!$accepted_mimetype) {
            // restituisco l'errore di mimetype non accettato
            $error_code = ADA_FILE_UPLOAD_ERROR_MIMETYPE;
        } elseif (!$accepted_filesize) {
            // restituisco l'errore di dimensione del file non accettata
            $error_code = ADA_FILE_UPLOAD_ERROR_FILESIZE;
        }
    }

    //echo $error_code;
    ?>
<script type="text/javascript">
    var error    = <?php echo $error_code; ?>;
    var filename = '<?php echo ($filename_prefix ?? '') . $filename; ?>';
    var filetype = <?php echo $ada_filetype; ?>;
    window.parent.exitUploadFileState(error, filename, filetype);
</script>
    <?php
    exit();
} elseif ($id_profile == AMA_TYPE_STUDENT || $id_profile == AMA_TYPE_TUTOR || $id_profile == AMA_TYPE_AUTHOR) {
    /*
     * upload di un file da Collabora:invia file
     */
    $id_node = $_SESSION['sess_id_node'];
    $id_course = $_SESSION['sess_id_course'];
    $id_course_instance = $_SESSION['sess_id_course_instance'];


    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        /*
         * dati passati dal form di upload del file
         */
        $course_id          = $_POST['id_course'];
        $course_instance_id = $_POST['id_course_instance'];
        $user_id            = $_POST['sender'];
        $node_id            = $_POST['id_node'];
        /*
         * dati relativi al file uploadato
         */
        $filename          = $_FILES['file_up']['name'];
        $source            = $_FILES['file_up']['tmp_name'];
        $file_size         = $_FILES['file_up']['size'];
        $file_type         = mime_content_type($source);
        $up_file_type      = $_FILES['file_up']['type'];
        $file_upload_error = $_FILES['file_up']['error'];
        // contiene il codice di errore da restituire al chiamante
        $error_code = 0;
        $ada_filetype = -1;
        /*
         * codice esistente:
         */

        $course_ha = $dh->getCourse($id_course);
        $course_title = $course_ha['titolo'];
        if (AMADataHandler::isError($course_ha)) {
            $msg = $course_ha->getMessage();
            header("Location: " . $http_root_dir . "/browsing/student.php?status=$msg");
        }

        // look for the author, starting from author's id
        $author_id = $course_ha['id_autore'];
        //il percorso in cui caricare deve essere dato dal media path del corso, e se non presente da quello di default
        if ($course_ha['media_path'] != "") {
            $media_path = $course_ha['media_path']  ;
        } else {
            $media_path = MEDIA_PATH_DEFAULT . $author_id ;
        }
        /*
         * fine codice esistente.
         */

        /*
         * controllo che la cartella indicata da $media_path esista e sia scrivibile
         */
        $upload_path = $root_dir . $media_path;
        if (!is_dir($upload_path) || !is_writable($upload_path)) {
            // restituire un messaggio di errore e saltare la parte di scrittura del file
            $error_code = ADA_FILE_UPLOAD_ERROR_UPLOAD_PATH;
            Utilities::redirect(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) . '?err_code=' . $error_code);
        } else {
            // cartella di upload presente e scrivibile
            /*
             * controllo che sia stato inviato un file e che non si siano verificati errori
             * durante l'upload.
             */
            $empty_filename = empty($filename);
            $accepted_mimetype = isset($ADA_MIME_TYPE[$file_type]) && ($ADA_MIME_TYPE[$file_type]['permission'] == ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE);
            // if php detected mimetype is not accepted, try with browser declared mimetype
            if ($accepted_mimetype == false) {
                $accepted_mimetype = array_key_exists($up_file_type, $ADA_MIME_TYPE) && ($ADA_MIME_TYPE[$up_file_type]['permission'] == ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE);
                if ($accepted_mimetype != false) {
                    $file_type = $up_file_type;
                }
            }
            $accepted_filesize = ($file_size < ADA_FILE_UPLOAD_MAX_FILESIZE);

            if (
                !$empty_filename && !$file_upload_error &&
                $accepted_mimetype && $accepted_filesize
            ) {
                /*
                 * qui spostamento del file
                 */
                // vito, 19 mar 2009, clean filename here.
                $filename = strtr($filename, [' ' => '_', '\'' => '_']);

                //echo 'tutto ok';
                if ($id_profile == AMA_TYPE_AUTHOR) {
                    $filename_prefix = '';
                } else {
                    /*
                     * vito, 30 mar 2009:
                     * in case this file has been uploaded by a tutor or by a student,
                     * build the prefix for the uploaded filename adding the ADA type
                     * of the uploaded file.
                     */
                    $uploaded_file_type = $ADA_MIME_TYPE[$file_type]['type'];

                    $filename_prefix = $course_instance_id . '_' . $user_id . '_' . $node_id . '_' . $uploaded_file_type . '_';
                }
                $destination = $upload_path . DIRECTORY_SEPARATOR . $filename_prefix . $filename;

                /*
                 * se esiste gia' un file con lo stesso nome di quello che stiamo
                 * caricando, rinominiamo il nuovo file.
                 * es. pippo.txt -> ggmmaa_hhmmss_pippo.txt
                 */
                if (is_file($destination)) {
                    $date = date('dmy_His');
                    $filename  = $date . '_' . $filename;
                    $destination = $upload_path . DIRECTORY_SEPARATOR . $filename_prefix . $filename;
                }

                /*
                 * codice esistente:
                 */
                $file_move = uploadFile($_FILES, $source, $destination);

                if ($file_move[0] == "no") {
                    // restituisco l'errore di problemi in uploadFile
                    $error_code = ADA_FILE_UPLOAD_ERROR_UPLOAD;
                }
                /*
                 * fine codice esistente:
                 */
                if ($error_code != 0) {
                    // gestire stampa del messaggio di errore
                    Utilities::redirect(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) . '?err_code=' . $error_code);
                } else {
                    // redirige l'utente alla pagina da cui è arrivato all'upload.
                    $navigation_history = $_SESSION['sess_navigation_history'];
                    $last_visited_node  = $navigation_history->lastModule();
                    /**
                     * Must ask user what she wants to do.
                     * This is done with a modal dialog, jQuery is needed
                     */

                    if (ModuleLoaderHelper::isLoaded('COLLABORAACL') && array_key_exists('grantedUsers', $_POST) && count($_POST['grantedUsers'])) {
                        $saveData = [
                        'courseId' => intval($_POST['id_course']),
                        'instanceId' => intval($_POST['id_course_instance']),
                        'fileAclId' => 0, // new fileACL
                        'ownerId' => $userObj->getId(),
                        'nodeId' => trim($_POST['id_node']),
                        'filename' => basename($destination),
                        'grantedUsers' => array_map('intval', $_POST['grantedUsers']),
                        ];
                        $GLOBALS['dh'] = AMACollaboraACLDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
                        $res = $GLOBALS['dh']->saveGrantedUsers($saveData);

                        if (AMADB::isError($res) || $res instanceof CollaboraACLException) {
                            // handle ACL error here
                        } else {
                            // handle ACL saved OK here
                        }
                    }

                    $_SESSION['uploadOk'] = true;
                    Utilities::redirect(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']));
                }
            } else {
                $error_code = ADA_FILE_UPLOAD_ERROR_UPLOAD;
                if (!$accepted_filesize) {
                    $error_code = ADA_FILE_UPLOAD_ERROR_FILESIZE;
                } elseif (!$accepted_mimetype) {
                    $error_code = ADA_FILE_UPLOAD_ERROR_MIMETYPE;
                }
                Utilities::redirect(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']) . '?err_code=' . $error_code);
            }
        }
    } else {
        $error_message = translateFN('Upload del file non riuscito.');
        $get_errorcode = isset($_GET['err_code']) ? intval($_GET['err_code']) : -1;
        switch ($get_errorcode) {
            case -1:
                $error_message = null;
                break;
            case ADA_FILE_UPLOAD_ERROR_UPLOAD:
                $error_message .= '';
                break;
            case ADA_FILE_UPLOAD_ERROR_UPLOAD_PATH:
                $error_message .= ' ' . translateFN('Il percorso di destinazione non è scrivibile.');
                break;
            case ADA_FILE_UPLOAD_ERROR_FILESIZE:
                $error_message .= ' ' . translateFN('La dimensione del file supera quella massima consentita.');
                break;
            case ADA_FILE_UPLOAD_ERROR_MIMETYPE:
                $error_message .= ' ' . translateFN('Il tipo di file inviato non &egrave; tra quelli accettati dalla piattaforma.');
                break;
        }
        $form = UserModuleHtmlLib::uploadForm('upload.php', $sess_id_user, $id_course, $id_course_instance, $id_node, $error_message);
        $form = $form->getHtml();

        $layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
        ];

        $layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        ];

        if (array_key_exists('uploadOk', $_SESSION) && $_SESSION['uploadOk'] === true) {
            unset($_SESSION['uploadOk']);
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache"); // HTTP/1.0
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

            $askOptions['title'] = translateFN('File caricato con successo');
            $askOptions['message']  = translateFN('Cosa vuoi fare ora?');
            $askOptions['buttons'][] = [
            'label' => translateFN('Torna al Corso'),
            'action' => HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $id_node,
            'icon' => 'ui-icon-arrowrefresh-1-w',
            ];
            $askOptions['buttons'][] = [
            'label' => translateFN('Carica un altro file'),
            'action' => $_SERVER['PHP_SELF'],
            'icon' => 'ui-icon-circle-arrow-n',
            ];
            $askOptions['buttons'][] = [
            'label' => translateFN('Vai all\'elenco dei file'),
            'action' => HTTP_ROOT_DIR . '/browsing/download.php',
            'icon' => 'ui-icon-folder-open',
            ];

            $optionsAr['onload_func']  = "askActionToUser('" . rawurlencode(json_encode($askOptions)) . "');";
        }

        if (ModuleLoaderHelper::isLoaded('COLLABORAACL')) {
            $layout_dataAr['CSS_filename'][] = MODULES_COLLABORAACL_PATH . '/layout/ada-blu/css/moduleADAForm.css';
            array_splice($layout_dataAr['JS_filename'], count($layout_dataAr['JS_filename']) - 1, 0, [ MODULES_COLLABORAACL_PATH . '/js/multiselect.min.js' ]);
            if (!isset($optionsAr)) {
                $optionsAr = [];
            }
            if (!array_key_exists('onload_func', $optionsAr)) {
                $optionsAr['onload_func'] = '';
            }
            $optionsAr['onload_func'] .= 'initDoc();';
        }
    }

    $nodeObj = DBRead::readNodeFromDB($id_node);
    if (!AMADataHandler::isError($nodeObj)) {
        $node_title = $nodeObj->name;
        $node_version = $nodeObj->version;
        $node_date = $nodeObj->creation_date;
        $authorHa = $nodeObj->author;
        $node_author = $authorHa['username'];
        $node_level = $nodeObj->level;
        $node_keywords = ltrim($nodeObj->title);
        $node_path = $nodeObj->findPathFN();
    }


    $content_dataAr = [
    //'head'         => $head_form,
    'form'         => $form ?? '',
    'status'       => $status,
    'user_name'    => $user_name,
    'user_type'    => $user_type,
    'messages'     => $user_messages->getHtml(),
    'agenda'       => $user_agenda->getHtml(),
    'title'        => $node_title,
    'version'      => $node_version,
    'date'         => $node_date,
    'author'       => $node_author,
    'level'        => $node_level,
    'keywords'     => $node_keywords,
    'course_title' => $course_title ?? null,
    'path'         => $node_path,
    //'node_medias'  => $node_medias,
    //'node_links'   => $media_links
    ];

    /* 5.
    HTML page building
    */


    ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr ?? null);
} else {
    /*
     * L'autore e l'amministratore non possono utilizzare il modulo collabora,
     * pertanto li rimandiamo alla pagina da cui provengono.
     */
    $navigation_history = $_SESSION['sess_navigation_history'];
    $last_visited_node  = $navigation_history->lastModule();
    header("Location: $last_visited_node");
    exit();
}

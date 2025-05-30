<?php

/**
 * EDIT NODE.
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Helper\ServiceHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAAuthor;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities as MainUtilities;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent;
use Lynxlab\ADA\Services\NodeEditing\NodeEditing;
use Lynxlab\ADA\Services\NodeEditing\NodeEditingViewer;
use Lynxlab\ADA\Services\NodeEditing\PreferenceSelector;
use Lynxlab\ADA\Services\NodeEditing\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Services\Functions\copyNodeFN;
use function Lynxlab\ADA\Services\Functions\deleteNodeFN;
use function Lynxlab\ADA\Services\Functions\getNodeData;

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
$allowedUsersAr = [AMA_TYPE_AUTHOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_AUTHOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

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
if ($id_profile == 0 || ($id_profile != AMA_TYPE_TUTOR && $id_profile != AMA_TYPE_AUTHOR && $id_profile != AMA_TYPE_STUDENT)) {
    $errObj = new ADAError(null, translateFN('Utente non autorizzato, impossibile proseguire.'));
} elseif (
    $id_profile == AMA_TYPE_STUDENT && isset($id_course_instance) && intval($id_course_instance) > 0 &&
        $userObj->getStudentStatus($userObj->getId(), $id_course_instance) == ADA_STATUS_TERMINATED
) {
    /**
     * @author giorgio 03/apr/2015
     *
     * if user has the terminated status for the course instance, redirect to view
     */
    MainUtilities::redirect(HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $parent_id . '&id_course=' . $id_course .
            '&id_course_instance=' . $id_course_instance);
}


$level = 0; // default
$chat_link = "";

$online_users_listing_mode = 2;
if (!isset($id_course_instance)) {
    $id_course_instance = null;
}
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);

if (!isset($op)) {
    $op = 'edit';
}

$help = translateFN("Da qui l'autore di un nodo o di una nota ne pu&ograve; modificare le propriet&agrave;");

// MAIN: delete,copy,preview,edit

// vito 16 gennaio 2009
$form = null;
//vito, 20 feb 2009
$icon  = '';
$body_onload = "";

switch ($op) {
    case 'delete':
        if (
            isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] == 'POST'
                and isset($id_node) and isset($parent_id)
        ) {
            // vito 16 gennaio 2009
            $result = $dh->removeNode($id_node); //$id_user);  passare anche lo userid perch�se ne tenga traccia ?
            $message = urlencode(translateFN("Nodo eliminato"));
            // vito, 9 mar 2009, $parent_id
            header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$parent_id&msg=$message");
            exit();
        } else {
            $self = "author"; // per il templates
            $action = "edit_node";
            $data = deleteNodeFN($id_node, $id_course, $action);
        }
        break;
    case 'copy':
        if (
            isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] == 'POST'
                and isset($new_id_node)
        ) {
            $nodeObj = DBRead::readNodeFromDB($sess_id_node);
            if (is_object($nodeObj)) {
                $nodeObj->copy($new_id_node);
                $new_nodeObj = DBRead::readNodeFromDB($new_id_node);
                if (is_object($new_nodeObj)) {
                    $message = urlencode(translateFN("Nodo copiato"));
                    header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$new_id_node&msg=$message");
                }
            }
        } else {
            $self = "author"; // per il templates
            $action = "edit_node";
            $status = translateFN("Copia del nodo");
            $data = copyNodeFN($id_node, $id_course, $action);
        }
        break;

        /*
         * vito, 17 nov 2008: promote noTe to noDe.
         * A Tutor now suggests the promotion af a noTe to the author of the course, which
         * will eventually promote the noTe.
        */
    case 'suggest_publishing':
        /*
         * Get the page we're coming from
        */
        //$navigation_history = $_SESSION['sess_navigation_history'];
        //$last_page = $navigation_history->previousItem();

        /*
         * Only the tutor is allowed to suggest note promotion.
        */
        if ($id_profile != AMA_TYPE_TUTOR) {
            header("Location: $http_root_dir/browsing/view.php");
            exit();
        }
        /*
     * Obtain info about the course author
        */
        $course_data = $dh->getCourse($id_course);
        if (AMADataHandler::isError($course_data)) {
            $errObj = new ADAError($course_data, translateFN("Errore nell'ottenimento delle informazioni sul corso."));
        }
        $course_author_id = $course_data['id_autore'];

        $author_data = $dh->getAuthor($course_author_id);
        if (AMADataHandler::isError($author_data)) {
            $errObj = new ADAError($author_data, translateFN("Errore nell'ottenimento delle informazioni sull'autore del corso."));
        }

        /*
     * Obtain note data
        */
        $note_data = $dh->getNodeInfo($id_node);
        if (AMADataHandler::isError($note_data)) {
            $errObj = new ADAError($note_data, translateFN("Errore nell'ottenimento dei dati relativi alla nota da promuovere"));
        }
        $note_title = $note_data['name'];
        /*
     * Prepare the text of the message
        */
        $message_text  = sprintf(translateFN("Il tutor %s segnala la seguente nota per la promozione a nodo del corso."), $user_name);
        $note_url = $http_root_dir . '/browsing/view.php?id_course=' . $id_course . '&id_course_instance=' . $id_course_instance . '&id_node=' . $id_node;

        $link_to_note = CDOMElement::create('a', "href:$note_url");
        $link_to_note->addChild(new CText($note_title));

        $message_text .= $link_to_note->getHtml();

        $message_handler = MessageHandler::instance();

        $message_ha['destinatari'] = "{$author_data['username']}, $user_name";
        $message_ha['data_ora']    = "now";
        $message_ha['tipo']        = ADA_MSG_SIMPLE;
        $message_ha['mittente']    = $user_name;
        $message_ha['testo']       = $message_text;
        $message_ha['titolo']      = translateFN("Promozione di una nota a nodo");
        $message_ha['priorita']    = 2;

        $result = $message_handler->sendMessage($message_ha);
        if (AMADataHandler::isError($result)) {
            $errObj = new ADAError($result, translateFN("Errore nell'invio del messaggio di suggerimento promozione nota."));
        }
        $status = translateFN("Proposta di promozione inviata all'autore del corso");
        header("Location: $http_root_dir/browsing/view.php?status=$status");
        exit();

        break;

    case 'publish': // promote a noTe to noDe (only Tutors) or a private note to a public note (student/tutor)
        // if (isset($submit)){

        $nodeObj = DBRead::readNodeFromDB($id_node);

        if (is_object($nodeObj) and (!AMADatahandler::isError($nodeObject))) {
            $node_type = $nodeObj->type;
            $node_name = $nodeObj->name;
            $node_ha = $nodeObj->object2arrayFN();

            switch ($type) {
                case ADA_PRIVATE_NOTE_TYPE: //  private notes  to forum notes
                    $node_ha['type'] = ADA_NOTE_TYPE;
                    $res = $dh->doEditNode($node_ha);
                    $message = urlencode(translateFN("Nota pubblicata nel forum"));
                    header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$id_node&msg=$message");
                    exit();
                    break;

                case ADA_NOTE_TYPE: // forum notes to nodes
                    $id_toc =  $sess_id_course . "_" . $courseObj->id_nodo_toc;
                    $parent_node_id = $node_ha['parent_id'];
                    $parent_node_type =  $node_ha['type'];
                    if (($parent_node_type == ADA_NOTE_TYPE) or ($parent_node_type == ADA_PRIVATE_NOTE_TYPE)) {   // cannot attach a noDe to a noTe  !
                        $pathAr =  $nodeObj->findLogicalPathFN();
                        while (
                            ($parent_node_type == ADA_NOTE_TYPE) &&
                                ($id_toc != $parent_node_id)
                        ) {
                            $path_element = array_shift($pathAr);
                            $parent_node_id = $path_element[0];
                            $nodeObjTmp = DBRead::readNodeFromDB($parent_node_id);
                            $parent_node_type = $nodeObjTmp->type;
                        }
                    }
                    if ($id_toc == $parent_node_id) {
                        $message = urlencode(translateFN("Non &egrave; possibile pubblicare questa nota."));
                        // header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$id_node&msg=$message");
                    }
                    $node_ha['parent_id'] = $parent_node_id;
                    $node_ha['type'] = ADA_LEAF_TYPE;
                    $node_ha['id_instance'] = "";

                    $res = $dh->doEditNode($node_ha);
                    //$GLOBALS['debug']=1; Utilities::mydebug(__LINE__,__FILE__,$res); $GLOBALS['debug']=0;
                    if (!AMADatahandler::isError($res)) {
                        $message = urlencode(translateFN("Nota pubblicata nel corso"));
                        header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$id_node&msg=$message");
                    } else {
                        $authoObj = new ADAAuthor($course_author_id);
                        $author_name = $authoObj->username;
                        $destAr =  [$user_name];
                        /*$tutor_id = $dh->courseInstanceTutorGet($sess_id_course_instance);
                         $tutor = $dh->getAuthor($tutor_id);
                         $tutor_uname = $tutor['username'];*/
                        $mh = new MessageHandler();
                        $message_ha['destinatari'] = $destAr;
                        $message_ha['priorita'] = 1;
                        $message_ha['data_ora'] = "now";
                        $message_ha['titolo'] = translateFN("Nodo pubblicato nel corso");
                        $message_ha['testo'] = translateFN("Il tutor della classe ha ritenuto di intereesse per tutti la nota");
                        $message_ha['testo'] .= "<a href=\"$http_root_dir/browsing/view.php?id_node=$id_node\">$node_name</a>";
                        $message_ha['testo'] .= translateFN(" e ha provveduto a pubblicarla nel tuo corso.");
                        $message_ha['testo'] .= $course_title;
                        $message_ha['data_ora'] = "now";
                        $message_ha['mittente'] = $author_name;
                        // e-mail
                        // vito, 20 apr 2009
                        //                               $message_ha['tipo'] = ADA_MSG_MAIL;
                        //                               $res = $mh->sendMessage($message_ha);
                        // messaggio interno
                        $message_ha['tipo'] = ADA_MSG_SIMPLE;
                        $res = $mh->sendMessage($message_ha);
                    }
                    break;
            }
        }
        //} else {
        //$self="author"; // per il templates
        //$action = "edit_node";
        //$status = translateFN("Pubblicazione del nodo");
        //$data = copyNodeFN($id_node,$id_course,$action);

        //}
        break;

    case 'preview':
        /*
               * Mostra l'anteprima del contenuto del nodo
        */
        //$self="edit_node"; // per il template
        $status = translateFN("Preview del nodo");
        //  $data = NodeEditingViewer::getPreviewForm('edit_node.php?op=edit','edit_node.php?op=save');
        $form = NodeEditingViewer::getPreviewForm('edit_node.php?op=edit', 'edit_node.php?op=save');

        /* vito, 20 feb 2009
           * usa i dati presenti nella sessione per mostrare l'anteprima del nodo
           * che si sta editando. E' il metodo NodeEditingViewer::getPreviewForm
           * che si occupa di passare i dati in $_POST nella sessione, pertanto è
           * necessario che questo sia invocato prima.
        */
        $content_dataAr = unserialize($_SESSION['sess_node_editing']['node_data']);
        $icon  = CourseViewer::getClassNameForNodeType($content_dataAr['type']);
        if ($status == '') {
            $status = translateFN('Visualizzazione anteprima del nodo');
        }

        // vito, 20 apr 2009
        /*
          * Choose the right template for the preview
        */
        switch ($content_dataAr['type']) {
            case ADA_NOTE_TYPE:
                $self = 'previewnote';
                break;
            case ADA_PRIVATE_NOTE_TYPE:
                $self = 'previewprivatenote';
                break;
            case ADA_GROUP_TYPE:
            case ADA_LEAF_TYPE:
            default:
                $self = 'preview';
                break;
        }
        $preview_additional_data = [
                'title'      => $content_dataAr['name'],
                'version'    => $content_dataAr['version'],
                'author'     => $user_name,
                'node_level' => $content_dataAr['level'],
                'keywords'   => $content_dataAr['title'],
                'date'       => $content_dataAr['creation_date'],
                'edit_link'  => 'edit_node.php?op=edit',
                'save_link' =>  'edit_node.php?op=save',
        ];

        if (!isset($layout_dataAr['CSS_filename'])) {
            $layout_dataAr['CSS_filename'] = [];
        }
        if (!isset($layout_dataAr['JS_filename'])) {
            $layout_dataAr['JS_filename'] = [];
        }

        $layout_dataAr['CSS_filename'][] = JQUERY_UI_CSS;
        $layout_dataAr['CSS_filename'][] = JQUERY_NIVOSLIDER_CSS;
        $layout_dataAr['CSS_filename'][] = ROOT_DIR . '/js/include/jquery/nivo-slider/themes/default/default.css';
        $layout_dataAr['CSS_filename'][] = JQUERY_JPLAYER_CSS;

        $layout_dataAr['JS_filename'] = array_merge($layout_dataAr['JS_filename'], [
                JQUERY,
                JQUERY_UI,
                JQUERY_NIVOSLIDER,
                JQUERY_JPLAYER,
                JQUERY_NO_CONFLICT,
                ROOT_DIR . '/js/browsing/view.js',
        ]);

        $body_onload = "initDoc();";
        /**
         * This should prevent Google Chrome browser xss auditor error
         * see: https://stackoverflow.com/questions/43249998/chrome-err-blocked-by-xss-auditor-details
         */
        header('X-XSS-Protection:0');

        break;

    case 'save':
        /*
                   * Salvataggio delle modifiche apportate al nodo.
                   * Determina i media da associare e disassociare, aggiorna  i media per il nodo
                   * e salva le modifiche fatte al nodo.
        */

        /*
                   * media associati al nodo prima delle modifiche
        */
        $previous_media = [];
        $previous_media = unserialize($_SESSION['sess_node_editing']['media_in_db']);
        /*
               * media trovati nel nodo dopo le modifiche
        */
        $current_media = [];
        $content_dataAr = unserialize($_SESSION['sess_node_editing']['node_data']);
        $current_media = NodeEditing::getMediaFromNodeText($content_dataAr['text']);
        /*
               * determino i media da disassociare e quelli da associare
        */
        if (is_array($previous_media)) {
            foreach ($previous_media as $media => $type) {
                if (isset($current_media[$media])) {
                    unset($previous_media[$media]);
                    unset($current_media[$media]);
                }
            }
        }
        /*
               * se previous_media contiene degli elementi, sono elementi da disassociare dal nodo
               * se current_media  contiene degli elementi, sono elementi da associare al nodo
        */
        $result = NodeEditing::updateMediaAssociationsWithNode(
            $_SESSION['sess_id_node'],
            $_SESSION['sess_id_user'],
            $previous_media,
            $current_media
        );
        if (AMADB::isError($result)) {
            $errObj = new ADAError($result, translateFN("Errore nell'associazione dei media con il nodo"));
        }
        /*
               * salvo le modifiche fatte al nodo
        */
        unset($content_dataAr['DataFCKeditor']);
        $result = NodeEditing::saveNode($content_dataAr);
        if (AMADB::isError($result)) {
            $errObj = new ADAError($result, translateFN('Errore durante il salvataggio delle modifiche al nodo'));
        }

        unset($_SESSION['sess_node_editing']);

        header("Location: $http_root_dir/browsing/view.php?id_node={$content_dataAr['id']}");

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            ADAEventDispatcher::buildEventAndDispatch([
            'eventClass' => NodeEvent::class,
            'eventName' => NodeEvent::POSTEDITREDIRECT,
            ], $content_dataAr);
        }

        exit();
        //    $data['form'] = translateFN("Le modifiche al nodo sono state salvate correttamente.");
        //    $self="edit_node";
        break;

    case 'edit':
    default:
        $self = "edit_node"; // per il template
        $action = "edit_node";

        $body_onload = "switchToFCKeditor('$template_family');";
        /*
               * Mostra la pagina per l'editing del nodo.
        */
        /*
               * Verifica la pagina da cui proviene l'utente.
               * Se l'utente proviene da una pagina diversa da edit_node.php e i dati relativi all'editing
               * del nodo sono presenti in sessione, si tratta di dati non salvati, quindi non dovrebbero
               * essere mostrati. Al momento faccio l'unset della sessione.
               * I dati relativi alla navigazione in ADA sono gestiti da un oggetto di navigazione mantenuto
               * nella variabile di sessione $sess_navigation_history.
        */
        $navigation_history = $_SESSION['sess_navigation_history'];
        /* vito, 24 apr 2009
               * Save the page from which the user select the add node operation
               * so that, if he cancels the editing operation, we can redirect
               * him there.
        */
        if (strcmp($navigation_history->previousItem(), __FILE__) !== 0) {
            if (isset($id_course) && isset($id_node)) {
                $_SESSION['page_to_load_on_cancel_editing'] = HTTP_ROOT_DIR . '/browsing/view.php?id_course=' . $id_course . '&id_node=' . $id_node;
            } else {
                $_SESSION['page_to_load_on_cancel_editing'] = $navigation_history->previousPage();
            }
        }
        $need_to_unset_session = strcmp($navigation_history->previousItem(), __FILE__);
        if (!isset($_SESSION['sess_node_editing']['node_data'])  || $need_to_unset_session !== 0) {
            if ($need_to_unset_session !== 0) {
                unset($_SESSION['sess_node_editing']);
            }

            $media_found = [];
            if (!isset($id_node)) {
                $id_node = null;
            }
            $node_to_edit = getNodeData($id_node);
            $media_found = NodeEditing::getMediaFromNodeText($node_to_edit['text']);
            $_SESSION['sess_node_editing']['media_in_db'] = serialize($media_found);
        } else {
            $node_to_edit = unserialize($_SESSION['sess_node_editing']['node_data']);
            unset($_SESSION['sess_node_editing']['node_data']);
        }
        /*
               * Ottiene le preferenze di visualizzazione per l'editor
        */
        $flags  = PreferenceSelector::getPreferences($id_profile, $node_to_edit['type'], EDIT_OPERATION, $ADA_ELEMENT_VIEWING_PREFERENCES);
        /*
               * Mostra l'editor
        */
        //    $data   = NodeEditingViewer::getEditingForm($action, $id_course, $sess_id_course_instance, $sess_id_user, $node_to_edit, $flags);
        if (!isset($id_course)) {
            $id_course = null;
        }
        $form   = NodeEditingViewer::getEditingForm($action, $id_course, $sess_id_course_instance, $sess_id_user, $node_to_edit, $flags);
        $status = translateFN("Modifica del nodo");
        /* vito, 20 feb 2009
               * usa i dati presenti nella sessione per mostrare alcune informazioni relative al nodo
               * che si sta editando
        */
        $icon  = CourseViewer::getClassNameForNodeType($node_to_edit['type']);
        $title = Utilities::getEditingFormTitleForNodeType($node_to_edit['type']);
        if ($status == '') {
            $status = $title;
        }
        $version = $node_to_edit['version'];
        $author = $user_name;
        $node_level = $node_to_edit['level'];
        $keywords = $node_to_edit['title'];
        $creation_date = $node_to_edit['creation_date'];
        $edit_link = '';
        $save_link = '';
        $content_dataAr_and_buttons_CSS_class = 'hide_node_data';
        // vito, 20 apr 2009
        $preview_additional_data = [
                'title'      => $title,
                'version'    => $node_to_edit['version'],
                'author'     => $user_name,
                'node_level' => $node_to_edit['level'],
                'keywords'   => $node_to_edit['title'],
                'date'       => $node_to_edit['creation_date'],
        ];
}

if (isset($data) && is_object($data)) {
    $msg = urlencode($data->message);
    header("Location: " . $http_root_dir . "/browsing/view.php?id_node=$id_node&msg=$msg");
}
// vito, 20 apr 2009, commentate le righe seguenti
/*
 $course_dataHa = $dh->getCourse($id_course);
 if ((is_array($course_dataHa) && count($course_dataHa)>0)){
 $course_title = $course_dataHa['titolo'];
 }
*/
$chat_link = "<a href=\"$http_root_dir/comunica/ada_chat.php target=\"Chat\">" . translateFN("chat") . "</a>";

// vito, 20 apr 2009, commentate le righe seguenti
/*
 // find all course available
 $field_list_ar = array('nome','titolo','data_pubblicazione');
 $clause = "ID_UTENTE_AUTORE = '$sess_id_user'";   // matching conditions: ...
 $courses_dataHa = $dh->findCoursesList($field_list_ar, $clause);
 if (AMADataHandler::isError($courses_dataHa)){
 $msg = $courses_dataHa->getMessage();

 }
*/
$title = translateFN('ADA - Modifica Nodo');
// vito 16 gennaio 2009
if ($form == null) {
    if (isset($data['form'])) {
        $html_form = $data['form'];
    }
} else {
    $html_form = $form->getHtml();
}

/*
 * vito, 24 apr 2009
 * build the link for the Cancel operation, that when confirmed, redirects the user
 * to the page where he clicked Add Node.
*/
$link   = $_SESSION['page_to_load_on_cancel_editing'];
$text   = addslashes(translateFN('Vuoi annullare le modifiche apportate al nodo?'));
$cancel = "confirmCriticalOperationBeforeRedirect('$text','$link')";

$content_dataAr = [
        'status'     => $status,
        'user_name'  => $user_name,
        'user_type'  => $user_type,
        'level'      => $user_level,
        'path'       => $node_path,
        'chat_link'  => $chat_link,
        'help'       => $help,
        'messages'   => $user_messages->getHtml(),
        'agenda'     => $user_agenda->getHtml(),
        'chat_users' => $online_users,
        'menu'       => $data['menu'] ?? '',
        'head'       => $data['head_form'] ?? '',
        'form'       => $html_form,
        'icon'       => $icon,
        'cancel'     => $cancel,
];

if (is_array($preview_additional_data)) {
    $content_dataAr = array_merge($content_dataAr, $preview_additional_data);
}

/*
 * vito, 1 ottobre 2008: passiamo il parametro onload_func=switchToFCKeditor() per
 * mostrare l'editor. Questo risolve i problemi che si avevano con IE e event.observe di prototype
*/
$options = ['onload_func' => $body_onload];

ARE::render($layout_dataAr, $content_dataAr, null, $options);

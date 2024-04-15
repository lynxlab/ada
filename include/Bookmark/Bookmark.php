<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Bookmark\Bookmark;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class Bookmark was declared with namespace Lynxlab\ADA\Main\Bookmark. //

//
// +----------------------------------------------------------------------+
// | ADA version 1.8                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2001-2007 Lynx                                         |
// +----------------------------------------------------------------------+
// |                                                                      |
// |                                 BOOKMARK     C L A S S               |
// |                                                                      |
// |                                                                      |
// |                                                                      |
// |                                                                      |
// |                                                                      |
// +----------------------------------------------------------------------+
// | Author: Stefano Penge <steve@lynxlab.com>                            |
// |                                                                      |
// +----------------------------------------------------------------------+
//
//

namespace Lynxlab\ADA\Main\Bookmark;

use Lynxlab\ADA\CORE\HtmlElements\Form;
use Lynxlab\ADA\CORE\HtmlElements\IList;
use Lynxlab\ADA\CORE\HtmlElements\Table;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;

/**************************
/   bookmark management
/**************************/

class Bookmark
{
    public $bookmark_id;
    public $descrizione;
    public $data;
    public $ora;
    public $node_id;
    public $corso;
    public $titolo;
    public $error_msg;
    public $full;
    public $utente;

    public function __construct($id_bk = "")
    {
        // finds out information about a bookmark
        $dh = $GLOBALS['dh'];
        if (!empty($id_bk)) {
            $dataHa = $dh->getBookmarkInfo($id_bk);
            if (AMADataHandler::isError($dataHa)) {
                $this->error_msg = $dataHa->getMessage();
                $this->full = 0;
            } else {
                $this->bookmark_id = $id_bk;
                $this->node_id =  $dataHa['node_id'];
                $course_instance_id = $dataHa['course_id'];
                $course_instanceHa = $dh->courseInstanceGet($course_instance_id);
                $course_id = $course_instanceHa['id_corso'];
                $courseHa = $dh->getCourse($course_id);
                $this->corso = $courseHa['titolo'];
                $node = $dh->getNodeInfo($this->node_id);
                $node_title = $node['name'];
                $this->titolo =  $node_title;
                $this->data = $dataHa['date'];
                $this->utente = $dataHa['student_id'];
                //$ts = dt2tsFN($dataHa['date']);
                //$this->ora =  ts2tmFN($ts);
                $this->descrizione = $dataHa['description'];
            }
        }
    }

    public static function getBookmarks($id_user, $id_tutor = "", $id_node = '')
    {
        // ritorna la lista di bookmark dell'utente ed eventualmente (se passato) per il nodo

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $debug = $GLOBALS['debug'] ?? null;

        $out_fields_ar = ['id_nodo', 'data', 'descrizione', 'id_utente_studente'];
        $data_studentHa = $dh->findBookmarksList($out_fields_ar, $id_user, $sess_id_course_instance, $id_node);
        if (AMADataHandler::isError($data_studentHa)) {
            $msg = $data_studentHa->getMessage();
            return $msg;
            // header("Location: $error?err_msg=$msg");
        }
        if (!empty($id_tutor)) {
            $data_tutorHa = $dh->findBookmarksList($out_fields_ar, $id_tutor, $sess_id_course_instance, $id_node);
            $dataHa = array_merge($data_studentHa, $data_tutorHa);
        } else {
            $dataHa = $data_studentHa;
        }
        return  $dataHa;
    }

    public function getNodeBookmarks($id_node)
    {
        // ritorna la lista di bookmarks  per questo nodo per tutti gli utenti

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $debug = $GLOBALS['debug'];

        $out_fields_ar = ['id_nodo', 'data', 'descrizione', 'id_utente_studente'];
        $dataHa = $dh->findBookmarksList($out_fields_ar, 0, $sess_id_course_instance, $id_node);
        if (AMADataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            return $msg;
            // header("Location: $error?err_msg=$msg");
        }
        return  $dataHa;
    }

    public static function isNodeBookmarkedFN($id_user, $id_node)
    {
        // cerca un nodo nella lista di bookmark dell'utente
        $dataHa = Bookmark::getBookmarks($id_user, $id_tutor = "", $id_node);

        /* foreach ($dataHa as $bkm){
           $id_bk = $bkm[0];
           $id_bk_node = $bkm[1];
           if ($id_bk_node == $id_node)
               return $id_bk;
        }
        */
        if (is_array($dataHa) && isset($dataHa[0][0])) {
            return  $dataHa[0][0];
        } else {
            return false;
        }
    }

    public function getBookmarkInfo($id_bk = "")
    {
        if ($id_bk != "") {
            //???
        }
        $res_ar[0]['id'] =  $this->bookmark_id;
        $res_ar[0]['id_nodo'] =  $this->node_id;
        $res_ar[0]['data']  =  $this->data;
        //$res_ar[0]['ora']  =   $this->ora;
        $res_ar[0]['corso'] = $this->corso;
        $res_ar[0]['titolo'] = $this->titolo;
        $res_ar[0]['descrizione'] = $this->descrizione;
        $res_ar[0]['utente'] = $this->utente;

        return $res_ar;
    }

    public function setBookmark($id_user, $id_node, $node_title, $node_description = "")
    {
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $debug = $GLOBALS['debug'];

        $date = ""; //init date
        $dataHa = $dh->addBookmark($id_node, $id_user, $sess_id_course_instance, $date, $node_title);
        if (AMADataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            // VA gestito l'errore !
            return $msg;
            // header("Location: $error?err_msg=$msg");
        } else {
            $this->bookmark_id = $dataHa;
            return "";
        }
    }

    public function updateBookmark($id_user, $id_bk, $node_description)
    {
        // aggiorna il bookmark
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $sess_id_user = $GLOBALS['sess_id_user'];
        $debug = $GLOBALS['debug'];

        if (!isset($id_user)) {
            $id_user = $sess_id_user;
        }

        if (!isset($id_bk)) {
            $id_bk = $this->bookmark_id;
        }

        // verifica
        $res_ha = $dh->getBookmarkInfo($id_bk);
        if ($res_ha['student_id'] == $id_user) {
            $dataHa = $dh->setBookmarkDescription($id_bk, $node_description);
            if (AMADataHandler::isError($dataHa)) {
                $msg = $dataHa->getMessage();
                return $msg;
                // header("Location: $error?err_msg=$msg");
            } else {
                return $this->getBookmarkInfo($id_bk);
            }
        }
    }

    public function removeBookmark($id_user, $id_bk)
    {

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $sess_id_user = $GLOBALS['sess_id_user'];
        $debug = $GLOBALS['debug'];

        $date = ""; //init date
        if (!isset($id_user)) {
            $id_user = $sess_id_user;
        }
        if (!isset($id_bk)) {
            $id_bk = $this->bookmark_id;
        }

        // verifica
        $res_ha = $dh->getBookmarkInfo($id_bk);
        if ($res_ha['student_id'] == $id_user) {
            $dataHa = $dh->removeBookmark($id_bk);
            if (AMADataHandler::isError($dataHa)) {
                $msg = $dataHa->getMessage();
                return "<strong>$msg</strong><br />";
                // header("Location: $error?err_msg=$msg");
            }
        }
    }

    public function formatBookmarks($dataAr)
    {
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $reg_enabled = $GLOBALS['reg_enabled'];
        $id_profile = $GLOBALS['id_profile'];
        if (!is_array($dataAr) || (!count($dataAr))) {
            $res = translateFN("Nessun segnalibro");
            // header("Location: $error?err_msg=$msg");
        } else {
            $formatted_dataHa = [];
            $k = -1;
            foreach ($dataAr as $bookmark) {
                $id_bk = $bookmark[0];
                $id_node = $bookmark[1];
                $date =   $bookmark[2];
                $user =   $bookmark[4];
                $node = $dh->getNodeInfo($id_node);
                $title = $node['name'];
                $description =   $bookmark[3];

                $k++;
                $formatted_dataHa[$k]['data'] =  ts2dFN($date);
                if (is_array($dh->getTutor($user))) { // bookmarks del tutor
                    $formatted_dataHa[$k]['id_nodo'] = "<a href=\"view.php?id_node=$id_node\"><img src=\"img/check.png\" border=0> $title</a> (" . translateFN("Tutor") . ")";
                    if ($id_profile == AMA_TYPE_TUTOR) {
                        $formatted_dataHa[$k]['del'] =  "<a href=\"bookmarks.php?op=delete&id_bk=$id_bk\">
                                                        <img src=\"img/delete.png\" name=\"del_icon\" border=\"0\"
                                                        alt=\"" . translateFN("Elimina") . "\"></a>";
                        $formatted_dataHa[$k]['edit'] =  "<a href=\"bookmarks.php?op=edit&id_bk=$id_bk\">
                                                        <img src=\"img/edit.png\" name=\"edit_icon\" border=\"0\"
                                                        alt=\"" . translateFN("Edit") . "\"></a>";
                    } else {
                        $formatted_dataHa[$k]['del'] = "-";
                        $formatted_dataHa[$k]['edit'] = "-";
                    }
                } else {
                    $formatted_dataHa[$k]['nodo'] = "<a href=\"view.php?id_node=$id_node\"><img src=\"img/check.png\" border=0> $title</a>";
                    if ($reg_enabled) {
                        $formatted_dataHa[$k]['del'] =  "<a href=\"bookmarks.php?op=delete&id_bk=$id_bk\">
                                                        <img src=\"img/delete.png\" name=\"del_icon\" border=\"0\"
                                                        alt=\"" . translateFN("Elimina") . "\"></a>";
                        $formatted_dataHa[$k]['edit'] =  "<a href=\"bookmarks.php?op=edit&id_bk=$id_bk\">
                                                        <img src=\"img/edit.png\" name=\"edit_icon\" border=\"0\"
                                                        alt=\"" . translateFN("Edit") . "\"></a>";
                    } else {
                        $formatted_dataHa[$k]['del'] = "-";
                        $formatted_dataHa[$k]['edit'] = "-";
                    }
                }
                $formatted_dataHa[$k]['zoom'] =  "<a href=\"bookmarks.php?op=zoom&id_bk=$id_bk\">
                                                        <img src=\"img/zoom.png\" name=\"zoom_icon\" border=\"0\"
                                                        alt=\"" . translateFN("Zoom") . "\"></a>";
            }

            $t = new Table();
            $t->initTable('', 'default', '2', '1', '100%', '', '', '', '', 1, 0);
            $t->setTable($formatted_dataHa, translateFN("Segnalibri"), '');
            $res = $t->getTable();
        }
        return $res;
    }

    public function exportBookmarks($dataAr, $mode = 'ada')
    {

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $debug = $GLOBALS['debug'];

        if (!is_array($dataAr) || (!count($dataAr))) {
            $res = translateFN("Nessun segnalibro");
            // header("Location: $error?err_msg=$msg");
        } else {
            if ($mode == 'standard') {
                $formatted_data = "<a href=\"bookmarks.php?op=export&mode=ada\" >" . translateFN("Formato ADA") . "</a> | ";
                $formatted_data .= translateFN("Formato Standard") . "<p>";
            } else {
                $formatted_data = translateFN("Formato ADA") . " | ";
                $formatted_data .= "<a href=\"bookmarks.php?op=export&mode=standard\" >" . translateFN("Formato Standard") . "</a><p>";
            }
            $formatted_data .= "<form><textarea rows=10 cols=80 wrap=virtual>\n";
            $ilist_data = [];
            foreach ($dataAr as $bookmark) {
                $id_bk = $bookmark[0];
                $id_node = $bookmark[1];
                $date =   $bookmark[2];
                $node = $dh->getNodeInfo($id_node);
                $title = $node['name'];
                $description =   $bookmark[3];
                if ($mode == 'standard') {
                    //formato standard
                    //$formatted_data.="<li><a href=\"$http_root_dir/browsing/view.php?id_node=$id_node\" alt=\"$title\"> $title </a></li>\n";
                    $list_item = "<a href=\"$http_root_dir/browsing/view.php?id_node=$id_node\" alt=\"$title\"> $title </a>";
                    $ilist_data[] = $list_item;
                } else {
                    $c_n = explode('_', $id_node);
                    $num_node = $c_n[1];
                    // formato ADA
                    //  $formatted_data.="<li>$title <LINK TYPE=internal VALUE=\"$num_node\"></li>\n";
                    $list_item = "$title <LINK TYPE=internal VALUE=\"$num_node\">";
                    $ilist_data[] = $list_item;
                }
            }

            $lObj = new IList();
            $lObj->initList('1', 'a', 3);
            $lObj->setList($ilist_data);
            $formatted_data .= $lObj->getList();
            $formatted_data .= "</textarea></form>\n</p>\n";
        }
        return $formatted_data;
    }

    public function editBookmark($dataHa)
    {

        $sess_id_user = $GLOBALS['sess_id_user'];
        $id_bk = $dataHa[0]['id'];

        $dataAr = [];
        array_push($dataAr, [translateFN('Corso'), $dataHa[0]['corso']]);
        array_push($dataAr, [translateFN('Nodo'), $dataHa[0]['titolo']]);
        array_push($dataAr, [translateFN('Data'), $dataHa[0]['data']]);
        array_push($dataAr, [translateFN('Id'), $dataHa[0]['id_nodo']]);

        $t = new Table();
        $t->initTable('0', 'center', '0', '0', '100%', 'black', 'white', 'black', 'white', '0', '0');
        $t->setTable($dataAr, $caption = "", $summary = translateFN("Caratteristiche del segnalibro"));
        $t->getTable();

        $formatted_data =  $t->data;


        $data = [
            [
                'label' => '',
                'type' => 'textarea',
                'name' => 'description',
                'value' => $dataHa[0]['descrizione'],
                'rows' => '10',
                'cols' => '80',
                'wrap' => 'virtual',
            ],
            [
                'label' => '',
                'type' => 'submit',
                'name' => 'Submit',
                'value' => translateFN('Salva'),
            ],
            [
                'label' => '',
                'type' => 'hidden',
                'name' => 'id_bk',
                'value' => $id_bk,
            ],
            [
                'label' => '',
                'type' => 'hidden',
                'name' => 'id_user',
                'value' => $sess_id_user,
            ],
            [
                'label' => '',
                'type' => 'hidden',
                'name' => 'op',
                'value' => 'update',
            ],

        ];
        $f = new Form();
        $f->initForm("bookmarks.php", "PUT", "Edit");
        $f->setForm($data);
        $formatted_data .=  $f->getForm();

        return $formatted_data;
    }

    public function addBookmark()
    {


        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course = $GLOBALS['sess_id_course'];
        $sess_id_user = $GLOBALS['sess_id_user'];
        $debug = $GLOBALS['debug'];

        $data = [
            [
                'label' => 'Nodo',
                'type' => 'text',
                'name' => 'id_node',
                'value' => $sess_id_course . "_",
            ],
            [
                'label' => 'Titolo',
                'type' => 'text',
                'name' => 'booomark_title',
                'value' => translateFN('Titolo del bookmark'),

            ],
            [
                'label' => '',
                'type' => 'textarea',
                'name' => 'description',
                'value' => translateFN('Descrizione del nodo'),
                'rows' => '10',
                'cols' => '80',
                'wrap' => 'virtual',
            ],
            [
                'label' => '',
                'type' => 'submit',
                'name' => 'Submit',
                'value' => translateFN('Salva'),
            ],
            [
                'label' => '',
                'type' => 'hidden',
                'name' => 'id_user',
                'value' => $sess_id_user,
            ],
            [
                'label' => '',
                'type' => 'hidden',
                'name' => 'op',
                'value' => 'update',
            ],

        ];
        $f = new Form();
        $f->initForm("bookmarks.php", "POST", "Edit");
        $f->setForm($data);
        $formatted_data =  "<tr><td>" . $f->getForm() . "</td></tr>";

        return $formatted_data;
    }

    public function formatBookmark($dataHa)
    {
        $id_bk = $dataHa[0]['id'];


        $formatted_dataHa = [];
        $formatted_dataHa['corso'][0] = translateFN('Corso');
        $formatted_dataHa['data'][0] = translateFN('Data');
        //$formatted_dataHa['ora'][0] = translateFN('Ora');
        $formatted_dataHa['titolo'][0] = translateFN('Titolo');
        $formatted_dataHa['descrizione'][0] =  translateFN('Descrizione');

        $formatted_dataHa['corso'][1] = $dataHa[0]['corso'];
        $formatted_dataHa['data'][1] = $dataHa[0]['data'];
        //$formatted_dataHa['ora'][1] = $dataHa[0]['ora'];
        $formatted_dataHa['titolo'][1] = $dataHa[0]['titolo'];
        $formatted_dataHa['descrizione'][1] =  $dataHa[0]['descrizione'];

        $t = new Table();
        $t->initTable(0, 'default', '0', '0', '100%', '', '', '', '', 0, 0);
        $t->setTable($formatted_dataHa, translateFN("Dettaglio segnalibro"), '');
        $res = $t->getTable();
        return $res;
    }
}

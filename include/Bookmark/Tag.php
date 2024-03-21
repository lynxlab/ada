<?php

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

use Lynxlab\ADA\CORE\HmtlElements\Form;
use Lynxlab\ADA\CORE\HmtlElements\Table;
use Lynxlab\ADA\Main\Course\Student;

use function Lynxlab\ADA\Main\Utilities\ts2dFN;

class Tag extends Bookmark
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

    public function __construct($id_bk = "")
    {
        // finds out information about a tag
        $dh = $GLOBALS['dh'];
        if (!empty($id_bk)) {
            $dataHa = $dh->get_bookmark_info($id_bk);
            if (AMA_DataHandler::isError($dataHa)) {
                $this->error_msg = $dataHa->getMessage();
                $this->full = 0;
            } else {
                $this->bookmark_id = $id_bk;
                $this->node_id =  $dataHa['node_id'];
                $course_instance_id = $dataHa['course_id'];
                $course_instanceHa = $dh->course_instance_get($course_instance_id);
                $course_id = $course_instanceHa['id_corso'];
                $courseHa = $dh->get_course($course_id);
                $this->corso = $courseHa['titolo'];
                $node = $dh->get_node_info($this->node_id);
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

    public function edit_tag($dataHa)
    {

        $sess_id_user = $GLOBALS['sess_id_user'];
        $id_bk = $dataHa[0]['id'];

        $dataAr = [];
        array_push($dataAr, [translateFN('Corso'), $dataHa[0]['corso']]);
        array_push($dataAr, [translateFN('Nodo'), $dataHa[0]['titolo']]);
        array_push($dataAr, [translateFN('Data'), $dataHa[0]['data']]);
        array_push($dataAr, [translateFN('Id'), $dataHa[0]['id_nodo']]);

        $t = new Table();
        $t->initTable('0', 'center', '0', '0', '100%', '', '', '', '', '0', '0');
        $t->setTable($dataAr, $caption = "", $summary = translateFN("Caratteristiche del tag "));
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
        $f->initForm("tags.php", "POST", "Edit");
        $f->setForm($data);
        $formatted_data .=  $f->getForm();

        return $formatted_data;
    }

    public function add_tag($existing_tagsAr)
    {


        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course = $GLOBALS['sess_id_course'];
        $sess_id_node = $GLOBALS['sess_id_node'];
        $sess_id_user = $GLOBALS['sess_id_user'];
        $debug = $GLOBALS['debug'];
        $existing_tagAr = ['bello', 'interessante', 'confuso', 'dubbio'];


        /*
            array(
                          'label'=>'Tag',
                          'type'=>'text',
                          'name'=>'description',
                          'value'=>translateFN('Descrizione del nodo')
                          ),
            */
        $data = [
            [
                'label' => '',
                //'type'=>'text',
                'type' => 'hidden',
                'name' => 'id_node',
                'value' => $sess_id_node,
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
        // versione con select
        $select_field =  [
            'label' => 'Tag',
            'type' => 'select', //text',
            'name' => 'booomark_title',
            'value' => $existing_tagsAr, //translateFN("Tag")
        ];
        // versione con input
        $input_field =     [
            'label' => 'Tag',
            'type' => 'text',
            'name' => 'booomark_title',
            'value' => translateFN("Tag"),
        ];
        if (!is_array($existing_tagsAr)) {
            array_unshift($data, $input_field);
        } else {
            array_unshift($data, $select_field);
        }

        $f = new Form();
        $f->initForm("tags.php", "POST", "Edit");
        $f->setForm($data);
        $formatted_data =  $f->getForm(); //."</td></tr>";

        return $formatted_data;
    }

    public function get_tagsFN($sess_id_course_instance, $id_bk)
    {
        // ritorna una lista di tag con la descrizione uguale a quella passata
        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        //$sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $sess_id_user = $GLOBALS['sess_id_user'];

        $debug = $GLOBALS['debug'];
        $dataHa = $dh->get_bookmark_info($id_bk);
        $description = $dataHa['description'];
        $out_fields_ar = ['id_nodo', 'data', 'descrizione', 'id_utente_studente'];
        $clause = "descrizione = '$description'";
        $dataHa = $dh->_find_bookmarks_list($out_fields_ar, $clause);
        if (AMA_DataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            return $msg;
            // header("Location: $error?err_msg=$msg");
        }
        return $dataHa;
    }

    public function get_class_tagsFN($sess_id_node, $sess_id_course_instance, $ordering = 's')
    {
        //   ritorna una lista di tag per questo nodo, da tutta la classe

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $sess_id_course_instance = $GLOBALS['sess_id_course_instance'];
        $sess_id_user = $GLOBALS['sess_id_user'];

        $debug = $GLOBALS['debug'];

        $out_fields_ar = ['id_nodo', 'data', 'descrizione', 'id_utente_studente'];
        $dataHa = $dh->find_bookmarks_list($out_fields_ar, '', $sess_id_course_instance, $sess_id_node);
        if (AMA_DataHandler::isError($dataHa)) {
            $msg = $dataHa->getMessage();
            return $msg;
            // header("Location: $error?err_msg=$msg");
        }
        //print_r($dataHa);
        switch ($ordering) {
            case 'd': // date or id
            case 'i':
            default:
                $ordered_tagsHa = $dataHa;
                break;
            case 'a':   // ordering on absolute activity index
                //???
                break;
            case 's': // somiglianza tra activity index degli autori con quello dell'utente
                $student_classObj = new Student($sess_id_course_instance);
                // conviene farsi dare la lista e ordinarla una volta per tutte ?
                $student_listAr =  $student_classObj->student_list;
                foreach ($student_listAr as $student) {
                    $id_student = $student['id_utente_studente'];
                    $student_dataHa =  $student_classObj->find_student_index_att($id_course, $sess_id_course_instance, $id_student);
                    $user_activity_index = $student_dataHa['index_att'];
                    $class_student_activityAr[$id_student] = $user_activity_index;
                    //echo "$id_student : $user_activity_index <br>";
                }
                $user_activity_index = $class_student_activityAr[$sess_id_user];
                //print_r($class_student_activityAr);
                //  asort ($class_student_activityAr,SORT_NUMERIC); // ordinamento su indice attività
                //print ($user_activity_index."<br>");
                $ord_tag_ind = [];
                $tagsdataHa = [];
                foreach ($dataHa as $bk_Ar) {
                    //print_r($bk_Ar);
                    $id_bk = $bk_Ar[0]; //BK id
                    //$node = $bk_Ar[1];
                    //$date = $bk_Ar[2]; //date
                    //$description = $bk_Ar[3]; //description
                    $author_id =  $bk_Ar[4];
                    $author_activity_index = $class_student_activityAr[$author_id];
                    $distance = abs($author_activity_index - $user_activity_index);
                    $ord_tag_ind[$id_bk] = $distance;
                    $tagsdataHa[$id_bk] = $bk_Ar;
                    //echo ("$id_bk : $author_activity_index ; $distance<br>");
                }
                //print_r($ord_tag_ind);
                asort($ord_tag_ind, SORT_NUMERIC); // ordinamento su distanza ia da user
                //print_r($ord_tag_ind);
                $ordered_tagsHa = [];
                foreach ($ord_tag_ind as $id_bk => $distance) {
                    //echo ("$id_bk => $distance ;");
                    $ordered_tagsHa[] = $tagsdataHa[$id_bk];
                }
                //print_r($ordered_tagsHa);
                break;
                //...
        }
        return  $ordered_tagsHa;
    }

    public function format_as_tag($dataHa)
    {
        $id_bk = $dataHa[0]['id'];


        $formatted_dataHa = [];
        $formatted_dataHa['corso'][0] = translateFN('Corso');
        $formatted_dataHa['data'][0] = translateFN('Data');
        //$formatted_dataHa['ora'][0] = translateFN('Ora');
        $formatted_dataHa['titolo'][0] = translateFN('Nodo');
        $formatted_dataHa['descrizione'][0] =  translateFN('Tag');

        $formatted_dataHa['corso'][1] = $dataHa[0]['corso'];
        $formatted_dataHa['data'][1] = $dataHa[0]['data'];
        //$formatted_dataHa['ora'][1] = $dataHa[0]['ora'];
        $formatted_dataHa['titolo'][1] = $dataHa[0]['titolo'];
        $formatted_dataHa['descrizione'][1] =  $dataHa[0]['descrizione'];

        $t = new Table();
        $t->initTable(0, 'default', '0', '0', '100%', '', '', '', '', 0, 0);
        $t->setTable($formatted_dataHa, translateFN("Dettaglio tag"), '');
        $res = $t->getTable();
        return $res;
    }

    public function format_as_tags($dataAr)
    {
        $dh = $GLOBALS['dh'];
        $debug = $GLOBALS['debug'];
        $reg_enabled = $GLOBALS['reg_enabled'];
        $id_profile = $GLOBALS['id_profile'];
        $sess_id_user = $_SESSION['sess_id_user'];
        if (!is_array($dataAr) || (!count($dataAr))) {
            $res = translateFN("Nessuna tag");
            // header("Location: $error?err_msg=$msg");
        } else {
            $formatted_dataHa = [];
            $k = -1;
            foreach ($dataAr as $bookmark) {
                $id_bk = $bookmark[0];
                $id_node = $bookmark[1];
                $date =   $bookmark[2];
                $author_id =   $bookmark[4];
                $node = $dh->get_node_info($id_node);
                $title = $node['name'];
                $icon  = $node['icon'];
                $description =   $bookmark[3];
                $authorHa = $dh->_get_user_info($author_id);
                $author_uname = $authorHa['username'];
                $k++;
                $formatted_dataHa[$k]['autore'] = "<a href=\"tags.php?op=list_by_user&id_auth=$author_id\">$author_uname</a>";
                $formatted_dataHa[$k]['data'] =  ts2dFN($date);
                $formatted_dataHa[$k]['tag'] = "<a href=\"tags.php?op=list_by_tag&id_bk=$id_bk\"><img src=\"img/check.png\" border=0>&nbsp;$description</a>";

                if (is_array($dh->get_tutor($author_id))) { // tag del tutor differenziate ??
                    $formatted_dataHa[$k]['id_nodo'] = "<a href=\"view.php?id_node=$id_node\"><img src=\"img/$icon\" border=0> $title</a> (" . translateFN("Tutor") . ")";
                    //vito 13 gennaio 2009
                    //                if ($id_profile == AMA_TYPE_TUTOR){
                    //                  $formatted_dataHa[$k]['del'] =  "<a href=\"tags.php?op=delete&id_bk=$id_bk\">
                    //                  <img src=\"img/delete.png\" name=\"del_icon\" border=\"0\"
                    //                  alt=\"" . translateFN("Elimina") . "\"></a>";
                    //                  $formatted_dataHa[$k]['edit'] =  "<a href=\"tags.php?op=edit&id_bk=$id_bk\">
                    //                  <img src=\"img/edit.png\" name=\"edit_icon\" border=\"0\"
                    //                  alt=\"" . translateFN("Edit") . "\"></a>";
                    //                } else {
                    //                  $formatted_dataHa[$k]['del'] = "-";
                    //                  $formatted_dataHa[$k]['edit'] = "-";
                    //                }
                } else {
                    $formatted_dataHa[$k]['nodo'] = "<a href=\"view.php?id_node=$id_node\"><img src=\"img/$icon\" border=0> $title</a>";
                    // vito 13 gennaio 2009
                    //                if ($reg_enabled AND $author_id == $sess_user_id){
                    //                  $formatted_dataHa[$k]['del'] =  "<a href=\"tags.php?op=delete&id_bk=$id_bk\">
                    //                  <img src=\"img/delete.png\" name=\"del_icon\" border=\"0\"
                    //                  alt=\"" . translateFN("Elimina") . "\"></a>";
                    //                  $formatted_dataHa[$k]['edit'] =  "<a href=\"tags.php?op=edit&id_bk=$id_bk\">
                    //                  <img src=\"img/edit.png\" name=\"edit_icon\" border=\"0\"
                    //                  alt=\"" . translateFN("Edit") . "\"></a>";
                    //                } else {
                    //                  $formatted_dataHa[$k]['del'] = "-";
                    //                  $formatted_dataHa[$k]['edit'] = "-";
                    //                }
                }
                $formatted_dataHa[$k]['zoom'] =  "<a href=\"tags.php?op=zoom&id_bk=$id_bk\">
              <img src=\"img/zoom.png\" name=\"zoom_icon\" border=\"0\"
              alt=\"" . translateFN("Zoom") . "\"></a>";
            }

            $t = new Table();
            $t->initTable('', 'default', '2', '1', '100%', '', '', '', '', 1, 0);
            $t->setTable($formatted_dataHa, translateFN("Tag"), '');
            $res = $t->getTable();
        }
        return $res;
    }
}

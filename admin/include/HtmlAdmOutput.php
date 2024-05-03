<?php

//
// +----------------------------------------------------------------------+
// | ADA version 1.8                                                              |
// +----------------------------------------------------------------------+
// | Copyright (c) 2001-2006 Lynx                                         |
// +----------------------------------------------------------------------+
// |                                                                                         |
// |              HTML ADMIN OUTPUT                                       |
// |                                                                                         |
// |                                                                                         |
// |                                                                                         |
// |                                                                                         |
// |                                                                                         |
// +----------------------------------------------------------------------+
// | Author: Marco Benini                                                       |
// |                                                                                          |
// +----------------------------------------------------------------------+
//

namespace Lynxlab\ADA\Admin;

use Lynxlab\ADA\Main\form\PhpOpenFormGen;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\dirTree;
use function Lynxlab\ADA\Main\Utilities\readDir;
use function Lynxlab\ADA\Main\Utilities\todayDateFN;

class HtmlAdmOutput
{
    // Metodi

    // Funzione back ad una determinata pagina
    public function goFileBack($file_back, $label)
    {
        // inizializzazione variabili
        $str = "";

        if (!$label) {
            $label = "Home";
        }

        $str = "<p>&nbsp;</p>";//</div>";
        $str .= "<p align=\"center\"><a href=\"$file_back\">$label</a></p>";
        return $str ;
    }

    // Funzione scrittura form aggiungi course
    public function formAddCourse($file_action, $file_back, $authors_ha, $is_author = 0)
    {

        $root_dir = $GLOBALS['root_dir'];
        // inizializzazione variabili
        $str = "";

        // nome
        $fields["add"][] = "course[nome]";
        $names["add"][] = "Nome";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "true";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = 32;

        // titolo
        $fields["add"][] = "course[titolo]";
        $names["add"][] = "Titolo";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "true";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = 128;

        // autore
        if ($is_author) {
            $fields["add"][] = "course[id_autore]";
            $names["add"][] = "Autore";
            $edittypes["add"][] = "hidden";
            $necessary["add"][] = "true";
            $values["add"][] = $authors_ha[0][0];
            $options["add"][] = "";
            $maxsize["add"][] = "";
        } else {
            $labels_sel = "";
            $val_sel = "";
            $max = count($authors_ha) ;
            for ($i = 0; $i < $max; $i++) {
                $labels_sel .= ":" . $authors_ha[$i][1] . " " . $authors_ha[$i][2] . " ";
                if ($i != ($max - 1)) {
                    $val_sel .= $authors_ha[$i][0] . ":" ;
                } else {
                    $val_sel .= $authors_ha[$i][0] ;
                }
            }

            $fields["add"][] = "course[id_autore]";
            $names["add"][] = "Autore $labels_sel";
            $edittypes["add"][] = "select";
            $necessary["add"][] = "true";
            $values["add"][] = "";
            $options["add"][] = "$val_sel";
            $maxsize["add"][] = "";
        }

        // descrizione
        $fields["add"][] = "course[descr]";
        $names["add"][] = "descrizione";
        $edittypes["add"][] = "textarea";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // data creazione
        if ($is_author) {
            $gma = todayDateFN();
            $fields["add"][] = "course[d_create]";
            $names["add"][] = "data creazione (GG/MM/AAAA)";
            $edittypes["add"][] = "hidden";
            $necessary["add"][] = "";
            $values["add"][] = $gma;
            $options["add"][] = "";
            $maxsize["add"][] = 12;
        } else {
            $fields["add"][] = "course[d_create]";
            $names["add"][] = "data creazione (GG/MM/AAAA)";
            $edittypes["add"][] = "text";
            $necessary["add"][] = "";
            $values["add"][] = "";
            $options["add"][] = "";
            $maxsize["add"][] = 12;
        }

        // data pubblicazione
        if (!$is_author) {
            $fields["add"][] = "course[d_publish]";
            $names["add"][] = "data pubblicazione";
            $edittypes["add"][] = "text";
            $necessary["add"][] = "";
            $values["add"][] = "";
            $options["add"][] = "";
            $maxsize["add"][] = 12;
        }
        // media path
        $fields["add"][] = "course[media_path]";
        $names["add"][] = "media path";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = 50;


        $temp_dir_base = $root_dir . "/layout/";
        $layout_OK = dirTree($temp_dir_base);
        $val_sel = "";
        $max = count($layout_OK) ;
        for ($i = 0; $i < $max; $i++) {
            //if (($layout_OK[$i]!='.') && ($layout_OK[$i]!='..'))
            if ($i != ($max - 1)) {
                $val_sel .= $layout_OK[$i] . ":" ;
            } else {
                $val_sel .= $layout_OK[$i] ;
            }
        }
        // $layout_OK [] = "";
        $fields["add"][] = "course[layout]";
        $names["add"][] = "Layout: $val_sel";
        $edittypes["add"][] = "select";
        $necessary["add"][] = "";
        $values["add"][] = $course['layout'] ?? "";
        $options["add"][] = $val_sel;
        $maxsize["add"][] = 20;

        // id_nodo_toc
        $fields["add"][] = "course[id_nodo_toc]";
        $names["add"][] = "id_nodo_toc";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // id_nodo_iniziale
        $fields["add"][] = "course[id_nodo_iniziale]";
        $names["add"][] = "id_nodo_iniziale";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // file XML possibili

        // vito, 15 giugno 2009
        $message = "";
        $modello = DataValidator::checkInputValues('modello','Integer',INPUT_GET);
        if ($is_author && (int)$modello == 1) {
            $course_models = readDir(AUTHOR_COURSE_PATH_DEFAULT, 'xml');

            /*
             * vito, 30 mar 2009
             * Decomment the following lines (those after the comment SEARCH INTO AUTHOR'S UPLOAD DIR)
             * to enable searching for course models into author's
             * upload dir in addition to those stored into AUTHOR_COURSE_PATH_DEFAULT dir.
             *
             * It is necessary to handle this change in admin/author_course_xml_to_db_process.php:
             * now it builds the root dir relative position for the given xml file by prefixing it
             * with AUTHOR_COURSE_PATH_DEFAULT. If we allow searching into the author's upload dir
             * we have to avoid adding this prefix because the filename will be already a root dir
             * relative filename.
             *
             * If an author wants to create a new course based on an existing course model,
             * show him the course models in the course model repository, (common to all authors) and
             * the ones he has uploaded, stored in UPLOAD_PATH/<authorid>.
             * Otherwise, if an admin wants to create a course from an existing model, show him only the
             * course models stored in the course model repository.
             */
            // SEARCH INTO AUTHOR'S UPLOAD DIR
            //        if (!is_array($course_models)) {
            //          $course_models = array();
            //        }
            //        if ($is_author) {
            //        $authors_uploaded_files = UPLOAD_PATH.$authors_ha[0][0];
            //        $authors_course_models  = readDir($authors_uploaded_files, 'xml');
            //        $course_models = array_merge($course_models, $authors_course_models);
            //        }

            $num_files = 0;
            if (is_array($course_models)) {
                $num_files = sizeof($course_models);
            }


            $val_sel = '';
            $label_sel = '';
            if ($num_files > 0) {
                foreach ($course_models as $value) {
                    //vito, 30 mar 2009
                    // SEARCH INTO AUTHOR'S UPLOAD DIR
                    //$val_sel.=$value['path_to_file'].":";
                    $val_sel .= $value['file'] . ":";

                    $label_sel .= ":" . $value['file'];
                }
                $val_sel = substr($val_sel, 0, -1);
                // vito, 12 giugno 2009
                //}
                //if ($is_author AND ((int)$_GET['modello'])==1) {
                $fields["add"][] = "course[xml]";
                $names["add"][] = "XML" . $label_sel;
                $edittypes["add"][] = "select";
                $necessary["add"][] = "";
                $values["add"][] = "";
                $options["add"][] = $val_sel;
                $maxsize["add"][] = "";
                //}
            } else {
                $message = translateFN("Non sono presenti modelli di corso. E' comunque possibile creare un corso vuoto.");
            }
        }

        // creazione del form
        $str = PhpOpenFormGen::makeForm($fields, $names, $edittypes, $necessary, $values, $options, $maxsize, $file_action, "add", false, true);

        // scrittura stringa back
        //  $str .= $this->goFileBack($file_back,"Home");

        return $message . $str ;
    }

    public function formConfirmpassword($file_action, $file_back, $username, $id_user, $id_course, $token)
    {
        // inizializzazione variabili
        $str = "";
        // password
        $fields["add"][] = "user[password]";
        $names["add"][] = "password";
        $edittypes["add"][] = "password";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = 50;

        // password check
        $fields["add"][] = "user[passwordcheck]";
        $names["add"][] = "ripeti password";
        $edittypes["add"][] = "password";
        $necessary["add"][] = "";
        $values["add"][] = "";
        $options["add"][] = "";
        $maxsize["add"][] = 50;

        // uid
        $fields["add"][] = "user[uid]";
        $names["add"][] = "uid";
        $edittypes["add"][] = "hidden";
        $necessary["add"][] = "";
        $values["add"][] = "$id_user";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // course
        $fields["add"][] = "course";
        $names["add"][] = "course";
        $edittypes["add"][] = "hidden";
        $necessary["add"][] = "";
        $values["add"][] = "$id_course";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // username
        $fields["add"][] = "user[username]";
        $names["add"][] = "username";
        $edittypes["add"][] = "hidden";
        $necessary["add"][] = "";
        $values["add"][] = "$username";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // token
        $fields["add"][] = "token";
        $names["add"][] = "tok";
        $edittypes["add"][] = "hidden";
        $necessary["add"][] = "";
        $values["add"][] = "$token";
        $options["add"][] = "";
        $maxsize["add"][] = "";

        // creazione del form
        $str = PhpOpenFormGen::makeForm($fields, $names, $edittypes, $necessary, $values, $options, $maxsize, $file_action, "add", false, true);

        // scrittura stringa back
        //  $str .= $this->goFileBack($file_back,translateFN("Indietro"));

        return $str ;
    }

    // Function to get username for password changing
    public function formGetUsername($file_action)
    {
        // inizializzazione variabili
        $http_root_dir =   $GLOBALS['http_root_dir'];
        $root_dir =   $GLOBALS['root_dir'];

        $str = "";
        // username
        $fields["add"][] = "username";
        $names["add"][] = "username or email address";
        $edittypes["add"][] = "text";
        $necessary["add"][] = "";
        $values["add"][] = $username ?? null;
        $options["add"][] = "";
        $maxsize["add"][] = 50;

        // creazione del form
        $str = PhpOpenFormGen::makeForm($fields, $names, $edittypes, $necessary, $values, $options, $maxsize, $file_action, "add", false, true);

        // scrittura stringa back
        // $str .= $this->goFileBack($file_back,translateFN("Indietro"));

        return $str ;
    }

    // Funzione scrittura  di un messaggio generico di risposta
    public function info($messaggio)
    {
        $str = "<p>";
        $str .= $messaggio ;
        $str .= "</p>\n";
        return $str ;
    }

    public function cmp($a, $b)
    {
        // Ordina per cognome - il terzo elemento e' il cognome
        return strcmp($a[2], $b[2]);
    }
}

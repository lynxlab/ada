<?php

/**
 * User classes
 *
 *
 * @package     model
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        user_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\User;

use Lynxlab\ADA\CORE\HmtlElements\Table;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\History\History;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\aasort;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\ts2tmFN;

/**
 * AdaAbstractUser class:
 *
 * This is just a rename of the 'old' ADAUser class which is now declared
 * and implemented in its own 'ADAUser.inc.php' file required below.
 *
 * This was made abstract in order to be 100% sure that nobody will ever
 * instate it. Must instantiate the proper ADAUser class instead.
 *
 * The whole ADA system will than be able to use the usual ADAUser class,
 * but with extended methods and properties for each customization.
 *
 *
 * @author giorgio 04/giu/2013
 *
 */

abstract class ADAAbstractUser extends ADALoggableUser
{
    /**
     * Undocumented variable
     *
     * @var \Lynxlab\ADA\Main\History\History
     */
    public $history;
    protected $whatsnew;

    public $user_ex_historyAr;

    public function __construct($user_dataAr = [])
    {
        parent::__construct($user_dataAr);

        $this->setHomePage(HTTP_ROOT_DIR . '/browsing/user.php');
        $this->setEditProfilePage('browsing/edit_user.php');
        $this->history = null;
    }

    /**
     * Must override setUserId method to get $whatsnew whenever we set $id_user
     *
     *
     * @param $user_id
     * @author giorgio 03/mag/2013
     */
    public function setUserId($id_user)
    {
        parent::setUserId($id_user);
        $this->setwhatsnew(MultiPort::get_new_nodes($this));
    }

    /**
     * whatsnew getter
     * @return array returns whatsnew array, populated in the constructor
     * @author giorgio

     */
    public function getwhatsnew()
    {
        return $this->whatsnew;
    }

    /**
     * whatsnew setter.
     *
     * @param array $newwhatsnew    new array to be set as the whatsnew array
     *
     * @return
     */
    public function setwhatsnew($newwhatsnew)
    {
        $this->whatsnew = $newwhatsnew;
    }

    /**
     * updates $whatsnew array based on the values from the db.
     *
     * @author giorgio
     *
     */
    public function updateWhatsNew()
    {
        $this->whatsnew = MultiPort::update_new_nodes_in_session($this);
    }

    /**
     *
     * @param $id_course_instance
     * @return void
     */
    public function set_course_instance_for_history($id_course_instance)
    {
        $historyObj = new History($id_course_instance, $this->id_user);
        // se non e' un errore, allora
        $this->history = $historyObj;
    }

    /**
     * Undocumented function
     *
     * @param int $id_course_instance
     * @return \Lynxlab\ADA\Main\History\History
     */
    public function getHistoryInCourseInstance($id_course_instance)
    {
        if (($this->history == null) || ($this->history->id_course_instance != $id_course_instance)) {
            $this->history = new History($id_course_instance, $this->id_user);
        }
        return $this->history;
    }


    // MARK: existing methods

    /**
     *
     * @param $id_user
     * @param $id_course_instance
     * @return integer
     */
    public function get_student_level($id_user, $id_course_instance)
    {
        $dh = $GLOBALS['dh'];
        // FIXME: _get_student_level was a private method, now it is public.
        $user_level = $dh->get_student_level($id_user, $id_course_instance);
        if (AMA_DataHandler::isError($user_level)) {
            $this->livello = 0;
        } else {
            $this->livello = $user_level;
        }
        return $this->livello;
    }

    /**
     *
     * @param $id_user
     * @param $id_course_instance
     * @return void
     */
    public function get_student_score($id_user, $id_course_instance)
    {
        // NON CI SONO ESERCIZI, NON DOVREBBE ESSERCI PUNTEGGIO
    }

    /**
     *
     * @param $id_student
     * @param $id_course_instance
     * @return integer
     */
    public function get_student_status($id_student, $id_course_instance)
    {
        $dh = $GLOBALS['dh'];

        $this->status = 0;
        if ($this->tipo == AMA_TYPE_STUDENT) {
            $student_courses_subscribe_statusHa = $dh->course_instance_student_presubscribe_get_status($id_student);
            if (is_object($student_courses_subscribe_statusHa)) {
                return $student_courses_subscribe_statusHa->error;
            }
            if (empty($student_courses_subscribe_statusHa)) {
                return "";
            }
            foreach ($student_courses_subscribe_statusHa as $course_subscribe_status) {
                if ($course_subscribe_status['istanza_corso'] == $id_course_instance) {
                    $this->status = $course_subscribe_status['status'];
                    break;
                }
            }
        }
        return $this->status;
    }

    /**
     *
     * @param $id_student
     * @return string
     */
    public function get_student_family($id_student)
    {
        if (isset($this->template_family)) {
            return $this->template_family;
        } else {
            return ADA_TEMPLATE_FAMILY;
        }
    }

    /**
     *
     * @param $id_student
     * @param $node_type
     * @return integer
     */
    public function total_visited_nodesFN($id_student, $node_type = "")
    {
        //  returns 0 or the number of nodes visited by this student
        if (is_object($this->history)) {
            return $this->history->get_total_visited_nodes($node_type);
        }
        return 0;
    }

    /**
     *
     * @param $id_student
     * @return integer
     */
    public function total_visited_notesFN($id_student)
    {
        $visited_nodes_count = $this->total_visited_nodesFN($id_student, ADA_NOTE_TYPE);
        return $visited_nodes_count;
    }

    public function getDefaultTester()
    {
        return null;
    }

    public function get_exercise_dataFN($id_course_instance, $id_student = "")
    {
        $dh = $GLOBALS['dh'];
        $out_fields_ar = ['ID_NODO','ID_ISTANZA_CORSO','DATA_VISITA','PUNTEGGIO','COMMENTO','CORREZIONE_RISPOSTA_LIBERA'];
        $dataHa = $dh->find_ex_history_list($out_fields_ar, $this->id_user, $id_course_instance);

        if (AMA_DataHandler::isError($dataHa) || empty($dataHa)) {
            $this->user_ex_historyAr = '';
        } else {
            aasort($dataHa, ["-1"]) ;
            $this->user_ex_historyAr = $dataHa;
        }
    }

    public function history_ex_done_FN($id_student, $id_profile = "", $id_course_instance = "")
    {
        /*
            Esercizi svolti
            Crea array con nodo e punteggio, ordinato in ordine
            decrescente di punteggio.
        */

        $dh = $GLOBALS['dh'];
        $error = $GLOBALS['error'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $debug = $GLOBALS['debug'] ?? null;

        if (empty($id_profile)) {
            $id_profile = AMA_TYPE_TUTOR;
        }

        $ids_nodi_padri = [];
        if (!empty($this->user_ex_historyAr)) {
            foreach ($this->user_ex_historyAr as $k => $e) {
                $exer_stats_ha[$k]['nome'] = $e[0];
                $exer_stats_ha[$k]['titolo'] = $e[1];
                $exer_stats_ha[$k]['id_nodo_parent'] = $e[2];
                $exer_stats_ha[$k]['id_exe'] = $e[3];
                $exer_stats_ha[$k]['id_nodo'] = $e[4];
                $exer_stats_ha[$k]['id_istanza'] = $e[5];
                $exer_stats_ha[$k]['data'] = $e[6];
                $exer_stats_ha[$k]['punteggio'] = $e[7];
                $exer_stats_ha[$k]['commento'] = $e[8];
                $exer_stats_ha[$k]['correzione'] = $e[9];

                $ids_nodi_padri[] = $exer_stats_ha[$k]['id_nodo_parent'];
            }

            if (!empty($ids_nodi_padri)) {
                $nodi_padri = $dh->get_nodes($ids_nodi_padri, ['nome','titolo']);
            }

            $label1 = translateFN('Esercizio');
            $label2 = translateFN('Data');
            $label3 = translateFN('Punteggio');
            $label4 = translateFN('Corretto');
            $data = [];

            foreach ($exer_stats_ha as $k => $e) {
                $id_exe = $e['id_exe'];
                $id_nodo = $e['id_nodo'];
                $nome = $e['nome'];
                $titolo = $e['titolo'];
                $nome_padre = $nodi_padri[$e['id_nodo_parent']]['nome'];

                $punteggio = $e['punteggio'];
                if (($e['commento'] != '-') or ($e['correzione'] != '-')) {
                    $corretto =  translateFN('Si');
                } else {
                    $corretto =  translateFN('-');
                }

                $date = ts2dFN($e['data']) . " " . ts2tmFN($e['data']);

                if ($id_profile == AMA_TYPE_TUTOR) {
                    $zoom_module = "$http_root_dir/tutor/tutor_exercise.php";
                } else {
                    $zoom_module = "$http_root_dir/browsing/exercise_history.php";
                }

                // vito, 18 mar 2009
                $link = CDOMElement::create('a');
                if (!empty($id_course_instance) && is_numeric($id_course_instance)) {
                    $link->setAttribute('href', $zoom_module . '?op=exe&id_exe=' . $id_exe . '&id_student=' . $id_student . '&id_nodo=' . $id_nodo . '&id_course_instance=' . $id_course_instance);
                } else {
                    $link->setAttribute('href', $zoom_module . '?op=exe&id_exe=' . $id_exe . '&id_student=' . $id_student . '&id_nodo=' . $id_nodo);
                }
                $link->addChild(new CText($nome_padre . ' > '));
                $link->addChild(new CText($nome));
                $html = $link->getHtml();

                $data[] =  [
                    $label1 => $html,
                    $label2 => $date,
                    $label3 => $punteggio,
                    $label4 => $corretto,
                ];
            }
            $t = new Table();
            $t->initTable('0', 'center', '1', '1', '90%', '', '', '', '', '1', '0', '', 'default', 'exercise_table');
            $t->setTable($data, translateFN("Esercizi e punteggio"), translateFN("Esercizi e punteggio"));
            $res = $t->getTable();
            $res = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $res, 1); // replace first occurence of class
        } else {
            $res = translateFN("Nessun esercizio.");
        }
        return $res;
        //end history_ex_done_FN
    }

    /**
     * this function fix user certificate.
     *
     * @return boolean
     */
    public static function Check_Requirements_Certificate($userId, $instanceStatus)
    {
        /* be implemented according to the use cases */
        return true;
    }
}

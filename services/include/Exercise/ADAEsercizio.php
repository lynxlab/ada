<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\Main\Utilities;

/**
 * @name ADAEsercizio
 * This has just getters and setters for its attributes.
 *
 */
class ADAEsercizio
{
    public $id;
    public $testo;
    public $dati;
    public $risposta;
    public $flag_ex;
    public $flag_risp;
    public $updated_data_ids;

    public function __construct($id_node, $testo, $dati, $student_answer = null)
    {
        $this->id        = $id_node;
        $this->testo     = $testo;
        $this->dati      = $dati;
        if ($student_answer == null) {
            $this->risposta['ripetibile'] = true;
        } else {
            $this->risposta  = $student_answer;
        }
        $this->flag_ex   = false;
        $this->flag_risp = false;
        $this->updated_data_ids = [];
    }

    /*
     * Getters
    */
    public function getUpdatedDataIds()
    {
        return $this->updated_data_ids;
    }
    public function getId()
    {
        return $this->id;
    }

    public function getText()
    {
        return $this->testo['testo'];
    }

    public function getExerciseData()
    {
        return $this->dati;
    }

    public function getAuthorId()
    {
        return $this->testo['id_utente'];
    }

    public function getTitle()
    {
        return $this->testo['nome'];
    }

    public function getType()
    {
        return $this->testo['tipo'];
    }

    public function getExerciseFamily()
    {
        $type = $this->testo['tipo'];
        return $type[0];
    }

    public function getExerciseInteraction()
    {
        $type = $this->testo['tipo'];
        return $type[1] ?? 0;
    }

    public function getExerciseMode()
    {
        $type = $this->testo['tipo'];
        return $type[2] ?? 0;
    }

    public function getExerciseSimplification()
    {
        $type = $this->testo['tipo'];
        return $type[3] ?? 0;
    }

    public function getExerciseBarrier()
    {
        $type = $this->testo['tipo'];
        return $type[4] ?? 0;
    }

    public function getStudentAnswer()
    {
        return $this->risposta['risposta_libera'];
        //return $this->risposta[6];
    }

    public function getAnswerText($id_answer = null)
    {
        if ($id_answer == null) {
            $id_answer = $this->getStudentAnswer();
        }
        return $this->dati[$id_answer]['nome'];
    }

    public function getRating()
    {
        return $this->risposta['punteggio'];
        //return $this->risposta[8];
    }

    public function getExecutionDate()
    {
        if (is_array($this->risposta)) {
            return Utilities::ts2dFN($this->risposta['data_visita']);
        }
    }
    public function getCorrectness($id_node)
    {
        return $this->dati[$id_node]['correttezza'];
    }

    public function getAuthorComment($id_node = null)
    {
        /*
       * Se $id_node è NULL, sto considerando il testo della risposta data
       * dallo studente, che è in $this->dati['nome']
        */
        if ($id_node == null) {
            $student_answer = $this->getStudentAnswer();

            foreach ($this->dati as $answer) {
                if ($answer['nome'] == $student_answer) {
                    return $answer['testo'];
                }
            }
        }
        return $this->dati[$id_node]['testo'];
    }

    public function getExerciseLevel()
    {
        return $this->testo['livello'];
    }

    public function getParentId()
    {
        return $this->testo['id_nodo_parent'];
    }

    public function getOrder()
    {
        return $this->testo['ordine'];
    }

    public function getStudentId()
    {
        return $this->risposta['id_utente_studente'];
        //return $this->risposta[1];
    }

    public function getCourseInstanceId()
    {
        return $this->risposta['id_istanza_corso'];
        //return $this->risposta[3];
    }

    public function getStudentAnswerId()
    {
        return $this->risposta['id_history_ex'];
    }

    public function getRepeatable()
    {
        return $this->risposta['ripetibile'];
    }

    public function getTutorComment()
    {
        return $this->risposta['commento'];
    }

    public function getAttachment()
    {
        if (isset($this->risposta['allegato'])) {
            return $this->risposta['allegato'];
        } else {   // dovrebbe restituire null o false, ma il metodo $dh->addExHistory
            //si aspetta una cosa del genere.
            return " ";
        }
    }
    /*
     * Setters
    */
    public function setTitle($title)
    {
        $this->testo['nome'] = $title;
        if (!$this->flag_ex) {
            $this->flag_ex = true;
        }
    }

    public function setText($text)
    {
        $this->testo['testo'] = $text;
        if (!$this->flag_ex) {
            $this->flag_ex = true;
        }
    }

    public function setStudentAnswer($answer)
    {
        $this->risposta['risposta_libera'] = $answer;
        if (!$this->flag_risp) {
            $this->flag_risp = true;
        }
    }

    public function setRating($rating)
    {
        $this->risposta['punteggio'] = $rating;
        if (!$this->flag_risp) {
            $this->flag_risp = true;
        }
    }

    public function setTutorComment($comment)
    {
        $this->risposta['commento'] = $comment;
        if (!$this->flag_risp) {
            $this->flag_risp = true;
        }
    }

    public function setRepeatable($flag)
    {
        $this->risposta['ripetibile'] = $flag;
        if (!$this->flag_risp) {
            $this->flag_risp = true;
        }
    }

    public function setStudentId($id)
    {
        $this->risposta['id_utente_studente'] = $id;
    }

    public function setCourseInstanceId($id)
    {
        $this->risposta['id_istanza_corso'] = $id;
    }

    public function setAttachment($file)
    {
        $this->risposta['allegato'] = $file;
        if (!$this->flag_risp) {
            $this->flag_risp = true;
        }
    }

    public function setExerciseDataAnswerForItem($id, $value)
    {
        if (!isset($this->dati[$id])) {
            return false;
        }

        $this->dati[$id]['nome'] = $value;
        if (!$this->flag_ex) {
            $this->flag_ex = true;
        }
        return true;
    }

    public function setExerciseDataAuthorCommentForItem($id, $value)
    {
        if (!isset($this->dati[$id])) {
            return false;
        }

        $this->dati[$id]['testo'] = $value;
        if (!$this->flag_ex) {
            $this->flag_ex = true;
        }
        return true;
    }

    public function setExerciseDataCorrectnessForItem($id, $value)
    {
        if (!isset($this->dati[$id])) {
            return false;
        }

        $this->dati[$id]['correttezza'] = $value;
        if (!$this->flag_ex) {
            $this->flag_ex = true;
        }
        return true;
    }

    public function getExerciseDataAnswerForItem($id)
    {
        if (isset($this->dati[$id])) {
            return $this->dati[$id]['nome'];
        }
    }

    public function getExerciseDataAuthorCommentForItem($id)
    {
        if (isset($this->dati[$id])) {
            return $this->dati[$id]['testo'];
        }
    }

    public function getExerciseDataCorrectnessForItem($id)
    {
        if (isset($this->dati[$id])) {
            return $this->dati[$id]['correttezza'];
        }
    }

    public function getExerciseDataTypeForItem($id)
    {
        if (isset($this->dati[$id])) {
            return $this->dati[$id]['tipo'];
        }
    }

    public function getExerciseDataOrderForItem($id)
    {
        if (isset($this->dati[$id])) {
            return $this->dati[$id]['ordine'];
        }
    }


    /**
     * @method saveOrUpdateEsercizio
     * Used to check if this exercise needs to be saved (in case it doesn't exists in db)
     * or it needs to get updated (in case it exists in db and some change has been made)
     * @return 1 if this exercise needs to be saved
     * @return 2 if this exercise needs to be updated
     * @return 0 otherwise
     */
    public function saveOrUpdateEsercizio()
    {
        if ($this->testo['id_nodo'] == null) {
            return 1;
        } elseif ($this->flag_ex) {
            return 2;
        }

        return 0;
    }

    /**
     * @method saveOrUpdateRisposta
     * Used to check if the content of $this->risposta needs to be saved or updated.
     *
     * @return 1 if it needs to be saved
     * @return 2 if it needs to be updated
     * @return 0 otherwise
     */
    public function saveOrUpdateRisposta()
    {
        if ($this->flag_ex) {
            if (!isset($this->risposta['id_nodo']) || $this->risposta['id_nodo'] == null) {
                return 0;
            }
        }

        if ($this->risposta['id_nodo'] == null) {
            return 1;
        } elseif ($this->flag_risp) {
            return 2;
        }

        return 0;
    }

    public function deleteDataItem($id)
    {
        if (isset($this->dati[$id])) {
            $this->updated_data_ids[$id] = ADA_EXERCISE_DELETED_ITEM;
            unset($this->dati[$id]);
            if (!$this->flag_ex) {
                $this->flag_ex = true;
            }
        }
    }

    public function updateExercise($data = [])
    {
        unset($data['edit_exercise']);

        if (isset($data['exercise_title']) && !empty($data['exercise_title'])) {
            $this->setTitle($data['exercise_title']);

            if (!isset($this->updated_data_ids[$this->id])) {
                $this->updated_data_ids[$this->id] = true;
            }
            unset($data['exercise_title']);
        }

        if (isset($data['exercise_text']) && !empty($data['exercise_text'])) {
            $this->setText($data['exercise_text']);

            if (!isset($this->updated_data_ids[$this->id])) {
                $this->updated_data_ids[$this->id] = true;
            }
            unset($data['exercise_text']);
        }

        foreach ($data as $exercise_data_id => $value) {
            $data = explode('_', $exercise_data_id);
            $node_id = $data[0] . '_' . $data[1];
            $key = $data[2];

            if (!isset($this->updated_data_ids[$node_id])) {
                $this->updated_data_ids[$node_id] = ADA_EXERCISE_MODIFIED_ITEM;
            }

            //        print_r($this->dati[$node_id]);
            //        echo '<br />';

            switch ($key) {
                case 'answer':
                    //$field = 'nome';
                    $result = $this->setExerciseDataAnswerForItem($node_id, $value);
                    break;
                case 'comment':
                    //$field = 'testo';
                    $result = $this->setExerciseDataAuthorCommentForItem($node_id, $value);
                    break;
                case 'correctness':
                    $result = $this->setExerciseDataCorrectnessForItem($node_id, $value);
                    break;
                default:
                    return false;
            }
            //        if (!isset($this->dati[$node_id])) {
            //          //sto aggiungendo dei dati, da gestire
            //        }
            //        else {
            //          $this->dati[$node_id][$field] = $value;
            //          if (!$this->flag_risp) {
            //            $this->flag_risp = TRUE;
            //          }
            //        }
        }
    }
}

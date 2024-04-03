<?php

namespace Lynxlab\ADA\Services\Exercise;

use function Lynxlab\ADA\Main\AMA\DBRead\get_max_idFN;

/**
 * @name ExerciseDAO
 * This is a Data Acces Object use to get ADA Exercises Object from database,
 * or to store an ADA Exercise Object in the database.
 *
 */
class ExerciseDAO
{
    /**
     * @method getExercise
     * Used to retrieve an exercise from database.
     * It gets all the needed data to build an exercise and returns the exercise object.
     * @return ADA_Exercise object on success, AMA_PEAR_Error on failure.
     */

    public static function getExercise($id_node, $id_answer = null)
    {
        $dh = $GLOBALS['dh'];
        $exercise_nodes = $dh->get_exercise($id_node);
        if (AMA_DataHandler::isError($exercise_nodes)) {
            $errObj = new ADA_Error($exercise_nodes, 'Error while loading exercise');
        }

        $nodes = [];
        $exercise_text = '';
        foreach ($exercise_nodes as $exercise) {
            if ($exercise['id_nodo'] == $id_node) {
                $exercise_text = $exercise;
            } else {
                $nodes[$exercise['id_nodo']] = $exercise;
            }
        }

        $student_answer = null;

        if ($id_answer != null) {
            $student_answer = $dh->get_student_answer($id_answer);
            if (AMA_DataHandler::isError($student_answer)) {
                //return $student_answer;
                $errObj = new ADA_Error($student_answer, 'Error while loading student answer');
            }
        }
        return new ADAEsercizio($id_node, $exercise_text, $nodes, $student_answer);
    }

    /**
     * @method getNextExerciseId
     * Used to get the id for the next exercise, if this exercise requires the next in sequence to
     * or a random one to be shown.
     * @param object $exercise - ADA Exercise object
     * @param int $id_student  -
     * @return mixed - a string representing next exercise id in case it finds an exercise to show or null.
     */
    public static function getNextExerciseId($exercise, $id_student)
    {
        $dh = $GLOBALS['dh'];

        $next_exercise_id = null;
        switch ($exercise->getExerciseMode()) {
            case ADA_SINGLE_EXERCISE_MODE:
            default:
                return $next_exercise_id;
                break;
            case ADA_SEQUENCE_EXERCISE_MODE:
                $exercises = $dh->get_other_exercises($exercise->getParentId(), $exercise->getOrder(), $id_student);
                if (AMA_DataHandler::isError($exercises)) {
                    return $exercises;
                }
                break;
            case ADA_RANDOM_EXERCISE_MODE:
                // get all of the exercises for parent_id node and shuffle them.
                $exercises = $dh->get_other_exercises($exercise->getParentId(), 0, $id_student);
                if (AMA_DataHandler::isError($exercises)) {
                    return $exercises;
                }
                shuffle($exercises);
                break;
        }

        foreach ($exercises as $ex) {
            if (($ex['ripetibile'] == null) || ($ex['ripetibile'] == 1)) {
                $next_exercise_id = $ex['id_nodo'];
                return $next_exercise_id;
            }
        }
        // There aren't exercises for the user
        return $next_exercise_id; // null
    }

    /**
     * @method save
     * Used to save or update an exercise in the database.
     * @param object $exercise - the exercise object we want to save.
     * @return mixed - true or AMA_PEAR Error.
     */
    public static function save($exercise)
    {
        $dh = $GLOBALS['dh'];
        // AL MOMENTO NON  VIENE USATO PER CREARE L'ESERCIZIO

        switch ($exercise->saveOrUpdateEsercizio()) {
            case 0:
            default:
                break;
            case 1:
                // save
                break;
            case 2:
                // update
                $nodes = [];
                $nodes = $exercise->getUpdatedDataIds();

                foreach ($nodes as $updated_node => $operation_on_node) {
                    if ($updated_node == $exercise->getId()) {
                        $data = [];
                        $data['id']        = $exercise->getId();
                        $data['name']      = $exercise->getTitle();
                        $data['text']      = $exercise->getText();
                        $data['type']      = $exercise->getType();
                        $data['parent_id'] = $exercise->getParentId();
                        $data['order']     = $exercise->getOrder();

                        $result = $dh->doEdit_node($data);
                        if (AMA_DataHandler::isError($result)) {
                            return false;
                        }
                    } else {
                        if ($operation_on_node == ADA_EXERCISE_MODIFIED_ITEM) {
                            $data['id']        = $updated_node;//$ex_data['id_nodo'];
                            $data['name']      = $exercise->getExerciseDataAnswerForItem($updated_node);//$ex_data['nome'];
                            $data['text']      = $exercise->getExerciseDataAuthorCommentForItem($updated_node);
                            $data['type']      = $exercise->getExerciseDataTypeForItem($updated_node);
                            $data['parent_id'] = $exercise->getId();
                            $data['order']     = $exercise->getExerciseDataOrderForItem($updated_node);
                            $data['correctness'] = $exercise->getExerciseDataCorrectnessForItem($updated_node);

                            $result = $dh->doEdit_node($data);
                            if (AMA_DataHandler::isError($result)) {
                                return false;
                            }
                        } elseif ($operation_on_node == ADA_EXERCISE_DELETED_ITEM) {
                            $result = $dh->remove_node($updated_node);
                            if (AMA_DataHandler::isError($result)) {
                                return false;
                            }
                        }
                    }
                }


                //                $data = array();
                //              $data['id']        = $exercise->getId();
                //              $data['name']      = $exercise->getTitle();
                //              $data['text']      = $exercise->getText();
                //              $data['type']      = $exercise->getType();
                //              $data['parent_id'] = $exercise->getParentId();
                //              $data['order']     = $exercise->getOrder();
                //
                //              $result = $dh->doEdit_node($data);
                //                if (AMA_DataHandler::isError($result)) {
                //                  return FALSE;
                //                }
                //
                //                $exercise_data = $exercise->getExerciseData();
                //                foreach ($exercise_data as $ex_data) {
                //                  $data['id']        = $ex_data['id_nodo'];
                //                $data['name']      = $ex_data['nome'];
                //                $data['text']      = $ex_data['testo'];
                //                $data['type']      = $ex_data['tipo'];
                //                $data['parent_id'] = $exercise->getId();
                //                $data['order']     = $ex_data['ordine'];
                //                $data['correctness'] = $ex_data['correttezza'];
                //
                //                  $result = $dh->doEdit_node($data);
                //                  if (AMA_DataHandler::isError($result)) {
                //                    return FALSE;
                //                  }
                //                }

                break;
        }

        switch ($exercise->saveOrUpdateRisposta()) {
            case 0:
            default:
                break;
            case 1:
                // save
                $result = $dh->add_ex_history(
                    $exercise->getStudentId(),
                    $exercise->getCourseInstanceId(),
                    $exercise->getId(),
                    $exercise->getStudentAnswer(),
                    "-",
                    $exercise->getRating(),
                    "-",
                    $ripetibile = 0,
                    $exercise->getAttachment()
                );
                //print_r($result);
                if (AMA_DataHandler::isError($result)) {
                    return false;
                }
                return true;
                break;
            case 2:
                // update
                $data =  [ 'commento' => $exercise->getTutorComment(),
                        'da_ripetere' => $exercise->getRepeatable(),
                        'punteggio' => $exercise->getRating() ];
                $result = $dh->set_ex_history($exercise->getStudentAnswerId(), $data);
                if (AMA_DataHandler::isError($result)) {
                    return false;
                }
                return true;
                break;
        }
        return true;
    }

    public static function delete($exercise_id)
    {
        $dh = $GLOBALS['dh'];

        $exercise = self::getExercise($exercise_id);
        $exercise_data = $exercise->getExerciseData();
        foreach ($exercise_data as $node_id => $node_data) {
            $result = $dh->remove_node($node_id);
            //            print_r($result);
        }

        $result = $dh->remove_node($exercise_id);
    }

    public static function canEditExercise($exercise_id)
    {
        $dh = $GLOBALS['dh'];

        $tokens = explode('_', $exercise_id);
        $course_id = $tokens[0];

        $result = $dh->course_instance_get_list(null, $course_id);
        if (AMA_DataHandler::isError($result)) {
            return false;
        }
        /*
       * There aren't active course instances, the exercise can be edited.
        */
        if (is_array($result) && sizeof($result) == 0) {
            return true;
        }

        /*
       * There is at least an active course instance.
       * This exercise can be edited only if no one has executed it.
        */
        foreach ($result as $course_instance_data) {
            $course_instance_id = $course_instance_data[0];
            $ex_history = $dh->find_exercise_history_for_course_instance($exercise_id, $course_instance_id);

            if (AMA_DataHandler::isError($ex_history)) {
                return false;
            }

            if (is_array($ex_history) && sizeof($ex_history) > 0) {
                return false;
            }
        }
        return true;
    }

    public static function addAnswer($exercise, $answer_data = [])
    {
        $dh = $GLOBALS['dh'];

        $tmpAr = [];
        $tmpAr = explode('_', $exercise->getId());
        $id_course = $tmpAr[0];
        $last_node = get_max_idFN($id_course);

        $tmpAr = [];
        $tempAr = explode('_', $last_node);
        $new_id = $tempAr[1] + 1;
        $new_node = $id_course . '_' . $new_id;

        $node_to_add = [
                'id'             => $new_node,
                'parent_id'      => $exercise->getId(),
                'id_node_author' => $exercise->getAuthorId(),
                'level'          => $exercise->getExerciseLevel(),
                'order'          => $answer_data['position'],
                'version'        => 0,
                'creation_date'  => isset($ymdhms) ?: null,
                'icon'           => '',
                'type'           => ADA_LEAF_TYPE,
                'pos_x0'       => 100,
                'pos_y0'       => 100,
                'pos_x1'       => 200,
                'pos_y1'       => 200,
                'name'           => $answer_data['answer'],              // titolo
                'title'          => '', // keywords
                'text'           => $answer_data['comment'],
                'bg_color'       => '#FFFFFF',
                'color'          => '',
                'correctness'    => $answer_data['correctness'],
                'copyright'      => '',
        ];
        $result = $dh->add_node($node_to_add);
        if (AMA_DataHandler::isError($result)) {
            $errObj = new ADA_Error($result, 'Error while adding a new answer');
        }
        return true;
    }

    public static function getExerciseInfo($exerciseObj, $id_course_instance)
    {
        $dh = $GLOBALS['dh'];
        /*
       * qui inserire uno switch che in base al tipo di esercizio
       * richiama un metodo opportuno di dh
        */
        $result = $dh->get_ex_report($exerciseObj->getId(), $id_course_instance);
        return $result;
    }
}

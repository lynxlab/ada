<?php

namespace Lynxlab\ADA\Services\Exercise;

/**
 * @name ExerciseViewer
 * This class (and its subclasses) manages the html form generation for each one of ExerciseFamily
 * in ADA.
 */
abstract class ExerciseViewer //extends AbsExerciseViewer
{
    /**
     * @method fill_field_with_data
     * It simply checks if array position $data[$field_name] and returns its content.
     * Otherwise it returns an empty string.
     *
     * @param string $field_name - a key for the associative array $data
     * @param array  $data       - an associative array
     * @return string
     */
    public function fillFieldWithData($field_name, $data = [])
    {
        //return ( isset($data[$field_name]) ) ? $data[$field_name] : "";
        $field_data = '';
        if (isset($data[$field_name])) {
            $field_data = $data[$field_name];
        }
        return $field_data;
    }

    /**
     * @method shuffleList
     * It shuffles an associative array, preserving $key=>$value association.
     *
     * @param array $a - the original array
     * @return array $shuffled - the shuffled array
     */
    public function shuffleList($a = [])
    {
        if (count($a) == 1) {
            return $a;
        }

        $shuffled = [];
        while (!empty($a)) {
            $key = array_rand($a, 1);
            $shuffled[$key] = $a[$key];
            unset($a[$key]);
        }

        return $shuffled;
    }

    public function getAddAnswerForm($edit_form_base_action, $exercise, $field)
    {
        return null;
    }

    /**
     *
     * @param $userObj     - the user object
     * @param $exerciseObj - the exercise object
     * @param $action      - the action for the form
     * @return String      - the form
     */
    public function getViewingForm($userObj, $exerciseObj, $id_course_instance, $action)
    {
        if ($userObj->tipo == AMA_TYPE_TUTOR) {
            return $this->getExerciseReport($exerciseObj, $id_course_instance);
        }

        return $this->getStudentForm($action, $exerciseObj);
    }

    abstract public function getExerciseReport($exerciseObj, $id_course_instance);
    abstract public function getStudentForm($form_action, $exercise);
}

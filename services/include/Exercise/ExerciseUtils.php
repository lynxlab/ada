<?php

use Lynxlab\ADA\Services\Exercise\ExerciseUtils;

// Trigger: ClassWithNameSpace. The class ExerciseUtils was declared with namespace Lynxlab\ADA\Services\Exercise. //

namespace Lynxlab\ADA\Services\Exercise;

class ExerciseUtils
{
    public static function tokenizeString($string)
    {

        $data = [];
        $length = strlen($string);

        $current_char = null;
        $current_word = null;
        $current_stop = null;
        $word_count = 0;

        for ($i = 0; $i < $length; $i++) {
            $current_char = $string[$i];
            /* state 1 */
            if (
                $current_char == ' ' || $current_char == ',' || $current_char == '.' || $current_char == ':'
                || $current_char == ';'  || $current_char == '!'  || $current_char == '?'
            ) {
                if ($i == 0) {
                    $state = 1;
                }
                if ($state == 0) {
                    if (!isset($data[$word_count])) {
                        $data[$word_count] = [null, null];
                    }
                    $data[$word_count][0] = $current_word;
                    if ($data[$word_count][1] !== null) {
                        $word_count++;
                    }
                    $current_word = null;

                    $state = 1;
                }
                $current_stop .= $current_char;
            } else {
                /*state 0*/
                if ($i == 0) {
                    $state = 0;
                }
                if ($state == 1) {
                    if (!isset($data[$word_count])) {
                        $data[$word_count] = [null, null];
                    }
                    $data[$word_count][1] = $current_stop;
                    $current_stop = null;
                    if ($data[$word_count][0] !== null) {
                        $word_count++;
                    }

                    $state = 0;
                }
                $current_word .= $current_char;
            }
        }
        if ($i == $length) {
            if ($current_word !== null) {
                $data[$word_count][0] = $current_word;
            }
            if ($current_stop !== null) {
                $data[$word_count][1] = $current_stop;
            }
        }
        return $data;
    }
}

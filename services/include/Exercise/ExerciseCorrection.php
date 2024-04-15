<?php

use Lynxlab\ADA\Services\Exercise\ExerciseCorrection;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class ExerciseCorrection was declared with namespace Lynxlab\ADA\Services\Exercise. //

namespace Lynxlab\ADA\Services\Exercise;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class ExerciseCorrection //implements iExerciseCorrection
{
    public function raiseUserLevel($exercise, $user_level)
    {
        return ($user_level == $exercise->getExerciseLevel() && $exercise->getExerciseBarrier() && $exercise->getRating() == ADA_MAX_SCORE);
    }

    public function getMessageForStudent($username, $exercise)
    {
        $msg  = "Punteggio ottenuto: " . $exercise->getRating() . "<BR>";
        $msg .= "La tua risposta " . $exercise->getStudentAnswer() . "<BR>";
        $msg .= "Il commento dell'autore: " . $exercise->getAuthorComment() . "<BR>";
        return $msg;
    }

    public function getMessageForTutor($username, $exercise)
    {
        /*
         * OLD EXERCISE.PHP CODE
        */

        $node_title = $exercise->getTitle();
        $node_exAr = explode('_', $exercise->getId());
        $node_ex_id =  $node_exAr[1];
        $testo = translateFN("Esercizio: ") . $node_title . " <link type=internal value=\"$node_ex_id\"><br />\n";

        //        $useranswer = $exercise->getStudentAnswer();
        //        $testo .= "Qui la risposta dello studente<BR>";

        $testo .= $exercise->getStudentAnswer();
        $testo .= "Valutazione: " . $exercise->getRating() . "<BR>";
        return $testo;
        //        if (is_array($useranswer)){
        //            $userAnswerStr = "<ul>\n";
        //            foreach ($useranswer as $ua){
        //                $node_ansHa = $dh->getNodeInfo($ua);
        //                $answerStr = $node_ansHa ['text'];
        //                $node_ansAr = explode('_',$ua);
        //                $node_ans_id =  $node_ansAr[1];
        //                $userAnswerStr .= "<li><link type=internal value=\"$node_ans_id\"> $answerStr</li>\n";
        //            }
        //            $userAnswerStr = "</ul>\n";
        //            $testo .= translateFN("Risposta: ").$userAnswerStr;
        //        }  else {
        //            if  ($exercise_type==3){
        //                $node_ansHa = $dh->getNodeInfo($useranswer);
        //                $answerStr = $node_ansHa ['text'];
        //                $node_ansAr = explode('_',$useranswer);
        //                $node_ans_id =  $node_ansAr[1];
        //                $userAnswer = "<link type=internal value=\"$node_ans_id\">$answerStr\n";
        //                $testo .= translateFN("Risposta: ").$useranswer;
        //            } else {
        //                $testo .= translateFN("Risposta: ").$useranswer;
        //            }
        //
        //        }
        //        $testo .= translateFN("Valutazione: ") . $this->rating;
        //        return $testo;
    }

    public function setStudentData($exercise, $id_student, $id_course_instance)
    {
        $exercise->setStudentId($id_student);
        $exercise->setCourseInstanceId($id_course_instance);
    }
}

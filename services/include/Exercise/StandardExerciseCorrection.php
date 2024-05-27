<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Services\Exercise\ExerciseCorrection;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class StandardExerciseCorrection extends ExerciseCorrection
{
    public function rateStudentAnswer($exercise, $student_answer, $id_student, $id_course_instance)
    {
        $correctness   = $exercise->getCorrectness($student_answer);

        $exercise->setStudentAnswer($student_answer);
        $exercise->setRating($correctness);
        parent::setStudentData($exercise, $id_student, $id_course_instance);
    }

    public function getMessageForStudent($username, $exercise)
    {
        $interaction = $exercise->getExerciseInteraction();

        //        $msg  = translateFN('Esercizio inviato') . '<br />';
        //        $msg .= translateFN("Titolo dell'esercizio: ") . $exercise->getTitle() . '<br />';
        //        $msg .= translateFN('Domanda: ') . $exercise->getText() . '<br />';
        //        $msg .= translateFN('La tua risposta: ') . $exercise->getAnswerText($exercise->getStudentAnswer()) . '<br />';

        $message_for_student = CDOMElement::create('div', 'id:message_for_student');

        $exercise_submitted = CDOMElement::create('div', 'id:exercise_submitted');
        $exercise_submitted->addChild(new CText(translateFN('Esercizio inviato.')));
        $message_for_student->addChild($exercise_submitted);

        $exercise_title = CDOMElement::create('div', 'id:exercise_title');
        //$label = CDOMElement::create('div','class:page_label');
        //$label->addChild(new CText(translateFN("Titolo dell'esercizio:")));
        $title = CDOMElement::create('div', 'id:title');
        $title->addChild(new CText($exercise->getTitle()));
        //$exercise_title->addChild($label);
        $exercise_title->addChild($title);
        $message_for_student->addChild($exercise_title);

        $exercise_question = CDOMElement::create('div', 'id:exercise_question');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('Domanda')));
        $question = CDOMElement::create('div', 'id:question');
        $question->addChild(new CText($exercise->getText()));
        $exercise_question->addChild($label);
        $exercise_question->addChild($question);
        $message_for_student->addChild($exercise_question);

        $student_answer = CDOMElement::create('div', 'id:student_answer');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('La tua risposta')));
        $answer = CDOMElement::create('div', 'id:answer');
        $answer->addChild(new CText($exercise->getAnswerText($exercise->getStudentAnswer())));
        $student_answer->addChild($label);
        $student_answer->addChild($answer);
        $message_for_student->addChild($student_answer);

        switch ($interaction) {
            case ADA_FEEDBACK_EXERCISE_INTERACTION: // with feedback
                //            $msg .= translateFN("Il commento dell'autore: ") . $exercise->getAuthorComment($exercise->getStudentAnswer()) . '<br />';
                $comment = CDOMElement::create('div', 'id:author_comment');
                $label = CDOMElement::create('div', 'class:page_label');
                $label->addChild(new CText(translateFN("Il commento dell'autore:")));
                $comment->addChild($label);
                $comment->addChild(new CText($exercise->getAuthorComment($exercise->getStudentAnswer())));
                $message_for_student->addChild($comment);
                break;

            case ADA_RATING_EXERCISE_INTERACTION: // with feedback and rating
                //            $msg .= translateFN('Punteggio ottenuto: ') . $exercise->getRating() . '<br />';
                //            $msg .= translateFN("Il commento dell'autore: ") . $exercise->getAuthorComment($exercise->getStudentAnswer()) . '<br />';
                $exercise_rating = CDOMElement::create('div', 'id:exercise_rating');
                $label = CDOMElement::create('div', 'class:page_label');
                $label->addChild(new CText(translateFN('Punteggio ottenuto:')));
                $rating = CDOMElement::create('div', 'id:rating');
                $rating->addChild(new CText($exercise->getRating()));
                $exercise_rating->addChild($label);
                $exercise_rating->addChild($rating);
                $message_for_student->addChild($exercise_rating);

                $comment = CDOMElement::create('div', 'id:author_comment');
                $label = CDOMElement::create('div', 'class:page_label');
                $label->addChild(new CText(translateFN("Il commento dell'autore:")));
                $comment->addChild($label);
                $comment->addChild(new CText($exercise->getAuthorComment($exercise->getStudentAnswer())));
                $message_for_student->addChild($comment);
                break;

            case ADA_BLIND_EXERCISE_INTERACTION: // no feedback
            default:
                break;
        }
        //        return $msg;
        return $message_for_student;
    }
}

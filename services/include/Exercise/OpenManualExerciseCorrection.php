<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class OpenManualExerciseCorrection extends ExerciseCorrection
{
    public function rateStudentAnswer($exercise, $student_answer, $id_student, $id_course_instance)
    {
        $exercise->setStudentAnswer($student_answer);
        $exercise->setRating(0);
        parent::setStudentData($exercise, $id_student, $id_course_instance);
    }

    public function getMessageForStudent($username, $exercise)
    {
        //        $msg  = translateFN('Esercizio inviato') . '<br />';
        //        $msg .= translateFN("Titolo dell'esercizio: ") . $exercise->getTitle() . '<br />';
        //        $msg .= translateFN('Domanda: ') . $exercise->getText() . '<br />';
        //        $msg .= translateFN('La tua risposta è: ') . $exercise->getStudentAnswer() . '<br />';
        //        return $msg;
        $message_for_student = CDOMElement::create('div', 'id:message_for_student');

        $exercise_submitted = CDOMElement::create('div', 'id:exercise_submitted');
        $exercise_submitted->addChild(new CText(translateFN('Hai inviato il seguente esercizio')));
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
        //$label = CDOMElement::create('div','class:page_label');
        //$label->addChild(new CText(translateFN("Testo dell'esercizio:")));
        $question = CDOMElement::create('div', 'id:question');
        $question->addChild(new CText($exercise->getText()));
        //$exercise_question->addChild($label);
        $exercise_question->addChild($question);
        $message_for_student->addChild($exercise_question);

        $student_answer = CDOMElement::create('div', 'id:student_answer');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('La tua risposta')));
        $answer = CDOMElement::create('div', 'id:answer');
        $answer->addChild(new CText($exercise->getStudentAnswer()));
        $student_answer->addChild($label);
        $student_answer->addChild($answer);
        $message_for_student->addChild($student_answer);

        return $message_for_student;
    }
}

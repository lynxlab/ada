<?php

use Lynxlab\ADA\Services\Exercise\ExerciseUtils;

use Lynxlab\ADA\Services\Exercise\ExerciseCorrection;

use Lynxlab\ADA\Services\Exercise\ClozeExerciseViewer;

use Lynxlab\ADA\Services\Exercise\ClozeExerciseCorrection;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class ClozeExerciseCorrection was declared with namespace Lynxlab\ADA\Services\Exercise. //

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class ClozeExerciseCorrection extends ExerciseCorrection
{
    public $author_comment;

    public function rateStudentAnswer($exercise, $student_answer, $id_student, $id_course_instance)
    {
        $exercise_data  = $exercise->getExerciseData();

        $comment = CDOMElement::create('div');

        $tokenized_exercise_text = [];
        $tokenized_exercise_text = ExerciseUtils::tokenizeString($exercise->getText());

        $rating = 0;
        foreach ($exercise_data as $a) {
            $posizione = $a['ordine'];
            if ($posizione > 0) {
                if (strcmp($a['nome'], $student_answer[$posizione]) == 0) {
                    $rating += $a['correttezza'];

                    if ($a['correttezza'] == 0) {
                        $css_classname = 'wrong_answer';
                    } else {
                        $css_classname = 'right_answer';
                    }
                    $comment_for_answer = CDOMElement::create('div', "class:$css_classname");
                    $comment_for_answer->addChild(new CText("{$a['nome']}: {$a['testo']}"));
                    $comment->addChild($comment_for_answer);
                }

                if (empty($student_answer[$posizione])) {
                    $student_answer[$posizione] = NO_ANSWER;
                }
                $tokenized_exercise_text[$posizione - 1][0] = $student_answer[$posizione];
            }
        }
        /*
         * Set user answer.
        */
        $string = '';
        foreach ($tokenized_exercise_text as $token) {
            $string .= $token[0] . $token[1];
        }

        $rating /= count($student_answer);

        $this->author_comment = $comment;

        $exercise->setStudentAnswer($string);
        $exercise->setRating($rating);
        parent::setStudentData($exercise, $id_student, $id_course_instance);
    }

    public function getMessageForStudent($username, $exercise)
    {
        $interaction = $exercise->getExerciseInteraction();
        $message_for_student = CDOMElement::create('div', 'id:message_for_student');

        $exercise_submitted = CDOMElement::create('div', 'id:exercise_submitted');
        $exercise_submitted->addChild(new CText(translateFN('Esercizio inviato.')));
        $message_for_student->addChild($exercise_submitted);
        $exercise_question = CDOMElement::create('div', 'id:exercise_question');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('Domanda')));
        $question = CDOMElement::create('div', 'id:question');
        $question->addChild(ClozeExerciseViewer::formatExerciseText($exercise));
        $exercise_question->addChild($label);
        $exercise_question->addChild($question);
        $message_for_student->addChild($exercise_question);

        $student_answer = CDOMElement::create('div', 'id:student_answer');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('La tua risposta')));
        $answer = CDOMElement::create('div', 'id:answer');
        $answer->addChild(ClozeExerciseViewer::formatStudentAnswer($exercise));
        $student_answer->addChild($label);
        $student_answer->addChild($answer);
        $message_for_student->addChild($student_answer);

        switch ($interaction) {
            case ADA_FEEDBACK_EXERCISE_INTERACTION: // with feedback
                $comment = CDOMElement::create('div', 'id:author_comment');
                $label = CDOMElement::create('div', 'class:page_label');
                $label->addChild(new CText(translateFN("Il commento dell'autore:")));
                $comment->addChild($label);
                $comment->addChild($this->author_comment);
                $message_for_student->addChild($comment);
                break;

            case ADA_RATING_EXERCISE_INTERACTION: // with feedback and rating
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
                $comment->addChild($this->author_comment);
                $message_for_student->addChild($comment);
                break;

            case ADA_BLIND_EXERCISE_INTERACTION: // no feedback
            default:
                break;
        }

        return $message_for_student;
    }
}

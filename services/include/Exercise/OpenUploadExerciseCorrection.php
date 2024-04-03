<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Upload\Functions\upload_file;

class OpenUploadExerciseCorrection extends ExerciseCorrection
{
    public function rateStudentAnswer($exercise, $student_answer, $id_student, $id_course_instance)
    {
        /*
         * upload del file
        */
        $file_uploaded = false;

        if ($_FILES['file_up']['error'] == UPLOAD_ERR_OK) {
            $filename          = $_FILES['file_up']['name'];
            $source            = $_FILES['file_up']['tmp_name'];

            $file_destination  = MEDIA_PATH_DEFAULT . $exercise->getAuthorId() . DIRECTORY_SEPARATOR;
            $file_destination .= $id_course_instance . "_" . $id_student . "_" . $exercise->getId() . "_";
            $file_destination .= $filename;

            $file_move = upload_file($_FILES, $source, ROOT_DIR . $file_destination);

            if ($file_move[0] == "ok") {
                $file_uploaded = true;
            }
        }

        /*
         * salvataggio della risposta studente
        */
        $exercise->setStudentAnswer($student_answer);
        if ($file_uploaded) {
            $replace = [" " => "_","\'" => "_"];
            $file_destination = strtr($file_destination, $replace);

            $exercise->setAttachment($file_destination);
        }
        $exercise->setRating(0);
        parent::setStudentData($exercise, $id_student, $id_course_instance);
    }

    public function getMessageForStudent($username, $exercise)
    {
        $message_for_student = CDOMElement::create('div', 'id:message_for_student');

        $exercise_submitted = CDOMElement::create('div', 'id:exercise_submitted');
        $exercise_submitted->addChild(new CText(translateFN('Esercizio inviato.')));
        $message_for_student->addChild($exercise_submitted);

        $exercise_title = CDOMElement::create('div', 'id:exercise_title');
        $title = CDOMElement::create('div', 'id:title');
        $title->addChild(new CText($exercise->getTitle()));
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
        $answer->addChild(new CText($exercise->getStudentAnswer()));
        $student_answer->addChild($label);
        $student_answer->addChild($answer);
        $message_for_student->addChild($student_answer);

        $attached_file = CDOMElement::create('div', 'id:attached_file');
        $label = CDOMElement::create('div', 'class:page_label');
        $label->addChild(new CText(translateFN('Il file inviato')));
        $attachment = CDOMElement::create('div', 'id:attachment');
        $attachment->addChild(new CText($exercise->getAttachment()));
        $attached_file->addChild($label);
        $attached_file->addChild($attachment);
        $message_for_student->addChild($attached_file);

        return $message_for_student;
    }
}

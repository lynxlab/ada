<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * @name OpenManualExerciseViewer
 * This class contains all of the methods needed to display an ADA OpenManual Exercise based on the user
 * that is seeing this exercise.
 * An ADA OpenManual Exercise is ...
 */
class OpenManualExerciseViewer extends ExerciseViewer
{
    public function getStudentForm($form_action, $exercise)
    {

        $div = CDOMElement::create('div');
        $div_text = CDOMElement::create('div', 'id:exercise_text');
        $div_text->addChild(new CText($exercise->getText()));
        $div->addChild($div_text);

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);

        $div_textarea = CDOMElement::create('div', 'id:answer');
        $label = CDOMElement::create('label', 'for:useranswer');
        $label->addChild(new CText(translateFN('Scrivi la tua risposta')));
        $div_textarea->addChild($label);
        $div_textarea->addChild(CDOMElement::create('textarea', 'id:useranswer, name:useranswer'));
        $form->addChild($div_textarea);

        $form->addChild(CDOMElement::create('hidden', 'id:op, name:op, value:answer'));

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Procedi');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));

        $form->addChild($div_buttons);
        $div->addChild($form);
        return $div->getHtml();
    }

    private function getExercise($exercise)
    {

        $div = CDOMElement::create('div');

        $div_title = CDOMElement::create('div', 'id:exercise_title');
        $div_title->addChild(new CText(translateFN('Esercizio:') . ' '));
        $div_title->addChild(new CText($exercise->getTitle()));
        $div->addChild($div_title);

        $div_date = CDOMElement::create('div', 'id:exercise_date');
        $div_date->addChild(new CText(translateFN('Data di svolgimento:') . ' '));
        $div_date->addChild(new CText($exercise->getExecutionDate()));
        $div->addChild($div_date);

        $div_question = CDOMElement::create('div', 'id:exercise_question');
        $div_question->addChild(new CText(translateFN('Domanda:') . ' '));
        $div_question->addChild(new CText($exercise->getText()));
        $div->addChild($div_question);

        $div_answer = CDOMElement::create('div', 'id:exercise_answer');
        $div_answer->addChild(new CText(translateFN('Risposta:') . ' '));
        $div_answer->addChild(new CText($exercise->getStudentAnswer()));
        $div->addChild($div_answer);

        $div_rating = CDOMElement::create('div', 'id:exercise_rating');
        $div_rating->addChild(new CText(translateFN('Punteggio:') . ' '));
        $div_rating->addChild(new CText($exercise->getRating()));
        $div->addChild($div_rating);

        return $div;
    }

    public function getExerciseHtml($exercise)
    {
        $div = $this->getExercise($exercise);
        return $div->getHtml();
    }

    public function getTutorForm($form_action, $exercise)
    {
        $div = $this->getExercise($exercise);

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);

        $div_rating = CDOMElement::create('div', 'id:exercise_rating');
        $label = CDOMElement::create('label', 'for:punteggio');
        $label->addChild(new CText(translateFN('Punteggio:') . ' '));
        $div_rating->addChild($label);
        $div_rating->addChild(CDOMElement::create('text', "id:punteggio,name:punteggio,value:{$exercise->getRating()}"));
        $form->addChild($div_rating);

        $div_textarea = CDOMElement::create('div', 'id:tutor_comment');
        $div_textarea->addChild(CDOMElement::create('textarea', 'id:comment, name:comment'));
        $form->addChild($div_textarea);

        $div_checkbox1 = CDOMElement::create('div', 'id:exercise_repeatable');
        $label1 = CDOMElement::create('label', 'for:ripetibile');
        $label1->addChild(new CText(translateFN('Ripetibile:') . ' '));
        $div_checkbox1->addChild($label1);
        $div_checkbox1->addChild(CDOMElement::create('checkbox', 'id:ripetibile, name:ripetibile'));
        $form->addChild($div_checkbox1);

        $div_checkbox2 = CDOMElement::create('div', 'id:exercise_sendmessage');
        $label2 = CDOMElement::create('label', 'for:messaggio');
        $label2->addChild(new CText(translateFN('Invia messaggio:') . ' '));
        $div_checkbox2->addChild($label2);
        $div_checkbox2->addChild(CDOMElement::create('checkbox', 'id:messaggio, name:messaggio'));
        $form->addChild($div_checkbox2);

        $form->addChild(CDOMElement::create('hidden', "name:student_id,value:{$exercise->getStudentId()}"));
        $form->addChild(CDOMElement::create('hidden', "name:course_instance,value:{$exercise->getCourseInstanceId()}"));

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Salva');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));
        $div_buttons->addChild(CDOMElement::create('reset'));

        $form->addChild($div_buttons);
        $div->addChild($form);
        return $div->getHtml();
    }

    public function getAuthorForm($form_action, $data = [])
    {
        $error_msg = "";
        if (isset($data['empty_field']) && $data['empty_field'] == true) {
            $error_msg = translateFN("Attenzione: campo non compilato!") . '<br />';
        }

        $div = CDOMElement::create('div');

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);

        $div_error_message = CDOMElement::create('div', 'class:error_msg');
        $div_error_message->addChild(new CText($error_msg));
        $form->addChild($div_error_message);

        $div_textarea1 = CDOMElement::create('div', 'id:exercise_question');
        $label1 = CDOMElement::create('label', 'for:question');
        $label1->addChild(new CText(translateFN('Testo domanda')));
        $div_textarea1->addChild($label1);
        $textarea1 = CDOMElement::create('textarea', 'id:question,name:question');
        $textarea1->addChild(new CText($question));
        $div_textarea1->addChild($textarea1);
        $form->addChild($div_textarea1);

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Procedi');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));
        $div_buttons->addChild(CDOMElement::create('reset'));

        $form->addChild($div_buttons);
        $div->addChild($form);
        return $div->getHtml();
    }

    public function getEditForm($form_action, $exercise)
    {
        $edit_exercise = CDOMElement::create('div', 'id:edit_exercise');
        $form = CDOMElement::create('form', 'id:edited_exercise, name:edited_exercise, method:post');
        $form->setAttribute('action', "$form_action&save=1");      /*
       * Exercise title
        */
        $exercise_title = CDOMElement::create('div', 'id:title');
        $label = CDOMElement::create('label', 'for:exercise_title');
        $label->addChild(new CText(translateFN("Titolo dell'esercizio")));
        $input_title = CDOMElement::create('div', 'id:exercise_title');
        $input_title->addChild(new CText($exercise->getTitle()));
        $mod_title = CDOMElement::create('a', "href:$form_action&edit=title");
        $mod_title->addChild(new CText(translateFN("Modifica")));
        $exercise_title->addChild($label);
        $exercise_title->addChild($input_title);
        $exercise_title->addChild($mod_title);

        /*
       * Exercise question
        */
        $exercise_question = CDOMElement::create('div', 'id:text');
        $label = CDOMElement::create('label', 'for:exercise_text');
        $label->addChild(new CText(translateFN("Testo dell'esercizio")));
        $exercise_text = CDOMElement::create('div', 'id:exercise_text');
        $exercise_text->addChild(new CText($exercise->getText()));
        $mod_text = CDOMElement::create('a', "href:$form_action&edit=text");
        $mod_text->addChild(new CText(translateFN("Modifica")));
        $exercise_question->addChild($label);
        $exercise_question->addChild($exercise_text);
        $exercise_question->addChild($mod_text);

        /*
       * Form buttons
        */
        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:edit_exercise, name:edit_exercise');
        $input_submit->setAttribute('value', translateFN('Salva modifiche'));
        $buttons->addChild($input_submit);

        $form->addChild($exercise_title);
        $form->addChild($exercise_question);
        $form->addChild($buttons);

        $edit_exercise->addChild($form);

        return $edit_exercise;
    }

    public function getEditFieldForm($form_action, $exercise, $exercise_field = null)
    {
        $edit_exercise = CDOMElement::create('div', 'id:edit_exercise');
        $form = CDOMElement::create('form', 'id:edited_exercise, name:edited_exercise, method:post');

        /*
       * Exercise title
        */
        if ($exercise_field == 'title') {
            $form->setAttribute('action', "$form_action&update=title");

            $exercise_title = CDOMElement::create('div', 'id:title');
            $label = CDOMElement::create('label', 'for:exercise_title');
            $label->addChild(new CText(translateFN("Titolo dell'esercizio")));
            $input_title = CDOMElement::create('text', 'id:exercise_title, name:exercise_title');
            $input_title->setAttribute('value', $exercise->getTitle());
            $exercise_title->addChild($label);
            $exercise_title->addChild($input_title);
            $form->addChild($exercise_title);
        } elseif ($exercise_field == 'text') {
            /*
             * Exercise question
             */
            $form->setAttribute('action', "$form_action&update=text");

            $exercise_question = CDOMElement::create('div', 'id:text');
            $label = CDOMElement::create('label', 'for:exercise_text');
            $label->addChild(new CText(translateFN("Testo dell'esercizio")));
            $exercise_text = CDOMElement::create('textarea', 'id:exercise_text, name:exercise_text');
            $exercise_text->addChild(new CText($exercise->getText()));
            $exercise_question->addChild($label);
            $exercise_question->addChild($exercise_text);
            $form->addChild($exercise_question);
        }

        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:edit_exercise, name:edit_exercise');
        $input_submit->setAttribute('value', translateFN('Salva modifiche'));
        $buttons->addChild($input_submit);
        $form->addChild($buttons);

        $edit_exercise->addChild($form);
        return $edit_exercise;
    }


    public function getExerciseReport($exerciseObj, $id_course_instance)
    {
        return $this->getStudentForm('exercise.php', $exerciseObj);
    }

    public function checkAuthorInput($post_data = [], &$data = [])
    {
        $empty_field = false;
        $i = (isset($data['answers'])) ? count($data['answers']) + 1 : 1;
        if (!isset($data['last_index'])) {
            $data['last_index'] = 1;
        }
        $last_index = $data['last_index'];

        if (!isset($data['empty_field'])) {   // DOMANDA
            if (isset($post_data['question']) && $post_data['question'] !== "") {
                $data['question'] = $post_data['question'];
            } else {
                $empty_field = true;
            }
        } elseif (isset($data['empty_field']) && $data['empty_field']) {
            // DOMANDA
            if (!isset($data['question']) && isset($post_data['question']) && $post_data['question'] !== "") {
                $data['question'] = $post_data['question'];
            } elseif (!isset($post_data['question']) || $post_data['question'] == "") {
                $empty_field = true;
            }
        }

        if ($empty_field) {
            $data['empty_field'] = true;
        } else {
            unset($data['empty_field']);
            $data['last_index']++;
        }

        return !$empty_field;
    }
}

<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Services\Exercise\ExerciseUtils;
use Lynxlab\ADA\Services\Exercise\ExerciseViewer;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * @name ClozeExerciseViewer
 * This class contains all of the methods needed to display an ADA Cloze Exercise based on the user
 * that is seeing this exercise.
 * An ADA Cloze Exercise is ...
 */
class ClozeExerciseViewer extends ExerciseViewer
{
    public function getStudentForm($form_action, $exercise)
    {

        $data = ExerciseUtils::tokenizeString($exercise->getText());

        $hidden_words = $exercise->getExerciseData();

        foreach ($hidden_words as $answer) {
            if ($answer['ordine'] != 0) {
                $lista['nascoste'][] = [
                    'id_nodo'     => $answer['id_nodo'],
                    'posizione'   => $answer['ordine'],
                    'parola'      => $answer['nome'],
                    'correttezza' => $answer['correttezza'],
                ];
            } else {
                $lista['altre'][] = $answer['nome'];
            }
        }

        switch ($exercise->getExerciseSimplification()) {
            case ADA_SIMPLIFY_EXERCISE_SIMPLICITY:
                return $this->viewSimplifiedExercise($lista, $data, $form_action);

            case ADA_MEDIUM_EXERCISE_SIMPLICITY:
                return $this->viewMediumExercise($lista, $data, $form_action);

            case ADA_NORMAL_EXERCISE_SIMPLICITY:
            default:
                return $this->viewNormalExercise($lista, $data, $form_action);
        }
    }

    private function getExercise($exercise)
    {
        $div = CDOMElement::create('div');

        $div_title = CDOMElement::create('div', 'id:exercise_title');
        $div_title->addChild(new CText($exercise->getTitle()));
        $div->addChild($div_title);

        $div_date = CDOMElement::create('div', 'id:exercise_date');
        $div_date->addChild(new CText(translateFN('Data di svolgimento') . ' '));
        $div_date->addChild(new CText($exercise->getExecutionDate()));
        $div->addChild($div_date);

        $div_question = CDOMElement::create('div', 'id:exercise_text');
        //$div_question->addChild(new CText(translateFN('Domanda')));
        $div_question->addChild($this->formatExerciseText($exercise));
        $div->addChild($div_question);

        $div_answer = CDOMElement::create('div', 'id:student_answer');
        $div_answer->addChild(new CText(translateFN('Risposta') . ' '));
        $div_answer2 = CDOMElement::create('div', 'id:answer');
        $div_answer2->addChild($this->formatStudentAnswer($exercise));
        $div_answer->addChild($div_answer2);
        $div->addChild($div_answer);

        $div_choices = CDOMElement::create('div', 'id:exercise_choices');
        $div_choices->addChild(new CText(translateFN('Scelte possibili:') . ' '));
        $div_choices->addChild($this->getHiddenWords($exercise));
        $div->addChild($div_choices);

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
            $error_msg   = translateFN("Attenzione: campo non compilato!") . "<br />";
            $answer      = parent::fillFieldWithData('last_answer', $data);
            $comment     = parent::fillFieldWithData('last_comment', $data);
            $hide        = parent::fillFieldWithData('last_hide', $data);
            $correctness = parent::fillFieldWithData('last_correctness', $data);
        }
        $question    = parent::fillFieldWithData('question', $data);

        $div = CDOMElement::create('div');

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);

        $div_error_message = CDOMElement::create('div', 'class:error_msg');
        $div_error_message->addChild(new CText($error_msg));
        $form->addChild($div_error_message);

        $div_textarea1 = CDOMElement::create('div', 'id:exercise_question');
        $label1 = CDOMElement::create('label', 'for:question');
        $label1->addChild(new CText(translateFN('Frase completa:')));
        $div_textarea1->addChild($label1);
        $textarea1 = CDOMElement::create('textarea', 'id:question,name:question');
        $textarea1->addChild(new CText($question));
        $div_textarea1->addChild($textarea1);
        $form->addChild($div_textarea1);

        $div_textarea2 = CDOMElement::create('div', 'id:exercise_answer');
        $label2 = CDOMElement::create('label', 'for:answer');
        $label2->addChild(new CText(translateFN('Parola da nascondere:')));
        $div_textarea2->addChild($label2);
        $textarea2 = CDOMElement::create('textarea', 'id:answer,name:answer');
        $textarea2->addChild(new CText($answer));
        $div_textarea2->addChild($textarea2);
        $form->addChild($div_textarea2);

        $div_textarea3 = CDOMElement::create('div', 'id:exercise_comment');
        $label3 = CDOMElement::create('label', 'for:comment');
        $label3->addChild(new CText(translateFN('Commento alla risposta')));
        $div_textarea3->addChild($label3);
        $textarea3 = CDOMElement::create('textarea', 'id:comment,name:comment');
        $textarea3->addChild(new CText($comment));
        $div_textarea3->addChild($textarea3);
        $form->addChild($div_textarea3);

        $div_position = CDOMElement::create('div', 'id:exercise_position');
        $label4 = CDOMElement::create('label', 'for:hide');
        $label4->addChild(new CText(translateFN('Posizione:')));
        $div_position->addChild($label4);
        $div_position->addChild(CDOMElement::create('text', "id:hide,name:hide,value:$hide"));
        $form->addChild($div_position);

        $div_correctness = CDOMElement::create('div', 'id:exercise_correctness');
        $label4 = CDOMElement::create('label', 'for:hide');
        $label4->addChild(new CText(translateFN('Correttezza:')));
        $div_correctness->addChild($label4);
        $div_correctness->addChild(CDOMElement::create('text', "id:correctness,name:correctness,value:$correctness"));
        $form->addChild($div_correctness);

        $div_stop = CDOMElement::create('div', 'id:exercise_ended');
        $div_text = CDOMElement::create('div');
        $div_text->addChild(new CText(translateFN('Finito?')));
        $div_stop->addChild($div_text);
        $label5 = CDOMElement::create('label', 'for:finito');
        $label5->addChild(new CText('Si'));
        $div_stop->addChild($label5);
        $radio1 = CDOMElement::create('radio', 'name:finito,value:1,checked:true');
        $div_stop->addChild($radio1);
        $label6 = CDOMElement::create('label', 'for:finito');
        $label6->addChild(new CText('No'));
        $div_stop->addChild($label6);
        $radio2 = CDOMElement::create('radio', 'name:finito,value:0,checked:false');

        $div_stop->addChild($radio2);
        $form->addChild($div_stop);

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
        $title = CDOMElement::create('div', 'id:exercise_title');
        $title->addChild(new CText($exercise->getTitle()));
        $mod_title = CDOMElement::create('a', "href:$form_action&edit=title");
        $mod_title->addChild(new CText(translateFN('[Modifica]')));
        $exercise_title->addChild($label);
        $exercise_title->addChild($title);
        $exercise_title->addChild($mod_title);

        /*
       * Exercise text
        */
        $text = [];

        $tokenized_text = ExerciseUtils::tokenizeString($exercise->getText());
        $text = [];
        foreach ($tokenized_text as $t) {
            $text[] = new CText($t[0] . $t[1]);
        }

        $hidden_words = $exercise->getExerciseData();
        $hidden_word_position = [];

        foreach ($hidden_words as $hidden_word) {
            $position = $hidden_word['ordine'] - 1;
            if (!isset($hidden_word_position[$position])) {
                $hidden_word_position[$position] = $position;
                $span = CDOMElement::create('div');
                $link = CDOMElement::create('a', "href:$form_action&edit=$position");
                $link->addChild(new CText(translateFN('[Modifica]')));
                $span->addChild($text[$position]);
                $span->addChild($link);
                $text[$position] = $span;
            }
        }

        $exercise_question = CDOMElement::create('div', 'id:exercise_text');

        foreach ($text as $parola) {
            //$string .= $parola . ' ';
            $exercise_question->addChild($parola);
        }





        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:edit_exercise, name:edit_exercise');
        $input_submit->setAttribute('value', translateFN('Modifica esercizio'));
        $buttons->addChild($input_submit);

        $form->addChild($exercise_title);
        $form->addChild($exercise_question);
        //$form->addChild($answers);
        $form->addChild($buttons);

        $edit_exercise->addChild($form);
        return $edit_exercise;
    }

    public function getEditFieldForm($form_action, $exercise, $exercise_field = null)
    {
        $edit_exercise = CDOMElement::create('div');
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
            $label = CDOMElement::create('label', 'for:question');
            $label->addChild(new CText(translateFN("Testo dell'esercizio")));
            $exercise_text = CDOMElement::create('textarea', 'id:exercise_text, name:exercise_text');
            $exercise_text->addChild(new CText($exercise->getText()));
            $exercise_question->addChild($label);
            $exercise_question->addChild($exercise_text);
            $form->addChild($exercise_question);
        } elseif (count($exercise_field) > 0 && is_numeric($exercise_field)) {
            $form->setAttribute('action', "$form_action&update=$exercise_field");
            $position = $exercise_field + 1;
            $exercise_data = $exercise->getExerciseData();
            $hidden_words_in_this_place = [];

            foreach ($exercise_data as $hidden_word) {
                if ($hidden_word['ordine'] == $position && $hidden_word['correttezza'] < ADA_MAX_SCORE) {
                    $node = $hidden_word['id_nodo'];

                    $possible_answer = CDOMElement::create('div', 'class:possible_answer');

                    $answer      = CDOMElement::create('text', "id:{$node}_answer, name:{$node}_answer");
                    $answer->setAttribute('value', $hidden_word['nome']);
                    $possible_answer->addChild($answer);

                    $comment     = CDOMElement::create('text', "id:{$node}_comment, name:{$node}_comment");
                    $comment->setAttribute('value', $hidden_word['testo']);
                    $possible_answer->addChild($comment);

                    $correctness = CDOMElement::create('text', "id:{$node}_correctness, name:{$node}_correctness");
                    $correctness->setAttribute('value', $hidden_word['correttezza']);
                    $possible_answer->addChild($correctness);

                    $delete = CDOMElement::create('a', "href:$form_action&delete={$node}");
                    $delete->addChild(new CText(translateFN('[Elimina]')));
                    $possible_answer->addChild($delete);

                    $form->addChild($possible_answer);
                }
            }
        }

        $add_answer = CDOMElement::create('div');
        $link = CDOMElement::create('a', "href:$form_action&edit=$position&add=1");
        $link->addChild(new CText(translateFN('Aggiungi risposta')));
        $add_answer->addChild($link);
        $form->addChild($add_answer);

        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:edit_exercise, name:edit_exercise');
        $input_submit->setAttribute('value', translateFN('Salva modifiche'));
        $buttons->addChild($input_submit);
        $form->addChild($buttons);

        $edit_exercise->addChild($form);
        return $edit_exercise;
    }

    public function getAddAnswerForm($form_action, $exercise, $field)
    {
        $add_answer = CDOMElement::create('div');
        $form = CDOMElement::create('form', 'id:added_answer, name:added_answer, method:post');
        $form->setAttribute('action', "$form_action&add_answer_to={$exercise->getId()}");

        $new_answer = CDOMElement::create('div', 'class:possible_answer');

        $answer      = CDOMElement::create('text', 'id:answer, name:answer');
        $answer->setAttribute('value', translateFN('Testo della risposta'));
        $new_answer->addChild($answer);

        $comment     = CDOMElement::create('text', 'id:comment, name:comment');
        $comment->setAttribute('value', translateFN('Commento alla risposta'));
        $new_answer->addChild($comment);

        $correctness = CDOMElement::create('text', 'id:correctness, name:correctness');
        $correctness->setAttribute('value', translateFN('Correttezza della risposta'));
        $new_answer->addChild($correctness);

        $position = CDOMElement::create('hidden', 'id:position, name:position');
        $position->setAttribute('value', $field);
        $new_answer->addChild($position);

        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:add_answer, name:add_answer');
        $input_submit->setAttribute('value', translateFN('Aggiungi questa risposta'));
        $buttons->addChild($input_submit);

        $form->addChild($new_answer);
        $form->addChild($buttons);

        $add_answer->addChild($form);
        return $add_answer;
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
            // RISPOSTA
            if (isset($post_data['answer']) && $post_data['answer'] !== "") {
                $data['answers'][$i]['answer'] = $post_data['answer'];
                $data['last_answer'] = $post_data['answer'];
            } else {
                $empty_field = true;
            }
            // NASCONDI PAROLA
            if (isset($post_data['hide']) && $post_data['hide'] !== "") {
                $data['answers'][$i]['hide'] = $post_data['hide'];
                $data['last_hide'] = $post_data['hide'];
            } else {
                $empty_field = true;
            }

            // CORRETTEZZA
            if (isset($post_data['correctness']) && $post_data['correctness'] !== "") {
                $data['answers'][$i]['correctness'] = $post_data['correctness'];
                $data['last_correctness'] = $post_data['correctness'];
            } else {
                $empty_field = true;
            }
            // COMMENTO
            if (isset($post_data['comment']) && $post_data['comment'] !== "") {
                $data['answers'][$i]['comment'] = $post_data['comment'];
                $data['last_comment'] = $post_data['comment'];
            }
        } elseif (isset($data['empty_field']) && $data['empty_field']) {
            // DOMANDA
            if (!isset($data['question']) && isset($post_data['question']) && $post_data['question'] !== "") {
                $data['question'] = $post_data['question'];
            } elseif (!isset($post_data['question']) || $post_data['question'] == "") {
                $empty_field = true;
            }
            // RISPOSTA
            if (!isset($data['answers'][$last_index]['answer']) && isset($post_data['answer']) && $post_data['answer'] !== "") {
                $data['answers'][$last_index]['answer'] = $post_data['answer'];
                $data['last_answer'] = $post_data['answer'];
            } elseif (!isset($post_data['answer']) || $post_data['answer'] == "") {
                $empty_field = true;
            }
            // NASCONDI PAROLA
            if (!isset($data['answers'][$last_index]['hide']) && isset($post_data['hide']) && $post_data['hide'] !== "") {
                $data['answers'][$last_index]['hide'] = $post_data['hide'];
                $data['last_hide'] = $post_data['hide'];
            } elseif (!isset($post_data['hide']) || $post_data['hide'] == "") {
                $empty_field = true;
            }
            // CORRETTEZZA
            if (!isset($data['answers'][$last_index]['correctness']) && isset($post_data['correctness']) && $post_data['correctness'] !== "") {
                $data['answers'][$last_index]['correctness'] = $post_data['correctness'];
                $data['last_correctness'] = $post_data['correctness'];
            } elseif (!isset($post_data['correctness']) || $post_data['correctness'] == "") {
                $empty_field = true;
            }
            // COMMENTO
            if (!isset($data['answers'][$last_index]['comment']) && isset($post_data['comment']) && $post_data['comment'] !== "") {
                $data['answers'][$last_index]['comment'] = $post_data['comment'];
                $data['last_comment'] = $post_data['comment'];
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

    /*
     * Private methods
    */

    // Used by getStudentForm
    public function viewSimplifiedExercise($lista, $exercisetext, $form_action)
    {

        $posizione = [];
        foreach ($lista['nascoste'] as $item) {
            $posizione[$item['posizione']][$item['parola']] = $item['parola'];
        }

        $div = CDOMElement::create('div');

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);
        $cloze_text = CDOMElement::create('div', 'id:cloze_exercise_text');

        $words_count = count($exercisetext);
        for ($i = 0; $i < $words_count; $i++) {
            if (isset($posizione[$i + 1])) {
                $p = $i + 1;
                $div_select = CDOMElement::create('div');

                $empty_option = ['---' => '---'];
                $options = array_merge($empty_option, parent::shuffleList($posizione[$p]));

                $select = BaseHtmlLib::selectElement("id:useranswer[$p],name:useranswer[$p], size:0", $options);
                $div_select->addChild($select);
                $div_select->addChild(new CText($exercisetext[$i][1]));
                $cloze_text->addChild($div_select);
            } else {
                $word = new CText($exercisetext[$i][0] . $exercisetext[$i][1]);
                $cloze_text->addChild($word);
            }
        }

        $form->addChild($cloze_text);

        $form->addChild(CDOMElement::create('hidden', 'id:op, name:op, value:answer'));

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Procedi');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));
        $div_buttons->addChild(CDOMElement::create('reset'));
        $form->addChild($div_buttons);

        $div->addChild($form);
        return $div->getHtml();
    }

    public function viewMediumExercise($lista, $exercisetext, $form_action)
    {
        $posizione = [];

        foreach ($lista['nascoste'] as $parola) {
            if ($parola['correttezza'] == ADA_MAX_SCORE) {
                $posizione[$parola['posizione']] = strlen($parola['parola']);
            }
        }
        $div = CDOMElement::create('div');

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);

        $words_count = count($exercisetext);
        for ($i = 0; $i < $words_count; $i++) {
            if (isset($posizione[$i + 1])) {
                $p = $i + 1;
                $div_text = CDOMElement::create('div');
                $text_input = CDOMElement::create('text', "id:useranswer[$p],name:useranswer[$p], maxlength:{$posizione[$p]}, size:{$posizione[$p]}");
                $div_text->addChild($text_input);
                $div_text->addChild(new CText($exercisetext[$i][1]));
                $form->addChild($div_text);
            } else {
                $word = new CText($exercisetext[$i][0] . $exercisetext[$i][1]);
                $form->addChild($word);
            }
        }

        $form->addChild(CDOMElement::create('hidden', 'id:op, name:op, value:answer'));

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Procedi');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));
        $div_buttons->addChild(CDOMElement::create('reset'));
        $form->addChild($div_buttons);

        $div->addChild($form);
        return $div->getHtml();
    }

    public function viewNormalExercise($lista, $exercisetext, $form_action)
    {
        // tokenizzare il testo dell'esercizio e stampare l'esercizio
        // con le posizioni delle parole nascoste contenenti degli input text senza
        // dimensione massima fissata
        $posizione = [];
        foreach ($lista['nascoste'] as $parola) {
            $posizione[$parola['posizione']] = 1;
        }

        $div = CDOMElement::create('div');

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);
        $text = CDOMElement::create('div', 'id:exercise_text');
        $form->addChild($text);
        $words_count = count($exercisetext);
        for ($i = 0; $i < $words_count; $i++) {
            if (isset($posizione[$i + 1])) {
                $p = $i + 1;
                $div_text = CDOMElement::create('div', 'class:hidden_word');
                $text_input = CDOMElement::create('text', "id:useranswer[$p],name:useranswer[$p]");
                $div_text->addChild($text_input);
                $div_text->addChild(new CText($exercisetext[$i][1]));
                $text->addChild($div_text);
            } else {
                $word = new CText($exercisetext[$i][0] . $exercisetext[$i][1]);
                $text->addChild($word);
            }
        }

        $form->addChild(CDOMElement::create('hidden', 'id:op, name:op, value:answer'));

        $div_buttons = CDOMElement::create('div', 'id:buttons');
        $button_text = translateFN('Procedi');
        $div_buttons->addChild(CDOMElement::create('submit', "id:button,name:button,value:$button_text"));
        $div_buttons->addChild(CDOMElement::create('reset'));
        $form->addChild($div_buttons);

        $div->addChild($form);
        return $div->getHtml();
    }

    // Used by getTutorForm

    public static function formatExerciseText($exercise)
    {
        $text = [];
        $text = ExerciseUtils::tokenizeString($exercise->getText());

        $hidden_words = $exercise->getExerciseData();
        $hidden_word_position = [];

        foreach ($hidden_words as $hidden_word) {
            $position = $hidden_word['ordine'] - 1;
            if (!isset($hidden_word_position[$position])) {
                $hidden_word_position[$position] = $position;
                //8gennaio
                //$text[$position] = '<span class="RIGHT_ANSWER">*'.$text[$position].'*</span>';
                // vito 4 feb 2009
                //$text[$position][0] = '<span class="RIGHT_ANSWER">*'.$text[$position][0].'*</span>';
                $span = CDOMElement::create('span', 'class:RIGHT_ANSWER');
                $span->addChild(new CText($text[$position][0]));
                $text[$position][0] = $span->getHtml();
            }
        }

        $string = '';
        $span = CDOMElement::create('span');
        foreach ($text as $parola) {
            //8gennaio2009
            //    $string .= $parola . ' ';
            //vito 4 feb 2009
            //$string .= $parola[0].$parola[1];
            $span->addChild(new CText($parola[0] . $parola[1]));
        }
        //vito 4 feb 2009
        //return $string;
        return $span;
    }

    public static function formatStudentAnswer($exercise)
    {
        $text = [];
        $text = ExerciseUtils::tokenizeString($exercise->getText());

        $student_answer = [];
        $student_answer = ExerciseUtils::tokenizeString($exercise->getStudentAnswer());

        $hidden_words = $exercise->getExerciseData();
        $hidden_word_position = [];

        $posizione = 0;
        foreach ($hidden_words as $hidden_word) {
            $position = $hidden_word['ordine'] - 1;
            if (!isset($hidden_word_position[$position])) {
                $hidden_word_position[$position] = $position;
            }
        }

        foreach ($hidden_word_position as $position) {
            if ($student_answer[$position][0] == $text[$position][0]) {
                $class_name = 'right_answer';
                //vito 4 feb 2009
                //$delimiter  = '*';
            } else {
                $class_name = 'wrong_answer';
                //vito 4 feb 2009
                //$delimiter  = '#';
            }
            //vito 4 feb 2009
            //$student_answer[$position][0] = '<span class="'.$class_name.'">'.$delimiter.$student_answer[$position][0].$delimiter.'</span>';
            $span = CDOMElement::create('span', "class:$class_name");
            $span->addChild(new CText($student_answer[$position][0]));
            $student_answer[$position][0] = $span->getHtml();
        }
        //vito 4 feb 2009
        //$string = '';
        $span = CDOMElement::create('span');
        foreach ($student_answer as $a) {
            //vito 4 feb 2009
            //    $string .= $a[0].$a[1];
            $span->addChild(new CText($a[0] . $a[1]));
        }
        //vito 4 feb 2009
        //return $string;
        return $span;
    }

    public function getHiddenWords($exercise)
    {
        $text = [];
        $text = ExerciseUtils::tokenizeString($exercise->getText());

        $hidden_words = $exercise->getExerciseData();

        $parole_nascoste = [];
        foreach ($hidden_words as $word) {
            $posizione = $word['ordine'];
            $parole_nascoste[$posizione][] = $word['nome'];
        }


        $output = CDOMElement::create('div');
        foreach ($parole_nascoste as $posizione => $array) {
            $line = CDOMElement::create('div');
            $line->addChild(new CText($text[$posizione - 1][0] . ': '));
            foreach ($array as $word) {
                if ($word != $text[$posizione - 1][0]) {
                    $line->addChild(new CText($word . ' '));
                }
            }
            $output->addChild($line);
        }
        //vito 4 feb 2009
        //return $output->getHtml();
        return $output;
    }
}

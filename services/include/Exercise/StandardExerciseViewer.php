<?php

namespace Lynxlab\ADA\Services\Exercise;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Services\Exercise\ExerciseDAO;
use Lynxlab\ADA\Services\Exercise\ExerciseViewer;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * @name StandardExerciseViewer
 * This class contains all of the methods needed to display an ADA Standard Exercise based on the user
 * that is seeing this exercise.
 * An ADA Standard Exercise is a multiple choice exercise...
 */
class StandardExerciseViewer extends ExerciseViewer
{
    public function getStudentForm($form_action, $exercise)
    {
        $answers = $exercise->getExerciseData();

        $div = CDOMElement::create('div');
        /*
        $div_title = CDOMElement::create('div','id:exercise_title');
        $div_title->addChild(new CText(translateFN('Esercizio:')));
        $div_title->addChild(new CText($exercise->getTitle()));
        $div->addChild($div_title);
        */
        $div_text = CDOMElement::create('div', 'id:exercise_text');
        $div_text->addChild(new CText($exercise->getText()));
        $div->addChild($div_text);

        $form = CDOMElement::create('form', 'id:esercizio, name:esercizio, method:POST');
        $form->setAttribute('action', $form_action);
        foreach ($answers as $answer) {
            $div_choice = CDOMElement::create('div', 'class:possible_answer');
            //$label = CDOMElement::create('label', 'for:useranswer');
            //$label->addChild(new CText($answer['nome']));
            $radio = CDOMElement::create('radio', "id:useranswer,name:useranswer,value:{$answer['id_nodo']}");
            //$div_choice->addChild($label);
            $div_choice->addChild($radio);
            $possible_answer = CDOMElement::create('span');
            $possible_answer->addChild(new CText($answer['nome']));
            $div_choice->addChild($possible_answer);
            $form->addChild($div_choice);
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

    private function getExercise($exercise)
    {

        $div = CDOMElement::create('div');

        $div_title = CDOMElement::create('div', 'id:exercise_title');
        $div_title->addChild(new CText(translateFN('Esercizio:')));
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
        $div_answer->addChild(new CText($exercise->getAnswerText()));
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

        $div_textarea = CDOMElement::create('div', 'id:tutor_comment');
        $div_textarea->addChild(CDOMElement::create('textarea', 'id:comment, name:comment'));
        $form->addChild($div_textarea);

        $div_checkbox1 = CDOMElement::create('div', 'id:exercise_repeatable');
        $label1 = CDOMElement::create('label', 'for:ripetibile');
        $label1->addChild(new CText(translateFN('Ripetibile:')));
        $div_checkbox1->addChild($label1);
        $div_checkbox1->addChild(CDOMElement::create('checkbox', 'id:ripetibile, name:ripetibile'));
        $form->addChild($div_checkbox1);

        $div_checkbox2 = CDOMElement::create('div', 'id:exercise_sendmessage');
        $label2 = CDOMElement::create('label', 'for:messaggio');
        $label2->addChild(new CText(translateFN('Invia messaggio:')));
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
            $error_msg   = translateFN("Attenzione: campo non compilato!") . '<br />';
            $answer      = parent::fillFieldWithData('last_answer', $data);
            $comment     = parent::fillFieldWithData('last_comment', $data);
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
        $label2->addChild(new CText(translateFN('Testo risposta')));
        $div_textarea2->addChild($label2);
        $textarea2 = CDOMElement::create('textarea', 'id:answer,name:answer');
        if (!isset($answer)) {
            $answer = '';
        }
        $textarea2->addChild(new CText($answer));
        $div_textarea2->addChild($textarea2);
        $form->addChild($div_textarea2);

        $div_textarea3 = CDOMElement::create('div', 'id:exercise_comment');
        $label3 = CDOMElement::create('label', 'for:comment');
        $label3->addChild(new CText(translateFN('Commento alla risposta')));
        $div_textarea3->addChild($label3);
        $textarea3 = CDOMElement::create('textarea', 'id:comment,name:comment');
        if (!isset($comment)) {
            $comment = '';
        }
        $textarea3->addChild(new CText($comment));
        $div_textarea3->addChild($textarea3);
        $form->addChild($div_textarea3);

        $div_correctness = CDOMElement::create('div', 'id:exercise_correctness');
        $label4 = CDOMElement::create('label', 'for:hide');
        $label4->addChild(new CText(translateFN('Correttezza:')));
        $div_correctness->addChild($label4);
        if (!isset($correctness)) {
            $correctness = '';
        }
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
        $form->setAttribute('action', "$form_action&save=1");
        /*
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
       * Exercise question
        */
        $exercise_question = CDOMElement::create('div', 'id:text');
        $label = CDOMElement::create('label', 'for:exercise_text');
        $label->addChild(new CText(translateFN("Testo dell'esercizio")));
        $text = CDOMElement::create('div', 'id:exercise_text');
        $text->addChild(new CText($exercise->getText()));
        $mod_text = CDOMElement::create('a', "href:$form_action&edit=text");
        $mod_text->addChild(new CText(translateFN('[Modifica]')));
        $exercise_question->addChild($label);
        $exercise_question->addChild($text);
        $exercise_question->addChild($mod_text);
        /*
       * Exercise data
        */
        $exercise_data  = $exercise->getExerciseData();

        //$answers = CDOMElement::create('div','id:answers');
        $table = CDOMElement::create('table');
        $thead  = CDOMElement::create('thead');
        $col1  = CDOMElement::create('tr');
        $col1->addChild(new CText(translateFN('Possibile risposta')));
        $col2  = CDOMElement::create('tr');
        $col2->addChild(new CText(translateFN('Commento')));
        $col3  = CDOMElement::create('tr');
        $col3->addChild(new CText(translateFN('Correttezza')));
        $thead->addChild($col1);
        $thead->addChild($col2);
        $thead->addChild($col3);
        $table->addChild($thead);
        $answers = CDOMElement::create('tbody');
        foreach ($exercise_data as $answer_id => $answer_data) {
            //$exercise_answer = CDOMElement::create('div');
            $exercise_answer = CDOMElement::create('tr');

            //$answer = CDOMElement::create('div');
            $answer = CDOMElement::create('td');

            //   $label1 = CDOMElement::create('label',"for:{$answer_id}_answer");
            //   $label1->addChild(new CText(translateFN("Possibile risposta")));
            $answer_text = CDOMElement::create('div', 'id:answer_text');
            $answer_text->addChild(new CText($answer_data['nome']));
            //    $answer->addChild($label1);
            $answer->addChild($answer_text);

            //$comment = CDOMElement::create('div');
            $comment = CDOMElement::create('td');
            //   $label2 = CDOMElement::create('label',"for:{$answer_id}_comment");
            //   $label2->addChild(new CText(translateFN("Commento")));
            //$textarea2 = CDOMElement::create('textarea',"id:{$answer_id}_comment, name:{$answer_id}_comment");
            //$textarea2->addChild(new CText($answer_data['testo']));
            $answer_comment = CDOMElement::create('div', 'id:answer_comment');
            $answer_comment->addChild(new CText($answer_data['testo']));
            //  $comment->addChild($label2);
            $comment->addChild($answer_comment);

            //$correctness = CDOMElement::create('div');
            $correctness = CDOMElement::create('td');

            //  $label3 = CDOMElement::create('label',"for:{$answer_id}_correctness");
            //  $label3->addChild(new CText(translateFN("Correttezza")));
            //$textarea3 = CDOMElement::create('textarea',"id:{$answer_id}_correctness, name:{$answer_id}_correctness");
            //$textarea3->addChild(new CText($answer_data['correttezza']));
            $answer_correctness = CDOMElement::create('div', 'id:answer_correctness');
            $answer_correctness->addChild(new CText($answer_data['correttezza']));
            //  $correctness->addChild($label3);
            $correctness->addChild($answer_correctness);

            $actions = CDOMElement::create('td');
            $modify = CDOMElement::create('a', "href:$form_action&edit={$answer_id}");
            $modify->addChild(new CText(translateFN('[Modifica]')));
            $delete = CDOMElement::create('a', "href:$form_action&delete={$answer_id}");
            $delete->addChild(new CText(translateFN('[Elimina]')));
            $actions->addChild($modify);
            $actions->addChild($delete);

            $exercise_answer->addChild($answer);
            $exercise_answer->addChild($comment);
            $exercise_answer->addChild($correctness);
            $exercise_answer->addChild($actions);

            $answers->addChild($exercise_answer);
        }
        $table->addChild($answers);

        $add_answer = CDOMElement::create('div');
        $link = CDOMElement::create('a', "href:$form_action&edit=0&add=1");
        $link->addChild(new CText(translateFN('Aggiungi risposta')));
        $add_answer->addChild($link);


        $buttons = CDOMElement::create('div');
        $input_submit = CDOMElement::create('submit', 'id:edit_exercise, name:edit_exercise');
        $input_submit->setAttribute('value', translateFN('Modifica esercizio'));
        $buttons->addChild($input_submit);

        $form->addChild($exercise_title);
        $form->addChild($exercise_question);
        $form->addChild($table);
        $form->addChild($add_answer);
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
        } elseif (count($exercise_field) > 0) {
            $node = $exercise_field;

            $form->setAttribute('action', "$form_action&update=$node");

            $possible_answer = CDOMElement::create('div', 'id:possible_answer');
            $label = CDOMElement::create('label', "for:{$node}_answer");
            $label->addChild(new CText(translateFN('Risposta possibile: ')));
            $answer = CDOMElement::create('text', "id:{$node}_answer,name:{$node}_answer");
            $answer->setAttribute('value', $exercise->getExerciseDataAnswerForItem($node));
            $possible_answer->addChild($label);
            $possible_answer->addChild($answer);
            $form->addChild($possible_answer);

            $answer_rating = CDOMElement::create('div', 'id:answer_rating');
            $label = CDOMElement::create('label', "for:{$node}_correctness");
            $label->addChild(new CText(translateFN('Punteggio associato: ')));
            $rating = CDOMElement::create('text', "id:{$node}_correctness,name:{$node}_correctness");
            $rating->setAttribute('value', $exercise->getExerciseDataCorrectnessForItem($node));
            $answer_rating->addChild($label);
            $answer_rating->addChild($rating);
            $form->addChild($answer_rating);

            $author_comment = CDOMElement::create('div', 'id:author_comment');
            $label = CDOMElement::create('label', "for:{$node}_comment");
            $label->addChild(new CText(translateFN('Commento associato: ')));
            $comment = CDOMElement::create('text', "id:{$node}_comment,name:{$node}_comment");
            $comment->setAttribute('value', $exercise->getExerciseDataAuthorCommentForItem($node));
            $author_comment->addChild($label);
            $author_comment->addChild($comment);
            $form->addChild($author_comment);


            //        $data = explode('_', $exercise_field);
            //        $node = $data[0].'_'.$data[1];
            //        $field = $data[2];
            //        //echo $node .'<br />' .$field;
            //
            //        $textarea = CDOMElement::create('textarea',"id:{$node}_{$field}, name:{$node}_{$field}");
            //
            //        switch($field) {
            //          case 'answer':
            //            $value = $exercise->getExerciseDataAnswerForItem($node);
            //            break;
            //          case 'comment':
            //            $value = $exercise->getExerciseDataAuthorCommentForItem($node);
            //            break;
            //          case 'correctness':
            //            $value = $exercise->getExerciseDataCorrectnessForItem($node);
            //            break;
            //          default:
            //            $value = '';
            //        }
            //        $textarea->addChild(new CText($value));
            //        $form->addChild($textarea);
        }
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
        /*
       * ottiene i dati relativi alla risposte fornite dagli utenti nella classe
       * e alle risposte possibile ammesse dall'esercizio
        */
        $exercise_data    = ExerciseDAO::getExerciseInfo($exerciseObj, $id_course_instance);
        $possible_answers = $exerciseObj->getExerciseData();

        $div = CDOMElement::create('div');
        $div->addChild(new CText($exerciseObj->getText()));
        $data = [];

        $exercise_data_count = count($exercise_data);
        $thead_data = [
            translateFN('Testo della risposta'),
            translateFN('Punteggio'),
            translateFN('Numero di risposte'),
        ];

        /*
       * scorre le risposte fornite dalla classe
        */
        for ($i = 0; $i < $exercise_data_count; $i++) {
            $href = 'view.php?id_node=' . $exercise_data[$i]['risposta_libera'];
            $answer = CDOMElement::create('a', "href:$href");

            $answer_id = $exercise_data[$i]['risposta_libera'];
            $answer->addChild(new CText($exerciseObj->getExerciseDataAnswerForItem($answer_id)));

            $tbody_data[$i] = [
                $answer->getHtml(),
                $exercise_data[$i]['punteggio'],
                $exercise_data[$i]['risposte'],
            ];

            if (isset($possible_answers[$answer_id])) {
                unset($possible_answers[$answer_id]);
            }
        }

        /*
       * considera eventuali risposte all'esercizio che non sono state date
       * da nessuno studente
        */
        foreach ($possible_answers as $answer_id => $answer_data) {
            $href = 'view.php?id_node=' . $answer_id;
            $answer = CDOMElement::create('a', "href:$href");
            $answer->addChild(new CText($exerciseObj->getExerciseDataAnswerForItem($answer_id)));

            $tbody_data[$i] = [
                $answer->getHtml(),
                $exerciseObj->getExerciseDataCorrectnessForItem($answer_id),
                0,
            ];
            $i++;
        }
        $div->addChild(BaseHtmlLib::tableElement('class:' . ADA_SEMANTICUI_TABLECLASS, $thead_data, $tbody_data));
        return $div->getHtml();
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
}

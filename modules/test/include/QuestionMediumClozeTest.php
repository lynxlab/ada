<?php

use Lynxlab\ADA\Module\Test\QuestionMediumClozeTest;

use Lynxlab\ADA\Module\Test\QuestionClozeTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class QuestionMediumClozeTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CLi;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionMediumClozeTest extends QuestionClozeTest
{
    /**
     * return necessaries html objects that represent the object
     *
     * @access protected
     *
     * @param $ref reference to the object that will contain this rendered object
     * @param $feedback "show feedback" flag on rendering
     * @param $rating "show rating" flag on rendering
     * @param $rating_answer "show correct answer" on rendering
     *
     * @return an object of CDOMElement
     */
    protected function renderingHtml(&$ref = null, $feedback = false, $rating = false, $rating_answer = false)
    {
        if (!$this->display) {
            return new CText(''); //if we don't have to display this question, let's return an empty item
        }
        $out = parent::renderingHtml($ref, $feedback, $rating, $rating_answer);

        $li = new CLi();
        $li->setAttribute("class", "answer_cloze_test");
        $li->addChild(new CText($this->getPreparedText($feedback, $rating, $rating_answer)));
        $ref->addChild($li);

        return $out;
    }

    /**
     * abstract function that will replace cloze entries in text
     *
     * @param array $params - matched params from regexp
     * @return a string of HTML
     * @see getPreparedText
     */
    public function clozePlaceholder($params)
    {
        $ordine = $params[1];
        $value = $params[2];

        $name = $this->getPostFieldName();
        $post_data = $this->getPostData();

        $class = 'normal_cloze_question_test';
        $obj = CDOMElement::create('text');
        $obj->setAttribute('name', $name . '[' . self::POST_ANSWER_VAR . '][' . $ordine . ']');
        $obj->setAttribute('value', $post_data[self::POST_ANSWER_VAR][$ordine]);
        $obj->setAttribute('maxlength', strlen($value));
        $obj->setAttribute('size', strlen($value));
        if ($this->feedback) {
            $obj->setAttribute('readonly', '');
            $risposta = $this->givenAnswer['risposta'][self::POST_ANSWER_VAR][$ordine];
            if (!empty($risposta)) {
                $obj->setAttribute('value', $risposta);
                if (!empty($this->children)) {
                    foreach ($this->children as $answer) {
                        if ($this->isAnswerCorrect($answer, $ordine, $risposta)) {
                            $class .= ' right_answer_test';
                        } else {
                            $class .= ' wrong_answer_test';
                        }
                    }
                }
            } else {
                $class .= ' empty_answer_test';
            }
        }

        $correctAnswer = false;
        if ($this->feedback && ($this->rating || $this->rating_answer) && !strstr($class, 'right_answer_test')) {
            $correctAnswer = $this->getMostCorrectAnswer($ordine);
            if ($correctAnswer) {
                $popup = CDOMElement::create('div', 'id:popup_' . $this->id_nodo . '_' . $ordine);
                $popup->setAttribute('style', 'display:none;');
                $popup->addChild(new CText($correctAnswer->testo));
                if ($this->rating) {
                    $popup->addChild(new CText(' (' . $correctAnswer->correttezza . ' ' . translateFN('Punti') . ')'));
                }

                $obj->setAttribute('class', $class . ' answerPopup');
                $obj->setAttribute('title', $this->id_nodo . '_' . $ordine);
                $html = $obj->getHtml() . $popup->getHtml();
            }
        }

        if (!$correctAnswer) {
            $obj->setAttribute('class', $class);
            $html = $obj->getHtml();
        }

        if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $span = CDOMElement::create('span', 'class:clozePopup,title:' . $this->id_nodo . '_' . $ordine);
            $html .= $span->getHtml();

            $div = CDOMElement::create('div', 'id:popup_' . $this->id_nodo . '_' . $ordine);
            $div->setAttribute('style', 'display:none;');
            $risposte = [];
            if (!empty($this->children)) {
                foreach ($this->children as $k => $v) {
                    if ($v->ordine == $ordine) {
                        $v->correttezza = is_null($v->correttezza) ? 0 : $v->correttezza;
                        $risposte[] = $v->testo . ' (' . $v->correttezza . ' ' . translateFN('Punti') . ')';
                    }
                }
            }
            $div->addChild(new CText(implode('<br/>', $risposte)));
            $html .= $div->getHtml();
        }

        return $html;
    }

    /**
     * implementation of exerciseCorrection for Medium Cloze question type
     *
     * @access public
     *
     * @return a value representing the points earned or an array containing points and attachment elements
     */
    public function exerciseCorrection($data)
    {
        $points = 0;

        if (is_array($data) && !empty($data) && !empty($this->children)) {
            foreach ($data[self::POST_ANSWER_VAR] as $k => $v) {
                foreach ($this->children as $answer) {
                    if ($this->isAnswerCorrect($answer, $k, $v)) {
                        $points += $answer->correttezza;
                        break;
                    }
                }
            }
        }

        if ($points > $this->getMaxScore()) {
            $points = $this->getMaxScore();
        }

        return ['points' => $points,'attachment' => null];
    }

    /**
     * return true/false if the given value matches the answer
     *
     * @access protected
     *
     * @param $answer answer object
     * @param $value value to correct
     *
     * @return boolean
     */
    protected function isAnswerCorrect($answer, $order, $value)
    {
        return ($answer->ordine == $order && call_user_func($answer->compareFunction, $answer->testo, $value) == 0);
    }
}

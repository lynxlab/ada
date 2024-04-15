<?php

use Lynxlab\ADA\Module\Test\QuestionTest;

use Lynxlab\ADA\Module\Test\QuestionOpenAutomaticTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class QuestionOpenAutomaticTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionOpenAutomaticTest extends QuestionTest
{
    /**
     * used to configure object with database's data options
     *
     * @access protected
     *
     */
    protected function configureProperties()
    {
        if (!parent::configureProperties()) {
            return false;
        }
        return true;
    }

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

        $name = $this->getPostFieldName();
        $post_data = $this->getPostData();

        $html = '';

        $li = CDOMElement::create('li', 'class:answer_open_test');

        $textArea = CDOMElement::create('textarea');
        $textArea->setAttribute('name', $name . '[' . self::POST_ANSWER_VAR . ']');
        $textArea->setAttribute('class', 'open_answer_test');
        if ($this->feedback) {
            $textArea->addChild(new CText($this->givenAnswer['risposta'][self::POST_ANSWER_VAR]));
            $textArea->setAttribute('readonly', '');
        } elseif (!empty($post_data[self::POST_ANSWER_VAR])) {
            $textArea->addChild(new CText($post_data[self::POST_ANSWER_VAR]));
        }
        $li->addChild($textArea);

        if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $span = CDOMElement::create('span', 'class:clozePopup,title:' . $this->id_nodo);
            $html .= $span->getHtml();

            $div = CDOMElement::create('div', 'id:popup_' . $this->id_nodo);
            $div->setAttribute('style', 'display:none;');
            $risposte = [];
            if (!empty($this->children)) {
                foreach ($this->children as $k => $v) {
                    $risposte[] = $v->testo . ' (' . $v->correttezza . ' ' . translateFN('Punti') . ')';
                }
            }
            $div->addChild(new CText(implode('<br/>', $risposte)));
            $html .= $div->getHtml();
        }

        $ref->addChild($li);

        return $out;
    }

    /**
     * implementation of exerciseCorrection for OpenAutomatic question type
     *
     * @access public
     *
     * @return a value representing the points earned or an array containing points and attachment elements
     */
    public function exerciseCorrection($data)
    {
        $data[self::POST_ANSWER_VAR] = trim($data[self::POST_ANSWER_VAR]);

        $points = 0;
        foreach ($this->children as $answer) {
            if (call_user_func($answer->compareFunction, $answer->testo, $data[self::POST_ANSWER_VAR]) == 0) {
                $points = $answer->correttezza;
                break;
            }
        }

        if ($points > $this->getMaxScore()) {
            $points = $this->getMaxScore();
        }

        return ['points' => $points,'attachment' => null];
    }

    /**
     * return exercise max score
     *
     * @access public
     *
     * @return a value representing the max score
     */
    public function getMaxScore()
    {
        $score = 0;
        foreach ($this->children as $v) {
            if ($v->correttezza > $score) {
                $score = $v->correttezza;
            }
        }
        return $score;
    }
}

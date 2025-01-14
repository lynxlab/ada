<?php

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CElement;
use Lynxlab\ADA\CORE\html4\CLi;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Module\Test\QuestionClozeTest;
use Lynxlab\ADA\Module\Test\QuestionEraseClozeTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionSlotClozeTest extends QuestionClozeTest
{
    public const CLOZEDELIMITER = '§§§';
    public const HTMLDELIMITER = '~~~';
    public const SPANHEADER = '<span class="answer_cloze_item_test">';
    public const SPANFOOTER = '</span>';

    protected $boxPosition;

    protected $clozePlaceholders = [];
    protected $htmlPlaceholders = [];

    protected $spanInstances = 0;
    protected $clozeOrders;

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

        //fifth character
        $this->boxPosition = $this->tipo[4];
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

        if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $rating = true;
            $rating_answer = true;
        }

        $out = parent::renderingHtml($ref, $feedback, $rating, $rating_answer);

        $li = new CLi();
        $li->setAttribute('class', 'answer_cloze_slot_test');

        $preparedText = $this->getPreparedText($feedback, $rating, $rating_answer);

        if (!$feedback) {
            $this->buildDragDropElements($li, $preparedText);
        } else {
            $li->addChild(new CText($preparedText));
        }

        $ref->addChild($li);

        return $out;
    }

    /**
     * builds drag'n'drop exercise html
     *
     * @param \Lynxlab\ADA\CORE\html4\CElement $html CBase element reference
     * @param string $preparedText cloze prepared text
     * @param boolean $showAnswers call showAnswers javascript function on elements' click
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement reference
     * @see getPreparedText
     */
    public function buildDragDropElements(CElement $html, $preparedText, $showAnswers = false)
    {
        $ulBox = CDOMElement::create('ul');
        $ulBox->setAttribute('id', 'ulBox' . $this->id_nodo);
        $ulBox->setAttribute('class', 'dragdropBox sortable drop' . $this->id_nodo);

        $box = CDOMElement::create('div');
        if (!empty($this->titolo_dragdrop)) {
            $span = CDOMElement::create('span', 'class:title_dragdrop');
            $span->addChild(new CText($this->titolo_dragdrop));
            $box->addChild($span);
        }
        $box->addChild($ulBox);

        $children = $this->children;
        if (!empty($children)) {
            shuffle($children);
            foreach ($children as $c) {
                $item = CDOMElement::create('li');
                $item->setAttribute('class', 'draggable drag' . $this->id_nodo);
                $item->setAttribute('id', 'answer' . $c->id_nodo);
                if ($showAnswers) {
                    $item->setAttribute('onclick', "showAnswers('ordine" . $c->ordine . "');");
                }
                $item->addChild(new CText($c->testo));

                $ulBox->addChild($item);
            }
        }

        $text = CDOMElement::create('div');
        $text->addChild(new CText($preparedText));

        //switch per gestire la stampa del box delle risposte
        $boxClass = 'divDragDropBox ';
        $textClass = 'textDragDrop';
        switch ($this->boxPosition) {
            case ADA_TOP_TEST_DRAGDROP:
                $html->addChild($box);
                $html->addChild($text);
                $boxClass .= 'top';
                break;
            case ADA_RIGHT_TEST_DRAGDROP:
                $html->addChild($box);
                $html->addChild($text);
                $boxClass .= 'right';
                $textClass .= 'Left';
                break;
            case ADA_BOTTOM_TEST_DRAGDROP:
                $html->addChild($text);
                $html->addChild($box);
                $boxClass .= 'bottom';
                break;
            case ADA_LEFT_TEST_DRAGDROP:
                $html->addChild($box);
                $html->addChild($text);
                $boxClass .= 'left';
                $textClass .= 'Right';
                break;
        }
        $divclear = CDOMElement::create('div', 'class:clear');
        $html->addChild($divclear);
        $box->setAttribute('class', $boxClass);
        $text->setAttribute('class', $textClass);

        return $html;
    }

    /**
     * function used to match cloze replacements in text
     *
     * @access protected
     *
     * @param $feedback "show feedback" flag on rendering
     * @param $rating "show rating" flag on rendering
     * @param $rating_answer "show correct answer" on rendering
     *
     * @return an array with matches or false
     */
    protected function getPreparedText($feedback = false, $rating = false, $rating_answer = false)
    {
        $this->feedback = $feedback;
        $this->rating = $rating;
        $this->rating_answer = $rating_answer;

        $regexpPutHtmlPlaceholder = '#\s*<[^>]+>.*(</[^>]+>)*\s*#mU';
        $regexpRemoveHtmlPlaceholder = '#' . self::HTMLDELIMITER . '#mU';
        $regexpRemoveClozePlaceholder = '#' . self::CLOZEDELIMITER . '#mU';
        $regexpSpan = '#' . self::SPANHEADER . '(.*)' . self::SPANFOOTER . '#mU';

        $regexpSlots = '#[\s' . self::CLOZEDELIMITER . ']+#mu';

        $html = $this->replaceInternalLinkMedia($this->testo);
        $html = preg_replace_callback(QuestionClozeTest::REGEXPCLOZE, [$this,'putClozePlaceholder'], $html);
        $html = preg_replace_callback($regexpPutHtmlPlaceholder, [$this,'putHtmlPlaceholder'], $html);
        $html = preg_replace_callback($regexpSlots, [$this,'addSpan'], $html);
        $html = preg_replace_callback($regexpRemoveHtmlPlaceholder, [$this,'removeHtmlPlaceholder'], $html);
        $html = preg_replace_callback($regexpRemoveClozePlaceholder, [$this,'removeClozePlaceholder'], $html);
        $html = preg_replace_callback($regexpSpan, [$this,'countSpanAndRemoveClozeMarker'], $html);

        return $html;
    }

    /**
     * wrap a span around an element
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    protected function addSpan($params)
    {
        if (is_array($params)) {
            $params = $params[0];
        }
        if (empty($params)) {
            return;
        }
        $params = trim($params);
        return self::SPANHEADER . $params . '</span>';
    }

    /**
     * saves html coming from getPreparedText
     * and replaced it with a placeholder
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    protected function putHtmlPlaceholder($params)
    {
        $this->htmlPlaceholders[] = $params[0];
        return self::HTMLDELIMITER;
    }

    /**
     * restores saved html over placeholders
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    protected function removeHtmlPlaceholder($params = null)
    {
        return array_shift($this->htmlPlaceholders);
    }

    /**
     * saves cloze markers coming from getPreparedText
     * and replaced it with a placeholder
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    protected function putClozePlaceholder($params)
    {
        $this->clozePlaceholders[] = $params[0];
        return self::CLOZEDELIMITER;
    }

    /**
     * restores saved cloze markers over placeholders
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    protected function removeClozePlaceholder($params = null)
    {
        $value = array_shift($this->clozePlaceholders);
        return $this->addSpan($value);
    }

    /**
     * restores saved cloze markers over placeholders
     *
     * @access protected
     *
     * @param $params params coming from getPreparedText
     *
     * @return string
     * @see getPreparedText
     */
    public function countSpanAndRemoveClozeMarker($params)
    {
        $ordine = ++$this->spanInstances;
        $isCloze = false;
        if (preg_match(QuestionClozeTest::REGEXPCLOZE, $params[0], $match)) {
            $this->clozeOrders[$match[1]] = $ordine;
            $isCloze = true;
            $value = $match[2];
        } else {
            $value = $params[1];
        }

        $popup = '';
        $class = '';
        $html = '';

        if ($this->feedback) {
            /*
            $answer = array();
            if (!empty($this->children)) {
                foreach($this->children as $v) {
                    if ($ordine == $v->ordine) {
                        $answer[] = $v;
                    }
                }
            }
            */
            $answer = $this->searchChild($ordine, 'ordine', true);

            if (isset($this->givenAnswer['risposta'][self::POST_ANSWER_VAR][$ordine])) {
                $risposta = $this->givenAnswer['risposta'][self::POST_ANSWER_VAR][$ordine];
            } else {
                $risposta = '';
            }

            if (empty($answer) && empty($risposta)) {
                return ' ';
            }
            $obj = CDOMElement::create('div');
            $risposta = $this->searchChild($risposta);
            if (is_object($risposta)) {
                $obj->addChild(new CText($risposta->testo));
            }
            $class = 'answer_slot_test';
            if (!empty($risposta) && !empty($answer)) {
                $tmp_class = '';
                foreach ($answer as $a) {
                    if ($this->isAnswerCorrect($a, $ordine, $risposta->id_nodo)) {
                        $tmp_class = ' right_answer_test';
                        break;
                    } else {
                        $tmp_class = ' wrong_answer_test';
                    }
                }
                $class .= $tmp_class;
            } elseif (empty($answer)) {
                $class .= ' wrong_answer_test';
            } else {
                $class .= ' empty_answer_test';
            }

            $correctAnswer = false;
            if ($this->rating || $this->rating_answer) {
                $correctAnswer = $this->getMostCorrectAnswer($ordine);
                if ($correctAnswer) {
                    $popup = CDOMElement::create('div', 'id:popup_' . $this->id_nodo . '_' . $ordine);
                    $popup->setAttribute('style', 'display:none;');
                    $popup->addChild(new CText($correctAnswer->testo));
                    if ($this->rating) {
                        $popup->addChild(new CText(' (' . (int)$correctAnswer->correttezza . ' ' . translateFN('Punti') . ')'));
                    }

                    $obj->setAttribute('class', $class . ' answerPopup');
                    $obj->setAttribute('title', $this->id_nodo . '_' . $ordine);
                    $html = ' ' . $obj->getHtml() . $popup->getHtml();
                }
            }

            if (!$correctAnswer) {
                $obj->setAttribute('class', $class);
                $html = ' ' . $obj->getHtml();
            }
        } else {
            $name = $this->getPostFieldName();
            $post_data = $this->getPostData();

            $id = $this->id_nodo . '_' . $ordine;

            $input = CDOMElement::create('hidden');
            $input->setAttribute('id', 'dropInput' . $id);
            $input->setAttribute('name', $name . '[' . self::POST_ANSWER_VAR . '][' . $ordine . ']');
            $input->setAttribute('value', $post_data !== false ? $post_data[self::POST_ANSWER_VAR][$ordine] : '');
            $html .= $input->getHtml();

            $ddUl = CDOMElement::create('ul');
            $ddUl->setAttribute('id', 'drop' . $id);
            $ddUl->setAttribute('class', 'sortable drop' . $this->id_nodo);
            $html .= $ddUl->getHtml();

            if ($this->feedback && $_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT) {
                $html = str_replace('answer_cloze_item_test', '', $html);
            }

            $html = str_replace("\n", '', $html);
        }

        if (!$this->feedback && $_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT && $isCloze) {
            $span = CDOMElement::create('span', 'class:clozePopup,title:' . $this->id_nodo . '_' . $ordine);
            $html .= $span->getHtml();

            $div = CDOMElement::create('div', 'id:popup_' . $this->id_nodo . '_' . $ordine);
            $div->setAttribute('style', 'display:none;');
            $risposte = [];
            /*
            if (!empty($this->children)) {
                foreach($this->children as $k=>$v) {
                    if ($v->ordine == $ordine) {
                        $risposte[] = $v->testo.' ('.(int)$v->correttezza.' '.translateFN('Punti').')';
                    }
                }
            }
            */
            $answers = $this->searchChild($ordine, 'ordine', true);
            if (!empty($answers)) {
                foreach ($answers as $v) {
                    $risposte[] = $v->testo . ' (' . (int)$v->correttezza . ' ' . translateFN('Punti') . ')';
                }
            }


            $div->addChild(new CText(implode('<br/>', $risposte)));
            $html .= $div->getHtml();
        }

        return $html;
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

        $value = '<cloze title="' . $ordine . '">' . $value . '</cloze>';
        $html = $value;

        if ($_SESSION['sess_id_user_type'] != AMA_TYPE_STUDENT) {
            $span = CDOMElement::create('span', 'class:clozePopup,title:' . $this->id_nodo . '_' . $ordine);
            $html .= $span->getHtml();

            $div = CDOMElement::create('div', 'id:popup_' . $this->id_nodo . '_' . $ordine);
            $div->setAttribute('style', 'display:none;');
            $risposte = [];
            /*
            foreach($this->children as $k=>$v) {
                if ($v->ordine == $ordine) {
                    $risposte[] = $v->testo.' ('.(int)$v->correttezza.' '.translateFN('Punti').')';
                }
            }
            */

            $answers = $this->searchChild($ordine, 'ordine', true);
            if (!empty($answers)) {
                foreach ($answers as $v) {
                    $risposte[] = $v->testo . ' (' . (int)$v->correttezza . ' ' . translateFN('Punti') . ')';
                }
            }


            $div->addChild(new CText(implode('<br/>', $risposte)));
            $html .= $div->getHtml();
        }

        return $html;
    }

    /**
     * implementation of exerciseCorrection for Normal Cloze question type
     *
     * @access public
     *
     * @return a value representing the points earned or an array containing points and attachment elements
     */
    public function exerciseCorrection($data)
    {
        $points = 0;

        if (is_array($data) && !empty($data)) {
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

        return ['points' => $points, self::POST_ATTACHMENT_VAR => null];
    }

    /**
     * return true/false if the given value matches the answer
     *
     * @access protected
     *
     * @param $answer answer object
     * @param $order answer order
     * @param $value user given answer
     *
     * @return boolean
     */
    protected function isAnswerCorrect($answer, $order, $value)
    {
        if ($answer->ordine == $order) {
            $return = ($answer->id_nodo == $value && $answer->correttezza > 0);
            if (!$return) {
                $givenAnswer = $this->searchChild($value);
                if (is_object($givenAnswer)) {
                    $return = (strcasecmp($answer->testo, $givenAnswer->testo) == 0 && $answer->correttezza > 0);
                } else {
                    $return = false;
                }
            }
            return $return;
        } else {
            return false;
        }
    }

    /**
     * Computes cloze orders with span orders (calling getPreparedText)
     * and returns them
     *
     * @access public
     *
     * @return array
     * @see getPreparedText
     */
    public function getClozeOrders()
    {
        $this->getPreparedText();
        return $this->clozeOrders;
    }

    /**
     * Function that automatic creates answers (and save it to database) for cloze question type
     * ADA_SLOT_TEST_SIMPLICITY version
     *
     * @global db $dh
     *
     * @param int $question_id
     * @param array $data question data
     * @param array $test test record
     *
     * @see QuestionEraseClozeTest::createEraseClozeAnswers
     * @see QuestionClozeTest::createClozeAnswers
     */
    public static function createSlotClozeAnswers($question_id, $data, $test)
    {
        QuestionEraseClozeTest::createEraseClozeAnswers($question_id, $data, $test);
    }

    /**
     * Serialize answer data
     *
     * @return string serialized data
     *
     * @see Root::saveAnswers
     */
    public function serializeAnswers($data)
    {
        $orders = [];
        if (!empty($this->children)) {
            foreach ($this->children as $k => $v) {
                $orders[] = $v->ordine;
            }
        }

        foreach ($data[self::POST_ANSWER_VAR] as $k => $v) {
            if (!in_array($k, $orders) && empty($v)) {
                unset($data[self::POST_ANSWER_VAR][$k]);
            }
        }

        return parent::serializeAnswers($data);
    }
}

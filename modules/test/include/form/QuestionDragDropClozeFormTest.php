<?php

use Lynxlab\ADA\Module\Test\QuestionFormTest;

use Lynxlab\ADA\Module\Test\QuestionDragDropClozeFormTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class QuestionDragDropClozeFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 *
 * @package
 * @author      Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Test;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class QuestionDragDropClozeFormTest extends QuestionFormTest
{
    protected function content()
    {
        $this->commonElements();

        //tipologia domanda cloze
        $box = 'box_position';
        $options = [
            ADA_TOP_TEST_DRAGDROP => translateFN('Sopra il testo'),
            ADA_RIGHT_TEST_DRAGDROP => translateFN('A destra del testo'),
            ADA_BOTTOM_TEST_DRAGDROP => translateFN('Sotto il testo'),
            ADA_LEFT_TEST_DRAGDROP => translateFN('A sinistra del testo'),
        ];

        if (isset($this->data[$box])) {
            $defaultValue = $this->data[$box];
        } else {
            $defaultValue = ADA_RIGHT_TEST_DRAGDROP;
        }
        $this->addSelect($box, translateFN('Posizione box drag\'n\'drop') . ':', $options, $defaultValue);

        //titolo box drag'n'drop
        $this->addTextInput('titolo_dragdrop', translateFN('Titolo box drag\'n\'drop (lasciare vuoto se non usato)') . ':')
             ->withData($this->data['titolo_dragdrop']);
    }
}

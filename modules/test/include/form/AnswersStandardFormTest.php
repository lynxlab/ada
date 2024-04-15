<?php

use Lynxlab\ADA\Module\Test\AnswerHeaderControlTest;

use Lynxlab\ADA\Module\Test\AnswerFooterControlTest;

use Lynxlab\ADA\Module\Test\AnswerControlTest;

use Lynxlab\ADA\Module\Test\FormTest;

use Lynxlab\ADA\Module\Test\AnswersStandardFormTest;

// Trigger: ClassWithNameSpace. The class AnswersStandardFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

class AnswersStandardFormTest extends FormTest
{
    protected $question;
    protected $open_answer = false;
    protected $case_sensitive = false;

    public function __construct($data, $question, $case_sensitive, $open_answer)
    {
        $this->question = $question;
        $this->open_answer = $open_answer;
        $this->case_sensitive = $case_sensitive;

        parent::__construct($data);
    }

    protected function content()
    {
        $this->setName('answersForm');

        $defaultData = ['case_sensitive' => $this->question['tipo'][5]];

        $this->addControl(new AnswerHeaderControlTest($this->open_answer, $this->case_sensitive));

        if (!empty($this->data)) {
            foreach ($this->data as $k => $v) {
                $this->addControl(new AnswerControlTest($this->open_answer, $this->case_sensitive, $v['record']))->withData($v);
            }
        } else {
            $this->addControl(new AnswerControlTest($this->open_answer, $this->case_sensitive))->withData($defaultData);
        }
        //hidden row
        $this->addControl(new AnswerControlTest($this->open_answer, $this->case_sensitive, null, true))->withData($defaultData);

        $this->addControl(new AnswerFooterControlTest());
    }
}

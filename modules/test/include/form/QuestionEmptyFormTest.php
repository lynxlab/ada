<?php

use Lynxlab\ADA\Module\Test\QuestionFormTest;

use Lynxlab\ADA\Module\Test\QuestionEmptyFormTest;

// Trigger: ClassWithNameSpace. The class QuestionEmptyFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

class QuestionEmptyFormTest extends QuestionFormTest
{
    protected function content()
    {
        $this->commonElements();
    }
}

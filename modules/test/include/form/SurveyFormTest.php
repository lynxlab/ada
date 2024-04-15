<?php

use Lynxlab\ADA\Module\Test\SurveyFormTest;

use Lynxlab\ADA\Module\Test\RootFormTest;

// Trigger: ClassWithNameSpace. The class SurveyFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

class SurveyFormTest extends RootFormTest
{
    protected function content()
    {
        parent::content();

        $this->setName('surveyForm');
    }
}

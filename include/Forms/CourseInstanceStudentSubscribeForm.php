<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\CourseInstanceStudentSubscribeForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class CourseInstanceStudentSubscribeForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/**
 * CourseInstanceStudentSubscribeForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Description of CourseInstanceStudentSubscribeForm
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class CourseInstanceStudentSubscribeForm extends FForm
{
    public function __construct()
    {
        parent::__construct();

        $this->addTextInput('username', translateFN('Username'))
             ->setRequired()
             ->setValidator(FormValidator::EMAIL_VALIDATOR);
    }
}

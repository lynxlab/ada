<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\InstanceTransferForm;

use Lynxlab\ADA\Main\Forms\InstancePaypalForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class InstanceTransferForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/**
 * InstancePaypalForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 *
 */
class InstanceTransferForm extends FForm
{
    public function __construct()
    {
        parent::__construct();

        //$action = PAYPAL_ACTION;
        $action = HTTP_ROOT_DIR . "/browsing/student_course_instance_transfer.php";
        //?instance=$instanceId&student=$studentId&provider=$providerId&course=$courseId";
        $this->setAction($action);
        $submitValue = translateFN('Paga con bonifico');
        $this->setSubmitValue($submitValue);

        $this->addHidden('instance');
        $this->addHidden('student');
        $this->addHidden('provider');
        $this->addHidden('course');
    }
}

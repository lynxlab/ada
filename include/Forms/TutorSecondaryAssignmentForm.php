<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\TutorSecondaryAssignmentForm;

use Lynxlab\ADA\Main\Forms\TutorAssignmentForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class TutorSecondaryAssignmentForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/**
 * TutorAssignmentForm file
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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Description of TutorAssignmentForm
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class TutorSecondaryAssignmentForm extends FForm
{
    public function __construct($tutorsAr = [], $checkedTutors = [])
    {
        parent::__construct();
        $this->addCheckboxes(
            'id_tutors_new[]',
            translateFN("Seleziona i tutors dall'elenco"),
            $tutorsAr,
            $checkedTutors
        );
        $this->addHidden('id_tutors_old');
        $this->addHidden('id_course_instance');
        $this->addHidden('id_course');
    }
}

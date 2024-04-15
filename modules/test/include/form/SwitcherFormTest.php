<?php

use Lynxlab\ADA\Module\Test\SwitcherFormTest;

use Lynxlab\ADA\Module\Test\FormTest;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class SwitcherFormTest was declared with namespace Lynxlab\ADA\Module\Test. //

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

class SwitcherFormTest extends FormTest
{
    protected $id_course;

    public function __construct($id_course)
    {
        $this->id_course = $id_course;

        parent::__construct();
    }

    protected function content()
    {
        $dh = $GLOBALS['dh'];

        $this->setName('switcherForm');

        //lista dei test presenti
        $test_list = $dh->testGetCourseSurveys(['id_corso' => $this->id_course]); //getting already present test list
        $test_ids = [];
        if (!empty($test_list)) {
            $checkboxes = [];
            foreach ($test_list as $v) {
                $checkboxes[$v['id_test']] = $v['titolo'] . ' (ID:' . $v['id_test'] . '  creato il: ' . AMADataHandler::tsToDate($v['data_creazione']) . ')';
                $test_ids[] = $v['id_test'];
            }
            $this->addCheckboxes('delete_test[]', translateFN('Seleziona i sondaggi da rimuovere dal corso') . ':', $checkboxes, null);
        }

        //lista dei test da aggiungere
        $tmp_tests = $dh->testGetNodes(['id_nodo_parent' => null,'tipo' => 'LIKE ' . ADA_TYPE_SURVEY . '%']); //getting available test
        $options = ['' => ' --- '];
        $empty = true;
        foreach ($tmp_tests as $v) {
            if (!in_array($v['id_nodo'], $test_ids)) {
                $options[$v['id_nodo']] = $v['titolo'] . ' (ID:' . $v['id_nodo'] . '  creato il: ' . AMADataHandler::tsToDate($v['data_creazione']) . ')';
                $empty = false;
            }
        }

        if ($empty) {
            $options = ['' => translateFN('Nessun questionario presente')];
            $empty = false;
        }
        if (!$empty) {
            $this->addSelect('id_test', translateFN('Seleziona il sondaggio da aggiungere al corso') . ':', $options, '');
        }
    }
}

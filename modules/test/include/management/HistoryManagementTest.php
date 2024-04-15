<?php

use Lynxlab\ADA\Module\Test\TutorManagementTest;

use Lynxlab\ADA\Module\Test\HistoryManagementTest;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Course\CourseInstance;

use Lynxlab\ADA\Main\Course\Course;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class HistoryManagementTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class HistoryManagementTest extends TutorManagementTest
{
    protected $id_student;

    /**
     * constructs tutor management and configure it according to parameters,
     * invoking parent constructor
     *
     * @param string $what 'test' or 'survey' string
     * @param \Lynxlab\ADA\Main\Course\Course $courseObj course object reference
     * @param \Lynxlab\ADA\Main\Course\CourseInstance $course_instanceObj course instance object reference
     * @param int $id_student id student
     * @param int $id_test id test
     * @param int $id_history_test id history test
     */
    public function __construct($what, $courseObj, $course_instanceObj, $id_student = null, $id_test = null, $id_history_test = null)
    {
        parent::__construct($what, $courseObj, $course_instanceObj, $id_student, $id_test, $id_history_test);

        $this->id_student = $id_student;
    }

    /**
     * function that return list of students that sent test or survey
     *
     * @global db $dh
     *
     * @return array an array composed of 'html', 'path' and 'title' keys
     */
    protected function listStudents()
    {
        $array = [
            'html' => translateFN(''),
            'path' => translateFN('Storico') . ' ' . ucfirst($this->plurale),
            'title' => translateFN('Storico') . ' ' . ucfirst($this->plurale),
        ];
        return $array;
    }

    /**
     * function that return list of test sent test or survey by student
     *
     * @global db $dh
     *
     * @param boolean $student if true switch scope from tutor to student
     *
     * @return array an array composed of 'html', 'path' and 'title' keys
     */
    protected function listTests($student = false)
    {
        $array = parent::listTests(true);

        $array['html'] = str_replace('&id_student=' . $this->id_student, '', $array['html']);
        $array['path'] = translateFN('Storico') . ' ' . ucfirst($this->plurale);
        $array['title'] = translateFN('Storico') . ' ' . ucfirst($this->plurale);
        return $array;
    }

    /**
     * function that return list of history test sent test or survey by student
     *
     * @global db $dh
     *
     * @param boolean $student if true switch scope from tutor to student
     *
     * @return array an array composed of 'html', 'path' and 'title' keys
     */
    protected function listHistoryTests($student = false)
    {
        $array = parent::listHistoryTests(true);

        $array['html'] = str_replace('&id_student=' . $this->id_student, '', $array['html']);
        $array['path'] = '<a href="' . $this->filepath . '?op=' . $this->what . '&id_course_instance=' . $this->course_instanceObj->id . '&id_course=' . $this->courseObj->id . '">' . translateFN('Storico') . ' ' . ucfirst($this->plurale) . '</a> &gt; ' . $this->test['titolo'];
        $array['title'] = translateFN('Storico') . ' ' . ucfirst($this->plurale);
        return $array;
    }

    /**
     * function that return a specific history test
     *
     * @global db $dh
     *
     * @return array an array composed of 'html', 'path' and 'title' keys
     */
    protected function viewHistoryTests()
    {
        $array = parent::viewHistoryTests();

        $array['path'] = '<a href="' . $this->filepath . '?op=' . $this->what . '&id_course_instance=' . $this->course_instanceObj->id . '&id_course=' . $this->courseObj->id . '">' . translateFN('Storico') . ' ' . ucfirst($this->plurale) . '</a> &gt; <a href="' . $this->filepath . '?op=' . $this->what . '&id_course_instance=' . $this->course_instanceObj->id . '&id_course=' . $this->courseObj->id . '&id_test=' . $this->test['id_nodo'] . '">' . $this->test['titolo'] . '</a> &gt; ' . translateFN('Tentativo') . ' #' . $this->history_test['id_history_test'];
        $array['title'] = translateFN('Storico') . ' ' . ucfirst($this->plurale);
        return $array;
    }
}

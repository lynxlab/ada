<?php

use Lynxlab\ADA\Module\Test\TestManagementTest;

use Lynxlab\ADA\Module\Test\RootManagementTest;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class TestManagementTest was declared with namespace Lynxlab\ADA\Module\Test. //

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TestManagementTest extends RootManagementTest
{
    /**
     * test constructor that calls parent constructor
     *
     * @param string $action string that represent the action to execute 'add', 'mod' or 'del'
     * @param int $id node id
     */
    public function __construct($action, $id = null)
    {
        parent::__construct($action, $id);
        $this->mode = ADA_TYPE_TEST;
        $this->what = translateFN('test');
    }
}

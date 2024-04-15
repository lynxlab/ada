<?php

use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;

use Lynxlab\ADA\Main\Forms\lib\classes\FCNullControl;

// Trigger: ClassWithNameSpace. The class FCNullControl was declared with namespace Lynxlab\ADA\Main\Forms\lib\classes. //

/**
 * FCNullControl file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCNullControl
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCNullControl extends FormControl
{
    public function render()
    {
        return '';
    }
}

<?php

/**
 * FCOption file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCOption
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCOption extends FormControl
{
    public function render()
    {
        $html = '<option';
        $html .= $this->renderAttributes();
        if ($this->controlData !== null) {
            $html .= ' value="' . $this->controlData . '"';
        }
        if ($this->selected !== false) {
            $html .= ' selected';
        }
        $html .= '>' . $this->labelText . '</option>';
        return $html;
    }
}

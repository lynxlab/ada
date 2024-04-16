<?php

/**
 * FCInput file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCInput
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCInput extends FormControl
{
    public function render()
    {
        $this->setAttribute('class', 'input_text');

        $html = '<input type="' . $this->controlType . '" id="' . $this->controlId . '" name="' . $this->controlId . '"';
        if ($this->controlData !== null) {
            $html .= ' value="' . $this->controlData . '"';
        }
        $html .= $this->renderAttributes();
        $html .= ' />
			<div class="' . self::DEFAULT_CLASS . ' clear"></div>';
        return $this->label() . $html;
    }
}

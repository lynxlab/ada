<?php

/**
 * FCInputHidden file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCInputHidden
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCInputHidden extends FormControl
{
    public function __construct($controlType, $controlId, $labelText)
    {
        parent::__construct($controlType, $controlId, $labelText);
        $this->setHidden();
    }

    public function render()
    {
        $this->setAttribute('class', 'input_hidden');
        $html = '<input type="' . $this->controlType . '" id="' . $this->controlId . '" name="' . $this->controlId . '"';
        if ($this->controlData !== null) {
            $html .= ' value="' . $this->controlData . '"';
        }
        $html .= $this->renderAttributes();
        $html .= ' />
			<div class="' . self::DEFAULT_CLASS . ' clear"></div>';

        return $html;
    }
}

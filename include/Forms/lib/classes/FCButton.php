<?php

/**
 * FCButton file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCButton
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCButton extends FormControl
{
    public function render()
    {
        $html = '<button id="' . $this->getId() . '" type="button" name="' . $this->getName() . '"' . $this->renderAttributes() . '>' . $this->labelText . '</button>';
        return $html;
    }
}

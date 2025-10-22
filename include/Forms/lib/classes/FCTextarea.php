<?php

/**
 * FCTextarea file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCTextarea
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCTextarea extends FormControl
{
    public function render()
    {
        $html = '<textarea id="' . $this->getId() . '" name="' . $this->getName() . '"' . $this->renderAttributes() . ' >' . $this->controlData . '</textarea><div class="' . self::DEFAULT_CLASS . ' clear"></div>';
        return $this->label() . $html;
    }
}

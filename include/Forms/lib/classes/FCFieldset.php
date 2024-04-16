<?php

/**
 * FCFieldset file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCFieldset
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCFieldset extends FormControl
{
    public function withData($data)
    {
        if (empty($this->controls) && is_array($data) && count($data) > 0) {
            $this->controls = $data;
        } elseif (is_array($this->controls)) {
            foreach ($this->controls as $control) {
                if ($control->getData() === $data) {
                    $control->setSelected();
                } elseif ($control->isSelected()) {
                    $control->setNotSelected();
                } else {
                    $control->withData($data);
                }
            }
        }
        return $this;
    }

    public function getControls()
    {
        return $this->controls;
    }

    public function render()
    {
        $html = $this->label() .
                '<fieldset id="' . $this->controlId . '" class="' . self::DEFAULT_CLASS . '"><ol class="' . self::DEFAULT_CLASS . '">';
        foreach ($this->controls as $control) {
            $html .= '<li class="' . self::DEFAULT_CLASS . '">' . $control->render() . '</li>';
        }
        $html .= '</ol></fieldset>';
        return $html;
    }

    private $controls = [];
}

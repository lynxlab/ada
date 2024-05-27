<?php

/**
 * FCInputCheckable file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCInputCheckable
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCInputCheckable extends FormControl
{
    public function render()
    {
        switch ($this->controlType) {
            default:
            case self::INPUT_CHECKBOX:
                $this->setAttribute('class', 'input_checkbox');
                break;
            case self::INPUT_RADIO:
                $this->setAttribute('class', 'input_radio');
                break;
        }

        $html = '<input type="' . $this->controlType . '" id="' . $this->controlId . '" name="' . $this->controlId . '"';
        //$html = '<input type="'.$this->controlType.'" name="'.$this->controlId.'"';
        if ($this->controlData !== null) {
            $html .= ' value="' . $this->controlData . '"';
        }
        if ($this->selected !== false) {
            $html .= ' checked';
        }
        $html .= $this->renderAttributes();
        $html .= ' />'
              . '<label for="' . $this->controlId . '">' . $this->labelText . '</label>';
        return $html;
    }

    public function withData($data)
    {
        if (is_null($this->controlData)) {
            parent::withData($data);
        } elseif ($this->getData() == $data) {
            $this->setSelected();
        } else {
            $this->setNotSelected();
        }
        return $this;
    }
}

<?php

use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;

use Lynxlab\ADA\Main\Forms\lib\classes\FCSelect;

use Lynxlab\ADA\Main\Forms\lib\classes\FCOption;

// Trigger: ClassWithNameSpace. The class FCSelect was declared with namespace Lynxlab\ADA\Main\Forms\lib\classes. //

/**
 * FCSelect file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FCSelect
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FCSelect extends FormControl
{
    public function withData($options, $checked = '')
    {
        if (is_array($this->options) && count($this->options) > 0) {
            return $this->setSelectedOption($options);
        }
        if (is_array($options) && count($options) > 0) {
            foreach ($options as $value => $text) {
                $control =  FormControl::create(FormControl::OPTION, '', $text);
                $control->withData($value);
                if ($value == $checked) {
                    $control->setSelected();
                }
                $this->options[] = $control;
            }
        }
        return $this;
    }

    public function addOption(FCOption $option)
    {
        $this->options[] = $option;
        return $this;
    }

    public function getData()
    {
        if (is_array($this->options) && count($this->options) > 0) {
            foreach ($this->options as $control) {
                if ($control->isSelected()) {
                    return $control->getData();
                }
            }
        }
    }

    private function setSelectedOption($value)
    {
        foreach ($this->options as $control) {
            if ($control->isSelected()) {
                $control->setNotSelected();
            }
            if (is_numeric($value) && ((int)$control->getData() == (int)$value)) {
                $control->setSelected();
            } elseif ($control->getData() === $value) {
                $control->setSelected();
            }
        }
        return $this;
    }

    public function render()
    {
        $html = '<select id="' . $this->controlId . '" name="' . $this->controlId . '"';
        $html .= $this->renderAttributes();
        $html .=  ' >';
        foreach ($this->options as $option) {
            $html .= $option->render();
        }
        $html .= '</select>
			<div class="' . self::DEFAULT_CLASS . ' clear"></div>';
        return $this->label() . $html;
    }

    private $options = [];
}

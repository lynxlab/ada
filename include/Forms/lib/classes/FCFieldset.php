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

use Lynxlab\ADA\CORE\html4\CBaseElement;

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

    public function addCDOM($element)
    {
        return $this->addControl($element);
    }

    public function addControl($element)
    {
        $this->controls[] = $element;
        return $this;
    }

    /**
     * Returns the label for this form control.
     *
     * @return string the html for the control's label
     */
    protected function label()
    {
        $html = '<span id="l_' . $this->controlId . '" class="' . self::DEFAULT_CLASS;
        if ($this->isMissing) {
            $html .= ' error';
        }
        $html .= '" >' . $this->labelText;
        if ($this->isRequired) {
            $html .= ' (*)';
        }
        $html .= '</span>';
        return $html;
    }

    public function render()
    {
        $html = $this->label() .
                '<fieldset id="' . $this->controlId . '" class="' . self::DEFAULT_CLASS . '"' .
                $this->renderAttributes() .
                '><ol class="' . self::DEFAULT_CLASS . '">';
        foreach ($this->controls as $control) {
            $hidden = '';
            if ($control instanceof CBaseElement) {
                $controlHtml = $control->getHtml();
            } else {
                if ($control->isHidden()) {
                    $hidden .= ' hidden';
                }
                $controlHtml = $control->render();
            }
            $html .= '<li class=" ' . FormControl::DEFAULT_CLASS . $hidden .  '">' . $controlHtml . '</li>';
        }
        $html .= '</ol></fieldset>';
        return $html;
    }

    private $controls = [];
}

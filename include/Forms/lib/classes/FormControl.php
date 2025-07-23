<?php

/**
 * FormControl file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FormControl
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

abstract class FormControl
{
    public function __construct($controlType, $controlId, $labelText)
    {
        $this->controlType = $controlType;
        $this->controlId = $controlId;
        $this->labelText = $labelText;
        $this->controlData = null;
        $this->selected = false;
        $this->isRequired = false;
        $this->isMissing = false;
        $this->hidden = false;

        $this->validator = FormValidator::DEFAULT_VALIDATOR;
    }
    /**
     * Creates a new FormControl object.
     *
     * @param string $controlType
     * @param string $controlId
     * @param string $labelText
     * @return FormControl
     */
    public static function create($controlType, $controlId, $labelText)
    {
        switch ($controlType) {
            case self::INPUT_CHECKBOX:
            case self::INPUT_RADIO:
                return new FCInputCheckable($controlType, $controlId, $labelText);
            case self::INPUT_HIDDEN:
                return new FCInputHidden($controlType, $controlId, $labelText);
            case self::INPUT_FILE:
            case self::INPUT_TEXT:
            case self::INPUT_PASSWORD:
                return new FCInput($controlType, $controlId, $labelText);
            case self::SELECT:
                return new FCSelect($controlType, $controlId, $labelText);
            case self::OPTION:
                return new FCOption($controlType, $controlId, $labelText);
            case self::TEXTAREA:
                return new FCTextarea($controlType, $controlId, $labelText);
            case self::FIELDSET:
                return new FCFieldset($controlType, $controlId, $labelText);
            case self::INPUT_BUTTON:
                return new FCButton($controlType, $controlId, $labelText);
            case self::INPUT_IMAGE:
            default:
                return new FCNullControl($controlType, $controlId, $labelText);
        }
    }
    /**
     * Returns the label for this form control.
     *
     * @return string the html for the control's label
     */
    protected function label()
    {
        $html = '<label for="' . $this->controlId . '" id="l_' . $this->controlId . '" class="' . self::DEFAULT_CLASS;
        if ($this->isMissing) {
            $html .= ' error';
        }
        $html .= '" >' . $this->labelText;
        if ($this->isRequired) {
            $html .= ' (*)';
        }
        $html .= '</label>';
        return $html;
    }

    /**
     * Returns the html for this attributes form control.
     *
     * @return string the html for the control's attributes
     */
    protected function renderAttributes()
    {
        $htmlAttributes = '';
        foreach ($this->attributes as $key => $value) {
            $htmlAttributes .= ' ' . $key . '="' . $value . '"';
        }
        return $htmlAttributes;
    }

    /**
     * Sets the data of this form control to $data.
     *
     * @param mixed $data
     * @return FormControl
     */
    public function withData($data)
    {
        $this->controlData = $data;
        return $this;
    }
    /**
     * Renders the element as html code.
     * To be implemented by extending classes
     *
     * @return string
     */
    abstract public function render();
    /**
     * Sets this form control as selected.
     *
     * @return FormControl
     */
    public function setSelected()
    {
        $this->selected = true;
        return $this;
    }
    public function setNotSelected()
    {
        $this->selected = false;
        return $this;
    }

    /**
     * Sets this form control as required.
     *
     * @return FormControl
     */
    public function setHidden()
    {
        $this->hidden = true;
        return $this;
    }

    /**
     * Sets this form control as required.
     *
     * @return FormControl
     */
    public function setRequired()
    {
        $this->isRequired = true;
        return $this;
    }
    /**
     * Sets this form control as missing.
     * @return FormControl
     */
    public function setIsMissing()
    {
        $this->isMissing = true;
        return $this;
    }

    public function getIsMissing()
    {
        return $this->isMissing;
    }

    /**
     * Sets this form control as attribute.
     * @return FormControl
     */
    public function setAttribute($attribute, $value)
    {
        if (isset($this->attributes['class']) && $attribute == 'class') {
            $value = $this->attributes['class'] . ' ' . $value;
        }
        $this->attributes[$attribute] = $value;
        //        $this->isMissing = TRUE;
        return $this;
    }
    /**
     * Returns true if this form control is marked as selected.
     * @return boolean
     */
    public function isHidden()
    {
        return $this->hidden;
    }
    /**
     * Returns true if this form control is marked as selected.
     * @return boolean
     */
    public function isSelected()
    {
        return $this->selected;
    }
    /**
     * Returns true if this form control was marked as required.
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->isRequired;
    }
    /**
     * Returns the id of this form control.
     *
     * @return string
     */
    public function getId()
    {
        return $this->controlId;
    }
    /**
     * Set the id of this form control.
     *
     * @return self
     */
    public function setId($controlId)
    {
        $this->controlId = $controlId;
        return $this;
    }
    /**
     * Returns the data contained in this form control.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->controlData;
    }
    /**
     * Returns the validator assigned to this form control.
     *
     * @return integer
     */
    public function getValidator()
    {
        return $this->validator;
    }
    /**
     * Sets the validator for this form control.
     *
     * @param integer $validator
     *
     * @return FormControl
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
        return $this;
    }
    /**
     *
     * @var string
     */
    protected $controlId;
    /**
     *
     * @var string
     */
    protected $labelText;
    /**
     *
     * @var string
     */
    protected $controlType;
    /**
     *
     * @var array
     */
    protected $controlData;
    /**
     *
     * @var boolean
     */
    protected $selected;
    /**
     *
     * @var boolean
     */
    protected $hidden;
    /**
     *
     * @var boolean
     */
    protected $isRequired;
    /**
     *
     * @var boolean
     */
    protected $isMissing;
    /**
     *
     * @var integer
     */
    protected $validator;
    /**
     *
     * @var array
     */
    protected $attributes = ['class' => self::DEFAULT_CLASS];


    public const INPUT_TEXT = 'text';
    public const INPUT_PASSWORD = 'password';
    public const INPUT_CHECKBOX = 'checkbox';
    public const INPUT_RADIO = 'radio';
    public const INPUT_SUBMIT = 'submit';
    public const INPUT_IMAGE = 'image';
    public const INPUT_RESET = 'reset';
    public const INPUT_BUTTON = 'button';
    public const INPUT_HIDDEN = 'hidden';
    public const INPUT_FILE = 'file';
    public const SELECT = 'select';
    public const OPTION = 'option';
    public const TEXTAREA = 'textarea';
    public const FIELDSET = 'fieldset';


    public const DEFAULT_CLASS = 'form';
}

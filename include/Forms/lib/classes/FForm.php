<?php

/**
 * FForm.inc.php file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Provides simple form creation methods.
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

use Lynxlab\ADA\CORE\html4\CBaseAttributesElement;
use Lynxlab\ADA\Main\Forms\lib\classes\FCFieldset;
use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

abstract class FForm
{
    public function __construct()
    {
        $this->action = '';
        $this->method = 'POST';
        $this->enctype = '';
        $this->accept = '';
        $this->name = '';
        $this->onSubmit = '';
        $this->onReset = '';
        $this->acceptCharset = '';
        $this->id = '';
        $this->controls = [];
    }
    /**
     * Given a Request object, uses its contents to fill the controls in the
     * form.
     *
     * @param Request $request
     */
    final public function fillWithRequestData($request)
    {
        foreach ($this->controls as $control) {
            if (!($control instanceof FormControl)) {
                continue;
            }
            $control->withData($request->getArgument($control->getId()));
        }
    }
    /**
     * Fills the controls in the form with contents of $_POST.
     */
    final public function fillWithPostData()
    {
        $this->fillWithArrayData($_POST);
        //         foreach($this->controls as $control) {
        //             if(isset($_POST[$control->getId()]) &&!($control instanceof FCFieldset) ) {
        //                 $control->withData($_POST[$control->getId()]);
        //             }
        //             else if ($control instanceof FCFieldset)
        //             {
        //              foreach ($control->getControls() as $field)
        //              {
        //                  if (isset($_POST[$field->getId()]))
        //                  {
        //                      $field->withData($_POST[$field->getId()]);
        //                  }
        //              }
        //             }
        //         }
    }
    /**
     * Fills the controls in the form with contents of the given array.
     */
    final public function fillWithArrayData($formData = [])
    {
        foreach ($this->controls as $control) {
            if (!($control instanceof FormControl)) {
                continue;
            }
            if (isset($formData[$control->getId()]) && (!($control instanceof FCFieldset))) {
                $control->withData($formData[$control->getId()]);
            } elseif ($control instanceof FCFieldset) {
                foreach ($control->getControls() as $field) {
                    if (isset($formData[$field->getId()])) {
                        $field->withData($formData[$field->getId()]);
                    }
                }
            }
        }
    }

    /**
     * Iterates over each control in the form and uses FormValidator to validate
     * it. If all the controls in the form are valid, the form is valid.
     *
     * @return boolean
     */
    final public function isValid()
    {
        $isValid = true;
        $validator = new FormValidator();
        foreach ($this->controls as $control) {
            if ($control instanceof FormControl && $control->isRequired()) {
                if (!$validator->validate($control)) {
                    $control->setIsMissing();
                    $isValid = false;
                }
            }
        }
        return $isValid;
    }

    public function toArray()
    {
        $formAsArray = [];
        foreach ($this->controls as $control) {
            if (!$control instanceof FCFieldset && $control instanceof FormControl) {
                $formAsArray[$control->getId()] = $control->getData();
            } elseif ($control instanceof FCFieldset) {
                foreach ($control->getControls() as $field) {
                    $formAsArray[$field->getId()] = $field->getData();
                }
            }
        }
        return $formAsArray;
    }

    public function getControls()
    {
        return $this->controls;
    }

    protected function setCustomJavascript($js, $append = true)
    {
        if ($append) {
            $this->customJavascript .= "\n" . $js;
        } else {
            $this->customJavascript = $js;
        }
    }

    protected function setId($id)
    {
        $this->id = $id;
    }

    protected function setAction($action)
    {
        $this->action = $action;
    }

    protected function setMethod($method)
    {
        $this->method = $method;
    }

    protected function setEncType($encType)
    {
        $this->enctype = $encType;
    }

    protected function setAccept($accept)
    {
        $this->accept = $accept;
    }

    protected function setName($name)
    {
        $this->name = $name;
    }

    protected function setOnSubmit($onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    protected function setOnReset($onReset)
    {
        $this->onReset = $onReset;
    }

    protected function setAcceptCharset($acceptCharset)
    {
        $this->acceptCharset = $acceptCharset;
    }

    protected function setSubmitValue($submitValue)
    {
        $this->submitValue = $submitValue;
    }

    /**
     * @author giorgio 01/lug/2013
     *
     * getter for the form name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /*
     * Form controls creational methods
     */

    /**
     * Adds the given FormControl and returns it so that it is possible to call
     * FormControl's methods after its creation.
     *
     * @param FormControl $control
     * @return FormControl
     */
    public function addControl(FormControl $control)
    {
        $this->controls[] = $control;
        return $control;
    }
    /**
     * Adds a new text input.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addTextInput($id, $label)
    {
        $control = FormControl::create(FormControl::INPUT_TEXT, $id, $label);
        return $this->addControl($control);
    }
    /**
     * Adds a new password input.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addPasswordInput($id, $label)
    {
        $control = FormControl::create(FormControl::INPUT_PASSWORD, $id, $label);
        return $this->addControl($control);
    }
    /**
     * Adds a new file input.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addFileInput($id, $label)
    {
        $control = FormControl::create(FormControl::INPUT_FILE, $id, $label);
        return $this->addControl($control);
    }
    /**
     * Adds the given checkboxes.
     *
     * @param string $id
     * @param string $label
     * @param array $data value and label for each checkbox
     * @param mixed $checked an array of values or a single value
     * @return FormControl
     */
    final protected function addCheckboxes($id, $label, $data, $checked)
    {
        $checkboxButtons = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $value => $text) {
                $control = FormControl::create(FormControl::INPUT_CHECKBOX, $id, $text)
                         ->withData($value);
                if (
                    (!is_array($checked) && $value == $checked) ||
                    (is_array($checked) && in_array($value, $checked))
                ) {
                    $control->setSelected();
                }
                //$this->addControl($control);
                $checkboxButtons[] = $control;
            }
        }
        return $this->addControl(FormControl::create(FormControl::FIELDSET, $id, $label))
                    ->withData($checkboxButtons);
    }
    /**
     * Adds the given radio buttons.
     *
     * @param string $id
     * @param string $label
     * @param array $data value and label for each radio button
     * @param string $checked the value of the radio button to be checked
     * @return FormControl
     */
    final protected function addRadios($id, $label, $data, $checked)
    {
        $radioButtons = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $value => $text) {
                $control = FormControl::create(FormControl::INPUT_RADIO, $id, $text)
                         ->withData($value);
                //$this->addControl($control);
                $radioButtons[] = $control;
                if ($value == $checked) {
                    $control->setSelected();
                }
            }
        }
        return $this->addControl(FormControl::create(FormControl::FIELDSET, $id, $label))
                    ->withData($radioButtons);
    }
    /**
     * Adds the given select.
     *
     * @param string $id
     * @param string $label
     * @param array $data value and label for each select option
     * @param string $checked the value of the option to be checked
     * @return FormControl|void
     */
    final protected function addSelect($id, $label, $data, $checked)
    {
        if (is_array($data) && count($data) > 0) {
            $control = FormControl::create(FormControl::SELECT, $id, $label)
                     ->withData($data, $checked);
            return $this->addControl($control);
        }
    }
    /**
     * Adds a new textarea.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addTextarea($id, $label)
    {
        $control = FormControl::create(FormControl::TEXTAREA, $id, $label);
        return $this->addControl($control);
    }
    /**
     * Adds a new button.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addButton($id, $label)
    {
        $control = FormControl::create(FormControl::INPUT_BUTTON, $id, $label);
        return $this->addControl($control);
    }
    /**
     * Adds a new hidden input.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addHidden($id)
    {
        $control = FormControl::create(FormControl::INPUT_HIDDEN, $id, '');
        return $this->addControl($control);
    }
    /**
     * Adds a new fieldset.
     *
     * @param string $id
     * @param string $label
     * @return FormControl
     */
    final protected function addFieldset($label, $id = '')
    {
        $control = FormControl::create(FormControl::FIELDSET, $id, $label);
        return $this->addControl($control);
    }
    final protected function addSubmit($id)
    {
    }
    final protected function addReset($id)
    {
    }

    /**
     * Adds a CDOM element to the form
     *
     * @param \CBaseAttributesElement $element
     * @return FForm
     */
    public function addCDOM(CBaseAttributesElement $element)
    {
        $this->controls[] = $element;
        return $this;
    }

    /*
     * Rendering
     */
    /*
     action      %URI;          #REQUIRED -- server-side form handler --
     method      (GET|POST)     GET       -- HTTP method used to submit the form--
     enctype     %ContentType;  "application/x-www-form-urlencoded"
     accept      %ContentTypes; #IMPLIED  -- list of MIME types for file upload --
     name        CDATA          #IMPLIED  -- name of form for scripting --
     onsubmit    %Script;       #IMPLIED  -- the form was submitted --
     onreset     %Script;       #IMPLIED  -- the form was reset --
     accept-charset %Charsets;  #IMPLIED  -- list of supported charsets --
*/


    /**
     * Renders the form and its controls.
     *
     * @return string the html to be rendered
     */
    public function render()
    {
        $submitButton = $this->submitValue();
        if ($submitButton == '') {
            $submitButton = ' value="' . translateFN('Invia') . '"';
        }
        $html = '<div class="fform ' . FormControl::DEFAULT_CLASS . '">
			<form' . $this->formId() . $this->formAction() . $this->formMethod() . $this->formEncType() . $this->formAccept() . $this->formName() . $this->formOnSubmit() . $this->formOnReset() . $this->formAcceptCharset() . '>
  <fieldset class="' . FormControl::DEFAULT_CLASS . '">
    <ol class="' . FormControl::DEFAULT_CLASS . '">';

        foreach ($this->controls as $control) {
            $hidden = '';
            if ($control instanceof CBaseAttributesElement) {
                if ($control->getAttribute('data-render-hiddenparent')) {
                    $hidden .= ' hidden';
                    unset($control->{'data-render-hiddenparent'});
                }
                $html .= '<li class="' . FormControl::DEFAULT_CLASS . $hidden . '">' . $control->getHtml() . '</li>';
            } elseif ($control instanceof FormControl) {
                if ($control->isHidden()) {
                    $hidden .= ' hidden';
                }
                $html .= '<li class="' . FormControl::DEFAULT_CLASS . $hidden . '">' . $control->render() . '</li>';
            }
        }
        $html .= '
   </ol>
   </fieldset>
   <div id="error_form_' . $this->name . '" class="hide_error form">
		' . translateFN('Sono presenti errori nel form, si prega di correggere le voci evidenziate in rosso') . '
   </div>
   <p class="' . FormControl::DEFAULT_CLASS . ' submit"><input ' . $submitButton . ' class="' . FormControl::DEFAULT_CLASS . '" type="submit" id="submit_' . $this->name . '" name="submit_' . $this->name . '" onClick="return validate_' . $this->name . '();"' . $this->submitValue() . '/></p>
</form>
</div>';

        $html .= $this->addJsValidation() . "\n";
        $html .= $this->addCustomJavascript() . "\n";

        return $html;
    }


    public function getHtml()
    {
        return $this->render();
    }

    /**
     * Adds the custom javascript specified by user
     *
     * @return string the custom javascript specified by user
     */
    private function addCustomJavascript()
    {
        if (!is_null($this->customJavascript)) {
            return '<script type="text/javascript">
				' . $this->customJavascript . '
			</script>';
        }
    }

    /**
     * Adds the javascript used to validate the form.
     *
     * @return string the javascript used to validate the form.
     */
    private function addJsValidation()
    {
        $validator = new FormValidator();
        $jsFields = [];
        $jsRegexps = [];


        foreach ($this->controls as $control) {
            if (!($control instanceof FormControl)) {
                continue;
            }
            $v = $control->getValidator();
            if (!is_null($v)) {
                if (! $control instanceof FCFieldset) {
                    if ($control->isRequired()) {
                        $jsFields[] = $control->getId();
                        $jsRegexps[] = $validator->getRegexpForValidator($control->getValidator());
                    }
                } else {
                    foreach ($control->getControls() as $field) {
                        $vField = $field->getValidator();
                        if ($field->isRequired()) {
                            $jsFields[] = $field->getId();
                            $jsRegexps[] = $validator->getRegexpForValidator($vField);
                        }
                    }
                }
                //              $jsRegexps[] = $validator->getRegexpForValidator($control->getValidator());
                //              $jsRegexps[] = $validator->getRegexpForValidator($v);
            }
        }
        $html = '<script type="text/javascript">
					var validateContentFields_' . $this->name . ' = new Array("' . implode('","', $jsFields) . '");
					var validateContentRegexps_' . $this->name . ' = new Array(' . implode(',', $jsRegexps) . ');
					function validate_' . $this->name . '() {
						return validateContent(validateContentFields_' . $this->name . ',validateContentRegexps_' . $this->name . ' , "' . $this->name . '");
					}
				</script>';

        return $html;
    }
    /**
     * Returns the id attribute for the form element.
     *
     * @return string
     */
    private function formId()
    {
        if ($this->id != '') {
            return ' id="' . $this->id . '"';
        }
        return '';
    }
    /**
     * Returns the action attribute for the form element.
     *
     * @return string
     */
    private function formAction()
    {
        if ($this->action != '') {
            return ' action="' . $this->action . '"';
        }
        return '';
    }
    /**
     * Returns the method attribute for the form element.
     *
     * @return string
     */
    private function formMethod()
    {
        if ($this->method != '') {
            return ' method="' . $this->method . '"';
        }
        return ' method="POST"';
    }
    /**
     * Returns the enctype attribute for the form element.
     *
     * @return string
     */
    private function formEncType()
    {
        if ($this->enctype != '') {
            return ' enctype="' . $this->enctype . '"';
        }
        return '';
    }

    /**
     * Returns the accept attribute for the form element.
     *
     * @return string
     */
    private function formAccept()
    {
        if ($this->accept != '') {
            return ' accept="' . $this->accept . '"';
        }
        return '';
    }

    /**
     * Returns the name attribute for the form element.
     *
     * @return string
     */
    private function formName()
    {
        if ($this->name != '') {
            return ' name="' . $this->name . '"';
        }
        return '';
    }

    /**
     * Returns the onsubmit attribute for the form element.
     *
     * @return string
     */
    private function formOnSubmit()
    {
        if ($this->onSubmit != '') {
            return ' onsubmit="' . $this->onSubmit . '"';
        }
        return '';
    }

    /**
     * Returns the onreset attribute for the form element.
     *
     * @return string
     */
    private function formOnReset()
    {
        if ($this->onReset != '') {
            return ' onreset="' . $this->onReset . '"';
        }
        return '';
    }

    /**
     * Returns the accept-charset attribute for the form element.
     *
     * @return string
     */
    private function formAcceptCharset()
    {
        if ($this->acceptCharset != '') {
            return ' accept-charset="' . $this->acceptCharset . '"';
        }
        return '';
    }

    /**
     * Returns the submit value
     *
     * @return string
     */
    private function submitValue()
    {
        if ($this->submitValue != '') {
            return ' value="' . $this->submitValue . '"';
        }
        return '';
    }

    /**
     *
     * @var string
     */
    protected $id;
    /**
     *
     * @var string
     */
    private $action;
    /**
     *
     * @var string
     */
    private $method;
    /**
     *
     * @var string
     */
    private $enctype;
    /**
     *
     * @var string
     */
    private $accept;
    /**
     *
     * @var string
     */
    private $name;
    /**
     *
     * @var string
     */
    private $onSubmit;
    /**
     *
     * @var string
     */
    private $onReset;
    /**
     *
     * @var string
     */
    private $acceptCharset;
    /**
     *
     * @var array
     */
    protected $controls;
    /**
     *
     * @var string
     */
    private $submitValue = '';
    /**
     *
     * @var string
     */
    private $customJavascript = null;
}

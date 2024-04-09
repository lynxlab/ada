<?php

/**
 * @package     collabora-access-list module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\CollaboraACL;

use Lynxlab\ADA\CORE\html4\CBaseAttributesElement;
use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormControl;

/**
 * class for handling all module forms
 *
 * @author giorgio
 *
 */

abstract class CollaboraACLAbstractForm extends FForm
{
    private $withSubmit;
    private $isReadOnly;

    protected $doNotSemanticUI = false;
    protected $maxlength = 255;

    public function __construct($formName = null, $action = null)
    {
        parent::__construct();
        if (!is_null($formName)) {
            $this->setName($formName);
        }
        if (!is_null($action)) {
            $this->setAction($action);
        }

        $this->withSubmit = false;
    }

    public function addCDOM(CBaseAttributesElement $element)
    {
        $this->controls[] = $element;
        return $this;
    }

    public function getHtml()
    {
        if (strlen($this->getName()) <= 0) {
            $this->setName($this->id);
        }
        if ($this->withSubmit === false) {
            $this->removeSubmit();
        }
        return parent::getHtml();
    }

    public function withSubmit()
    {
        $this->withSubmit = true;
        return $this;
    }

    public function toSemanticUI()
    {
        if (!$this->doNotSemanticUI) {
            $this->setCustomJavascript('
					$j("#' . $this->id . ' select").addClass("ui form input");
					$j("#' . $this->id . '").parents("div.fform").addClass("ui");
					$j("#error_form_' . $this->id . '").addClass("ui red message");', true);
            if ($this->withSubmit) {
                $this->setCustomJavascript('
					$j("#submit_' . $this->id . '").addClass("ui button");', true);
            }
        }
        return $this;
    }

    public function addJSDataProperty($key, $value)
    {
        if (is_string($value)) {
            $value = '"' . $value . '"';
        } elseif (is_bool($value)) {
            $value = ($value ? 'true' : 'false');
        }
        $this->setCustomJavascript('$j("#' . $this->id . '").data("' . $key . '",' . $value . ');', true);
    }

    public function withUIClassOnLi()
    {
        $this->setCustomJavascript('$j("#' . $this->id . ' ol.form>li.form").addClass("ui field");', true);
        return $this;
    }

    private function removeSubmit()
    {
        $this->setCustomJavascript('$j("#' . $this->id . ' >p.submit").remove();');
    }

    /**
     * Get isReadOnly
     *
     * @return boolean
     */
    public function getIsReadOnly()
    {
        return $this->isReadOnly;
    }

    /**
     * Set isReadOnly
     * @param boolean $isReadOnly
     *
     * @return \Lynxlab\ADA\Module\CollaboraACL\CollaboraACLAbstractForm
     */
    protected function setIsReadOnly($isReadOnly)
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }

    /**
     * Adds this form controls to the passed form, without building a new form
     *
     * @param FForm $theForm
     * @return FForm
     */
    public function addControlsToForm(FForm $theForm)
    {
        array_map(function ($control) use ($theForm) {
            if ($control instanceof FormControl) {
                $theForm->addControl($control);
            } elseif ($control instanceof CBaseAttributesElement) {
                $theForm->addCDOM($control);
            }
        }, $this->getControls());
        return $theForm;
    }
}

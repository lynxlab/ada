<?php

use Lynxlab\ADA\Module\CloneInstance\CloneInstanceAbstractForm;

// Trigger: ClassWithNameSpace. The class CloneInstanceAbstractForm was declared with namespace Lynxlab\ADA\Module\CloneInstance. //

/**
 * @package     cloneinstance module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2022, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\CloneInstance;

use Lynxlab\ADA\CORE\html4\CBaseAttributesElement;
use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

/**
 * class for handling all module forms
 *
 * @author giorgio
 *
 */

abstract class CloneInstanceAbstractForm extends FForm
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
     * @return CloneInstanceAbstractForm
     */
    protected function setIsReadOnly($isReadOnly)
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }
}

<?php

namespace Lynxlab\ADA\Main\HtmlLibrary;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

class FormElementCreator
{
    public static function addTextInput($name, $label_text, $valuesAr = [], $errorsAr = [], $attributes = '', $required = false)
    {
        $input = CDOMElement::create('text', "id:$name, name:$name");
        $input->setAttributes($attributes);

        if (is_array($valuesAr) && isset($valuesAr[$name])) {
            $input->setAttribute('value', $valuesAr[$name]);
        }
        if (is_array($errorsAr) && isset($errorsAr[$name])) {
            $error_message = translateFN("Attenzione: il campo &egrave; vuoto o contiene caratteri non validi");
        } else {
            $error_message = null;
        }
        $div = self::controlContainer($name, $label_text, $error_message);
        $div->addChild($input);
        if ($required) {
            $div->addChild(new CText('(*)'));
        }

        return $div;
    }
    public static function addDateInput($name, $label_text, $valuesAr = [], $errorsAr = [], $attributes = '', $required = false)
    {
        $input = CDOMElement::create('text', "id:$name, name:$name");
        $input->setAttributes($attributes);

        if (is_array($valuesAr) && isset($valuesAr[$name])) {
            $input->setAttribute('value', $valuesAr[$name]);
        }
        if (is_array($errorsAr) && isset($errorsAr[$name])) {
            $error_message = translateFN("Attenzione: il campo &egrave; vuoto o contiene una data non valida");
        } else {
            $error_message = null;
        }
        $div = self::controlContainer($name, $label_text, $error_message);
        $div->addChild($input);
        if ($required) {
            $div->addChild(new CText('(*)'));
        }

        return $div;
    }
    public static function addPasswordInput($name, $label_text, $errorsAr = [], $attributes = '', $required = false)
    {
        $password = CDOMElement::create('password', "id:$name, name:$name");
        $password->setAttributes($attributes);

        if (is_array($errorsAr) && isset($errorsAr[$name])) {
            $error_message = translateFN("Attenzione: le password digitate non corrispondono");
        } else {
            $error_message = null;
        }
        $div = self::controlContainer($name, $label_text, $error_message);
        $div->addChild($password);
        if ($required) {
            $div->addChild(new CText('(*)'));
        }
        return $div;
    }

    public static function addTextArea($name, $label_text, $valuesAr = [], $errorsAr = [], $attributes = '', $required = false)
    {
        $textarea = CDOMElement::create('textarea', "id:$name, name:$name");
        $textarea->setAttributes($attributes);

        if (is_array($valuesAr) && isset($valuesAr[$name])) {
            $textarea->addChild(new CText($valuesAr[$name]));
        }

        if (is_array($errorsAr) && isset($errorsAr[$name])) {
            $error_message = translateFN("Attenzione: il campo &egrave; vuoto o contiene caratteri non validi");
        } else {
            $error_message = null;
        }
        $div = self::controlContainer($name, $label_text, $error_message);
        $div->addChild($textarea);
        if ($required) {
            $div->addChild(new CText('(*)'));
        }
        return $div;
    }

    public static function addSelect($name, $label_text, $dataAr = [], $valuesAr = [], $errorsAr = [], $attributes = '', $required = false)
    {
        if (is_array($valuesAr) && isset($valuesAr[$name])) {
            $select = BaseHtmlLib::selectElement2("id:$name, name:$name", $dataAr, $valuesAr[$name]);
        } else {
            $select = BaseHtmlLib::selectElement2("id:$name, name:$name", $dataAr);
        }

        $select->setAttributes($attributes);
        if (is_array($errorsAr) && isset($errorsAr[$name])) {
            $error_message = translateFN("Attenzione: &egrave; necessario scegliere il " . $label_text);
        } else {
            $error_message = null;
        }

        $div = self::controlContainer($name, $label_text, $error_message);
        $div->addChild($select);
        if ($required) {
            $div->addChild(new CText('(*)'));
        }
        return $div;
    }

    public static function addSubmitAndResetButtons($submitClass = '', $resetClass = '')
    {
        $div = CDOMElement::create('div', 'id:buttons');
        $submit = CDOMElement::create('submit', 'id:submit, name:submit');
        $submit->setAttribute('value', translateFN('Invia'));
        if (strlen($submitClass) > 0) {
            $submit->setAttribute('class', $submitClass);
        }
        $reset = CDOMElement::create('reset', 'id:reset, name:reset');
        if (strlen($resetClass) > 0) {
            $reset->setAttribute('class', $resetClass);
        }
        $div->addChild($submit);
        $div->addChild($reset);

        return $div;
    }

    private static function controlContainer($name, $label_text, $error_message = null)
    {
        $div   = CDOMElement::create('div', "id:div_$name");
        $label = CDOMElement::create('label', "for:$name");
        $label->addChild(new CText(translateFN($label_text)));
        $div->addChild($label);
        if ($error_message != null) {
            $label->setAttribute('class', $label->getAttribute('class') . ' error');
            $error_div = CDOMElement::create('div', 'class:error');
            $error_div->addChild(new CText($error_message));
            $div->addChild($error_div);
        }

        return $div;
    }
}

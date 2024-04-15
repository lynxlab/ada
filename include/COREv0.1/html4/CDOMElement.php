<?php

use Lynxlab\ADA\CORE\html4\CUl;

use Lynxlab\ADA\CORE\html4\CTr;

use Lynxlab\ADA\CORE\html4\CTObject;

use Lynxlab\ADA\CORE\html4\CTHead;

use Lynxlab\ADA\CORE\html4\CTh;

use Lynxlab\ADA\CORE\html4\CTFoot;

use Lynxlab\ADA\CORE\html4\CTextarea;

use Lynxlab\ADA\CORE\html4\CTd;

use Lynxlab\ADA\CORE\html4\CTBody;

use Lynxlab\ADA\CORE\html4\CTable;

use Lynxlab\ADA\CORE\html4\CSubmitInput;

use Lynxlab\ADA\CORE\html4\CSpan;

use Lynxlab\ADA\CORE\html4\CSelect;

use Lynxlab\ADA\CORE\html4\CResetInput;

use Lynxlab\ADA\CORE\html4\CRadio;

use Lynxlab\ADA\CORE\html4\COption;

use Lynxlab\ADA\CORE\html4\COptgroup;

use Lynxlab\ADA\CORE\html4\COl;

use Lynxlab\ADA\CORE\html4\CMap;

use Lynxlab\ADA\CORE\html4\CLink;

use Lynxlab\ADA\CORE\html4\CLi;

use Lynxlab\ADA\CORE\html4\CLegend;

use Lynxlab\ADA\CORE\html4\CLabel;

use Lynxlab\ADA\CORE\html4\CInputText;

use Lynxlab\ADA\CORE\html4\CInputPassword;

use Lynxlab\ADA\CORE\html4\CImg;

use Lynxlab\ADA\CORE\html4\CIFrame;

use Lynxlab\ADA\CORE\html4\CI;

use Lynxlab\ADA\CORE\html4\CHiddenInput;

use Lynxlab\ADA\CORE\html4\CH4;

use Lynxlab\ADA\CORE\html4\CH3;

use Lynxlab\ADA\CORE\html4\CH2;

use Lynxlab\ADA\CORE\html4\CH1;

use Lynxlab\ADA\CORE\html4\CForm;

use Lynxlab\ADA\CORE\html4\CFileInput;

use Lynxlab\ADA\CORE\html4\CFieldset;

use Lynxlab\ADA\CORE\html4\CElement;

use Lynxlab\ADA\CORE\html4\CDt;

use Lynxlab\ADA\CORE\html4\CDOMElement;

use Lynxlab\ADA\CORE\html4\CDl;

use Lynxlab\ADA\CORE\html4\CDiv;

use Lynxlab\ADA\CORE\html4\CDd;

use Lynxlab\ADA\CORE\html4\CColgroup;

use Lynxlab\ADA\CORE\html4\CCol;

use Lynxlab\ADA\CORE\html4\CCheckbox;

use Lynxlab\ADA\CORE\html4\CCaption;

use Lynxlab\ADA\CORE\html4\CButtonInput;

use Lynxlab\ADA\CORE\html4\CButton;

use Lynxlab\ADA\CORE\html4\CBaseElement;

use Lynxlab\ADA\CORE\html4\CArea;

use Lynxlab\ADA\CORE\html4\CA;

// Trigger: ClassWithNameSpace. The class CDOMElement was declared with namespace Lynxlab\ADA\CORE\html4. //

namespace Lynxlab\ADA\CORE\html4;

class CDOMElement
{
    /**
     * Undocumented function
     *
     * @param string $element_name
     * @param string $a_list_of_attribute_values_pairs
     * @return \Lynxlab\ADA\CORE\html4\CElement
     */
    public static function create($element_name, $a_list_of_attribute_values_pairs = null)
    {
        $element_name = strtolower($element_name);

        switch ($element_name) {
            case 'ol':
                $element = new COl();
                break;
            case 'ul':
                $element = new CUl();
                break;
            case 'li':
                $element = new CLi();
                break;
            case 'dl':
                $element = new CDl();
                break;
            case 'dt':
                $element = new CDt();
                break;
            case 'dd':
                $element = new CDd();
                break;
            case 'table':
                $element = new CTable();
                break;
            case 'caption':
                $element = new CCaption();
                break;
            case 'fieldset':
                $element = new CFieldset();
                break;
            case 'span':
                $element = new CSpan();
                break;
            case 'div':
                $element = new CDiv();
                break;
            case 'optgroup':
                $element = new COptgroup();
                break;
            case 'option':
                $element = new COption();
                break;
            case 'thead':
                $element = new CTHead();
                break;
            case 'tfoot':
                $element = new CTFoot();
                break;
            case 'tbody':
                $element = new CTBody();
                break;
            case 'colgroup':
                $element = new CColgroup();
                break;
            case 'tr':
                $element = new CTr();
                break;
            case 'td':
                $element = new CTd();
                break;
            case 'th':
                $element = new CTh();
                break;
            case 'a':
                $element = new CA();
                break;
            case 'textarea':
                $element = new CTextarea();
                break;
            case 'button':
                $element = new CButton();
                break;
            case 'select':
                $element = new CSelect();
                break;
            case 'label':
                $element = new CLabel();
                break;
            case 'legend':
                $element = new CLegend();
                break;
            case 'object':
                $element = new CTObject();
                break;
            case 'map':
                $element = new CMap();
                break;
            case 'form':
                $element = new CForm();
                break;
            case 'col':
                $element = new CCol();
                break;
            case 'link':
                $element = new CLink();
                break;
            case 'img':
                $element = new CImg();
                break;
            case 'area':
                $element = new CArea();
                break;
            case 'file':
                $element = new CFileInput();
                break;
            case 'hidden':
                $element = new CHiddenInput();
                break;
            case 'submit':
                $element = new CSubmitInput();
                break;
            case 'reset':
                $element = new CResetInput();
                break;
            case 'text':
                $element = new CInputText();
                break;
            case 'password':
                $element = new CInputPassword();
                break;
            case 'input_button':
                $element = new CButtonInput();
                break;
            case 'checkbox':
                $element = new CCheckbox();
                break;
            case 'radio':
                $element = new CRadio();
                break;
            case 'iframe':
                $element = new CIFrame();
                break;
            case 'i':
                $element = new CI();
                break;
            case 'h1':
                $element = new CH1();
                break;
            case 'h2':
                $element = new CH2();
                break;
            case 'h3':
                $element = new CH3();
                break;
            case 'h4':
                $element = new CH4();
                break;
            default:
                return null;
        }

        if ($element instanceof CBaseElement) {
            $element->setAttributes($a_list_of_attribute_values_pairs);
            return $element;
        }

        return null;

        /* //funziona dal 5.3?
        $element_class = 'Element_'.$element_name;
        $element = new $element_class();
        $element->setAttributes($a_list_of_attribute_values_pairs);
        return $element;
        */
    }
}

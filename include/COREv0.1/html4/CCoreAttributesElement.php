<?php

/**
 * abstract class CBase: defines an abstract method, getHtml()
 * that all of the elements in this hierarchy have to redefine.
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

/**
 * abstract class CoreAttributesElement: this class defines base methods common to all
 * of the DOM elements.
 *
 * @author vito
 */
abstract class CCoreAttributesElement extends CBaseElement
{
    protected $id;
    protected $class;
    protected $style;
    protected $title;

    /**
     * children array
     *
     * @var array
     */
    protected $children;

    /**
     * which elements can be added as children
     *
     * @var array
     */
    protected $accept;

    /**
     * which elements cannot be added as children
     *
     * @var array
     */
    protected $reject;

    public function __construct()
    {
        $this->children = [];
        $this->accept   = [];
        $this->reject   = [];
    }

    public function getHtml()
    {
        $matches   = [];
        $pattern   = [];
        $attribute = [];

        $html_element = get_class($this);
        $template     = CHtmlTags::getTagForHtmlElement($html_element);

        $search_attributes = '/%([a-z]+)%/';
        preg_match_all($search_attributes, $template, $matches);

        foreach ($matches[1] as $match => $text) {
            // FIXME: avoid skipping newline
            $pattern[$match] = "/\s*%$text%\s*/";

            if ($text == 'children') {
                foreach ($this->children as $child) {
                    $attribute[$match] .= $child->getHtml();
                }
            } else {
                if (!property_exists($this, $text) || $this->$text === false) {
                    $this->$text = 'false';
                } elseif ($this->$text === true) {
                    $this->$text = 'true';
                }

                if (is_null($this->$text)) {
                    $attribute[$match] = " ";
                } elseif (empty($this->$text) && $this->$text !== 0 && $this->$text !== '0') {
                    $attribute[$match] = " $text";
                } else {
                    // the whitespace at the beginning of the string is needed
                    $attribute[$match] = " $text=\"{$this->$text}\"";
                }
            }
        }

        $html = preg_replace($pattern, $attribute, $template);

        return $html;
    }
}

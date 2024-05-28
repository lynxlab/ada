<?php

/**
 * menu_class.inc.php
 *
 * @package        menu_class.inc
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2014, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           menu_class.inc
 * @version        0.1
 */

namespace Lynxlab\ADA\Main;

use Lynxlab\ADA\CORE\html4\CBaseElement;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * ADA menu class.
 *
 * @author giorgio
 */

class Menu
{
    public const ALWAYS_ENABLED = '%ALWAYS%';
    public const NEVER_ENABLED = '%NEVER%';
    public const NON_MULTIPROVIDER_MENU = !MULTIPROVIDER;

    /**
     * set this to false to have a visibile, disabled, empty dropdown
     */
    private const SKIP_IF_NO_CHILDREN = true;

    /**
     * tree id of the menu
     *
     * @var number
     */
    private $tree_id;

    /**
     * tree id that originally linked this menu
     * null if this was not a linked menu
     *
     * @var number
     */
    private $linked_from = null;

    /**
     * nonzero if menu is vertical
     *
     * @var number
     */
    private $isVertical;

    /**
     * array of options passed by the currently executing php script
     *
     * @var array
     */
    private $menuOptions;

    /**
     * array of left hand side menu subtree
     *
     * @var array
     */
    private $leftItemsArray;

    /**
     * array of right hand side menu subtree
     *
     * @var array
     */
    private $rightItemsArray;

    /**
     * name constructor, set menu options and get the
     * left and right submenus from the DataHandler
     */
    public function __construct($module, $script, $user_type, $menuoptions)
    {

        if (!isset($menuoptions['self_instruction']) || strlen($menuoptions['self_instruction']) <= 0) {
            $self_instruction = false;
        } else {
            // set self_instuction to either 0 or 1
            $self_instruction = intval($menuoptions['self_instruction']) > 0 ? 1 : 0;
        }

        $this->menuOptions = $menuoptions;

        // get the menu from the database
        $dh = $GLOBALS['dh'];
        $getAllMenuItems = false;

        // get tree_id, isVertical and db where menu is stored
        $res = $dh->getMenutreeId($module, $script, $user_type, $self_instruction);

        if (!AMADB::isError($res) && is_array($res) && count($res) > 0) {
            // set found object properties
            $this->tree_id = $res['tree_id'];
            $this->isVertical = $res['isVertical'];
            if (isset($res['linked_from'])) {
                $this->linked_from = $res['linked_from'];
            }

            // get menu items
            $resItems = $dh->getMenuChildren($this->tree_id, $res['dbToUse'], $getAllMenuItems);
            if (!AMADB::isError($resItems) && count($resItems) > 0) {
                $this->leftItemsArray  = $resItems['left']  ?? null;
                $this->rightItemsArray = $resItems['right'] ?? null;
            }
        }
    }

    /**
     * builds all the HTML for the menu
     *
     * @return string html code for the Menu
     *
     * @access public
     */
    public function getHtml()
    {
        $mainContainer = CDOMElement::create('ul', 'class:left menu sm sm-ada');

        if (is_array($this->leftItemsArray) && count($this->leftItemsArray) > 0) {
            foreach ($this->leftItemsArray as $item) {
                $this->buildAll($mainContainer, $item, true);
            }
        }

        if (is_array($this->rightItemsArray) && count($this->rightItemsArray) > 0) {
            $rightContainer = CDOMElement::create('ul', 'class:right menu sm sm-ada');
            foreach ($this->rightItemsArray as $item) {
                $this->buildAll($rightContainer, $item, true);
            }
        }

        return $mainContainer->getHtml() . ((isset($rightContainer)) ? $rightContainer->getHtml() : '');
    }

    /**
     * checks if menu is vertical
     *
     * @return boolean true if menu is vertical
     *
     * @access public
     */
    public function isVertical()
    {
        return $this->isVertical > 0;
    }

    /**
     * tree id getter
     *
     * @return number
     *
     * @access public
     */
    public function getId()
    {
        return $this->tree_id;
    }

    /**
     * linked from getter
     *
     * @return number
     *
     * @access public
     */
    public function getLinkedFromId()
    {
        return $this->linked_from;
    }

    /**
     * actually builds the menu CBaseElement if the passed item
     * and adds them as a child to the passed container
     *
     * @param \Lynxlab\ADA\CORE\html4\CElement $container where to add the generated CBaseElement
     * @param array $item item to be generated
     * @param boolean $firstLevel true if the item is a first level one
     *
     * @access private
     */
    private function buildAll($container, $item, $firstLevel)
    {
        // do something only if item is enabled
        if ($this->isEnabled($item)) {
            if (is_null($item['children']) || !isset($item['children']) || count($item['children']) <= 0) {
                /**
                 * item has no children, so it's not a dropdown
                 * it can be either a special or an href
                 */
                if (intval($item['specialItem']) > 0 && !is_null($item['extraHTML'])) {
                    /**
                     * item is a special
                     */
                    $DOMitem = $this->buildSpecialItem($item);
                } else {
                    /**
                     * item is an href
                     */
                    $DOMitem = $this->buildHREFItem($item);
                }
            } else {
                /**
                 * item has children, it's a dropdown if one of its children is enabled
                 */
                $isDropDown = false;
                if (is_array($item['children']) && count($item['children']) > 0) {
                    foreach ($item['children'] as $index => $child) {
                        if (!$this->isEnabled($child)) {
                            $isDropDown = $isDropDown || false;
                            // unset disabled children for proper rendering
                            unset($item['children'][$index]);
                        } else {
                            $isDropDown = $isDropDown || true;
                        }
                    }
                }

                if ($isDropDown) {
                    $DOMitem = $this->buildDropDownItem($item, $firstLevel);
                } else {
                    $DOMitem = $this->buildHREFItem($item);
                }
            }

            if (isset($DOMitem)) {
                $container->addChild($DOMitem);
            }
        }
    }

    /**
     * builds and adds the common stuff to the CBaseElement that
     * is being generated: an icon, a label and the extraHTML
     *
     * @param CBaseElement $DOMitem the target CBaseElement
     * @param array $item the source generating item
     *
     * @access private
     */
    private function buildCommon($DOMitem, $item, $isDropDown = false)
    {

        // add the icon
        if (!is_null($item['icon'])) {
            $DOMitem->addChild($this->buildItemIcon($item));
        }

        // add the label inside a span
        if (!is_null($item['label'])) {
            if (!str_contains($item['label'], 'template_field')) {
                // translate label directly if it's not a template field
                $label = translateFN($item['label']);
            } else {
                /**
                 * label has some template field in it, let's translate word by word.
                 * The preg_split will take a string like:
                 *
                 * edit <template_field class="template_field" name="what">what</template_field>some text <template_field class="template_field" name="whatxxx">whatxxx</template_field> some other text
                 *
                 * and produce an array like:
                 *
                 * array (size=8)
                 *  0 => string 'edit' (length=4)
                 *  1 => string '<template_field class="template_field" name="what">what</template_field>' (length=72)
                 *  2 => string 'some' (length=4)
                 *  3 => string 'text' (length=4)
                 *  4 => string '<template_field class="template_field" name="whatxxx">whatxxx</template_field>' (length=78)
                 *  5 => string 'some' (length=4)
                 *  6 => string 'other' (length=5)
                 *  7 => string 'text' (length=4)
                 *
                 *  so that it's easy to translate word by word and then glue the
                 *  pieces together while keeping the template_field tags unmodified
                 */
                $splitted = preg_split('/(<template_field[^>]*>\w*<\/template_field>)|\s+/', $item['label'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                // if splitted has only one element, it's the template field itself
                if (is_array($splitted) && count($splitted) > 1) {
                    foreach ($splitted as $count => $word) {
                        // if $word is a not template field, translate it
                        if (str_contains($item['label'], 'template_field')) {
                            $splitted[$count] = translateFN($word);
                        }
                    }
                }
                // glue splitted array and we're done
                $label = implode(' ', $splitted);
            }
            $span = CDOMElement::create('span', 'class:menulabel');
            $span->addChild(new CText($label));
            if ($isDropDown) {
                $span->addChild($this->buildDropDownIcon());
            }
            $DOMitem->addChild($span);
        }
    }

    /**
     * builds the icon for the current element
     *
     * @param array $item current item for which to build the icon
     *
     * @return \Lynxlab\ADA\CORE\html4\CBaseElement the generated icon
     *
     * @access private
     */
    private function buildItemIcon($item)
    {
        $DOMitemIcon = CDOMElement::create('i');
        $DOMitemIcon->setAttribute('class', trim($item['icon'] . ' icon'));
        if (!is_null($item['icon_size'])) {
            $DOMitemIcon->setAttribute('class', $DOMitemIcon->getAttribute('class') . ' ' . $item['icon_size']);
        }
        return $DOMitemIcon;
    }

    /**
     * builds the extra dropdown item icon
     *
     * @return \Lynxlab\ADA\CORE\html4\CBaseElement the generated icon
     *
     * @access private
     */
    private function buildDropDownIcon()
    {
        $DOMitemIcon = CDOMElement::create('i');
        $DOMitemIcon->setAttribute('class', 'dropdown icon');
        return $DOMitemIcon;
    }

    /**
     * builds a dropdown submenu item either for first level and other levels
     * if the item has children, build all its submenus as well by calling buildAll method
     *
     * @param array $item current item for which to build the submenu
     * @param boolean $firstLevel true if current item is first level
     *
     * @return \Lynxlab\ADA\CORE\html4\CBaseElement the generated submenu
     *
     * @access private
     */
    private function buildDropDownItem($item, $firstLevel)
    {
        $DOMitem = CDOMElement::create('li');
        if (array_key_exists('item_id', $item) && strlen($item['item_id']) > 0) {
            $DOMitem->setAttribute('data-item-id', $item['item_id']);
        }

        // set class attribute
        /**
         * @author giorgio 16/set/2014
         * simple class added to have a non-js working
         * dropdown as a workaround to some bug causing
         * firefox crash on xp and vista.
         * Should you wish to revert to a js dropdown,
         * remove the simple class and uncomment
         * menu_functions.js dropdown methods call
         */
        $baseClass = 'ui item' . ($firstLevel ? ' simple dropdown ' : '');
        $DOMitem->setAttribute('class', trim($baseClass . $item['extraClass']));

        $HREFItem = CDOMElement::create('a', 'href:#,onclick:javascript:return false;');
        $this->buildCommon($HREFItem, $item, !$firstLevel);
        $DOMitem->addChild($HREFItem);

        if (!is_null($item['extraHTML'])) {
            $DOMitem->addChild(new CText($item['extraHTML']));
        }

        if ($firstLevel) {
            $DOMitem->addChild($this->buildDropDownIcon());
            if (!is_null($item['menuExtraClass'])) {
                $DOMitem->setAttribute('class', $DOMitem->getAttribute('class') . ' ' . $item['menuExtraClass']);
            }
        }

        $subContainer = CDOMElement::create('ul', 'class:menu');
        foreach ($item['children'] as $child) {
            $this->buildAll($subContainer, $child, false);
            if (!is_null($child['menuExtraClass'])) {
                $subContainer->setAttribute('class', $subContainer->getAttribute('class') . ' ' . $child['menuExtraClass']);
            }
        }

        $DOMitem->addChild($subContainer);
        return $DOMitem;
    }

    /**
     * builds and adds the querystring to the href element
     *
     * @param array $item current item for which to build the href
     * @param \Lynxlab\ADA\CORE\html4\CBaseElement $DOMitem the element where to set the href property
     *
     * @access private
     */
    private function buildHREFParams($item, $DOMitem)
    {

        if (str_contains($item['href_paramlist'], 'template_field')) {
            /**
             * if href_paramlist is a template field append it right away
             */
            $paramString = $item['href_paramlist'];
        } else {
            /**
             * if it's not a template field, must check the parameters
             * list against the passed menu options array
             */

            // explode and trim requested parameters name
            $requestedParams = array_map('trim', explode(",", $item['href_paramlist']));
            $foundParams = [];

            /**
             * search each parameter in the options array, either in parameter
             * name array key or in the <item_id, parameter name> array key
             */
            foreach ($requestedParams as $param) {
                if (isset($this->menuOptions[$param]) && strlen($this->menuOptions[$param]) > 0) {
                    $foundParams[$param] = $this->menuOptions[$param];
                } elseif (isset($this->menuOptions[$item['item_id']][$param]) && strlen($this->menuOptions[$item['item_id']][$param]) > 0) {
                    $foundParams[$param] = $this->menuOptions[$item['item_id']][$param];
                }
            }

            if (count($foundParams) > 0) {
                $paramString = http_build_query($foundParams, '', '&amp;');
            }
        }

        // set the actual href value
        if (isset($paramString) && strlen($paramString) > 0) {
            // add the question mark or ampersand to the href
            $conjunction = (!str_contains($DOMitem->getAttribute('href'), '?')) ? '?' : '&amp;';
            // add actual parameters
            $DOMitem->setAttribute('href', $DOMitem->getAttribute('href') . $conjunction . $paramString);
        }
    }

    /**
     * builds an href (aka menu tree leaf) menu element
     *
     * @param array $item current item for which to build the href
     *
     * @return \Lynxlab\ADA\CORE\html4\CBaseElement the generated href element
     */
    private function buildHREFItem($item)
    {

        $DOMitem = CDOMElement::create('a');
        $hasOnClick = false;

        // set href prefix
        if (!is_null($item['href_prefix'])) {
            $prefix = $this->constSubstitute($item['href_prefix']);
            // if prefix does not ends with a slash, add it
            if (!str_ends_with($prefix, '/')) {
                $prefix .= '/';
            }
            $DOMitem->setAttribute('href', $DOMitem->getAttribute('href') . $prefix);
        }

        // set href
        if (!is_null($item['href_path'])) {
            $DOMitem->setAttribute('href', $DOMitem->getAttribute('href') . $item['href_path']);
        }

        // set href params
        if (!is_null($item['href_paramlist'])) {
            $this->buildHREFParams($item, $DOMitem);
        }

        // set href properties
        if (!is_null($item['href_properties'])) {
            $properties = json_decode($item['href_properties'], true);
            if (is_array($properties) && count($properties) > 0) {
                foreach ($properties as $name => $value) {
                    if (stripos($name, 'onclick') !== false) {
                        $hasOnClick = true;
                    }
                    $DOMitem->setAttribute($name, $this->constSubstitute($value));
                }
            }
        }

        // do not send out without an href
        if (strlen($DOMitem->getAttribute('href') ?? '') <= 0) {
            $DOMitem->setAttribute('href', 'javascript:void(0);');
            // if element has no link and no children, add disabled class
            if (!$item['specialItem'] && !$hasOnClick && (is_null($item['children']) || !isset($item['children']) || count($item['children']) <= 0)) {
                $item['extraClass'] .= ' disabled';
                if (self::SKIP_IF_NO_CHILDREN) {
                    return new CText('');
                }
            }
        }

        // build common elements
        $this->buildCommon($DOMitem, $item);

        $LIitem = CDOMElement::create('li');
        if (array_key_exists('item_id', $item) && strlen($item['item_id'] ?? '') > 0) {
            $LIitem->setAttribute('data-item-id', $item['item_id']);
        }
        // set class attribute
        $LIitem->setAttribute('class', trim('item ' . trim($item['extraClass'] ?? '')));
        $LIitem->addChild($DOMitem);

        if (!is_null($item['extraHTML'])) {
            $LIitem->addChild(new CText($item['extraHTML']));
        }

        return $LIitem;
    }

    /**
     * builds a special menu item, such as search form in view.php menu
     *
     * @param array $item current item for which to build the special
     *
     * @return \Lynxlab\ADA\CORE\html4\CBaseElement the generated special element
     *
     * @access private
     */
    private function buildSpecialItem($item)
    {
        $DOMitem = CDOMElement::create('li', 'class:item');
        if (array_key_exists('item_id', $item) && strlen($item['item_id']) > 0) {
            $DOMitem->setAttribute('data-item-id', $item['item_id']);
        }
        if (!is_null($item['extraClass'])) {
            $DOMitem->setAttribute('class', $DOMitem->getAttribute('class') . ' ' . $item['extraClass']);
        }
        $DOMitem->addChild(new CText($item['extraHTML']));

        return $DOMitem;
    }

    /**
     * check if a menu item is enabled by looking at the enabledON field:
     * - if it's always enabled, return true
     * - if it's never  enabled, return false
     * - if the field starts with a dollar sign, checks if a global exists with the given
     *   index and return its boolval, or return true if it does not.
     *   e.g.
     *      enabledON = '$com_enabled' checks for $GLOBALS['com_enabled']
     * - else checks if the constant enclosed in the percent signs is defined and true
     *
     * @param array $item array item to check
     *
     * @return boolean true if menu is enabled
     *
     * @access private
     */
    private function isEnabled($item)
    {
        if ($item['enabledON'] === self::ALWAYS_ENABLED) {
            return true;
        } elseif ($item['enabledON'] === self::NEVER_ENABLED) {
            return false;
        } elseif ($item['enabledON'][0] === '$') {
            /**
             * 01. remove the dollar sign at first position of string
             */
            $globalToCheck = substr($item['enabledON'], 1);
            /**
             * 02. check if it's a valid php variable name using the regexp found at:
             * http://php.net/manual/en/language.variables.basics.php
             */
            if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $globalToCheck)) {
                /**
                 * 03. check if a global by the $globalToCheck name exists,return its boolval
                 */
                return (isset($GLOBALS[$globalToCheck]) ? (bool)($GLOBALS[$globalToCheck]) : true);
            } else {
                return true;
            }
        } elseif ($item['enabledON'][0] === '%') {
            /**
             * must put into a var because of a limitation with PHP<5.5
             * see Note at http://php.net/manual/en/function.empty.php
             */
            $const = $this->constSubstitute($item['enabledON']);
            return !empty($const);
        } else {
            /**
             * check if $item['enabledON'] contains a valid JSON object that must be like:
             * {
             *    "func":"functionToBeCalled",
             *    "params": array to be passed to the funtion as a parameter
             * }
             */
            $enabledObj = json_decode($item['enabledON']);
            if (json_last_error() == JSON_ERROR_NONE) {
                $callFunc = null;
                if (property_exists($enabledObj, 'func') &&  is_callable($enabledObj->func, false, $callFunc)) {
                    if (!property_exists($enabledObj, 'params')) {
                        return call_user_func($callFunc) === true;
                    } else {
                        $callParams = (array)$enabledObj->params;
                        foreach ($callParams as $pKey => $pVal) {
                            $callParamsFunc = null;
                            if (is_object($pVal) && property_exists($pVal, 'func') && is_callable($pVal->func, false, $callParamsFunc)) {
                                if (!is_null($callParamsFunc)) {
                                    $callParams[$pKey] = call_user_func($callParamsFunc, property_exists($pVal, 'params') ? $pVal->params : null);
                                }
                            }
                        }
                        return call_user_func($callFunc, $callParams) === true;
                    }
                } else {
                    // if object has not a valid function, return false
                    return false;
                }
            } else {
                // if $item['enabledON'] produces a json errror, do not enable menu item
                return false;
            }
        }
    }

    /**
     * substitues the constants found in the DB with actual ADA CONSTANTS value
     * e.g.
     *  %HTTP_ROOT_DIR%/browsing => http://ada.lynxlab.com/browsing
     *
     * @param string $string
     * @return string the converted string
     */
    private function constSubstitute($string)
    {
        $regExp = '/%(\w+)%/';
        $search = [];
        $replace = [];
        $matches = preg_match_all($regExp, $string, $matchArr);
        if ($matches > 0 && $matches !== false) {
            foreach ($matchArr[0] as $matchCount => $matchValue) {
                $search[] = $matchValue;
                if (defined($matchArr[1][$matchCount])) {
                    $replace[] = constant($matchArr[1][$matchCount]);
                }
            }
        }
        return trim(str_replace($search, $replace, $string));
    }

    /**
     * Get array of left hand side menu subtree
     *
     * @return  array
     */
    public function getLeftItemsArray()
    {
        return $this->leftItemsArray;
    }

    /**
     * Set array of left hand side menu subtree
     *
     * @param  array  $_leftItemsArray  array of left hand side menu subtree
     *
     * @return  self
     */
    public function setLeftItemsArray(array $_leftItemsArray)
    {
        uasort($_leftItemsArray, fn($a, $b) => $a['order'] - $b['order']);
        $this->leftItemsArray = $_leftItemsArray;

        return $this;
    }

    /**
     * Get array of right hand side menu subtree
     *
     * @return  array
     */
    public function getRightItemsArray()
    {
        return $this->rightItemsArray;
    }

    /**
     * Set array of right hand side menu subtree
     *
     * @param  array  $_rightItemsArray  array of right hand side menu subtree
     *
     * @return  self
     */
    public function setRightItemsArray(array $_rightItemsArray)
    {
        uasort($_rightItemsArray, fn($a, $b) => $a['order'] - $b['order']);
        $this->rightItemsArray = $_rightItemsArray;

        return $this;
    }
}

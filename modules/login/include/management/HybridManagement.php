<?php

/**
 * LOGIN MODULE
 *
 * @package     login module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2015-2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Login;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class HybridManagement
{
    public $option_id;
    public $key;
    public $newkey;
    public $value;

    /**
     * name constructor
     */
    public function __construct($data = [])
    {
        if (is_array($data) && count($data) > 0) {
            $this->fillFromArray($data);
        }
    }

    /**
     * build, manage and display the module's pages
     *
     * @return array
     *
     * @access public
     */
    public function run($action = null)
    {
        /* @var $html   string holds html code to be retuned */
        $htmlObj = null;
        /* @var $path   string  path var to render in the help message */
        $help = translateFN('Da qui puoi inserire o modifcare le opzioni per il login provider');
        /* @var $status string status var to render in the breadcrumbs */
        $title = translateFN('Opzioni login');

        switch ($action) {
            case Constants::MODULES_LOGIN_EDIT_OPTIONSET:
                /**
                 * edit action, display the form with passed data
                 */
                $htmlObj = CDOMElement::create('span');
                $htmlObj->addChild(new CText('Le opzioni di questo provider non si configurano con un form'));
                // no break
            default:
                /**
                 * return an empty page as default action
                 */
                break;
        }

        return [
                'htmlObj'   => $htmlObj,
                'help'      => $help,
                'title'     => $title,
        ];
    }

    /**
     * fills object properties from an array
     *
     * @param array $data assoc array to get values from
     *
     * @access private
     */
    protected function fillFromArray($data)
    {
        foreach ($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = trim($val);
            }
        }
    }

    /**
     * returns object properties as an array
     *
     * @return array
     *
     * @access public
     */
    public function toArray()
    {
        return (array) $this;
    }
}

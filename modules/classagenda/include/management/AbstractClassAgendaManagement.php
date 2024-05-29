<?php

/**
 * Base Management Class
 *
 * @package         calssagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classagenda
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classagenda;

/**
 * base class for module
 *
 * @author giorgio
 */

abstract class AbstractClassAgendaManagement
{
    /**
     * array of available export formats
     *
     * @var array
     */
    protected static $exportFormats = ['pdf', 'csv'];

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
        return [
            'htmlObj'   => null,
            'help'      => null,
            'title'     => null,
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

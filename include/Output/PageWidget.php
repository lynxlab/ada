<?php

/**
 * Layout, Template, CSS, JS classes
 *
 *
 * @package     view
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Output;

/**
 * class for setting the needed XML for the page widget, if any.
 *
 * @author giorgio 25/set/2013
 */

class PageWidget
{
    /**
     * holds widgets configuration file full pathname or null on error
     * @var string
     */
    public $pageWidgets;

    /**
     * holds widgets configuration file full dirname or null on error
     * @var string
     */
    public $pageWidgetsDir;

    /**
     * hold error string if any
     * @var string
     */
    public $error;

    /**
     * default widget configuration file extension
     * @var string
     */
    private static $widgetConfFileExtension = '.xml';

    /**
     * where to start looking for dirname.
     * e.g. assuming template is in ROOT_DIR .'layout/ada_blu/templates/main/default.tpl'
     * it'll extract the dir starting AND NOT INCLUDING the value of the variable.
     * e.g. 'main/'
     *
     * @var string
     */
    private static $extractPathStartingFrom = 'templates/';

    /**
     * PageWidget constructor, the XML filename is the same as the template, but with xml
     * extension. If one with same name is found inside the currently active provider, that
     * one is preferred over the standard one.
     *
     * @param string $filename template file name used to build widget xml file name
     */
    public function __construct($filename)
    {
        $this->pageWidgets = null;
        $this->pageWidgetsDir = null;
        $this->error = '';

        $extractStringFrom = strrpos($filename, self::$extractPathStartingFrom) + strlen(self::$extractPathStartingFrom);
        $extractLength  = strrpos($filename, '/') - $extractStringFrom + 1;

        $dirname = substr($filename, $extractStringFrom, $extractLength);
        $filename = preg_replace('/\..*$/', self::$widgetConfFileExtension, basename($filename));

        $widgets_filename = '';

        if (!MULTIPROVIDER) {
            $widgets_dir = ROOT_DIR . "/clients/" . $GLOBALS['user_provider'] . "/widgets/$dirname";
            $widgets_filename = $widgets_dir . $filename;
        }

        if (!file_exists($widgets_filename)) {
            $widgets_dir = ROOT_DIR . "/widgets/$dirname";
            $widgets_filename = $widgets_dir . $filename;
            if (!file_exists($widgets_filename)) {
                $widgets_dir = $widgets_filename = null;
                $this->error = "$widgets_filename not found";
            }
        }
        $this->pageWidgets = $widgets_filename;
        $this->pageWidgetsDir = $widgets_dir;
    }
}

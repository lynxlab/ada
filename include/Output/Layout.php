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

class Layout
{
    // vars
    public $template;
    public $template_dir;
    public $CSS_filename;
    public $CSS_dir;
    public $JS_filename;
    public $JS_dir;
    public $family;
    public $module_dir;
    public $error_msg;
    public $full;
    public $external_module = false;
    // @author giorgio 25/set/2013
    // widgets configuration file name and dir
    public $WIDGET_filename;
    public $WIDGET_dir;
    public $error = '';
    public $menu;

    //constructor
    public function __construct($user_type, $node_type, $family = "", $node_author_id = "", $node_course_id = "", $module_dir = "")
    {

        $http_root_dir = HTTP_ROOT_DIR;
        $root_dir      = ROOT_DIR;
        $modules_dir   = MODULES_DIR;

        $this->error = "";
        if (empty($module_dir)) {
            $modules_dir = str_replace($root_dir, '', $modules_dir);
            $actual_dir = str_replace($root_dir, '', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
            /**
             * windows fix: replace back with forwardslash
             */
            $actual_dir = str_replace('\\', '/', $actual_dir);
            if (empty($actual_dir)) {
                $module_dir = 'main';
            } else {
                if (strpos($actual_dir, $modules_dir) !== false) {
                    $this->external_module = true;
                }
                $module_dir = substr($actual_dir, 1);
            }
        }

        if (!$family) {
            $family = ADA_TEMPLATE_FAMILY; //default
        }
        $this->family = $family;
        $this->module_dir = $module_dir;

        /**
         * @author giorgio 20/ott/2014
         *
         * $basedir_ada var was already here but it looks like
         * it's never used. I set it to null and keep it here
         * for compatibilty reason, it's probably safe to remove it
         */
        if (!isset($basedir_ada)) {
            $basedir_ada = null;
        }

        // Template
        $TplObj = new Template($user_type, $node_type, $family, $node_author_id, $node_course_id, $basedir_ada, $module_dir, $this->external_module);
        $this->template = $TplObj->template;
        $this->template_dir = $TplObj->template_dir;

        // Cascading Style Sheet(s)
        $CSSObj = new CSS($user_type, $node_type, $family, $node_author_id, $node_course_id, $basedir_ada, $module_dir, $this->external_module);
        $this->CSS_filename = $CSSObj->CSS_filename;
        $this->CSS_dir = $CSSObj->CSS_dir;

        // Javascript
        $JSObj = new JS($user_type, $node_type, $family, $node_author_id, $node_course_id, $basedir_ada, $module_dir, $this->external_module);
        $this->JS_filename = $JSObj->JS_filename;
        $this->JS_dir = $JSObj->JS_dir;
        //$this->debug();

        // Widgets
        $pageWidgetObj = new PageWidget($this->template);
        $this->WIDGET_dir = $pageWidgetObj->pageWidgetsDir;
        $this->WIDGET_filename = $pageWidgetObj->pageWidgets;
        //end function Layout
    }

    public function debug()
    {
        // forces debug
        //var_dump($this);
        $GLOBALS['debug'] = 1;
        mydebug(__LINE__, __FILE__, "FDIR: " . $this->family);
        mydebug(__LINE__, __FILE__, "CSS: " . $this->CSS_filename);
        mydebug(__LINE__, __FILE__, "CSSDIR: " . $this->CSS_dir);

        mydebug(__LINE__, __FILE__, "TPL: " . $this->template);
        mydebug(__LINE__, __FILE__, "TPLDIR: " . $this->template_dir);

        mydebug(__LINE__, __FILE__, "JS: " . $this->JS_filename);
        mydebug(__LINE__, __FILE__, "JSDIR: " . $this->JS_dir);

        mydebug(__LINE__, __FILE__, "MDIR: " . $this->module_dir);


        $GLOBALS['debug'] = 0;
    }

    /**
     * Returns an associative array of the layouts family installed in ADA
     *
     * @return array $layouts
     */
    public static function getLayouts()
    {
        /*
     * path to the directory containing all the layouts families
        */
        $path_to_dir = ROOT_DIR . '/layout/';
        /*
     * initialize the layouts array so that it contains at least the 'none' option
        */
        $layouts = ['none' => translateFN('seleziona un layout')];
        if (is_readable($path_to_dir)) {
            /*
       * do not consider as layout names '.', '..', and 'CVS'
            */
            $files = array_diff(scandir($path_to_dir), ['.', '..', '.svn']);
            /*
       * check if any of the resulting filenames is a directory and if it so
       * consider the filename as a layout name
            */
            foreach ($files as $filename) {
                if (is_dir($path_to_dir . $filename)) {
                    $layouts[$filename] = $filename;
                }
            }
        }
        return $layouts;
    }
}

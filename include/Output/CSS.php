<?php

/**
 * Layout, Template, CSS, JS classes
 *
 *
 * @package		view
 * @author		Stefano Penge <steve@lynxlab.com>
 * @copyright	Copyright (c) 2009, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version		0.1
 */

namespace Lynxlab\ADA\Main\Output;

class CSS
{
    public $CSS_filename;
    public $CSS_dir;
    public $family;
    public $error_msg;
    public $full;

    public function __construct($user_type, $node_type, $family = "", $node_author_id = "", $node_course_id = "", $basedir_ada = null, $function_group = 'main', $is_external_module = false)
    {

        $root_dir = $GLOBALS['root_dir'];
        $http_root_dir = $GLOBALS['http_root_dir'];

        /**
         * giorgio 12/ago/2013
         * sets user select provider
         */
        $user_provider = !MULTIPROVIDER ? $GLOBALS['user_provider'] : '';

        $CSS_files = [];
        // reads CSS from filesystem
        //  la struttura dei CSS ricopia quella di ADA (default)

        $rel_pref = $root_dir . '/';
        if (($function_group == "main") || (strtoupper((string) $function_group) === strtoupper((string) $basedir_ada))) {
            $module_dir = "main";
            // es. index.php -> layout/clear/css/main/default/index.css
        } else {
            $module_dir = $function_group;
            // es. browsing/view.php -> layout/clear/css/browsing/default/view.css
        }
        if (!$family) {
            $family = ADA_TEMPLATE_FAMILY;
        }

        if ($is_external_module) {
            $CSS_module_dir = $rel_pref . $module_dir . "/layout/$family/css/";
            // as an extreme fallback, use css/main
            $CSS_dir = $rel_pref . "layout/$family/css/main/";
        } else {
            /**
             * giorgio 11/ago/2013
             * module_dir comes as 'clients/PROVIDERNAME'
             * let's put it back in place
             */
            if (!MULTIPROVIDER && stristr($module_dir, $user_provider)) {
                $module_dir = 'main';
            }

            if (!isset($CSS_module_dir)) {
                $CSS_module_dir = '';
            }
            $CSS_dir = $rel_pref . "layout/$family/css/$module_dir/";
        }


        if (is_file($CSS_module_dir . "default.css")) {
            $CSS_files[] = $CSS_module_dir . "default.css";
        } else {
            $CSS_files[] = $CSS_dir . "default.css";
        } //adding default file

        if (is_file($CSS_module_dir . $node_type . ".css")) {
            $CSS_files[] = $CSS_module_dir . $node_type . ".css";
        } elseif (!in_array($CSS_dir . $node_type . ".css", $CSS_files)) {
            $CSS_files[] = $CSS_dir . $node_type . ".css";
        } //adding specific node type file

        if (!empty($node_author_id)) {
            if (!empty($node_course_id)) {
                $CSS_files[] = $http_root_dir . "/courses/media/$node_author_id/css/$node_course_id.css";
            }
        }

        /**
         * giorgio 11/ago/2013
         * if it's not multiprovider add node_type css and default css
         * (same structure as in 'main' css sudir)
         */
        if (!MULTIPROVIDER) {
            $CSS_provider_dir = $rel_pref . "clients/" . $user_provider . "/layout/$family/css/";

            if (is_file($CSS_provider_dir . $module_dir . "/default.css")) {
                $CSS_files[] = $CSS_provider_dir . "default.css";
            }

            if (is_file($CSS_provider_dir . "main/default.css")) {
                $CSS_files[] = $CSS_provider_dir . "main/default.css";
            }

            if (is_file($CSS_provider_dir . $module_dir . "/" . $node_type . ".css")) {
                $CSS_files[] = $CSS_provider_dir . $module_dir . "/" . $node_type . ".css";
            }
        } else {
            $CSS_provider_dir = '';
        }

        /**
         * @author giorgio 10/nov/2014
         *
         * add adamenu.css
         */
        $adamenuCSS = (isset($_SESSION['IE-version']) &&
            $_SESSION['IE-version'] !== false && $_SESSION['IE-version'] <= 8) ? "adamenu-ie8.css" : "adamenu.css";

        $adamenuCSSDir = $rel_pref . "layout/$family/css/";
        if (is_file($adamenuCSSDir . $adamenuCSS)) {
            $CSS_files[] = $adamenuCSSDir . $adamenuCSS;
        }

        if (!MULTIPROVIDER) {
            /**
             * if not multiprovider, include client's adamenu.css also
             */
            $adamenuCSSDir = $CSS_provider_dir; //  . '../';
            if (is_file($adamenuCSSDir . $adamenuCSS)) {
                $CSS_files[] = $adamenuCSSDir . $adamenuCSS;
            }
        }

        $this->CSS_filename = implode(';', $CSS_files);
        $this->CSS_dir = $CSS_dir;
        $this->family = $family;

        //  Utilities::mydebug(__LINE__,__FILE__,"CSS DDS: $duplicate_dir_structure fgroup:$function_group mdir:$module_dir bdir:$basedir_ada". $this->CSS_filename."<br>");

    } //end function CSS

}

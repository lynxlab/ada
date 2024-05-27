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

class JS
{
    public $JS_filename;
    public $JS_dir;
    public $error_msg;
    public $full;
    public function __construct($user_type, $node_type, $family = "", $node_author_id = "", $node_course_id = "", $basedir_ada = null, $function_group = 'main', $is_external_module = false)
    {
        $root_dir = $GLOBALS['root_dir'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        if (($function_group == "main") || (strtoupper((string) $function_group) == strtoupper((string) $basedir_ada))) {
            $module_dir = "main";
            $rel_pref = "";
            // es. index.php -> js/main/default/index.js
        } else {
            $rel_pref = "../";
            $module_dir = $function_group;
            // es. browsing/view.php -> ../js/browsing/default/view.js
        }

        $rel_pref = $root_dir . '/';
        if ($is_external_module) {
            $JS_dir = $rel_pref . $module_dir . "/js/";
        } else {
            $JS_dir = $rel_pref . "js/$module_dir/";
        }

        $JS_files[] = $rel_pref . "external/lib/js/prototype-1.7.3.js";
        $JS_files[] = $rel_pref . "external/lib/js/scriptaculous/scriptaculous.js";
        $JS_files[] = $JS_dir . "default.js";
        $jsfile = $JS_dir . $node_type . ".js";
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && strlen($GLOBALS['user_provider']) > 0) {
            $providerJS = ROOT_DIR . '/clients/' . $GLOBALS['user_provider'] . str_replace($root_dir, '', $jsfile);
            if (is_file($providerJS)) {
                $jsfile = $providerJS;
            }
        }
        if (!in_array($jsfile, $JS_files)) {
            $JS_files[] = $jsfile;
        }
        if (!empty($node_author_id)) {
            if (!empty($node_course_id)) {
                $JS_author_file = $rel_pref . "courses/media/$node_author_id/js/$node_course_id.js";
            }
        }
        // javascript fissi
        $JS_files[] = $rel_pref . "js/include/chkfrm.js";
        //  $this->JS_filename = $default_JS_file.";".$JS_file.";".$JS_author_file.";".$JS_ajax.";".$check_JS_file.";";
        $this->JS_filename = implode(';', $JS_files);
        $this->JS_dir = $JS_dir;
    } //end function JS
}

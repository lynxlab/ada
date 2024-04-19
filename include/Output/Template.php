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

class Template
{
    public $template;
    public $template_dir;
    public $family;
    public $error_msg;
    public $error;
    public $full;

    public function __construct($user_type, $node_type, $family = "", $node_author_id = "", $node_course_id = "", $basedir_ada = null, $function_group = 'main', $is_external_module = false)
    {

        $root_dir = $GLOBALS['root_dir'];
        $http_root_dir = $GLOBALS['http_root_dir'];
        $duplicate_dir_structure =  $GLOBALS['duplicate_dir_structure'];
        // 0 or 1

        $tpl_fileextension =  $GLOBALS['tpl_fileextension'];

        /**
         * giorgio 12/ago/2013
         * sets user select provider
         */
        $user_provider = !MULTIPROVIDER ? $GLOBALS['user_provider'] : '';

        // templates file extensions could be .tpl or .dwt or .HTML etc
        // default: .tpl
        if (!isset($tpl_fileextension)) {
            $tpl_fileextension = ".tpl";
        }
        if (!isset($duplicate_dir_structure)) {
            $duplicate_dir_structure = 1; //default
        }

        if (!$family) {
            $family = ADA_TEMPLATE_FAMILY; //default
            //                 } else {
            //                       $family = $GLOBALS['template_family'];
            //                 }
            //             } else {
            //                $GLOBALS['template_family'] = $family;
        }

        // mydebug(__LINE__,__FILE__,"BA $basedir_ada FG $function_group");

        //___________TPL ____________
        // reads templates from filesystem
        //
        if (($function_group == "main") || (strtoupper((string) $function_group) === strtoupper((string) $basedir_ada))) {
            $module_dir = 'main';
        } else {
            $module_dir = $function_group;
        }

        if ($is_external_module) {
            if (!MULTIPROVIDER) {
                $tpl_dir = $root_dir . "/clients/" . $user_provider . "/layout/$family/templates/$module_dir/";
                $tpl_filename = $tpl_dir . $node_type . $tpl_fileextension;

                if (!isset($tpl_filename) || !file_exists($tpl_filename)) {
                    $tpl_filename = $tpl_dir . "default" . $tpl_fileextension;
                    if (!isset($tpl_filename) || !file_exists($tpl_filename)) {
                        unset($tpl_filename);
                    }
                }
            }

            if (!isset($tpl_filename)) {
                $tpl_dir = $root_dir . "/$module_dir/layout/$family/templates/";
                $tpl_filename = $tpl_dir . $node_type . $tpl_fileextension;
            }
        } else {
            /**
             * giorgio 11/ago/2013
             * if it's not multiprovider, let's firstly check for a template
             * in the clients/provider dir with only one possibility in $module_dir
             */
            if (!MULTIPROVIDER) {
                if (stristr($module_dir, $user_provider)) {
                    $module_dir = 'main';
                }
                $tpl_dir = $root_dir . "/clients/" . $user_provider . "/layout/$family/templates/$module_dir/";
                $tpl_filename = $tpl_dir . $node_type . $tpl_fileextension;
                /**
                 * giorgio 12/ago/2013
                 *
                 * checking for default template in user selected provider may not be
                 * a good idea because it's not known where and when ada shall use this
                 * template, and it's unpleasant that all of a sudden the user finds
                 * him/her self in the provider template while he/she is browsing....
                 *
                 *  Should you disable it, check carefully all 'anonymous' pages
                 *  at least info.php should use the default template
                 */
                if (!isset($tpl_filename) || !file_exists($tpl_filename)) {
                    $tpl_filename = $tpl_dir . "default" . $tpl_fileextension;
                }
            }
        }
        /**
         * giorgio 11/ago/2013
         * if $tpl_filename is not found inside client dir, resume normal operation
         */
        if (!isset($tpl_filename) || !file_exists($tpl_filename)) {
            $tpl_dir = $root_dir . "/layout/$family/templates/$module_dir/";
            $tpl_filename = $tpl_dir . $node_type . $tpl_fileextension;
        }

        // es. layout/clear/templates/browsing/default/view.tpl
        if (!file_exists($tpl_filename)) {
            //$tpl_dir = $root_dir."/templates/$module_dir/$family/";
            $tpl_filename = $tpl_dir . "default" . $tpl_fileextension;
            // mydebug(__LINE__,__FILE__, " trying $tpl_filename...<br>");
            if (!file_exists($tpl_filename)) {
                $module_dir = "main";
                $tpl_dir = $root_dir . "/layout/$family/templates/$module_dir/";
                $tpl_filename = $tpl_dir . "default" . $tpl_fileextension;
                if (!file_exists($tpl_filename)) {
                    $this->error = "$tpl_filename not found";
                }
                //mydebug(__LINE__,__FILE__, "  $tpl_filename...<br>");
            }
        }
        $this->template = $tpl_filename;
        $this->template_dir = $tpl_dir;
        $this->family = $family;
        // end function Template
    }
}

<?php

/**
 * Media Viewers
 *
 * PHP version >= 5.0
 *
 * @package     view
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        media_viewing_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Media;

/**
 * class InternalLinkViewer, returns the correct representation for this internal link based on
 * InternalLinkViewingPreferences and on user level
 *
 */
class InternalLinkViewer
{
    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $media_value
     * @param  mixed  $InternalLinkViewingPreferences
     * @param  int    $user_level
     * @return string
     */
    public static function view($http_file_path, $media_value, $InternalLinkViewingPreferences = 0, $user_level = -1, $id_course = '')
    {
        if (strlen($id_course) > 0) {
            $id_node = $id_course . '_' . $media_value;

            $nodeObj = new Node($id_node, 0);
            // controllo errore

            if ($nodeObj->full == 1) {
                $linked_node_level = $nodeObj->level;
                $name = $nodeObj->name;

                if ($_SESSION['sess_id_user_type'] == AMA_TYPE_STUDENT && $linked_node_level > $user_level) {
                    $exploded_link = '<img src="img/_linkdis.png" border="0" alt="' . $name . '" /><span class="link_unreachable">' . $name . '</span>';
                } else {
                    $exploded_link = '<a href="' . HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $id_node . '"><img src="img/_linka.png" border="0" alt="' . $name . '">' . $name . '</a>';
                }
            } else {
                $exploded_link = '<img src="img/_linkdis.png" border="0" alt="' . $id_node . '" />';
            }
            return $exploded_link;
        }
        return '';
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $InternalLinkViewingPreferences)
    {
        //return '<a href="'.$http_file_path.$real_file_name.'">'.$file_name.'</a>';
        return '';
    }
}

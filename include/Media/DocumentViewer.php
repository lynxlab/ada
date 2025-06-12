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
 * class DocumentViewer
 *
 */
class DocumentViewer
{
    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $media_value
     * @param  mixed  $DocumentViewingPreferences
     * @return string
     */
    public static function view($http_file_path, $media_value, $DocumentViewingPreferences = DOC_VIEWING_MODE)
    {
        switch ($DocumentViewingPreferences) {
            case 0:
            case 1:
            case 2:
            default:
                $exploded_document = '<a data-type="doc" href="' . $http_file_path . $media_value .
                    '" target="_blank"><img data-type="doc" src="img/_doc.png" border="0" alt="' . $media_value .
                    '">' . $media_value . '</a>';
                break;
        }
        return $exploded_document;
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $DocumentViewingPreferences)
    {
        $complete_file_name = $file_name;
        if (strlen($file_name) > 15) {
            preg_match('/\.[^.]*$/', $complete_file_name, $ext);
            preg_replace('/\.[^.]*$/', '', $file_name);
            $file_name = substr($file_name, 0, 12) . '...' . $ext[0];
        }
        $link =  [
            '<img src="img/_doc.png" data-type="doc">',
            '<a data-type="doc" href="' . $http_file_path . $real_file_name . '" target="_blank" title="' . $complete_file_name . '">' . $file_name . '</a>',
        ];
        //        return $link;
        return implode('', $link);
    }
}

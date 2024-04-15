<?php

use Lynxlab\ADA\Main\Node\Media;

use Lynxlab\ADA\Main\Media\ImageViewer;

// Trigger: ClassWithNameSpace. The class ImageViewer was declared with namespace Lynxlab\ADA\Main\Media. //

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
 * class ImageViewer, returns the correct representation for the image based on ImageViewingPreferences
 *
 */
class ImageViewer
{
    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $file_name
     * @param  mixed  $ImageViewingPreferences
     * @return string
     */
    public static function view($http_file_path, $file_name, $ImageViewingPreferences = IMG_VIEWING_MODE, $imageTitle = null, $width = null, $height = null)
    {
        if (!is_null($width)) {
            $width = ' width="' . $width . '"';
        }
        if (!is_null($height)) {
            $height = ' height="' . $height . '"';
        }
        switch ($ImageViewingPreferences) {
            case 0:
            case 1:
            default:
                $exploded_img = '<a href="' . $http_file_path . $file_name . '"><img src="img/_img.png" border="0" alt="' . $file_name . '"' . $width . $height . ' />' . $imageTitle . '</a>';
                break;

            case 2:
                $exploded_img = '<img src="' . $http_file_path . $file_name . '" alt="' . $file_name . '"' . $width . $height . ' />';
                break;
        }
        return $exploded_img;
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $ImageViewingPreferences = IMG_VIEWING_MODE, $imageTitle = null)
    {

        if (is_file($path_to_file)) {
            $size = getimagesize($path_to_file);
            $x = $size[0];
            $y = $size[1];
        } else {
            $x = $y = 0;
        }

        $r = ($y != 0) ? (int)round($x / $y) : 0;

        $file_name_http = $http_file_path . $real_file_name;

        if ($imageTitle == null || !isset($imageTitle)) {
            $imageTitle = $file_name;
        }

        switch (IMG_VIEWING_MODE) {   // it would be better to use a property instead
            case 2: // full img in page, only icon here
                $link_media = '<img src="img/_img.png" data-type="img"><a data-type="img" href="#" onclick="newWindow(\'' . $file_name_http . '\',' . $x . ',' . $y . ');">' . $imageTitle . '</a>';
                break;

            case 1: // icon in page, a reduced size preview  here
                $link_media = '<img src="' . HTTP_ROOT_DIR . '/include/resize.php?img=' . $file_name . '&ratio=' . $r . '" data-type="img"><a data-type="img" href="#" onclick="newWindow(\'' . $file_name_http . '\',' . $x . ',' . $y . ');">' . $file_name . '</a>';
                break;

            case 0: // icon in page,  icon here
            default:
                $link_media = '<img src="img/_img.png" data-type="img"><a data-type="img" href="#" onclick="newWindow(\'' . $file_name_http . '\',' . $x . ',' . $y . ');">' . $imageTitle . '</a>';
                break;
        }

        return $link_media;
    }
}

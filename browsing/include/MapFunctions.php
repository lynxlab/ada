<?php

// return node type

namespace Lynxlab\ADA\Browsing;

use Lynxlab\ADA\Browsing\ImageDevice;

class MapFunctions
{
    public static function returnAdaNodeType($t)
    {
        if ($t == ADA_LEAF_TYPE) {
            return "nodo";
        } elseif ($t == ADA_GROUP_TYPE) {
            return "gruppo";
        } elseif ($t == ADA_LEAF_WORD_TYPE) {
            return "lemma";
        } elseif ($t == ADA_GROUP_WORD_TYPE) {
            return "gruppo_lemmi";
        } elseif ($t == ADA_PERSONAL_EXERCISE_TYPE || strval($t)[0] == ADA_STANDARD_EXERCISE_TYPE) {
            return "test";
        }
    }

    // return node icon
    public static function returnAdaNodeIcon($icon, $type)
    {
        //$pathAr = explode(MEDIA_PATH_DEFAULT);
        //if(preg_match((explode(MEDIA_PATH_DEFAULT))),$icon) == 0 ) return "img/".returnAdaNodeType($type).".png";
        $file_pathAR = explode("/", $icon);
        $num_el = count($file_pathAR);
        if ($num_el < 2) {
            return "img/" . static::returnAdaNodeType($type) . ".png"; // it is a path to a file
        }
        //  if(preg_match("/services\/media/",$icon) == 0 ) return "img/".returnAdaNodeType($type).".png";

        $iconAR = (explode("/", $icon));

        // $len = count($iconAR);
        //$file_name = $iconAR[count($iconAR)-1];
        // $file_thumb = 'thumb'.$file_name;

        $file_thumb = 'thumb_' . $iconAR[count($iconAR) - 1];
        $iconAR[count($iconAR) - 1] = $file_thumb;
        $icon_thumb = implode("/", $iconAR);
        if (file_exists($icon_thumb)) {
            return preg_replace('#' . ROOT_DIR . '#', HTTP_ROOT_DIR, $icon_thumb);
        }

        $id_img = new ImageDevice();
        $new_icon = $id_img->resizeImage($icon);
        if (!is_null($new_icon)) {
            imagejpeg($new_icon, $icon_thumb);
        }
        if (file_exists($icon_thumb)) {
            return preg_replace('#' . ROOT_DIR . '#', HTTP_ROOT_DIR, $icon_thumb);
        } else {
            return "img/" . static::returnAdaNodeType($type) . ".png";
        }
    }

    // return node position
    public static function returnAdaNodePos($t, $id)
    {
        $d = [];

        foreach ($t as $pos) {
            array_push($d, $pos);
        };

        $width =  ($d[2] - $d[0]);

        if ($width < 0) {
            $width *= -1;
        }


        return [$d[0], $d[1], 100, 100];
    }

    // return node links
    public static function returnAdaNodeLink($t)
    {

        $d = [];

        foreach ($t as $lin) {
            array_push($d, $lin['id_node_to']);
        }

        return implode(",", $d);
    }

    public static function returnMapType()
    {

        return $_GET['map_type'] ?? "nodo";
    }
}

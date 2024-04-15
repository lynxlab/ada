<?php

use Lynxlab\ADA\Main\Node\Media;

use Lynxlab\ADA\Main\Media\ExternalLinkViewer;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

// Trigger: ClassWithNameSpace. The class ExternalLinkViewer was declared with namespace Lynxlab\ADA\Main\Media. //

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

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;

/**
 * class ExternalLinkViewer
 *
 */
class ExternalLinkViewer
{
    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $media_value
     * @param  mixed  $ExternalLinkViewingPreferences
     * @return string
     */
    public static function view($http_file_path, $media_value, $ExternalLinkViewingPreferences)
    {
        switch ($ExternalLinkViewingPreferences) {
            case 0:
            case 1:
            case 2:
            default:
                /*
         * Remove http[s]:// from the link
                */
                $cleaned_string = preg_replace("/http[s]?:\/\//", "", $media_value);
                //        $ADA_EXTERNAL_LINKS_MAX_LENGTH = 10;
                //        $string_length = count($cleaned_string);
                //
                //        $diff = $string_length - $ADA_EXTERNAL_LINKS_MAX_LENGTH;
                //        if($diff > 3) {
                //          $stop = $string_lenght/2 -$diff/2;
                //          $inizio = substr($cleaned_string,0, $stop);
                //          $fine   = substr($tring, $string_lenght-$stop);
                //          $cleaned_string = "$inizio...$fine";
                //        }
                //
                //$exploded_ext_link = "<a href=\"$media_value\" target=\"_blank\"><img src=\"img/_web.png\" border=\"0\" title=\"$media_value\" alt=\"\"> $cleaned_string </a>";

                /*
         * Ottiene l'id per il media con nome $media_value
         * e costruisce il link a external_link.php
                */
                $dh = $GLOBALS['dh'];
                $id = $dh->getRisorsaEsternaId($media_value);
                if (AMADataHandler::isError($id)) {
                    $exploded_ext_link = $cleaned_string;
                } else {
                    $spanLink = CDOMElement::create('span');
                    $linkImg = CDOMElement::create('img');
                    $linkImg->setAttribute('src', 'img/_web.png');
                    $linkImg->setAttribute('border', '0');
                    $linkImg->setAttribute('title', $media_value);
                    $linkImg->setAttribute('alt', $media_value);
                    $linkImg->setAttribute('data-type', 'link');
                    $spanLink->addChild($linkImg);
                    $spanLink->addChild(new CText($cleaned_string));

                    /**
                     * @author giorgio 11/dec/2017
                     *
                     * open all external links in a blank window
                     * (remove comment to the if/else block to restore
                     * behaviour of opening non https links inside an iframe)
                     */
                    $href = $media_value;
                    //                  if (stripos($media_value,'https')===0) {
                    /**
                     * @author giorgio 09/set/2015
                     *
                     * if link is https do not show it in an iframe
                     * as it will cause security problems
                     */
                    //                      $href = $media_value;
                    //                  } else {
                    //                      $href = HTTP_ROOT_DIR.'/browsing/external_link.php?id='.$id;
                    //                  }
                    $link = BaseHtmlLib::link($href, $spanLink);
                    $link->setAttribute('target', '_blank');
                    $link->setAttribute('data-type', 'link');
                    $exploded_ext_link = $link->getHtml();
                }
                break;
        }
        return $exploded_ext_link;
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $ExternalLinkViewingPreferences)
    {
        //return '<a href="'.$http_file_path.$real_file_name.'">'.$file_name.'</a>';
        return '';
    }
}

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

use getID3;
use Lynxlab\ADA\Main\HtmlLibrary\MediaViewingHtmlLib;
use Lynxlab\ADA\Main\Node\Media;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class VideoPlayer, returns the correct player for this video file, based on VideoPlayingPreferences
 *
 */
class VideoPlayer
{
    public const DEFAULT_WIDTH = DEFAULT_VIDEO_WIDTH;
    public const DEFAULT_HEIGHT = DEFAULT_VIDEO_HEIGHT;

    /**
     * function heightCalc
     *
     */
    public static function heightCalc($width = self::DEFAULT_WIDTH, $mediaWidth = self::DEFAULT_WIDTH, $mediaHeight = self::DEFAULT_HEIGHT)
    {
        if (intval($mediaWidth) === 0) {
            return $mediaHeight;
        }
        $height_dest = floor($mediaHeight * ($width / $mediaWidth));
        return $height_dest;
    }


    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $file_name
     * @param  mixed  $VideoPlayingPreferences
     * @return string
     */
    public static function view($http_file_path, $file_name, $VideoPlayingPreferences = VIDEO_PLAYING_MODE, $videoTitle = null, $width = null, $height = null)
    {

        $getID3 = new getID3();
        $toAnalyze = (!empty($http_file_path) ? $http_file_path : ROOT_DIR) . $file_name;
        $fileInfo = $getID3->analyze(urldecode(str_replace(HTTP_ROOT_DIR, ROOT_DIR, $toAnalyze)));
        $id3Error = array_key_exists('error', $fileInfo);

        if (empty($width)) {
            $width = self::DEFAULT_WIDTH;
        }
        //      if (empty($height)) {
        //          $height = self::DEFAULT_HEIGHT;
        //      }
        $mediaWidth = (!$id3Error && intval($fileInfo['video']['resolution_x']) > 0) ? intval($fileInfo['video']['resolution_x']) : null;
        $mediaHeight = (!$id3Error && intval($fileInfo['video']['resolution_y']) > 0) ? intval($fileInfo['video']['resolution_y']) : null;
        $height = VideoPlayer::heightCalc($width, $mediaWidth, $mediaHeight);

        if ((empty($width) || empty($height)) && isset($fileInfo['video']) && !empty($fileInfo['video'])) {
            $width = (intval($fileInfo['video']['resolution_x']) > 0) ? intval($fileInfo['video']['resolution_x']) : null;
            $height = (intval($fileInfo['video']['resolution_y']) > 0) ? intval($fileInfo['video']['resolution_y']) : null;
        }


        if ($videoTitle == null || !isset($videoTitle)) {
            $videoTitle = $file_name;
        }

        $extension = pathinfo($file_name, PATHINFO_EXTENSION);

        switch ($VideoPlayingPreferences) {
            case 2:
                // tag are replaced by fullsize img
                switch ($extension) {
                    case 'dcr': //shockwave
                        $exploded_video = '
							<object classid="clsid:166B1BCA-3F9C-11CF-8075-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/director/sw.cab#version=8,0,0,0" width="' . $width . '" height="' . $height . '">
								<param name="movie" value="' . $http_file_path . $file_name . '">
								<embed src="' . $http_file_path . $file_name . '" quality="high" pluginspage="http://www.macromedia.com/shockwave/download/" width="' . $width . '" height="' . $height . '"></embed>
							</object>';
                        break;

                    case 'swf': // flash
                        $exploded_video = '
							<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="' . $width . '" height="' . $height . '">
								<param name="movie" value="' . $http_file_path . $file_name . '">
								<param name="quality" value="high">
								<embed src="' . $http_file_path . $file_name . '" quality="high" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" width="' . $width . '" height="' . $height . '"></embed>
							</object>';
                        break;

                    case 'ogg':
                    case 'mpg':
                    case 'mp4':
                        if (defined('USE_MEDIA_CLASS') && class_exists(USE_MEDIA_CLASS, false)) {
                            $className = USE_MEDIA_CLASS;
                            $file_name = $className::getPathForFile($file_name);
                        } else {
                            $file_name = Media::getPathForFile($file_name);
                        }
                        $exploded_video = MediaViewingHtmlLib::jplayerMp4Viewer($http_file_path . $file_name, $file_name, $width, $height);
                        break;

                    default:
                        $exploded_video = translateFN("Nessun riproduttore video per") . " " . $http_file_path . $file_name;
                        break;
                }
                break;

            case 1:
            case 0:
            default:
                // tag are replaced by icons
                $desc = translateFN('guarda il filmato');
                $exploded_video = '<a href="#" onclick="openMessenger(\'loader.php?lObject=\\1&ext=\\2&sAuthorId=9&sWidth=' . $width . '&sHeight=' . $height . '\',' . $width . ',' . $height . ');"><img src="img/_video.png" border="0" alt="' . $file_name . '">' . $desc . '</a>';
                break;
        }
        return $exploded_video;
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $VideoPlayingPreferences = VIDEO_PLAYING_MODE, $videoTitle = null, $media_type = null)
    {
        switch ($media_type) {
            case null:
                return '';
            case _VIDEO:
                $label = translateFN('video');
                break;
            case _LABIALE:
                $label = translateFN('video del labiale');
                break;
            case _LIS:
                $label = translateFN('video LIS');
                break;
            case _FINGER_SPELLING:
                $label = translateFN('video dello Spelling');
                break;
        }
        $root_dir_path = str_replace(HTTP_ROOT_DIR, ROOT_DIR, $http_file_path);
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        if ($videoTitle == null || !isset($videoTitle)) {
            $videoTitle = $file_name;
        }

        $templateFamily = (isset($_SESSION['sess_template_family']) && strlen($_SESSION['sess_template_family']) > 0) ? $_SESSION['sess_template_family'] : ADA_TEMPLATE_FAMILY;
        return '<a data-type="video" href="#" onClick="openInRightPanel(\'' . $http_file_path . $file_name . '\',\'' . $extension . '\');"><img data-type="video" src="../layout/' . $templateFamily . '/img/flv_icon.png" alt="video">' . $label . ' ' . $videoTitle . '</a>';
        //return '<img src="img/_video.png"><a href="'.$http_file_path.$real_file_name.'" target="_blank">'.$file_name.'</a>';
    }
}

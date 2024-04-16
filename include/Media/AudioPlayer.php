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

/**
 * class AudioPlayer, returns the correct player for this audio file based on AudioPlayingPreferences
 *
 */
class AudioPlayer
{
    /**
     * function view
     *
     * @param  string $http_file_path
     * @param  string $file_name
     * @param  mixed  $AudioPlayingPreferences
     * @return string
     */
    public static function view($http_file_path, $file_name, $AudioPlayingPreferences = AUDIO_PLAYING_MODE, $audioTitle = null)
    {
        $http_root_dir = $GLOBALS['http_root_dir'];

        $getID3 = new getID3();
        $toAnalyze = (!empty($http_file_path) ? $http_file_path : ROOT_DIR) . $file_name;
        $fileInfo = $getID3->analyze(urldecode(str_replace(HTTP_ROOT_DIR, ROOT_DIR, $toAnalyze)));

        if ($audioTitle == null || !isset($audioTitle)) {
            $audioTitle = $file_name;
        }
        switch ($AudioPlayingPreferences) {
            case 0:
                $exploded_audio = '<a href="' . $http_file_path . $file_name . '" target="_blank"><img src="img/_audio.png" border="0" alt="' . $audioTitle . '">' . $audioTitle . '</a>';
                break;

            case 1:
            case 2:
            default:
                if ($fileInfo['fileformat'] == 'mp3') { // use jplayer if mp3
                    require_once ROOT_DIR . '/include/HtmlLibrary/MediaViewingHtmlLib.inc.php';
                    $exploded_audio = MediaViewingHtmlLib::jplayerMp3Viewer($http_file_path . $file_name, $audioTitle);
                } else {
                    $url = $http_root_dir . "/external/mediaplayer/1pixelout/1pixelout.swf";
                    $exploded_audio = '
					<object type="application/x-shockwave-flash" data="' . $url . '" width="290" height="24" >
						<param name="movie" value="' . $url . '" />
						<param name="wmode" value="transparent" />
						<param name="menu" value="false" />
						<param name="quality" value="high" />
						<param name="FlashVars" value="soundFile=' . $http_file_path . $file_name . '" />
						<embed src="' . $url . '" flashvars="soundFile=' . $http_file_path . $file_name . '" width="290" height="24" />
					</object>';
                }
                break;
        }
        return $exploded_audio;
    }

    public static function link($http_file_path, $file_name, $real_file_name, $path_to_file, $AudioPlayingPreferences = AUDIO_PLAYING_MODE, $audioTitle = null)
    {
        if ($audioTitle == null || !isset($audioTitle)) {
            $audioTitle = $file_name;
        }
        return '<img src="img/_audio.png" data-type="audio"><a data-type="audio" href="' . $http_file_path . $real_file_name . '" target="_blank">' . $audioTitle . '</a>';
    }
}

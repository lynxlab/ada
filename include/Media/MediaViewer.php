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

class MediaViewer
{
    private $viewing_preferences;
    private $user_data;
    private $media_path;
    private $media_title;
    private $default_http_media_path;

    public function __construct($media_path, $user_data = [], $VIEWING_PREFERENCES = [], $title = '')
    {
        $this->media_path = $media_path;
        $this->user_data = $user_data;
        $this->viewing_preferences = $VIEWING_PREFERENCES;
        $this->media_title = $title;
        $this->default_http_media_path = $this->media_path;
    }

    public function setMediaPath($media_data = [])
    {
        if (file_exists(ROOT_DIR . MEDIA_PATH_DEFAULT . $media_data['owner'] . '/' . $media_data['value'])) {
            $this->media_path = HTTP_ROOT_DIR . MEDIA_PATH_DEFAULT . $media_data['owner'] . '/';
        } else {
            $this->media_path = $this->default_http_media_path;
        }
    }

    /**
     * function getViewer, used to call the appropriate viewer for the selected media type ($media_data['type'])
     *
     * @param array $media_data - associative array, at least with defined keys 'type' and 'path'
     * @param array $user_data  - associative array, at least with a defined key 'level'
     * @param array $node_data  - associative array, at least with a defined key 'level'
     * @param array $VIEWING_PREFERENCES
     * @return string
     */
    public function getViewer($media_data = [])
    {
        $media_type  = $media_data[1] ?? null;
        if (isset($media_data['type'])) {
            $media_type = $media_data['type'];
        }

        $media_value = $media_data[2] ?? null;
        if (isset($media_data['value'])) {
            $media_value = $media_data['value'];
        }

        $media_title = null;
        if (isset($media_data['title']) && !is_null($media_data['title'])) {
            $media_title = $media_data['title'];
        }

        $media_width = null;
        if (isset($media_data['width']) && !is_null($media_data['width'])) {
            $media_width = $media_data['width'];
        }

        $media_height = null;
        if (isset($media_data['height']) && !is_null($media_data['height'])) {
            $media_height = $media_data['height'];
        }

        /**
         * @author giorgio 23/apr/2013
         *
         * modified: now each 'if' body outputs to a variable instead of returning immediately.
         * string is wrapped around a <div> element with proper class for css styling just before returning.
         *
         */

        /* @var $return string */
        $return = '';
        if ($media_type === _IMAGE || $media_type === _MONTESSORI) {
            $return = ImageViewer::view($this->media_path, $media_value, $this->viewing_preferences[_IMAGE], $media_title, $media_width, $media_height);
        } elseif ($media_type === _SOUND || $media_type === _PRONOUNCE) {
            $return = AudioPlayer::view($this->media_path, $media_value, $this->viewing_preferences[_SOUND], $media_title);
        } elseif ($media_type === _VIDEO || $media_type === _LABIALE || $media_type === _FINGER_SPELLING) {
            $return = VideoPlayer::view($this->media_path, $media_value, $this->viewing_preferences[_VIDEO], $media_title, $media_width, $media_height);
        } elseif ($media_type === _LIS && isset($_SESSION['mode']) && $_SESSION['mode'] == 'LIS') {
            $return = VideoPlayer::view($this->media_path, $media_value, $this->viewing_preferences[_VIDEO], $media_title, $media_width, $media_height);
        } elseif ($media_type === _DOC) {
            $return = DocumentViewer::view($this->media_path, $media_value, $this->viewing_preferences[_DOC]);
        } elseif ($media_type === _LINK) {
            $return = ExternalLinkViewer::view($this->media_path, $media_value, $this->viewing_preferences[_LINK]);
        } else {
            $return = '';
        }

        /**
         * @author giorgio 23/apr/2013
         *
         * array to hold proper css classes
         */

        $cssArray =  [
                _IMAGE => 'image',
                _MONTESSORI => 'montessori',
                _SOUND => 'sound',
                _PRONOUNCE => 'pronounce',
                _VIDEO => 'video',
                _LABIALE => 'labiale',
                _LIS => 'lis',
                _FINGER_SPELLING => 'finger-spelling',
                _DOC => 'doc',
                _LINK => 'link',
        ];

        /**
         * @author giorgio 23/apr/2013
         *
         * wrap $return around a div and return as promised
         */

        if ($return !== '') {
            $return = "<div class='media " . $cssArray[$media_type] . "'>" . $return . "</div>";
        }

        return $return;
    }

    public function displayLink($media_data = [])
    {
        $media_value = $media_data[2] ?? null;
        if (isset($media_data['value'])) {
            $media_value = $media_data['value'];
        }
        return InternalLinkViewer::view($this->media_path, $media_value, $this->viewing_preferences[INTERNAL_LINK], $this->user_data['level'], $this->user_data['id_course']);
    }

    /**
     * function getMediaLink
     *
     * @param  $media_data -
     * @return string      - the html string containing the appropriate link for the given media
     */
    public function getMediaLink($media_data = [])
    {
        $media_type  = $media_data[1];
        if (isset($media_data['type'])) {
            $media_type  = $media_data['type'];
        }

        $media_value = $media_data[2];
        if (isset($media_data['value'])) {
            $media_value  = $media_data['value'];
        }
        $media_real_file_name = $media_data[3];
        $path_to_media = $media_data[4];
        $media_title = $media_data[5] ?? null;
        if (isset($media_data['title'])) {
            $media_title  = $media_data['title'];
        }


        if ($media_type === _IMAGE || $media_type === _MONTESSORI) {
            $viewing_prefs = $this->viewing_preferences[_IMAGE] ?? null;
            return ImageViewer::link($this->media_path, $media_value, $media_real_file_name, $path_to_media, $viewing_prefs, $media_title, $media_type);
        } elseif ($media_type === _SOUND || $media_type === _PRONOUNCE) {
            $viewing_prefs = $this->viewing_preferences[_SOUND] ?? null;
            return AudioPlayer::link($this->media_path, $media_value, $media_real_file_name, $path_to_media, $viewing_prefs, $media_title, $media_type);
        } elseif ($media_type === _VIDEO || $media_type === _LABIALE || $media_type === _LIS || $media_type === _FINGER_SPELLING) {
            $viewing_prefs = $this->viewing_preferences[_VIDEO] ?? null;
            return VideoPlayer::link($this->media_path, $media_value, $media_real_file_name, $path_to_media, $viewing_prefs, $media_title, $media_type);
        } elseif ($media_type === _DOC) {
            $viewing_prefs = $this->viewing_preferences[_DOC] ?? null;
            return DocumentViewer::link($this->media_path, $media_value, $media_real_file_name, $path_to_media, $viewing_prefs, $media_title);
        } elseif ($media_type === _LINK) {
            $viewing_prefs = $this->viewing_preferences[_LINK] ?? null;
            return ExternalLinkViewer::view($this->media_path, $media_value, $viewing_prefs);
        } else {
            return '';
        }
    }

    private function checkExtension($filename, $extension)
    {
        $path_to_file = str_replace(HTTP_ROOT_DIR, ROOT_DIR, $this->media_path);
        return (pathinfo($path_to_file . $filename, PATHINFO_EXTENSION) == $extension);
    }
}

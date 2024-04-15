<?php

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\Node\Media;

use Lynxlab\ADA\Main\Node\EmptyMedia;

use Lynxlab\ADA\Main\Node\ADAResource;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

// Trigger: ClassWithNameSpace. The class Media was declared with namespace Lynxlab\ADA\Main\Node. //

/**
 * Node, Media, Link classes
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        node_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Node;

use Lynxlab\ADA\Main\Media\MediaViewer;

class Media extends ADAResource
{
    public static function findById($id)
    {
        $dh = $GLOBALS['dh'];

        $result = $dh->getRisorsaEsternaInfo($id);
        if (AMADataHandler::isError($result)) {
            $mediaObj = new EmptyMedia();
        } else {
            if (defined('USE_MEDIA_CLASS') && class_exists(USE_MEDIA_CLASS, false)) {
                $className = USE_MEDIA_CLASS;
            } else {
                $className = 'Media';
            }
            $mediaObj = new $className(
                $id,
                $result['nome_file'],
                $result['tipo'],
                $result['copyright'],
                $result['id_utente'],
                $result['titolo'],
                $result['keywords'],
                $result['descrizione'],
                $result['pubblicato'],
                $result['lingua']
            );
        }
        return $mediaObj;
    }

    public static function getPathForFile($filename)
    {
        return $filename;
    }

    public function __construct($id, $filename, $type, $isCopyrighted, $ownerId, $title, $keywords, $description, $published, $lang)
    {
        $this->id_resource = $id;
        $this->fileName = $filename;
        $this->type = $type;
        $this->isCopyrighted = $isCopyrighted;
        $this->ownerId = $ownerId;
        $this->full = 1;
        $this->title = $title;
        $this->keywords = $keywords;
        $this->description = $description;
        $this->lang = $lang;
        $this->published = $published;

        $this->pathToOwnerDir = MEDIA_PATH_DEFAULT . $this->ownerId . DIRECTORY_SEPARATOR;
        //        $this->pathToOwnerCourseDir = MEDIA_PATH_DEFAULT . $this->_ownerCourseId . DIRECTORY_SEPARATOR;
        $this->pathToOwnerCourseDir = $GLOBALS['media_path'];

        if (MEDIA_LOCAL_PATH != '') {
            $this->pathToFile = MEDIA_LOCAL_PATH . $this->fileName;
        } else {
            if (file_exists(ROOT_DIR . $this->pathToOwnerDir . $this->fileName)) {
                $this->pathToFile = $this->pathToOwnerDir . $this->fileName;
            } else {
                $this->pathToFile = $this->pathToOwnerCourseDir . $this->fileName;
            }
        }
    }

    protected function pathToMedia()
    {
        return ROOT_DIR . $this->pathToFile;
    }

    protected function urlToMedia()
    {
        return HTTP_ROOT_DIR . $this->pathToFile;
    }


    public function getLinkToMedia()
    {
        $media_dataAr = [
            0 => null,
            1 => $this->type,
            2 => $this->getDisplayFilename(),
            3 => $this->fileName,
            4 => $this->pathToMedia(),
            5 => $this->title,
        ];

        if (file_exists(ROOT_DIR . $this->pathToOwnerDir . $this->fileName)) {
            $this->pathToFile = $this->pathToOwnerDir . $this->fileName;
            $mediaViewer = new MediaViewer(HTTP_ROOT_DIR . $this->pathToOwnerDir, [], []);
        } else {
            $this->pathToFile = $this->pathToOwnerCourseDir . $this->fileName;
            $mediaViewer = new MediaViewer(HTTP_ROOT_DIR . $this->pathToOwnerCourseDir, [], []);
        }
        return $mediaViewer->getMediaLink($media_dataAr);
    }

    public function getDisplayFilename()
    {
        return $this->fileName;
    }

    public $title;
    public $keywords;
    public $description;
    public $lang;
    public $published;

    protected $fileName = '';
    protected $type = 0;
    protected $isCopyrighted = false;
    protected $ownerId = 0;
    protected $pathToOwnerDir = '';
    protected $pathToFile = '';
    protected $pathToOwnerCourseDir;
}

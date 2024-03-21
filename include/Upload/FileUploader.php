<?php

/**
 * FileUploader file
 *
 * PHP version 5
 *
 * @package  Default
 * @author   vito <vito@lynxlab.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FileUploader
 *
 * @package  Default
 * @author   vito <vito@lynxlab.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Upload;

class FileUploader
{
    public function __construct($pathToUploadFolder, $fieldUploadName = 'uploaded_file')
    {
        $this->error = $_FILES[$fieldUploadName]['error'];
        $this->name = $_FILES[$fieldUploadName]['name'];
        $this->size = $_FILES[$fieldUploadName]['size'];
        $this->tmpName = $_FILES[$fieldUploadName]['tmp_name'];
        $this->type = trim(mime_content_type($this->tmpName), '"');

        $this->destinationFolder = $pathToUploadFolder;
        $this->errorMessage = '';
    }

    public function upload($reduction = false)
    {
        $this->cleanFileName();

        if (!is_dir($this->destinationFolder)) {
            if (!$this->createUploadDir()) {
                $this->errorMessage = 'Upload directory do not exists: ' . $this->destinationFolder;
                //return ADA_FILE_UPLOAD_ERROR_UPLOAD_PATH;
                return false;
            }
        }

        if (!is_writable($this->destinationFolder)) {
            $this->errorMessage = 'Upload directory not writable';
            //return ADA_FILE_UPLOAD_ERROR_UPLOAD_PATH;
            return false;
        }
        if (empty($this->name)) {
            $this->errorMessage = 'Uploaded filename is empty';
            return false;
        }
        if ($this->error) {
            $this->errorMessage = 'There was an error during the upload';
            //return $this->error;
            return false;
        }
        $ADA_MIME_TYPE = $GLOBALS['ADA_MIME_TYPE'];
        if ($ADA_MIME_TYPE[$this->type]['permission'] != ADA_FILE_UPLOAD_ACCEPTED_MIMETYPE) {
            $this->errorMessage = 'Mimetype not accepted: <b>' . $this->type . '</b>';
            //return ADA_FILE_UPLOAD_ERROR_MIMETYPE;
            return false;
        }
        if ($this->size >= ADA_FILE_UPLOAD_MAX_FILESIZE) {
            //return ADA_FILE_UPLOAD_ERROR_FILESIZE;
            $this->errorMessage = 'The uploaded file size exceeds the maximum permitted filesize';
            return false;
        }
        if ($reduction) {
            $this->reduceImage();
        }

        return $this->moveFileToDestinationFolder();
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    private function cleanFileName()
    {
        $this->name = preg_replace('/[^\w\-\.]/', '_', $this->name);
    }

    private function moveFileToDestinationFolder()
    {
        if (file_exists($this->getPathToUploadedFile())) {
            $this->name = time() . '_' . $this->name;
        }

        return @move_uploaded_file($this->tmpName, $this->getPathToUploadedFile());
    }

    public function getPathToUploadedFile()
    {
        return $this->destinationFolder . $this->name;
    }

    public function getFileName()
    {
        return $this->name;
    }

    public static function listDirectoryContents($pathToDirectory, $filterFiles = FileUploader::FILES_AND_DIRS, $includeFullPath = false)
    {

        if (is_dir($pathToDirectory)) {
            $files = scandir($pathToDirectory);
            $filteredFiles = [];

            foreach ($files as $f) {
                $pathToTheFile = $pathToDirectory . DIRECTORY_SEPARATOR . $f;
                if (self::testFileType($pathToTheFile, $filterFiles)) {
                    if ($includeFullPath) {
                        $filteredFiles[] = $pathToTheFile;
                    } else {
                        $filteredFiles[] = $f;
                    }
                }
            }

            if ($includeFullPath) {
                $diffArray = [
                    $pathToDirectory . DIRECTORY_SEPARATOR . '.',
                    $pathToDirectory . DIRECTORY_SEPARATOR . '..',
                ];
            } else {
                $diffArray = ['.', '..'];
            }

            return array_diff($filteredFiles, $diffArray);
        } else {
            return [];
        }
    }

    private static function testFileType($pathToTheFile, $type)
    {
        switch ($type) {
            case FileUploader::FILES_ONLY:
                return is_file($pathToTheFile);

            case FileUploader::DIRS_ONLY:
                return is_dir($pathToTheFile);

            default:
                return true;
        }
    }

    /**
     * Creates the upload directory for the user
     *
     * @param integer $user_id
     * @return FALSE if an error occurs, a string containing the path to the
     * directory on success
     */
    public function createUploadDir()
    {

        if (mkdir($this->destinationFolder, 0o777, true) == false) {
            return false;
        }

        return $this->destinationFolder;
    }

    /**
     * Reduce image using GD
     *
     */
    public function reduceImage()
    {
        require_once ROOT_DIR . '/browsing/include/class_image.inc.php';
        $id_img = new ImageDevice();
        $new_img = $id_img->resize_image($this->tmpName, AVATAR_MAX_WIDTH, AVATAR_MAX_HEIGHT);
        if (stristr($this->type, 'png')) {
            imagepng($new_img, $this->tmpName);
        }
        if (stristr($this->type, 'jpeg')) {
            imagejpeg($new_img, $this->tmpName);
        }
        if (stristr($this->type, 'gif')) {
            imagegif($new_img, $this->tmpName);
        }
        //        imagejpeg($new_img,$this->tmpName);
    }

    public function getType()
    {
        return $this->type;
    }


    private $name;
    private $tmpName;
    private $size;
    private $type;
    private $error;
    private $errorMessage;
    private $destinationFolder;

    public const FILES_AND_DIRS = 0;
    public const FILES_ONLY = 1;
    public const DIRS_ONLY = 2;
}

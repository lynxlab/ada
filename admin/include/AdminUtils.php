<?php

/**
 * AdminUtils file
 *
 * PHP version 5
 *
 * @package     Default
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 */

/**
 * Description of AdminUtils
 *
 * @package     Default
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2010, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 */

namespace Lynxlab\ADA\Admin;

class AdminUtils
{
    /**
     *
     *
     * @param integer $user_id
     */
    public static function performCreateAuthorAdditionalSteps($user_id)
    {

        $author_upload_directory = self::createUploadDirForUser($user_id);
        if ($author_upload_directory != false) {
            $result = self::copyCourseModelsIntoDir($author_upload_directory);
        }

        $result = self::createUploadDirForAuthor($user_id);
    }

    /**
     * Creates the upload directory for the user with id $user_id
     *
     * @param integer $user_id
     * @return FALSE if an error occurs, a string containing the path to the
     * directory on success
     */
    public static function createUploadDirForUser($user_id)
    {
        if (is_writable(realpath(ADA_UPLOAD_PATH))  == false) {
            return false;
        }
        $user_upload_directory = ADA_UPLOAD_PATH . $user_id;

        if (mkdir($user_upload_directory) == false) {
            return false;
        }

        return $user_upload_directory;
    }

    /**
     * Copies the available course models into the directory at the given path
     *
     * @param string $directory_path
     * @return boolean TRUE on success, FALSE on error
     */
    private static function copyCourseModelsIntoDir($directory_path)
    {
        $course_models_repository = realpath(ADA_COURSE_MODELS_PATH);
        if (is_readable($course_models_repository) == false) {
            return false;
        }

        $available_course_models = array_diff(
            scandir($course_models_repository),
            ['.','..']
        );
        if ($available_course_models == false) {
            return false;
        }

        foreach ($available_course_models as $course_model) {
            $source_path = $course_models_repository
                         . DIRECTORY_SEPARATOR . $course_model;
            $destination_path = $directory_path
                              . DIRECTORY_SEPARATOR . $course_model;
            if (is_readable($source_path) && is_file($source_path)) {
                copy($source_path, $destination_path);
            }
        }
        return true;
    }

    /**
     * Creates the upload directory for the author with id $author_id
     *
     * @param integer $user_id
     * @return boolean TRUE on success, FALSE on error
     */
    private static function createUploadDirForAuthor($user_id)
    {
        $path_to_dir = ROOT_DIR . MEDIA_PATH_DEFAULT;
        if (is_writable($path_to_dir)  == false) {
            return false;
        }

        if (mkdir($path_to_dir . $user_id, ADA_WRITABLE_DIRECTORY_PERMISSIONS) == false) {
            return false;
        }
        return true;
    }
}

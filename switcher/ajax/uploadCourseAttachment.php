<?php

/**
 * uploads a course attachment file
 *
 * @package     edit course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2017, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Upload\FileUploader;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);


$error = true;
$data = '';
if (isset($_FILES) && count($_FILES) > 0) {
    $fileUploader = new FileUploader(Course::MEDIA_PATH_DEFAULT . $courseID . '/', $fieldUploadName);
    $checkFileArr = $GLOBALS['dh']->getRisorsaEsternaInfoFromFilename($fileUploader->getFileName(), $courseID);

    if (!AMADB::isError($checkFileArr) && $checkFileArr !== false && count($checkFileArr) > 0) {
        $data = sprintf(translateFN('Il file %s già esiste per questo corso'), $checkFileArr['nome_file']);
    } else {
        // file not found for the passed course, add it
        // prepare data to be saved
        $extRes = [
            'nome_file' => $fileUploader->getFileName(),
            'tipo' => array_key_exists($fileUploader->getType(), $GLOBALS['ADA_MIME_TYPE']) ? $GLOBALS['ADA_MIME_TYPE'][$fileUploader->getType()]['type'] : -1,
            'id_nodo' => $courseID,
            'keywords' => isset($filekeywords) ? trim($filekeywords) : null,
            'titolo' => isset($filetitle) ? trim($filetitle) : null,
            'pubblicato' => 1,
            'copyright' => null,
            'lingua' => null,
            'descrizione' => $filedescr ?? null,
            'id_utente' => $userID,
        ];
        // 2nd param forces duplicate filename insertion (if the same file is found linked to a different node/course)
        $res = $GLOBALS['dh']->addRisorsaEsterna($extRes, true);
        if (!AMADB::isError($res)) {
            if ($fileUploader->upload() == false) {
                $GLOBALS['dh']->delRisorseNodi($courseID, $extRes);
                $GLOBALS['dh']->removeRisorsaEsterna($extRes);
                $data = $fileUploader->getErrorMessage();
            } else {
                $data = sprintf(translateFN('File %s caricato correttamente'), $fileUploader->getFileName());
                $error = false;
            }
        } else {
            $data = $res->getMessage();
        }
    }
} else {
    $data = translateFN('Array files vuoto');
}

if ($error) {
    header(' ', true, 500);
}
header('Content-Type: application/json');
die(json_encode(['message' => $data]));

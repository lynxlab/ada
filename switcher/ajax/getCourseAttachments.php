<?php

/**
 * gets course attachments
 *
 * @package     edit course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2017, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../../config_path.inc.php';

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

$error = true;
$data = null;
$withTrashLink = false;
if (array_key_exists('courseID', $_GET) && intval($_GET['courseID']) > 0) {
    $courseID = intval($_GET['courseID']);
    $res = $GLOBALS['dh']->get_node_resources($courseID);
    if (!AMA_DB::isError($res)) {
        foreach ($res as $extResID) {
            $resInfo = $GLOBALS['dh']->get_risorsa_esterna_info($extResID);
            if (!AMA_DB::isError($resInfo)) {
                if (is_null($data)) {
                    $data = [];
                }
                $data[$extResID] = $resInfo;
            } else {
                $data = $res->getMessage();
                break;
            }
        }
    } else {
        $data = $res->getMessage();
    }
} else {
    $data = translateFN('Passare un id corso valido');
}

$error = !(is_array($data) && count($data) > 0);

if ($error === false) {
    $mappedData = $data;
    unset($data);
    $withTrashLink = array_key_exists('trashLink', $_GET) && intval($_GET['trashLink']) === 1;
    array_walk($mappedData, function (&$v, $key) use ($courseID, $withTrashLink) {
        if ($withTrashLink) {
            if ($v['id_utente'] == $_SESSION['sess_userObj']->getId()) {
                $v['deleteLink'] = '<i class="trash large icon link icon" onclick="javascript:deleteCourseAttachment(' . $key . ',' . $courseID . ');"></i>';
            } else {
                $v['deleteLink'] = '<i class="block basic large red icon link icon" title="' . translateFN('File di un altro utente, impossibile cancellare') . '"></i>';
            }
        }
        $link =  BaseHtmlLib::link(str_replace(ROOT_DIR, HTTP_ROOT_DIR, Course::MEDIA_PATH_DEFAULT . $courseID . '/' . str_replace(' ', '_', $v['nome_file'])), translateFN('Click per aprire'));
        $link->setAttribute('target', '_blank');
        $v['url'] = $link->getHtml();
    });
    $data['caption'] = translateFN('Files allegati al corso');
    $data['resources'] = $mappedData;
    $data['headers'] = [
        [
            'property' => 'nome_file',
            'label' => translateFN('Nome file'),
        ],
        [
            'property' => 'titolo',
            'label' => translateFN('Titolo'),
        ],
        [
            'property' => 'descrizione',
            'label' => translateFN('Descrizione'),
        ],
        [
            'property' => 'keywords',
            'label' => translateFN('Keywords'),
        ],
        [
            'property' => 'url',
            'label' => translateFN('Link'),
        ],
    ];
    if ($withTrashLink) {
        $data['headers'][] = [
            'property' => 'deleteLink',
            'label' => translateFN('Cancella'),
        ];
    }
} else {
    $data = translateFN('Allegati al corso') . ': ' . $data;
}

if ($error) {
    header(' ', true, 500);
}
header('Content-Type: application/json');
die(json_encode(['data' => $data]));

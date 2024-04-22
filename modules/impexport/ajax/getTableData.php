<?php

use Lynxlab\ADA\CORE\html4\CBase;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Impexport\AMARepositoryDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
*/
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Users (types) allowed to access this module.
*/
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
];

if (array_key_exists('id_course', $_REQUEST) || array_key_exists('id_node', $_REQUEST)) {
    $neededObjAr[AMA_TYPE_AUTHOR] = ['node', 'layout', 'course'];
    $isAuthorImporting = true;
} else {
    $isAuthorImporting = false;
    $courseObj = null;
    $nodeObj = null;
}

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$rdh = AMARepositoryDataHandler::instance();

$result = [ 'data' => [] ];
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    /**
     * it's a GET
     */
    $getParams = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    $what = array_key_exists('what', $getParams) ? ucfirst(trim($getParams['what'])) : null;
    $canDo = [
        'edit'  => in_array($userObj->getType(), [ AMA_TYPE_SWITCHER ]),
        'trash' => in_array($userObj->getType(), [ AMA_TYPE_SWITCHER ]),
        'import' => in_array($userObj->getType(), [ AMA_TYPE_SWITCHER ]),
    ];
    if (!is_null($what)) {
        [$entity, $action] = explode('::', $what);
        if ($entity == 'Repository') {
            $whereArr = [];
            if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
                $whereArr['id_tester'] = $rdh->getTesterIDFromPointer();
            }
            $list = $rdh->getRepositoryList($whereArr);

            if (!AMADB::isError($list) && is_array($list) && count($list) > 0) {
                $result['data'] = array_map(function ($el) use ($canDo, $userObj, $courseObj, $nodeObj, $isAuthorImporting) {
                    if ($userObj->getType() == AMA_TYPE_AUTHOR) {
                        $canDo['trash'] =  $isAuthorImporting ? false : $userObj->getId() == $el['exporter_userid'];
                        $canDo['import'] = $isAuthorImporting;
                    }
                    $actions = [];
                    // if ($canDo['edit']) {
                    //     $actions['edit'] = CDOMElement::create('a', 'class:tiny teal ui button, title:'.translateFN('Modifica'));
                    //     $actions['edit']->setAttribute('href', MODULES_IMPEXPORT_HTTP .'/editRepoItem.php?id='.$el['id'];
                    //     $actions['edit']->addChild(new CText(translateFN('Modifica')));
                    // }
                    if ($canDo['import']) {
                        $actions['import'] = CDOMElement::create('a', 'class:tiny purple ui button, title:' . translateFN('Importa'));
                        $impHref = MODULES_IMPEXPORT_HTTP . '/import.php?repofile=' . urlencode($el['id_course'] . DIRECTORY_SEPARATOR . MODULES_IMPEXPORT_REPODIR . DIRECTORY_SEPARATOR . $el['filename']);
                        if ($isAuthorImporting) {
                            $impHref .= sprintf("&id_course=%d&id_node=%s", $courseObj->getId(), $nodeObj->id);
                        }
                        $actions['import']->setAttribute('href', $impHref);
                        $actions['import']->addChild(new CText(translateFN('Importa')));
                    }
                    if ($canDo['trash']) {
                        $actions['trash'] = CDOMElement::create('a', 'class:tiny red ui button, title:' . translateFN('Cancella'));
                        $actions['trash']->setAttribute('href', 'javascript:(new initDoc()).deleteRepoItem($j(this),\'' . $el['id'] . '\');');
                        $actions['trash']->addChild(new CText(translateFN('Cancella')));
                    }

                    if (isset($el['filename'])) {
                        unset($el['filename']);
                    }
                    $retArr =  $el;
                    $retArr['actions']  =  array_reduce($actions, function ($carry, $item) {
                        if (strlen($carry ?? '') <= 0) {
                            $carry = '';
                        }
                        $carry .= ($item instanceof CBase ? $item->getHtml() : '');
                        return $carry;
                    });

                    return $retArr;
                }, $list);
            }
        }
    }
}

header('Content-Type: application/json');
die(json_encode($result));

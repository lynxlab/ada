<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Services\NodeEditing\NodeEditing;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Module\Slideimport\Functions\getNameFromFileName;

ini_set('display_errors', '0');
error_reporting(E_ALL);
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
$allowedUsersAr = [AMA_TYPE_AUTHOR];

/**
 * Get needed objects
*/
$neededObjAr = [
        AMA_TYPE_AUTHOR => ['layout'],
];

/**
 * Performs basic controls before entering this module
*/
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$retArray = ['status' => 'ERROR'];

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['selectedPages']) && is_array($_POST['selectedPages']) && count($_POST['selectedPages']) > 0 &&
    isset($_POST['courseID']) && intval($_POST['courseID']) > 0 &&
    isset($_POST['startNode']) && strlen(trim($_POST['startNode'])) > 0 &&
    isset($_POST['asNewCourse']) && intval($_POST['asNewCourse']) >= 0 &&
    isset($_POST['asSlideShow']) && intval($_POST['asSlideShow']) >= 0 &&
    isset($_POST['url']) && strlen(trim($_POST['url'])) > 0
) {
    // sanitize and setup variables
    $selectedPages = array_values($_POST['selectedPages']);
    $courseID = intval($_POST['courseID']);
    $startNode = trim($_POST['startNode']);
    $asNewCourse = intval($_POST['asNewCourse']) === 0 ? false : true;
    $asSlideShow = intval($_POST['asSlideShow']) === 0 ? false : true;
    $withLinkedNodes = intval($_POST['withLinkedNodes']) === 0 ? false : true;
    $hasFrontPage = intval($_POST['hasFrontPage']) === 0 ? false : true;
    $createStartNode = $asNewCourse || $asSlideShow || $hasFrontPage;
    $authorID = $userObj->getId();
    $fileName = str_replace(HTTP_ROOT_DIR, ROOT_DIR, trim($_POST['url']));
    $info = pathinfo($fileName);
    $nodeBaseName = isset($info['filename']) ? getNameFromFileName($info['filename']) : translateFN('File Importato');
    $media_path = ROOT_DIR . MEDIA_PATH_DEFAULT . $userObj->getId() . DIRECTORY_SEPARATOR . $info['filename'];
    $slideShowPath = '..' . MEDIA_PATH_DEFAULT . $userObj->getId() . DIRECTORY_SEPARATOR . $info['filename'] . DIRECTORY_SEPARATOR;
    $imgtemplate = '<MEDIA TYPE="' . _IMAGE . '" VALUE="' . $info['filename'] . DIRECTORY_SEPARATOR . '%filenamehere%">';
    $slideShowupdateMedia = [];

    if (is_readable($media_path)) {
        // create node 0 of the course
        $node_data =  [
                'id' => $courseID . '_0',
                'name' => $nodeBaseName,
                'title' => $nodeBaseName,
                'type' => ($asSlideShow && !$withLinkedNodes) ? ADA_LEAF_TYPE : ADA_GROUP_TYPE,
                'id_node_author' => $authorID,
                'id_nodo_parent' => null,
                'parent_id' => null,
                'text' => translateFN('Importazione del file') . ' ' . $nodeBaseName,
                'id_course' => $courseID];

        $resource_data = [
                'tipo' => _IMAGE,
                'id_utente' => $authorID,
                'copyright' => 0,
                'pubblicato' => 0,
                'lingua' => 0,
                'descrizione' => '',
                'keywords' => '',
        ];

        if (!$asSlideShow && $hasFrontPage) {
            reset($selectedPages);
            $selectedPage = array_shift($selectedPages);
            $node_data['text'] = str_replace('%filenamehere%', $selectedPage . '.jpg', $imgtemplate);
            /**
             * cursed addMedia method in ama.inc.php starts inserting resources from array index 1!!!
             */
            $node_data['resources_ar'] = [ 1 =>
                array_merge($resource_data, [
                    'nome_file' => $info['filename'] . DIRECTORY_SEPARATOR . $selectedPage . '.' . IMAGE_FORMAT,
                    'titolo' => translateFN('Pagina') . ' ' . $selectedPage,
                ])];
        }

        if ($asNewCourse === false) {
            $order = $GLOBALS['dh']->getOrdineMaxVal($startNode);
            if (AMADB::isError($order) || is_null($order)) {
                $order = 0;
            }
            $node_data['id'] = null;
            $node_data['id_nodo_parent'] = $startNode;
            $node_data['parent_id'] = $startNode;
        } else {
            $order = 0;
        }

        if ($createStartNode) {
            $node_data['order'] = ++$order;
            $createdNodeID = NodeEditing::createNode($node_data);
        } else {
            $createdNodeID = $startNode;
        }

        if (!AMADB::isError($createdNodeID)) {
            $error = false;
            if ($createStartNode) {
                $createdNodes = [$createdNodeID];
            } else {
                $createdNodes = [];
            }

            $order = $GLOBALS['dh']->getOrdineMaxVal($createdNodeID);
            if (AMADB::isError($order) || is_null($order)) {
                $order = 0;
            }

            // prepare nivo slider holding elements
            $slideshow_data = $node_data;
            $slideshow_data['resources_ar'] = [];
            $wrapper = CDOMElement::create('div', 'class:slider-wrapper theme-default');
            $nivo = CDOMElement::create('div', 'id:slider,class:nivoSlider');
            $wrapper->addChild($nivo);

            foreach ($selectedPages as $key => $selectedPage) {
                if (
                    !$asSlideShow || ($key > 0 && $asSlideShow && $withLinkedNodes) ||
                    ($key == 0 && $asSlideShow && $withLinkedNodes && !$hasFrontPage)
                ) {
                    // build a node foreach selectedPage
                    $child_data = $node_data;
                    $child_data['id'] = null;
                    $child_data['order'] = ++$order;
                    $child_data['type'] = ADA_LEAF_TYPE;
                    $child_data['id_nodo_parent'] = $createdNodeID;
                    $child_data['parent_id'] = $createdNodeID;
                    $child_data['name'] = translateFN('Pagina') . ' ' . $selectedPage;
                    $child_data['title'] = $child_data['name'];
                    $child_data['text'] = str_replace('%filenamehere%', $selectedPage . '.jpg', $imgtemplate);

                    /**
                     * cursed addMedia method in ama.inc.php starts inserting resources from array index 1!!!
                     */
                    $child_data['resources_ar'] = [ 1 =>
                        array_merge($resource_data, [
                                'nome_file' => $info['filename'] . DIRECTORY_SEPARATOR . $selectedPage . '.' . IMAGE_FORMAT,
                                'titolo' => !$asSlideShow ? $child_data['name'] : ($selectedPage . ' - ' . $nodeBaseName),
                        ])];

                    $childNodeID = NodeEditing::createNode($child_data);
                    if (AMADB::isError($childNodeID)) {
                        $error = true;
                        // delete all nodes
                        foreach ($createdNodes as $createdNode) {
                            $GLOBALS['dh']->removeNode($createdNode);
                        }
                        break;
                    } else {
                        array_push($createdNodes, $childNodeID);
                    }
                } elseif ($key == 0 && $asSlideShow && $withLinkedNodes) {
                    // must add first node external resource here, otherwise it will
                    // be incorrectly listed as last one in the navigation panel/resource section
                    $GLOBALS['dh']->addOnlyInRisorsaEsterna(array_merge($resource_data, [
                        'id_nodo' => $createdNodeID,
                        'nome_file' => $info['filename'] . DIRECTORY_SEPARATOR . $selectedPage . '.' . IMAGE_FORMAT,
                        'titolo' => $selectedPage . ' - ' . $nodeBaseName]));
                    // external resource will be linked to the node afterwards
                }

                if ($asSlideShow) {
                    // img object
                    $imgPath = $slideShowPath . $selectedPage . '.' . IMAGE_FORMAT;
                    $img = CDOMElement::create('img');
                    $img->setAttribute('alt', '');
                    $img->setAttribute('title', $selectedPage . ' - ' . $nodeBaseName);
                    $img->setAttribute('data-thumb', $imgPath);
                    $img->setAttribute('src', $imgPath);

                    if (
                        isset($childNodeID) && !AMADB::isError($childNodeID) && $withLinkedNodes &&
                        ($key != 0  || ($key == 0 && !$hasFrontPage))
                    ) {
                        $a = CDOMElement::create('a', 'href:view.php?id_node=' . $childNodeID);
                        $a->addChild($img);
                        $nivo->addChild($a);
                    } else {
                        $nivo->addChild($img);
                    }
                    $slideShowupdateMedia[$info['filename'] . DIRECTORY_SEPARATOR . $selectedPage . '.' . IMAGE_FORMAT] = _IMAGE;
                }
            } // end foreach selectedPages

            // if it's a slideshow, the first created note must be updated
            if ($asSlideShow) {
                $slideshow_data['id'] = $createdNodeID;
                $slideshow_data['text'] = $wrapper->getHtml();
                if (AMADB::isError(NodeEditing::saveNode($slideshow_data))) {
                    $error = true;
                    // delete all nodes
                    foreach ($createdNodes as $createdNode) {
                        $GLOBALS['dh']->removeNode($createdNode);
                    }
                } else {
                    // update node media
                    if (count($slideShowupdateMedia) > 0) {
                        NodeEditing::updateMediaAssociationsWithNode($slideshow_data['id'], $authorID, [], $slideShowupdateMedia);
                    }
                }
            }

            if (!$error) {
                $retArray['status'] = 'OK';
                $retArray['nodeId'] = $createdNodeID;
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($retArray);

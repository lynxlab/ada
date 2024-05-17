<?php

/**
 * SLIDEIMPORT MODULE.
 *
 * @package        slideimport module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           slideimport
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Slideimport;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\AMAError;

class AMASlideimportDataHandler extends AMADataHandler
{
    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public static $PREFIX = 'module_slideimport_';

    /**
     * @var string char for separating courseId from nodeId (e.g. 110_0) in tabella nodo
     */
    public static $courseSeparator = '_';

    /**
     * Recursively gets an array with passed node and all of its children
     * inlcuded values are name and id, used for json encoding when building
     * course tree for selecting which node to export.
     *
     * @param string $rootNode the id of the node to be treated as root
     * @param AMASlideimportDataHandler $dh the data handler used to retreive datas
     * @param boolean $mustRecur
     *
     * @return array
     *
     * @access public
     */
    public function getAllChildrenArray($rootNode, $dh = null, $mustRecur = true)
    {
        if (is_null($dh)) {
            $dh = $this;
        }

        // first get all passed node data
        $nodeInfo = $dh->getNodeInfo($rootNode);

        if (!AMADB::isError($nodeInfo)) {
            $retarray =  ['id' => $rootNode, 'label' => $nodeInfo['name']];

            if ($mustRecur) {
                // get node children only having instance=0
                $childNodesArray = $dh->getNodeChildren($rootNode, 0);
                if (!empty($childNodesArray) && !AMADB::isError($childNodesArray)) {
                    $i = 0;
                    $children = [];
                    foreach ($childNodesArray as &$childNodeId) {
                        $children[$i++] = $this->getAllChildrenArray($childNodeId, $dh, $mustRecur);
                    }
                    $retarray['children'] = $children;
                }
            }
        } else {
            return [];
        }

        return $retarray;
    }

    /**
     * Get the children of a given node.
     *
     * THIS METHOD IS OVERRIDDEN TO EXCLUDE
     * NODES OF TYPES: ADA_NOTE_TYPE, ADA_PRIVATE_NOTE_TYPE THAT
     * THAT WE DON'T WANT IN THE RETURNED ARRAY
     *
     * @access public
     *
     * @param $node_id the id of the father
     *
     * @return an array of ids containing all the id's of the children of a given node
     *
     * @see getNodeInfo
     *
     */
    public function &getNodeChildren($node_id, $id_course_instance = "")
    {
        $excludeNodeTypes =  [ ADA_NOTE_TYPE, ADA_PRIVATE_NOTE_TYPE ];

        $values = [
            $node_id,
        ];
        if ($id_course_instance != "") {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent=? AND id_istanza=?";
            $values[] = $id_course_instance;
        } else {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent=?";
        }

        if (is_array($excludeNodeTypes) && !empty($excludeNodeTypes)) {
            $sql .= " AND `tipo` NOT IN(" . implode(',', $excludeNodeTypes) . ")";
        }

        $sql .= " ORDER BY ordine ASC";

        $res_ar = & $this->getColPrepared($sql, $values);
        if (AMADB::isError($res_ar)) {
            return new AMAError(AMA_ERR_GET);
        }
        // return an error in case of an empty recordset
        if (!$res_ar) {
            $retErr = new AMAError(AMA_ERR_NOT_FOUND);
            return $retErr;
        }
        // return nested array
        return $res_ar;
    }
}

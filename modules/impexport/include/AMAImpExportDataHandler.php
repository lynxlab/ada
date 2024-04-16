<?php

/**
 * @package     import/export course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Impexport;

class AMAImpExportDataHandler extends AMADataHandler
{
    /**
     * Get the children of a given node.
     *
     * FOR EXPORT PURPOSES THIS METHOD IS OVERRIDDEN TO EXCLUDE
     * NODES OF TYPES: ADA_NOTE_TYPE, ADA_PRIVATE_NOTE_TYPE THAT
     * THAT WE DON'T WANT TO EXPORT
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
    public function &exportGetNodeChildren($node_id, $id_course_instance = "")
    {
        $db = & $this->getConnection();

        $excludeNodeTypes =  [ ADA_NOTE_TYPE, ADA_PRIVATE_NOTE_TYPE ];

        if (AMADB::isError($db)) {
            return $db;
        }

        if ($id_course_instance != "") {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent='$node_id' AND id_istanza='$id_course_instance'";
        } else {
            $sql  = "select id_nodo,ordine from nodo where id_nodo_parent='$node_id'";
        }

        if (is_array($excludeNodeTypes) && !empty($excludeNodeTypes)) {
            $sql .= " AND `tipo` NOT IN(" . implode(',', $excludeNodeTypes) . ")";
        }

        $sql .= " ORDER BY ordine ASC";

        $res_ar = & $db->getCol($sql);
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

    /**
     * Need to promote this method to public
     * @see AMATesterDataHandler::doGetIdPosition
     */
    public function getIdPosition($pos_ar)
    {
        return parent::doGetIdPosition($pos_ar);
    }
    /**
     * Need to promote this method to public
     * @see AMATesterDataHandler::doAddPosition
     */
    public function addPosition($pos_ar)
    {
        return parent::doAddPosition($pos_ar);
    }

    /**
     * gets all the nodes that have an internal link in their text (aka testo) field.
     *
     * @param int $course_id the id of the course to search for
     *
     * @return array of fetched rows
     *
     * @access public
     */
    public function getNodesWithInternalLinkForCourse($course_id, $start_import_time = null)
    {
        $sql = 'SELECT `id_nodo`  FROM `nodo` WHERE UPPER(`testo`) LIKE ? AND `id_nodo` LIKE ?';

        $values =  [ '<%LINK%TYPE="INTERNAL"%VALUE%>%', $course_id . '_%'];

        if (!is_null($start_import_time)) {
            $sql .= ' AND `data_creazione`>= ?';
            array_push($values, $start_import_time);
        }

        return $this->getAllPrepared($sql, $values);
    }

    /**
     * gets all the course nodes that
     *
     * @param int $course_id the id of the course to search for
     *
     * @return array of fetched rows
     *
     * @access public
     */
    public function getNodesWithTestLinkForCourse($course_id, $start_import_time = null)
    {
        $sql = 'SELECT N.`id_nodo`FROM `nodo` N
				JOIN
				`module_test_nodes` MT ON N.`id_nodo` = MT.`id_nodo_riferimento`
				WHERE N.`id_nodo` LIKE ? AND N.`testo` LIKE \'%modules/test/index.php?id_test=%\'';

        $values =  [$course_id . '%'];

        if (!is_null($start_import_time)) {
            $sql .= ' AND N.`data_creazione`>= ?';
            array_push($values, $start_import_time);
        }

        return $this->getAllPrepared($sql, $values);
    }
}

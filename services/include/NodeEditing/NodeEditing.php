<?php

/**
 * NodeEditing class.
 *
 * @package
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Services\NodeEditing;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\NodeEvent;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class NodeEditing, provides utility methods needed by
 * node editing activity.
 *
 * @author vito
 */

class NodeEditing
{
    /**
     * function getMediaFromNodeText, used to obtain all of the media
     * associated with a node by parsing its text.
     *
     * @param  string $text  - text of node
     * @return array  $media - an associative array ('media'=>'media_type')
     */
    public static function getMediaFromNodeText($text)
    {
        $media_type  = _IMAGE . '|' . _SOUND . '|' . _VIDEO . '|' . _PRONOUNCE . '|' . _MONTESSORI . '|' . _LABIALE . '|' . _LIS . '|' . _FINGER_SPELLING . '|' . _LINK . '|INTERNAL'; //'0|1|2|4|....';
        $media_value = '(?:[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]{3,4})';

        //        $extract_media_tags = '/<(?:LINK|MEDIA) TYPE="('.$media_type.')" VALUE="([a-zA-Z0-9_\-\/\.?~+%=&,$\'\(\):;*@\[\]]+)">/';
        $extract_media_tags = '/<(?:LINK|MEDIA) TYPE="([0-5]+|INTERNAL)" VALUE="([a-zA-Z0-9_\-\/\.?~+%=&,$\'\(\):;*@\[\]]+)">/i';

        $media_tags_found   = [];


        preg_match_all($extract_media_tags, $text, $media_tags_found);

        $media = [];
        foreach ($media_tags_found[2] as $key => $item) {
            $media[$item] = $media_tags_found[1][$key];
        }
        return $media;
    }

    /**
     * function updateMediaAssociationsWithNode, used to add and/or remove
     * internal links and external resources from a node.
     *
     * @param string $edited_node_id  - id of the node
     * @param int    $user_id         - id of the user currently editing this node
     * @param array  $media_to_remove - all the media(internal links, external resources) to remove from this node
     * @param array  $media_to_add    - all the media(internal links, external resources) added to this node
     * @return mixed
     */
    public static function updateMediaAssociationsWithNode($edited_node_id, $user_id, $media_to_remove = [], $media_to_add = [])
    {
        $dh = $GLOBALS['dh'];

        // vito, 27 mar 2009
        $sess_id_course = array_key_exists('sess_id_course', $_SESSION) ? $_SESSION['sess_id_course'] : null;

        if (!empty($media_to_remove)) {
            //            $internal_links     = array();
            //            $external_resources = array();
            foreach ($media_to_remove as $media => $type) {
                switch ($type) {
                    case 'INTERNAL':
                        //    $internal_links[] = $dh->getLinkId($edited_node_id, $media);
                        //$internal_links[] = $media;

                        // vito, 27 mar 2009
                        $linked_node = $sess_id_course . '_' . $media;
                        // vito, 27 mar 2009
                        //$internal_link = $dh->getLinkId($dh->sqlPrepared($edited_node_id), $dh->sqlPrepared($media));
                        $internal_link = $dh->getLinkId($dh->sqlPrepared($edited_node_id), $dh->sqlPrepared($linked_node));
                        if (AMADataHandler::isError($internal_link)) {
                            return $internal_link;
                        }
                        $result = $dh->removeLink($internal_link);
                        if (AMADataHandler::isError($result)) {
                            return $result;
                        }
                        break;
                    default:
                        // $external_resources[] = $dh->getRisorsaEsternaId($media);
                        $external_resource = $dh->getRisorsaEsternaId($media);
                        if (AMADataHandler::isError($external_resource)) {
                            return $external_resource;
                        }
                        $result = $dh->delRisorseNodi($dh->sqlPrepared($edited_node_id), $external_resource);
                        if (AMADataHandler::isError($result)) {
                            return $result;
                        }
                        break;
                }
            }
        }

        if (!empty($media_to_add)) {
            //            $internal_links     = array();
            //            $external_resources = array();
            foreach ($media_to_add as $media => $type) {
                switch ($type) {
                    case 'INTERNAL':
                        // $internal_links[$link++] = array('id_nodo' => $edited_node_id,
                        // vito, 27 mar 2009
                        $linked_node = $sess_id_course . '_' . $media;

                        $link_ha = [
                            'id_nodo'        => $edited_node_id,
                            // vito, 27 mar 2009
                            //'id_nodo_to'     => $media,
                            'id_nodo_to'     => $linked_node,
                            'id_utente'      => $user_id,
                            'tipo'           => null,
                            'data_creazione' => '',
                            'stile'          => null,
                            'significato'    => '',
                            'azione'         => null,
                            'posizione'      => [100, 100, 200, 200],
                        ];

                        $result = $dh->addLink($link_ha);
                        //if (AMADataHandler::isError($result)) return $result;
                        break;
                    case _LINK:
                        $res_ha = [
                            'nome_file' => $media,
                            'tipo'      => $type,
                            'copyright' => null,
                            'id_nodo'   => $edited_node_id,
                            'id_utente' => $user_id,
                        ];
                        $id_ext_res = $dh->addRisorsaEsterna($res_ha);
                        if (AMADataHandler::isError($id_ext_res)) {
                            return $id_ext_res;
                        }
                        if ($id_ext_res < 0) { // il media e' gia' in risorsa_esterna
                            $result_ext = $dh->addRisorseNodi("'$edited_node_id'", abs($id_ext_res));
                            if (AMADataHandler::isError($result_ext)) {
                                return $result_ext;
                            }
                        }
                        break;
                    default:
                        $res_ha = [
                            'nome_file' => $media,
                            'tipo'      => $type,
                            'copyright' => null,
                            'id_nodo'   => $edited_node_id,
                            'id_utente' => $user_id,
                        ];
                        //                        $id_ext_res = $dh->addRisorsaEsterna($res_ha);
                        //                        if (AMADataHandler::isError($id_ext_res)) return $id_ext_res;
                        $external_resource = $dh->getRisorsaEsternaId($media);
                        if (AMADataHandler::isError($external_resource)) {
                            return $external_resource;
                        }
                        $result_ext = $dh->addRisorseNodi("'$edited_node_id'", $external_resource);
                        if (AMADataHandler::isError($result_ext)) {
                            return $result_ext;
                        }
                        break;
                }
            }
        }
        return true;
    }
    /**
     * function saveNodePosition, used to save node position
     *
     * @param array $node_data
     * @return mixed
     */
    public static function saveNodePosition($node_data = [])
    {
        $dh = $GLOBALS['dh'];

        if (isset($node_data['position'])) {
            $position_string = $node_data['position'];
            unset($node_data['position']);

            $position_array = [];
            $matches        = [];

            $regexp = '/([0-9]+),([0-9]+),([0-9]+),([0-9]+)/';

            if (preg_match($regexp, $position_string, $matches)) {
                $node_data['pos_x0'] = $matches[1];
                $node_data['pos_y0'] = $matches[2];
                $node_data['pos_x1'] = $matches[3];
                $node_data['pos_y1'] = $matches[4];
                // use this position
            } else {
                // use a default position
                $node_data['pos_x0'] = 100;
                $node_data['pos_y0'] = 100;
                $node_data['pos_x1'] = 200;
                $node_data['pos_y1'] = 200;
            }
        }

        $result = $dh->setNodePosition($node_data);
        if (AMADataHandler::isError($result)) {
            return $result;
        }

        return true;
    }

    /**
     * function saveNode, used to save node text and other attributes
     *
     * @param array $node_data
     * @return mixed
     */
    public static function saveNode($node_data = [])
    {
        $dh = $GLOBALS['dh'];
        /*
     * Increment version counter
        */
        if (isset($node_data['forcecreationupdate'])) {
            $node_data['version']++;
        }

        $node_data['is_forkedpaths'] = isset($node_data['is_forkedpaths']) ? intval($node_data['is_forkedpaths']) : 0;

        //vito 6 feb 2009
        if (trim($node_data['name']) == "") {
            $node_data['name'] = translateFN('Senza Titolo');
        }
        /*
     * Update node in db
        */
        /*
     * vito, 8 ottobre 2008: se voglio modificare una nota
     * devo passare anche l'id istanza corso
        */
        if (
            $node_data['type']    == ADA_NOTE_TYPE
            || $node_data['type'] == ADA_PRIVATE_NOTE_TYPE
        ) {
            $node_data['id_instance'] = $_SESSION['sess_id_course_instance'];
        }

        if (isset($node_data['position'])) {
            $position_string = $node_data['position'];
            unset($node_data['position']);

            $position_array = [];
            $matches        = [];

            $regexp = '/([0-9]+),([0-9]+),([0-9]+),([0-9]+)/';

            if (preg_match($regexp, $position_string, $matches)) {
                $node_data['pos_x0'] = $matches[1];
                $node_data['pos_y0'] = $matches[2];
                $node_data['pos_x1'] = $matches[3];
                $node_data['pos_y1'] = $matches[4];
                // use this position
            } else {
                // use a default position
                $node_data['pos_x0'] = 100;
                $node_data['pos_y0'] = 100;
                $node_data['pos_x1'] = 200;
                $node_data['pos_y1'] = 200;
            }
        }

        /*
     * Handle icon assignment.
        */
        $root_dir = $GLOBALS['root_dir'];
        $template_family = $_SESSION['sess_template_family'];
        $path_to_icon = $root_dir . '/templates/browsing/' . $template_family;
        //        if(trim($node_data['icon']) == "" || !file_exists($path_to_icon.'/'.$node_data['icon'])) {
        //        if(trim($node_data['icon']) == "" || !file_exists($node_data['icon'])) {
        if (!isset($node_data['icon']) || !file_exists($node_data['icon'])) {
            $node_data['icon'] = 'nodo.png';
        }

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            ADAEventDispatcher::buildEventAndDispatch([
                'eventClass' => NodeEvent::class,
                'eventName' => 'PRESAVE',
            ], $node_data, ['isUpdate' => true]);
        }

        $result = $dh->doEditNode($node_data);

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            ADAEventDispatcher::buildEventAndDispatch([
                'eventClass' => NodeEvent::class,
                'eventName' => 'POSTSAVE',
            ], $node_data, ['isUpdate' => true, 'saveResult' => $result]);
        }

        if (AMADataHandler::isError($result)) {
            return $result;
        }

        return true;
    }

    public static function createNode($node_data = [])
    {
        $dh = $GLOBALS['dh'];

        // vito 26 jan 2009
        $regexp = '/([0-9]+),([0-9]+),([0-9]+),([0-9]+)/';
        $matches = [];

        if (isset($node_data['position']) && preg_match($regexp, $node_data['position'], $matches)) {
            $node_data['pos_x0'] = $matches[1];
            $node_data['pos_y0'] = $matches[2];
            $node_data['pos_x1'] = $matches[3];
            $node_data['pos_y1'] = $matches[4];
            // use this position
        } else {
            // use a default position
            $node_data['pos_x0'] = 100;
            $node_data['pos_y0'] = 100;
            $node_data['pos_x1'] = 200;
            $node_data['pos_y1'] = 200;
        }
        unset($node_data['position']);

        //vito 6 feb 2009
        if (trim($node_data['name']) == "") {
            $node_data['name'] = translateFN('Senza Titolo');
        }

        /*
     * vito, 8 ottobre 2008: se voglio inserire una nota
     * devo passare anche l'id istanza corso
        */
        if (
            $node_data['type']    == ADA_NOTE_TYPE
            || $node_data['type'] == ADA_PRIVATE_NOTE_TYPE
        ) {
            $node_data['id_instance'] = $_SESSION['sess_id_course_instance'];
        }

        /*
     * Handle icon assignment.
        */
        $root_dir = $GLOBALS['root_dir'];
        $template_family = $_SESSION['sess_template_family'];
        $path_to_icon = $root_dir . '/templates/browsing/' . $template_family;
        if (!isset($node_data['icon']) || trim($node_data['icon']) == "" || !file_exists($node_data['icon'])) {
            $node_data['icon'] = 'nodo.png';
        }

        $node_data['creation_date'] = "now";
        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            ADAEventDispatcher::buildEventAndDispatch([
                'eventClass' => NodeEvent::class,
                'eventName' => 'PRESAVE',
            ], $node_data, ['isUpdate' => false]);
        }

        $result = $dh->addNode($node_data);

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            ADAEventDispatcher::buildEventAndDispatch([
                'eventClass' => NodeEvent::class,
                'eventName' => 'POSTSAVE',
            ], $node_data, ['isUpdate' => false, 'saveResult' => $result]);
        }

        if (AMADataHandler::isError($result)) {
            return $result;
        } else {
            $node_id = $result;
        }

        //        return true;
        return $node_id;
    }

    public static function getAuthorMedia($id_course, $media_type = [])
    {
        $dh = $GLOBALS['dh'];
        $course_ha = $dh->getCourse($id_course);
        if (AMADataHandler::isError($course_ha)) {
            return $course_ha;
        }
        $id_author = $course_ha['id_autore'];

        $author_media = $dh->getRisorseAutore($id_author, $media_type);
        return $author_media;
    }
}

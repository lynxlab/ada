<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\Node\Media;

use Lynxlab\ADA\Main\Node\ADAResource;

use Lynxlab\ADA\Main\AMA\AMADataHandler;

use function \translateFN;

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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Link extends ADAResource
{
    public $position;
    public $type;
    public $node_id;
    public $author;
    public $meaning;
    public $creation_date;
    public $to_node_id;
    public $style;
    public $action;


    public function __construct($id_link)
    {
        //global $dh,$error;
        // constructor
        $dh =   $GLOBALS['dh'];
        $error =   $GLOBALS['error'];
        $debug =   $GLOBALS['debug'] ?? null;

        $dataHa = $dh->getLinkInfo($id_link);
        if (AMADataHandler::isError($dataHa) || (!is_array($dataHa))) {
            $msg = $dataHa->getMessage();
            if (!strstr($msg, 'record not found')) {
                header("Location: $error?err_msg=$msg");
                exit;
            } else {
                $this->full = 1;
                return $msg;
            }
        }



        if (!empty($dataHa['id_nodo'])) {
            //                foreach ($dataHa as $linkHa) {
            $linkHa = $dataHa; //?? uno solo???
            $this->position =  $linkHa['posizione'];
            $this->author =  $linkHa['autore'];
            $this->node_id =  $linkHa['id_nodo'];
            $this->to_node_id =  $linkHa['id_nodo_to'];
            $this->type =  $linkHa['tipo'];
            $this->creation_date =  $linkHa['data_creazione'];
            $this->style =  $linkHa['stile'];
            $this->action =  $linkHa['azione'];
            $this->meaning =  $linkHa['significato'];
            //                }

            $this->full = 1;
        } else {
            $this->error_msg = translateFN("Nessuno");
        }
    }
}

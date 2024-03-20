<?php

/**
 * SERVICE.
 *
 * @package     service
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        services_class
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Service;

use Lynxlab\ADA\Main\AMA\MultiPort;

use function Lynxlab\ADA\Main\Service\Functions\level2descriptionFN;

class ServiceImplementor
{
    private $provider_name;
    private $provider_ragsoc;

    private $provider_id;
    private $provider_address;
    private $provider_department;
    private $provider_country;
    private $provider_city;
    private $provider_phone;
    private $provider_email;
    private $provider_responsible;

    private $provider_pointer;
    private $provider_desc;

    private $implementorId;

    private $name;
    private $title;
    private $id_author;
    private $id_layout;

    private $d_create;
    private $d_publish;
    private $id_start_node;
    private $id_toc_node;
    private $media_path;

    private $descr;
    private $description;

    public static function findImplementor($implementorId)
    {
        $common_dh = $GLOBALS['common_dh'];

        //$provider_dataHa = $common_dh->get_tester_info_from_id($id_provider);
        $provider_dataHa = $common_dh->get_tester_info_from_id_course($implementorId);
        if (AMA_DataHandler::isError($provider_dataHa)) {
            // ?
        } else {
            $provider_dsn = MultiPort::getDSN($provider_dataHa['puntatore']);
            if ($provider_dsn != null) {
                $provider_dh = AMA_DataHandler::instance($provider_dsn);
                if (AMA_DataHandler::isError($provider_dh)) {
                    return $provider_dh;
                } else {
                    $courseAr = $provider_dh->get_course($implementorId);

                    if (AMA_DataHandler::isError($courseAr)) {
                        // continue
                        $courseAr = [];
                        $courseAr['id_course'] = $implementorId;
                    } else {
                        if (!isset($courseAr['id_nodo_iniziale'])) {
                            $courseAr['id_nodo_iniziale'] = 0;
                        }

                        $id_start_node = $courseAr['id_nodo_iniziale'];
                        $id_desc = $implementorId . "_" . $id_start_node;
                        $user_level = "999";

                        $nodeHa = $provider_dh->get_node_info($id_desc);
                        if (AMA_DataHandler::isError($nodeHa)) {
                            // continue
                            $nodeHa = [];
                            $nodeHa['text'] = null;
                        }
                    }
                }
            }
        }
        $serviceImplementorObj = new ServiceImplementor($provider_dataHa, $courseAr, $nodeHa);
        return $serviceImplementorObj;
    }

    public function __construct($provider_dataHa, $courseAr, $nodeHa)
    {

        $this->implementorId = $courseAr['id_course'];

        // id_tester,nome,ragione_sociale,indirizzo,citta,provincia,nazione,telefono,e_mail,responsabile,puntatore
        $this->provider_id = $provider_dataHa['id_tester'];
        $this->provider_name = $provider_dataHa['nome'];
        $this->provider_country = $provider_dataHa['nazione'];
        $this->provider_department = $provider_dataHa['provincia'];
        $this->provider_desc = $provider_dataHa['descrizione'];
        $this->provider_email = $provider_dataHa['e_mail'];
        $this->provider_phone = $provider_dataHa['telefono'];
        $this->provider_address = $provider_dataHa['indirizzo'];
        $this->provider_ragsoc = $provider_dataHa['ragione_sociale'];
        $this->provider_responsible = $provider_dataHa['responsabile'];
        $this->provider_city = $provider_dataHa['citta'];
        $this->provider_pointer = $provider_dataHa['puntatore'];

        $this->name = $courseAr['nome'];
        $this->title = $courseAr['titolo'];
        // $this->id_author = $courseAr['id_autore'];
        // $this->id_layout = $courseAr['id_layout'];
        if ($courseAr['descr'] == null) {
            $this->descr = level2descriptionFN($serviceAr[3]);
        } else {
            $this->descr = $courseAr['descr'];
        }

        $this->d_create = $courseAr['d_create'];
        $this->d_publish = $courseAr['d_publish'];
        $this->id_start_node = $courseAr['id_nodo_iniziale'];
        //  $this->id_toc_node = $courseAr['id_nodo_toc'];
        //  $this->media_path = $courseAr['media_path'];

        if ($nodeHa['text'] == null) {
            //  $errorObj = new ADA_error($nodeHa); //FIXME: mancano gli altri dati
            $this->description = translateFN("Not available");
        } else {
            $this->description = $nodeHa['text'];
        }
    }

    /* Getters */

    public function get_title()
    {
        return $this->title;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function get_descr()
    {
        return $this->descr;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_creation_date()
    {
        return $this->d_create;
    }

    public function get_publish_date()
    {
        return $this->d_publish;
    }

    public function get_start_node()
    {
        return $this->id_start_node;
    }

    public function get_provider_pointer()
    {
        return $this->provider_pointer;
    }

    public function get_provider_name()
    {
        return $this->provider_name;
    }

    public function get_provider_country()
    {
        return $this->provider_country;
    }

    public function get_provider_department()
    {
        return $this->provider_department;
    }

    public function get_provider_city()
    {
        return $this->provider_city;
    }

    public function get_provider_desc()
    {
        return $this->provider_desc;
    }

    public function get_provider_address()
    {
        return $this->provider_address;
    }

    public function get_provider_e_mail()
    {
        return $this->provider_email;
    }

    public function get_provider_phone()
    {
        return $this->provider_phone;
    }

    public function get_provider_ragsoc()
    {
        return $this->provider_ragsoc;
    }

    public function get_provider_responsible()
    {
        return $this->provider_responsible;
    }

    /**
     *
     * @return an associative array containing the information about provider and course
     */
    public function get_implementor_info()
    {
        $courseAr = [
            $this->get_title(),
            $this->get_description(),
            $this->get_name(),
            $this->get_creation_date(),
            $this->get_publish_date(),
            $this->get_start_node(),
            $this->get_provider_name(),
            $this->get_provider_country(),
            $this->get_provider_department(),
            $this->get_provider_city(),
            $this->get_descr(),
            $this->get_provider_desc(),
            $this->get_provider_e_mail(),
            $this->get_provider_phone(),
            $this->get_provider_ragsoc(),
            $this->get_provider_address(),
            $this->get_provider_responsible(),
        ];
        return $courseAr;
    }
}

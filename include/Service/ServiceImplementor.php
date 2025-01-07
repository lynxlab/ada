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

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
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
        $common_dh = AMACommonDataHandler::getInstance();

        //$provider_dataHa = $common_dh->getTesterInfoFromId($id_provider);
        $provider_dataHa = $common_dh->getTesterInfoFromIdCourse($implementorId);
        if (AMADataHandler::isError($provider_dataHa)) {
            // ?
        } else {
            $provider_dsn = MultiPort::getDSN($provider_dataHa['puntatore']);
            if ($provider_dsn != null) {
                $provider_dh = AMADataHandler::instance($provider_dsn);
                if (AMADataHandler::isError($provider_dh)) {
                    return $provider_dh;
                } else {
                    $courseAr = $provider_dh->getCourse($implementorId);

                    if (AMADataHandler::isError($courseAr)) {
                        // continue
                        $courseAr = [];
                        $courseAr['id_course'] = $implementorId;
                    } else {
                        if (!isset($courseAr['id_nodo_iniziale'])) {
                            $courseAr['id_nodo_iniziale'] = 0;
                        }

                        if (!isset($courseAr['id_course'])) {
                            $courseAr['id_course'] = $implementorId;
                        }

                        $id_start_node = $courseAr['id_nodo_iniziale'];
                        $id_desc = $implementorId . "_" . $id_start_node;
                        $user_level = "999";

                        $nodeHa = $provider_dh->getNodeInfo($id_desc);
                        if (AMADataHandler::isError($nodeHa)) {
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
            $common_dh = AMACommonDataHandler::getInstance();
            $serviceAr = $common_dh->getServiceInfoFromCourse($courseAr['id_course']);
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
            //  $errorObj = new ADAError($nodeHa); //FIXME: mancano gli altri dati
            $this->description = translateFN("Not available");
        } else {
            $this->description = $nodeHa['text'];
        }
    }

    /* Getters */

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getDescr()
    {
        return $this->descr;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCreationDate()
    {
        return $this->d_create;
    }

    public function getPublishDate()
    {
        return $this->d_publish;
    }

    public function getStartNode()
    {
        return $this->id_start_node;
    }

    public function getProviderPointer()
    {
        return $this->provider_pointer;
    }

    public function getProviderName()
    {
        return $this->provider_name;
    }

    public function getProviderCountry()
    {
        return $this->provider_country;
    }

    public function getProviderDepartment()
    {
        return $this->provider_department;
    }

    public function getProviderCity()
    {
        return $this->provider_city;
    }

    public function getProviderDesc()
    {
        return $this->provider_desc;
    }

    public function getProviderAddress()
    {
        return $this->provider_address;
    }

    public function getProviderEMail()
    {
        return $this->provider_email;
    }

    public function getProviderPhone()
    {
        return $this->provider_phone;
    }

    public function getProviderRagsoc()
    {
        return $this->provider_ragsoc;
    }

    public function getProviderResponsible()
    {
        return $this->provider_responsible;
    }

    /**
     *
     * @return an associative array containing the information about provider and course
     */
    public function getImplementorInfo()
    {
        $courseAr = [
            $this->getTitle(),
            $this->getDescription(),
            $this->getName(),
            $this->getCreationDate(),
            $this->getPublishDate(),
            $this->getStartNode(),
            $this->getProviderName(),
            $this->getProviderCountry(),
            $this->getProviderDepartment(),
            $this->getProviderCity(),
            $this->getDescr(),
            $this->getProviderDesc(),
            $this->getProviderEMail(),
            $this->getProviderPhone(),
            $this->getProviderRagsoc(),
            $this->getProviderAddress(),
            $this->getProviderResponsible(),
        ];
        return $courseAr;
    }
}

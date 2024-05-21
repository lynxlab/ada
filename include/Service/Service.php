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
use Lynxlab\ADA\Main\Service\ServiceImplementor;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Service
{
    private $implementors = [];

    private $id_service;
    private $title;
    private $level;
    private $description;
    private $duration;
    private $min_meetings;
    private $max_meetings;
    private $meeting_max_time;

    public static function findServiceFromImplementor($id_course)
    {
        $common_dh = AMACommonDataHandler::getInstance();
        $service_dataHa = $common_dh->getServiceInfoFromCourse($id_course);
        $serviceObj = new Service($service_dataHa);
        return $serviceObj;
    }

    public function __construct($serviceAr)
    {
        $common_dh = AMACommonDataHandler::getInstance();
        $this->id_service = $serviceAr[0];
        /*

    $providersAr = $common_dh->getTesterForService( $this->id_service);

    if (AMADataHandler::isError($providersAr)){
        // ??
        } else {
        // $provider_pointer = $this->getProviderPointer();
        foreach ($providersAr as $providerAr){
        $id_provider = $providerAr[0];
        $implementorsAr = $common_dh->getCoursesForService($this->id_service,$id_provider);
        $implementorId = $implementorsAr[0];
        $provider_dataHa =  $common_dh->getTesterInfoFromId($id_provider);
        $provider_pointer = $provider_dataHa[10];
        $implementorObj = new Service_implementor($implementorId,$provider_pointer);
        //          var_dump($this->implementors);
        $this->implementors[$implementorId] = $implementorObj;
        }
        }

        $providersAr = $common_dh->getTesterInfoFromService( $this->id_service);
        //T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore
        if (AMADataHandler::isError($providersAr)){
        // ??
        } else {
        // $provider_pointer = $this->getProviderPointer();
        foreach ($providersAr as $providerAr){
        $id_provider = $providerAr['id_tester'];
        $implementorsAr = $common_dh->getCoursesForService($this->id_service,$id_provider);
        $implementorId = $implementorsAr[0];
        $provider_pointer = $providerAr['puntatore'];
        $implementorObj = new Service_implementor($implementorId,$provider_pointer);
        $this->implementors[$implementorId] = $implementorObj;
        }
        }
        */

        if (AMADataHandler::isError($serviceAr)) {
            //
        } else {
            $this->title =  $serviceAr[1];
            $this->description = $serviceAr[2];
            $this->level = $serviceAr[3];
            // durata_servizio, min_incontri, max_incontri, durata_max_incontro
            $this->duration =  $serviceAr[4];
            $this->min_meetings =  $serviceAr[5];
            $this->max_meetings =  $serviceAr[6];
            $this->meeting_max_time =  $serviceAr[7] / 60;
        }
    }

    /* Getters */

    public function getTitle()
    {
        return translateFN($this->title);
    }

    public function getDescription()
    {
        return translateFN($this->description);
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function getMinMeetings()
    {
        return $this->min_meetings;
    }

    public function getMaxMeetings()
    {
        return $this->max_meetings;
    }

    public function getMeetingMaxTime()
    {
        return $this->meeting_max_time;
    }

    public function getServiceInfo()
    {
        $serviceAr = [
            $this->getTitle(),
            $this->getDescription(),
            $this->getLevel(),
            $this->getDuration(),
            $this->getMinMeetings(),
            $this->getMaxMeetings(),
            $this->getMeetingMaxTime(),
        ];
        return $serviceAr;
    }

    public function getImplementors()
    {

        $courseAr = [];
        foreach ($this->implementors as $implementorId) {
            $implementorObj = new ServiceImplementor($implementorId, $courseAr, []);
            //          var_dump($this->implementors);
            if (!isset($this->implementors[$implementorId])) {
                $this->implementors[$implementorId] = $implementorObj;
            }
            $courseAr[$implementorId] = $implementorObj;
        }
        return  $courseAr;
    }

    public function getImplementor($implementorId)
    {
        if (!isset($this->implementors[$implementorId])) {
            $courseAr = $this->getImplementors();
            return $courseAr[$implementorId];
        } else {
            return $this->implementors[$implementorId];
        }
    }
}

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

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\HtmlLibrary\GuestHtmlLib;
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

    public static function findServicesToSubscribe($orderBy = 'service', $minLevel = 1, $maxLevel = 5)
    {
        $common_dh = $GLOBALS['common_dh'];
        $callerModule = $GLOBALS['self'];
        $sess_id_user = $_SESSION['sess_id_user'];
        $userObj = $_SESSION['sess_userObj'];



        // filtering on levels
        // $level_ha = Multiport::getServiceMaxLevel($sess_id_user); FIXME: it OUGHT TO be used to filter services

        // version using COMMON

        if (isset($minLevel) and ($minLevel < 5)) {
            $livelloMin = $minLevel;
        } else {
            $livelloMin = 1;
        }
        if (isset($maxLevel) and ($maxLevel > 1)) {
            $livelloMax = $maxLevel;
        } else {
            $livelloMax = 5;
        }


        $clause = "s.livello <= $livelloMax AND s.livello >= $livelloMin ";

        //  ordering
        if ($orderBy == 'service') {
            $service_infoAr = $common_dh->getServices(['s.nome', 't.nazione', 's.livello'], $clause);
        } elseif ($orderBy == 'country') {
            $service_infoAr = $common_dh->getServices(['t.nazione', 't.provincia', 't.nome'], $clause);
        }

        $s = 0;
        $providers_data = [];
        foreach ($service_infoAr as $course_dataHa) {
            //var_dump($course_dataHa);
            $service_implementation_id = $course_dataHa[3];
            $provider_name =  $course_dataHa[5];
            $provider_id =  $course_dataHa[4];
            if (!isset($providers_data[$provider_id])) {
                $provider_dataHa =  $common_dh->getTesterInfoFromId($provider_id);
                $provider_pointer = $provider_dataHa[10];
                $providers_data[$provider_id] = $provider_dataHa;
            } else {
                $provider_pointer = $providers_data[$provider_id][10];
            }
            $provider_dsn = MultiPort::getDSN($provider_pointer);
            if ($provider_dsn != null) {
                // $provider_dataHa = $common_dh->getTesterInfoFromPointer($provider);
                $provider_dh = AMADataHandler::instance($provider_dsn);
                $id_course_instanceAr = $provider_dh->getCourseInstanceForThisStudentAndCourseModel($sess_id_user, $service_implementation_id);
            } else {
                $id_course_instanceAr = null;
            }

            // already subscribed?
            if (
                (AMADataHandler::isError($id_course_instanceAr)) or ($id_course_instanceAr == null)
            ) { // never subscribed
                $id_course_instance = 0;
            } else {
                //var_dump($id_course_instanceAr);
                $id_course_instance = $id_course_instanceAr['istanza_id'];
                /* // FIXME: we have to get the real status AND expiration date for service implementation
         *   $now = time();
         if ($data_fine < $now){
         $stato = ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED;
         } else {
         $stato = ADA_SERVICE_SUBSCRIPTION_STATUS_UNDEFINED;
         }
         $service_infoAr[$s][9] = $stato;
         */
            }
            $service_infoAr[$s][8] = $id_course_instance;
            $s++;
        }

        $optionsAr = [
            'callerModule' => $callerModule,
            'orderBy' => $orderBy,
        ];
        return GuestHtmlLib::displayAllServicesData($userObj, $service_infoAr, $optionsAr);
    }

    public static function findServiceFromImplementor($id_course)
    {
        $common_dh = $GLOBALS['common_dh'];
        $service_dataHa = $common_dh->getServiceInfoFromCourse($id_course);
        $serviceObj = new Service($service_dataHa);
        return $serviceObj;
    }

    public function __construct($serviceAr)
    {
        $common_dh = $GLOBALS['common_dh'];
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
            $implementorObj = new ServiceImplementor($implementorId);
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

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
use Lynxlab\ADA\Main\HtmlLibrary\GuestHtmlLib;

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
        // $level_ha = Multiport::get_service_max_level($sess_id_user); FIXME: it OUGHT TO be used to filter services

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
            $service_infoAr = $common_dh->get_services(['s.nome', 't.nazione', 's.livello'], $clause);
        } elseif ($orderBy == 'country') {
            $service_infoAr = $common_dh->get_services(['t.nazione', 't.provincia', 't.nome'], $clause);
        }

        $s = 0;
        $providers_data = [];
        foreach ($service_infoAr as $course_dataHa) {
            //var_dump($course_dataHa);
            $service_implementation_id = $course_dataHa[3];
            $provider_name =  $course_dataHa[5];
            $provider_id =  $course_dataHa[4];
            if (!isset($providers_data[$provider_id])) {
                $provider_dataHa =  $common_dh->get_tester_info_from_id($provider_id);
                $provider_pointer = $provider_dataHa[10];
                $providers_data[$provider_id] = $provider_dataHa;
            } else {
                $provider_pointer = $providers_data[$provider_id][10];
            }
            $provider_dsn = MultiPort::getDSN($provider_pointer);
            if ($provider_dsn != null) {
                // $provider_dataHa = $common_dh->get_tester_info_from_pointer($provider);
                $provider_dh = AMA_DataHandler::instance($provider_dsn);
                $id_course_instanceAr = $provider_dh->get_course_instance_for_this_student_and_course_model($sess_id_user, $service_implementation_id);
            } else {
                $id_course_instanceAr = null;
            }

            // already subscribed?
            if (
                (AMA_DataHandler::isError($id_course_instanceAr)) or ($id_course_instanceAr == null)
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
        $service_dataHa = $common_dh->get_service_info_from_course($id_course);
        $serviceObj = new Service($service_dataHa);
        return $serviceObj;
    }

    public function __construct($serviceAr)
    {
        $common_dh = $GLOBALS['common_dh'];
        $this->id_service = $serviceAr[0];
        /*

    $providersAr = $common_dh->get_tester_for_service( $this->id_service);

    if (AMA_DataHandler::isError($providersAr)){
        // ??
        } else {
        // $provider_pointer = $this->get_provider_pointer();
        foreach ($providersAr as $providerAr){
        $id_provider = $providerAr[0];
        $implementorsAr = $common_dh->get_courses_for_service($this->id_service,$id_provider);
        $implementorId = $implementorsAr[0];
        $provider_dataHa =  $common_dh->get_tester_info_from_id($id_provider);
        $provider_pointer = $provider_dataHa[10];
        $implementorObj = new Service_implementor($implementorId,$provider_pointer);
        //          var_dump($this->implementors);
        $this->implementors[$implementorId] = $implementorObj;
        }
        }

        $providersAr = $common_dh->get_tester_info_from_service( $this->id_service);
        //T.id_tester,T.nome,T.ragione_sociale,T.indirizzo,T.provincia,T.nazione,T.telefono,T.e_mail,T.responsabile,T.puntatore
        if (AMA_DataHandler::isError($providersAr)){
        // ??
        } else {
        // $provider_pointer = $this->get_provider_pointer();
        foreach ($providersAr as $providerAr){
        $id_provider = $providerAr['id_tester'];
        $implementorsAr = $common_dh->get_courses_for_service($this->id_service,$id_provider);
        $implementorId = $implementorsAr[0];
        $provider_pointer = $providerAr['puntatore'];
        $implementorObj = new Service_implementor($implementorId,$provider_pointer);
        $this->implementors[$implementorId] = $implementorObj;
        }
        }
        */

        if (AMA_DataHandler::isError($serviceAr)) {
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

    public function get_title()
    {
        return translateFN($this->title);
    }

    public function get_description()
    {
        return translateFN($this->description);
    }

    public function get_level()
    {
        return $this->level;
    }

    public function get_duration()
    {
        return $this->duration;
    }

    public function get_min_meetings()
    {
        return $this->min_meetings;
    }

    public function get_max_meetings()
    {
        return $this->max_meetings;
    }

    public function get_meeting_max_time()
    {
        return $this->meeting_max_time;
    }

    public function get_service_info()
    {
        $serviceAr = [
            $this->get_title(),
            $this->get_description(),
            $this->get_level(),
            $this->get_duration(),
            $this->get_min_meetings(),
            $this->get_max_meetings(),
            $this->get_meeting_max_time(),
        ];
        return $serviceAr;
    }

    public function get_implementors()
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

    public function get_implementor($implementorId)
    {
        if (!isset($this->implementors[$implementorId])) {
            $courseAr = $this->get_implementors();
            return $courseAr[$implementorId];
        } else {
            return $this->implementors[$implementorId];
        }
    }
}

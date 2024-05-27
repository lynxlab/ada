<?php

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\HtmlLibrary;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\User\ADAGenericUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Service\Functions\level2stringFN;

class GuestHtmlLib
{
    /*
     * methods used to display all services list
     */

    public static function displayImplementationServiceData(ADAGenericUser $UserObj, $service_infoAr, $optionsAr = [])
    {

        $service_div = CDOMElement::create('div', 'id:service_info');

        $info_provider_thead_data = [$service_infoAr[6]];
        $info_provider_tbody_data = [
        [$service_infoAr[14]],
        [$service_infoAr[15]],
        [$service_infoAr[9]],
        [$service_infoAr[8] . "/" . $service_infoAr[7]],
        [$service_infoAr[13]],
        [$service_infoAr[12]],
        [$service_infoAr[11]],
        ];
        $element_attributes = "";
        $provider_Table = BaseHtmlLib::tableElement($element_attributes, $info_provider_thead_data, $info_provider_tbody_data);

        //   $service_div->addChild($serviceTable);
        $provider_data = $provider_Table->getHtml();

        $thead_data = [
    //    translateFN('Nome'),
  //      translateFN('Paese'),
    //    translateFN('Città'),
    //    translateFN('Livello'),
    //    translateFN('Durata (gg)'),
    //    translateFN('Numero incontri'),
    //    translateFN('Durata incontro (minuti)'),
        translateFN('Informazioni'),
        translateFN('Descrizione dettagliata'),
        translateFN('Fornitore'),
        ];


        //  var_dump($service_infoAr);
        $tbody_dataAr = [];

        $tbody_dataAr[] = [
        //translateFN('Nome')=>$service_infoAr[0],
  //      translateFN('Paese')=>$service_infoAr[7]."/".$service_infoAr[8],
     //   translateFN('Città')=>$service_infoAr[9],
     //   translateFN('Livello')=>level2stringFN($service_infoAr[3]),
     //   translateFN('Durata (gg)')=>$service_infoAr[4],
     //   translateFN('Numero incontri')=>$service_infoAr[5],
     //   translateFN('Durata incontro (minuti)')=>$service_infoAr[6],
        translateFN('Informazioni') => $service_infoAr[1],
        translateFN('Descrizione dettagliata') => $service_infoAr[10],
        translateFN('Fornitore') => $provider_data, //$service_infoAr[6]."<br />".$service_infoAr[7]."/".$service_infoAr[8].$service_infoAr[11]
        ];

        $element_attributes = "class:service_info_tab";
        $serviceTable = BaseHtmlLib::tableElement($element_attributes, $thead_data, $tbody_dataAr);

        $service_div->addChild($serviceTable);
        $service_data = $service_div->getHtml();

        return $service_data;
    }

    public static function displayServiceData(ADAGenericUser $UserObj, $service_infoAr = [], $optionsAr = [])
    {

        $service_div = CDOMElement::create('div', 'id:service_info');

        $label_title = translateFN('Service');
        $label_level = translateFN('Level');
        $label_description = translateFN('Description');
        $label_service_time = translateFN('Open for');
        $label_service_min_meetings = translateFN('Min Meetings');
        $label_service_max_meetings = translateFN('Max meetings');
        $label_service_meeting_max_time = translateFN('Meetings duration');

        $overall_service_data = "";


        //var_dump($service_infoAr);



        if (!AMADataHandler::isError($service_infoAr)) {
            $service_title =  $service_infoAr[0];

            $service_level = level2stringFN($service_infoAr[2]);
            $service_description = $service_infoAr[1];
            // durata_servizio, min_incontri, max_incontri, durata_max_incontro
            $service_time =  $service_infoAr[3] . " " . translateFN("days");
            $service_min_meetings =  $service_infoAr[4];
            $service_max_meetings =  $service_infoAr[5];
            $service_meeting_max_time =  $service_infoAr[6] . " " . translateFN("min");
        } else {
            $service_description = translateFN("Not available");
            $service_level = translateFN("?");
            $service_title =  translateFN("Not available");
        }

        $thead_data = [
          $label_title,
          $label_level,
        //  $label_description,
          $label_service_time,
          $label_service_min_meetings,
          $label_service_max_meetings,
          $label_service_meeting_max_time,

        ];

        $tbody_dataAr[] = [
                      //$label_provider=>$tester_name,

                      $label_title => $service_title,
                      $label_level => $service_level,
                   //   $label_description=>nl2br($service_description),
                      $label_service_time => $service_time,
                      $label_service_min_meetings => $service_min_meetings,
                      $label_service_max_meetings => $service_max_meetings,
                      $label_service_meeting_max_time => $service_meeting_max_time,



                      ];

        $serviceTable = BaseHtmlLib::tableElement('', $thead_data, $tbody_dataAr);

        $service_div->addChild($serviceTable);
        $service_data = $service_div->getHtml();



        return $service_data;
    }
    /*
    static public function displayServiceImplementationData(ADAGenericUser $UserObj, $service_infoAr=array(), $optionsAr=array()){

        $label_title = translateFN('Titolo');
        $label_provider = translateFN('Erogatore');
        $label_provider_country = translateFN('Paese');
        $label_provider_city = translateFN('Città');
        $overall_service_data = "";


    // var_dump($service_infoAr);



        if (!AMADataHandler::isError($service_infoAr)){
            $service_title =  $service_infoAr[0];
                    // provider's infos
            $service_provider_name = $service_infoAr[7];
            $service_provider_country =$service_infoAr[8];
            $service_provider_city = $service_infoAr[9];
          } else {
            $service_title =  translateFN("Servizio non disponibile");

          }

           $row = array(
                          $label_provider=>$service_provider_name,
                          $label_provider_country=>$service_provider_country,
                          $label_provider_city=>$service_provider_city


                          );
          $impl_service_dataList = BaseHtmlLib::plainListElement("",$row);
          $impl_service_data = $impl_service_dataList->getHtml();



        return $impl_service_data;
        }
        */
}

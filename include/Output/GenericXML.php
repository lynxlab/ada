<?php

/**
 * NEW Output classes
 *
 *
 * PHP version >= 5.0
 *
 * @package     ARE
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        output_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Output;

/**
 *
 * Classe generica di output XML
 */
class GenericXML extends Output
{
    //vars:
    public $xmlheader;
    public $xmlbody;
    public $xmlfooter;
    public $error;
    public $errorCode;
    public $static_name;
    public $URL;
    public $idNode;

    //functions:
    public function __construct($portal, $date, $course_title)
    {
        $root_dir =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];

        $this->xmlheader = "<?xml version='1.0'?>
        <?xml-stylesheet type=\"text/xsl\" href=\"$http_root_dir/browsing/ada.xsl\"?>
        <!DOCTYPE MAP SYSTEM \"$http_root_dir/browsing/ada.dtd\">
        <MAP>\n";
        $this->xmlheader .= "<PORTAL>\n$portal\n</PORTAL>\n";
        $this->xmlheader .= "<DOCDATE>\n$date\n</DOCDATE>\n";
        $this->xmlheader .= "<DOCTITLE>\n$course_title\n</DOCTITLE>\n";

        $this->xmlfooter = "</MAP>\n";
    }


    public function fillinFN($dataHa)
    {
        /*  traduzione parziale delle chiavi essenziali */
        $this->xmlbody = "<NODE>\n";
        $this->xmlbody .= "<NODEID>" . $this->idNode . "</NODEID>\n";
        $this->xmlbody .= "<VERSION>" . $dataHa['version'] . "</VERSION>\n";
        $this->xmlbody .= "<NAME>" . $dataHa['title'] . "</NAME>\n";
        $this->xmlbody .= "<COPYRIGHT>" . strip_tags($dataHa['author']) . "</COPYRIGHT>\n";
        $this->xmlbody .= "<KEYWORDS>" . strip_tags($dataHa['keywords']) . "</KEYWORDS>\n";



        /* traduzione completa di tutte le chiavi;
     $this->xmlbody="<NODE>\n";
     foreach ($dataHa as $field=>$data){
           if ($field<>'text'){
              $this->xmlbody.="<".$field.">".$data."</".$field.">";
          }
        }
        */
        $this->xmlbody .= "<TEXT>\n";
        $this->xmlbody .= "<PARAGRAPH><![CDATA[" . $dataHa['text'];
        //     $this->xmlbody.="<PARAGRAPH>".strip_tags($dataHa['text']); ONLY TEXT
        $this->xmlbody .= "]]></PARAGRAPH>\n";
        //    $this->xmlbody.="</PARAGRAPH>\n";

        $this->xmlbody .= "</TEXT>\n";
        /* MEDIA e LINKS
         //if ($dataHa['media']!=translateFN("Nessuno")) {
         $this->xmlbody.="<MEDIA>".$dataHa['media']."</MEDIA>\n";
         //}
         //if ($dataHa['links']!=translateFN("Nessuno")) {
         $this->xmlbody.="<LINKS>".$dataHa['links']."</LINKS>\n";
         //}
         */
        $this->xmlbody .= "</NODE>\n";
    }

    public function outputFN($type)
    {
        // manda effettivamente al browser i dati(dimensioni, testo, ...)

        switch ($type) {
            case 'page':  // standard
            default:
                $data = $this->xmlheader;
                $data .= $this->xmlbody;
                $data .= $this->xmlfooter;
                print $data;
                break;
            case 'dimension':
                $data = $this->xmlheader;
                $data .= $this->xmlbody;
                $data .= $this->xmlfooter;
                $dim_data = strlen($data);
                print $dim_data;
                break;
            case 'source': // debugging purpose only
                $data = $this->xmlheader;
                $data .= $this->xmlbody;
                $data .= $this->xmlfooter;
                $source_data = htmlentities($data, ENT_COMPAT | ENT_HTML401, ADA_CHARSET);
                print $source_data;
                break;
            case 'error': // debugging purpose only
                $data = $this->error;
                print $data;
                break;
            case 'file': // useful for caching pages
                $data = $this->xmlheader;
                $data .= $this->xmlbody;
                $data .= $this->xmlfooter;
                $fp = fopen($this->static_name, "w");
                $result = fwrite($fp, $data);
                fclose($fp);
                break;
        }
    }
}

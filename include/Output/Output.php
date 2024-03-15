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
 * Generic output class
 */
class Output
{
    //vars:
    public $interface;
    public $content;
    public $static_name;
    public $error;
    public $errorCode;

    public function __construct()
    {
    }

    /**
     * manda effettivamente al browser la pagina, oppure solo i dati (dimensioni, testo, ...)
     *
     * @param string $type
     * @return void
     */
    public function outputFN($type)
    {

        switch ($type) {
            case 'dimension':
                $data = $this->content;
                $dim_data = strlen($data);
                print $dim_data;
                break;

            case 'text':
                $data = $this->content;
                $text_data = strip_tags($data);
                print $text_data;
                break;

            case 'source': // debugging purpose only
                $data = $this->content;
                $source_data = htmlentities($data, ENT_COMPAT | ENT_HTML401, ADA_CHARSET);
                print $source_data;
                break;

            case 'error': // debugging purpose only
                $data = $this->error . $this->errorCode;
                print $data;
                break;

            case 'file': // useful for caching pages
                $data = $this->content;
                $fp = fopen($this->static_name, "w");
                $result = fwrite($fp, $data);
                fclose($fp);
                break;

            case 'page':  // standard
            default:
                $data = $this->content;
                print $data;
                break;
        }
    }
}

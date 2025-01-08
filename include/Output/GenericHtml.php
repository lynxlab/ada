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

use Dompdf\Dompdf;
use Dompdf\Options;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Output\Output;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Classe generica di stampa su browser
 */
class GenericHtml extends Output
{
    //vars:

    public $template;
    public $CSS_filename;
    public $family;
    public $htmlheader;
    public $htmlbody;
    public $htmlfooter;
    public $error;
    public $errorCode;
    public $replace_field_code;
    public $full_static_filename;
    public $external_module = false;
    public $replace_microtemplate_field_code;
    public $module_dir;
    public $JS_filename;
    public $tplfield;
    public $orientation;
    public $returnasstring;
    public $outputfile;
    public $forcedownload;


    public function __construct($template, $title, $meta_keywords = "")
    {
        $keywords  = "ADA, Lynx, e-learning, Elearning, ";
        $keywords .= ADA_METAKEYWORDS;
        $description = ADA_METADESCRIPTION;
        $this->template = $template;
        $template_name = basename($template);
        $this->htmlheader = "
                 <!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
                 <html>
                 <head>
                 <meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . ADA_CHARSET . "\">
                 <meta name=\"powered_by\" content=\"ADA v." . ADA_VERSION . "\">
        <meta name=\"templates\" content=\"$template_name\">
        <meta name=\"class\" content=\"generic HTML\">
        <meta name=\"description\" content=\"$description\">
        <meta name=\"keywords\" content=\"$keywords, $meta_keywords\">
        <!-- Stile -->\n";
        $this->htmlheader .= "<title>\n$title\n</title>\n";
        $this->htmlheader .= "</head>\n";
        $this->htmlbody = "<body>\n";
        $this->htmlfooter = "\n</body>\n</html>";
        $this->replace_field_code = $GLOBALS['replace_field_code'];
        $this->replace_microtemplate_field_code = $GLOBALS['replace_microtemplate_field_code'];
    }

    public function fillinTemplateFN($dataHa)
    {
        /* Riempie i campi del template

        Il template e' HTML standard con campi,
        Per default i campi sono commenti in stile dreamWeaver 4)
        <!-- #BeginEditable "doctitle" -->
        <!-- #EndEditable -->
        ma il formato puo' essere cambiato dal file di configurazione
        ed e' contenuto nella variabile globale $replace_field_code

        I dati passati sono in forma di array associativo field=>data
        */

        $root_dir = $GLOBALS['root_dir'];
        $tpl_fileextension =  $GLOBALS['tpl_fileextension'];
        if (!isset($this->replace_field_code) or  empty($this->replace_field_code)) {
            $this->replace_field_code = "<!-- #BeginEditable \"%field_name%\" -->([a-zA-Z0-9_\t;&\n ])*<!-- #EndEditable -->"; // default value
        }
        $template = $this->template;

        if (!strstr($template, (string) $tpl_fileextension)) {
            $template .= $tpl_fileextension;
        }
        if (!file_exists($template)) {
            $template = $root_dir . "/layout/" . ADA_TEMPLATE_FAMILY . "/templates/default" . $tpl_fileextension;
        }
        $tpl = '';
        $fid = fopen($template, 'r');

        while ($row = fread($fid, 4096)) {
            $tpl .= $row;
        }



        $bodytpl = strstr($tpl, '<body');
        $n = strpos($bodytpl, "</body>");
        $tpl = substr($bodytpl, strpos($bodytpl, ">", 5) + 1, $n - strpos($bodytpl, ">", 5) - 1);
        $this->htmlbody .= $tpl;
        if (USE_MICROTEMPLATES) {
            $tpl = $this->includeMicrotemplates();
        }
        // $tpl = $this->include_microtemplates_tree();
        /**
         * @author giorgio 08/mag/2015
         * added HTTP_ROOT_DIR as template_field 'constant'
         */
        $dataHa['HTTP_ROOT_DIR'] = HTTP_ROOT_DIR;

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CoreEvent::class,
                    'eventName' => CoreEvent::PREFILLINTEMPLATE,
                    'eventPrefix' => basename($_SERVER['SCRIPT_FILENAME']),
                ],
                basename($_SERVER['SCRIPT_FILENAME']),
                [
                    'dataHa' => $dataHa,
                ]
            );
            foreach ($event->getArguments() as $key => $val) {
                ${$key} = $val;
            }
        }

        foreach ($dataHa as $field => $data) {
            $ereg = str_replace('%field_name%', $field, $this->replace_field_code);
            $preg = str_replace('%field_name%', $field, preg_quote($this->replace_field_code, '/'));
            //$replace_string = "<!-- #BeginEditable \"$field\" -->([a-zA-Z0-9_\t;&\n ])*<!-- #EndEditable -->";

            if (0!== preg_match('/' . $preg . '/i', $tpl)) {
                if (gettype($data) == 'array') {
                    $tObj = new Table();
                    $tObj->setTable($data);
                    $tabled_data = $tObj->getTable();
                    if (ADA_STATIC_TEMPLATE_FIELD) {
                        $tpl = str_replace($ereg, $tabled_data, $tpl); //faster !!!
                    } else {
                        $tpl = preg_replace('/' . $preg . '/i', $tabled_data, $tpl);
                        //        $tpl = eregi_replace($ereg,$tabled_data,$tpl);
                    }
                } else {
                    // simple data type
                    if (ADA_STATIC_TEMPLATE_FIELD) {
                        $tpl = str_replace($ereg, $data ?? '', $tpl); //faster !!!
                    } else {
                        $tpl = preg_replace('/' . $preg . '/i', $data, $tpl);
                        //        $tpl = eregi_replace($ereg,$data,$tpl);
                    }
                }
            }
        }

        // removing extra template fields that don't match
        $ereg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", $this->replace_field_code);
        $preg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", preg_quote($this->replace_field_code, '/'));
        $tpl = preg_replace('/' . $preg . '/i', "<!-- template_field_removed -->", $tpl);
        //  $tpl = eregi_replace($ereg,"<!-- template_field_removed -->",$tpl);

        /*
         * traduzione dei template
         * vito, 15 ottobre 2008: parse del template per tradurre il testo contenuto nella lingua dell'utente
         */
        // ottiene tutto il testo marcato per la traduzione
        $matches = [];
        preg_match_all('/<i18n>(.*)<\/i18n>/', $tpl, $matches);
        // costruisce l'array contenente il testo tradotto
        $pattern = [];
        $translated_text = [];
        foreach ($matches[1] as $match => $text) {
            $quoted_text = preg_quote($text, '/');
            $pattern[$match] = "/<i18n>$quoted_text<\/i18n>/";
            $translated_text[$match] = translateFN($text);
        }
        // sostituisce nel template il testo tradotto al testo originale
        $tpl = preg_replace($pattern, $translated_text, $tpl);
        /*
         * fine della traduzione
         */

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CoreEvent::class,
                    'eventName' => CoreEvent::POSTFILLINTEMPLATE,
                    'eventPrefix' => basename($_SERVER['SCRIPT_FILENAME']),
                ],
                basename($_SERVER['SCRIPT_FILENAME']),
                [
                    'html' => $tpl,
                ]
            );
            foreach ($event->getArguments() as $key => $val) {
                ${$key} = $val;
            }
        }

        $this->htmlbody = $tpl;
    }

    public function includeMicrotemplates()
    {
        // trying to include microtemplates (from files)
        // parses template row by row
        $root_dir = $GLOBALS['root_dir'];
        $tpl_fileextension =  $GLOBALS['tpl_fileextension'];
        $tpl = $this->htmlbody;
        $module_dir = $this->module_dir;
        $preg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", preg_quote($this->replace_microtemplate_field_code, '/'));
        $tpl_ar = explode("\n", $tpl);
        $k = 0;
        foreach ($tpl_ar as $tpl_row) {
            //echo $k.$tpl_row;
            $k++;
            if (preg_match("/$preg/", $tpl_row, $regs)) {
                $microtpl_name = $regs[1];

                // valerio: 26/11/2012 inizio modifica microtemplate per moduli esterni
                $external_microtpl_filename = $root_dir . "/$module_dir/layout/" . $this->family . "/templates/" . $microtpl_name . $tpl_fileextension;
                if ($this->external_module && file_exists($external_microtpl_filename)) {
                    $microtpl_filename = $external_microtpl_filename;
                } else {
                    // steve 26/03/09: try to find microtemplates navigating the folders tree
                    // layout/claire/templates/browsing/header.tpl ?
                    $microtpl_filename = $root_dir . "/layout/" . $this->family . "/templates/$module_dir/" . $microtpl_name . $tpl_fileextension;
                }

                // giorgio: 12/ago/2013 try to load provider microtemplate if it's singleprovider environment
                if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
                    $provider_microtpl_filename = $root_dir . "/clients/" . $GLOBALS['user_provider'] . "/layout/" . $this->family . "/templates/$module_dir/" . $microtpl_name . $tpl_fileextension;

                    if (file_exists($provider_microtpl_filename)) {
                        $microtpl_filename = $provider_microtpl_filename;
                    } else {
                        $clientmicrotpl_filename = $root_dir . "/clients/" . $GLOBALS['user_provider'] . "/layout/" . $this->family . "/templates/" . $microtpl_name . $tpl_fileextension;
                        if (file_exists($clientmicrotpl_filename)) {
                            $microtpl_filename = $clientmicrotpl_filename;
                        }
                    }
                }
                // giorgio: 12/ago/2013 end

                // fine modifica moduli esterni
                if (file_exists($microtpl_filename)) {
                    $microtpl_code = file_get_contents($microtpl_filename);
                } else {  // layout/claire/templates/header.tpl ?
                    // $microtpl_filename = $root_dir."/templates/".$this->family."/".$microtpl_name.$tpl_fileextension;
                    $microtpl_filename = $root_dir . "/layout/" . $this->family . "/templates/" . $microtpl_name . $tpl_fileextension;
                    if (file_exists($microtpl_filename)) {
                        $microtpl_code = file_get_contents($microtpl_filename);
                    } else {
                        $microtpl_code = "<!-- not found at address: $microtpl_filename -->"; // raises an error?
                    }
                }
                $preg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", preg_quote($this->replace_microtemplate_field_code, '/'));
                $tpl_row = preg_replace('/' . $preg . '/', $microtpl_code, $tpl_row);
                //         $tpl_row = ereg_replace($ereg,$microtpl_code,$tpl_row);
            }
            $tpl_new_ar[] = $tpl_row;
        }
        $tpl = implode("\n", $tpl_new_ar);
        return $tpl;
    }
    public function includeMicrotemplatesTree()
    {
        // mod  steve 26/03/09:
        // this version tries to find microtemplates navigating the folders tree
        //  allowing to have a single header, footer etc placed in /main/$family or main/default
        // trying to include microtemplates (from files)
        // parses template row by row
        $root_dir = $GLOBALS['root_dir'];
        $tpl_fileextension =  $GLOBALS['tpl_fileextension'];
        $tpl = $this->htmlbody;
        $module_dir = $this->module_dir;
        $preg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", preg_quote($this->replace_microtemplate_field_code, '/'));
        $tpl_ar = explode("\n", $tpl);
        $k = 0;
        foreach ($tpl_ar as $tpl_row) {
            //echo $k.$tpl_row;
            $k++;
            if (preg_match("/$preg/", $tpl_row, $regs)) {
                $microtpl_name = $regs[1];

                // valerio: 26/11/2012 inizio modifica microtemplate per moduli esterni
                $external_microtpl_filename = $root_dir . "/$module_dir/layout/" . $this->family . "/templates/" . $microtpl_name . $tpl_fileextension;
                if ($this->external_module && file_exists($external_microtpl_filename)) {
                    $microtpl_filename = $external_microtpl_filename;
                } else {
                    $microtpl_filename = $root_dir . "/templates/$module_dir/" . $this->family . "/" . $microtpl_name . $tpl_fileextension;
                }
                // fine modifica moduli esterni
                // layout/claire/browsing/header.tpl ?
                $microtpl_filename = $root_dir . "/templates/$module_dir/" . $this->family . "/" . $microtpl_name . $tpl_fileextension;
                if (file_exists($microtpl_filename)) {
                    $microtpl_code = file_get_contents($microtpl_filename);
                } else {
                    // main/claire/header.tpl ?
                    $microtpl_filename = $root_dir . "/templates/main/" . $this->family . "/" . $microtpl_name . $tpl_fileextension;
                    if (file_exists($microtpl_filename)) {
                        $microtpl_code = file_get_contents($microtpl_filename);
                    } else {
                        // main/default/header.tpl ?
                        $microtpl_filename = $root_dir . "/templates/main/default/" . $microtpl_name . $tpl_fileextension;
                        if (file_exists($microtpl_filename)) {
                            $microtpl_code = file_get_contents($microtpl_filename);
                        } else {
                            $microtpl_code = ""; // raises an error?
                        }
                    }
                }
                $preg = str_replace('%field_name%', "([a-zA-Z0-9_]+)", preg_quote($this->replace_microtemplate_field_code, '/'));
                $tpl_row = preg_replace('/' . $preg . '/', $microtpl_code, $tpl_row);
                //         $tpl_row = ereg_replace($ereg,$microtpl_code,$tpl_row);
            }
            $tpl_new_ar[] = $tpl_row;
        }
        $tpl = implode("\n", $tpl_new_ar);
        return $tpl;
    }

    public function verifyTemplateFN($dataHa)
    {
        global $root_dir;

        /* verify if template exists and if there is a  match among number and names of fields
         case 0: ok
         case 1: file doesn't exist   (very bad!)
         *case 2: more field in template than in data array
         (some field are left empty, we want to filter data from code side)
         case 3: more field in data array than in template
         (some data get lost, we want to filter data  from interface side)


         */

        //$replace_field_code = $GLOBALS['replace_field_code'];
        $replace_field_code = $this->replace_field_code;
        //$template_family = $GLOBALS['template_family'];
        $tpl_fileextension = $GLOBALS['tpl_fileextension'];

        $template_family = $this->family;
        if (!$template_family) {
            if (defined('ADA_TEMPLATE_FAMILY')) {
                $template_family = ADA_TEMPLATE_FAMILY;
            } else {
                $template_family = "default";
            }
        } else {
            $template_family = "default";
        }

        if (!isset($replace_field_code) or  empty($replace_field_code)) {
            $replace_field_code = "<!-- #BeginEditable \"%field_name%\" -->([a-zA-Z0-9_\t;&\n ])*<!-- #EndEditable -->";
        }


        $template = $this->template;
        if (!strstr($template, (string) $tpl_fileextension)) {
            $template .= $tpl_fileextension;   // add extension
        }
        if (!file_exists($template)) {
            $template = $root_dir . "/templates/main/" . $template_family . "/default" . $tpl_fileextension;
        }
        if (file_exists($template)) {
            $tpl = '';
            $fid = fopen($template, 'r');
            while ($row = fread($fid, 4096)) {
                $tpl .= $row;
            }

            $tplOk = [];
            foreach ($dataHa as $field => $data) {
                $ereg = str_replace('%field_name%', $field, $replace_field_code);
                $preg = str_replace('%field_name%', $field, preg_quote($replace_field_code, '/'));
                //$ereg = "<!-- #BeginEditable \"$field\" -->([a-zA-Z0-9_\t;&\n ])*<!-- #EndEditable -->";
                if (ADA_STATIC_TEMPLATE_FIELD) {
                    $tplOk[$field] = strpos($ereg, $tpl); //faster !!!
                } else {
                    $tplOk[$field] = preg_match("/$preg/", $tpl);
                }
            }
        } else {
            $this->error = translateFN("Il template non esiste.");
            $this->errorCode = '1';
        }

        $this->tplfield = $tplOk;
        $totalTplFields = count($tplOk);
        $totalDataFields = count($dataHa);
        $matching = ($totalDataFields - $totalTplFields);
        if ($matching > 0) {
            $this->error = translateFN("I campi del template non sono sufficienti.");
            $this->errorCode = '3';
        } elseif ($matching < 0) {
            $this->error = translateFN("Non tutti i campi del template sono stati riempiti.");
            $this->errorCode = '2';
        } else {
            $this->error = '';
            $this->errorCode = 0;
        }
    }

    public function ignoreTemplateFN($dataHa)
    {
        /*
         ignora il template e restituisce solo il contenuto dei campi
         i dati passati sono in forma di array associativo field=>data

         */

        $start_separator = "<br>";  // or else <p>
        $end_separator = "";    // </p>
        $tpl = '';

        foreach ($dataHa as $field => $data) {
            if (gettype($data) == 'array') {
                $tObj = new Table();
                $tObj->setTable($data);
                $tabled_data = $tObj->getTable();
                $tpl .= $start_separator . $tabled_data . $end_separator;
            } else {
                $tpl .= $start_separator . $data . $end_separator;
            }
        }

        $this->htmlbody .= $tpl;
    }

    public function applyStyleFN($stylesheetpath = '')
    {
        $this->doApplyCSSFN($stylesheetpath);
        $this->doApplyJSFN($stylesheetpath);
    }

    /**
     * @deprecated use apply_styleFN instead
     * @param $stylesheetpath
     */
    public function applyCSSFN($stylesheetpath = "")
    {
        // wrapper for applyCSS and apply_JS
        $this->doApplyCSSFN($stylesheetpath);
        $this->doApplyJSFN($stylesheetpath);
    }


    private function doApplyJSFN($jspath = "")
    {
        // inserting js link
        $http_root_dir = $GLOBALS['http_root_dir'];
        $root_dir = $GLOBALS['root_dir'];
        /*
        $template_family = $this->family;
        if (!$template_family){
          if (defined('ADA_TEMPLATE_FAMILY')){
          $template_family = ADA_TEMPLATE_FAMILY;
          }
          else {
          $template_family = "default";
          }
        }
        else {
          $template_family = "default";
        }
  */
        $template_family = 'standard';

        if (empty($jspath)) {
            if (!isset($this->module_dir)) {
                //$jspath = "js/main/$template_family/";
                $jspath = 'js/main/';
            } else {
                $module_dir = $this->module_dir;
                //$jspath = "../js/$module_dir/$template_family/";
                $jspath = "../js/$module_dir/";
            }
        }

        $jsAr = array_unique(explode(";", $this->JS_filename));
        array_unshift($jsAr, ROOT_DIR . "/js/include/load_js.js");
        $html_js_code = "<noscript>" . translateFN("Questo browser non supporta Javascript") . "</noscript>\n";
        /*
         * vito, 6 ottobre 2008: import PHP defines from ada_config.php as javascript variables.
         */
        if (false !== stristr($this->JS_filename, 'install.js')) {
            /**
             * If installing ADA, composer dependencies are not installed yet.
             * Load jquery and jquery-migrate from a CDN.
             */
            $html_js_code .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha512-bnIvzh6FU75ZKxp0GXLH9bewza/OIw6dLVh9ICg0gogclmYGguQJWl8U30WpbsGTqbIiAwxTsbe76DErLq5EDQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
            $html_js_code .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.4.1/jquery-migrate.min.js" integrity="sha512-KgffulL3mxrOsDicgQWA11O6q6oKeWcV00VxgfJw4TcM8XRQT8Df9EsrYxDf7tpVpfl3qcYD96BpyPvA4d1FDQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
        } else {
            $html_js_code .= "<script type=\"text/javascript\" src=\"$http_root_dir/include/PHPjavascript.php\"></script>";
        }

        foreach ($jsAr as $javascript) {
            if (!empty($javascript)) {
                if (!file_exists($javascript)) {
                    if (!strstr($javascript, '.js')) {
                        $javascript =  $javascript . '.js'; // if there is no extension, we add it
                    }
                    if (!stristr($javascript, 'js/')) {
                        $javascript =  $jspath . $javascript; // if there is no path, we add it
                    }
                }

                if (file_exists($javascript)) {
                    // giorgio: 28/dic/2020 try to load provider js if it's singleprovider environment
                    if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
                        $clientJavascript = str_replace($root_dir, $root_dir . DIRECTORY_SEPARATOR . 'clients' . DIRECTORY_SEPARATOR . $GLOBALS['user_provider'], $javascript);
                        if (is_readable($clientJavascript)) {
                            $javascript = $clientJavascript;
                        }
                    }
                    $jsFileTS = filemtime($javascript);
                    $javascript = str_replace($root_dir, $http_root_dir, $javascript);
                    $html_js_code .= "<script type=\"text/javascript\" src=\"$javascript?ts=$jsFileTS\"></script>\n";
                }
            }
        }

        $this->htmlheader = str_replace('<!-- Javascript -->', $html_js_code, $this->htmlheader);
    }

    private function doApplyCSSFN($stylesheetpath = "")
    {

        // inserting style sheet link
        $http_root_dir =   $GLOBALS['http_root_dir'];
        $root_dir =   $GLOBALS['root_dir'];

        $template_family = $this->family;
        if (!$template_family) {
            if (defined('ADA_TEMPLATE_FAMILY')) {
                $template_family = ADA_TEMPLATE_FAMILY;
            } else {
                $template_family = "default";
            }
        }/* else {
        $template_family = "default";
      }*/

        /**
         * @author giorgio 04/apr/2014
         *
         * removed the above else to have $template_family
         * not pointing to 'default' that does not exists
         * anymore, and it will point to ADA_TEMPLATE_FAMILY
         */

        if (empty($stylesheetpath)) {
            if (!isset($this->module_dir)) {
                $stylesheetpath = ROOT_DIR . "/layout/$template_family/css/main/";
            } else {
                $module_dir = $this->module_dir;
                if ($module_dir == "main") {
                    $stylesheetpath = ROOT_DIR . "/layout/../$template_family/css/main/";
                } else {
                    $stylesheetpath = ROOT_DIR . "/layout/$template_family/css/$module_dir/";
                }
            }
        }

        $stylesheetAr = explode(";", $this->CSS_filename);
        $html_css_code = "";
        foreach ($stylesheetAr as $stylesheet) {
            if (!empty($stylesheet)) {
                if (!file_exists($stylesheet)) {
                    if (!strstr($stylesheet, '.css')) {
                        $stylesheet =  $stylesheet . '.css'; // if there is no extension, we add it
                    }

                    if (!stristr($stylesheet, 'css/')) {
                        $stylesheet =  $stylesheetpath . $stylesheet; // if there is no path, we add it
                    }
                }

                if (file_exists($stylesheet)) {
                    // this is for standard browsers
                    $stylesheet = str_replace($root_dir, $http_root_dir, $stylesheet);
                    $html_css_code .= "<link rel=\"stylesheet\" href=\"$stylesheet\" type=\"text/css\" media=\"screen,print\">\n";
                }


                /* steve 31/03/09
                 *
                 * add alternate CSS for non standard browsers, namely IE 6 to 9 and...*/

                for ($ie_version = 6; $ie_version <= 9; $ie_version++) {
                    $cond_com_begin = "\n<!--[if IE " . $ie_version . "]>\n";
                    $cond_com_end = "<![endif]-->\n";

                    //  if there is the extension we strip it off
                    if (strstr($stylesheet, '.css')) {
                        $stylesheet_name = substr($stylesheet, 0, -4);
                        $ie_stylesheet =  $stylesheet_name . "_ie" . $ie_version;
                    } else {
                        $ie_stylesheet =  $stylesheet . "_ie" . $ie_version;
                    }
                    $ie_stylesheet =  $ie_stylesheet . ".css";
                    // path
                    if (!stristr($ie_stylesheet, 'css/')) {
                        $ie_stylesheet =  $stylesheetpath . $ie_stylesheet; // if there is no path, we add it
                    }

                    if (file_exists($ie_stylesheet)) {
                        $ie_stylesheet = str_replace($root_dir, $http_root_dir, $ie_stylesheet);
                        $html_css_code .= $cond_com_begin . "<link rel=\"stylesheet\" href=\"$ie_stylesheet\" type=\"text/css\" media=\"screen,print\">\n" . $cond_com_end;
                    }
                }
                /* end mod  */
            }
        }

        /**
         * @author giorgio 03/apr/2014
         * Look for a print.css that will be used for print media
         * and will be one for each module_dir (i.e. browsing/print.css, switcher/print.css)
         * plus a global print.css that must be put at css root level
         */
        $lookFor = 'print.css';
        /**
         * Look for the print.css file in :
         *
         *  $stylesheetpath . '../' that is css root
         *  $stylesheetpath . ''  that is module's own dir
         *
         * This way module's own print.css will be the
         * last loaded one and can overwrite properly
         */
        foreach (['../', ''] as $subdir) {
            $fileName = $stylesheetpath . $subdir . $lookFor;
            if (file_exists($fileName)) {
                $fileTS = filemtime($fileName);
                $fileName = str_replace($root_dir, $http_root_dir, $fileName);
                $html_css_code .= "<link rel=\"stylesheet\" href=\"$fileName?ts=$fileTS\" type=\"text/css\" media=\"print\">\n";
            }
        }

        /*
         * sara 24/nov/2014
         * Look for the print.css file in external modules (newsletter, test ecc..)
         */
        if ($this->external_module) {
            $stylesheetpath = ROOT_DIR . '/' . $this->module_dir . '/layout/';
            foreach (['', $template_family . '/css/'] as $subdir) {
                $fileName = $stylesheetpath . $subdir . $lookFor;
                if (file_exists($fileName)) {
                    $fileTS = filemtime($fileName);
                    $fileName = str_replace($root_dir, $http_root_dir, $fileName);
                    $html_css_code .= "<link rel=\"stylesheet\" href=\"$fileName?ts=$fileTS\" type=\"text/css\" media=\"print\">\n";
                }
            }
        }

        /**
         * end @author giorgio 03/apr/2014
         */

        $this->htmlheader = str_replace('<!-- Stile -->', $html_css_code, $this->htmlheader);
    }


    public function resetImgSrcFN($path, $family = "")
    {
        // we have to substitute  src="img/pippo.png" with src="templates/browsing/default/img/pippo.png"
        $http_root_dir =   $GLOBALS['http_root_dir'];
        $root_dir =   $GLOBALS['root_dir'];


        $module_dir = $this->module_dir;

        if (empty($module_dir)) {
            $module_dir = "main";
        }

        if ($module_dir == "main") {
            $rel_path = "";
        } else {
            $rel_path = "../";
        }



        //$rel_path = $root_dir."/";
        if (!isset($family) or $family == "") {
            if (isset($this->family) and ($this->family <> "")) {
                $family = $this->family;
            } else {
                if (defined('ADA_TEMPLATE_FAMILY')) {
                    $family = ADA_TEMPLATE_FAMILY;
                } else {
                    $family = "default";
                }
            }
        }

        //valerio 17/10/2012 10:00
        $newpath = $http_root_dir . '/layout/' . $family;

        $this->htmlbody = str_replace('src="img/', 'src="' . $newpath . '/img/', $this->htmlbody);
        $this->htmlbody = str_replace("src='img/", "src='" . $newpath . "/img/", $this->htmlbody);
        $this->htmlbody = str_replace('background="img/', 'background="' . $newpath . '/img/', $this->htmlbody);
        $this->htmlbody = str_replace("background='img/", "background='" . $newpath . "/img/", $this->htmlbody);
        //$this->htmlbody .= '<!-- PATH TO IMAGES: '.$newpath.'/img/-->';
    }


    public function outputFN($type)
    {
        // manda effettivamente al browser la pagina   oppure solo i dati (dimensioni, testo, ...))

        switch ($type) {
            case 'page':  // standard
            default:
                if (ModuleLoaderHelper::isLoaded('MODULES_EVENTDISPATCHER')) {
                    $event = ADAEventDispatcher::buildEventAndDispatch(
                        [
                            'eventClass' => CoreEvent::class,
                            'eventName' => CoreEvent::HTMLOUTPUT,
                            'eventPrefix' => basename($_SERVER['SCRIPT_FILENAME']),
                        ],
                        basename($_SERVER['SCRIPT_FILENAME']),
                        [
                            'htmlheader' => $this->htmlheader,
                            'htmlbody' => $this->htmlbody,
                            'htmlfooter' => $this->htmlfooter,
                        ]
                    );
                    foreach ($event->getArguments() as $key => $val) {
                        $this->{$key} = $val;
                    }
                }
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                print $data;
                break;
            case 'dimension':
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                $dim_data = strlen($data);
                print $dim_data;
                break;
            case 'text':
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                $text_data = strip_tags($data);
                print $text_data;
                break;
            case 'source': // debugging purpose only
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                $source_data = htmlentities($data, ENT_COMPAT | ENT_HTML401, ADA_CHARSET);
                print $source_data;
                break;
            case 'error': // debugging purpose only
                $data = $this->error;
                print $data;
                break;
            case 'file': // useful for caching pages
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                $fp = fopen($this->full_static_filename, "w");
                $result = fwrite($fp, $data);
                fclose($fp);
                break;
            case 'pdf':
                $data = $this->htmlheader;
                $data .= $this->htmlbody;
                $data .= $this->htmlfooter;
                // make dompf tmp font dir if needed
                if (!is_dir(ADA_UPLOAD_PATH . 'tmp/dompdf')) {
                    $oldmask = umask(0);
                    mkdir(ADA_UPLOAD_PATH . 'tmp/dompdf', 0775, true);
                    umask($oldmask);
                }

                $dompdf_options = new Options([
                    // Rendering
                    "defaultMediaType"       => 'print',
                    "defaultPaperSize"       => 'A4',
                    "fontDir"           => ADA_UPLOAD_PATH . 'tmp/dompdf',
                    "fontCache"         => ADA_UPLOAD_PATH . 'tmp/dompdf',
                    "tempDir"           => ADA_UPLOAD_PATH . 'tmp/dompdf',
                    // Features
                    "isPhpEnabled"               => true,
                    "isRemoteEnabled"            => true,
                    "isJavascriptEnabled"        => true,
                    "isHtml5ParserEnabled"      => false,
                    "isFontSubsettingEnabled"   => false,
                    "httpContext" => ['ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer'       => false,
                        'verify_depth'      => 0,
                    ]],
                ]);

                $dompdf = new Dompdf($dompdf_options);
                $dompdf->setPaper('A4', $this->orientation);
                $dompdf->loadHtml($data);
                $dompdf->render();

                if ($this->returnasstring) {
                    return $dompdf->output();
                } else {
                    $dompdf->stream($this->outputfile . '.pdf', ['Attachment' => $this->forcedownload]);
                    die();
                }
                break;
        }
    }

    public function printPageFN($node_data, $template, $imgpath, $stylesheetpath, $use_template = 1)
    {
        if ($use_template) {
            $this->template =  $template;
            $this->verifyTemplateFN($node_data);
            if (!empty($this->error)) {
                // echo $this->errorCode;
                switch ($this->errorCode) {
                    case 1: //template doesn't exist !
                        $this->ignoreTemplateFN($node_data);
                        break;
                    case 2: // some template's fields are empty: ok
                        $this->fillinTemplateFN($node_data);
                        break;
                    case 3: //template's fields don't suffice: ok
                        $this->fillinTemplateFN($node_data);
                        break;
                }
                $this->applyStyleFN($stylesheetpath);
            } else {
                $this->fillinTemplateFN($node_data);
            }
        } else {
            $this->ignoreTemplateFN($node_data);
        }
        $this->resetImgSrcFN($imgpath);
        $this->outputFN('page');
    }
}

<?php

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        utilities
 * @version     0.1
 */

namespace Lynxlab\ADA\Main;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

use function readdir as phpreaddir;

class Utilities
{
    public static function mydebug($line, $file, $vars)
    {
        $debug = $GLOBALS['debug'];
        if ($debug) {
            print "<i>Debugging line $line of script $file</i>.<br>";
            $type = gettype($vars);
            switch ($type) {
                case 'array':
                    foreach ($vars as $k => $v) {
                        print "$k: $v<br>";
                    }
                    break;
                case 'object':
                    static::printVars($vars);
                    break;
                default:
                    print($vars);
            }
        }
    }

    ///////////////////////////////
    /*
 time and date functions
 */

    /**    Returns the offset from the origin timezone to the remote timezone, in seconds.
     *    @param $remote_tz
     *    @param $origin_tz If null the servers current timezone is used as the origin.
     *    @return int
     */
    public static function getTimezoneOffset($remote_tz, $origin_tz = null)
    {
        /*
 *
    switch ($remote_tz) {
        case "Europe/Roma":
            $offset = 0;
            break;
        case "Europe/Madrid":
            $offset = 0;
            break;
        case "Europe/Bucharest":
            $offset = -3600;
            break;
        case "Europe/Sofia":
            $offset = -3600;
            break;
        case "Europe/London":
            $offset = 3600;
            break;
    }

/*
 * NECESSARIO PHP 5.2.X
 *
 */
        if ($origin_tz === null) {
            $origin_tz = SERVER_TIMEZONE;
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $remote_dtz->getOffset($remote_dt) - $origin_dtz->getOffset($origin_dt);

        return $offset;
    }

    public static function todayDateFN()
    {
        $now = time();
        return static::ts2dFN($now);
    }

    public static function todayTimeFN()
    {
        $now = time();
        return static::ts2tmFN($now);
    }

    public static function ts2dFN($timestamp = "")
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }

        $dataformattata  = (new DateTimeImmutable())->setTimestamp($timestamp)->format(str_replace('%', '', ADA_DATE_FORMAT));
        /*
  $data = getdate($timestamp);
  $dataformattata = $data['mday']."/".$data['mon']."/".$data['year'];
  */
        return $dataformattata;
    }

    public static function ts2tmFN($timestamp = "")
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        //$data = getdate($timestamp);
        $dataformattata = date("H:i:s", $timestamp);
        //$dataformattata = $data['hours'].":".$data['minutes'].":".$data['seconds'];
        return $dataformattata;
    }

    public static function sumDateTimeFN($arraydate)
    {
        // array date is dd/mm/yy,hh:mm:ss
        // $date = dt2tsFN($arraydate[0]);
        $date = $arraydate[0];
        $time = $arraydate[1];

        // Data
        $date_ar = explode("/", $date);
        if (count($date_ar) < 3) {
            return 0;
        }
        $giorno = $date_ar[0];
        $mese = $date_ar[1];
        $anno = $date_ar[2];

        // ORA
        $time_ar = explode(":", $time);
        switch (count($time_ar)) {
            case 0:
                $time_ar[] = "00";
                $time_ar[] = "00";
                $time_ar[] = "00";
                break;
            case 1:
                $time_ar[] = "00";
                $time_ar[] = "00";
                break;
            case 2:
                $time_ar[] = "00";
                break;
        }

        $ora = $time_ar[0];
        $min = $time_ar[1];
        $sec = $time_ar[2];

        $timestamp = mktime($ora, $min, $sec, $mese, $giorno, $anno);
        //$timestamp = ($date+$time);
        // return ($date+$time);
        return ($timestamp);
    }

    public static function dt2tsFN($date)
    {
        $date_ar = preg_split('/[\\/.-]/', $date);
        if (count($date_ar) < 3) {
            return 0;
        }
        $format_ar = preg_split('#[/.-]#', ADA_DATE_FORMAT);
        if ($format_ar[0] == "%d") {
            $giorno = (int)$date_ar[0];
            $mese = (int)$date_ar[1];
        } else {   // english-like format
            $giorno = (int)$date_ar[1];
            $mese = (int)$date_ar[0];
        }

        $anno = (int)$date_ar[2];

        $unix_date = mktime(0, 0, 0, $mese, $giorno, $anno);
        return $unix_date;
    }

    /* FUNZIONI DI INDICIZZAZIONE E ORDINAMENTO*/
    // COMPARAZIONE NODI:

    ///////////////////////////////
    /*
 array functions
 */

    public static function aasort(&$array, $args)
    {
        /*
   Syntax: aasort($assoc_array, array("+first_key", "-second_key", etc..));
   */
        $args = array_reverse($args);
        if (count($array) > 0) {
            foreach ($args as $arg) {
                $temp_array = $array;
                $array = [];
                $order_key = substr($arg, 1, strlen($arg));

                foreach ($temp_array as $index => $nirvana) {
                    $sort_array[$index] = $temp_array[$index][$order_key];
                }

                ($arg[0] == "+") ? (asort($sort_array)) : (arsort($sort_array));

                foreach ($sort_array as $index => $nirvana) {
                    $array[$index] = $temp_array[$index];
                }
            }
        }
    }

    public static function masort($array, $arg, $sort_order = 1, $sort_method = SORT_STRING)
    {
        // multiple array sort

        /* works with typical AMA array, ie:
   array (
   array (asd,3f,asdf),
   array (5,asdf,34)
   )

   $arg: field to be used as key
   $sort_order : 1 (default) or -1 (reverse)
   */
        $temparray = [];
        $i = 0;
        foreach ($array as $subarray) {
            $key = ucfirst(strtolower(strip_tags($subarray[$arg])));
            if (!isset($temparray[$key])) {
                $temparray[$key] = $i;
            } else {
                $temparray[$key] .= ",$i";
            }
            $i++;
            // echo$key.":".$temparray[$key]."<br>";
        }

        $arraycopy = [];
        $max = count($array);
        $i = 0;
        if ($sort_order == -1) {
            krsort($temparray, $sort_method);
        } else {
            ksort($temparray, $sort_method);
        }
        foreach ($temparray as $key => $value) {
            $keyAr = explode(",", $value);
            foreach ($keyAr as $keyElem) {
                $arraycopy[$i] = $array[$keyElem];
                $i++;
            }
        }
        return $arraycopy;
    }

    public static function checkJavascriptFN($browser)
    {
        /* ********
   Check Browser version
   */
        $javascript_ok = 0;
        // $browser = HTTP_USER_AGENT;
        if (stristr($browser, "Mozilla") and ($browser[8] > 3)) {
            $javascript_ok = 1;
        }
        $debug = 1;
        return $javascript_ok;
        // *** End Check browser
    }

    public static function whoami()
    {

        $PHP_SELF = $_SERVER['PHP_SELF'];
        $SCRIPT_NAME = $_SERVER['SCRIPT_NAME'];

        if (!isset($PHP_SELF)) {      // not available in PHP>4.2.1
            if (isset($_SERVER['PHP_SELF'])) {
                $parent = $_SERVER['PHP_SELF'];
            } else {
                // $parent = $SCRIPT_NAME; // not available in PHP>4.2.1
                $parent =  $_SERVER['SCRIPT_NAME'];
            }
        } else {
            $parent = $PHP_SELF; //register_globals = off AND   PHP<4.2.2
        }

        $exploded = explode('.', basename($parent));
        $self = array_shift($exploded);  // = es. view
        $GLOBALS['SELF'] = $self;
        return $self;
    }

    /**
     * public static function substr_gentle
     *
     * Return a delimited string without truncate the last word.
     *
     * @author Valerio
     *
     * @param  string  $str          - a text string
     * @param  int  $limit - an array with additional parameters
     * @return string $new_str       - a substring with no truncated ending word
     */
    public static function substrGentle($str, $limit)
    {
        $str = str_replace("\n", '', substr($str, 0, $limit + 50));
        $array = explode("\n", wordwrap($str, $limit));
        return $array[0];
    }

    public static function dirTree($path)
    {

        /*
   **** dato un percorso ritorna l'elenco delle directory ****
   */
        $dirlist = [];
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $dir_path = $path . "/" . $file;
                    if (is_dir($dir_path)) {
                        $dirlist[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return ($dirlist);
    }

    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? static::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public static function leggidir($dir, $ext = "", $moreExtension = [])
    {
        return static::readDir($dir, $ext, $moreExtension);
    }

    protected static function printVars($obj)
    {
        $arr = get_object_vars($obj);
        foreach ($arr as $prop => $val) {
            echo "\t$prop = $val<br>\n";
        }
    }

    public static function readDir($dir, $ext = "", $moreExtension = [])
    {
        /*
   **** dato un percorso ritorna l'elenco dei file dei tipi consentiti ****
   */
        $nomedata = [];
        $elencofile = "";
        // vito, 31 ottobre 2008: aggiunto il check $ext!=""
        if (isset($ext) && $ext != "") {
            $allowed_extAr = [$ext];
        } else {
            $allowed_extAr = $moreExtension + [
                'txt',
                'doc',
                'rtf',
                'ppt',
                'xls',
                'htm',
                'html',
                'pdf',
                'zip',
                'jpg',
                'jpeg',
                'gif',
                'png',
                'mp3',
                'avi',
                'sxw',
                'odt',
                'xlsx',
                'xltx',
                'docx',
                'dotx',
                'pptx',
                'ppsx',
                'potx',
            ];
        }
        // var_dump($dir);
        // var_dump(opendir($dir));

        // die(__FILE__.':'.__LINE__);

        $dirid = @opendir($dir);
        if ($dirid) {
            $i = 0;
            while (($file = phpreaddir($dirid)) != false) {
                $fileAr = explode('.', $file);
                $ext = strtolower(array_pop($fileAr));
                if (in_array($ext, $allowed_extAr)) {
                    //$elencofile[$i]['file'] = $dir."/".$file;
                    // vito, 30 mar 2009
                    if (!is_array($elencofile)) {
                        $elencofile = [];
                    }
                    $elencofile[$i]['path_to_file'] = $dir . "/" . $file;
                    $elencofile[$i]['filemtime'] = filemtime($dir . "/" . $file);
                    $filetime = date("d/m/y", $elencofile[$i]['filemtime']);
                    $elencofile[$i]['data'] = $filetime;
                    $elencofile[$i]['file'] = $file;
                    $i++;
                }
            }
            closedir($dirid);
            // va ordinato ora
            if (is_array($elencofile)) {
                sort($elencofile);
                reset($elencofile);
            }
        }
        return $elencofile;
    }

    /*
 * Convert the letter of word in image
 * @param 	string 	$word word to translate
 * @param 	string 	$img_dir directory containg the letter image
 * @return 	string	string containig the images
 *
 */

    public static function convertiDattiloFN($word, $img_dir)
    {
        $dattilo_string = "";

        if ((isset($word)) and ($word != null)) {
            $end = strlen($word);
            $dattilo_string = $word;
            for ($l = 0; $l < $end; $l++) {
                $lettera = strtolower($word[$l]);
                $dattilo_string .= "<img src='$img_dir/$lettera.jpg' alt=$lettera>";
            }
        } else {
            $letAr = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
            foreach ($letAr as $lettera) {
                $dattilo_string .= strToUpper($lettera) . "<img src='$lettera.jpg' alt=$lettera border=0>";
            }
        }

        return $dattilo_string;
    }

    /*
 * Send Location header to browser, causing redirect
 * @param	string	$url url to redirect the browser
 *
 */
    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit();
    }

    public static function getCallingMethodName($backTrace = 2)
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backTrace + 1);
        return implode('::', [
            'class' => isset($dbt[1]['class']) ? $dbt[$backTrace]['class'] : null,
            'method' => isset($dbt[1]['function']) ? $dbt[$backTrace]['function'] : null,
        ]);
    }

    public static function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $parts = explode('.', $ip);
        if ($parts[0] == "173" && $parts[1] == "0") {
            // paypal sandbox ips
            return $parts[0] . $parts[1];
        }
        return $ip;
    }

    public static function inInstall()
    {
        return (0 === strcasecmp('install.php', basename($_SERVER['SCRIPT_FILENAME'])));
    }

    public static function isMultiArray($array)
    {
        return is_array($array) && count($array) > 0 && is_array($array[array_key_first($array)]);
    }

    /**
     * send headers Implementing Cross-Origin Isolation
     *
     * @return bool true on headers sent
     */
    public static function sendCrossOriginIsolation()
    {
        $filename = '';
        $line = 0;
        if (!headers_sent($filename, $line)) {
            header("Cross-Origin-Embedder-Policy: require-corp");
            header("Cross-Origin-Opener-Policy: same-origin");
            return true;
        } else {
            error_log(__METHOD__ . sprintf(" headers already sent by %s:%s", $filename, $line));
        }
        return false;
    }
}

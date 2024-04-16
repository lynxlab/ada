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

namespace Lynxlab\ADA\Main\Utilities;

use DateTime;
use DateTimeZone;

function mydebug($line, $file, $vars)
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
                printVars($vars);
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
function getTimezoneOffset($remote_tz, $origin_tz = null)
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

function todayDateFN()
{
    $now = time();
    return ts2dFN($now);
}

function todayTimeFN()
{
    $now = time();
    return ts2tmFN($now);
}

function ts2dFN($timestamp = "")
{
    if (empty($timestamp)) {
        $timestamp = time();
    }

    $dataformattata = strftime(ADA_DATE_FORMAT, $timestamp); // AMA version
    /*
  $data = getdate($timestamp);
  $dataformattata = $data['mday']."/".$data['mon']."/".$data['year'];
  */
    return $dataformattata;
}

function ts2tmFN($timestamp = "")
{
    if (empty($timestamp)) {
        $timestamp = time();
    }
    //$data = getdate($timestamp);
    $dataformattata = date("H:i:s", $timestamp);
    //$dataformattata = $data['hours'].":".$data['minutes'].":".$data['seconds'];
    return $dataformattata;
}

function sumDateTimeFN($arraydate)
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

function dt2tsFN($date)
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

function aasort(&$array, $args)
{
    /*
   Syntax: aasort($assoc_array, array("+first_key", "-second_key", etc..));
   */
    $args = array_reverse($args);
    if (count($array) > 0) {
        foreach ($args as $arg) {
            $temp_array = $array;
            $array = array();
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

function masort($array, $arg, $sort_order = 1, $sort_method = SORT_STRING)
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
    $temparray = array();
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

    $arraycopy = array();
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

function checkJavascriptFN($browser)
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

function whoami()
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
 * function substr_gentle
 *
 * Return a delimited string without truncate the last word.
 *
 * @author Valerio
 *
 * @param  string  $str          - a text string
 * @param  int  $limit - an array with additional parameters
 * @return string $new_str       - a substring with no truncated ending word
 */
function substrGentle($str, $limit)
{
    $str = str_replace("\n", '', substr($str, 0, $limit + 50));
    $array = explode("\n", wordwrap($str, $limit));
    return $array[0];
}

function dirTree($path)
{

    /*
   **** dato un percorso ritorna l'elenco delle directory ****
   */
    $dirlist = array();
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

function delTree($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function leggidir($dir, $ext = "", $moreExtension = [])
{
    return readDir($dir, $ext, $moreExtension);
}

function printVars($obj)
{
    $arr = get_object_vars($obj);
    foreach ($arr as $prop => $val) {
        echo "\t$prop = $val<br>\n";
    }
}

function readDir($dir, $ext = "", $moreExtension = [])
{
    /*
   **** dato un percorso ritorna l'elenco dei file dei tipi consentiti ****
   */
    $nomedata = array();
    $elencofile = "";
    // vito, 31 ottobre 2008: aggiunto il check $ext!=""
    if (isset($ext) && $ext != "") {
        $allowed_extAr = array($ext);
    } else {
        $allowed_extAr = $moreExtension + array(
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
            'potx'
        );
    }

    $dirid = @opendir($dir);
    if ($dirid) {
        $i = 0;
        while (($file = readdir($dirid)) != false) {
            $fileAr = explode('.', $file);
            $ext = strtolower(array_pop($fileAr));
            if (in_array($ext, $allowed_extAr)) {
                //$elencofile[$i]['file'] = $dir."/".$file;
                // vito, 30 mar 2009
                if (!is_array($elencofile)) {
                    $elencofile = array();
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

function convertiDattiloFN($word, $img_dir)
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
        $letAr = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
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
function redirect($url)
{
    header('Location: ' . $url);
    exit();
}

function GetCallingMethodName($backTrace = 2)
{
    $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backTrace + 1);
    return implode('::', [
        'class' => isset($dbt[1]['class']) ? $dbt[$backTrace]['class'] : null,
        'method' => isset($dbt[1]['function']) ? $dbt[$backTrace]['function'] : null,
    ]);
}

function getUserIpAddr()
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

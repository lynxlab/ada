<?php
/**
 * Actions perfomed at the start of each ADA module.
 *
 * This file contains all the actions that a ADA module has to perform before
 * doing anything else.
 *
 * PHP version >= 5.0
 *
 * @package
 * @author		Stefano Penge <steve@lynxlab.com>
 * @author		Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author		Vito Modena <vito@lynxlab.com>
 * @copyright	Copyright (c) 2009, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link		module_init
 * @version		0.1
 */

use function Lynxlab\ADA\Main\Utilities\today_dateFN;

/**
 * Functions used in this module
 */
require_once ROOT_DIR.'/include/module_init_functions.inc.php';

/**
 * Imports $_GET and $_POST variables
 */
//import_request_variables('GP',ADA_GP_VARIABLES_PREFIX);
extract($_GET,EXTR_OVERWRITE,ADA_GP_VARIABLES_PREFIX);
extract($_POST,EXTR_OVERWRITE,ADA_GP_VARIABLES_PREFIX);

/**
 * Graffio 19/08/2014
 * set the variable $GLOBALS['simpleCleaned'] in order to NOT clean the messagges
 * $GLOBALS['simpleCleaned'] = true means that the clean function is already been executed
 */
$GLOBALS['simpleCleaned'] = true;

/**
 *	Validates $_SESSION data
 */
if(!isset($neededObjAr) || !is_array($neededObjAr)) {
  $neededObjAr = array();
}
if(!isset($allowedUsersAr) || !is_array($allowedUsersAr)) {
  $allowedUsersAr = array();
}
if (!isset($trackPageToNavigationHistory)) {
	$trackPageToNavigationHistory = true;
}
session_controlFN($neededObjAr, $allowedUsersAr, $trackPageToNavigationHistory);
/**
 * Clears variables specified in $whatAR
 */
if(isset($variableToClearAR) && is_array($variableToClearAR)) {
  clear_dataFN($variableToClearAR);
}

$ymdhms = today_dateFN();
?>
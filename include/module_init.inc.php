<?php

use Lynxlab\ADA\Main\ModuleInit;

use function Lynxlab\ADA\Main\Utilities\todayDateFN;

/**
 * Imports $_GET and $_POST variables
 */

//import_request_variables('GP',ADA_GP_VARIABLES_PREFIX);
extract($_GET, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);
extract($_POST, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);

/**
 * Graffio 19/08/2014
 * set the variable $GLOBALS['simpleCleaned'] in order to NOT clean the messagges
 * $GLOBALS['simpleCleaned'] = true means that the clean function is already been executed
 */
$GLOBALS['simpleCleaned'] = true;

/**
 *  Validates $_SESSION data
 */
if (!isset($neededObjAr) || !is_array($neededObjAr)) {
    $neededObjAr = [];
}
if (!isset($allowedUsersAr) || !is_array($allowedUsersAr)) {
    $allowedUsersAr = [];
}
if (!isset($trackPageToNavigationHistory)) {
    $trackPageToNavigationHistory = true;
}
ModuleInit::sessionControlFN($neededObjAr, $allowedUsersAr, $trackPageToNavigationHistory);
/**
 * Clears variables specified in $whatAR
 */
if (isset($variableToClearAR) && is_array($variableToClearAR)) {
    ModuleInit::clearDataFN($variableToClearAR);
}

$ymdhms = todayDateFN();

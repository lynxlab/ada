<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_ADMIN];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_ADMIN => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  whoami();  // = admin!

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
AdminHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
/*
 * 1. dati del tester (con link modifica)
 * 2. elenco servizi erogati dal tester (con link modifica)
 * 3. link a lista utenti presenti sul tester
 */

$id_tester = DataValidator::isUinteger($_GET['id_tester']);

if ($id_tester !== false) {
    $tester_infoAr = $common_dh->getTesterInfoFromId($id_tester);
    if (AMACommonDataHandler::isError($tester_infoAr)) {
        $errObj = new ADAError($tester_infoAr);
    } else {
        /*    $testersAr = array();
            $tester_dataAr = array(
              array(translateFN('id')       , $tester_infoAr[0]),
              array(translateFN('Nome')     , $tester_infoAr[1]),
              array(translateFN('Ragione Sociale')       , $tester_infoAr[2]),
              array(translateFN('Indirizzo')  , $tester_infoAr[3]),
              array(translateFN('Provincia') , $tester_infoAr[4]),
              array(translateFN('Citt&agrave;')     , $tester_infoAr[5]),
              array(translateFN('Nazione')  , $tester_infoAr[6]),
              array(translateFN('Telefono')    , $tester_infoAr[7]),
              array(translateFN('E-mail')    , $tester_infoAr[8]),
              array(translateFN('Responsabile')     , $tester_infoAr[9]),
              array(translateFN('Puntatore al database')  , $tester_infoAr[10])
            );
            //$tester_data = BaseHtmlLib::tableElement('',array(),$tester_dataAr);

            $tester_data = AdminModuleHtmlLib::displayTesterInfo($tester_dataAr);

            $services_dataAr = $common_dh->getInfoForTesterServices($id_tester);
            if(AMACommonDataHandler::isError($services_dataAr)) {
              $errObj = new ADAError($services_dataAr);
            }
            else {
              $tester_services = AdminModuleHtmlLib::displayServicesOnThisTester($id_tester, $services_dataAr);
            }

            $tester_dsn = MultiPort::getDSN($tester_infoAr[10]);
            if($tester_dsn != NULL) {
              $tester_dh = AMADataHandler::instance($tester_dsn);
              $users_on_this_tester = $tester_dh->countUsersByType(array(AMA_TYPE_STUDENT,AMA_TYPE_AUTHOR,AMA_TYPE_TUTOR,AMA_TYPE_SWITCHER,AMA_TYPE_ADMIN));
              if(AMADataHandler::isError($users_on_this_tester)) {
              $errObj = new ADAError($users_on_this_tester);
              }
              else {
              $user_list_link = new CText('Numero di utenti presenti sul tester: '.$users_on_this_tester);
              }
            }
            */
    }
} else {
    /*
     * non e' stato passato id_tester
     */
}




//$tester_services = new CText('servizi offerti da questo provider<br />');
//$user_list_link  = new CText('numero di utenti presenti sul provider e link alista utenti');



$label = translateFN("Gestisci servizi associati al provider");

$home_link = CDOMElement::create('a', 'href:admin.php');
$home_link->addChild(new CText(translateFN("Home dell'Amministratore")));
$tester_profile_link = CDOMElement::create('a', 'href:tester_profile.php?id_tester=' . $id_tester);
$tester_profile_link->addChild(new CText(translateFN("Profilo del provider")));
$module = $home_link->getHtml() . ' > ' . $tester_profile_link->getHtml() . ' > ' . $label;

$help  = translateFN("Gestisci servizi associati al provider");

$content_dataAr = [
  'user_name'    => $user_name,
  'user_type'    => $user_type,
  'status'       => $status,
  'label'        => $label,
  'help'         => $help,
  'data'         => $data,
  'module'       => $module,
];

ARE::render($layout_dataAr, $content_dataAr);

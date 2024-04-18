<?php

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'user','course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER,AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
   AMA_TYPE_SWITCHER => ['layout', 'user'], // ,'course','course_instance'),
   AMA_TYPE_STUDENT => ['layout', 'course','course_instance'],
];

if (isset($_GET['forcereturn'])) {
    $forcereturn = (bool)intval($_GET['forcereturn']);
} else {
    $forcereturn = false;
}

if (!$forcereturn) {
    /**
    * Performs basic controls before entering this module
    */

    require_once ROOT_DIR . '/include/module_init.inc.php';

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
    * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
    * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
    * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
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
    BrowsingHelper::init($neededObjAr);
}

if (!isset($self)) {
    $self = whoami();
}

$title =  translateFN('Attestato di frequenza');

$logo = '<img class="usercredits_logo" src="' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/header-logo.png"  />';
$logoProvider = null;
if (MULTIPROVIDER === false) {
    $providerImg = HTTP_ROOT_DIR . '/clients/' . $client . '/layout/' . ADA_TEMPLATE_FAMILY . '/img/' . 'header-logo.png';
    if (function_exists('get_headers')) {
        $headers = @get_headers($providerImg);
        if (!str_contains($headers[0], '404')) {
            $logoProvider = '<img class="usercredits_logoProvider" src="' . $providerImg . '"  />';
        }
    }
}

if (isset($_GET['id_user'])) {
    $id_user = $_GET['id_user'];
}

if (isset($_GET['id_instance'])) {
    $id_instance = $_GET['id_instance'];
} else {
    $id_instance = $sess_id_instance;
}

//instance
if (!(isset($courseInstanceObj) && $courseInstanceObj instanceof CourseInstance)) {
    $courseInstanceObj =  new CourseInstance($id_instance);
}
$courseId = $courseInstanceObj->getCourseId();

// course
if (!(isset($courseObj) && $courseObj instanceof Course)) {
    $courseObj = new Course($courseId);
}

$codice_corso = $courseObj->getCode();

$UserCertificateObj = MultiPort::findUser($id_user, $id_instance);

$userFullName = $UserCertificateObj->getFullName();
$gender = $UserCertificateObj->getGender();
$birthplace = $UserCertificateObj->getBirthCity();
$codFisc = $UserCertificateObj->getFiscalCode();
$province = $UserCertificateObj->getProvince();
$birthdate = $UserCertificateObj->getBirthDate();


if (strToUpper($gender) == "F") {
    $nato = translateFN('nata');
} else {
    $nato = translateFN('nato');
}
if ((!is_null($birthplace) && stripos($birthplace, 'NULL') === false && strlen($birthplace) > 0) && (!is_null($birthdate) && $birthdate > 0 && strlen($birthdate) > 0)) {
    $birthSentence = "";
}
if (!is_null($codFisc) && stripos($codFisc, 'NULL') === false && strlen($codFisc) > 0) {
    $CodeFiscSentence = translateFN(' Codice Fiscale: ') . $codFisc;
}
if (!is_null($courseObj->getTitle()) && stripos($courseObj->getTitle(), 'NULL') === false && strlen($courseObj->getTitle()) > 0) {
    $mainSentence = '<strong>' . $courseObj->getTitle() . '</strong>';
    $courseDurationSentence = translateFN('Monte ore maturato: ') . '<strong>' . $courseObj->getDurationHours() . translateFN(' ore </strong>');
}

$UserCertificateObj->setCourseInstanceForHistory($id_instance);
$user_historyObj = $UserCertificateObj->history;
$time = $user_historyObj->historyNodesTimeFN();
$timeSentence = translateFN('Monte ore frequentato: ') . '<strong>' . $time . translateFN(' ore </strong>');

$data_inizio = $courseInstanceObj->getStartDate();

if ($data_inizio != '') {
    $data_Sentence = translateFN('Data inizio corso: ') . '<strong>' . $data_inizio . '</strong>';
}

$testerAr = $common_dh->getTesterInfoFromIdCourse($courseObj->getId());

if (!is_null($testerAr['nome']) && stripos($testerAr['nome'], 'NULL') === false && strlen($testerAr['nome'])) {
    $providerSentence = translateFN('Provider che ha organizzato il corso: ') . '<strong>' . $testerAr['nome'] . '</strong>';
}

$currentData = ts2dFN(time());
$luogo = $testerAr['citta'];
$placeAndDate = $luogo . ' ' . $currentData;

$responsabile = $testerAr['responsabile'];
$signature = translateFN('Il Rappresentante Legale del Provider: ') . $responsabile;

$content_dataAr   = [
 'logo' => $logo,
 'title' => $title,
 'logoProvider' => $logoProvider,
 'userFullName' => $userFullName,
 'birthSentence' => $birthSentence ?? null,
 'CodeFiscSentence' => $CodeFiscSentence ?? null,
 'mainSentence' => $mainSentence,
 'timeSentence' => $timeSentence,
 'data_Sentence' => $data_Sentence,
 'providerSentence' => $providerSentence,
 'placeAndDate' => $placeAndDate,
 'signature' => $signature,
 'courseDescription' => (isset($courseObj) && $courseObj instanceof Course) ? $courseObj->getDescription() : null,
 'courseDurationSentence' => $courseDurationSentence ?? null,
 ];

/**
 * Look for a certificate template to use, can be either:
 * - $self . '-instance-' . <COURSE_INSTANCE_ID>
 * - $self . '-course-' . <COURSE_ID>
 * - $self . '-servicelevel-' . <COURSE_SERVICE_LEVEL>
 */
$foundtpl = false;
foreach ([$userObj->template_family, $_SESSION['sess_template_family']] as $tpldir) {
    if (!$foundtpl && strlen($tpldir) > 0) {
        $basetpl = ROOT_DIR . '/layout/' . $tpldir . '/templates/browsing/';
        $suffixes = [
            'instance-' . $courseInstanceObj->getId(),
            'course-' . $courseObj->getId(),
            'servicelevel-' . $courseObj->getServiceLevel(),
        ];
        foreach ($suffixes as $suffix) {
            if (!$foundtpl) {
                $path = $basetpl . $self . '-' . $suffix . '.tpl';
                if (is_file($path) && is_readable($path)) {
                    // template found, set the $self global accordingly
                    $self = $self . '-' . $suffix;
                    $foundtpl = true;
                }
            }
        }
    }
}

if ($forcereturn) {
    return [
       'filename' => translateFN('Attestato') . '-[' . $codice_corso . ']-[' . $id_user . '].pdf',
       'content' => ARE::render($layout_dataAr, $content_dataAr, ARE_PDF_RENDER, ['returnasstring' => true,'outputfile' => translateFN('Attestato') . '-[' . $codice_corso . ']-[' . $id_user . ']']),
    ];
} else {
    ARE::render($layout_dataAr, $content_dataAr, ARE_PDF_RENDER, ['outputfile' => translateFN('Attestato') . '-[' . $codice_corso . ']-[' . $id_user . ']']);
}

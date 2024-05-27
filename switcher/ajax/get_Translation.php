<?php

/**
 * save_traslation.php
 *
 * @package
 * @author      sara <sara@lynxlab.com>
 * @copyright           Copyright (c) 2009-2013, Lynx s.r.l.
 * @license     http:www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Forms\TranslationForm;
use Lynxlab\ADA\Main\Helper\SwitcherHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
];

$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
$self =  "switcher";

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
SwitcherHelper::init($neededObjAr);

$self =  "translation";

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $form = $form = new TranslationForm();
    $form->fillWithPostData();
    if ($form->isValid()) {
        $search_text = $_POST['t_name'];
        $language_code = $_POST['selectLanguage'];
        $common_dh = AMACommonDataHandler::getInstance();
        $thead_data = [translateFN("Errore")];
        if (is_null($search_text) || $search_text == "") {
            $total_results = [];
            $msgEr = translateFN("Nessun input sottomesso");
            $temp_results = [translateFN("") => $msgEr];
            array_push($total_results, $temp_results);
            $result_table = BaseHtmlLib::tableElement('id:table_result', $thead_data, $total_results);
            $result_table->setAttribute('class', $result_table->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
            $result = $result_table->getHtml();
            $retArray = ["status" => "ERROR", "msg" =>  translateFN("Nessun input sottomesso"), "html" => $result];
        } else {
            //$result = $common_dh->findTranslationForMessage($search_text, $language_code, ADA_SYSTEM_MESSAGES_SHOW_SEARCH_RESULT_NUM);
            $result = $common_dh->findTranslationForMessage($search_text, $language_code, null);

            if (AMADataHandler::isError($result)) {
                $total_results = [];
                $msgEr = translateFN("Errore nella ricerca dei messaggi");
                $temp_results = [translateFN("") => $msgEr];
                array_push($total_results, $temp_results);
                $result_table = BaseHtmlLib::tableElement('id:table_result', $thead_data, $total_results);
                $result = $result_table->getHtml();
                $retArray = ["status" => "ERROR", "msg" =>  translateFN("Errore nella ricerca dei messaggi"), "html" => $result];
            } elseif ($result == null) {
                $total_results = [];
                $msgEr = translateFN("Nessuna frase trovata");
                $temp_results = [translateFN("") => $msgEr];
                array_push($total_results, $temp_results);
                $result_table = BaseHtmlLib::tableElement('id:table_result', $thead_data, $total_results);
                $result_table->setAttribute('class', $result_table->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
                $result = $result_table->getHtml();
                $retArray = ["status" => "ERROR", "msg" =>  translateFN("Nessuna frase trovata"), "html" => $result];
            } else {
                $thead_data = [
                    null,
                    translateFN('Testo'),
                    translateFN('Azioni'),
                    translateFN('TestoCompleto'),
                    translateFN('CodLingua'),
                    translateFN('Id'),
                ];
                $total_results = [];
                //$imgDetails='<img class="imgEx tooltip" src='.HTTP_ROOT_DIR.'/layout/ada_blu/img/details_open.png >';
                $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/details_open.png');
                $imgDetails->setAttribute('class', 'imgDetls tooltip');
                $imgDetails->setAttribute('title', translateFN('espande/riduce il testo'));
                foreach ($result as $row) {
                    $testoCompleto = $row['testo_messaggio'];
                    $testoRidotto =  substr($row['testo_messaggio'], 0, 30);
                    if (strlen($testoCompleto) > 30) {
                        $testoRidotto = $testoRidotto . '...';
                    }
                    $id_message = $row['id_messaggio'];
                    $newButton = CDOMElement::create('button');
                    $newButton->setAttribute('class', 'buttonTranslate tooltip');
                    $newButton->addChild(new CText(translateFN('Clicca per aggiornare la traduzione')));
                    $temp_results = [
                        null => $imgDetails, translateFN('Testo') => $testoRidotto, translateFN('Azioni') => $newButton, translateFN('TestoCompleto') => $testoCompleto,
                        translateFN('CodLingua') => $language_code, translateFN('Id') => $id_message,
                    ];
                    array_push($total_results, $temp_results);
                }

                $result_table = BaseHtmlLib::tableElement('id:table_result', $thead_data, $total_results);
                $result_table->setAttribute('class', $result_table->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
                $result = $result_table->getHtml();
                $retArray = ["status" => "OK", "msg" =>  translateFN("Ricerca eseguita con successo"), "html" => $result];
            }
        }
    } else {
        $retArray = ["status" => "ERROR", "msg" =>  translateFN("Dati inseriti non validi"), "html" => null];
    }

    echo json_encode($retArray, JSON_INVALID_UTF8_IGNORE);
}

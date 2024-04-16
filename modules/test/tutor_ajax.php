<?php

use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;

use function Lynxlab\ADA\Main\AMA\DBRead\readUserFromDB;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_TUTOR];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
        AMA_TYPE_TUTOR => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
//require_once(ROOT_DIR.'/include/HtmlLibrary/ServicesModuleHtmlLib.inc.php');

//needed to promote AMADataHandler to AMATestDataHandler. $sess_selected_tester is already present in session
$GLOBALS['dh'] = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

switch ($_GET['mode']) {
    default:
        $res = false;
        break;
    case 'comment':
        $res = $dh->testUpdateAnswer($_GET['id_answer'], ['commento' => $_POST['comment']]);
        if (!$dh->isError($res) && $res && $_POST['notify'] == true) {
            $answer = $dh->testGetAnswer($_GET['id_answer']);
            $answer = $answer[0];
            $history = $dh->testGetHistoryTest(['id_history_test' => $answer['id_history_test']]);
            $history = $history[0];
            $test = $dh->testGetNode($history['id_nodo']);
            $studentObj = readUserFromDB($answer['id_utente']);
            if (!$dh->isError($studentObj) && !$dh->isError($answer) && !$dh->isError($history) && !$dh->isError($test)) {
                $what = '';
                $link = '';

                if ($test['tipo'][0] == ADA_TYPE_TEST) {
                    $what = 'test';
                    $name = 'test';
                } elseif ($test['tipo'][0] == ADA_TYPE_SURVEY) {
                    $what = 'survey';
                    $name = 'sondaggio';
                }
                $link = '';
                if ($what) {
                    $href = MODULES_TEST_HTTP . '/history.php?op=' . $what . '&id_course=' . $answer['id_corso'] . '&id_course_instance=' . $answer['id_istanza_corso'] . '&id_test=' . $test['id_nodo'] . '&id_history_test=' . $answer['id_history_test'];
                    $link = '<a href="' . $href . '">' . translateFN('Visualizza') . ' ' . translateFN($name) . '</a>';
                }

                $titolo = sprintf(translateFN('Messaggio dal tutor sul %s:'), translateFN($name)) . ' ' . $test['titolo'];
                $testo = $_POST['comment'];
                $testo .= '<br /><br />' . $link;

                $message_ha = [
                    'destinatari' => $studentObj->getUserName(),
                    'data_ora' => 'now',
                    'tipo' => ADA_MSG_SIMPLE,
                    'mittente' => $_SESSION['sess_userObj']->getUserName(),
                    'titolo' => $titolo,
                    'testo' => $testo,
                    'priorita' => 2,
                ];
                $mh = MessageHandler::instance(MultiPort::getDSN($sess_selected_tester));
                $result = $mh->sendMessage($message_ha);
            }
        }
        break;
    case 'answer':
        $res = $dh->testUpdateAnswer($_GET['id_answer'], ['correzione_risposta' => $_POST['answer']]);
        break;
    case 'points':
        $res = $dh->testUpdateAnswer($_GET['id_answer'], ['punteggio' => $_GET['points']]);
        break;
    case 'repeatable':
        $res = ($dh->testSetHistoryTestRepeatable($_GET['id_history_test'], $_GET['repeatable'])
            && $dh->testRecalculateHistoryTestPoints($_GET['id_history_test']));
        break;
}

if (!$dh->isError($res) && $res) {
    echo 1;
} else {
    echo 0;
}

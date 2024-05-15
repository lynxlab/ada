<?php

/**
 * Media Manager
 *
 * @package     Services / Node
 * stamos
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright           Copyright (c) 2011, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Node\Media;
use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Output\Output;
use Lynxlab\ADA\Main\Utilities as MainUtilities;
use Lynxlab\ADA\Services\NodeEditing\Utilities;

use function Lynxlab\ADA\Comunica\Functions\exitWithJSONError;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */

$variableToClearAR = ['layout','user','course','course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [];

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = MainUtilities::whoami();

/*
 * YOUR CODE HERE
 */

if ($op == 'read') {
    if (!isset($_POST['nome_file']) || !isset($_POST['id_utente'])) {
        exitWithJSONError(translateFN('Errore: parametri passati allo script PHP non corretti'));
    }
    $filename = $_POST['nome_file'];
    $author_id = $_POST['id_utente'];
    $media_found = $dh->getRisorsaEsternaInfoAutore($filename, $author_id);
    if (AMADataHandler::isError($media_found) and $media_found->code == AMA_ERR_GET) {
        exitWithJSONError(translateFN('Errore: Media non trovato'));
    }
    print(json_encode($media_found));
} elseif (!isset($op) || $op = 'insert' || $op == 'update') {
    /*
     * Check that this script was called with the right arguments.
     * If not, stop script execution and report an error to the caller.
     */
    if (!isset($_POST['nome_file']) || !isset($_POST['tipo']) || !isset($_POST['id_utente'])) {
        exitWithJSONError(translateFN('Errore: parametri passati allo script PHP non corretti'));
    }

    $filename  = $_POST['nome_file'];
    $tipo_file  = $_POST['tipo'];
    $author_id = $_POST['id_utente'];
    $copyright = $_POST['copyright'];
    $keywords = $_POST['keywords'];
    $titolo = $_POST['titolo'];
    $descrizione = $_POST['descrizione'];
    $pubblicato = $_POST['pubblicato'];
    $lingua = $_POST['lingua'];

    $res_ha['nome_file'] = $nome_file;
    $res_ha['tipo'] = $tipo_file;
    $res_ha['id_utente'] = $id_utente_autore;
    $res_ha['copyright'] = $copyright;
    $res_ha['keywords'] = $keywords;
    $res_ha['titolo'] = $titolo;
    $res_ha['descrizione'] = $descrizione;
    if ($pubblicato == 'on') {
        $pubblicato = 1;
    } else {
        $pubblicato = 0;
    }
    $res_ha['pubblicato'] = $pubblicato;
    $res_ha['lingua'] = $lingua;

    $media_found = $dh->getRisorsaEsternaInfoAutore($filename, $author_id);
    if (AMADataHandler::isError($media_found) and $media_found->code == AMA_ERR_NOT_FOUND) {
        $op = 'insert';
        $id_res_ext = $dh->addOnlyInRisorsaEsterna($res_ha);
        if (AMADataHandler::isError($id_res_ext)) {
            exitWithJSONError(translateFN("Errore nell'inserimento del media"));
        }
        $response = [];
        $response['result'] = 'Inserimento media riuscito';
    } elseif (isset($media_found['id_risorsa_ext'])) {
        $op = 'update';
        $id_res_ext = $media_found['id_risorsa_ext'];
        $update_media = $dh->setRisorsaEsterna($id_res_ext, $res_ha);
        if (AMADataHandler::isError($update_media)) {
            exitWithJSONError(translateFN("Errore nell'aggiornamento del media"));
        }
        $response = [];
        $response['result'] = 'Aggiornamento media riuscito';
    } else {
        if (AMADataHandler::isError($media_found) and $media_found->code == AMA_ERR_GET) {
            $response = [];
            $response['result'] = 'Errore AMA ';
        }
    }

    print(json_encode($response));
}

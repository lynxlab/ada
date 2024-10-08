<?php

/**
 * @package test
 * @author  Valerio Riva <valerio@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Test;

use Lynxlab\ADA\Main\Node\Node;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use Lynxlab\ADA\Module\Test\DeleteFormTest;
use Lynxlab\ADA\Module\Test\ManagementTest;
use Lynxlab\ADA\Module\Test\TopicFormTest;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TopicManagementTest extends ManagementTest
{
    protected $id_test = null;

    /**
     * Topic Management constructor
     * calls parent constructor too.
     *
     * @param string $action represents the action to do ('add', 'mod', 'del')
     * @param int $id topic id
     * @param int $id_test test id
     */
    public function __construct($action, $id = null, $id_test = null)
    {
        parent::__construct($action, $id);

        if ($_POST && ($_POST['id_nodo_parent'] ?? 0) == $id_test) {
            $this->what = translateFN('sessione');
        } else {
            $this->what = translateFN('argomento');
        }
        $this->id_test = $id_test;
    }

    /**
     * function that set "tipo" attribute from default values, post or from database record
     */
    protected function setTipo()
    {
        $this->tipo = [
            0 => ADA_GROUP_TOPIC,
            1 => ADA_PICK_QUESTIONS_NORMAL,
            2 => 0, //setted to zero becase it is not applicable
            3 => 0, //setted to zero becase it is not applicable
            4 => 0, //setted to zero becase it is not applicable
            5 => 0, //setted to zero becase it is not applicable
        ];

        if ($_POST) {
            $this->tipo[1] = intval($_POST['random'] ?? 0);
            $this->tipo[2] = 0;
            $this->tipo[3] = 0;
            $this->tipo[4] = 0;
            $this->tipo[5] = 0;
        } else {
            $this->readTipoFromRecord();
        }
    }

    /**
     * Adds a topic node
     *
     * @global db $dh
     *
     * @return array an array with 'html' and 'path' keys
     */
    public function add()
    {
        $dh = $GLOBALS['dh'];

        $test = $dh->testGetNode($this->id_test);
        $nodo = new Node($test['id_nodo_riferimento']);
        if (!AMATestDataHandler::isError($nodo)) {
            $path = $nodo->findPathFN();
        }

        $form = new TopicFormTest($test['id_nodo'], $_POST, $_GET['id_nodo_parent'] ?? null);

        if ($_POST) {
            if ($form->isValid()) {
                $siblings = $dh->testGetNodesByParent($_POST['id_nodo_parent']);
                $ordine = count($siblings) + 1;

                //crea nuovo topic con i dati del form
                $this->setTipo();
                $data = [
                    'id_corso' => $test['id_corso'],
                    'id_utente' => $_SESSION['sess_id_user'],
                    'id_istanza' => $test['id_istanza'],
                    'nome' => $_POST['nome'],
                    'titolo' => $_POST['titolo'],
                    'testo' => Node::prepareInternalLinkMediaForDatabase($_POST['testo']),
                    'tipo' => $this->getTipo(),
                    'livello' => $_POST['random_number'],
                    'id_nodo_parent' => $_POST['id_nodo_parent'],
                    'id_nodo_radice' => $test['id_nodo'],
                    'durata' => $_POST['durata'] * 60,
                    'ordine' => $ordine,
                ];
                $res = $dh->testAddNode($data);
                unset($data);

                if (!AMATestDataHandler::isError($res)) {
                    if ($test['id_nodo'] == $_POST['id_nodo_parent']) {
                        $_GET['topic'] = $ordine - 1;
                    }
                    $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');
                    Utilities::redirect(MODULES_TEST_HTTP . '/index.php?id_test=' . $test['id_nodo'] . $get_topic);
                } else {
                    $html = sprintf(translateFN('Errore durante la creazione del %s'), $this->what);
                }
            } else {
                $html = $form->getHtml();
            }
        } else {
            $html = $form->getHtml();
        }

        return [
            'html' => $html,
            'path' => $path,
        ];
    }

    /**
     * modifies a topic node
     *
     * @global db $dh
     *
     * @return array an array with 'html' and 'path' keys
     */
    public function mod()
    {
        $dh = $GLOBALS['dh'];

        $topic = &$this->r;

        $test = $dh->testGetNode($topic['id_nodo_radice']);
        $nodo = new Node($test['id_nodo_riferimento']);
        if (!AMATestDataHandler::isError($nodo)) {
            $path = $nodo->findPathFN();
        }

        if ($_POST) {
            $data = $_POST;
        } else {
            $data = [
                'nome' => $topic['nome'],
                'titolo' => $topic['titolo'],
                'testo' => $topic['testo'],
                'durata' => round($topic['durata'] / 60, 2),
                'random' => $topic['tipo'][1],
                'random_number' => $topic['livello'],
                'id_nodo_parent' => $topic['id_nodo_parent'],
            ];
        }

        $form = new TopicFormTest($topic['id_nodo_radice'], $data, $topic['id_nodo_parent']);

        if ($_POST) {
            if ($form->isValid()) {
                //crea nuovo test con i dati del form
                $this->setTipo();
                $data = [
                    'nome' => $_POST['nome'],
                    'titolo' => $_POST['titolo'],
                    'testo' => Node::prepareInternalLinkMediaForDatabase($_POST['testo']),
                    'id_nodo_parent' => $_POST['id_nodo_parent'],
                    'tipo' => $this->getTipo(),
                    'livello' => $_POST['random_number'],
                    'durata' => $_POST['durata'] * 60,
                ];
                if ($dh->testUpdateNode($topic['id_nodo'], $data)) {
                    $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');
                    Utilities::redirect(MODULES_TEST_HTTP . '/index.php?id_test=' . $test['id_nodo'] . $get_topic);
                } else {
                    $html = sprintf(translateFN('Errore durante la modifica del %s'), $this->what);
                }
            } else {
                $html = $form->getHtml();
            }
        } else {
            $html = $form->getHtml();
        }

        return [
            'html' => $html,
            'path' => $path,
        ];
    }

    /**
     * deletes a topic node
     *
     * @global db $dh
     *
     * @return array an array with 'html' and 'path' key
     */
    public function del()
    {
        $dh = $GLOBALS['dh'];

        $topic = &$this->r;

        $test = $dh->testGetNode($topic['id_nodo_radice']);
        $nodo = new Node($test['id_nodo_riferimento']);
        if (!AMATestDataHandler::isError($nodo)) {
            $path = $nodo->findPathFN();
        }

        if ($topic['id_nodo_radice'] == $topic['id_nodo_parent']) {
            $this->what = translateFN('sessione');
        }

        if (isset($_POST['delete'])) {
            if ($_POST['delete'] == 1) {
                if (AMATestDataHandler::isError($dh->testDeleteNodeTest($this->id))) {
                    $html = sprintf(translateFN('Errore durante la cancellazione del %s'), $this->what);
                } else {
                    $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');
                    Utilities::redirect(MODULES_TEST_HTTP . '/index.php?id_test=' . $topic['id_nodo_radice'] . $get_topic);
                }
            } else {
                $get_topic = (isset($_GET['topic']) ? '&topic=' . $_GET['topic'] : '');
                Utilities::redirect(MODULES_TEST_HTTP . '/index.php?id_test=' . $topic['id_nodo_radice'] . $get_topic);
            }
        } else {
            $titolo = $topic['titolo'];
            if (empty($titolo)) {
                $titolo = $topic['nome'];
            }
            $titolo = $this->what . ' "' . $titolo . '"';
            $message = sprintf(translateFN('Stai per cancellare %s e tutti i dati contenuti. Continuare?'), $titolo);
            $form = new DeleteFormTest($message);
            $html = $form->getHtml();
        }

        return [
            'html' => $html,
            'path' => $path,
        ];
    }

    /**
     * returns status message based on $action attribute
     */
    public function status()
    {
        switch ($this->action) {
            case 'add':
                return sprintf(translateFN('Aggiunta di un %s'), $this->what);
                break;
            case 'mod':
                return sprintf(translateFN('Modifica di un %s'), $this->what);
                break;
            case 'del':
                return sprintf(translateFN('Cancellazione di un %s'), $this->what);
                break;
        }
    }
}

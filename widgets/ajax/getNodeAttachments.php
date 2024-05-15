<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\Course;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\CollaboraACL\AMACollaboraACLDataHandler;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLActions;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLException;
use Lynxlab\ADA\Module\CollaboraACL\FileACL;
use Lynxlab\ADA\Widgets\Widget;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Widgets\Functions\cleanFileName;

/**
 * Common initializations and include files
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Users (types) allowed to access this module.
 */
[$allowedUsersAr, $neededObjAr] = array_values(CollaboraACLActions::getAllowedAndNeededAr());
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
extract(BrowsingHelper::init($neededObjAr));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    // session_write_close();
    extract($_GET);
    if (!isset($widgetMode)) {
        $widgetMode = Widget::ADA_WIDGET_ASYNC_MODE;
    }
    /**
     * checks and inits to be done if this has been called in async mode
     * (i.e. with a get request)
     */
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (
            $widgetMode != Widget::ADA_WIDGET_SYNC_MODE &&
            preg_match("#^" . trim(HTTP_ROOT_DIR, "/") . "($|/.*)#", $_SERVER['HTTP_REFERER']) != 1
        ) {
            die('Only local execution allowed.');
        }
    }
    /**
     * Your code starts here
     */
    try {
        if (!isset($userId)) {
            throw new CollaboraACLException(translateFN("Specificare un id studente"));
        }
        if (!isset($nodeId)) {
            throw new CollaboraACLException(translateFN("Specificare un id nodo"));
        }
        if (!isset($courseId)) {
            $courseId = $_SESSION['sess_id_course'];
            $courseObj = $_SESSION['sess_courseObj'];
        }
        if (!isset($courseInstanceId) || (isset($courseInstanceId) && $courseInstanceId < 0)) {
            $courseInstanceId = $_SESSION['sess_id_course_instance'];
        }

        /**
         * get the correct testername
         */
        if (!MULTIPROVIDER) {
            if (isset($GLOBALS['user_provider']) && !empty($GLOBALS['user_provider'])) {
                $testerName = $GLOBALS['user_provider'];
            } else {
                throw new CollaboraACLException(translateFN('Nessun fornitore di servizi &egrave; stato configurato'));
            }
        } elseif (isset($courseId)) {
            $testerInfo = $GLOBALS['common_dh']->getTesterInfoFromIdCourse($courseId);
            if (!AMADB::isError($testerInfo) && is_array($testerInfo) && isset($testerInfo['puntatore'])) {
                $testerName = $testerInfo['puntatore'];
            }
        } // end if (!MULTIPROVIDER)

        if (!isset($testerName)) {
            throw new CollaboraACLException(translateFN('Spiacente, non so a che fornitore di servizi sei collegato'));
        }

        if (ModuleLoaderHelper::isLoaded('COLLABORAACL')) {
            $aclDH = AMACollaboraACLDataHandler::instance(MultiPort::getDSN($testerName));

            if (!isset($courseObj)) {
                $courseObj = new Course($courseId);
            }

            if (!($courseObj instanceof Course)) {
                throw new CollaboraACLException(translateFN('Impossibile caricare il corso'));
            }

            if ($courseObj->media_path != "") {
                $media_path = $courseObj->media_path;
            } else {
                $media_path = MEDIA_PATH_DEFAULT . $courseObj->id_autore;
            }
            $download_path = ROOT_DIR . $media_path;

            $elencofile = Utilities::leggidir($download_path);
            $outputArr = [];
            if (is_array($elencofile) && count($elencofile) > 0) {
                $filesACL = $aclDH->findBy('FileACL', ['id_corso' => $courseId, 'id_istanza' => $courseInstanceId, 'id_nodo' => $nodeId]);
                if ($userObj->getType() != AMA_TYPE_TUTOR) {
                    $elencofile = array_filter($elencofile, function ($fileel) use ($filesACL, $userObj) {
                        $elPath = str_replace(ROOT_DIR . DIRECTORY_SEPARATOR, '', $fileel['path_to_file']);
                        return FileACL::isAllowed($filesACL, $userObj->getId(), $elPath, CollaboraACLActions::READ_FILE);
                    });
                }
                $elencofile = array_filter($elencofile, function ($singleFile) use ($nodeId, $courseInstanceId) {
                    $filenameAr = explode('_', $singleFile['file']);
                    $file_courseInstanceId = $filenameAr[0] ?? null;
                    $file_nodeId = null;
                    if (isset($filenameAr[2]) && isset($filenameAr[3])) {
                        $file_nodeId =  $filenameAr[2] . "_" . $filenameAr[3];
                    }
                    return ($file_nodeId == $nodeId && $file_courseInstanceId == $courseInstanceId);
                });

                if (is_array($elencofile) && count($elencofile) > 0) {
                    usort($elencofile, fn ($a, $b) => strcasecmp(cleanFileName($a['file']), cleanFileName($b['file'])));

                    $icongeneric = 'attachment';
                    $iconcls = [
                        'notset' => 'attachment',
                        _IMAGE => 'photo',
                        _SOUND => 'music',
                        _VIDEO => 'video',
                        _DOC => 'text file outline',
                    ];
                    $title = CDOMElement::create('div', 'class:nodeattachments title');
                    $title->addChild(CDOMElement::create('i', 'class:dropdown icon'));
                    $title->addChild(new CText(translateFN('Files allegati al nodo')));
                    $outputArr[] = $title;

                    $maincontent = CDOMElement::create('div', 'class:nodeattachments content');
                    $cont = CDOMElement::create('div', 'class:ui feed basic segment');
                    $maincontent->addChild($cont);

                    foreach ($elencofile as $singleFile) {
                        $filenameAr = explode('_', $singleFile['file']);
                        if (isset($filenameAr[4]) && array_key_exists($filenameAr[4], $iconcls)) {
                            $icon = $iconcls[$filenameAr[4]];
                        } else {
                            $icon = $icongeneric;
                        }
                        $event = CDOMElement::create('div', 'class:event');
                        $cont->addChild($event);
                        $label = CDOMElement::create('div', 'class:label');
                        $label->addChild(CDOMElement::create('i', 'class:icon ' . $icon));
                        $event->addChild($label);
                        $content = CDOMElement::create('div', 'class:content');
                        $event->addChild($content);
                        $date = CDOMElement::create('div', 'class:date');
                        $content->addChild($date);
                        $date->addChild(new CText($singleFile['data']));
                        $summary = CDOMElement::create('div', 'class:summary');
                        $content->addChild($summary);
                        $link = CDOMElement::create('a', 'target:_blank,href:download.php?file=' . $singleFile['file']);
                        $link->addChild(new CText(cleanFileName($singleFile['file'])));
                        $summary->addChild($link);
                    }
                    $outputArr[] = $maincontent;
                }
            }
            $output = implode(PHP_EOL, array_map(fn ($el) => $el->getHtml(), $outputArr));
        }
    } catch (CollaboraACLException $e) {
        $divClass = 'error';
        $divMessage = basename($_SERVER['PHP_SELF']) . ': ' . $e->getMessage();
        $outDIV = CDOMElement::create('div', "class:ui $divClass message");
        $closeIcon = CDOMElement::create('i', 'class:close icon');
        $closeIcon->setAttribute('onclick', 'javascript:$j(this).parents(\'.ui.message\').remove();');
        $outDIV->addChild($closeIcon);
        $errorSpan = CDOMElement::create('span');
        $errorSpan->addChild(new CText($divMessage));
        $outDIV->addChild($errorSpan);
        $output = $outDIV->getHtml();
    }

    if (!isset($output)) {
        $output = '';
    }
    /**
     * Common output in sync or async mode
     */
    switch ($widgetMode) {
        case Widget::ADA_WIDGET_SYNC_MODE:
            return $output;
            break;
        case Widget::ADA_WIDGET_ASYNC_MODE:
        default:
            die($output);
    }
}

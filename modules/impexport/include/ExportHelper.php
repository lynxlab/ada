<?php

/**
 * @package     import/export course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2012, Lynx s.r.l.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version 0.1
 */

namespace Lynxlab\ADA\Module\Impexport;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use ZipArchive;

class ExportHelper
{
    /**
     * elements for which a cdata will be generated in course and node properties
     */
    public static $cDataElementNameForCourse =  ['nome', 'titolo', 'descr'];
    public static $cDataElementNameForCourseNode =  ['name', 'title', 'text','icon'];
    public static $cDataElementNameForExtReS =  ['nome_file', 'keywords', 'titolo', 'descrizione'];
    public static $cDataElementNameForTest =  ['nome', 'titolo', 'consegna', 'testo', 'copyright', 'didascalia', 'titolo_dragdrop', 'icona'];

    /**
     * @var string char for separating courseId from nodeId (e.g. 110_0) in tabella nodo
     */
    public static $courseSeparator = '_';

    /**
     *
     * @var array holds media files to be exported
     * keys are the array of the exported courses and values are arrays of files belonging to the course
     */
    public $mediaFilesArray;

    /**
     * derived from config_install.inc.php
     * @var string
     */
    public $mediaFilesPath;

    /**
     * name of the zip file that will be generated.
     * .zip extension, session UserObj name and
     * System date in YYYYMMDD format will be appended,
     * resulting in an actual file name such as ADAExport_switcherAda0_20130711.zip
     * Extension will be .zip of course ;)
     * @var string
     */
    private static $outputFileName = "ADAExport";

    /**
     * Module's own log file to log import progress, and if something goes wrong
     * @var string
     */
    private $logFile;

    /**
     * array of exported ADA nodes id
     * @var array
     */
    public $exportedNONTestNodeArray;

    /**
     * holds the exported TEST nodes to be saved as XML
     *
     * @var \DOMElement
     */
    public $testNodeXMLElement;

    /**
     * constructor.
     *
     * Initialize the $mediaFilesArray
     */
    public function __construct($exportCourse)
    {
        $this->mediaFilesArray = [];
        $this->mediaFilesPath = substr(MEDIA_PATH_DEFAULT, 1);

        $this->exportedNONTestNodeArray = [];

        // make the module's own log dir if it's needed
        if (!is_dir(MODULES_IMPEXPORT_LOGDIR)) {
            mkdir(MODULES_IMPEXPORT_LOGDIR, 0777, true);
        }

        /**
         * sets the log file name that will be used from now on!
         */
        $this->logFile = MODULES_IMPEXPORT_LOGDIR . "export-" . $exportCourse .
        "_" . date('d-m-Y_His') . ".log";
    }

    /**
     * builds the xml object with the passed node and all of its children
     * The passed node is treated like it's a root node, so to export the
     * whole course it's enough to pass <course_id>_0, otherwhise pass
     * the nodeId (e.g. 110_1) you want to export.
     *
     * @param int $course_id the id of the course to export
     * @param string $nodeId the id of the node to export, in ADA format (e.g. xxx_yyy)
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param AMAImportExportDataHandler $dh the dataHandler used to retreive datas
     * @param boolean $mustRecur if set to true, will do recursion for exporting children
     * @param boolean $exportSurvey if set to true, will export nodes linked to surveys
     *
     * @return null|\DOMElement on error | DOMElement pointer to the exported root XML node
     *
     * @access public
     */
    public function exportCourseNodeChildren($course_id, $nodeId, &$domtree, &$dh, $exportSurvey, $mustRecur = false)
    {
        static $count = 0;
        // first export all passed node data
        $nodeInfo = $dh->getNodeInfo($nodeId);
        if (AMADB::isError($nodeInfo)) {
            return;
        }

        if (MODULES_TEST && !$exportSurvey && $nodeInfo['type'][0] == ADA_PERSONAL_EXERCISE_TYPE) {
            // check if nodeInfo['id'] is linked to a survey
            $dh_test = AMATestDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
            $surveysArr = $dh_test->testGetCourseSurveys(['id_corso' => $course_id,'id_nodo' => $nodeId]);
            if (AMADB::isError($surveysArr) || (!AMADB::isError($surveysArr) && is_array($surveysArr) && count($surveysArr) > 0)) {
                // do not export the node
                $this->logMessage(__METHOD__ . ' Skipping survey as per passed parameter, linked node has id=' . $nodeId);
                return;
            }
        }

        unset($nodeInfo['author']);

        // add the $nodeId to the exported nodes array
        if (!in_array($nodeId, $this->exportedNONTestNodeArray)) {
            $this->exportedNONTestNodeArray[] = $nodeId;
        }

        if ($count++ % 2) {
            $this->logMessage(__METHOD__ . ' Exporting ADA node_id=' . $nodeId . ' num. ' . ($count));
        }

        /**
         * NOTE: Following fields will be modified or omitted and must be calculated when importing:
         *
         * - id_node: is exported with '<course_id>_' prefix removed
         * - id_parent: is exported with '<course_id>_' prefix removed
         * - id_utente: WILL BE SELECTED BY THE USER DOING THE IMPORT (is the author, actually)
         * - id_posizione: exporting as an xml object, shall check if exists on table posizione when importing
         *
         */
        $nodeInfo['id'] = self::stripOffCourseId($course_id, $nodeId);
        $nodeInfo['parent_id'] = self::stripOffCourseId($course_id, $nodeInfo['parent_id']);

        // create XML node for current course node
        $XMLnode = $domtree->createElement("nodo");

        foreach ($nodeInfo as $name => $value) {
            $name = strtolower($name);
            if ($name === 'position') {
                continue;
            } elseif (in_array($name, self::$cDataElementNameForCourseNode)) {
                if ($name === 'text' || $name === 'icon') {
                    $value = $this->doPathExportingSubstitutions($name, $value, $course_id);
                }
                $XMLElementForCourseNode = self::buildCDATASection($domtree, $name, $value);
            } elseif (preg_match('/id/', $name)) {
                $XMLnode->setAttribute($name, $value);
            } elseif ($name === 'language') {
                $XMLElementForCourseNode = $domtree->createElement($name, self::getLanguageTableFromID($value));
            } else {
                $XMLElementForCourseNode = $domtree->createElement($name, $value);
            }

            if (isset($XMLElementForCourseNode)) {
                $XMLnode->appendChild($XMLElementForCourseNode);
                unset($XMLElementForCourseNode);
            }
        }
        // set the position object
        $XMLnode->appendChild(self::buildPosizioneXML($domtree, $nodeInfo['position']));
        unset($nodeInfo);

        // get the list of the links from the node
        $nodeLinksArr = $dh->getNodeLinks($nodeId);
        if (!empty($nodeLinksArr) && !AMADB::isError($nodeLinksArr)) {
            foreach ($nodeLinksArr as &$nodeLinkId) {
                $nodeLinkInfo = $dh->getLinkInfo($nodeLinkId);
                /**
                 * - id_autore: WILL BE SELECTED BY THE USER DOING THE IMPORT (is the author, actually)
                 */
                if (!AMADB::isError($nodeLinkInfo)) {
                    unset($nodeLinkInfo['autore']);
                    $nodeLinkInfo['id_nodo'] = self::stripOffCourseId($course_id, $nodeLinkInfo['id_nodo']);
                    $nodeLinkInfo['id_nodo_to'] = self::stripOffCourseId($course_id, $nodeLinkInfo['id_nodo_to']);
                    $nodeLinkInfo['id_LinkEsportato'] = $nodeLinkId;
                    $XMLnode->appendChild(self::buildLinkXML($domtree, $nodeLinkInfo));
                }
            }
        }
        unset($nodeLinksArr);
        unset($nodeLinkInfo);
        // end get links

        // get the list of external resources associated to the node
        $extResArr = $dh->getNodeResources($nodeId);
        if (!empty($extResArr) && !AMADB::isError($extResArr)) {
            foreach ($extResArr as &$extResId) {
                $extResInfo = $dh->getRisorsaEsternaInfo($extResId);
                if (!AMADB::isError($extResInfo)) {
                    $XMLnode->appendChild(self::buildExternalResourceXML($domtree, $extResInfo, $course_id));
                }
            }
        }
        unset($extResArr);
        unset($extResInfo);
        // end get external resources

        // get extended nodes
        $extendedNode = $dh->getExtendedNode($nodeId);
        if (!empty($extendedNode) && !AMADB::isError($extendedNode)) {
            $extendedNode['id_node'] = self::stripOffCourseId($course_id, $extendedNode['id_node']);
            $XMLnode->appendChild(self::buildExtendedNodeXML($domtree, $extendedNode));
        }
        unset($extendedNode);
        // end extended nodes

        // Okay, the node itself has been added to the XML, now do the recursion if asked to
        if ($mustRecur) {
            // get node children only having instance=0
            $childNodesArray = $dh->exportGetNodeChildren($nodeId, 0);
            if (!empty($childNodesArray) && !AMADB::isError($childNodesArray)) {
                foreach ($childNodesArray as &$childNodeId) {
                    $temp = self::exportCourseNodeChildren($course_id, $childNodeId, $domtree, $dh, $exportSurvey, $mustRecur);
                    if (!is_null($temp)) {
                        $XMLnode->appendChild($temp);
                    }
                }
            }
            unset($childNodesArray);
        }
        return $XMLnode;
    }

    /**
     * builds the xml object with the passed TEST node and all of its children
     *
     * @param int $course_id the id of the course to export
     * @param string $nodeId the id of the node to export, in ADA format (e.g. xxx_yyy)
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param AMATestDataHandler $dh_test the dataHandler used to retreive datas
     * @param DOMElement $XMLElement the element to append nodes to
     *
     * @access public
     */
    public function exportTestNodeChildren($course_id, $nodeId, &$domtree, &$dh_test, $XMLElement = null)
    {
        static $count = 0;
        $nodeInfo = $dh_test->testGetNode($nodeId);
        if (!AMADB::isError($nodeInfo)) {
            if (function_exists('memory_get_usage')) {
                $mem = memory_get_usage();
            } else {
                $mem = 'N/A';
            }

            $this->logMessage(__METHOD__ . ' Exporting ADA TEST Node num. ' . ($count++) . ' nodeId=' . $nodeId . ' memory_get_usage()=' . $mem);

            //          $XMLElement =& $XMLElement->appendChild(self::buildTestXML($domtree, $nodeInfo));
            if (is_null($XMLElement)) {
                $this->testNodeXMLElement->appendChild(self::buildTestXML($domtree, $nodeInfo));
            } else {
                $XMLElement = $XMLElement->appendChild(self::buildTestXML($domtree, $nodeInfo));
            }

            $childrenNodesArr = $dh_test->testGetNodesByParent($nodeId, null, ['id_istanza' => 0]);
            foreach ($childrenNodesArr as $childNode) {
                $this->exportTestNodeChildren($course_id, $childNode['id_nodo'], $domtree, $dh_test);
            }
        }
    }

    /**
     * strips off the course id and the separator character from an ADA node id.
     * (e.g. if value is 110_23 and course id is 110, will return 23)
     *
     * @param int $course_id the course id to be stripped off
     * @param string $value the string to be stripped off
     *
     * @return string
     *
     * @access public
     */
    public function stripOffCourseId($course_id, $value)
    {
        return str_replace($course_id . self::$courseSeparator, '', $value);
    }

    /**
     * builds a CDATA section
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param string $name the name of the XML object to be generated
     * @param string $value the contents of the generated CDATA section
     *
     * @return \DOMDocument the generated XML node
     *
     * @access public
     */
    public function buildCDATASection(&$domtree, &$name, &$value)
    {
        // creates a CDATA section
        $XMLCDATAElement = $domtree->createElement($name);
        $XMLCDATAElement->appendChild($domtree->createCDATASection((string) $value)) ;

        return $XMLCDATAElement;
    }

    /**
     * builds the XML for note 'extended_node' infos
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param array $extendedInfo the array for which XML will be generated
     *
     * @return \DOMDocument the generated XML node
     *
     * @access public
     */
    public function buildExtendedNodeXML(&$domtree, &$extendedInfo)
    {
        $XMLNodeExtended = $domtree->createElement("extended");
        foreach ($extendedInfo as $name => $value) {
            // all fields but language and id_node are cdatas
            if (preg_match('/id/', $name)) {
                $XMLNodeExtended->setAttribute($name, $value);
            } elseif ($name == 'language') {
                $XMLExtendedNodeElement = $domtree->createElement($name, self::getLanguageTableFromID($value));
            } else {
                $XMLExtendedNodeElement = self::buildCDATASection($domtree, $name, $value);
            }

            if (isset($XMLExtendedNodeElement)) {
                $XMLNodeExtended->appendChild($XMLExtendedNodeElement);
                unset($XMLExtendedNodeElement);
            }
        }
        return $XMLNodeExtended;
    }


    /**
     * builds the XML for external resource
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param array $extResInfo the array for which XML will be generated
     * @param int $course_id the id of the course that's being exported
     *
     * @return \DOMDocument the generated XML node
     *
     * @access public
     */
    public function buildExternalResourceXML(&$domtree, &$extResInfo, &$course_id)
    {
        $XMLNodeExtRes = $domtree->createElement("resource");

        foreach ($extResInfo as $name => $value) {
            if (in_array($name, self::$cDataElementNameForExtReS)) {
                if ($name === 'nome_file') {
                    // add to the mediaFilesArray
                    $fileName = ROOT_DIR . MEDIA_PATH_DEFAULT . $extResInfo['id_utente'] . '/' . $value;
                    // do the path substitution as if it was an icon
                    $this->doPathExportingSubstitutions('icon', $fileName, $course_id);
                } elseif ($name === 'id_utente') {
                    continue;
                }

                $XMLElementForCourseRes = self::buildCDATASection($domtree, $name, $value);
            } elseif ($name === 'lingua') {
                $XMLElementForCourseRes = $domtree->createElement($name, self::getLanguageTableFromID($value));
            } else {
                $XMLElementForCourseRes = $domtree->createElement($name, $value);
            }

            $XMLNodeExtRes->appendChild($XMLElementForCourseRes);
        }
        return $XMLNodeExtRes;
    }

    /**
     * called by exportTestNodeChildren to generate the XML for a test node
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param array $testElement the array for which XML will be generated
     *
     * @return \DOMDocument the generated XML node
     *
     * @access private
     */
    private function buildTestXML(&$domtree, &$testElement)
    {
        $XMLTest = $domtree->createElement('test');
        foreach ($testElement as $name => $value) {
            if ($name === 'id_corso' || $name === 'id_utente') {
                continue;
            } elseif (preg_match('/id_/', $name)) {
                if ($name === 'id_nodo') {
                    $name = 'id_nodoTestEsportato';
                }
                $XMLTest->setAttribute($name, $value ?? '');
            } elseif (in_array($name, self::$cDataElementNameForTest)) {
                // substitute url path with specail tag
                // $value = str_replace (parse_url(HTTP_ROOT_DIR, PHP_URL_PATH),'<http_path/>',$value);

                if ($name === 'icona' || $name === 'nome') {
                    $value = $this->doPathExportingSubstitutions('icon', $value, $testElement['id_corso']);
                } elseif ($name === 'testo') {
                    $value = $this->doPathExportingSubstitutions('text', $value, $testElement['id_corso']);
                }

                $XMLTest->appendChild(self::buildCDATASection($domtree, $name, $value));
            } else {
                $XMLTest->appendChild($domtree->createElement($name, $value ?? ''));
            }
        }

        return $XMLTest;
    }

    /**
     * builds the XML for node internal links
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param array $link the array for which XML will be generated
     *
     * @return \DOMDocument the generated XML node
     *
     * @access public
     */
    public function buildLinkXML(&$domtree, &$link)
    {
        $XMLNodeLink = $domtree->createElement("link");
        foreach ($link as $name => $value) {
            if ($name === 'posizione') {
                $XMLNodeLink->appendChild(self::buildPosizioneXML($domtree, $value));
            } else {
                $XMLNodeLink->setAttribute($name, $value);
            }
        }
        return $XMLNodeLink;
    }

    /**
     * builds the xml for a 'posizione' object
     *
     * @param \DOMDocument $domtree the XML object to append nodes to
     * @param array $posizione the array for which XML will be generated
     *
     * @return \DOMDocument the generated XML node
     *
     * @access public
     */
    public function buildPosizioneXML(&$domtree, &$posizione)
    {
        $XMLNodePosition = $domtree->createElement("posizione");
        foreach ($posizione as $name => $value) {
            if ($name == 0) {
                $name = 'x0';
            } elseif ($name == 1) {
                $name = 'y0';
            } elseif ($name == 2) {
                $name = 'x1';
            } elseif ($name == 3) {
                $name = 'y1';
            }

            $XMLNodePosition->setAttribute($name, $value);
        }
        return $XMLNodePosition;
    }

    /**
     * adds a filename to the mediaFilesArray
     *
     * @param int $course_id id of the course to which add the file
     * @param string $filePath filename to add
     *
     * @access private
     */
    private function addFileToMediaArray($course_id, $filePath)
    {
        // make filePath leading slash agnostic
        if ($filePath[0] !== '/') {
            $filePath = '/' . $filePath;
        }
        $filePath = html_entity_decode(urldecode($filePath), ENT_COMPAT | ENT_HTML401, ADA_CHARSET);
        if (is_file(ROOT_DIR . $filePath) || is_file($filePath)) {
            $this->logMessage(__METHOD__ . ' really adding to media array: ' . ROOT_DIR . $filePath);
            if (!isset($this->mediaFilesArray[$course_id])) {
                $this->mediaFilesArray[$course_id] = [];
            }
            if (!in_array($filePath, $this->mediaFilesArray[$course_id])) {
                array_push($this->mediaFilesArray[$course_id], $filePath);
            }
        } else {
            $this->logMessage(__METHOD__ . ' NOT ADDED to media array: ' . ROOT_DIR . $filePath);
        }
        if (isset($this->mediaFilesArray[$course_id])) {
            $this->logMessage(__METHOD__ . 'size of array IS: ' . count($this->mediaFilesArray[$course_id]));
        }
    }

    /**
     * Generates the actual zip file to be downloaded
     *
     * @param string $XMLFile the string containing the generate XML string
     * @return string|NULL created zip file name or null on error
     *
     * @access public
     */
    public function makeZipFile(&$XMLFile, $exportMedia = true)
    {
        $zipFileName = ADA_UPLOAD_PATH . self::$outputFileName . '_' .
                $_SESSION['sess_userObj']->username . '_' . date("Ymd") . '.zip';

        $zip = new ZipArchive();
        $zipStatus = $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString(XML_EXPORT_FILENAME, $XMLFile);

        $this->logMessage(__METHOD__ . ' Beginning zip file creation');
        if ($zipStatus === true) {
            $this->logMessage(__METHOD__ . ' ZIP file: ' . $zipFileName . ' was SUCCESFULLY CREATED');
        } else {
            $this->logMessage(__METHOD__ . ' ZipArchive::open call returned error code ' . $zipStatus . ' check php.net');
        }
        $this->logMessage(__METHOD__ . ' exportMedia is ' . (($exportMedia) ? 'true' : 'false'));
        $this->logMessage(__METHOD__ . ' mediaFilesArray has ' . count($this->mediaFilesArray) . ' elements');
        if ($exportMedia) {
            foreach ($this->mediaFilesArray as $course_id => $mediaFiles) {
                foreach ($mediaFiles as $mediaFile) {
                    $this->logMessage(__METHOD__ . ' file name guessed from node text is: ' . $mediaFile);

                    // build outFileName by removing services/media/<id author>/
                    // from the mediaFile
                    $regExp = '/' . preg_quote($this->mediaFilesPath, '/') . '\d+\/(.+)/';
                    if (preg_match($regExp, $mediaFile, $matches)) {
                        $outFileName = $matches[1];
                    } else {
                        $outFileName = $mediaFile;
                    }

                    $zipStatus = false;
                    if (is_file(ROOT_DIR . '/' . $mediaFile)) {
                        $zipStatus = $zip->addFile(ROOT_DIR . '/' . urldecode($mediaFile), $course_id . '/' . urldecode($outFileName));
                    }

                    $this->logMessage(__METHOD__ . (($zipStatus) ? ' SUCCESSFULLY ' : ' UNSUCCESSFULLY') .
                            ' zipped ' . ROOT_DIR . '/' . urldecode($mediaFile) . '==>' . $course_id . '/' . urldecode($outFileName));
                }
            }
        } else {
            $this->logMessage(__METHOD__ . ' media zipping skipped as per passed parameter');
        }

        $this->logMessage(__METHOD__ . ' closing zip, hang on...');

        $closedOk = $zip->close();

        $this->logMessage(__METHOD__ . ' is returning ' . (($closedOk) ? $zipFileName : 'null') . ', form now on it\'s just a matter of sending out headers and zip file');

        if ($closedOk) {
            return $zipFileName;
        } else {
            return null;
        }
    }

    /**
     * static method to get the table identifier corresponding to the passed language id
     * (e.g. on most installations, passing 'it' will return 1)
     *
     * @param int $languageID language id
     *
     * @return string empty if value <=0 is passed|AMAError on error|int retrieved table identifier on success
     *
     * @access public
     */
    public static function getLanguageTableFromID($languageID)
    {
        if (intval($languageID) <= 0) {
            return '';
        }
        $res = AMACommonDataHandler::getInstance()->findLanguageTableIdentifierByLangaugeId($languageID);
        return (AMADB::isError($res)) ? '' : $res;
    }

    /**
     * Recursively gets an array with passed node and all of its children
     * inlcuded values are name and id, used for json encoding when building
     * course tree for selecting which node to export.
     *
     * @param string $rootNode the id of the node to be treated as root
     * @param AMAImportExportDataHandler $dh the data handler used to retreive datas
     * @param string $mustRecur
     *
     * @return array
     *
     * @access public
     */
    public function getAllChildrenArray($rootNode, $dh, $mustRecur = true)
    {
        // first get all passed node data
        $nodeInfo = $dh->getNodeInfo($rootNode);

        $retarray =  ['id' => $rootNode, 'label' => $nodeInfo['name']];

        if ($mustRecur) {
            // get node children only having instance=0
            $childNodesArray = $dh->exportGetNodeChildren($rootNode, 0);
            if (!empty($childNodesArray) && !AMADB::isError($childNodesArray)) {
                $i = 0;
                $children = [];
                foreach ($childNodesArray as &$childNodeId) {
                    $children[$i++] = $this->getAllChildrenArray($childNodeId, $dh, $mustRecur);
                }
                $retarray['children'] = $children;
            }
        }
        return $retarray;
    }

    /**
     * does the proper string substitution on the path of the multimedia files
     * in node text and node icon and adds it to the media files array to be zipped
     *
     * @param string $name text or icon
     * @param string $value value to perform substitution on
     *
     * @return string the substitued string
     *
     * @access private
     */
    private function doPathExportingSubstitutions($name, $value, $course_id)
    {
        /**
         * check for media files inside the text or in the icon
         */
        if ($name === 'text') {
            // remove HTTP_ROOT_DIR so that it'll become
            // a relative path (no more, it will be substituted with other abs path)
            $value = str_replace(HTTP_ROOT_DIR, '<http_root/>', $value ?? '');
            $value = str_replace((string) parse_url(HTTP_ROOT_DIR, PHP_URL_PATH), '<http_path/>', $value);

            $regExp = '/\/?(' . preg_quote($this->mediaFilesPath, '/') . ')(\d+)\/([^\"]+)/';
        } elseif ($name === 'icon') {
            // substitute ROOT_DIR with a special tag that will
            // be used to restore ROOT_DIR in the import environment
            $value = str_replace(ROOT_DIR, '<root_dir/>', $value ?? '');
            $regExp = '/\/?(' . preg_quote($this->mediaFilesPath, '/') . ')(\d+)\/([^\"]+)/';
        }

        /**
         * run regExp on $value to check for media files
         */
        if (isset($regExp)) {
            if (preg_match_all($regExp, $value, $matches) > 0) {
                foreach ($matches[0] as $match) {
                    $this->logMessage(__METHOD__ . ' would add to media array of course ' . $course_id . ': ' . $match);
                    $this->addFileToMediaArray($course_id, $match);
                }

                $replacement = '<id_autore/>';
                $value = preg_replace($regExp, "/$1" . $replacement . "/$3", $value);
            }
            unset($regExp);
        }

        return $value;
    }

    /**
     * logs a message in the log file defined in the logFile private property.
     *
     * @param string $text the message to be logged
     *
     * @access private
     */
    private function logMessage($text)
    {
        // the file must exists, otherwise logger won't log
        if (!is_file($this->logFile)) {
            touch($this->logFile);
        }
        ADAFileLogger::log($text, $this->logFile);
    }
}

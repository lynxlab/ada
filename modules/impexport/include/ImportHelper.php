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
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Impexport\AMAImpExportDataHandler;
use Lynxlab\ADA\Module\Test\AMATestDataHandler;
use SimpleXMLElement;
use ZipArchive;

class ImportHelper
{
    /**
     * set to true for debugging purposes: it will make all the actual recursion,
     * but won't write anything on the DB.
     * @var boolean
     */
    private static $DEBUG = false;

    /**
     * filename being imported
     * @var string
     */
    private $importFile;

    /**
     * user chosen author ID to which assing the imported course(s).
     *
     * @var int
     */
    private $assignedAuthorID;

    /**
     * id that the course had at the time it has been exported.
     * Note that it's not set until the runImport method gets executed
     *
     * @var int
     */
    private $courseOldID;

    /**
     * array to map old test node id to new (generated) ones
     * @var array
     */
    private $testNodeIDMapping;

    /**
     * array to map old course node it to new (generated) ones
     * @var array
     */
    private $courseNodeIDMapping;

    /**
     * stores all the internal link between nodes that must be saved after the
     * last imported node insertions
     * @var array
     */
    private $linksArray;

    /**
     * common AMA data handler
     * @var AMACommonDataHandler
     */
    private $common_dh;

    /**
     * tester AMA data hanlder
     * @var AMAImpExportDataHandler
     */
    private $dh;

    /**
     * arrayto return to the caller filled with import recap datas
     * @var array
     */
    private $recapArray;

    /**
     * Module's own log file to log import progress, and if something goes wrong
     * @var string
     */
    private $logFile;

    /**
     * the course_id selected by the user to import into. If null means new course
     * @var int
     */
    private $selectedCourseID;

    /**
     * the service_level selected by the user for the imported course. Only used if import in new course
     * @var int
     */
    private $selectedServiceLevel;

    /**
     * the node id selected by the user to import the imported nodes as if they where its children
     * if null means new course and new nodes.
     * @var string
     */
    private $selectedNodeID;

    /**
     * starting time of the running import
     * @var string
     */
    private $importStartTime;

    /**
     * @var string char for separating courseId from nodeId (e.g. 110_0) in tabella nodo
     */
    public static $courseSeparator = '_';

    /**
     * XML nodes for which to iterate (or recur)
     * Should in the near or far future add some more nodes of this type,
     * simply add names to this array and everything should be fine, provided
     * ama.inc.php knows how to handle the datas.
     *
     * The constructor shall add tests and surveys if MODULES_TEST is set
     * also, it can add other stuff provided the _import* method is implemented
     *
     * @var array
     */
    private $specialNodes =  ['nodi'];

    /**
     * must save the selected tester that's usually stored in $_SESSION
     * because this class is going to write_close and open the session
     * several times.
     */
    private $selectedTester;

    /**
     * constructs a new importHelper using import file name, and assigned author ID from passed postdatas
     * Initialized the recapArray and the two data handlers
     *
     * @param array $postDatas the datas coming from a POST request
     */
    public function __construct($postDatas)
    {
        //      $this->_importFile = $postDatas['importFileName'];
        if (isset($_SESSION['importHelper']['filename'])) {
            $this->importFile = $_SESSION['importHelper']['filename'];
        } else {
            $this->importFile = null;
        }

        if (strlen($this->importFile) <= 0) {
            $this->importFile = $postDatas['importFileName'];
        }
        $this->assignedAuthorID = $postDatas['author'];
        $this->selectedTester = $_SESSION['sess_selected_tester'];

        $this->selectedCourseID = (isset($postDatas['courseID']) && intval($postDatas['courseID']) > 0) ? intval($postDatas['courseID']) : null;
        $this->selectedNodeID =  (isset($postDatas['nodeID']) && trim($postDatas['nodeID']) !== '') ? trim($postDatas['nodeID']) : null;
        $this->selectedServiceLevel = (isset($postDatas['serviceLevel']) && intval($postDatas['serviceLevel']) > 0) ? intval($postDatas['serviceLevel']) : DEFAULT_SERVICE_TYPE;

        /**
         * if the selected node does not contain the course separator character,
         * assume that the import is done on the node 0 of the course
         */
        if (!str_contains($this->selectedNodeID ?? '', self::$courseSeparator)) {
            $this->selectedNodeID .= self::$courseSeparator . '0';
        }

        $this->recapArray = [];
        $this->linksArray = null;

        $this->common_dh = AMACommonDataHandler::getInstance();
        $this->dh = AMAImpExportDataHandler::instance(MultiPort::getDSN($this->selectedTester));

        $this->importStartTime = $this->dh->dateToTs('now');

        unset($_SESSION['importHelper']['filename']);

        $this->progressInit();

        if (MODULES_TEST) {
            /**
             * entries will be processed in the order they appear.
             * So in this case it is IMPORTANT that surveys MUST be added AFTER the tests are imported
             *
             * Keep this in mind should you ever add other specialNodes to the array
             */
            $this->specialNodes = array_merge($this->specialNodes, ['tests', 'surveys']);
        }

        // make the module's own log dir if it's needed
        if (!is_dir(MODULES_IMPEXPORT_LOGDIR)) {
            mkdir(MODULES_IMPEXPORT_LOGDIR, 0o777, true);
        }

        /**
         * REMOVE THEESE WHEN YOU'VE FINISHED!!!!
         */
        //      $this->_selectedCourseID = 110;
        //      $this->_selectedNodeID = $this->_selectedCourseID.self::$courseSeparator."0";
    }

    /**
     * runs the actual import
     *
     * @return Ambigous AMAError on error |array recpArray on success
     *
     * @access public
     */
    public function runImport()
    {
        $count = 0;

        $zipFileName = $this->importFile;
        if (!str_starts_with($zipFileName, ADA_UPLOAD_PATH)) {
            $zipFileName = ADA_UPLOAD_PATH . $zipFileName;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipFileName)) {
            $XMLfile = $zip->getFromName(XML_EXPORT_FILENAME);
            $XMLObj = new SimpleXMLElement($XMLfile);

            $this->progressResetValues(substr_count($XMLfile, '</nodo>') +
                    substr_count($XMLfile, '<survey ') +
                    substr_count($XMLfile, '</test>'));

            foreach ($XMLObj as $objName => $course) {
                // first level object must be 'modello_corso'
                if ($objName === 'modello_corso') {
                    $count++;

                    // get the attributes as local vars
                    // e.g. attributed exportedId=107 becomes
                    // a local var named $exportedId, initialized to 107 as a string
                    foreach ($course->attributes() as $name => $val) {
                        ${$name} = (string) $val;
                    }
                    // as a result of this foreach we have a php var for any XML object attribute
                    // var_dump ($exportedId); should neither raise an error nor dump a null value.
                    $this->courseOldID = $exportedId ?? null;

                    /**
                     * sets the log file name that will be used from now on!
                     */
                    $this->logFile = MODULES_IMPEXPORT_LOGDIR . "import-" . $this->courseOldID .
                    "_" . date('d-m-Y_His') . ".log";

                    $this->logMessage('**** IMPORT STARTED at ' . date('d/m/Y H:i:s') . '(timestamp: ' . $this->dh->dateToTs('now') . ') ****');

                    $this->progressSetTitle((string) $course->titolo);
                    /**
                     * ADDS THE COURSE TO THE APPROPIATE TABLES
                     */
                    if (!self::$DEBUG) {
                        if (is_null($this->selectedCourseID)) {
                            $courseNewID = $this->addCourse($course);
                            /**
                             * this is a new course you want, first node
                             * is going to be the root of the course (i.e. zero)
                             * Setting selectedNodeID will make the running code
                             * do the trick!
                             */
                            $this->selectedNodeID = null;
                        } else {
                            $courseNewID = $this->selectedCourseID;
                        }

                        if (AMADB::isError($courseNewID)) {
                            return $courseNewID;
                        }
                    } else {
                        $courseNewID = 123 * $count;
                    }

                    /**
                     * NOW ADD  NODES, TESTS AND SURVEYS
                     */
                    foreach ($this->specialNodes as $groupName) {
                        $method = '_import' . ucfirst(strtolower($groupName));

                        $this->logMessage(__METHOD__ . ' Saving ' . $groupName . ' by calling method: ' . $method);

                        if ($groupName === 'tests'  || $groupName === 'surveys') {
                            // prepares the mapping array by emptying it
                            if ($groupName === 'tests') {
                                if (isset($this->testNodeIDMapping)) {
                                    unset($this->testNodeIDMapping);
                                }
                                $this->testNodeIDMapping = [];
                            }
                            // prepares the test data handler
                            $this->dh->disconnect();
                            $this->dh = AMATestDataHandler::instance(MultiPort::getDSN($this->selectedTester));
                        }

                        /**
                         * calls a method named _import<groupName> foreach special node.
                         * e.g. for nodes it will call _importNodi, for tests _importTests....
                         */
                        if (method_exists($this, $method) && !empty($course->$groupName)) {
                            $specialVal = $this->$method($course->{$groupName}, $courseNewID);
                            // if it's an error return it right away
                            if (AMADB::isError($specialVal)) {
                                $this->logMessage(__METHOD__ . ' Error saving ' . $groupName . '. DB returned the following:');
                                $this->logMessage(print_r($specialVal, true));

                                return $specialVal;
                            } else {
                                $this->logMessage(__METHOD__ . ' Saving ' . $groupName . ' successfully ended');
                                $this->recapArray[$courseNewID][$groupName] = $specialVal;
                            }
                        }

                        if ($groupName === 'nodi') {
                            // save all the links and clean the array
                            $this->saveLinks($courseNewID);
                            // after links have been saved, update inernal links pseudo html in proper nodes
                            $this->updateInternalLinksInNodes($courseNewID);
                        } elseif ($groupName === 'tests' || $groupName === 'surveys') {
                            // restores the import/export data handler
                            $this->dh->disconnect();
                            $this->dh = AMAImpExportDataHandler::instance(MultiPort::getDSN($this->selectedTester));
                            if ($groupName === 'tests') {
                                $this->updateTestLinksInNodes($courseNewID);
                            }
                        }
                    }
                    //                  $this->_updateTestLinksInNodes ( $courseNewID );
                } // if ($objName === 'modello_corso')
                $this->logMessage('**** IMPORT ENDED at ' . date('d/m/Y H:i:s') . '(timestamp: ' . $this->dh->dateToTs('now') . ') ****');
                $this->logMessage('If there\'s no zip log below, this is a multi course import: pls find unzip log at the end of the last course log');
            } // foreach ($XMLObj as $objName=>$course)

            // extract the zip files to the appropriate media dir
            $this->unzipToMedia($zip);

            $zip->close();
            if (!self::$DEBUG) {
                unlink($zipFileName);
            }
        }
        $this->progressDestroy();
        return $this->recapArray;
    }

    private function unzipToMedia($zip)
    {
        if ($zip->numFiles > 0) {
            $this->progressSetStatus('COPY');

            $this->logMessage(__METHOD__ . ' Copying files from zip archive, only failures will be logged here');
            $destDir = ROOT_DIR . MEDIA_PATH_DEFAULT . $this->assignedAuthorID;
            if (self::$DEBUG) {
                print_r($destDir);
            }
            if (!is_dir($destDir)) {
                mkdir($destDir, 0o777, true);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);

                if ($fileinfo['basename'] !== XML_EXPORT_FILENAME) {
                    /**
                     * strips off course id from the directory of the file to be copied
                     * e.g. ZIPFILE/107/exerciseMedia/foo.png will be copied to:
                     *      /services/media/<AUTHORID>/exerciseMedia/foo.png
                     */
                    if (preg_match('/^[0-9]+(\/{1}.+)$/', $fileinfo['dirname'], $matches)) {
                        $outDir = $destDir . $matches[1];
                    } else {
                        $outDir = $destDir;
                    }
                    // attempts to make outdir
                    if (!is_dir($outDir)) {
                        mkdir($outDir, 0o777, true);
                    }
                    if (!copy("zip://" . $zip->filename . "#" . $filename, $outDir . "/" . $fileinfo['basename'])) {
                        $this->logMessage(__METHOD__ . ' Could not copy from zip: source=' . $filename . ' dest=' . $outDir . "/" . $fileinfo['basename']);
                    }
                }
            }
            $this->logMessage(__METHOD__ . ' Done copying from zip archive');
        }
    }

    /**
     * Gets a position ID from a position array.
     * If it's needed, it will save a new row in the posizione table.
     *
     * @param array $positionObj
     *
     * @return AMAError on error | number position id
     *
     * @access private
     */
    private function getPosition($positionObj)
    {
        $pos_ar = [];
        $pos_ar[0] = (int) $positionObj['x0'];
        $pos_ar[1] = (int) $positionObj['y0'];
        $pos_ar[2] = (int) $positionObj['x1'];
        $pos_ar[3] = (int) $positionObj['y1'];

        $this->logMessage(__METHOD__ . ' passed position \n' . print_r($pos_ar, true));

        // gets a position id by checkin if it's already in the DB
        // or adding a new position row if needed.

        if (($id = $this->dh->getIdPosition($pos_ar)) != -1) {
            // if a position is found in the posizione table, the use it
            $id_posizione = $id;
        } else {
            // add row to table "posizione"
            if (AMADB::isError($res = $this->dh->addPosition($pos_ar))) {
                $this->logMessage(__METHOD__ . ' Error adding position! DB returned the following:');
                $this->logMessage(print_r($res, true));

                return new AMAError($res->getMessage());
            } else {
                // get id of position just added
                $id_posizione = $this->dh->getIdPosition($pos_ar);
            }
        }
        $this->logMessage('Successfully got position_id=' . $id_posizione);

        return $id_posizione;
    }

    /**
     * Builds an extended node array from the passed XML element.
     * The extended node datas are then merged to the array passed
     * to the add_node method that saves everything up in the DB.
     *
     * @param SimpleXMLElement $extObj the element to be saved
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug|AMAError on error|true on success
     *
     * @access private
     */
    private function buildExtendedArray($extObj, $courseNewID)
    {
        $extdendedArr = [];
        foreach ($extObj as $name => $value) {
            $extdendedArr[$name] = (string) $value;
        }

        $extdendedArr['id'] = $courseNewID . self::$courseSeparator . $extObj['id_node'];
        $extdendedArr['lingua'] = self::getLanguageIDFromTable($extdendedArr['language']);
        unset($extdendedArr['language']);

        $this->logMessage(__METHOD__ . ' Saving extended node info:');
        $this->logMessage(print_r($extdendedArr, true));

        unset($extdendedArr['id']);
        $retval = $extdendedArr;

        if (!isset($this->recapArray[$courseNewID]['extended-nodes'])) {
            $this->recapArray[$courseNewID]['extended-nodes'] = 1;
        } else {
            $this->recapArray[$courseNewID]['extended-nodes']++;
        }

        $this->logMessage(__METHOD__ . ' Successfully built extended node array: ' . print_r($retval, true));

        return $retval;
    }

    /**
     * Builds an external resource array from the passed XML element.
     * Resources are then added to the array passed to the add_node method
     * that saves everything up in the DB.
     *
     * @param SimpleXMLElement $resObj
     * @param string  $nodeID the id of the node it's saving resources for
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug|AMAError on error|int inserted id on success
     *
     * @access private
     */
    private function buildResourceArray($resObj, $nodeID, $courseNewID)
    {
        $resourceArr = [];
        foreach ($resObj as $name => $value) {
            $resourceArr[$name] = (string) $value;
        }

        if (!isset($resourceArr['lingua'])) {
            $resourceArr['lingua'] = 0;
        }
        $resourceArr['lingua'] = self::getLanguageIDFromTable($resourceArr['lingua']);
        $resourceArr['id_utente'] = $this->assignedAuthorID;

        $retval = $resourceArr;

        if (!isset($this->recapArray[$courseNewID]['resource'])) {
            $this->recapArray[$courseNewID]['resource'] = 1;
        } else {
            $this->recapArray[$courseNewID]['resource']++;
        }

        $this->logMessage(__METHOD__ . ' Successfully built external resource array: ' . print_r($retval, true));

        return $retval;
    }

    /**
     * Builds an internal link array from the passed XML element.
     * Links are saved after all the nodes have been imported in the
     * _saveLinks method that does also the convertion from exported
     * node id to imported ones.
     *
     * @param SimpleXMLElement $linkObj the element to be saved
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug|AMAError on error|true on success
     *
     * @access private
     */
    private function buildLinkArray($linkObj, $courseNewID)
    {
        $linkArr = [];

        foreach ($linkObj->attributes() as $name => $value) {
            $linkArr[$name] = (string) $value;
        }

        if ($linkObj->posizione) {
            $linkArr['posizione'][0] = (int)  $linkObj->posizione['x0'];
            $linkArr['posizione'][1] = (int)  $linkObj->posizione['y0'];
            $linkArr['posizione'][2] = (int)  $linkObj->posizione['x1'];
            $linkArr['posizione'][3] = (int)  $linkObj->posizione['y1'];
        }

        unset($linkArr['id_LinkEsportato']);
        /**
         * keep the old node id in the links, they will be converted into new ones
         * just after all the nodes have been inserted
         */
        $linkArr['id_nodo'] = $this->courseOldID . self::$courseSeparator . $linkArr['id_nodo'];
        $linkArr['id_nodo_to'] = $this->courseOldID . self::$courseSeparator . $linkArr['id_nodo_to'];
        $linkArr['id_utente'] = $this->assignedAuthorID;
        $linkArr['data_creazione'] = Utilities::ts2dFN(time());

        $retval = $linkArr;

        // the recapArray shall be updated when actually saving links in the _saveLinks method

        $this->logMessage(__METHOD__ . ' Successfully built link element: ' . print_r($retval, true));

        return $retval;
    }

    /**
     * Iterative method saving the surveys entries in the DB
     *
     * @param SimpleXMLElement $xml the element from which the recursion starts (i.e. root node)
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug |AMAError on error |int number of imported nodes on success
     *
     * @access private
     */
    private function importSurveys($xml, $courseNewID)
    {
        $count = 0;

        if (self::$DEBUG) {
            echo '<pre>' . __METHOD__ . PHP_EOL;
        }

        foreach ($xml->children() as $survey) {
            foreach ($survey->attributes() as $name => $value) {
                // export every xml <survey> tag attribute as a local var
                ${$name} = (string) $value;
            }
            // if the test referenced by the id_nodoTestEsportato is not set
            // there's no corresponding test in the DB and we cannot save :(
            $id_nodoTestEsportato ??= -1;
            $id_nodo ??= null;
            if (!is_null($id_nodo) && isset($this->testNodeIDMapping[$id_nodoTestEsportato])) {
                if (!self::$DEBUG) {
                    // saves the survey row in the DB

                    $this->logMessage(__METHOD__ . ' Saving survey: id_corso=' . $courseNewID . ' id_test=' . $this->testNodeIDMapping[$id_nodoTestEsportato] . ' id_nodo=' . $courseNewID . self::$courseSeparator . $id_nodo);

                    $surveyResult = $this->dh->testAddCourseTest(
                        $courseNewID,
                        $this->testNodeIDMapping[$id_nodoTestEsportato],
                        $courseNewID . self::$courseSeparator . $id_nodo
                    );
                } else {  // prints out some basic info if in debug mode
                    print_r("id_corso=" . $courseNewID . PHP_EOL);
                    print_r("id_test=" . $this->testNodeIDMapping[$id_nodoTestEsportato] . PHP_EOL);
                    print_r("id_nodo=" . $courseNewID . self::$courseSeparator . $id_nodo . PHP_EOL);
                    $surveyResult = true;
                }
                // if it's an error return it right away, as usual
                if (AMADB::isError($surveyResult)) {
                    $this->logMessage(__METHOD__ . ' Error saving survey. DB returned the following:');
                    $this->logMessage(print_r($surveyResult, true));

                    return $surveyResult;
                } else {
                    $count++;
                    $this->progressIncrement();

                    $this->logMessage(__METHOD__ . ' Successfully saved survey');
                }
            }
        }
        if (self::$DEBUG) {
            echo '</pre>';
        }
        return $count;
    }

    /**
     * Recursive method saving a testnode in the DB and then recurring over all of its children
     *
     * @param SimpleXMLElement $xml the element from which the recursion starts (i.e. root node)
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug |AMAError on error |int number of imported nodes on success
     *
     * @access private
     */
    private function importTests($xml, $courseNewID)
    {

        static $savedCourseID = 0;
        static $count = 0;
        static $depth = 0;

        /**
         * needed to count how many test were imported
         * in each disctinct course
         */
        if ($savedCourseID != $courseNewID) {
            $savedCourseID = $courseNewID;
            $count = 0;
        }

        if (self::$DEBUG) {
            echo '<pre>' . __METHOD__ . PHP_EOL;
        }

        $outArr = [];
        $currentElement = $xml;

        $oldNodeID = (string) $currentElement['id_nodoTestEsportato'];
        $parentNodeID = (string) $currentElement['id_nodo_parent'];
        $rootNodeID = (string) $currentElement['id_nodo_radice'];
        $refNodeID = (string) $currentElement['id_nodo_riferimento'];

        foreach ($currentElement->children() as $name => $value) {
            if ($name === 'test') {
                continue;
            } else {
                $temp = (string) $value;
                if (!empty($temp)) {
                    $outArr[$name] = $temp;
                } else {
                    $outArr[$name] = null;
                }
            }
        }

        if (!empty($outArr)) {
            // make some adjustments to invoke the test datahandler's testAddNode method

            $this->logMessage(__METHOD__ . ' Saving test node. course id=' . $courseNewID .
                    ' so far ' . $count . ' nodes have been imported');

            $count++;
            $this->progressIncrement();

            $outArr['id_corso'] = $courseNewID;
            $outArr['id_posizione'] = (string) $currentElement['id_posizione'];
            $outArr['id_utente'] = $this->assignedAuthorID;
            $outArr['id_istanza'] = (string) $currentElement['id_istanza'];

            if (isset($this->testNodeIDMapping[$parentNodeID])) {
                $outArr['id_nodo_parent'] = $this->testNodeIDMapping[$parentNodeID];
            }
            if (isset($this->testNodeIDMapping[$rootNodeID])) {
                $outArr['id_nodo_radice'] = $this->testNodeIDMapping[$rootNodeID];
            }
            if (isset($refNodeID) && $refNodeID != '') {
                if (isset($this->courseNodeIDMapping[$refNodeID])) {
                    $outArr['id_nodo_riferimento'] = $this->courseNodeIDMapping[$refNodeID];
                }
            }

            $outArr['icona'] = (!is_null($outArr['icona'])) ? str_replace('<root_dir/>', ROOT_DIR, $outArr['icona']) : null;
            $outArr['icona'] = (!is_null($outArr['icona'])) ? str_replace('<id_autore/>', $this->assignedAuthorID, $outArr['icona']) : null;

            $outArr['testo'] = (!is_null($outArr['testo'])) ? str_replace('<id_autore/>', $this->assignedAuthorID, $outArr['testo']) : null;
            $outArr['testo'] = (!is_null($outArr['testo'])) ? str_replace('<http_root/>', HTTP_ROOT_DIR, $outArr['testo']) : null;
            $outArr['testo'] = (!is_null($outArr['testo'])) ? str_replace('<http_path/>', parse_url(HTTP_ROOT_DIR, PHP_URL_PATH) . '/', $outArr['testo']) : null;

            $outArr['nome'] = (!is_null($outArr['nome'])) ? str_replace('<id_autore/>', $this->assignedAuthorID, $outArr['nome']) : null;
            $outArr['nome'] = (!is_null($outArr['nome'])) ? str_replace('<http_root/>', HTTP_ROOT_DIR, $outArr['nome']) : null;
            $outArr['nome'] = (!is_null($outArr['nome'])) ? str_replace('<http_path/>', parse_url(HTTP_ROOT_DIR, PHP_URL_PATH) . '/', $outArr['nome']) : null;

            unset($outArr['data_creazione']);
            unset($outArr['versione']);
            unset($outArr['n_contatti']);

            // prints out some basic info if in debug mode
            if (self::$DEBUG) {
                echo "count=" . $count . PHP_EOL;
                if ($count == 1) {
                    //              if ($outArr['id']==$courseNewID.self::$courseSeparator.'1') {
                    print_r($outArr);
                    echo "<hr/>";
                } else {
                    var_dump($outArr['id_nodo_parent']);
                    var_dump($outArr['nome']);
                    var_dump($outArr['tipo']);
                }
            }

            /**
             * ACTUALLY SAVE THE NODE!! YAHOOOO!!!
             */
            if (!self::$DEBUG) {
                $this->logMessage('Saving test node with a call to testAddNode test data handler, passing:');
                $this->logMessage(print_r($outArr, true));

                $newNodeID = $this->dh->testAddNode($outArr);
                // if it's an error return it right away, as usual
                if (AMADB::isError($newNodeID)) {
                    $this->logMessage(__METHOD__ . ' Error saving test node. DB returned the following:');
                    $this->logMessage(print_r($newNodeID, true));

                    return $newNodeID;
                } else {
                    $this->logMessage(__METHOD__ . ' Successfully saved test node');
                }
            } else {
                $newNodeID = 666;
            }

            $this->testNodeIDMapping[$oldNodeID] = $newNodeID;
        }

        // recur the children
        if ($currentElement->test) {
            for ($i = 0; $i < count($currentElement->test); $i++) {
                $this->logMessage(__METHOD__ . ' RECURRING TEST NODES: depth=' . (++$depth) .
                        ' This test has ' . count($currentElement->test) . ' kids and is the brother n.' . $i);

                $this->importTests($currentElement->test[$i], $courseNewID);
            }
        }

        if (self::$DEBUG) {
            echo '</pre>';
        }
        $depth--;
        return $count;
    }

    /**
     * Recursive method saving a node in the DB and then recurring over all of its children
     *
     * @param SimpleXMLElement $xml the element from which the recursion starts (i.e. root node)
     * @param int $courseNewID the generated ID of the imported course
     *
     * @return boolean on debug |AMAError on error |int number of imported nodes on success
     *
     * @access private
     */
    private function importNodi($xml, $courseNewID)
    {
        static $savedCourseID = 0;
        static $count = 0;
        static $depth = 0;

        /**
         * needed to count how many nodes were imported
         * in each disctinct course
         */
        if ($savedCourseID != $courseNewID) {
            $savedCourseID = $courseNewID;
            $count = 0;
        }

        if (self::$DEBUG) {
            echo '<pre>' . __METHOD__ . PHP_EOL;
        }

        $outArr = [];
        $resourcesArr = [ 0 => 'unused' ];

        $currentElement = $xml;

        $outArr ['id'] = (string) $currentElement['id'];
        $outArr ['id_parent'] = (string) $currentElement['parent_id'];

        foreach ($currentElement->children() as $name => $value) {
            if ($name === 'posizione') {
                $outArr['pos_x0'] = (int) $value['x0'];
                $outArr['pos_y0'] = (int) $value['y0'];
                $outArr['pos_x1'] = (int) $value['x1'];
                $outArr['pos_y1'] = (int) $value['y1'];
            } elseif ($name === 'resource') {
                // must do an array push because the method that saves the resources expects
                // the array to start at index 1
                array_push($resourcesArr, $this->buildResourceArray($value, $courseNewID . self::$courseSeparator . $outArr['id'], $courseNewID));
                // NOTE: the files will be copied later on, together with the others
            } elseif ($name === 'link') {
                // this array is saved in the _saveLinks method
                $this->linksArray[] = $this->buildLinkArray($value, $courseNewID);
            } elseif ($name === 'extended') {
                // it's enough to merge the extended array to the outArr and then add_node saves 'em all
                $outArr = array_merge($outArr, $this->buildExtendedArray($value, $courseNewID));
            } elseif ($name === 'nodo') {
                continue;
            } else {
                $outArr[$name] = (string) $value;
            }
        }

        if ($outArr['id'] != '') {
            $this->logMessage(__METHOD__ . ' Saving course node. course id=' . $courseNewID .
                    ' so far ' . $count . ' nodes have been imported');

            // add the node to the counted elements
            $count++;
            $this->progressIncrement();

            // make some adjustments to invoke the datahandler's add_node method

            if (!is_null($outArr['id_parent']) && strtolower($outArr['id_parent']) != 'null' && ($outArr['id_parent'] != '')) {
                $oldNodeID = $this->courseOldID . self::$courseSeparator . $outArr['id_parent'];
                if (isset($this->courseNodeIDMapping[$oldNodeID])) {
                    $outArr['parent_id'] = $this->courseNodeIDMapping[$oldNodeID];
                } else {
                    $outArr['parent_id'] = $courseNewID . self::$courseSeparator . $outArr['id_parent'];
                }
            }
            //          else
            //          {
            //              $outArr['parent_id'] = null;
            //          }
            unset($outArr['id_parent']);

            $outArr['id_course'] = $courseNewID;
            $outArr['creation_date'] = 'now';
            $outArr['id_node_author'] = $this->assignedAuthorID;
            $outArr['version'] = 0;
            $outArr['contacts'] = 0;

            $outArr['icon'] = str_replace('<root_dir/>', ROOT_DIR, $outArr['icon']);
            $outArr['icon'] = str_replace('<id_autore/>', $this->assignedAuthorID, $outArr['icon']);
            $outArr['icon'] = str_replace('<http_path/>', parse_url(HTTP_ROOT_DIR, PHP_URL_PATH), $outArr['icon']);

            $outArr['text'] = str_replace('<id_autore/>', $this->assignedAuthorID, $outArr['text']);
            $outArr['text'] = str_replace('<http_root/>', HTTP_ROOT_DIR, $outArr['text']);
            $outArr['text'] = str_replace('<http_path/>', parse_url(HTTP_ROOT_DIR, PHP_URL_PATH), $outArr['text']);

            // oldID is needed below, for creating the array that maps the old node id
            // to the new node id. This must be done AFTER node is saved.
            $oldID = $outArr['id'];
            unset($outArr['id']); // when a generated id will be used and comment below

            // set array of resources to be saved together with the node
            // for some unbelievable reason the addMedia method called by add_node
            // expects the resources array to start at index 1, so let's make it happy.
            unset($resourcesArr[0]);
            $outArr['resources_ar'] =  $resourcesArr;

            // sets the parent if an exported root node is made child in import
            if (is_null($this->selectedNodeID) && $count == 1) {
                $outArr['parent_id'] = null;
            } elseif ((!isset($outArr['parent_id']) || $count == 1) && !is_null($this->selectedNodeID)) {
                $outArr['parent_id'] = $this->selectedNodeID;
            }

            // prints out some basic info if in debug mode
            if (self::$DEBUG) {
                echo "count=" . $count . PHP_EOL;
                if ($count == 1) {
                    //              if ($outArr['id']==$courseNewID.self::$courseSeparator.'1') {
                    print_r($outArr);
                    echo "<hr/>";
                } else {
                    var_dump($outArr['id']);
                    var_dump($outArr['parent_id']);
                    var_dump($outArr['name']);
                }
            }

            /**
             * ACTUALLY SAVE THE NODE!! YAHOOOO!!!
             */
            if (!self::$DEBUG) {
                // $outArr['id'] = $courseNewID.self::$courseSeparator.$outArr['id'];

                $this->logMessage('Saving course node, passing:');
                $this->logMessage(print_r($outArr, true));

                $addResult = $this->dh->addNode($outArr);
                // if it's an error return it right away, as usual
                if (AMADB::isError($addResult)) {
                    $this->logMessage(__METHOD__ . ' Error saving course node. DB returned the following:');
                    $this->logMessage(print_r($addResult, true));
                    return $addResult;
                } else {
                    // add node to the course node mapping array,
                    // keys are exported node ids, values are imported ones
                    $this->courseNodeIDMapping[$this->courseOldID . self::$courseSeparator . $oldID] = $addResult;
                    $this->logMessage(__METHOD__ . ' Successfully saved course node');
                }
            }
        }

        // recur the children
        if ($currentElement->nodo) {
            for ($i = 0; $i < count($currentElement->nodo); $i++) {
                $this->logMessage(__METHOD__ . ' RECURRING COURSE NODES: depth=' . (++$depth) .
                        ' This node has ' . count($currentElement->test) . ' kids and is the brother n.' . $i);

                $this->importNodi($currentElement->nodo[$i], $courseNewID);
            }
        }
        if (self::$DEBUG) {
            echo "</pre>";
        }
        $depth--;
        return $count;
    }


    /**
     * Adds a course to the modello_corso table of the current provider,
     * and then adds a service to the platform and links it to the provider
     *
     * @param SimpleXMLElement $course the root course node to be saved
     *
     * @return AMAError on error | int generated course id on success
     *
     * @access private
     */
    private function addCourse($course)
    {
        // gets all object inside 'modello_corso' that are NOT
        // of type 'nodi', 'tests', 'surveys'

        // holds datas of the course to be saved
        $courseArr = [];
        foreach ($course as $nodeName => $nodeValue) {
            if (!in_array($nodeName, $this->specialNodes)) {
                $courseArr[$nodeName] = (string) $nodeValue;
            }
        }

        $courseArr['id_autore'] = $this->assignedAuthorID;
        $courseArr['d_create'] = Utilities::ts2dFN(time());
        $courseArr['d_publish'] = null;
        $courseArr['service_level'] = $this->selectedServiceLevel;

        $this->logMessage('Adding course model by calling data handler add_course with the following datas:');
        $this->logMessage(print_r($courseArr, true));

        $rename_count = 0;
        do {
            $courseNewID = $this->dh->addCourse($courseArr);
            if (AMADB::isError($courseNewID)) {
                if (strlen($courseArr['nome']) > 32) { // 32 is the size of the field in the database
                    $this->logMessage('Generated name will be over maximum allowed size, I\'ll give up and generate an error message.');
                    $rename_count = -1; // this will force an exit from the while loop
                } else {
                    $this->logMessage($courseArr['nome'] . ' will generate a duplicate key, rename attempt #' . ++$rename_count);
                    $courseArr['nome'] .= '-DUPLICATE';
                }
            } else {
                $this->logMessage('Successfully created new corse with name:' . $courseArr['nome'] . ' and id: ' . $courseNewID);
            }
        } while (AMADB::isError($courseNewID) && $rename_count >= 0);

        if (!AMADB::isError($courseNewID)) {
            $retval = $courseNewID;
            // add a row in common.servizio
            $service_dataAr = [
                    'service_name' => $courseArr['titolo'],
                    'service_description' => $courseArr['descr'],
                    'service_level' => 1,
                    'service_duration' => 0,
                    'service_min_meetings' => 0,
                    'service_max_meetings' => 0,
                    'service_meeting_duration' => 0,
            ];
            $id_service = $this->common_dh->addService($service_dataAr);
            if (!AMADB::isError($id_service)) {
                $tester_infoAr = $this->common_dh->getTesterInfoFromPointer($this->selectedTester);
                if (!AMADB::isError($tester_infoAr)) {
                    $id_tester = $tester_infoAr[0];
                    $result = $this->common_dh->linkServiceToCourse($id_tester, $id_service, $courseNewID);
                    if (AMADB::isError($result)) {
                        $retval = $result;
                    }
                } else {
                    $retval = $tester_infoAr; // if (!AMADB::isError($tester_infoAr))
                }
            } else {
                $retval = $id_service; // if (!AMADB::isError($id_service))
            }
        } else {
            $retval = $courseNewID; // if (!AMADB::isError($courseNewID))
        }

        if (AMADB::isError($retval)) {
            $this->logMessage('Adding course (modello_corso table) has FAILED! Pls find details below:');
            $this->logMessage(print_r($retval, true));
        } else {
            $this->logMessage('Adding course OK! Generated course_id=' . $retval);
        }

        return $retval;
    }

    /**
     * updates the internal link ADA-html internal link tag with the new node id to ling to.
     * e.g. <LINK TYPE="INTERNAL" VALUE="8"> will be converted in <LINK TYPE="INTERNAL" VALUE="NEWID">
     *
     * @param int $courseNewID
     *
     * @access private
     */
    private function updateInternalLinksInNodes($courseNewID)
    {
        $this->logMessage(__METHOD__ . ' Updating nodes that have an internal link');
        $this->logMessage(__METHOD__ . ' Timestamp used to select node to update is: ' . $this->importStartTime);

        $nodesToUpdate = $this->dh->getNodesWithInternalLinkForCourse($courseNewID, $this->importStartTime);

        if (!AMADB::isError($nodesToUpdate)) {
            $this->logMessage(__METHOD__ . " Candidates for updating: \n" . print_r($nodesToUpdate, true));
            $this->logMessage(__METHOD__ . " This is the replacement NODE ids array \n" . print_r($this->courseNodeIDMapping, true));

            /**
             * build up source and replacements array
             * replacements are going to have a random string as
             * a fake attribute to prevent cyclic substitutions
             * e.g. if we have in text:
             *
             * blablabla... value='1'.... blablabla.... value='7'...blablabla value='1'...
             *
             * and in the mapping array: 1=>7 .... 7=>23....
             *
             * the result will be that all 1 become 7, and all 7 become 23 and at the and
             * all of the three links will point to 23.
             */

            $randomStr = '#' . substr(md5(time()), 0, 8);

            $prefix = '<LINK TYPE="INTERNAL" VALUE="';
            $suffix = '">';
            $suffix2 = '"' . $randomStr . '>';

            $search = [];
            $replace = [];

            foreach ($this->courseNodeIDMapping ?? [] as $oldID => $newID) {
                $oldID = str_replace($this->courseOldID . self::$courseSeparator, '', $oldID);
                $newID = str_replace($courseNewID . self::$courseSeparator, '', $newID);

                $search[] = $prefix . $oldID . $suffix;
                $replace[] = $prefix . $newID . $suffix2;
            }


            foreach ($nodesToUpdate as $arrElem) {
                foreach ($arrElem as $nodeID) {
                    $this->logMessage(__METHOD__ . ' UPDATING NODE id=' . $nodeID);
                    $nodeInfo = $this->dh->getNodeInfo($nodeID);
                    $nodeInfo['text'] = str_ireplace($search, $replace, $nodeInfo['text']);
                    // strip off the random fake attribute
                    $nodeInfo['text'] = str_ireplace($randomStr, '', $nodeInfo['text']);
                    $this->dh->setNodeText($nodeID, $nodeInfo['text']);
                }
            }
        }
    }

    /**
     * updates the links to the test nodes inside the testo fields of the node
     * the id of the test MUST be substituted with the generated ones
     *
     * @param int $courseNewID
     *
     * @access private
     */
    private function updateTestLinksInNodes($courseNewID)
    {
        $this->logMessage(__METHOD__ . ' Updating nodes that have a link to a test:');

        $nodesToUpdate = $this->dh->getNodesWithTestLinkForCourse($courseNewID, $this->importStartTime);

        if (!AMADB::isError($nodesToUpdate)) {
            $this->logMessage(__METHOD__ . " Candidates for updating: \n" . print_r($nodesToUpdate, true));
            $this->logMessage(__METHOD__ . " This is the replacement TEST ids array \n" . print_r($this->testNodeIDMapping, true));

            $delimiter = '#';
            $regExp = '/' . preg_quote('id_test=', '/') . '(\d+)' . '/';

            $search = [];
            $replace = [];

            foreach ($this->testNodeIDMapping as $oldID => $newID) {
                $search[] = 'id_test=' . $oldID . $delimiter;
                $replace[] = 'id_test=' . $newID;
            }

            foreach ($nodesToUpdate as $arrElem) {
                foreach ($arrElem as $nodeID) {
                    $this->logMessage(__METHOD__ . ' UPDATING NODE id=' . $nodeID);
                    $nodeInfo = $this->dh->getNodeInfo($nodeID);
                    /**
                     * First put a delimiter just after the linked id_test to prevent
                     * this situation:
                     *  id_test=2 ..blablabla... id_test=24
                     * without an end delimiter, how can you subsitute id_test=2
                     * BUT NOT id_test=24 ???
                     *
                     * NOTE: This is done in the 3rd argument of str_ireplace
                     */

                    $nodeInfo['text'] = str_ireplace(
                        $search,
                        $replace,
                        preg_replace($regExp, 'id_test=$1' . $delimiter, $nodeInfo['text'])
                    );

                    $this->dh->setNodeText($nodeID, $nodeInfo['text']);
                }
            }
        } else {
            $this->logMessage(__METHOD__ . ' Error in retreiving nodes to be updated: ' . $nodesToUpdate->getMessage());
        }
    }

    /**
     * Saves into the DB all the intrenal links between nodes.
     * Before adding the row to the DB, it maps the exported
     * id nodes to the imported ones. That's the reason why
     * this must be execute AFTER all nodes have been imported.
     *
     * @param int $courseNewID
     */
    private function saveLinks($courseNewID)
    {
        if (is_array($this->linksArray)) {
            $this->logMessage(__METHOD__ . ' Saving internal links for course');
            foreach ($this->linksArray as $num => $linkArray) {
                if (
                    isset($this->courseNodeIDMapping[$linkArray['id_nodo']]) &&
                    isset($this->courseNodeIDMapping[$linkArray['id_nodo_to']])
                ) {
                    $linkArray['id_nodo'] = $this->courseNodeIDMapping[$linkArray['id_nodo']];
                    $linkArray['id_nodo_to'] = $this->courseNodeIDMapping[$linkArray['id_nodo_to']];

                    $res = $this->dh->addLink($linkArray);

                    if (!AMADB::isError($res)) {
                        if (!isset($this->recapArray[$courseNewID]['links'])) {
                            $this->recapArray[$courseNewID]['links'] = 1;
                        } else {
                            $this->recapArray[$courseNewID]['links']++;
                        }
                        $this->logMessage(__METHOD__ . ' link # ' . $num . ' successfully saved. id_nodo=' . $linkArray['id_nodo'] . ' id_nodo_to=' . $linkArray['id_nodo_to']);
                    } else {
                        $this->logMessage(__METHOD__ . ' link # ' . $num . ' FAILED! id_nodo=' . $linkArray['id_nodo'] . ' id_nodo_to=' . $linkArray['id_nodo_to']);
                    }
                } else {
                    $this->logMessage(__METHOD__ . ' could not find a match in the mapping array. id_nodo=' . $linkArray['id_nodo'] . ' id_nodo_to=' . $linkArray['id_nodo_to']);
                }
            }
        } else {
            $this->logMessage(__METHOD__ . ' No links to be saved this time');
        }
        $this->linksArray = null;
    }

    /**
     * static method to get the language id corresponding to the passed language table identifier
     * (e.g. on most installations, passing 'it' will return 1)
     *
     * @param string $tableName the 2 chars ADA language table identifier
     *
     * @return int 0 if empty string passed|AMAError on error|int retrieved id on success
     *
     * @access public
     */
    public static function getLanguageIDFromTable($tableName)
    {
        if ($tableName == '') {
            return 0;
        }
        $res = AMACommonDataHandler::getInstance()->findLanguageIdByLangaugeTableIdentifier($tableName);
        return (AMADB::isError($res)) ? 0 : $res;
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

    /**
     * Private methods dealing with sessions
     *
     * all of the below methods open and close session because the requestProgress.php file
     * that is used to display to the user the progress of the import must reads theese
     * session vars, and if the session is left open, it gets stuck until this php ends.
     *
     */

    /**
     * Initializes empty progress session vars
     */
    private function progressInit()
    {
        /**
         * sets a session array for progrss displaying to the poor user
         */
        session_write_close();
        session_start();
        if (isset($_SESSION['importProgress'])) {
            unset($_SESSION['importProgress']);
        }
        $_SESSION['importProgress'] = [];
        session_write_close();
    }

    /**
     * Unsets progress session vars
     */
    private function progressDestroy()
    {
        session_start();
        if (isset($_SESSION['importProgress'])) {
            unset($_SESSION['importProgress']);
        }
        session_write_close();
        // leave the session open, please (?)
        // session_write_close();
    }

    /**
     * Resets (aka initializes with values) the progress session vars
     *
     * @param int $total count of total items to be imported
     */
    private function progressResetValues($total)
    {
        session_start();
        $_SESSION['importProgress']['totalItems'] = $total;
        $_SESSION['importProgress']['currentItem'] = 0;
        $_SESSION['importProgress']['status'] = 'ITEMS';
        session_write_close();
    }

    /**
     * Sets the status of the import process
     *
     * @param string $status status to be set
     */
    private function progressSetStatus($status)
    {
        session_start();
        $_SESSION['importProgress']['status'] = $status;
        session_write_close();
    }

    /**
     * Increments the current item count being imported
     */
    private function progressIncrement()
    {
        session_start();
        $_SESSION['importProgress']['currentItem']++;
        session_write_close();
    }

    /**
     * Sets the title of the course being imported
     *
     * @param string $title the title to be set
     */
    private function progressSetTitle($title)
    {
        session_start();
        $_SESSION['importProgress']['courseName'] = $title;
        session_write_close();
    }
}

<?php

/**
 * @package     import/export course
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2019, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Impexport;

use Exception;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMARepositoryDataHandler extends AMACommonDataHandler
{
    use WithCUD;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_impexport_';

    private const EXCEPTIONCLASS = Exception::class;

    /**
     * Saves export data to the repository table
     *
     * @param array $saveData
     * @return void
     * @throws Exception
     */
    public function saveExportData(array $saveData = [])
    {
        $isUpdate = array_key_exists('id', $saveData) && intval($saveData['id']) > 0;
        if (!array_key_exists('exportTS', $saveData)) {
            $saveData['exportTS'] = $this->dateToTs('now');
        }
        if (!array_key_exists('exporter_userid', $saveData)) {
            $saveData['exporter_userid'] = $_SESSION['sess_userObj']->getId();
        }
        if (!$isUpdate) {
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    self::PREFIX . 'repository',
                    $saveData
                ),
                array_values($saveData)
            );
            $saveData['id'] = $this->getConnection()->lastInsertID();
        } else {
            $whereArr = ['id' => $saveData['id']];
            unset($saveData['id']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    self::PREFIX . 'repository',
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
        }

        if (!AMADB::isError($result)) {
            return $saveData;
        } else {
            throw new Exception($result->getMessage(), $result->getCode());
        }
    }

    /**
     * Gets repository items
     *
     * @param array $whereArr
     * @return array
     */
    public function getRepositoryList(array $whereArr = [])
    {
        $sql = 'SELECT R.*,CONCAT(U.nome," ",U.cognome) AS exporter_fullname FROM `' . self::PREFIX . 'repository` R LEFT JOIN `utente` U ON U.`id_utente` = R.`exporter_userid`'
                . $this->buildWhereClause($whereArr, array_keys($whereArr));
        $res = $this->getAllPrepared($sql, array_values($whereArr), AMA_FETCH_ASSOC);

        if (!AMADB::isError($res)) {
            /**
             * this is needed to instantiate the proper dh and get course data
             */
            $testersArr = $this->getAllTesters(['id_tester']);
            $cachedValues = ['courseTitles' => [], 'courseProviders' => []];
            $res = array_map(function ($element) use ($testersArr, &$cachedValues) {
                if (!array_key_exists($element['id_course'], $cachedValues['courseTitles'])) {
                    $provider = array_filter($testersArr, fn ($el) =>
                        // var_dump([$el, $element]);
                        $el['id_tester'] == $element['id_tester']);
                    $provider = reset($provider);
                    $pdh = AMADataHandler::instance(MultiPort::getDSN($provider['puntatore']));
                    $courseData = $pdh->getCourse($element['id_course']);
                    if (!AMADB::isError($courseData)) {
                        $cachedValues['courseTitles'][$element['id_course']] = $courseData['titolo'];
                    } else {
                        $cachedValues['courseTitles'][$element['id_course']] = translateFN('Corso Sconosciuto');
                    }
                    $cachedValues['courseProviders'][$element['id_course']] = $provider['puntatore'];
                }
                $element['courseProvider'] = $cachedValues['courseProviders'][$element['id_course']];
                $element['courseTitle'] = $cachedValues['courseTitles'][$element['id_course']];
                $element['exportDateTime'] = Utilities::ts2dFN($element['exportTS']) . ' ' . Utilities::ts2tmFN($element['exportTS']);

                return $element;
            }, $res);
            return $res;
        } else {
            return [];
        }
    }

    /**
     * Deletes export data to the repository table
     *
     * @param array $delData
     * @return boolean
     * @throws Exception
     */
    public function deleteExport(array $delData = [])
    {
        if (array_key_exists('id', $delData) && intval($delData['id']) > 0) {
            $toDelete = $this->getRepositoryList(['id' => $delData['id']]);
            if (is_array($toDelete) && count($toDelete) == 1) {
                $toDelete = reset($toDelete);
                $delArr = ['id' => $toDelete['id'] ];
                $result = $this->queryPrepared(
                    $this->sqlDelete(
                        self::PREFIX . 'repository',
                        $delArr
                    ),
                    array_values($delArr)
                );
                if (!AMADB::isError($result)) {
                    $repodir = MODULES_IMPEXPORT_REPOBASEDIR . $toDelete['id_course'] . DIRECTORY_SEPARATOR . MODULES_IMPEXPORT_REPODIR;
                    @unlink($repodir . DIRECTORY_SEPARATOR . $toDelete['filename']);
                    @rmdir($repodir);
                    return true;
                } else {
                    throw new Exception($result->getMessage());
                }
            } else {
                throw new Exception(translateFN('Errore nella lettura dati'));
            }
        } else {
            throw new Exception(translateFN('ID export non valido'));
        }
    }

    /**
     * Gets a tester id from its pointer
     *
     * @param string $pointer
     * @return int
     */
    public function getTesterIDFromPointer($pointer = null)
    {
        $testerId = null;
        if (is_null($pointer)) {
            $pointer = $_SESSION['sess_selected_tester'];
        }
        $testerInfo = $this->getTesterInfoFromPointer($pointer);
        if (!AMADB::isError($testerInfo) && is_array($testerInfo) && isset($testerInfo[0])) {
            $testerId = $testerInfo[0];
        }
        return $testerId;
    }
}

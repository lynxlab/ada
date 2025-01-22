<?php

/**
 * @package     cloneinstance module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2022, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\CloneInstance;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMACloneInstanceDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_cloneinstance_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\CloneInstance\\';

    private const EXCEPTIONCLASS = CloneInstanceException::class;


    public function cloneInstance(int $sourceInstanceID, array $destCoursesID)
    {
        if ($sourceInstanceID > 0) {
            if (count($destCoursesID) > 0) {
                $cloneRecap = [];
                $userId = $_SESSION['sess_userObj']->getID();
                // ok to clone, load instance data
                $instanceArr = $this->courseInstanceGet($sourceInstanceID);
                if (!AMADB::isError($instanceArr)) {
                    // get subscritions list from its table
                    $subscriptionArr =  $this->getAllPrepared(
                        "SELECT * FROM `iscrizioni` WHERE `id_istanza_corso`=?",
                        [$sourceInstanceID],
                        AMA_FETCH_ASSOC
                    );
                    if (!AMADB::isError($subscriptionArr)) {
                        // get tutors list from its table
                        $tutorsList = $this->getAllPrepared(
                            "SELECT * FROM `tutor_studenti` WHERE `id_istanza_corso`=?",
                            [$sourceInstanceID],
                            AMA_FETCH_ASSOC
                        );
                        if (!AMADB::isError($tutorsList)) {
                            // so far, so good. clone it!
                            $errMsg = null;
                            $this->beginTransaction();
                            foreach ($destCoursesID as $courseID) {
                                $instanceID = $this->courseInstanceAdd($courseID, $instanceArr);
                                if (!AMADB::isError($instanceID) && intval($instanceID) > 0) {
                                    // add subscriptions
                                    // this will be modified by inserMultiRow
                                    $saveSubsArr = array_map(fn ($el) => array_merge($el, ['id_istanza_corso' => $instanceID]), $subscriptionArr);
                                    if (count($saveSubsArr) > 0) {
                                        $result = $this->queryPrepared(
                                            $this->insertMultiRow(
                                                $saveSubsArr,
                                                'iscrizioni'
                                            ),
                                            array_values($saveSubsArr)
                                        );
                                    } else {
                                        $result = true;
                                    }
                                    if (!AMADB::isError($result)) {
                                        // add tutors
                                        // this will be modified by inserMultiRow
                                        $saveTutorsArr = array_map(fn ($el) => array_merge($el, ['id_istanza_corso' => $instanceID]), $tutorsList);
                                        if (count($saveTutorsArr) > 0) {
                                            $result = $this->queryPrepared(
                                                $this->insertMultiRow(
                                                    $saveTutorsArr,
                                                    'tutor_studenti'
                                                ),
                                                array_values($saveTutorsArr)
                                            );
                                        } else {
                                            $result = true;
                                        }
                                        if (!AMADB::isError($result)) {
                                            // done!
                                            $cloneRecap[] = [
                                                'instanceId' => (int) $sourceInstanceID,
                                                'clonedInCourse' => (int) $courseID,
                                                'clonedInstanceId' => (int) $instanceID,
                                                'userId' => (int) $userId,
                                                'cloneTimestamp' => $this->dateToTs('now'),
                                            ];
                                        } else {
                                            $errMsg = $result->getMessage();
                                        }
                                    } else {
                                        $errMsg = $result->getMessage();
                                    }
                                } else {
                                    $errMsg = translateFN('Errore nella creazione della nuova istanza.');
                                }

                                if (!empty($errMsg)) {
                                    $this->rollBack();
                                    throw new CloneInstanceException($errMsg);
                                }
                            }

                            // save clone recap
                            $saveRecap = $cloneRecap;
                            if (count($saveRecap) > 0) {
                                $result = $this->queryPrepared(
                                    $this->insertMultiRow(
                                        $saveRecap,
                                        self::PREFIX . 'history'
                                    ),
                                    array_values($saveRecap)
                                );
                                if (AMADB::isError($result)) {
                                    $errMsg = $result->getMessage();
                                }
                            }

                            // final commit or rollback
                            if (!empty($errMsg)) {
                                $this->rollBack();
                                throw new CloneInstanceException($errMsg);
                            } else {
                                $this->commit();
                                return $cloneRecap;
                            }
                        } else {
                            throw new CloneInstanceException(translateFN("Errore nella lettura dei tutor dell'istanza"));
                        }
                    } else {
                        throw new CloneInstanceException(translateFN("Errore nella lettura delle iscrizioni all'istanza"));
                    }
                } else {
                    throw new CloneInstanceException(translateFN('Errore nella lettura dati istanza'));
                }
            } else {
                throw new CloneInstanceException(translateFN('Elenco corsi destinazione non valido'));
            }
        } else {
            throw new CloneInstanceException(translateFN('ID istanza non valido'));
        }
    }
}

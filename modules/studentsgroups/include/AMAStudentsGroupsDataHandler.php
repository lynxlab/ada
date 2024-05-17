<?php

/**
 * @package     studentsgroups module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\StudentsGroups;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMAStudentsGroupsDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_studentsgroups_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\StudentsGroups\\';

    private const EXCEPTIONCLASS = StudentsGroupsException::class;

    /**
     * Saves a Groups object
     *
     * @param array $saveData
     * @return \Lynxlab\ADA\Module\StudentsGroups\Groups|StudentsGroupsException
     */
    public function saveGroup($saveData)
    {
        if (array_key_exists('id', $saveData)) {
            $isUpdate = true;
        } else {
            $isUpdate = false;
        }

        if (array_key_exists('studentsgroupsfilefileNames', $saveData) && is_array($saveData['studentsgroupsfilefileNames']) && count($saveData['studentsgroupsfilefileNames']) === 1) {
            $groupscsv = reset($saveData['studentsgroupsfilefileNames']);
        }

        unset($saveData['studentsgroupsfile']);
        unset($saveData['studentsgroupsfilefileNames']);

        // set to null all empty passed fields
        foreach (array_keys($saveData) as $aKey) {
            if (str_starts_with($aKey, Groups::CUSTOMFIELDPRFIX) && strlen($saveData[$aKey]) <= 0) {
                $saveData[$aKey] = null;
            }
        }

        if (!$isUpdate) {
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    Groups::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
            $saveData['id'] = $this->lastInsertID();
        } else {
            $whereArr = ['id' => $saveData['id']];
            unset($saveData['id']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    Groups::TABLE,
                    array_keys($saveData),
                    $whereArr
                ),
                array_values($saveData + $whereArr)
            );
            $saveData['id'] = $whereArr['id'];
        }

        if (!AMADB::isError($result)) {
            $retArr = ['group' => new Groups($saveData)];
            /**
             * import the uploaded CSV if it's not an update
             */
            if (!$isUpdate && isset($groupscsv)) {
                $counters = [
                    'registered' => 0,
                    'duplicates' => 0,
                    'errors' => 0,
                    'invalidpasswords' => 0,
                    'total' => 0,
                ];
                /**
                 * handle uploaded file here!
                 */
                $groupscsv = ADA_UPLOAD_PATH . DIRECTORY_SEPARATOR . MODULES_STUDENTSGROUPS_NAME . DIRECTORY_SEPARATOR . $groupscsv;
                if (is_readable($groupscsv)) {
                    $usersToAdd = [];
                    $providers = $_SESSION['sess_userObj']->getTesters();
                    if (MULTIPROVIDER) {
                        array_unshift($providers, ADA_PUBLIC_TESTER);
                    }
                    $usersToSubscribe = file($groupscsv);

                    /* remove blank lines form array */
                    foreach ($usersToSubscribe as $key => $value) {
                        if (!trim($value)) {
                            unset($usersToSubscribe[$key]);
                        }
                    }

                    foreach ($usersToSubscribe as $row) {
                        ++$counters['total'];
                        $userDataAr = explode(',', $row);
                        if (is_array($userDataAr) && count($userDataAr) == MODULES_STUDENTSGROUPS_FIELDS_IN_CSVROW) {
                            $userDataAr = array_map('trim', explode(',', $row));
                            $subscriberObj = MultiPort::findUserByUsername($userDataAr[2]);
                            if ($subscriberObj == null) {
                                $subscriberObj = new ADAUser(
                                    [
                                        'nome' => trim($userDataAr[0]),
                                        'cognome' => trim($userDataAr[1]),
                                        'email' => trim($userDataAr[2]),
                                        'tipo' => AMA_TYPE_STUDENT,
                                        'username' => trim($userDataAr[2]),
                                        'stato' => ADA_STATUS_REGISTERED, // these students will never get the confirm email
                                        'birthcity' => '',
                                    ]
                                );
                                if (DataValidator::validatePassword($userDataAr[3], $userDataAr[3])) {
                                    $subscriberObj->setPassword($userDataAr[3]);
                                    if (ModuleLoaderHelper::isLoaded('SECRETQUESTION') === true) {
                                        $subscriberObj->setEmail('');
                                    }
                                    /**
                                     * save the user and add it to the providers of the session user (that is a switcher)
                                     */
                                    $result = MultiPort::addUser($subscriberObj, $providers);
                                    if ($result > 0) {
                                        ++$counters['registered'];
                                        $usersToAdd[] = $result;
                                    } else {
                                        ++$counters['errors'];
                                    }
                                } else {
                                    ++$counters['errors'];
                                    ++$counters['invalidpasswords'];
                                }
                            } else {
                                // user was found by findUserByUsername
                                ++$counters['duplicates'];
                                /**
                                 * add the user to the providers of the session user (that is a switcher)
                                 */
                                MultiPort::setUser($subscriberObj, $providers, false);
                                $usersToAdd[] = $subscriberObj->getId();
                            }
                        } else {
                            // not array or less than expected fields
                            ++$counters['errors'];
                        }
                    }
                    $retArr['importResults'] = $counters;
                    /**
                     * add users to the group
                     */
                    $sql = sprintf(
                        "INSERT INTO `%s` VALUES %s;",
                        Groups::UTENTERELTABLE,
                        implode(
                            ',',
                            array_map(fn ($el) => '(' . $retArr['group']->getId() . ',' . $el . ')', $usersToAdd)
                        )
                    );
                    $this->executeCriticalPrepared($sql);
                    @unlink($groupscsv);
                }
            }
            return $retArr;
        } else {
            return new StudentsGroupsException($result->getMessage());
        }
    }

    /**
     * Saves a group subscription to a course instance
     *
     * @param array $saveData
     * @return StudentsGroupsException|array
     */
    public function saveSubscribeGroup($saveData)
    {
        try {
            $saveData = array_map('intval', $saveData);
            $result = $this->findBy('Groups', ['id' => $saveData['groupId']]);
            if (!AMADB::isError($result)) {
                // check instance existence
                $iArr = $GLOBALS['dh']->courseInstanceGet($saveData['instanceId']);
                if (!AMADB::isError($iArr) && is_array($iArr) && isset($iArr['id_corso']) && $iArr['id_corso'] == $saveData['courseId']) {
                    $counters = [
                        'alreadySubscribed' => 0,
                        'subscribed' => 0,
                    ];
                    $group = reset($result);
                    $courseProviderAr = $GLOBALS['common_dh']->getTesterInfoFromIdCourse($saveData['courseId']);
                    $subscribedIds = array_map(fn ($s) => $s->getSubscriberId(), Subscription::findSubscriptionsToClassRoom($saveData['instanceId'], true));
                    foreach ($group->getMembers() as $student) {
                        if (!in_array($student->getId(), $subscribedIds)) {
                            if (!in_array($courseProviderAr['puntatore'], $student->getTesters())) {
                                // subscribe user to course provider
                                $isUserInProvider = Multiport::setUser($student, [$courseProviderAr['puntatore']]);
                            } else {
                                $isUserInProvider = true;
                            }
                            if ($isUserInProvider) {
                                $s = new Subscription($student->getId(), $saveData['instanceId']);
                                $s->setSubscriptionStatus(ADA_STATUS_SUBSCRIBED);
                                $s->setStartStudentLevel($iArr['start_level_student']);
                                Subscription::addSubscription($s);
                                ++$counters['subscribed'];
                            }
                        } else {
                            ++$counters['alreadySubscribed'];
                        }
                    }
                    $retval = $counters;
                } else {
                    $retval = new StudentsGroupsException(translateFN('ID corso o ID classe non valido'));
                }
            }
        } catch (StudentsGroupsException $e) {
            $retval = $e;
        }
        return $retval;
    }

    /**
     * Deletes a Group
     *
     * @param array $saveData
     * @return StudentsGroupsException|bool
     */
    public function deleteGroup($saveData)
    {
        $result = $this->queryPrepared(
            $this->sqlDelete(
                Groups::TABLE,
                $saveData
            ),
            array_values($saveData)
        );

        if (!AMADB::isError($result)) {
            return true;
        } else {
            return new StudentsGroupsException($result->getMessage());
        }
    }
}

<?php

/**
 * @package     collabora-access-list module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\CollaboraACL;

use Exception;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\AMA\Traits\WithTransactions;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLActions;
use Lynxlab\ADA\Module\CollaboraACL\FileACL;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMACollaboraACLDataHandler extends AMADataHandler
{
    use WithInstance;
    use WithCUD;
    use WithFind;
    use WithTransactions;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_collaboraacl_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\CollaboraACL\\';

    private const EXCEPTIONCLASS = CollaboraACLException::class;

    public function saveGrantedUsers($saveData)
    {
        $isUpdate = array_key_exists('fileAclId', $saveData) && intval($saveData['fileAclId']) > 0;

        try {
            if (!$this->beginTransaction()) {
                throw new CollaboraACLException(translateFN('Errore avvio transazione DB'));
            }
            if (!$isUpdate) {
                $course_ha = $this->getCourse($saveData['courseId']);
                if (self::isError($course_ha)) {
                    throw new CollaboraACLException(translateFN('Errore nella verifica del corso'));
                } else {
                    if ($course_ha['media_path'] != "") {
                        $media_path = $course_ha['media_path'];
                    } else {
                        $media_path = MEDIA_PATH_DEFAULT . $course_ha['id_autore'];
                    }
                    $media_path = trim($media_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                }
                // must insert a new row in the file table
                $insertData = [
                    'filepath' => $media_path . $saveData['filename'],
                    'id_corso' => $saveData['courseId'],
                    'id_istanza' => $saveData['instanceId'],
                    'id_nodo' => $saveData['nodeId'],
                    'id_owner' => $saveData['ownerId'],
                ];
                $result = $this->executeCriticalPrepared(
                    $this->sqlInsert(
                        FileACL::TABLE,
                        $insertData
                    ),
                    array_values($insertData)
                );
            } else {
                $result = true;
            }

            if (!AMADB::isError($result)) {
                if (!$isUpdate) {
                    $saveData['fileAclId'] = $this->getConnection()->lastInsertID();
                }
                // delete from files_utente table
                $delData = [
                    'file_id' => $saveData['fileAclId'],
                ];
                $this->queryPrepared(
                    $this->sqlDelete(
                        FileACL::UTENTERELTABLE,
                        $delData
                    ),
                    array_values($delData)
                );
                if (array_key_exists('grantedUsers', $saveData) && is_array($saveData['grantedUsers'])) {
                    if (count($saveData['grantedUsers']) > 0) {
                        // save in the utenteRelTable
                        $insertData = array_map(fn ($el) => [
                            'file_id' => $saveData['fileAclId'],
                            'utente_id' => $el,
                            'permissions' => CollaboraACLActions::READ_FILE,
                        ], $saveData['grantedUsers']);
                        $result = $this->queryPrepared(
                            $this->insertMultiRow(
                                $insertData,
                                FileACL::UTENTERELTABLE
                            ),
                            array_values($insertData)
                        );
                        if (AMADB::isError($result)) {
                            throw new CollaboraACLException($result->getMessage());
                        }
                    } else {
                        // no granted users, this will become a public file
                        // must delete from the files table
                        $delData = [
                            'id' => $saveData['fileAclId'],
                        ];
                        $this->queryPrepared(
                            $this->sqlDelete(
                                FileACL::TABLE,
                                $delData
                            ),
                            array_values($delData)
                        );
                        $saveData['fileAclId'] = -1;
                    }
                }
                $this->commit();
                return $saveData;
            } else {
                throw new CollaboraACLException($result->getMessage());
            }
        } catch (Exception $e) {
            $this->rollBack();
            return ($e);
        }
    }

    public function deleteFileACL($id)
    {
        // delete from the files table, will delete the relation as well
        $delData = [
            'id' => $id,
        ];
        $this->beginTransaction();
        $result = $this->queryPrepared(
            $this->sqlDelete(
                FileACL::TABLE,
                $delData
            ),
            array_values($delData)
        );
        if (AMADB::isError($result)) {
            $this->rollBack();
            return $result;
        } else {
            $this->commit();
            return true;
        }
    }
}

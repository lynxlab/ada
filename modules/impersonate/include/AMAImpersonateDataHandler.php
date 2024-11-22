<?php

/**
 * @package     impersonate module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Impersonate;

use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithFind;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Module\Impersonate\LinkedUsers;

class AMAImpersonateDataHandler extends AMADataHandler
{
    use WithCUD;
    use WithFind;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_impersonate_';

    /**
     * module's own model class namespace (can be the same of the datahandler's tablespace)
     *
     * @var string
     */
    public const MODELNAMESPACE = 'Lynxlab\\ADA\\Module\\Impersonate\\';

    private const EXCEPTIONCLASS = ImpersonateException::class;

    /**
     * insert or update a LinkedUser
     *
     * @param array $saveData LinkedUsers object, as an array
     * @param boolean $linkUpdate true if it's an update, false if insert
     * @return boolean|ImpersonateException
     */
    public function saveLinkedUsers($saveData, $linkUpdate)
    {

        $this->beginTransaction();
        if (!$linkUpdate) {
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(
                    LinkedUsers::TABLE,
                    $saveData
                ),
                array_values($saveData)
            );
        } else {
            $whereArr = [
                'source_id' => [
                    'op' => '=',
                    'value' => $saveData['source_id'],
                ],
                'linked_id' => [
                    'op' => '=',
                    'value' => $saveData['linked_id'],
                ],
                'source_type' => [
                    'op' => '=',
                    'value' => $saveData['source_type'],
                ],
                'linked_type' => [
                    'op' => '=',
                    'value' => $saveData['linked_type'],
                ],
            ];
            $updateData['is_active'] = $saveData['is_active'];
            unset($saveData['is_active']);
            $result = $this->queryPrepared(
                $this->sqlUpdate(
                    LinkedUsers::TABLE,
                    array_keys($updateData),
                    $whereArr
                ),
                array_values($updateData)
            );
        }

        if (!AMADB::isError($result)) {
            $this->commit();
            return true;
        } else {
            $this->rollBack();
            return new ImpersonateException($result->getMessage());
        }
    }
}

<?php

/**
 * @package     encrypt-fields module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields;

use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\Encryptfields\Events\EncryptFieldsEvents;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;

class FieldsConfig
{
    protected const EVENTSUBJECT = 'externalfields';

    /**
     * Gets all tables and field that require en/decryption
     * for the passed table using table alias
     *
     * @param string $table
     * @return array
     */
    public static function getFieldsForTable(string $table): array
    {
        if (!empty($table)) {
            $table = static::tableAlias()[$table] ?? $table;
            return static::tableDefs($table);
        }
        return [];
    }

    /**
     * Gets all tables and field that require en/decryption
     *
     * @return array
     */
    public static function getAllFields(): array
    {
        $extFields = [];
        $fields = [
            'utente' => [
                'fields' => [
                    'nome',
                    'cognome',
                    'nome_destinatario', // alias in comunica/include/Spools/Spool.php:649
                    'cognome_destinatario', // alias in comunica/include/Spools/Spool.php:649
                    'student', // alias in include/AMA/AMATesterDataHandler.php:1419
                    'lastname', //alias in include/AMA/AMATesterDataHandler.php:1419
                ],
            ],
        ];
        if (ModuleLoaderHelper::isLoaded('MODULES_EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => EncryptFieldsEvents::class,
                    'eventName' => EncryptFieldsEvents::POSTFIELDSCONFIG,
                ],
                self::EVENTSUBJECT
            );
            if ($event->hasArgument(self::EVENTSUBJECT)) {
                foreach ($event->getArgument(self::EVENTSUBJECT) as $table => $externalfields) {
                    $extFields[$table] = array_merge($extFields[$table] ?? [], $externalfields);
                }
            }
        }
        foreach ($extFields as $table => $exField) {
            if (!array_key_exists($table, $fields)) {
                $fields[$table] = $exField;
            }
        }
        return $fields;
    }

    /**
     * Gets all table configuration
     *
     * @param string $table
     * @return array
     */
    protected static function tableDefs(string $table): array
    {
        return static::getAllFields()[$table] ?? [];
    }

    /**
     * Gets posssible table alias
     *
     * @return array
     */
    protected static function tableAlias(): array
    {
        return [
            'u' => 'utente',
            'u1' => 'utente',
            'u2' => 'utente',
        ];
    }
}

<?php

/**
 * @package     encrypt-fields module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields;

use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Logger\ADAFileLogger;

class AMAEncryptFieldsDataHandler extends AMADataHandler
{
    /**
     * Encrypted filed sql data type
     */
    private const ENCRYPT_SQL_TYPE = 'varchar(1024)';

    /**
     * query to change the data type
     */
    private const ENCRYPT_SQL_ALTER = 'ALTER TABLE `%s` CHANGE `%s` `%s` %s;';

    /**
     * query to load the data to be en/decrypted
     */
    private const SELECT_ENC_DATA = 'SELECT `%s` FROM `%s` ORDER BY `%s` ASC';

    /**
     * query to write the en/decrypted data
     */
    private const UPDATE_ENC_DATA = 'UPDATE `%s` SET %s WHERE `%s`=?';

    /**
     * Toggles field encryption in all configured clients, including common
     *
     * @param boolean $dryRun true to not write to the database
     * @param boolean $encrypt true to do encryption. false to do decryption
     * @return void
     */
    public static function toggleEncryption($dryRun = true, $encrypt = false)
    {
        if (php_sapi_name() != "cli") {
            die('cli exec only');
        }

        $encData = FieldsConfig::getAllFields();
        $commondh = AMACommonDataHandler::getInstance();
        $allPointers = $commondh->getAllTesters(['puntatore']);
        $ch = new CypherUtils();

        if (!AMADB::isError($allPointers) && is_array($allPointers) && count($allPointers) > 0) {
            $dbs = [$commondh];

            foreach ($allPointers as $aPointer) {
                $dbs[] = $aPointer['puntatore'];
            }

            foreach ($dbs as $db) {
                if (!($db instanceof AbstractAMADataHandler)) {
                    $db = self::instance(MultiPort::getDSN($db));
                }
                if ($db instanceof AbstractAMADataHandler) {
                    $dbclient = array_reverse(explode('/', (string) $db->dsn))[0];
                    echo EchoHelper::ok("\n$dbclient\n");
                    foreach ($encData as $table => $fields) {
                        $desc = $db->getAllPrepared("DESC `$table`");
                        if (!AMADB::isError($desc)) {
                            $primaryKeys = array_filter(
                                $desc,
                                fn ($el) => ($el['Key'] ?? null) == 'PRI'
                            );
                            if (!empty($primaryKeys)) {
                                $primaryKey = reset($primaryKeys)['Field'];
                                if (!empty($primaryKey)) {
                                    $processFields = array_values(
                                        array_filter(
                                            $desc,
                                            fn ($el) => in_array($el['Field'], $fields['fields'])
                                        )
                                    );
                                    foreach ($processFields as $pField) {
                                        $alterSQL = sprintf(self::ENCRYPT_SQL_ALTER, $table, $pField['Field'], $pField['Field'], self::ENCRYPT_SQL_TYPE);
                                        if ($dryRun) {
                                            echo  "$alterSQL\n";
                                        } else {
                                            if ($encrypt) {
                                                $db->queryPrepared($alterSQL);
                                            }
                                        }
                                    }
                                    // now encrypt needed data
                                    $encFields = array_map(fn ($el) => $el['Field'], $processFields);
                                    $selectString = implode('`, `', array_merge([$primaryKey], $encFields));
                                    echo EchoHelper::warn(
                                        sprintf(
                                            "will %s %s from table `%s`\n",
                                            ($encrypt ? 'encrypt' : 'decrypt'),
                                            '`' . implode('`, `', $encFields) . '` ',
                                            $table
                                        )
                                    );

                                    $encRows = $db->getAllPrepared(
                                        sprintf(self::SELECT_ENC_DATA, $selectString, $table, $primaryKey),
                                        [],
                                        AMA_FETCH_ASSOC
                                    );
                                    if (!AMADB::isError($encRows)) {
                                        echo "selected " . count($encRows) . " rows for " . ($encrypt ? 'en' : 'de') . "cryption\n";
                                        $updateString = implode(
                                            ', ',
                                            array_map(
                                                fn ($el) => "`$el`" . '=?',
                                                $encFields
                                            )
                                        );
                                        $updateSql = sprintf(self::UPDATE_ENC_DATA, $table, $updateString, $primaryKey);
                                        $encCount = 0;
                                        foreach ($encRows as $row) {
                                            if ($encrypt) {
                                                $uData = array_map(fn ($el) => $ch->encrypt($row[$el]), $encFields);
                                            } else {
                                                // the eventlistener is disabled when running from cli!
                                                $uData = array_map(fn ($el) => $ch->decrypt($row[$el]), $encFields);
                                            }
                                            $uData[] = $row[$primaryKey];
                                            if (!$dryRun && count($uData) > 1) {
                                                if (!AMADB::isError($db->queryPrepared($updateSql, $uData))) {
                                                    $encCount++;
                                                }
                                            } else {
                                                $encCount++;
                                            }
                                        }
                                    }
                                    $echoMethod = $encCount == count($encRows) ? 'ok' : 'error';
                                    echo EchoHelper::{$echoMethod}(($encrypt ? 'en' : 'de') . "crypted $encCount rows\n");
                                    // all done! disconnect the DB
                                    $db->disconnect();
                                } else {
                                    ADAFileLogger::logError(
                                        sprintf("%s: cannot find primary key for `%s` on `%s`", __METHOD__, $table, $dbclient)
                                    );
                                }
                            } else {
                                ADAFileLogger::logError(
                                    sprintf("%s: cannot find primary key candidates for `%s` on `%s`", __METHOD__, $table, $dbclient)
                                );
                            }
                        }
                    }
                }
            }
            if (!$dryRun) {
                if ($encrypt) {
                    echo EchoHelper::warn("\nEncryption done!!\nEnable the module editing config_modules.inc.php and check permission of the key file.", true);
                } else {
                    echo EchoHelper::warn("\nDecryption done!!\nDisable the module editing config_modules.inc.php and delete the key file.", true);
                }
            }
        }
        echo "\n";
    }
}

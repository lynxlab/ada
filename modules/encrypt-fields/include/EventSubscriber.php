<?php

/**
 * @package     encrypt-fields module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields;

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Encryptfields\Events\EncryptFieldsEvents;
use Lynxlab\ADA\Module\Encryptfields\Exceptions\EncryptFieldsException;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Subscribers\ADAMethodSubscriberInterface;
use PDOStatement;
use PHPSQLParser\PHPSQLParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventSubscriber Class, defines node events names and handlers for this module
 */
class EventSubscriber implements EventSubscriberInterface, ADAMethodSubscriberInterface
{
    private $encode = [];
    private $decode = [];
    private $decodeCache = [];

    /**
     * EventSubscriber must implements EventSubscriberInterface
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $subsEvts = [
            EncryptFieldsEvents::FIELDSCONFIG => [
                'encryptFieldsConfig',
                // we want this listener to be run as last
                // so mark it with a negative priority
                -9999,
            ],
        ];
        if (php_sapi_name() == "cli") {
            return $subsEvts;
        } else {
            return array_merge(
                $subsEvts,
                [
                    CoreEvent::POSTFETCHALL => 'postFetchAll',
                    CoreEvent::POSTFETCH => 'postFetch',
                ]
            );
        }
    }

    /**
     * Event subscription to methods
     */
    public static function getSubscribedMethods()
    {
        if (php_sapi_name() == "cli") {
            return [];
        } else {
            return [
                MultiPort::class . '::addUser' => [
                    CoreEvent::PREPREPAREANDEXECUTE => 'encryptBeforeSave',
                ],
                MultiPort::class . '::setUser' => [
                    CoreEvent::PREPREPAREANDEXECUTE => 'encryptBeforeSave',
                ],
            ];
        }
    }

    /**
     * Return the event with the argument for encrypt fields configuration
     *
     * @param EncryptFieldsEvents $event
     * @return EncryptFieldsEvents
     */
    public function encryptFieldsConfig(EncryptFieldsEvents $event)
    {
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
        if ($event->hasArgument($event->getSubject())) {
            $fields = array_merge_recursive($event->getArgument($event->getSubject()), $fields);
        }
        $event->setArgument($event->getSubject(), $fields);
        return $event;
    }

    /**
     * Does the encryption before saving data
     *
     * @param CoreEvent $event
     * @return CoreEvent
     */
    public function encryptBeforeSave(CoreEvent $event): CoreEvent
    {
        $args = $event->getArguments();
        $sql = strtoupper($args['sql'] instanceof PDOStatement ? $args['sql']->queryString : $args['sql']);
        if (!str_starts_with($sql, 'SELECT')) {
            $encode = $this->setEncodeDecode($args['sql'])->getEncode();
            if (!empty($encode) && !empty($args['values'] ?? [])) {
                $results = $args['values'];
                $cUtils = new CypherUtils();
                try {
                    foreach ($encode as $table => $fields) {
                        foreach ($fields as $fData) {
                            foreach (['name', 'index'] as $what) {
                                // ENCRYPT FETCH ASSOC AND NUM ELEMENTS
                                if (array_key_exists($fData[$what], $results)) {
                                    try {
                                        /**
                                         * if decryption throws an exception, most
                                         * probably the string needs to be encrypted
                                         */
                                        $cUtils->decrypt($results[$fData[$what]]);
                                    } catch (EncryptFieldsException) {
                                        $results[$fData[$what]] = $cUtils->encrypt($results[$fData[$what]]);
                                    }
                                }
                            }
                        }
                    }
                } catch (EncryptFieldsException $e) {
                    if (php_sapi_name() == "cli") {
                        throw $e;
                    } else {
                        Utilities::redirect(HTTP_ROOT_DIR . '/error.php?err_msg=' . urlencode($e->getMessage()));
                    }
                }
                $args['values'] = $results;
            }
        }
        $event->setArguments($args);
        return $event;
    }

    /**
     * Does the decryption after a fetch all
     *
     * @param CoreEvent $event
     * @return CoreEvent
     */
    public function postFetchAll(CoreEvent $event): CoreEvent
    {
        return $this->decryptAfterLoad($event, true);
    }

    /**
     * Does the decryption after a fetch
     *
     * @param CoreEvent $event
     * @return CoreEvent
     */
    public function postFetch(CoreEvent $event): CoreEvent
    {
        return $this->decryptAfterLoad($event, false);
    }

    /**
     * Does the decryption after loading data
     *
     * @param CoreEvent $event
     * @param boolean $isFetchAll
     * @return CoreEvent
     */
    private function decryptAfterLoad(CoreEvent $event, bool $isFetchAll = false): CoreEvent
    {
        $args = $event->getArguments();
        $decode = $this->setEncodeDecode($args['resultObj'])->getDecode();
        if (!empty($decode) && !empty($args['resultAr'] ?? [])) {
            if (!$isFetchAll) {
                $results = [$args['resultAr']];
            } else {
                $results = $args['resultAr'];
            }
            $cUtils = new CypherUtils();
            try {
                $results = array_map(
                    function ($el) use ($decode, $cUtils) {
                        foreach ($decode as $table => $fields) {
                            foreach ($fields as $fData) {
                                // DECRYPT PDO::FETCH_ASSOC ELEMENTS
                                if (array_key_exists($fData['name'], $el)) {
                                    if (!array_key_exists($el[$fData['name']], $this->decodeCache)) {
                                        $this->decodeCache[$el[$fData['name']]] = $cUtils->decrypt($el[$fData['name']]);
                                    }
                                    $el[$fData['name']] = $this->decodeCache[$el[$fData['name']]];
                                }
                                // DECRYPT PDO::FETCH_NUM ELEMENTS
                                if (array_key_exists($fData['index'], $el)) {
                                    if (!array_key_exists($el[$fData['index']], $this->decodeCache)) {
                                        $this->decodeCache[$el[$fData['index']]] = $cUtils->decrypt($el[$fData['index']]);
                                    }
                                    $el[$fData['index']] = $this->decodeCache[$el[$fData['index']]];
                                }
                            }
                        }
                        return $el;
                    },
                    $results
                );
            } catch (EncryptFieldsException $e) {
                if (php_sapi_name() == "cli") {
                    throw $e;
                } else {
                    Utilities::redirect(HTTP_ROOT_DIR . '/error.php?err_msg=' . urlencode($e->getMessage()));
                }
            }
            if (!$isFetchAll) {
                $args['resultAr'] = reset($results);
            } else {
                $args['resultAr'] = $results;
            }
        }

        $event->setArguments($args);
        return $event;
    }

    /**
     * Finds out which field need en/decryption from the sql or PDOStatement
     *
     * @param string|PDOStatement $stmt the query string or PDOStatement
     * @return self
     */
    private function setEncodeDecode(string|PDOStatement $stmt): self
    {
        $encode = [];
        $decode = [];
        $query = strtoupper($stmt instanceof PDOStatement ? $stmt->queryString : $stmt);
        $isDecode = $stmt instanceof PDOStatement && str_starts_with($query, 'SELECT');
        if ($isDecode) {
            foreach (range(0, $stmt->columnCount() - 1) as $columnIndex) {
                $colmeta = $stmt->getColumnMeta($columnIndex);
                $colName = strtolower($colmeta['name']);
                $tableName = strtolower($colmeta['table']);
                if (in_array($colName, FieldsConfig::getFieldsForTable($tableName)['fields'] ?? [])) {
                    $decode[$tableName][] = [
                        'name' => $colName,
                        'index' => $columnIndex,
                    ];
                }
            }
        } else {
            $parsed = new PHPSQLParser($query);
            $sqlCommand = array_key_first($parsed->parsed);
            if (in_array($sqlCommand, ['INSERT', 'UPDATE'])) {
                $isInsert = $sqlCommand == 'INSERT';
                $tables = array_values(
                    array_filter(
                        $parsed->parsed[$sqlCommand],
                        fn ($el) => $el['expr_type'] == 'table'
                    )
                );
                $filterKey = $sqlCommand;
                $filterExpr = null;
                if ($isInsert) {
                    $filterExpr = 'column-list';
                } else {
                    $filterExpr = 'expression';
                    $filterKey = 'SET';
                }
                $colList = array_filter(
                    $parsed->parsed[$filterKey],
                    fn ($el) => $el['expr_type'] == $filterExpr
                );

                if (!empty($tables) && !empty($colList)) {
                    foreach ($tables as $table) {
                        $table['table'] = strtolower($table['table']);
                        foreach ($colList as $columnIndex => $aCol) {
                            foreach ($aCol['sub_tree'] ?? [] as $colDataIndex => $coldata) {
                                if ($coldata['expr_type'] == 'colref') {
                                    $colName = strtolower(end($coldata['no_quotes']['parts']));
                                    if (in_array($colName, FieldsConfig::getFieldsForTable($table['table'])['fields'] ?? [])) {
                                        $encode[$table['table']][] = [
                                            'name' => $colName,
                                            'index' => $isInsert ? $colDataIndex : $columnIndex,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->setEncode($encode)->setDecode($decode);
        return $this;
    }

    /**
     * Get the value of encode
     */
    public function getEncode()
    {
        return $this->encode;
    }

    /**
     * Set the value of encode
     */
    public function setEncode($encode): self
    {
        $this->encode = $encode;

        return $this;
    }

    /**
     * Get the value of decode
     */
    public function getDecode()
    {
        return $this->decode;
    }

    /**
     * Set the value of decode
     */
    public function setDecode($decode): self
    {
        $this->decode = $decode;

        return $this;
    }
}

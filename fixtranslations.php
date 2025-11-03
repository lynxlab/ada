<?php

/**
 * @package     main
 * @author      Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Admin\AdminHelper;

/**
 * Base config file
 */
require_once realpath(__DIR__) . '/config_path.inc.php';
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

function table2CSV($pdo, $table, $orderby = null)
{
    $date = (new DateTimeImmutable())->format('Y-m-d\TH:i:s');
    $sql = "SELECT * FROM $table";
    if (strlen($orderby) > 0) {
        $sql = "$sql ORDER BY `$orderby` ASC;";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $filename = $table . '-' . $date . '.csv';
    echo "Backup $table into $filename" . PHP_EOL;
    $fp = fopen($filename, 'w');
    while ($row = $stmt->fetch()) {
        fputcsv($fp, $row);
    }
    fclose($fp);
}

if (defined('ADA_CLI') && ADA_CLI) {
    $options = [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    $dsnArr = array_map(
        fn ($el) => trim($el, '/'),
        parse_url(ADA_COMMON_DB_TYPE . '://' . ADA_COMMON_DB_USER . ':'
            . ADA_COMMON_DB_PASS . '@' . ADA_COMMON_DB_HOST . '/'
            . ADA_COMMON_DB_NAME)
    );
    $commonpdo = AdminHelper::checkDB(
        ['HOST' => $dsnArr['host'], 'USER' => $dsnArr['user'], 'PASSWORD' => $dsnArr['pass'],],
        $dsnArr['path'],
        $options
    );
    $tblprefix = "messaggi_";
    $testoMessaggio = 'testo_messaggio';
    $idMessaggio = 'id_messaggio';
    $sistematbl = "{$tblprefix}sistema";
    $maxLev = 2;

    if ($commonpdo instanceof PDO) {
        $commonpdo->beginTransaction();
        try {
            $stmt = $commonpdo->prepare("SELECT COUNT(`$idMessaggio`) FROM `$sistematbl`;");
            $stmt->execute();
            $sistemaCount = (int) $stmt->fetchColumn(0);

            $stmt = $commonpdo->prepare("SELECT * FROM $sistematbl ORDER BY `$idMessaggio` ASC;");
            $stmt->execute();
            $sistemaRows = $stmt->fetchAll();

            $stmt = $commonpdo->prepare("SELECT `codice_lingua` FROM `lingue`;");
            $stmt->execute();
            $langs = array_map(fn ($l) => $l['codice_lingua'], $stmt->fetchAll());
            $langs = ['it', 'en'];

            table2CSV($commonpdo, $sistematbl, $idMessaggio);

            foreach ($langs as $lang) {
                $translationtbl = "{$tblprefix}{$lang}";
                table2CSV($commonpdo, $translationtbl, $idMessaggio);
                $stmt = $commonpdo->prepare("SELECT MAX(`$idMessaggio`) FROM `$translationtbl`;");
                $stmt->execute();
                $maxid = (int) $stmt->fetchColumn(0);
                echo "TABLE $translationtbl HAS MAXID $maxid" . PHP_EOL;

                $stmt = $commonpdo->prepare("SELECT * FROM `$sistematbl` WHERE `$idMessaggio` > ?;");
                $stmt->execute([$maxid]);

                $idx = 0;
                foreach ($stmt->fetchAll() as $idx => $toAdd) {
                    /**
                     * INSERT into tranlastion table all rows from messaggi_sistema
                     * with an ID greater than the max ID in translation table
                     */
                    $insert = "INSERT INTO `$translationtbl` VALUES (?,?);";
                    $stmt = $commonpdo->prepare($insert);
                    $stmt->execute(array_values($toAdd));
                }
                if ($idx++ > 0) {
                    echo "ADDED $idx ROWS TO $translationtbl" . PHP_EOL . PHP_EOL;
                }

                $stmt = $commonpdo->prepare("SELECT COUNT(`$idMessaggio`) FROM `$translationtbl`;");
                $stmt->execute();
                $transCount = (int) $stmt->fetchColumn(0);
                echo "$translationtbl HAS $transCount ROWS, $sistematbl HAS $sistemaCount " . ($transCount != $sistemaCount ? 'NOT OK!!' : 'OK') . PHP_EOL;

                if ($transCount != $sistemaCount) {
                    /**
                     * Print missing IDs:
                     * - found in translation table and not found in messaggi_sistema
                     * - found in messaggi_sistema and not in translation table
                     */
                    $stmt = $commonpdo->prepare("SELECT * FROM `$translationtbl` ORDER BY `$idMessaggio` ASC;");
                    $stmt->execute();
                    $transAll = $stmt->fetchAll();
                    $transIDS = array_column($transAll, $idMessaggio);
                    $sistemaIDS = array_column($sistemaRows, $idMessaggio);

                    echo "ROWS IN $translationtbl BUT NOT IN $sistematbl" . PHP_EOL;
                    $diff = array_diff($transIDS, $sistemaIDS);
                    $diffStr = array_map(
                        fn ($e) => $e[$testoMessaggio],
                        array_filter(
                            $transAll,
                            fn ($e) => in_array($e[$idMessaggio], $diff)
                        )
                    );
                    print_r(array_combine($diff, $diffStr));
                    if (!empty($diff)) {
                        foreach ($diff as $id) {
                            $stmt = $commonpdo->prepare("DELETE FROM `$translationtbl` WHERE `$idMessaggio`=?;");
                            $stmt->execute([$id]);
                        }
                        echo count($diff) . " ROWS DELETED" . PHP_EOL;
                    }

                    echo "ROWS IN $sistematbl BUT NOT IN $translationtbl" . PHP_EOL;
                    $diff = array_diff($sistemaIDS, $transIDS);
                    $diffStr = array_map(
                        fn ($e) => $e[$testoMessaggio],
                        array_filter(
                            $sistemaRows,
                            fn ($e) => in_array($e[$idMessaggio], $diff)
                        )
                    );
                    print_r(array_combine($diff, $diffStr));
                }

                foreach ($sistemaRows as $sistemaRow) {
                    if (strlen($sistemaRow[$testoMessaggio]) > 0) {
                        $stmt = $commonpdo->prepare("SELECT `$testoMessaggio` FROM `$translationtbl` WHERE `$idMessaggio` = ?;");
                        $stmt->execute([$sistemaRow[$idMessaggio]]);
                        $translated = $stmt->fetchColumn(0);
                        if (false === $translated) {
                            echo "NOTFOUND STRING: $translationtbl with id=" . $sistemaRow[$idMessaggio] . "(*" . $sistemaRow[$testoMessaggio] . "*)" . PHP_EOL;
                            /**
                             * INSERT into translation table the ID that was found
                             * in messaggi_sistema and not in the translation table
                             */
                            $insert = "INSERT INTO `$translationtbl` VALUES (?,?);";
                            $stmt = $commonpdo->prepare($insert);
                            $stmt->execute(array_values($sistemaRow));
                        } elseif (strlen($translated) == 0) {
                            echo "EMPTY STRING: $translationtbl with id=" . $sistemaRow[$idMessaggio] . ", setting to *" . $sistemaRow[$testoMessaggio] . "*" . PHP_EOL;
                            /**
                             * translation table had an empty string for the id,
                             * update the messagge with the one in messaggi_sistema
                             */
                            $update = "UPDATE `$translationtbl` SET `$testoMessaggio`=? WHERE `$idMessaggio`=?;";
                            $stmt = $commonpdo->prepare($update);
                            $stmt->execute([$sistemaRow[$testoMessaggio], $sistemaRow[$idMessaggio]]);
                        } elseif (strcasecmp($translated, $sistemaRow[$testoMessaggio]) !== 0) {
                            /**
                             * If the string is not the same, print
                             * the levenshtein distance if greater than maxLev
                             */
                            $lev = levenshtein($sistemaRow[$testoMessaggio], $translated);
                            if ($lev > $maxLev) {
                                echo sprintf("LEV IS %2s: %9s ", $lev, "(id=" . $sistemaRow[$idMessaggio] . ")");
                                echo "$sistematbl.*" . $sistemaRow[$testoMessaggio] . "*" . PHP_EOL;
                                echo "                     $translationtbl.*$translated*" . PHP_EOL;
                            }
                        }
                    } else {
                        /**
                         * messaggi_sistema had an empty string, print the id
                         */
                        echo "EMPTY STRING: $sistematbl with id=" . $sistemaRow[$idMessaggio];
                        $stmt = $commonpdo->prepare("DELETE FROM `$sistematbl` WHERE `$idMessaggio`=?;");
                        if ($stmt->execute([$sistemaRow[$idMessaggio]])) {
                            echo " DELETED";
                        }
                        echo PHP_EOL;
                    }
                }
                echo PHP_EOL;
            }
            $commonpdo->commit();
        } catch (Exception $e) {
            echo "ERROR " . $e->getMessage() . PHP_EOL;
            echo "Rolling back database transaction (useless if tables were not InnoDB)" . PHP_EOL;
            $commonpdo->rollBack();
        }
    }
} else {
    die('only cli execution');
}

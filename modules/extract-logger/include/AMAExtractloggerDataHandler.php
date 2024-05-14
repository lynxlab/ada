<?php

/**
 * EXTRACT-LOGGER MODULE.
 *
 * @package        extract-logger module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           oauth2
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\ExtractLogger;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;

class AMAExtractloggerDataHandler extends AMACommonDataHandler
{
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_extractlogger_';

    public function logData($script, $class, $data)
    {
        $tolog = [];
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    $tolog[$key] = $value::class;
                } else {
                    $tolog[$key] = gettype($value);
                }
            }
        }

        $sql = "SELECT `data`, `getdata`, `postdata` FROM `" . self::PREFIX . "log` WHERE `script` = :script AND `class` = :class";
        $found = $this->getAllPrepared($sql, compact('script', 'class'), AMA_FETCH_ASSOC);
        if (empty($found)) {
            $found = [];
            $foundget = [];
            $foundpost = [];
        } else {
            $founddb = reset($found);
            $found = json_decode($founddb['data'], true);
            $foundget = json_decode($founddb['getdata'], true);
            $foundpost = json_decode($founddb['postdata'], true);
        }
        $data = json_encode(array_merge($found, $tolog));
        $getdata = json_encode(array_merge($foundget, static::augmentGetPost($_GET)));
        $postdata = json_encode(array_merge($foundpost, static::augmentGetPost($_POST)));
        $sql = "INSERT INTO `" . self::PREFIX . "log` (`script`, `class`, `data`, `getdata`, `postdata`) " .
            "VALUES (:script, :class, :data, :getdata, :postdata) " .
            " ON DUPLICATE KEY UPDATE `data` = :data, `getdata` = :getdata, `postdata` = :postdata";
        $this->executeCriticalPrepared($sql, compact('script', 'class', 'data', 'getdata', 'postdata'));
    }

    private static function augmentGetPost($data)
    {
        $retarr = [];
        array_walk($data, function ($value, $key) use (&$retarr) {
            if (is_object($value)) {
                $type = $value::class;
            } elseif (is_numeric($value)) {
                $type = (filter_var($value, FILTER_VALIDATE_INT) !== false) ? 'int' : 'float';
            } else {
                $type = gettype($value);
            }
            $retarr[$key] = compact('type', 'value');
        });
        return $retarr;
    }
}
